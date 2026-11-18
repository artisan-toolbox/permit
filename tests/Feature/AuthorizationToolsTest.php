<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests\Feature;

use ArtisanToolbox\Permit\Catalog\CatalogCompiler;
use ArtisanToolbox\Permit\Facades\Permit;
use ArtisanToolbox\Permit\Storage\AssignmentConflict;
use ArtisanToolbox\Permit\Tests\AuthorizationTestCase;
use ArtisanToolbox\Permit\Tests\Fixtures\PermitUlidUser;
use ArtisanToolbox\Permit\Tests\Fixtures\PermitUuidUser;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

final class AuthorizationToolsTest extends AuthorizationTestCase
{
    public function test_inspect_prints_assignments_and_can_show_defaults_without_database_reads(): void
    {
        $user = $this->user('María');
        Permit::add($user, 'users::read');
        Permit::remove($user, 'users::print');

        self::assertSame(0, Artisan::call('permit:inspect', ['user' => $user->getKey(), '--no-ansi' => true]));
        $details = Artisan::output();
        self::assertStringContainsString('María', $details);
        self::assertMatchesRegularExpression('/Read\s*\.+\s*Allowed/', $details);
        self::assertMatchesRegularExpression('/Print user data\s*\.+\s*Denied/', $details);
        self::assertMatchesRegularExpression('/Auditor\s*\.+\s*Default · Allowed/', $details);

        self::assertSame(0, Artisan::call('permit:inspect '.$user->getKey().' --no-ansi'));
        self::assertSame($details, Artisan::output());

        DB::enableQueryLog();
        foreach ([['--defaults' => true], ['--no-interaction' => true]] as $arguments) {
            self::assertSame(0, Artisan::call('permit:inspect', $arguments));
            $catalog = Artisan::output();
            self::assertStringContainsString('Catalog defaults', $catalog);
            self::assertMatchesRegularExpression('/Print user data\s*\.+\s*Default · Allowed/', $catalog);
        }
        self::assertSame([], DB::getQueryLog());
        DB::disableQueryLog();

        self::assertSame(1, Artisan::call('permit:inspect', ['user' => '999']));
        self::assertSame(1, Artisan::call('permit:inspect', ['user' => $user->getKey(), '--defaults' => true]));
        self::assertStringContainsString('Choose a user or the --defaults option.', Artisan::output());
    }

    public function test_editor_localizes_crud_labels_and_preserves_locked_order(): void
    {
        $actor = $this->user();
        Permit::add($actor, 'user_permissions::read');
        $this->actingAs($actor);
        app()->setLocale('pt_BR');
        $this->getJson($this->endpoint($actor))
            ->assertOk()
            ->assertJsonPath('sections.0.name', 'Permissões de usuários')
            ->assertJsonPath('sections.1.order', 'locked')
            ->assertJsonPath('sections.1.abilities.0.name', 'Criar')
            ->assertJsonPath('sections.1.abilities.1.name', 'Ler')
            ->assertJsonPath('sections.1.abilities.2.name', 'Atualizar')
            ->assertJsonPath('sections.1.abilities.3.key', 'users::print')
            ->assertJsonPath('sections.1.abilities.4.key', 'users::audit')
            ->assertJsonPath('sections.2.name', 'Papéis');
    }

    public function test_global_optimization_calls_catalog_commands_and_exclusions_work(): void
    {
        $path = $this->temporaryPath.'/cache/permit.php';
        $options = ['--except' => 'config,events,routes,views'];
        $clearOptions = ['--except' => 'config,cache,compiled,events,routes,views'];
        self::assertSame(0, Artisan::call('optimize', $options));
        self::assertFileExists($path);
        $compiler = resolve(CatalogCompiler::class);
        $expected = $compiler->discover()->sections;

        app()->useAppPath($this->temporaryPath.'/without-source');
        self::assertSame($expected, $compiler->load()->sections);
        self::assertSame(0, Artisan::call('optimize:clear', ['--except' => $clearOptions['--except'].',permit']));
        self::assertFileExists($path);
        self::assertSame(0, Artisan::call('optimize:clear', $clearOptions));
        self::assertFileDoesNotExist($path);
        self::assertSame(0, Artisan::call('permit:catalog:clear'));
        self::assertFalse($compiler->load()->has('users::print'));
    }

