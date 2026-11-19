<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

final class ManagementIconsTest extends AuthorizationTestCase
{
    public function test_the_editor_receives_icon_names_for_declarations_and_generated_abilities(): void
    {
        $actor = $this->user();
        Permit::add($actor, Catalog::READ);

        $this->actingAs($actor)->getJson($this->endpoint($actor))
            ->assertOk()
            ->assertJsonPath('iconFamily', 'classic')
            ->assertJsonPath('sections.0.id', 'user_permissions')
            ->assertJsonPath('sections.0.icon', 'user-shield')
            ->assertJsonPath('sections.0.abilities.0.icon', 'eye')
            ->assertJsonPath('sections.0.abilities.1.icon', 'pen-to-square')
            ->assertJsonPath('sections.1.id', 'users')
            ->assertJsonPath('sections.1.icon', 'users')
            ->assertJsonPath('sections.1.abilities.0.icon', 'plus')
            ->assertJsonPath('sections.1.abilities.1.icon', 'eye')
            ->assertJsonPath('sections.1.abilities.2.icon', 'pen')
            ->assertJsonPath('sections.1.abilities.3.icon', 'print')
            ->assertJsonPath('sections.1.abilities.4.key', 'users::audit')
            ->assertJsonMissingPath('sections.1.abilities.4.icon')
            ->assertJsonPath('sections.2.id', 'role')
            ->assertJsonMissingPath('sections.2.icon')
            ->assertJsonMissingPath('sections.2.abilities.0.icon');
        self::assertTrue(Gate::forUser($actor)->allows('users::print'));
    }

    public function test_icon_configuration_applies_to_a_cached_catalog_without_changing_assignments(): void
    {
        self::assertSame(0, Artisan::call('permit:catalog:cache'));
        app()->useAppPath($this->temporaryPath.'/without-source');
        app()->forgetInstance(Catalog::class);

        $actor = $this->user();
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE]);
        $this->actingAs($actor);
        $initial = $this->getJson($this->endpoint($actor))->assertOk();

        config(['permit.icons' => [
            'family' => 'sharp',
            'crud' => ['create' => 'circle-plus', 'read' => null, 'update' => 'fa-solid fa-pen'],
            'user_permissions' => ['group' => 'key', 'update' => 12],
        ]]);

        $this->getJson($this->endpoint($actor))
            ->assertOk()
            ->assertJsonPath('iconFamily', 'sharp')
            ->assertJsonPath('revision', $initial->json('revision'))
            ->assertJsonPath('values', $initial->json('values'))
            ->assertJsonPath('sections.0.icon', 'key')
            ->assertJsonPath('sections.0.abilities.0.icon', 'eye')
            ->assertJsonMissingPath('sections.0.abilities.1.icon')
            ->assertJsonPath('sections.1.icon', 'users')
            ->assertJsonPath('sections.1.abilities.0.icon', 'circle-plus')
            ->assertJsonMissingPath('sections.1.abilities.1.icon')
            ->assertJsonMissingPath('sections.1.abilities.2.icon')
            ->assertJsonPath('sections.1.abilities.3.icon', 'print');

        config(['permit.icons.family' => 'invalid family']);
        $this->getJson($this->endpoint($actor))->assertOk()->assertJsonPath('iconFamily', 'classic');

        config(['permit.icons.enabled' => false]);
        $disabled = $this->getJson($this->endpoint($actor))
            ->assertOk()
            ->assertJsonMissingPath('iconFamily')
            ->assertJsonPath('revision', $initial->json('revision'))
            ->assertJsonPath('values', $initial->json('values'));

        foreach ($disabled->json('sections') as $section) {
            self::assertArrayNotHasKey('icon', $section);
            foreach ($section['abilities'] as $ability) {
                self::assertArrayNotHasKey('icon', $ability);
            }
        }

        $this->putJson($this->endpoint($actor), [
            'revision' => $initial->json('revision'),
            'values' => [...$initial->json('values'), 'users::print' => false],
        ])->assertNoContent();
        self::assertFalse(Gate::forUser($actor)->allows('users::print'));
    }
}
