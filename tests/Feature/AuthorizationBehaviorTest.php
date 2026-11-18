<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use ArtisanToolbox\Permit\Tests\Fixtures\PermitTargetPolicy;
use ArtisanToolbox\Permit\Tests\Fixtures\PermitTestUser;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthorizationBehaviorTest extends AuthorizationTestCase
{
    #[DataProvider('decisions')]
    public function test_assignment_overrides_defaults_for_permissions_and_roles(string $ability, ?bool $stored, bool $expected): void
    {
        $user = $this->user();

        if ($stored !== null) {
            $stored ? Permit::add($user, $ability) : Permit::remove($user->getKey(), $ability);
        } else {
            Permit::add($user, 'user_permissions::read');
        }

        self::assertSame($expected, Gate::forUser($user)->allows($ability));
        self::assertFalse(Gate::forUser(new GenericUser(['id' => $user->getKey()]))->allows($ability));
    }

    /** @return iterable<string, array{string, ?bool, bool}> */
    public static function decisions(): iterable
    {
        foreach (['users::print' => true, 'role::auditor' => true, 'users::read' => false, 'role::admin' => false] as $key => $default) {
            yield "{$key}: missing" => [$key, null, $default];
            yield "{$key}: granted" => [$key, true, true];
            yield "{$key}: denied" => [$key, false, false];
        }
    }

    public function test_guest_and_missing_row_decisions_keep_roles_independent_of_permissions(): void
    {
        $user = $this->user();
        Gate::define('other::allow', static fn (Authenticatable $actor): bool => true);
        self::assertFalse(Gate::allows('users::print'));
        self::assertFalse(Gate::allows('role::auditor'));
        self::assertTrue(Gate::forUser($user)->allows('users::print'));
        self::assertFalse(Gate::forUser($user)->allows('users::read'));
        self::assertFalse(Gate::forUser($user)->allows('users::delete'));
        self::assertTrue(Gate::forUser($user)->allows('other::allow'));

        Permit::add($user, 'role::admin');
        self::assertTrue(Gate::forUser($user)->allows('role::admin'));
        self::assertFalse(Gate::forUser($user)->allows('users::read'));
    }

    public function test_an_out_of_range_persisted_integer_key_does_not_fall_back_to_a_grant(): void
    {
        $record = new PermitTestUser;
        $record->setRawAttributes(['id' => '9223372036854775808'], sync: true);
        $record->exists = true;

        self::assertFalse(Gate::forUser($record)->allows('users::print'));
    }

    public function test_facade_validates_every_key_before_writing(): void
    {
        $user = $this->user();
        $this->expectException(InvalidArgumentException::class);

        try {
            Permit::add($user, ['users::read', 'users::deleted']);
        } finally {
            $this->assertDatabaseCount('user_permissions', 0);
        }
    }

    public function test_facade_applies_bulk_changes_and_keeps_idempotent_revisions(): void
    {
        $user = $this->user();
        Permit::allowAll($user);
        self::assertTrue(Gate::forUser($user)->allows(['users::read', 'role::admin', 'user_permissions::update']));
        $revision = DB::table('user_permissions')->value('revision');
        Permit::allowAll($user->getKey());
        self::assertSame($revision, DB::table('user_permissions')->value('revision'));

        Permit::denyAll($user);
        self::assertTrue(Gate::forUser($user)->none(['users::print', 'role::auditor', 'user_permissions::read']));
        self::assertNotSame($revision, DB::table('user_permissions')->value('revision'));
    }

    public function test_scope_and_assignments_are_independent_of_host_gate_overrides(): void
    {
        $actor = $this->user();
        $subject = $this->user('Read only');
        $protected = $this->user('Protected');
        config(['permit.management.target_policy' => PermitTargetPolicy::class]);
        Gate::before(static fn (Authenticatable $actor, string $ability): ?bool => in_array($ability, ['user_permissions::read', 'user_permissions::update', 'users::read'], true) ? true : null);
        self::assertTrue(Gate::forUser($subject)->allows('users::read'));

        $this->actingAs($actor)->getJson($this->endpoint($subject))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('canUpdate', false)
            ->assertJsonPath('values.users::read', false)
            ->assertJsonPath('values.users::print', true)
            ->assertJsonMissingPath('password');

        $this->getJson($this->endpoint($protected))->assertNotFound();
        $this->getJson('/permit/user-permissions?user=9999')->assertNotFound();
        $this->getJson('/permit/user-permissions?user=9223372036854775808')->assertNotFound();
        $this->getJson('/permit/user-permissions?user=01')->assertNotFound();
        $this->putJson($this->endpoint($subject), ['revision' => 'invalid'])->assertNotFound();
        $this->assertDatabaseCount('user_permissions', 0);
    }

    public function test_read_and_update_access_are_checked_again_after_opening_the_editor(): void
    {
        $actor = $this->user();
        $subject = $this->user('Noor');

        $this->getJson($this->endpoint($subject))->assertUnauthorized();
        $this->putJson($this->endpoint($subject), [])->assertUnauthorized();
        $this->actingAs($actor)->getJson($this->endpoint($subject))->assertForbidden();
        Permit::add($actor, ['user_permissions::read', 'user_permissions::update']);

        $current = $this->getJson($this->endpoint($subject))->assertOk()->assertJsonPath('canUpdate', true);
        $values = $current->json('values');
        $values['users::print'] = false;
        $values['role::admin'] = true;
        $payload = ['revision' => $current->json('revision'), 'values' => $values];
        $this->putJson($this->endpoint($subject), $payload)->assertNoContent();
        $this->putJson($this->endpoint($subject), $payload)->assertConflict();
        self::assertFalse(Gate::forUser($subject)->allows('users::print'));
        self::assertTrue(Gate::forUser($subject)->allows('role::admin'));

        $current = $this->getJson($this->endpoint($subject))->assertOk();
        $revision = DB::table('user_permissions')->where('user_id', $subject->getKey())->value('revision');
        Permit::remove($actor, 'user_permissions::update');
        $this->putJson($this->endpoint($subject), ['revision' => $current->json('revision'), 'values' => $values])->assertForbidden();
        self::assertSame($revision, DB::table('user_permissions')->where('user_id', $subject->getKey())->value('revision'));
    }

    public function test_the_real_csrf_middleware_rejects_an_unverified_write(): void
    {
        $admin = $this->user();
        $subject = $this->user();
        Permit::add($admin, ['user_permissions::read', 'user_permissions::update']);
        $this->actingAs($admin);
        $current = $this->getJson($this->endpoint($subject))->assertOk();
        $payload = ['revision' => $current->json('revision'), 'values' => $current->json('values')];

        $this->app->instance('env', 'local');
        $this->putJson($this->endpoint($subject), $payload, ['Sec-Fetch-Site' => 'cross-site'])->assertStatus(419);
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $subject->getKey()]);
        $this->putJson($this->endpoint($subject), $payload, ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
        $this->assertDatabaseHas('user_permissions', ['user_id' => $subject->getKey()]);
    }

    public function test_invalid_payloads_never_create_a_subject_assignment(): void
    {
        $admin = $this->user();
        $subject = $this->user();
        Permit::add($admin, ['user_permissions::read', 'user_permissions::update']);
        $this->actingAs($admin);
        $current = $this->getJson($this->endpoint($subject))->assertOk();
        $values = $current->json('values');
        $revision = $current->json('revision');

        foreach (['true', 1, null, []] as $invalid) {
            $this->putJson($this->endpoint($subject), ['revision' => $revision, 'values' => [...$values, 'users::read' => $invalid]])->assertUnprocessable();
        }
        $this->putJson($this->endpoint($subject), ['revision' => $revision, 'values' => [...$values, 'users::ghost' => false]])->assertUnprocessable();
        unset($values['users::read']);
        $this->putJson($this->endpoint($subject), ['revision' => $revision, 'values' => $values])->assertUnprocessable();
        $this->putJson($this->endpoint($subject), ['revision' => $revision, 'values' => []])->assertUnprocessable();
        $this->call('PUT', $this->endpoint($subject), server: ['CONTENT_TYPE' => 'application/json'], content: '{')->assertUnprocessable();
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $subject->getKey()]);
    }

    public function test_a_concurrent_first_write_merges_the_facades_intended_changes(): void
    {
        $subject = $this->user();
        $this->onNextAssignmentRead((string) $subject->getKey(), static fn () => Permit::add($subject, 'role::admin'));

        Permit::add($subject, 'users::read');

        self::assertTrue(Gate::forUser($subject)->allows(['users::read', 'role::admin']));
        $this->assertDatabaseCount('user_permissions', 1);
    }

    public function test_a_competing_first_save_returns_conflict_without_replacing_the_winner(): void
    {
        $admin = $this->user();
        $subject = $this->user();
        Permit::add($admin, ['user_permissions::read', 'user_permissions::update']);
        $this->actingAs($admin);
        $current = $this->getJson($this->endpoint($subject))->assertOk();
        $this->onNextAssignmentRead((string) $subject->getKey(), static fn () => Permit::add($subject, 'role::admin'));

        $this->putJson($this->endpoint($subject), ['revision' => $current->json('revision'), 'values' => $current->json('values')])
            ->assertConflict();

        self::assertTrue(Gate::forUser($subject)->allows('role::admin'));
        self::assertFalse(Gate::forUser($subject)->allows('users::read'));
    }

    public function test_an_editor_cannot_overwrite_a_revision_committed_on_another_connection(): void
    {
        $database = $this->temporaryPath.'/race.sqlite';
        touch($database);
        $settings = ['driver' => 'sqlite', 'database' => $database, 'foreign_key_constraints' => true];
        config([
            'database.default' => 'permit_authority',
            'database.connections.permit_authority' => $settings,
            'database.connections.permit_competitor' => $settings,
        ]);

        try {
            $schema = DB::connection('permit_authority')->getSchemaBuilder();
            $schema->create('users', static function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
            $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_18_000000_create_user_permissions_table.php';
            $migration->up();

            $admin = $this->user();
            $subject = $this->user();
            Permit::add($admin, ['user_permissions::read', 'user_permissions::update']);
            Permit::add($subject, 'role::admin');
            $this->actingAs($admin);
            $current = $this->getJson($this->endpoint($subject))->assertOk();
            $values = [...$current->json('values'), 'role::admin' => false];

            $this->onNextAssignmentRead((string) $subject->getKey(), static function () use ($subject): void {
                DB::connection('permit_competitor')->table('user_permissions')->where('user_id', $subject->getKey())->update([
                    'revision' => (string) Str::uuid(),
                    'permissions' => '{"users::read":true}',
                ]);
            }, occurrence: 2);

            $this->putJson($this->endpoint($subject), ['revision' => $current->json('revision'), 'values' => $values])->assertConflict();
            app()->forgetScopedInstances();
            self::assertTrue(Gate::forUser($subject)->allows(['role::admin', 'users::read']));
        } finally {
            DB::purge('permit_authority');
            DB::purge('permit_competitor');
        }
    }

    public function test_route_alias_only_executes_the_action_for_known_allowed_keys(): void
    {
        $user = $this->user();
        $requests = 0;
        $action = static function () use (&$requests): Response {
            $requests++;

            return response()->noContent();
        };
        Route::get('/admin', $action)->middleware('permit:role::admin');
        Route::get('/printing', $action)->middleware('permit:users::print');
        Route::get('/unknown', $action)->middleware('permit:users::ghost');
        Route::get('/empty', $action)->middleware('permit');
        Route::get('/multiple', $action)->middleware('permit:role::admin,users::print');
        $this->getJson('/printing')->assertUnauthorized();
        $this->actingAs($user)->getJson('/admin')->assertForbidden();
        Permit::add($user, 'role::admin');
        $this->getJson('/admin')->assertNoContent();
        $this->getJson('/printing')->assertNoContent();

        Gate::before(static fn (?Authenticatable $actor): bool => true);
        $this->getJson('/unknown')->assertForbidden();
        $this->getJson('/empty')->assertForbidden();
        $this->getJson('/multiple')->assertForbidden();
        $this->actingAs(new GenericUser(['id' => $user->getKey()]))->getJson('/printing')->assertForbidden();
        self::assertSame(2, $requests);
    }

    public function test_route_alias_uses_the_actor_selected_by_the_routes_guard(): void
    {
        $web = $this->user();
        $staff = $this->user('Staff');
        config(['auth.guards.staff' => ['driver' => 'session', 'provider' => 'users']]);
        Permit::add($staff, 'role::admin');
        Route::get('/staff', static fn (): Response => response()->noContent())->middleware(['auth:staff', 'permit:role::admin']);
        Route::get('/web-admin', static fn (): Response => response()->noContent())->middleware('permit:role::admin');
        $this->actingAs($web, 'web');
        Auth::guard('staff')->setUser($staff);

        $this->getJson('/web-admin')->assertForbidden();
        $this->getJson('/staff')->assertNoContent();
    }

    public function test_an_inertia_partial_response_shares_the_active_actors_gates(): void
    {
        Route::middleware('web')->get('/permission-page', static fn (): \Inertia\Response => Inertia::render('Page'));
        $headers = ['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'Page', 'X-Inertia-Partial-Data' => 'other'];
        $this->get('/permission-page', $headers)->assertOk()->assertJsonPath('props.permitAbilities', []);

        $actor = $this->user();
        Permit::add($actor, 'role::admin');
        Gate::before(static fn (Authenticatable $actor, string $ability): ?bool => $ability === 'users::read' ? true : null);
        $this->actingAs($actor)->get('/permission-page', $headers)
            ->assertOk()
            ->assertJsonPath('props.userPermissionEditor.endpoint', '/permit/user-permissions')
            ->assertJsonPath('props.permitAbilities.role::admin', true)
            ->assertJsonPath('props.permitAbilities.users::read', true);
    }

    /** @param Closure(): void $write */
    private function onNextAssignmentRead(string $id, Closure $write, int $occurrence = 1): void
    {
        DB::listen(static function (QueryExecuted $query) use ($id, &$occurrence, $write): void {
            if ($occurrence > 0 && str_starts_with($query->sql, 'select "revision", "permissions", "roles"')
                && in_array($id, $query->bindings, true) && --$occurrence === 0) {
                $write();
            }
        });
    }
}
