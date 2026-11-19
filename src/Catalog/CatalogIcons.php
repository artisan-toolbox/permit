<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Catalog;

/** @internal */
final readonly class CatalogIcons
{
    public ?string $family;

    /** @var array<string, mixed> */
    private array $options;

    public function __construct(mixed $configured)
    {
        $defaults = (require __DIR__.'/../../config/permit.php')['icons'];
        $this->options = is_array($configured) ? array_replace_recursive($defaults, $configured) : $defaults;
        $this->family = $this->options['enabled'] === true
            ? (self::token($this->options['family']) ?? $defaults['family'])
            : null;
    }

    public function name(?string $declared, ?string $configKey): ?string
    {
        if ($this->family === null) {
            return null;
        }

        return self::token($configKey === null ? $declared : data_get($this->options, $configKey));
    }

    private static function token(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1 ? $value : null;
    }
}
