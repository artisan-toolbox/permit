<?php

declare(strict_types=1);

use ArtisanToolbox\Permit\Http\Controllers\UserPermissions;
use ArtisanToolbox\Permit\Http\Middleware\AuthenticateManagement;
use ArtisanToolbox\Permit\Http\Middleware\ManagementJson;
use Illuminate\Support\Facades\Route;

Route::middleware([ManagementJson::class, 'web', AuthenticateManagement::class])->group(function (): void {
    Route::get('permit/user-permissions', [UserPermissions::class, 'show'])->name('permit.user-permissions.show');
    Route::put('permit/user-permissions', [UserPermissions::class, 'update'])->name('permit.user-permissions.update');
});
