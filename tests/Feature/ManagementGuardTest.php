<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert;

final class ManagementGuardTest extends AuthorizationTestCase
{
    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set([
            'permit.guard' => 'permit_management',
            'auth.guards.permit_management' => ['driver' => 'token', 'provider' => 'users'],
        ]);
    }

    public function test_a_real_token_guard_cannot_open_or_update_the_management_editor(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('api_token')->nullable();
        });
        $actor = $this->user();
        $subject = $this->user();
        $actor->forceFill(['api_token' => 'permit-guard-fixture-token'])->save();
        Permit::add($actor, ['role::admin', 'user_permissions::read', 'user_permissions::update']);
        $headers = ['Authorization' => 'Bearer permit-guard-fixture-token'];
        Route::get('/token-admin', static fn (): Response => response()->noContent())
            ->middleware(['auth:permit_management', 'permit:role::admin']);

        $this->getJson('/token-admin', $headers)->assertNoContent();
        $this->getJson($this->endpoint($subject), $headers)->assertUnauthorized();
        $this->putJson($this->endpoint($subject), ['revision' => str_repeat('a', 64), 'values' => []], $headers)->assertUnauthorized();
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $subject->getKey()]);
    }

    public function test_an_application_defined_stateful_guard_is_accepted(): void
    {
        config(['auth.guards.permit_management.driver' => 'permit_custom_session']);
        Auth::extend('permit_custom_session', static function (Application $app, string $name, array $config): SessionGuard {
            Assert::assertTrue($app['request']->hasSession());

            return $app->make(AuthManager::class)->createSessionDriver($name, $config);
        });
        $actor = $this->user();
        Permit::add($actor, ['user_permissions::read', 'user_permissions::update']);
        $sessionGuard = $this->app->make(AuthManager::class)->createSessionDriver('permit_management', ['provider' => 'users']);

        $this->withSession([$sessionGuard->getName() => $actor->getAuthIdentifier()])
            ->getJson($this->endpoint($actor))
            ->assertOk()
            ->assertJsonPath('canUpdate', true);
    }
}
