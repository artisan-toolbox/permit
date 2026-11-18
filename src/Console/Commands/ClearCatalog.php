<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Console\Commands;

use ArtisanToolbox\Permit\Catalog\CatalogCompiler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Remove the compiled Permit authorization catalog')]
#[Signature('permit:catalog:clear')]
final class ClearCatalog extends Command
{
    public function handle(CatalogCompiler $compiler): int
    {
        $compiler->clear();
        $this->components->info('The Permit catalog cache was cleared.');

        return self::SUCCESS;
    }
}
