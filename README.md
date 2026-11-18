<!-- MAINTAINER:README_HEAD:START -->
<div id="maintainer-title" align="center">
    <img src="logo/permit-icon.svg" alt="" width="88">
    <h1>Permit</h1>
</div>

<p id="maintainer-badges" align="center">
<a href="https://atb.dev.br/packages/permit/"><img src="https://img.shields.io/badge/Status-Unpublished%20preview-orange?style=flat-square" alt="Status"></a>
<a href="https://atb.dev.br/packages/permit/#requirements"><img src="https://img.shields.io/badge/PHP-%5E8.5-777BB4?style=flat-square" alt="PHP"></a>
<a href="https://atb.dev.br/packages/permit/#requirements"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square" alt="Laravel"></a>
<a href="https://github.com/artisan-toolbox/permit/actions/workflows/tests.yml?query=branch%3A1.x"><img src="https://img.shields.io/github/actions/workflow/status/artisan-toolbox/permit/tests.yml?branch=1.x&amp;label=Tests&amp;style=flat-square" alt="Tests"></a>
<a href="https://atb.dev.br/packages/permit/"><img src="https://img.shields.io/badge/Docs-Read-blue?style=flat-square" alt="Docs"></a>
</p>

<p id="maintainer-description" align="center">
    Laravel authorization package in preview. Roles and permissions are not implemented yet.
</p>
<!-- MAINTAINER:README_HEAD:END -->

Permit is an unpublished preview for PHP 8.5 and Laravel 13. Version `1.0.0-alpha.1` provides package metadata and Laravel service discovery. Authorization APIs are not available yet.

Install the development branch from a configured local checkout or repository. This command does not identify a stable release.

```bash
composer require artisan-toolbox/permit:1.x-dev
```

Read the installed preview version after Laravel discovers the service provider.

```php
use ArtisanToolbox\Permit\Permit;

$permit = app(Permit::class);

echo $permit::VERSION; // 1.0.0-alpha.1
```

See the [documentation](https://atb.dev.br/packages/permit/), [installation guide](https://atb.dev.br/packages/permit/installation/), [contribution guide](.github/CONTRIBUTING.md), [security policy](.github/SECURITY.md), [changelog](CHANGELOG.md), and [MIT license](LICENSE.md).
