<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\RoleRulesApp\Permit;

use ArtisanToolbox\Permit\Attributes\CRUD;
use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\ManageableByRoleUser;
use ArtisanToolbox\Permit\Attributes\Permission;
use ArtisanToolbox\Permit\Attributes\WithRole;

#[Group('reports', 'Reports')]
#[CRUD]
#[WithRole('admin')]
interface Reports
{
    #[Permission('Export', default: true)]
    #[WithRole('auditor')]
    #[ManageableByRoleUser('admin')]
    public function export(): void;
}
