<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\AuthorizationApp\Permit;

use ArtisanToolbox\Permit\Attributes\CRUD;
use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\LockedOrder;
use ArtisanToolbox\Permit\Attributes\Permission;

#[Group('users', 'Users', icon: 'users')]
#[CRUD(delete: false)]
#[LockedOrder]
interface Users
{
    #[Permission('Print user data', "Print Ada's data from C:\\Reports.", default: true, icon: 'print')]
    public function print(): void;

    #[Permission('Audit users', icon: 'fa-solid fa-eye')]
    public function audit(): void;
}
