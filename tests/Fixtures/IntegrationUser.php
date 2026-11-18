<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Foundation\Auth\User;

#[Connection('permit_integration')]
#[Unguarded]
#[Table(name: 'users')]
#[WithoutTimestamps]
final class IntegrationUser extends User {}
