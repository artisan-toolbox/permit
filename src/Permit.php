<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

use ArtisanToolbox\Maintainer\Versionable\Contracts\BeforeVersioning;
use ArtisanToolbox\Maintainer\Versionable\Contracts\Versionable;
use ArtisanToolbox\Maintainer\Versionable\Contracts\WithReadmeHead;
use ArtisanToolbox\Maintainer\Versionable\Readme\Badge;
use ArtisanToolbox\Maintainer\Versionable\Readme\Head;
use Illuminate\Support\Facades\Process;

class Permit implements BeforeVersioning, Versionable, WithReadmeHead
{
    public const string VERSION = '1.0.0-beta.5';

    public static function beforeVersioning(string $current, string $next): void
    {
        Process::path(dirname(__DIR__))
            ->forever()
            ->run(['npm', 'run', 'release:build'])
            ->throw();
    }

    public function readmeHead(): Head
    {
        return (new Head)
            ->withLogo(src: 'logo/permit-icon.svg', alt: '', width: 88)
            ->withTitle('Permit')
            ->withDescription('Laravel authorization package for declaring Gates and managing per-user permissions and roles.')
            ->addBadge(Badge::releaseVersion('https://github.com/artisan-toolbox/permit/releases'))
            ->addBadge(Badge::make(
                link: 'https://packagist.org/packages/artisan-toolbox/permit',
                imgSrc: 'https://img.shields.io/badge/Packagist-F28D1A?style=flat-square&logo=packagist&logoColor=white',
                imgAlt: 'Packagist',
            ))
            ->addBadge(Badge::make(
                link: 'https://atb.dev.br/packages/permit/#requirements',
                imgSrc: 'https://img.shields.io/badge/PHP-%5E8.5-777BB4?style=flat-square',
                imgAlt: 'PHP',
            ))
            ->addBadge(Badge::make(
                link: 'https://atb.dev.br/packages/permit/#requirements',
                imgSrc: 'https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square',
                imgAlt: 'Laravel',
            ))
            ->addBadge(Badge::make(
                link: 'https://github.com/artisan-toolbox/permit/actions/workflows/tests.yml?query=branch%3A1.x',
                imgSrc: 'https://img.shields.io/github/actions/workflow/status/artisan-toolbox/permit/tests.yml?branch=1.x&label=Tests&style=flat-square',
                imgAlt: 'Tests',
            ))
            ->addBadge(Badge::make(
                link: 'https://atb.dev.br/packages/permit/',
                imgSrc: 'https://img.shields.io/badge/Docs-Read-blue?style=flat-square',
                imgAlt: 'Docs',
            ));
    }
}
