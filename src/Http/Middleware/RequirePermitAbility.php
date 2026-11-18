<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Http\Middleware;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Storage\Users;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequirePermitAbility
{
    public function __construct(private Catalog $catalog, private Users $users, private Gate $gate) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $actor = $request->user();

        throw_if($actor === null, AuthenticationException::class);

        throw_if($this->users->supported($actor) === null || count($abilities) !== 1 || ! $this->catalog->has($abilities[0]), AuthorizationException::class);

        $this->gate->forUser($actor)->authorize($abilities[0]);

        return $next($request);
    }
}
