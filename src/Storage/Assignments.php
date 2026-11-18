<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Storage;

use stdClass;
use UnexpectedValueException;

final readonly class Assignments
{
    /**
     * @param  array<string, bool>  $permissions
     * @param  array<string, bool>  $roles
     */
    public function __construct(
        public ?string $revision = null,
        public array $permissions = [],
        public array $roles = [],
    ) {}

    public function value(string $ability): ?bool
    {
        return str_starts_with($ability, 'role::')
            ? ($this->roles[$ability] ?? null)
            : ($this->permissions[$ability] ?? null);
    }

    /** @param array<string, bool> $values */
    public function apply(array $values): self
    {
        $permissions = $this->permissions;
        $roles = $this->roles;
        foreach ($values as $ability => $allow) {
            if (str_starts_with($ability, 'role::')) {
                $roles[$ability] = $allow;
            } else {
                $permissions[$ability] = $allow;
            }
        }

        return new self($this->revision, $permissions, $roles);
    }

    /**
     * @param  'permissions'|'roles'  $column
     * @return array<string, bool>
     */
    public static function decode(mixed $json, string $column): array
    {
        throw_unless(is_string($json), UnexpectedValueException::class, 'Permit assignment data must be stored as a JSON object.');
        $object = json_decode($json, false, flags: JSON_THROW_ON_ERROR);

        throw_unless($object instanceof stdClass, UnexpectedValueException::class, 'Permit assignment data must be stored as a JSON object.');

        return self::validate(get_object_vars($object), $column);
    }

    /**
     * @param  'permissions'|'roles'  $column
     * @return array<string, bool>
     */
    public static function validate(mixed $map, string $column): array
    {
        throw_unless(is_array($map), UnexpectedValueException::class, 'Permit assignment data must be a map.');
        foreach ($map as $key => $value) {
            throw_if(! is_string($key) || ! is_bool($value), UnexpectedValueException::class, 'Permit assignment data must contain only boolean values keyed by ability.');

            throw_if(str_starts_with($key, 'role::') !== ($column === 'roles'), UnexpectedValueException::class, "Permit assignment [{$key}] is stored in the wrong map.");
        }

        return $map;
    }
}
