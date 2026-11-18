<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CRUD
{
    public function __construct(
        public bool $create = true,
        public bool $read = true,
        public bool $update = true,
        public bool $delete = true,
    ) {}
}
