<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Storage;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RenameAssignments
{
    public static function group(Connection $connection, string $table, string $from, string $to): void
    {
        self::validateGroup($from);
        self::validateGroup($to);

        if ($from === $to) {
            return;
        }
        $prefix = $from.'::';
        self::rename(
            $connection,
            $table,
            'permissions',
            static fn (string $key): ?string => str_starts_with($key, $prefix) ? $to.'::'.substr($key, strlen($prefix)) : null,
        );
    }

    public static function permission(Connection $connection, string $table, string $group, string $from, string $to): void
    {
        self::validateGroup($group);
        self::validateSegment($from);
        self::validateSegment($to);
        $key = $group.'::'.$from;
        self::rename(
            $connection,
            $table,
            'permissions',
            static fn (string $candidate): ?string => $candidate === $key ? $group.'::'.$to : null,
        );
    }

    public static function role(Connection $connection, string $table, string $from, string $to): void
    {
        self::validateSegment($from);
        self::validateSegment($to);
        $key = 'role::'.$from;
        self::rename(
            $connection,
            $table,
            'roles',
            static fn (string $candidate): ?string => $candidate === $key ? 'role::'.$to : null,
        );
    }

    /**
     * @param  'permissions'|'roles'  $column
     * @param  Closure(string): ?string  $destination
     */
    private static function rename(Connection $connection, string $table, string $column, Closure $destination): void
    {
        if ($connection->pretending()) {
            return;
        }
        $connection->transaction(function () use ($connection, $table, $column, $destination): void {
            $connection->table($table)->useWritePdo()->select(['user_id', 'revision', $column])->chunkById(500,
                function (Collection $rows) use ($connection, $table, $column, $destination): void {
                    foreach ($rows as $row) {
                        $stored = Assignments::decode($row->{$column}, $column);
                        $updated = $stored;
                        foreach ($stored as $key => $allowed) {
                            $newKey = $destination($key);

                            if ($newKey === null || $newKey === $key) {
                                continue;
                            }

                            throw_if(array_key_exists($newKey, $updated), AssignmentConflict::class, "Cannot rename Permit ability [{$key}] because [{$newKey}] already has a stored value.");
                            unset($updated[$key]);
                            $updated[$newKey] = $allowed;
                        }

                        throw_if($updated !== $stored && $connection->table($table)->where('user_id', $row->user_id)
                            ->where('revision', $row->revision)->update([
                                $column => json_encode((object) $updated, JSON_THROW_ON_ERROR),
                                'revision' => (string) Str::uuid(),
                            ]) !== 1, AssignmentConflict::class, 'A Permit assignment changed during the rename.');
                    }
                }, 'user_id');
        });
    }

    private static function validateSegment(string $segment): void
    {
        throw_if($segment === '' || str_contains($segment, ':'), InvalidArgumentException::class, 'A Permit rename requires non-empty ability key segments.');
    }

    private static function validateGroup(string $group): void
    {
        self::validateSegment($group);

        throw_if($group === 'role', InvalidArgumentException::class, 'The role prefix is reserved for Permit roles.');
    }
}
