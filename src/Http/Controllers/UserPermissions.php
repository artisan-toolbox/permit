<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Http\Controllers;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Catalog\CatalogIcons;
use ArtisanToolbox\Permit\Contracts\PermissionManagementPolicy;
use ArtisanToolbox\Permit\ManagementAction;
use ArtisanToolbox\Permit\Storage\AssignmentConflict;
use ArtisanToolbox\Permit\Storage\Assignments;
use ArtisanToolbox\Permit\Storage\AssignmentStore;
use ArtisanToolbox\Permit\Storage\Users;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use JsonException;
use LogicException;
use stdClass;

final readonly class UserPermissions
{
    public function __construct(
        private Catalog $catalog,
        private Users $users,
        private AssignmentStore $assignments,
        private Gate $gate,
        private Translator $translator,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $actor = $this->actor($request, Catalog::READ);
        $subject = $this->subject($request, $actor, ManagementAction::Read);
        $id = $this->users->id($subject);
        $current = $this->assignments->snapshot($subject);
        $canUpdate = $this->gate->forUser($actor)->allows(Catalog::UPDATE)
            && $this->inScope($actor, $subject, ManagementAction::Update);
        $values = [];
        foreach ($this->catalog->defaults as $key => $default) {
            $values[$key] = $current->value($key) ?? $default;
        }
        $icons = new CatalogIcons(config('permit.icons'));

        return response()->json([
            'subject' => $id,
            'revision' => $this->revision($id, $current),
            'canUpdate' => $canUpdate,
            'isCurrentUser' => $this->users->id($actor) === $id,
            'sections' => $this->catalog->present($this->translator, app()->getLocale(), $icons),
            'values' => (object) $values,
            ...($icons->family === null ? [] : ['iconFamily' => $icons->family]),
        ]);
    }

    public function update(Request $request): Response|JsonResponse
    {
        $actor = $this->actor($request, Catalog::UPDATE);
        $subject = $this->subject($request, $actor, ManagementAction::Update);
        $id = $this->users->id($subject);
        [$revision, $values] = $this->payload($request);
        $current = $this->assignments->fresh($subject);

        if (! hash_equals($this->revision($id, $current), $revision)) {
            return $this->conflict();
        }

        $defaults = $this->catalog->defaults;

        if (array_diff_key($defaults, $values) !== [] || array_diff_key($values, $defaults) !== []) {
            throw $this->invalid('values');
        }

        try {
            $this->assignments->replace($subject, $current->revision, $values);
        } catch (AssignmentConflict) {
            return $this->conflict();
        }

        return response()->noContent();
    }

    private function actor(Request $request, string $ability): Model&Authenticatable
    {
        $actor = $this->users->supported($request->user($this->users->guard()));

        throw_if($actor === null, AuthorizationException::class);
        $this->gate->forUser($actor)->authorize($ability);

        return $actor;
    }

    private function subject(Request $request, Authenticatable $actor, ManagementAction $action): Model
    {
        $id = $request->query('user');

        abort_unless(is_string($id), 404);

        try {
            $subject = $this->users->find($id);
        } catch (ModelNotFoundException) {
            abort(404);
        }
        abort_unless($this->inScope($actor, $subject, $action), 404);

        return $subject;
    }

    private function inScope(Authenticatable $actor, Model $subject, ManagementAction $action): bool
    {
        $implementation = config('permit.management.target_policy');

        if ($implementation === null) {
            return true;
        }

        throw_if(! is_string($implementation) || ! is_a($implementation, PermissionManagementPolicy::class, true), LogicException::class, 'The Permit target policy must implement PermissionManagementPolicy.');

        return resolve($implementation)->allows($actor, $subject, $action);
    }

    /** @return array{string, array<string, bool>} */
    private function payload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $this->invalid('values');
        }

        if (! $payload instanceof stdClass || property_exists($payload, 'revision') === false
            || ! is_string($payload->revision) || preg_match('/^[0-9a-f]{64}$/D', $payload->revision) !== 1) {
            throw $this->invalid('revision');
        }

        if (count(get_object_vars($payload)) !== 2 || ! ($payload->values ?? null) instanceof stdClass) {
            throw $this->invalid('values');
        }
        $values = [];
        foreach (get_object_vars($payload->values) as $key => $value) {
            if (! is_string($key) || ! is_bool($value)) {
                throw $this->invalid(is_string($key) ? "values.{$key}" : 'values');
            }
            $values[$key] = $value;
        }

        return [$payload->revision, $values];
    }

    private function revision(string $id, Assignments $assignments): string
    {
        return hash('sha256', json_encode([$id, $assignments->revision, $this->catalog->fingerprint], JSON_THROW_ON_ERROR));
    }

    private function invalid(string $key): ValidationException
    {
        return ValidationException::withMessages([$key => (string) __('permit::catalog.invalid_assignments')]);
    }

    private function conflict(): JsonResponse
    {
        return response()->json(['message' => __('permit::catalog.conflict')], 409);
    }
}
