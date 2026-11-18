<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Contracts;

use ArtisanToolbox\Permit\ManagementAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface PermissionManagementPolicy
{
    public function allows(Authenticatable $actor, Model $subject, ManagementAction $action): bool;
}
