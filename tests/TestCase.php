<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests;

use ArtisanToolbox\Permit\PermitServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $temporaryPath;

    protected function setUp(): void
    {
        $this->temporaryPath = rtrim(sys_get_temp_dir(), '/\\').'/permit-tests-'.Str::random(16);
        (new Filesystem)->ensureDirectoryExists($this->temporaryPath.'/cache');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            (new Filesystem)->deleteDirectory($this->temporaryPath);
        }
    }

    protected function resolveApplication(): Application
    {
        return parent::resolveApplication()->useBootstrapPath($this->temporaryPath);
    }

    protected function getPackageProviders($app): array
    {
        return [
            PermitServiceProvider::class,
        ];
    }
}
