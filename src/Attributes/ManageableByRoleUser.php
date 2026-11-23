<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Attributes;

use ArtisanToolbox\Permit\RoleMatch;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class ManageableByRoleUser
{
    public function __construct(public string $role, public RoleMatch $match = RoleMatch::Or) {}
}
