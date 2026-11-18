<?php

declare(strict_types=1);

use ArtisanToolbox\Maintainer\Versionable\Readme\Badge;
use ArtisanToolbox\Permit\Permit;

it('provides the managed README identity and preview requirements', function () {
    $head = (new Permit)->readmeHead();

    expect($head->title())->toBe('Permit')
        ->and($head->logo())->toBe(['src' => 'logo/permit-icon.svg', 'alt' => '', 'width' => 88])
        ->and(dirname(__DIR__, 2).'/logo/permit-icon.svg')->toBeFile()
        ->and($head->description())->toContain('preview')
        ->and(array_map(fn (Badge $badge): string => $badge->imgAlt(), $head->badges()))
        ->toBe(['Status', 'PHP', 'Laravel', 'Tests', 'Docs']);
});
