<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Catalog\CatalogCompiler;
use ArtisanToolbox\Permit\Catalog\GateRegistration;
use ArtisanToolbox\Permit\Console\Commands\CacheCatalog;
use ArtisanToolbox\Permit\Console\Commands\ClearCatalog;
use ArtisanToolbox\Permit\Console\Commands\InspectAssignments;
use ArtisanToolbox\Permit\Console\Commands\MakePermissionGroup;
use ArtisanToolbox\Permit\Http\Middleware\RequirePermitAbility;
use ArtisanToolbox\Permit\Http\Middleware\SharePermit;
use ArtisanToolbox\Permit\Storage\AssignmentStore;
use ArtisanToolbox\Permit\Storage\RenameAssignments;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class PermitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/permit.php', 'permit');

        $this->app->singleton(Permit::class);
        $this->app->singleton(GateRegistration::class);
        $this->app->singleton(Catalog::class, fn (): Catalog => $this->app->make(CatalogCompiler::class)->load());
        $this->app->scoped(AssignmentStore::class);
        $this->app->scoped(AssignmentManager::class);
    }

    public function boot(Router $router): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'permit');

        $router->aliasMiddleware('permit', RequirePermitAbility::class);
        $this->callAfterResolving(HttpKernel::class, static function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SharePermit::class);
            }
        });

        if (config()->boolean('permit.management.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        $this->app->booted(function (): void {
            $this->app->make(GateRegistration::class)->register($this->app->make(Catalog::class));
        });

        Blueprint::macro('renamePermissionGroup', function (string $from, string $to): void {
            RenameAssignments::group($this->connection, $this->getTable(), $from, $to);
        });
        Blueprint::macro('renamePermission', function (string $group, string $from, string $to): void {
            RenameAssignments::permission($this->connection, $this->getTable(), $group, $from, $to);
        });
        Blueprint::macro('renameRole', function (string $from, string $to): void {
            RenameAssignments::role($this->connection, $this->getTable(), $from, $to);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/permit.php' => config_path('permit.php')], 'permit-config');
            $this->publishesMigrations([__DIR__.'/../database/migrations' => database_path('migrations')], 'permit-migrations');
            $this->commands([CacheCatalog::class, ClearCatalog::class, MakePermissionGroup::class, InspectAssignments::class]);
            $this->optimizes(optimize: 'permit:catalog:cache', clear: 'permit:catalog:clear', key: 'permit');
        }
    }
}