    public function test_generator_and_compiler_discover_new_interfaces_from_source(): void
    {
        $this->withDeclarationFiles(function (string $path, string $namespace): void {
            self::assertSame(0, Artisan::call('make:permission-group', ['name' => 'Billing/Invoices']));
            self::assertFileExists($path.'/Permit/Billing/Invoices.php');
            self::assertStringContainsString("namespace {$namespace}\\Permit\\Billing", (string) file_get_contents($path.'/Permit/Billing/Invoices.php'));

            self::assertSame(0, Artisan::call('permit:catalog:cache'));
            self::assertTrue(resolve(CatalogCompiler::class)->load()->has('invoices::read'));
        });
    }

    public function test_rebuilding_a_cached_catalog_rejects_a_new_host_gate_collision(): void
    {
        self::assertSame(0, Artisan::call('permit:catalog:cache'));
        $artifact = $this->temporaryPath.'/cache/permit.php';
        $previous = file_get_contents($artifact);
        Gate::define('external::read', static fn (): bool => true);

        $this->withDeclarationFiles(function (string $appPath, string $namespace): void {
            (new Filesystem)->put($appPath.'/Permit/External.php', "<?php\nnamespace {$namespace}\\Permit;\n"
                .'use ArtisanToolbox\\Permit\\Attributes\\{Group, CRUD};'
                ."\n#[Group('external', 'External')] #[CRUD] interface External {}");
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('The Laravel Gate [external::read] already exists.');
            Artisan::call('permit:catalog:cache');
        }, after: function () use ($artifact, $previous): void {
            self::assertSame($previous, file_get_contents($artifact));
        });
    }

