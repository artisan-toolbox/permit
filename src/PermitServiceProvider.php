<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

use Illuminate\Support\ServiceProvider;

class PermitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Permit::class);
    }
}
