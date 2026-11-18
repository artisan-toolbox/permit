<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Permit;
use Composer\Semver\VersionParser;
use Illuminate\Filesystem\Filesystem;

it('exposes the unpublished preview version to Maintainer and frontend consumers', function () {
    $manifest = json_decode((new Filesystem)->get(dirname(__DIR__, 2).'/package.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(fn () => (new VersionParser)->normalize(Permit::VERSION))->not->toThrow(UnexpectedValueException::class)
        ->and($manifest['version'])->toBe(Permit::VERSION);
});
