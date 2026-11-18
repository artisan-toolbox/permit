<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Permit;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

it('validates and rebuilds the frontend before versioning', function () {
    Process::fake();

    Permit::beforeVersioning('1.0.0-alpha.1', '1.0.0-alpha.2');

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['npm', 'run', 'release:build']
        && $process->path === dirname(__DIR__, 2)
        && $process->timeout === null);
});

it('stops versioning when frontend validation fails', function () {
    Process::fake([
        '*' => Process::result(errorOutput: 'Frontend validation failed.', exitCode: 1),
    ]);

    expect(fn () => Permit::beforeVersioning('1.0.0-alpha.1', '1.0.0-alpha.2'))
        ->toThrow(ProcessFailedException::class, 'Frontend validation failed.');
});
