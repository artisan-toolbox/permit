<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Storage;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Cache\Factory as Cache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Throwable;
use UnexpectedValueException;

final class AssignmentStore
{
    /** @var array<string, Assignments> */
    private array $memo = [];

    public function __construct(
        private readonly Repository $config,
        private readonly Cache $cache,
        private readonly Users $users,
    ) {}

    public function snapshot(Model $user): Assignments
    {
        $connection = $this->users->connection();
        $id = $this->users->id($user);

        if ($connection->transactionLevel() > 0) {
            return $this->read($connection, $id);
        }

        $key = $this->cacheKey($connection, $id);

        return $this->memo[$key] ??= $this->load($connection, $id, $key);
    }

    public function fresh(Model $user): Assignments
    {
        return $this->read($this->users->connection(), $this->users->id($user));
    }

    /** @param array<string, bool> $changes */
    public function merge(Model $user, array $changes): void
    {
        $this->write($user, fn (Assignments $current): Assignments => $current->apply($changes), retry: true);
    }

    /** @param array<string, bool> $values */
    public function replace(Model $user, ?string $revision, array $values): void
    {
        $this->write($user, function (Assignments $current) use ($revision, $values): Assignments {
            throw_if($current->revision !== $revision, AssignmentConflict::class, 'The Permit row changed before it could be replaced.');

            return (new Assignments)->apply($values);
        }, retry: false);
    }

    private function load(Connection $connection, string $id, string $key): Assignments
    {
        $cached = $this->cached($key);

        if ($cached !== null) {
            $revision = $this->query($connection, $id)->value('revision');

            if ($revision === null) {
                return new Assignments;
            }

            if ($revision === $cached->revision) {
                return $cached;
            }
        }

        $current = $this->read($connection, $id);

        if ($current->revision !== null) {
            try {
                $this->shared()?->put($key, [
                    'revision' => $current->revision,
                    'permissions' => $current->permissions,
                    'roles' => $current->roles,
                ], $this->config->integer('permit.cache.ttl', 300));
            } catch (Throwable $error) {
                report($error);
            }
        }

        return $current;
    }

    private function cached(string $key): ?Assignments
    {
        try {
            $entry = $this->shared()?->get($key);

            if (! is_array($entry) || ! is_string($entry['revision'] ?? null) || ! Str::isUuid($entry['revision'])) {
                return null;
            }

            return new Assignments(
                $entry['revision'],
                Assignments::validate($entry['permissions'] ?? null, 'permissions'),
                Assignments::validate($entry['roles'] ?? null, 'roles'),
            );
        } catch (UnexpectedValueException) {
            return null;
        } catch (Throwable $error) {
            report($error);

            return null;
        }
    }

    private function read(Connection $connection, string $id): Assignments
    {
        $row = $this->query($connection, $id)->first(['revision', 'permissions', 'roles']);

        if ($row === null) {
            return new Assignments;
        }

        throw_if(! is_string($row->revision) || ! Str::isUuid($row->revision), UnexpectedValueException::class, 'A stored Permit assignment has an invalid revision.');

        return new Assignments(
            $row->revision,
            Assignments::decode($row->permissions, 'permissions'),
            Assignments::decode($row->roles, 'roles'),
        );
    }

    /** @param Closure(Assignments): Assignments $change */
    private function write(Model $user, Closure $change, bool $retry): void
    {
        $connection = $this->users->connection();
        $id = $this->users->id($user);
        $key = $this->cacheKey($connection, $id);
        $nested = $connection->transactionLevel() > 0;
        $maximumAttempts = $retry && ! $nested ? 3 : 1;
        for ($attempt = 1; ; $attempt++) {
            try {
                $save = function () use ($connection, $id, $change, $key, $nested): bool {
                    $current = $this->read($connection, $id);
                    $next = $change($current);

                    if ($current->permissions == $next->permissions && $current->roles == $next->roles) {
                        return false;
                    }

                    $values = [
                        'revision' => (string) Str::uuid(),
                        'permissions' => json_encode((object) $next->permissions, JSON_THROW_ON_ERROR),
                        'roles' => json_encode((object) $next->roles, JSON_THROW_ON_ERROR),
                    ];

                    if ($current->revision === null) {
                        $connection->table($this->table())->insert(['user_id' => $id, ...$values]);
                    } elseif ($this->query($connection, $id)->where('revision', $current->revision)->update($values) !== 1) {
                        throw new AssignmentConflict('The Permit assignment was changed concurrently.');
                    }

                    if ($nested) {
                        $connection->afterCommit(fn () => $this->evict($key));
                    }

                    return true;
                };
                $changed = $nested ? $connection->transaction($save) : $save();

                break;
            } catch (AssignmentConflict|UniqueConstraintViolationException $error) {
                if ($attempt < $maximumAttempts) {
                    continue;
                }

                throw new AssignmentConflict('The Permit assignment was changed concurrently.', previous: $error);
            }
        }

        unset($this->memo[$key]);

        if ($changed && ! $nested) {
            $this->evict($key);
        }
    }

    private function evict(string $key): void
    {
        try {
            $this->shared()?->forget($key);
        } catch (Throwable $error) {
            report($error);
        }
    }

    private function query(Connection $connection, string $id): Builder
    {
        return $connection->table($this->table())->useWritePdo()->where('user_id', $id);
    }

    private function table(): string
    {
        return $this->config->string('permit.table', 'user_permissions');
    }

    private function shared(): ?CacheRepository
    {
        if (! $this->config->boolean('permit.cache.enabled', true)) {
            return null;
        }

        $store = $this->config->get('permit.cache.store');

        if (is_string($store)) {
            return $this->cache->store($store);
        }
        $default = $this->config->string('cache.default', 'array');

        return in_array($this->config->get('cache.stores.'.$default.'.driver'), ['redis', 'memcached'], true)
            ? $this->cache->store($default)
            : null;
    }

    private function cacheKey(Connection $connection, string $id): string
    {
        return 'permit:assignments:'.hash('sha256', json_encode([
            $this->config->get('cache.prefix'),
            $connection->getName(),
            $connection->getDatabaseName(),
            $connection->getTablePrefix(),
            $this->table(),
            $this->users->model()::class,
            $id,
        ], JSON_THROW_ON_ERROR));
    }
}
