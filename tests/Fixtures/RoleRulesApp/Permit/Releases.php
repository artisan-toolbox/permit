<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\RoleRulesApp\Permit;

use ArtisanToolbox\Permit\Attributes\CRUD;
use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\ManageableByRoleUser;
use ArtisanToolbox\Permit\Attributes\Permission;
use ArtisanToolbox\Permit\Attributes\WithRole;
use ArtisanToolbox\Permit\RoleMatch;

#[Group('releases', 'Releases')]
#[CRUD]
#[WithRole('admin, developer, admin', RoleMatch::Or)]
#[ManageableByRoleUser('admin,developer')]
interface Releases
{
    #[Permission('Publish release')]
    #[WithRole('auditor,reviewer')]
    #[ManageableByRoleUser('auditor, reviewer', RoleMatch::And)]
    public function publish(): void;
}
