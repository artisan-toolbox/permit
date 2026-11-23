<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures\RoleRulesApp\Permit;

use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\ManageableByRoleUser;
use ArtisanToolbox\Permit\Attributes\Permission;

#[Group('payroll', 'Payroll')]
#[ManageableByRoleUser('admin')]
interface Payroll
{
    #[Permission('Approve payroll', default: true)]
    public function approve(): void;

    #[Permission('Close payroll')]
    public function close(): void;

    #[Permission('Export payroll')]
    #[ManageableByRoleUser('auditor')]
    public function export(): void;
}
