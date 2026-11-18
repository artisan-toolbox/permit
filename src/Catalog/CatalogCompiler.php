<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Catalog;

use ArtisanToolbox\Permit\Attributes\CRUD;
use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\LockedOrder;
use ArtisanToolbox\Permit\Attributes\Permission;
use ArtisanToolbox\Permit\Attributes\Role;
use ArtisanToolbox\Permit\Attributes\Roles;
use ArtisanToolbox\Permit\Console\PhpLiteral;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type Ability from Catalog
 * @phpstan-import-type Section from Catalog
 */
final readonly class CatalogCompiler
{
    private const int FORMAT = 1;

    private const array ATTRIBUTES = [Group::class, Permission::class, Role::class, Roles::class, CRUD::class, LockedOrder::class];

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private GateRegistration $gates,
    ) {}

    public function load(): Catalog
    {
        $path = $this->path();

        if (is_file($path)) {
            try {
                $cached = require $path;
                $sections = $this->readCache($cached);

                if ($sections !== null) {
                    return new Catalog($sections);
                }
            } catch (Throwable) {
            }
        }

        return $this->discover();
    }

    public function cache(): void
    {
        $catalog = $this->discover();
        $this->gates->assertAvailable($catalog);
        $path = $this->path();
        $this->files->ensureDirectoryExists(dirname($path));
        $data = PhpLiteral::encode(['format' => self::FORMAT, 'sections' => $catalog->sections]);
        $this->files->replace($path, "<?php\n\nreturn {$data};\n");
    }

    public function clear(): void
    {
        throw_if(is_file($this->path()) && ! $this->files->delete($this->path()), RuntimeException::class, 'Unable to remove the Permit catalog cache.');
    }

    public function discover(): Catalog
    {
        $sections = [$this->management()];
        $directory = $this->app->path('Permit');

        if (! is_dir($directory)) {
            return new Catalog($sections);
        }

        $namespace = $this->app->getNamespace().'Permit\\';
        foreach ($this->files->allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $class = $namespace.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
            $class = str_replace(DIRECTORY_SEPARATOR, '\\', $class);

            if (! interface_exists($class) && ! class_exists($class) && ! trait_exists($class)) {
                continue;
            }
            $source = new ReflectionClass($class);

            try {
                $section = $this->section($source);
            } catch (Throwable $error) {
                throw new InvalidArgumentException("Invalid Permit declaration [{$class}]: {$error->getMessage()}", previous: $error);
            }

            if ($section !== null) {
                $sections[] = $section;
            }
        }

        return new Catalog($sections);
    }

    /** @param ReflectionClass<object> $source
     * @return Section|null
     */
    private function section(ReflectionClass $source): ?array
    {
        $group = $source->getAttributes(Group::class);
        $roles = $source->getAttributes(Roles::class);
        $crud = $source->getAttributes(CRUD::class);
        $locked = $source->getAttributes(LockedOrder::class);
        $methods = array_values(array_filter($source->getMethods(), fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $source->getName()));
        $hasMarker = $group !== [] || $roles !== [];

        throw_if($source->getAttributes(Permission::class) !== [] || $source->getAttributes(Role::class) !== [], InvalidArgumentException::class, 'Permission and role attributes belong on declaration methods.');
        foreach ([...$source->getProperties(), ...$source->getReflectionConstants()] as $member) {
            throw_if(array_any(self::ATTRIBUTES, fn (string $attribute): bool => $member->getAttributes($attribute) !== []), InvalidArgumentException::class, 'Permit attributes belong on declaration interfaces and their methods.');
        }
        foreach ($methods as $method) {
            foreach ($method->getParameters() as $parameter) {
                throw_if(array_any(self::ATTRIBUTES, fn (string $attribute): bool => $parameter->getAttributes($attribute) !== []), InvalidArgumentException::class, 'Permit attributes belong on declaration interfaces and their methods.');
            }
        }

        if (! $hasMarker) {
            throw_if($crud !== [] || $locked !== [] || array_any($methods, $this->hasPermitMethodAttribute(...)), InvalidArgumentException::class, 'Permit attributes require a group or roles interface.');

            return null;
        }

        throw_if(! $source->isInterface() || $source->getInterfaces() !== [] || ($group !== [] && $roles !== []), InvalidArgumentException::class, 'Use a standalone interface with one group or roles declaration.');

        throw_if($roles !== [] && ($crud !== [] || $locked !== []), InvalidArgumentException::class, 'CRUD and LockedOrder belong on permission groups.');

        throw_if(count($group) > 1 || count($roles) > 1 || count($crud) > 1 || count($locked) > 1, InvalidArgumentException::class, 'Permit declaration attributes cannot be repeated.');

        $roleSection = $roles !== [];
        $declaration = $group === [] ? null : $this->instantiate($group[0]);

        if ($roleSection) {
            $this->instantiate($roles[0]);
        }

        if ($locked !== []) {
            $this->instantiate($locked[0]);
        }

        if ($declaration !== null && (! preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $declaration->id) || in_array($declaration->id, ['role', 'user_permissions'], true))) {
            throw new InvalidArgumentException("Invalid or reserved group ID [{$declaration->id}].");
        }

        $id = $declaration->id ?? 'role';
        $keyPrefix = $roleSection ? 'role' : $id;
        $abilities = $crud === [] ? [] : $this->crud($keyPrefix, $source->getName(), $this->instantiate($crud[0]));
        usort($methods, fn (ReflectionMethod $first, ReflectionMethod $second): int => $first->getStartLine() <=> $second->getStartLine());

        foreach ($methods as $method) {
            $abilities[] = $this->ability($method, $keyPrefix, $roleSection);
        }

        return [
            'kind' => $roleSection ? 'roles' : 'permissions',
            'id' => $id,
            'name' => $declaration->name ?? 'permit::catalog.roles.name',
            'description' => $roleSection ? 'permit::catalog.roles.description' : $declaration?->description,
            'order' => $locked === [] ? 'alphabetical' : 'locked',
            'source' => $source->getName(),
            'abilities' => $abilities,
        ];
    }

    private function hasPermitMethodAttribute(ReflectionMethod $method): bool
    {
        return array_any(
            self::ATTRIBUTES,
            fn (string $attribute): bool => $method->getAttributes($attribute) !== [],
        );
    }

    /** @return Ability */
    private function ability(ReflectionMethod $method, string $prefix, bool $roles): array
    {
        $attribute = $roles ? Role::class : Permission::class;
        $attributes = $method->getAttributes($attribute);
        $permitAttributes = array_filter(
            self::ATTRIBUTES,
            fn (string $name): bool => $method->getAttributes($name) !== [],
        );
        $type = $method->getReturnType();

        if (count($attributes) !== 1 || count($permitAttributes) !== 1 || ! $method->isPublic()
            || $method->isStatic() || $method->returnsReference() || $method->getNumberOfParameters() !== 0
            || ($type !== null && (! $type instanceof ReflectionNamedType || $type->getName() !== 'void'))) {
            throw new InvalidArgumentException("Method [{$method->getName()}] must be a public, non-static declaration with one matching Permit attribute and no arguments.");
        }

        $definition = $this->instantiate($attributes[0]);

        return [
            'key' => $prefix.'::'.$method->getName(),
            'name' => $definition->name,
            'description' => $definition->description,
            'default' => $definition->default,
            'source' => $method->getDeclaringClass()->getName().'::'.$method->getName(),
        ];
    }

    /**
     * @template AttributeType of object
     *
     * @param  ReflectionAttribute<AttributeType>  $attribute
     * @return AttributeType
     */
    private function instantiate(ReflectionAttribute $attribute): object
    {
        $name = $attribute->getName();
        $arguments = $attribute->getArguments();
        $constructor = (new ReflectionClass($name))->getConstructor();

        throw_if(count($arguments) > ($constructor?->getNumberOfParameters() ?? 0), InvalidArgumentException::class, "Too many arguments for Permit attribute [{$name}].");

        return new $name(...$arguments);
    }

    /** @return list<Ability> */
    private function crud(string $id, string $source, CRUD $crud): array
    {
        $abilities = [];
        foreach (['create', 'read', 'update', 'delete'] as $action) {
            if ($crud->{$action}) {
                $abilities[] = [
                    'key' => "{$id}::{$action}",
                    'name' => "permit::catalog.crud.{$action}.name",
                    'description' => "permit::catalog.crud.{$action}.description",
                    'default' => false,
                    'source' => "{$source} #[CRUD({$action})]",
                ];
            }
        }

        return $abilities;
    }

    /** @return Section */
    private function management(): array
    {
        $abilities = [];
        foreach (['read', 'update'] as $action) {
            $abilities[] = [
                'key' => 'user_permissions::'.$action,
                'name' => "permit::catalog.management.{$action}.name",
                'description' => "permit::catalog.management.{$action}.description",
                'default' => false,
                'source' => 'Permit UserPermissions',
            ];
        }

        return [
            'kind' => 'permissions',
            'id' => 'user_permissions',
            'name' => 'permit::catalog.management.name',
            'description' => 'permit::catalog.management.description',
            'order' => 'alphabetical',
            'source' => 'Permit UserPermissions',
            'abilities' => $abilities,
        ];
    }

    /** @return list<Section>|null */
    private function readCache(mixed $cached): ?array
    {
        if (! is_array($cached) || ($cached['format'] ?? null) !== self::FORMAT
            || ! is_array($cached['sections'] ?? null) || ! array_is_list($cached['sections'])
            || ($cached['sections'][0] ?? null) !== $this->management()) {
            return null;
        }
        foreach ($cached['sections'] as $section) {
            if (! is_array($section) || ! in_array($section['kind'] ?? null, ['permissions', 'roles'], true)
                || ! in_array($section['order'] ?? null, ['alphabetical', 'locked'], true)
                || ! is_string($section['id'] ?? null) || ! is_string($section['name'] ?? null)
                || ! is_string($section['source'] ?? null) || ! array_key_exists('description', $section)
                || ($section['description'] !== null && ! is_string($section['description']))
                || ! is_array($section['abilities'] ?? null) || ! array_is_list($section['abilities'])) {
                return null;
            }
            foreach ($section['abilities'] as $ability) {
                if (! is_array($ability) || ! is_string($ability['key'] ?? null) || ! is_string($ability['name'] ?? null)
                    || ! is_string($ability['source'] ?? null) || ! is_bool($ability['default'] ?? null)
                    || ! array_key_exists('description', $ability) || ($ability['description'] !== null && ! is_string($ability['description']))) {
                    return null;
                }
            }
        }

        /** @var list<Section> $sections */
        $sections = $cached['sections'];

        return $sections;
    }

    private function path(): string
    {
        return $this->app->bootstrapPath('cache/permit.php');
    }
}
