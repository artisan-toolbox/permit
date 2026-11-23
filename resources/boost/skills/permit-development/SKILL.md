---
name: permit-development
description: Use Artisan Toolbox Permit in a Laravel application to declare permissions and roles, authorize routes, restrict assignment editing by role, configure permission editor icons, and inspect or share Gate decisions.
license: MIT
metadata:
  author: Allan Mariucci Carvalho
---

# Use Permit

## Primary goal

Declare and evaluate per-user Gates with the `artisan-toolbox/permit` beta. Check the installed package version before using the API below.

## Workflow

1. Use PHP 8.5 with Intl, Laravel 13, and Inertia 3. Start with the user table configured for the session guard.
2. From the application root, run `composer require 'artisan-toolbox/permit:@beta'`. For a local source checkout, follow the [preview installation guide](https://atb.dev.br/docs/permit/installation).
3. Publish `permit-config` with `php artisan vendor:publish --tag=permit-config` if the application needs to set its guard or assignment table. Set those values before migration.
4. Run `php artisan vendor:publish --tag=permit-migrations`, then `php artisan migrate`.
5. Run `php artisan make:permission-group Users`. The generated group declares `users::create`, `users::read`, `users::update`, and `users::delete`.

For Vue, install the Composer-delivered package after Composer and register `createPermit()` on the Vue app after the Inertia plugin.

```bash
npm install --install-links @artisan-toolbox/permit@file:vendor/artisan-toolbox/permit
```

## References

- [Permit documentation](https://atb.dev.br/packages/permit/)
- [Install the preview](https://atb.dev.br/packages/permit/installation/)

## Examples

Run this example in Laravel Tinker in a disposable application. Replace `1` with the primary key of a test user. The example saves an explicit grant and then an explicit denial.

```php
use App\Models\User;
use ArtisanToolbox\Permit\Facades\Permit;
use Illuminate\Support\Facades\Gate;

$user = User::findOrFail(1);
Permit::add($user, 'users::read');
Gate::forUser($user)->allows('users::read'); // true
Permit::remove($user, 'users::read');
Gate::forUser($user)->allows('users::read'); // false
```

Use `->middleware('permit:users::read')` to protect a route. A method with `#[Role]` on an interface marked with `#[Roles]` declares a separate `role::methodName` Gate. Attach `->middleware('permit:role::admin')` after declaring an `admin` role. The `role` group ID is reserved.

Inspect a user's stored assignments with `php artisan permit:inspect <user-id>`. Use `--defaults` for catalog defaults. Rebuild the catalog after declaration changes with `php artisan permit:catalog:cache` or `php artisan optimize`.

Use the Blueprint macros `renamePermissionGroup()`, `renamePermission()`, and `renameRole()` to move saved keys with an application migration. Update the declarations and rebuild the catalog in the same deployment. See the [Artisan command reference](https://atb.dev.br/docs/permit/operations#artisan-commands) and [reversible migration example](https://atb.dev.br/docs/permit/operations#rename-assignment-keys).

The Vue plugin exposes the actor's Inertia Gate results through `<Gate>`, `$gate`, and `useGate()`.

```vue
<Gate allows="users::read">
    <a href="/users">Users</a>
</Gate>
```

### Restrict actions and assignment edits by role

These attributes require Permit `1.0.0-beta.4` or later. Declare `admin()` and `developer()` role methods first. In a permission group, import `WithRole` and `ManageableByRoleUser` from `ArtisanToolbox\Permit\Attributes` and the enum `ArtisanToolbox\Permit\RoleMatch`.

```php
#[Permission('Refund invoices')]
#[WithRole('admin,developer', RoleMatch::Or)]
#[ManageableByRoleUser('admin,developer', RoleMatch::And)]
public function refund(): void;
```

In this example, the user needs `invoices::refund` and either role to refund an invoice. The operator needs both roles to see or edit the refund assignment. Saving other changes preserves its hidden value or default.

With one string argument, `WithRole` defaults to `RoleMatch::And` and `ManageableByRoleUser` defaults to `RoleMatch::Or`. Supply exact role method names separated by commas. Permit trims spaces and ignores duplicates. Empty entries and undeclared roles fail catalog validation. Apply either attribute to the group to cover generated CRUD keys and custom permissions. The user must satisfy both the group and method declarations, each in its own mode.

If only administrators should grant the role itself, add `#[ManageableByRoleUser('admin')]` to the `admin()` role declaration. Seed the first administrator with `Permit::add($operator, 'role::admin')` in trusted application code. Follow the [declaration example](https://atb.dev.br/docs/permit/declarations#require-a-role) and the [editor walkthrough](https://atb.dev.br/docs/permit/management#restrict-assignment-edits).

### Show icons in the permission editor

Use Permit 1.0.0-beta.2 and Front Awesome 1.0.0-beta.6 or later to show catalog icons. Follow the [editor setup guide](https://atb.dev.br/docs/permit/management#enable-the-editor). In `app/Permit/Reports.php`, declare a group and permission with Font Awesome icon names.

```php
<?php

namespace App\Permit;

use ArtisanToolbox\Permit\Attributes\Group;
use ArtisanToolbox\Permit\Attributes\Permission;

#[Group('reports', 'Reports', icon: 'chart-column')]
interface Reports
{
    #[Permission('Export reports', icon: 'file-export')]
    public function export(): void;
}
```

The editor shows `chart-column` beside Reports and `file-export` beside Export reports. Use lowercase names such as `file-export`. Rebuild the catalog if it is cached when you change a declaration.

In `config/permit.php`, `icons.family` defaults to `classic`. Choose a family available through your Front Awesome icon provider. Edit `icons.crud` for permissions generated by `#[CRUD]` and `icons.user_permissions` for the built-in management group and its read and update permissions. Set a default icon to `null` to hide it, or set `icons.enabled` to `false` to hide all icons. Refresh Laravel's configuration cache after changes to a cached configuration.

## Authorization boundaries

- Authorize protected actions on the server. Use the Vue Gate results for presentation and Laravel Policies for record-level authorization.
- Use the facade or management routes for assignment changes so revisions and cache invalidation follow the same transaction. `allowAll()` and `denyAll()` include roles and management Gates.
- Assign the built-in `user_permissions::read` and `user_permissions::update` abilities to the users intended to administer permissions.
- Permit provides the management endpoint for Front Awesome's `UserPermissionEditor`. Follow [Connect the editor to Permit](https://atb.dev.br/docs/permit/management#enable-the-editor) for the operator access and Vue setup.
