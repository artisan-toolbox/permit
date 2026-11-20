<!-- MAINTAINER:README_HEAD:START -->
<div id="maintainer-title" align="center">
    <img src="logo/permit-icon.svg" alt="" width="88">
    <h1>Permit</h1>
</div>

<p id="maintainer-badges" align="center">
<a href="https://github.com/artisan-toolbox/permit/releases"><img src="https://img.shields.io/badge/version-1.0.0--beta.3-blue?style=flat-square" alt="version"></a>
<a href="https://packagist.org/packages/artisan-toolbox/permit"><img src="https://img.shields.io/badge/Packagist-F28D1A?style=flat-square&amp;logo=packagist&amp;logoColor=white" alt="Packagist"></a>
<a href="https://atb.dev.br/packages/permit/#requirements"><img src="https://img.shields.io/badge/PHP-%5E8.5-777BB4?style=flat-square" alt="PHP"></a>
<a href="https://atb.dev.br/packages/permit/#requirements"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square" alt="Laravel"></a>
<a href="https://github.com/artisan-toolbox/permit/actions/workflows/tests.yml?query=branch%3A1.x"><img src="https://img.shields.io/github/actions/workflow/status/artisan-toolbox/permit/tests.yml?branch=1.x&amp;label=Tests&amp;style=flat-square" alt="Tests"></a>
<a href="https://atb.dev.br/packages/permit/"><img src="https://img.shields.io/badge/Docs-Read-blue?style=flat-square" alt="Docs"></a>
</p>

<p id="maintainer-description" align="center">
    Laravel authorization package for declaring Gates and managing per-user permissions and roles.
</p>
<!-- MAINTAINER:README_HEAD:END -->

Permit registers declared permissions and roles as Laravel Gates. Its public API is in beta. Check the [requirements](https://atb.dev.br/packages/permit/#requirements) before installing.

Run these commands from your Laravel application root. The application needs the user table used by its session guard.

```bash
composer require 'artisan-toolbox/permit:@beta'
php artisan vendor:publish --tag=permit-migrations
php artisan migrate
php artisan make:permission-group Users
```

In Laravel Tinker, replace `1` with an existing user's primary key. Grant the ability and verify it through Laravel's Gate.

```php
use App\Models\User;
use ArtisanToolbox\Permit\Facades\Permit;
use Illuminate\Support\Facades\Gate;

$user = User::findOrFail(1);
Permit::add($user, 'users::read');
Gate::forUser($user)->allows('users::read'); // true
```

See the [documentation](https://atb.dev.br/packages/permit/), [installation guide](https://atb.dev.br/packages/permit/installation/), [contribution guide](.github/CONTRIBUTING.md), [security policy](.github/SECURITY.md), [changelog](CHANGELOG.md), and [MIT license](LICENSE.md).
