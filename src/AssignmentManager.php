<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Storage\AssignmentStore;
use ArtisanToolbox\Permit\Storage\Users;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class AssignmentManager
{
    public function __construct(private Catalog $catalog, private Users $users, private AssignmentStore $assignments) {}

    /** @param string|list<string> $abilities */
    public function add(int|string|Model $user, string|array $abilities): void
    {
        $this->change($user, (array) $abilities, true);
    }

    /** @param string|list<string> $abilities */
    public function remove(int|string|Model $user, string|array $abilities): void
    {
        $this->change($user, (array) $abilities, false);
    }

    public function allowAll(int|string|Model $user): void
    {
        $this->change($user, array_keys($this->catalog->defaults), true);
    }

    public function denyAll(int|string|Model $user): void
    {
        $this->change($user, array_keys($this->catalog->defaults), false);
    }

    /** @param array<array-key, mixed> $abilities */
    private function change(int|string|Model $subject, array $abilities, bool $allowed): void
    {
        throw_unless(array_is_list($abilities), InvalidArgumentException::class, 'Permit abilities must be supplied as a key or a list of keys.');
        $values = [];
        foreach ($abilities as $ability) {
            throw_if(! is_string($ability) || ! $this->catalog->has($ability), InvalidArgumentException::class, 'All Permit abilities must be registered catalog keys.');
            $values[$ability] = $allowed;
        }
        $user = $this->users->find($subject);

        if ($values !== []) {
            $this->assignments->merge($user, $values);
        }
    }
}
