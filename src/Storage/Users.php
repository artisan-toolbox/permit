<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Storage;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Resolve subjects through the Eloquent provider configured for Permit.
 */
final readonly class Users
{
    public function __construct(private Repository $config) {}

    public function guard(): string
    {
        return $this->config->string('permit.guard', 'web');
    }

    public function model(): Model&Authenticatable
    {
        $provider = $this->config->string('auth.guards.'.$this->guard().'.provider', '');
        $class = $this->config->string('auth.providers.'.$provider.'.model', '');

        throw_if($this->config->get('auth.providers.'.$provider.'.driver') !== 'eloquent'
            || ! is_subclass_of($class, Model::class)
            || ! is_a($class, Authenticatable::class, true), LogicException::class, 'The Permit guard must use an Eloquent user provider.');

        return new $class;
    }

    public function supported(mixed $user): (Model&Authenticatable)|null
    {
        if (! $user instanceof Model || ! $user instanceof Authenticatable || ! $user->exists) {
            return null;
        }
        $id = $this->rawId($user);

        if ((! is_int($id) && ! is_string($id)) || ! $this->validId($id, $user)) {
            return null;
        }
        $model = $this->model();

        return $user::class === $model::class
            && $user->getConnection()->getName() === $model->getConnection()->getName()
            && $user->getTable() === $model->getTable() ? $user : null;
    }

    public function find(int|string|Model $subject): Model&Authenticatable
    {
        if ($subject instanceof Model) {
            throw_if($this->supported($subject) === null, InvalidArgumentException::class, 'The Permit subject must be a persisted model from its configured user provider.');
            $subject = $this->id($subject);
        }
        $model = $this->model();

        if (! $this->validId($subject, $model)) {
            throw (new ModelNotFoundException)->setModel($model::class, [$subject]);
        }

        $user = $this->queryOnPrimary($model)->findOrFail($subject);

        if ($user instanceof Authenticatable) {
            return $user;
        }

        throw new LogicException('The Permit user query must return an authenticatable model.');
    }

    public function id(Model $user): string
    {
        $id = $this->rawId($user);

        throw_if(! is_int($id) && ! is_string($id), InvalidArgumentException::class, 'A Permit user requires an integer or string key.');

        return (string) $id;
    }

    private function rawId(Model $user): mixed
    {
        return $user->getRawOriginal($user->getKeyName()) ?? $user->getKey();
    }

    public function connection(): Connection
    {
        return $this->model()->getConnection();
    }

    /** @return Builder<Model> */
    public function query(): Builder
    {
        return $this->queryOnPrimary($this->model());
    }

    public function validId(int|string $id, Model $model): bool
    {
        if (in_array($model->getKeyType(), ['int', 'integer'], true)) {
            return preg_match('/^(?:0|-?[1-9][0-9]*)$/D', (string) $id) === 1
                && filter_var($id, FILTER_VALIDATE_INT) !== false;
        }

        return isset(class_uses_recursive($model)[HasUlids::class])
            ? Str::isUlid($id)
            : Str::isUuid($id);
    }

    /** @return Builder<Model> */
    private function queryOnPrimary(Model $model): Builder
    {
        return $model->newQuery()->useWritePdo();
    }
}
