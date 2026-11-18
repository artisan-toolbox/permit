<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Permission
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $default = false,
    ) {}
}
