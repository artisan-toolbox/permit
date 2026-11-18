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

it('registers Permit configuration and publishes its migration and configuration', function () {
    expect(config('permit.table'))->toBe('user_permissions')
        ->and(config('permit.guard'))->toBe('web')
        ->and(ServiceProvider::pathsToPublish(group: 'permit-migrations'))->not->toBeEmpty()
        ->and(ServiceProvider::pathsToPublish(group: 'permit-config'))->not->toBeEmpty();
});
