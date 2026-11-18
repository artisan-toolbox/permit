<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Facades;

use ArtisanToolbox\Permit\AssignmentManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void add(int|string|Model $user, string|list<string> $abilities)
 * @method static void remove(int|string|Model $user, string|list<string> $abilities)
 * @method static void allowAll(int|string|Model $user)
 * @method static void denyAll(int|string|Model $user)
 *
 * @see AssignmentManager
 */
final class Permit extends Facade
{
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return AssignmentManager::class;
    }
}
