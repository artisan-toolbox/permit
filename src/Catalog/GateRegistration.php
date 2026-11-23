<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Catalog;

use ArtisanToolbox\Permit\Storage\AssignmentStore;
use ArtisanToolbox\Permit\Storage\Users;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;

final class GateRegistration
{
    /** @var array<string, Closure(Authenticatable): bool> */
    private array $callbacks = [];

    public function __construct(private readonly Gate $gate) {}

    public function register(Catalog $catalog): void
    {
        $this->assertAvailable($catalog);

        foreach ($catalog->defaults as $key => $default) {
            $roleRules = $catalog->requiredRoles[$key] ?? [];
            $callback = function (Authenticatable $actor) use ($key, $default, $roleRules): bool {
                $user = resolve(Users::class)->supported($actor);

                return $user !== null
                    && (resolve(AssignmentStore::class)->snapshot($user)->value($key) ?? $default)
                    && ($roleRules === [] || Catalog::satisfiesRoleRules($this->gate->forUser($actor), $roleRules));
            };
            $this->gate->define($key, $callback);
            $this->callbacks[$key] = $callback;
        }
    }

    public function assertAvailable(Catalog $catalog): void
    {
        $existing = $this->gate->abilities();

        foreach (array_keys($catalog->defaults) as $key) {
            throw_if(isset($existing[$key]) && $existing[$key] !== ($this->callbacks[$key] ?? null), LogicException::class, "The Laravel Gate [{$key}] already exists.");
        }
    }
}
