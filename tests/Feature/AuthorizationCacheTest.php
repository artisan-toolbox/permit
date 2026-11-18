<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

final class AuthorizationCacheTest extends AuthorizationTestCase
{
    public function test_a_malformed_shared_entry_is_ignored_even_when_its_revision_matches(): void
    {
        $user = $this->user();
        config(['permit.cache.store' => 'array']);
        Permit::remove($user, 'users::print');
        $entry = null;
        Event::listen(KeyWritten::class, static function (KeyWritten $event) use (&$entry): void {
            $entry ??= $event;
        });

        self::assertFalse(Gate::forUser($user)->allows('users::print'));
        self::assertInstanceOf(KeyWritten::class, $entry);
        Cache::store('array')->put($entry->key, [
            ...$entry->value,
            'permissions' => ['users::print' => 'true'],
        ], 30);
        app()->forgetScopedInstances();

        self::assertFalse(Gate::forUser($user)->allows('users::print'));
    }

    public function test_a_warm_cache_cannot_grant_access_when_the_authoritative_read_fails(): void
    {
        $user = $this->user();
        config(['permit.cache.store' => 'array']);
        Permit::add($user, 'users::read');
        self::assertTrue(Gate::forUser($user)->allows('users::read'));

        app()->forgetScopedInstances();
        Schema::drop('user_permissions');

        $this->expectException(QueryException::class);
        Gate::forUser($user)->allows('users::read');
    }

    public function test_swapped_shared_maps_cannot_hide_an_explicit_denial(): void
    {
        $user = $this->user();
        config(['permit.cache.store' => 'array']);
        Permit::remove($user, ['users::print', 'role::auditor']);
        $entry = null;
        Event::listen(KeyWritten::class, static function (KeyWritten $event) use (&$entry): void {
            $entry ??= $event;
        });
        self::assertTrue(Gate::forUser($user)->none(['users::print', 'role::auditor']));
        self::assertInstanceOf(KeyWritten::class, $entry);
        Cache::store('array')->put($entry->key, [
            ...$entry->value,
            'permissions' => $entry->value['roles'],
            'roles' => $entry->value['permissions'],
        ], 30);
        app()->forgetScopedInstances();

        self::assertTrue(Gate::forUser($user)->none(['users::print', 'role::auditor']));
    }

    #[DataProvider('wrongStoragePartitions')]
    public function test_persisted_entries_in_the_wrong_map_fail_closed(string $ability, string $permissions, string $roles): void
    {
        $user = $this->user();
        DB::table('user_permissions')->insert([
            'user_id' => $user->getKey(),
            'revision' => (string) Str::uuid(),
            'permissions' => $permissions,
            'roles' => $roles,
        ]);

        $this->expectException(UnexpectedValueException::class);
        Gate::forUser($user)->allows($ability);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function wrongStoragePartitions(): iterable
    {
        yield 'role in permissions' => ['role::auditor', '{"role::auditor":false}', '{}'];
        yield 'permission in roles' => ['users::print', '{}', '{"users::print":false}'];
    }

    public function test_valid_retired_assignments_survive_partial_writes(): void
    {
        $user = $this->user();
        DB::table('user_permissions')->insert([
            'user_id' => $user->getKey(),
            'revision' => (string) Str::uuid(),
            'permissions' => '{"retired::archive":false}',
            'roles' => '{"role::retired":true}',
        ]);

        Permit::add($user, 'users::read');
        $row = DB::table('user_permissions')->first();
        self::assertNotNull($row);
        self::assertEquals(['retired::archive' => false, 'users::read' => true], json_decode($row->permissions, true));
        self::assertEquals(['role::retired' => true], json_decode($row->roles, true));
    }

    public function test_eviction_follows_the_assignment_transaction_with_an_unrelated_transaction_open(): void
    {
        $user = $this->user();
        config([
            'permit.cache.store' => 'array',
            'database.connections.permit_unrelated' => ['driver' => 'sqlite', 'database' => ':memory:'],
        ]);
        Permit::add($user, 'users::read');
        $entry = null;
        Event::listen(KeyWritten::class, static function (KeyWritten $event) use (&$entry): void {
            $entry ??= $event;
        });
        self::assertTrue(Gate::forUser($user)->allows('users::read'));
        self::assertInstanceOf(KeyWritten::class, $entry);
        $assignment = DB::connection();
        $unrelated = DB::connection('permit_unrelated');
        $assignment->beginTransaction();
        $unrelated->beginTransaction();

        try {
            Permit::remove($user, 'users::read');
            self::assertNotNull(Cache::store('array')->get($entry->key));
            $assignment->commit();
            self::assertNull(Cache::store('array')->get($entry->key));
            $unrelated->rollBack();
            self::assertFalse(Gate::forUser($user)->allows('users::read'));
        } finally {
            while ($assignment->transactionLevel() > 0) {
                $assignment->rollBack();
            }

            if ($unrelated->transactionLevel() > 0) {
                $unrelated->rollBack();
            }
        }
    }

    public function test_repeated_gate_checks_read_the_assignment_once_in_a_scope(): void
    {
        $user = $this->user();
        config(['permit.cache.enabled' => false]);
        Permit::add($user, 'users::read');

        DB::enableQueryLog();

        try {
            self::assertTrue(Gate::forUser($user)->allows('users::read'));
            self::assertTrue(Gate::forUser($user)->allows('users::print'));
            self::assertFalse(Gate::forUser($user)->allows('role::admin'));
            self::assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }
}
