<?php

declare(strict_types=1);

use ArtisanToolbox\Maintainer\Versionable\Readme\Badge;
use ArtisanToolbox\Permit\Permit;

it('provides the managed README identity with version and Packagist badges', function () {
    $head = (new Permit)->readmeHead();

    expect($head->title())->toBe('Permit')
        ->and($head->logo())->toBe(['src' => 'logo/permit-icon.svg', 'alt' => '', 'width' => 88])
        ->and(dirname(__DIR__, 2).'/logo/permit-icon.svg')->toBeFile()
        ->and($head->description())->toContain('Laravel authorization package')
        ->and(array_map(fn (Badge $badge): string => $badge->imgAlt(), $head->badges()))
        ->toBe(['version', 'Packagist', 'PHP', 'Laravel', 'Tests', 'Docs'])
        ->and($head->badges()[0]->imgSrc('1.2.3-beta.4'))->toBe('https://img.shields.io/badge/version-1.2.3--beta.4-blue?style=flat-square')
        ->and($head->badges()[1]->link())->toBe('https://packagist.org/packages/artisan-toolbox/permit');
});
