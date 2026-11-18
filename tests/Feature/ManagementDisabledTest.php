<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

final class ManagementDisabledTest extends AuthorizationTestCase
{
    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('permit.management.enabled', false);
    }

    public function test_management_routes_and_discovery_are_optional_with_gates_and_route_alias_available(): void
    {
        $actor = $this->user();
        Permit::add($actor, 'role::admin');
        Route::get('/operators', static fn (): Response => response()->noContent())->middleware('permit:role::admin');
        Route::middleware('web')->get('/disabled-management', static fn (): \Inertia\Response => Inertia::render('Page'));

        $this->actingAs($actor);
        $this->getJson($this->endpoint($actor))->assertNotFound();
        $this->getJson('/operators')->assertNoContent();
        $this->get('/disabled-management', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.permitAbilities.role::admin', true)
            ->assertJsonMissingPath('props.userPermissionEditor');
    }
}
