<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Http\Middleware;

use ArtisanToolbox\Permit\Storage\Users;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\StatefulGuard;

final class AuthenticateManagement extends Authenticate
{
    public function __construct(Factory $auth, private readonly Users $users)
    {
        parent::__construct($auth);
    }

    /** @param list<string|null> $guards */
    protected function authenticate($request, array $guards): void
    {
        $guard = $this->users->guard();

        if (! $this->auth->guard($guard) instanceof StatefulGuard) {
            $this->unauthenticated($request, [$guard]);
        }

        parent::authenticate($request, [$guard]);
    }
}
