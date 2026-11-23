<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Catalog\CatalogCompiler;
use ArtisanToolbox\Permit\Catalog\GateRegistration;
use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\RoleRulesTestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use PHPUnit\Framework\Attributes\DataProvider;

final class RoleAuthorizationTest extends RoleRulesTestCase
{
    #[DataProvider('catalogModes')]
    public function test_permissions_require_their_assignment_and_each_declared_role(bool $cached): void
    {
        $this->loadCatalog($cached);

        $user = $this->user();
        $gate = Gate::forUser($user);
        self::assertFalse($gate->allows('reports::export'));
        Permit::add($user, 'reports::read');
        self::assertFalse($gate->allows('reports::read'));

        Permit::add($user, 'role::admin');
        self::assertTrue($gate->allows(['reports::read', 'reports::export']));
        self::assertFalse($gate->allows('reports::delete'));

        Permit::remove($user, 'role::auditor');
        self::assertFalse($gate->allows('reports::export'));
        self::assertTrue($gate->allows('reports::read'));

        Permit::add($user, 'role::auditor');
        Permit::remove($user, 'reports::export');
        self::assertFalse($gate->allows('reports::export'));
        self::assertFalse(Gate::allows('reports::export'));
    }

    public function test_middleware_and_inertia_use_the_same_role_gate_for_the_acting_user(): void
    {
        Route::get('/export', static fn (): Response => response()->noContent())->middleware('permit:reports::export');
        Route::middleware('web')->get('/reports', static fn (): \Inertia\Response => Inertia::render('Reports'));
        $user = $this->user();
        $this->actingAs($user)->getJson('/export')->assertForbidden();
        $this->get('/reports', ['X-Inertia' => 'true'])->assertJsonPath('props.permitAbilities.reports::export', false);

        Gate::before(static fn (Authenticatable $actor, string $ability): ?bool => $ability === 'role::admin' && $actor === $user ? true : null);
        $this->getJson('/export')->assertNoContent();
        $this->get('/reports', ['X-Inertia' => 'true'])->assertJsonPath('props.permitAbilities.reports::export', true);
    }

    #[DataProvider('catalogModes')]
    public function test_role_lists_combine_the_group_and_method_match_modes(bool $cached): void
    {
        $this->loadCatalog($cached);

        $user = $this->user();
        $gate = Gate::forUser($user);
        Permit::add($user, ['role::reviewer', 'releases::publish', 'releases::read']);
        self::assertFalse($gate->allows('releases::publish'));

        foreach (['role::admin', 'role::developer'] as $role) {
            Permit::add($user, $role);
            self::assertTrue($gate->allows(['releases::read', 'releases::publish']));
            Permit::remove($user, $role);
        }

        Permit::add($user, 'role::developer');
        Permit::remove($user, 'role::reviewer');
        self::assertFalse($gate->allows('releases::publish'));
        self::assertTrue($gate->allows('releases::read'));
        Permit::add($user, 'role::reviewer');
        Permit::remove($user, 'releases::publish');
        self::assertFalse($gate->allows('releases::publish'));
    }

    private function loadCatalog(bool $cached): void
    {
        if ($cached) {
            self::assertSame(0, Artisan::call('permit:catalog:cache'));
            app()->useAppPath($this->temporaryPath.'/without-source');
            resolve(GateRegistration::class)->register(resolve(CatalogCompiler::class)->load());
        }
    }

    /** @return iterable<string, array{bool}> */
    public static function catalogModes(): iterable
    {
        yield 'discovered' => [false];
        yield 'cached' => [true];
    }
}
