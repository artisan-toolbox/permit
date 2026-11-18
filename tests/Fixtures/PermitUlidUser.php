<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User;

#[Table(name: 'ulid_users')]
#[WithoutTimestamps]
final class PermitUlidUser extends User
{
    use HasUlids;
}
