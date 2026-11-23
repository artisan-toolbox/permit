<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\RoleRulesApp\Permit;

use ArtisanToolbox\Permit\Attributes\ManageableByRoleUser;
use ArtisanToolbox\Permit\Attributes\Role;
use ArtisanToolbox\Permit\Attributes\Roles;

#[Roles]
interface AccessRoles
{
    #[Role('Administrator')]
    #[ManageableByRoleUser('admin')]
    public function admin(): void;

    #[Role('Auditor', default: true)]
    public function auditor(): void;

    #[Role('Developer')]
    #[ManageableByRoleUser('admin')]
    public function developer(): void;

    #[Role('Reviewer')]
    public function reviewer(): void;
}
