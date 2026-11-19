<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Group
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
    ) {}
}
