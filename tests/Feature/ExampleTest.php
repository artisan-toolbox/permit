<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Permit;

it('resolves the singleton', function () {
    expect(app(Permit::class))->toBeInstanceOf(Permit::class);
});

it('returns the same instance from the container', function () {
    expect(app(Permit::class))->toBe(app(Permit::class));
});

it('merges the package config', function () {
    expect(config('permit.placeholder'))->toBe('default');
});