    #[DataProvider('invalidDeclarations')]
    public function test_invalid_declarations_reject_the_build_and_preserve_the_previous_catalog(string $declaration, string $message): void
    {
        Artisan::call('permit:catalog:cache');
        $path = $this->temporaryPath.'/cache/permit.php';
        $previous = file_get_contents($path);
        $this->withDeclarationFiles(function (string $appPath, string $namespace) use ($declaration, $message): void {
            (new Filesystem)->put($appPath.'/Permit/InvalidDefinition.php', "<?php\nnamespace {$namespace}\\Permit;\n"
                .'use ArtisanToolbox\\Permit\\Attributes\\{ Group, CRUD, Permission, Roles, Role };'."\n".$declaration);
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($message);
            Artisan::call('permit:catalog:cache');
        }, after: function () use ($path, $previous): void {
            self::assertSame($previous, file_get_contents($path));
        });
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDeclarations(): iterable
    {
        yield 'reserved' => ["#[Group('role', 'Roles')] #[CRUD] interface InvalidDefinition {}", 'Invalid or reserved group ID'];
        yield 'bad placement' => ["#[Group('example', 'Example')] #[CRUD] class InvalidDefinition {}", 'standalone interface'];
        yield 'bad member placement' => ["class InvalidDefinition { #[Group('example', 'Example')] public const int Wrong = 1; }", 'interfaces and their methods'];
        yield 'collision' => ["#[Group('example', 'Example')] #[CRUD] interface InvalidDefinition { #[Permission('Read')] public function Read(): void; }", 'Duplicate Permit ability'];
        yield 'empty' => ["#[Group('example', 'Example')] interface InvalidDefinition {}", 'requires a name and at least one ability'];
        yield 'role marker' => ['#[Roles] #[CRUD] interface InvalidDefinition {}', 'belong on permission groups'];
        yield 'string CRUD boolean' => ["#[Group('example', 'Example')] #[CRUD(read: 'false')] interface InvalidDefinition {}", 'must be of type bool'];
        yield 'string permission default' => ["#[Group('example', 'Example')] interface InvalidDefinition { #[Permission('Report', default: 'false')] public function report(): void; }", 'must be of type bool'];
    }

    public function test_renames_preserve_exact_keys_rotate_revision_and_skip_pretend(): void
    {
        $user = $this->user();
        Permit::add($user, ['users::read', 'role::admin']);
        Permit::remove($user, 'users::print');
        $initial = DB::table('user_permissions')->value('revision');

        $connection = DB::connection();
        $connection->pretend(static fn () => DB::connection()->getSchemaBuilder()->table('user_permissions', static fn (Blueprint $table) => $table->renamePermissionGroup('users', 'accounts')));
        self::assertSame($initial, DB::table('user_permissions')->value('revision'));

        Schema::table('user_permissions', static fn (Blueprint $table) => $table->renamePermission('users', 'read', 'audit'));
        app()->forgetScopedInstances();
        self::assertTrue(Gate::forUser($user)->allows('users::audit'));
        self::assertFalse(Gate::forUser($user)->allows('users::read'));
        Schema::table('user_permissions', static fn (Blueprint $table) => $table->renamePermissionGroup('user', 'profile'));
        Schema::table('user_permissions', static fn (Blueprint $table) => $table->renamePermissionGroup('users', 'accounts'));
        Schema::table('user_permissions', static fn (Blueprint $table) => $table->renameRole('admin', 'operator'));
        $row = DB::table('user_permissions')->first();
        self::assertNotNull($row);
        self::assertNotSame($initial, $row->revision);
        self::assertEquals(['accounts::audit' => true, 'accounts::print' => false], json_decode($row->permissions, true, flags: JSON_THROW_ON_ERROR));
        self::assertEquals(['role::operator' => true], json_decode($row->roles, true, flags: JSON_THROW_ON_ERROR));

        Schema::table('user_permissions', static fn (Blueprint $table) => $table->renamePermissionGroup('users', 'accounts'));
        self::assertSame($row->revision, DB::table('user_permissions')->value('revision'));
    }

    public function test_rename_collision_rolls_back_prior_rows(): void
    {
        $first = $this->user();
        $second = $this->user();
        Permit::add($first, 'users::read');
        Permit::add($second, ['users::read', 'users::print']);
        $before = DB::table('user_permissions')->orderBy('user_id')->get()->all();

        try {
            Schema::table('user_permissions', static fn (Blueprint $table) => $table->renamePermission('users', 'read', 'print'));
            self::fail('Expected a rename collision.');
        } catch (AssignmentConflict) {
            self::assertEquals($before, DB::table('user_permissions')->orderBy('user_id')->get()->all());
        }
    }

    /** @param class-string<PermitUuidUser|PermitUlidUser> $class */
    #[DataProvider('stringKeyTypes')]
    public function test_migration_and_facade_support_string_primary_keys(string $class, string $type): void
    {
        $model = new $class;
        Schema::create($model->getTable(), static function (Blueprint $table) use ($type): void {
            $table->{$type}('id')->primary();
        });
        config(['auth.providers.users.model' => $class, 'permit.table' => 'string_permits']);
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_18_000000_create_user_permissions_table.php';
        $migration->up();
        $model->save();

        Permit::add($model, 'users::read');
        self::assertTrue(Gate::forUser($model)->allows('users::read'));
        Permit::remove($model->getKey(), 'users::read');
        self::assertFalse(Gate::forUser($model)->allows('users::read'));
        $model->delete();
        $this->assertDatabaseCount('string_permits', 0);
    }

    /** @return iterable<string, array{class-string<PermitUuidUser|PermitUlidUser>, string}> */
    public static function stringKeyTypes(): iterable
    {
        yield 'UUID' => [PermitUuidUser::class, 'uuid'];
        yield 'ULID' => [PermitUlidUser::class, 'ulid'];
    }

    /**
     * @param  Closure(string, string): void  $exercise
     * @param  (Closure(): void)|null  $after
     */
    private function withDeclarationFiles(Closure $exercise, ?Closure $after = null): void
    {
        $path = $this->temporaryPath.'/app';
        (new Filesystem)->ensureDirectoryExists($path.'/Permit');
        $namespace = 'PermitFixture'.Str::random(10);
        app()->useAppPath($path);
        (new ReflectionProperty(Application::class, 'namespace'))->setValue(app(), $namespace.'\\');
        $loader = static function (string $class) use ($path, $namespace): void {
            $prefix = $namespace.'\\';

            if (str_starts_with($class, $prefix)) {
                $file = $path.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

                if (is_file($file)) {
                    require $file;
                }
            }
        };
        spl_autoload_register($loader);

        try {
            $exercise($path, $namespace);
        } finally {
            spl_autoload_unregister($loader);
            $after?->__invoke();
        }
    }
}
