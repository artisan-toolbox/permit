<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Console\Commands;

use ArtisanToolbox\Permit\Catalog\Catalog;
use ArtisanToolbox\Permit\Storage\Assignments;
use ArtisanToolbox\Permit\Storage\AssignmentStore;
use ArtisanToolbox\Permit\Storage\Users;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Formatter\OutputFormatter;

use function Laravel\Prompts\search;

#[Description('Inspect Permit assignments or catalog defaults')]
#[Signature('permit:inspect {user? : The primary key of the user} {--defaults : Show catalog defaults}')]
final class InspectAssignments extends Command
{
    private const string DEFAULTS = 'catalog-defaults';

    public function handle(Catalog $catalog, Translator $translator, Users $users, AssignmentStore $store): int
    {
        $id = $this->argument('user');
        $defaults = $this->option('defaults');

        if ($defaults && $id !== null) {
            $this->components->error('Choose a user or the --defaults option.');

            return self::FAILURE;
        }

        if (! $defaults && $id === null && $this->input->isInteractive()) {
            $id = $this->choose($users);
        }
        $assignments = new Assignments;

        if (is_string($id) || is_int($id)) {
            try {
                $subject = $users->find($id);
            } catch (ModelNotFoundException) {
                $this->components->error('The selected user was not found.');

                return self::FAILURE;
            }
            $assignments = $store->snapshot($subject);
            $this->components->info($this->text('subject', ['subject' => $this->label($users, $subject)]));
        } else {
            $this->components->info($this->text('defaults'));
        }

        foreach ($catalog->present($translator, app()->getLocale()) as $section) {
            $this->newLine();
            $this->components->twoColumnDetail('<options=bold>'.$this->clean($section['name']).'</>');
            foreach ($section['abilities'] as $ability) {
                $stored = $assignments->value($ability['key']);
                $allowed = $stored ?? $catalog->defaults[$ability['key']];
                $status = $this->text($allowed ? 'allowed' : 'denied');

                if ($stored === null) {
                    $status = $this->text('default_status', ['status' => $status]);
                }
                $color = $stored === null ? 'yellow' : ($allowed ? 'green' : 'red');
                $this->components->twoColumnDetail($this->clean($ability['name']), "<fg={$color}>{$status}</>");
            }
        }

        return self::SUCCESS;
    }

    private function choose(Users $users): ?string
    {
        $model = $users->model();
        $id = $model->getQualifiedKeyName();
        $name = $model->qualifyColumn('name');
        $hasName = $users->connection()->getSchemaBuilder()->hasColumn($model->getTable(), 'name');
        $selected = search(
            label: $this->text('search'),
            options: function (string $value) use ($model, $users, $id, $name, $hasName): array {
                $query = $users->query();

                if ($value !== '') {
                    $query->where(function (Builder $query) use ($model, $users, $id, $name, $hasName, $value): void {
                        if ($users->validId($value, $model)) {
                            $query->where($id, $value);
                        } elseif (! $hasName) {
                            $query->whereRaw('1 = 0');
                        }

                        if ($hasName) {
                            $query->orWhereLike($name, '%'.$value.'%');
                        }
                    });
                }
                $matches = [self::DEFAULTS => $this->text('defaults')];

                if ($hasName) {
                    $query->orderBy($name);
                }
                foreach ($query->orderBy($id)->limit(20)->get($hasName ? [$id, $name] : [$id]) as $user) {
                    $matches['user:'.$users->id($user)] = $this->label($users, $user);
                }

                return $matches;
            },
            placeholder: $this->text('placeholder'),
        );

        return $selected === self::DEFAULTS ? null : substr((string) $selected, strlen('user:'));
    }

    private function label(Users $users, Model $user): string
    {
        $name = $user->getAttribute('name');
        $id = '#'.$users->id($user);

        return $this->clean(is_string($name) && trim($name) !== '' ? "{$name} ({$id})" : $id);
    }

    private function clean(string $text): string
    {
        return OutputFormatter::escape(preg_replace('/[\p{Cc}\p{Cf}]/u', '', $text) ?? '');
    }

    /** @param array<string, string> $replace */
    private function text(string $key, array $replace = []): string
    {
        return (string) __('permit::catalog.inspect.'.$key, $replace);
    }
}
