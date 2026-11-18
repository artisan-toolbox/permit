<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Permit;
use Illuminate\Support\ServiceProvider;

it('resolves the singleton', function () {
    expect(app()->bound(Permit::class))->toBeTrue();
});

it('returns the same instance from the container', function () {
    expect(resolve(Permit::class))->toBe(resolve(Permit::class));
});

it('does not register placeholder configuration or migration publishing', function () {
    expect(config('permit'))->toBeNull()
        ->and(ServiceProvider::pathsToPublish(group: 'permit-migrations'))->toBe([])
        ->and(ServiceProvider::pathsToPublish(group: 'permit-config'))->toBe([]);
});
