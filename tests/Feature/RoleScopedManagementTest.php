<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Tests\RoleRulesTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

final class RoleScopedManagementTest extends RoleRulesTestCase
{
    /** @return array<string, array{bool}> */
    public static function hiddenInputs(): array
    {
        return ['omitted' => [false], 'submitted' => [true]];
    }

    #[DataProvider('hiddenInputs')]
    public function test_saving_visible_changes_preserves_hidden_grants_denials_and_unassigned_defaults(bool $submitHidden): void
    {
        $actor = $this->user();
        $subject = $this->user('Noor');
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE]);
        Permit::add($subject, ['role::admin', 'payroll::close']);
        Permit::remove($subject, 'payroll::approve');

        $snapshot = $this->actingAs($actor)->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonMissing(['id' => 'payroll'])
            ->assertJsonMissing(['key' => 'reports::export'])
            ->assertJsonMissing(['key' => 'role::admin']);
        $values = $snapshot->json('values');
        self::assertSame([], array_intersect_key($values, array_flip([
            'reports::export', 'payroll::approve', 'payroll::close', 'payroll::export', 'role::admin',
        ])));
        $values['reports::read'] = true;

        if ($submitHidden) {
            $values += [
                'role::admin' => false, 'payroll::close' => false, 'payroll::approve' => true,
                'payroll::export' => true, 'reports::export' => false,
            ];
        }

        $this->putJson($this->endpoint($subject), ['revision' => $snapshot->json('revision'), 'values' => $values])
            ->assertNoContent();

        $row = DB::table('user_permissions')->where('user_id', $subject->getKey())->firstOrFail();
        $permissions = json_decode($row->permissions, true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($permissions['reports::read']);
        self::assertTrue($permissions['payroll::close']);
        self::assertFalse($permissions['payroll::approve']);
        self::assertArrayNotHasKey('payroll::export', $permissions);
        self::assertArrayNotHasKey('reports::export', $permissions);
        self::assertTrue(json_decode($row->roles, true, flags: JSON_THROW_ON_ERROR)['role::admin']);
    }

    public function test_an_editor_can_change_only_fields_for_which_the_actor_has_all_management_roles(): void
    {
        $actor = $this->user();
        $subject = $this->user('Noor');
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE, 'role::admin']);
        Permit::remove($actor, 'role::auditor');
        $snapshot = $this->actingAs($actor)->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonPath('values.payroll::approve', true)
            ->assertJsonPath('values.reports::export', true)
            ->assertJsonMissingPath('values.payroll::export')
            ->assertJsonMissing(['key' => 'payroll::export']);

        $values = [...$snapshot->json('values'), 'payroll::approve' => false, 'reports::export' => false];
        $this->putJson($this->endpoint($subject), ['revision' => $snapshot->json('revision'), 'values' => $values])
            ->assertNoContent();
        $this->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonPath('values.payroll::approve', false)
            ->assertJsonPath('values.reports::export', false);

        Permit::add($actor, 'role::auditor');
        $this->getJson($this->endpoint($subject))->assertOk()->assertJsonPath('values.payroll::export', false);
    }

    public function test_role_revocation_conflicts_with_an_open_editor_before_any_changes_are_saved(): void
    {
        $actor = $this->user();
        $subject = $this->user('Noor');
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE, 'role::admin']);
        $snapshot = $this->actingAs($actor)->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonPath('values.payroll::export', false);
        Permit::remove($actor, 'role::admin');

        $this->putJson($this->endpoint($subject), [
            'revision' => $snapshot->json('revision'),
            'values' => [...$snapshot->json('values'), 'reports::read' => true, 'payroll::export' => true],
        ])->assertConflict();
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $subject->getKey()]);
        $this->getJson($this->endpoint($subject))->assertOk()->assertJsonMissingPath('values.payroll::export');
    }

    public function test_self_editing_cannot_grant_a_protected_management_role(): void
    {
        $actor = $this->user();
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE]);
        $snapshot = $this->actingAs($actor)->getJson($this->endpoint($actor))->assertOk()
            ->assertJsonPath('isCurrentUser', true)
            ->assertJsonMissingPath('values.role::admin');

        $this->putJson($this->endpoint($actor), [
            'revision' => $snapshot->json('revision'),
            'values' => [...$snapshot->json('values'), 'role::admin' => true, 'payroll::export' => true],
        ])->assertNoContent();

        $this->getJson($this->endpoint($actor))->assertOk()
            ->assertJsonMissingPath('values.role::admin')
            ->assertJsonMissingPath('values.payroll::export');
    }

    #[DataProvider('releaseEditors')]
    public function test_either_group_role_can_edit_releases_but_publishing_requires_both_method_roles(string $role): void
    {
        $actor = $this->user();
        $subject = $this->user('Noor');
        Permit::add($actor, [Catalog::READ, Catalog::UPDATE, 'role::reviewer']);
        Permit::add($subject, 'releases::publish');
        $this->actingAs($actor)->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonMissing(['id' => 'releases']);

        Permit::add($actor, $role);
        Permit::remove($actor, 'role::reviewer');
        $snapshot = $this->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonPath('values.releases::read', false)
            ->assertJsonMissingPath('values.releases::publish');
        $this->putJson($this->endpoint($subject), [
            'revision' => $snapshot->json('revision'),
            'values' => [...$snapshot->json('values'), 'releases::read' => true, 'releases::publish' => false],
        ])->assertNoContent();

        Permit::add($actor, 'role::reviewer');
        $this->getJson($this->endpoint($subject))->assertOk()
            ->assertJsonPath('values.releases::read', true)
            ->assertJsonPath('values.releases::publish', true);
    }

    /** @return array<string, array{string}> */
    public static function releaseEditors(): array
    {
        return ['administrator' => ['role::admin'], 'developer' => ['role::developer']];
    }
}
