<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Foundation\Auth\User;

#[Unguarded]
#[Table(name: 'users')]
#[WithoutTimestamps]
final class PermitTestUser extends User {}
