<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Console;

use InvalidArgumentException;

final class PhpLiteral
{
    public static function encode(mixed $value): string
    {
        return match (true) {
            is_string($value) => "'".addcslashes($value, "\\'")."'",
            is_array($value) => '['.implode(', ', array_map(
                fn (int|string $key): string => self::encode($key).' => '.self::encode($value[$key]),
                array_keys($value),
            )).']',
            is_int($value) => (string) $value,
            $value === true => 'true',
            $value === false => 'false',
            $value === null => 'null',
            default => throw new InvalidArgumentException('Expected a scalar or array Permit declaration.'),
        };
    }
}
