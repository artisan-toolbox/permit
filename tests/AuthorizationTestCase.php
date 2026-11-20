<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests;

use ArtisanToolbox\Permit\Tests\Fixtures\PermitTestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Inertia\ServiceProvider;
use ReflectionProperty;

abstract class AuthorizationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('password')->default('internal-secret');
        });
        $migration = require dirname(__DIR__).'/database/migrations/2026_11_18_000000_create_user_permissions_table.php';
        $migration->up();
    }

    /** @param Application $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), ServiceProvider::class];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $app->useAppPath(__DIR__.'/Fixtures/AuthorizationApp');
        (new ReflectionProperty(Application::class, 'namespace'))->setValue($app, 'ArtisanToolbox\\Permit\\Tests\\Fixtures\\AuthorizationApp\\');
        $app['config']->set([
            'app.key' => 'base64:'.base64_encode(str_repeat('p', 32)),
            'session.driver' => 'array',
            'auth.defaults.guard' => 'web',
            'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
            'auth.providers.users' => ['driver' => 'eloquent', 'model' => PermitTestUser::class],
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
        ]);
    }

    protected function user(string $name = 'Ada'): PermitTestUser
    {
        return PermitTestUser::query()->create(['name' => $name]);
    }

    protected function endpoint(PermitTestUser $user): string
    {
        return '/permit/user-permissions?user='.$user->getKey();
    }
}
