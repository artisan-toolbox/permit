<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Console\Commands;

use ArtisanToolbox\Permit\Console\PhpLiteral;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Description('Create a Permit permission group interface')]
final class MakePermissionGroup extends GeneratorCommand
{
    protected $name = 'make:permission-group';

    protected $type = 'Permission group';

    protected function getStub(): string
    {
        return __DIR__.'/../stubs/group.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Permit';
    }

    protected function qualifyClass($name): string
    {
        $name = parent::qualifyClass($name);
        $prefix = $this->rootNamespace().'Permit\\';

        throw_unless(str_starts_with($name, $prefix), InvalidArgumentException::class, 'The interface must be inside the application Permit namespace.');
        foreach (explode('\\', substr($name, strlen($prefix))) as $segment) {
            throw_if(preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $segment) !== 1, InvalidArgumentException::class, 'The permission group name must be a valid class name.');
        }
        $this->id($name);

        return $name;
    }

    protected function buildClass($name): string
    {
        return str_replace(
            ['{{ id }}', '{{ name }}'],
            [PhpLiteral::encode($this->id($name)), PhpLiteral::encode(Str::headline(class_basename($name)))],
            parent::buildClass($name),
        );
    }

    private function id(string $name): string
    {
        $id = Str::snake(class_basename($name));

        throw_if(preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $id) !== 1 || in_array($id, ['role', 'user_permissions'], true), InvalidArgumentException::class, "Invalid or reserved Permit group ID [{$id}].");

        return $id;
    }
}
