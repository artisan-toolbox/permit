<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\AuthorizationApp\Permit;

use ArtisanToolbox\Permit\Attributes\Role;
use ArtisanToolbox\Permit\Attributes\Roles;

#[Roles]
interface RoleDefinitions
{
    #[Role('Administrator')]
    public function admin(): void;

    #[Role('Auditor', default: true)]
    public function auditor(): void;
}
