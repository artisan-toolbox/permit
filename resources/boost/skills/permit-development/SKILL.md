---
name: permit-development
description: Use Artisan Toolbox Permit in a Laravel application to declare permissions and roles, authorize routes, assign access, and inspect or share Gate decisions.
license: MIT
metadata:
  author: Allan Mariucci Carvalho
---

# Use the Permit preview

## Primary goal

Declare and evaluate per-user Gates with the unpublished `artisan-toolbox/permit` preview. Check the installed package version before using the API below.

## Workflow

1. Use PHP 8.5 with Intl, Laravel 13, and Inertia 3. Start with the user table configured for the session guard.
2. Configure a Composer path repository for the reviewed source. Follow the [preview installation guide](https://atb.dev.br/docs/permit/installation). From the application root, run `composer require artisan-toolbox/permit:1.x-dev`.
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

## Authorization boundaries

- Authorize protected actions on the server. Use the Vue Gate results for presentation and Laravel Policies for record-level authorization.
- Use the facade or management routes for assignment changes so revisions and cache invalidation follow the same transaction. `allowAll()` and `denyAll()` include roles and management Gates.
- Assign the built-in `user_permissions::read` and `user_permissions::update` abilities to the users intended to administer permissions.
- Permit provides the management endpoint for Front Awesome's `UserPermissionEditor`. Follow [Connect the editor to Permit](https://atb.dev.br/docs/permit/management#enable-the-editor) for the operator access and Vue setup.
