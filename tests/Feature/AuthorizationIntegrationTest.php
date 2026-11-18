<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\Fixtures\IntegrationUser;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

it('keeps committed Gate decisions authoritative across database reads and cache writes', function () {
    $driver = getenv('PERMIT_INTEGRATION_DB') ?: 'sqlite';
    $cacheDriver = getenv('PERMIT_INTEGRATION_CACHE') ?: 'array';
    $prefix = 'permit_it_'.Str::lower(Str::random(10)).'_';
    $database = match ($driver) {
        'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
        'mariadb', 'mysql', 'pgsql' => [
            'driver' => $driver,
            'host' => getenv('PERMIT_INTEGRATION_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('PERMIT_INTEGRATION_DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306),
            'unix_socket' => getenv('PERMIT_INTEGRATION_DB_SOCKET') ?: '',
            'database' => 'permit_testing',
            'username' => 'permit_test',
            'password' => 'permit_test',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
        ],
        default => throw new InvalidArgumentException("Unknown Permit test database [$driver]."),
    };
    $store = match ($cacheDriver) {
        'array' => ['driver' => 'array'],
        'redis' => ['driver' => 'redis', 'connection' => 'permit_integration'],
        'memcached' => ['driver' => 'memcached', 'servers' => [['host' => '127.0.0.1', 'port' => 11211]]],
        default => throw new InvalidArgumentException("Unknown Permit test cache [$cacheDriver]."),
    };
    config([
        'database.connections.permit_integration' => $database + ['prefix' => $prefix],
        'database.redis.client' => 'phpredis',
        'database.redis.permit_integration' => [
            'host' => getenv('PERMIT_INTEGRATION_REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('PERMIT_INTEGRATION_REDIS_PORT') === false ? 6379 : getenv('PERMIT_INTEGRATION_REDIS_PORT')),
            'database' => 0,
        ],
        'auth.guards.web.provider' => 'users',
        'auth.providers.users' => ['driver' => 'eloquent', 'model' => IntegrationUser::class],
        'cache.default' => 'permit_integration',
        'cache.stores.permit_integration' => $store,
        'cache.prefix' => $prefix,
        'permit.cache.enabled' => true,
        'permit.cache.store' => $cacheDriver === 'array' ? 'permit_integration' : null,
        'permit.table' => 'user_permissions',
    ]);

    $connection = DB::connection('permit_integration');
    $schema = $connection->getSchemaBuilder();
    $cache = Cache::store('permit_integration');
    $entries = [];

    try {
        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        expect(Artisan::call('migrate', [
            '--database' => 'permit_integration',
            '--path' => dirname(__DIR__, 2).'/database/migrations',
            '--realpath' => true,
            '--force' => true,
        ]))->toBe(0);

        $first = IntegrationUser::query()->create(['name' => 'Ada']);
        $second = IntegrationUser::query()->create(['name' => 'Noor']);
        $ability = 'user_permissions::read';
        expect(Gate::forUser($first)->allows($ability))->toBeFalse();
        Permit::add($first, $ability);
        Permit::add($second, $ability);
        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$entries): void {
            if ($event->storeName === 'permit_integration') {
                $entries[$event->key] = $event->value;
            }
        });

        expect(Gate::forUser($first)->allows($ability))->toBeTrue();
        expect(Gate::forUser($second)->allows($ability))->toBeTrue();
        expect($entries)->toHaveCount(2);
        [$firstKey, $secondKey] = array_keys($entries);
        $staleGrant = $entries[$firstKey];

        Permit::remove($first, $ability);
        expect($cache->get($firstKey))->toBeNull();
        expect($cache->get($secondKey))->not->toBeNull();

        // A cache fill that began before the revocation can arrive after eviction.
        $cache->put($firstKey, $staleGrant, 30);
        app()->forgetScopedInstances();
        expect(Gate::forUser($first)->allows($ability))->toBeFalse();
        expect(Gate::forUser($second)->allows($ability))->toBeTrue();

        $connection->beginTransaction();

        try {
            Permit::add($first, $ability);
            expect(Gate::forUser($first)->allows($ability))->toBeTrue();
        } finally {
            $connection->rollBack();
        }

        app()->forgetScopedInstances();
        expect(Gate::forUser($first)->allows($ability))->toBeFalse();
        expect(Gate::forUser($second)->allows($ability))->toBeTrue();

        $assignment = $connection->table('user_permissions')->where('user_id', $first->getKey());
        $importRevision = (string) Str::uuid();
        $assignment->update([
            'permissions' => '{"user_permissions::read":false,"legacy::export":false,"legacy_tools::write":true}',
            'revision' => $importRevision,
        ]);
        $schema->table('user_permissions', function (Blueprint $table): void {
            $table->renamePermissionGroup('legacy', 'exports');
        });
        $renamed = $assignment->first();
        expect($renamed)->not->toBeNull();
        expect(json_decode((string) $renamed?->permissions, true, flags: JSON_THROW_ON_ERROR))->toEqual([
            'user_permissions::read' => false,
            'legacy_tools::write' => true,
            'exports::export' => false,
        ]);
        expect($renamed?->revision)->not->toBe($importRevision);
    } finally {
        foreach (array_keys($entries) as $key) {
            $cache->forget($key);
        }
        $schema->dropIfExists('user_permissions');
        $schema->dropIfExists('users');
        $schema->dropIfExists('migrations');
        DB::purge('permit_integration');
    }
});
