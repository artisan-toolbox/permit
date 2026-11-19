<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Catalog;

use Collator;
use Illuminate\Contracts\Translation\Translator;
use InvalidArgumentException;

/**
 * @phpstan-type Ability array{key: string, name: string, description: ?string, default: bool, source: string, icon?: string, iconConfig?: string}
 * @phpstan-type Section array{kind: 'permissions'|'roles', id: string, name: string, description: ?string, order: 'alphabetical'|'locked', source: string, abilities: list<Ability>, icon?: string, iconConfig?: string}
 * @phpstan-type PresentedAbility array{key: string, name: string, description: string, icon?: string}
 * @phpstan-type PresentedSection array{kind: 'permissions'|'roles', id: string, name: string, description: string, order: 'alphabetical'|'locked', abilities: list<PresentedAbility>, icon?: string}
 */
final readonly class Catalog
{
    public const string READ = 'user_permissions::read';

    public const string UPDATE = 'user_permissions::update';

    /** @var array<string, bool> */
    public array $defaults;

    public string $fingerprint;

    /** @param list<Section> $sections */
    public function __construct(public array $sections)
    {
        $defaults = [];
        $sources = [];
        $groups = [];
        foreach ($sections as $section) {
            if (isset($groups[$section['id']])) {
                throw new InvalidArgumentException("Duplicate Permit section [{$section['id']}] in [{$section['source']}] and [{$groups[$section['id']]}].");
            }

            if (trim($section['name']) === '' || $section['abilities'] === []) {
                throw new InvalidArgumentException("Permit section [{$section['source']}] requires a name and at least one ability.");
            }
            $groups[$section['id']] = $section['source'];

            foreach ($section['abilities'] as $ability) {
                $comparisonKey = strtolower($ability['key']);

                if (isset($sources[$comparisonKey])) {
                    throw new InvalidArgumentException("Duplicate Permit ability [{$ability['key']}] in [{$ability['source']}] and [{$sources[$comparisonKey]}].");
                }

                if (trim($ability['name']) === '') {
                    throw new InvalidArgumentException("Permit ability [{$ability['source']}] requires a name.");
                }
                $sources[$comparisonKey] = $ability['source'];
                $defaults[$ability['key']] = $ability['default'];
            }
        }
        ksort($defaults, SORT_STRING);
        $this->defaults = $defaults;
        $this->fingerprint = hash('sha256', json_encode($defaults, JSON_THROW_ON_ERROR));
    }

    public function has(string $ability): bool
    {
        return isset($this->defaults[$ability]);
    }

    /** @return list<PresentedSection> */
    public function present(Translator $translator, string $locale, ?CatalogIcons $icons = null): array
    {
        $collator = new Collator($locale);
        $collator->setStrength(Collator::SECONDARY);
        $collator->setAttribute(Collator::NORMALIZATION_MODE, Collator::ON);
        $sections = [];
        $roles = [];

        foreach ($this->sections as $section) {
            $name = $this->translate($translator, $section['name'], $locale);
            $abilities = [];
            foreach ($section['abilities'] as $ability) {
                $abilityName = $this->translate($translator, $ability['name'], $locale);
                $icon = $icons?->name($ability['icon'] ?? null, $ability['iconConfig'] ?? null);
                $abilities[] = [
                    'key' => $ability['key'],
                    'name' => $abilityName,
                    'description' => $this->translate(
                        $translator,
                        $ability['description'] ?? ($section['kind'] === 'roles' ? 'permit::catalog.role_description' : 'permit::catalog.permission_description'),
                        $locale,
                        ['name' => $abilityName],
                    ),
                    ...($icon === null ? [] : ['icon' => $icon]),
                ];
            }

            if ($section['order'] === 'alphabetical') {
                usort($abilities, fn (array $first, array $second): int => $this->compare($collator, $first['name'], $second['name'], $first['key'], $second['key']));
            }

            $icon = $icons?->name($section['icon'] ?? null, $section['iconConfig'] ?? null);
            $presentation = [
                'kind' => $section['kind'],
                'id' => $section['id'],
                'name' => $name,
                'description' => $this->translate($translator, $section['description'] ?? 'permit::catalog.group_description', $locale, ['name' => $name]),
                'order' => $section['order'],
                'abilities' => $abilities,
                ...($icon === null ? [] : ['icon' => $icon]),
            ];

            if ($section['kind'] === 'roles') {
                $roles[] = $presentation;
            } else {
                $sections[] = $presentation;
            }
        }

        usort($sections, fn (array $first, array $second): int => $this->compare($collator, $first['name'], $second['name'], $first['id'], $second['id']));

        return [...$sections, ...$roles];
    }

    /** @param array<string, string> $replace */
    private function translate(Translator $translator, string $key, string $locale, array $replace = []): string
    {
        $label = $translator->get($key, $replace, $locale);

        return is_string($label) ? $label : $key;
    }

    private function compare(Collator $collator, string $first, string $second, string $firstKey, string $secondKey): int
    {
        return ($collator->compare($first, $second) ?: 0) ?: strcmp($firstKey, $secondKey);
    }
}
