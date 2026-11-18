<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures;

use ArtisanToolbox\Permit\Contracts\PermissionManagementPolicy;
use ArtisanToolbox\Permit\ManagementAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class PermitTargetPolicy implements PermissionManagementPolicy
{
    public function allows(Authenticatable $actor, Model $subject, ManagementAction $action): bool
    {
        return $subject->getAttribute('name') !== 'Protected'
            && ($action === ManagementAction::Read || $subject->getAttribute('name') !== 'Read only');
    }
}
