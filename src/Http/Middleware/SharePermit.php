<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Http\Middleware;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Storage\Users;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

final class SharePermit
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('permitAbilities', Inertia::always(static function (Request $request, Users $users, Catalog $catalog, Gate $gate): stdClass {
            $actor = $users->supported($request->user($users->guard()));

            if ($actor === null) {
                return new stdClass;
            }
            $verdicts = [];
            $actorGate = $gate->forUser($actor);
            foreach ($catalog->defaults as $key => $default) {
                $verdicts[$key] = $actorGate->allows($key);
            }

            return (object) $verdicts;
        }));

        if (config()->boolean('permit.management.enabled', true)) {
            Inertia::share('userPermissionEditor', Inertia::always(static fn (): array => [
                'version' => 1,
                'endpoint' => route('permit.user-permissions.show', absolute: false),
            ]));
        }

        return $next($request);
    }
}
