<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User;

#[Table(name: 'uuid_users')]
#[WithoutTimestamps]
final class PermitUuidUser extends User
{
    use HasUuids;
}
