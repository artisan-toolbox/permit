<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests;

use ArtisanToolbox\Permit\PermitServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PermitServiceProvider::class,
        ];
    }
}
