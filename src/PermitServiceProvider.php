<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

use Illuminate\Support\ServiceProvider;

class PermitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/permit.php', 'permit');

        $this->app->singleton(Permit::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/permit.php' => config_path('permit.php'),
        ], ['permit', 'permit-config']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['permit', 'permit-migrations']);
    }
}
