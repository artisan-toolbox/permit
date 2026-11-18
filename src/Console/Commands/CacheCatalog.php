<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Console\Commands;

use ArtisanToolbox\Permit\Catalog\CatalogCompiler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Compile the Permit authorization catalog')]
#[Signature('permit:catalog:cache')]
final class CacheCatalog extends Command
{
    public function handle(CatalogCompiler $compiler): int
    {
        $compiler->cache();
        $this->components->info('The Permit catalog was cached.');

        return self::SUCCESS;
    }
}
