---
name: permit-development
description: Install or inspect the unpublished Artisan Toolbox Permit preview in Laravel applications. Use for Permit package setup and version metadata, not for implementing roles, permissions, or unrelated Laravel authorization.
license: MIT
metadata:
  author: Allan Mariucci Carvalho
---

# Use the Permit preview

## Primary goal

Install the `artisan-toolbox/permit` preview and inspect its version. The current preview has no role, permission, policy, or gate integration API.

## Workflow

1. Confirm that the application uses PHP 8.5, Laravel 13, and Inertia 3.
2. Configure a Composer repository for the local Permit checkout or an accessible development repository. The preview is not a stable public release.
3. In the Laravel application's root directory, run `composer require artisan-toolbox/permit:1.x-dev`.
4. Let Laravel discover `ArtisanToolbox\Permit\PermitServiceProvider`. No configuration publishing or migration is required.
5. Resolve `ArtisanToolbox\Permit\Permit` from Laravel's container and read `Permit::VERSION`.

If the application needs the optional frontend metadata export, install it after Composer.

```bash
npm install --install-links @artisan-toolbox/permit@file:vendor/artisan-toolbox/permit
```

## References

- [Permit documentation](https://atb.dev.br/packages/permit/)
- [Install the preview](https://atb.dev.br/packages/permit/installation/)

## Examples

In the installed Laravel application, this code prints `1.0.0-alpha.1`.

```php
use ArtisanToolbox\Permit\Permit;

$permit = app(Permit::class);

echo $permit::VERSION;
```

The frontend entry point exports the same version.

```javascript
import { PERMIT_VERSION } from '@artisan-toolbox/permit';

console.log(PERMIT_VERSION); // 1.0.0-alpha.1
```

## Anti-patterns

- Do not invent role models, permission middleware, migrations, configuration keys, or authorization methods for this preview.
- Do not replace Laravel Gates and Policies with Permit until a documented authorization API exists.
- Do not install the frontend package from an npm registry. Composer supplies the package directory and compiled assets.
