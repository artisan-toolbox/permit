<div align="center">
    <h1>Permit</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/artisan-toolbox/permit"><img src="https://img.shields.io/packagist/v/artisan-toolbox/permit.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/artisan-toolbox/permit"><img src="https://img.shields.io/packagist/php-v/artisan-toolbox/permit.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/artisan-toolbox/permit"><img src="https://badge.laravel.cloud/badge/artisan-toolbox/permit?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/artisan-toolbox/permit/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/artisan-toolbox/permit/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/artisan-toolbox/permit"><img src="https://img.shields.io/packagist/dt/artisan-toolbox/permit.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Permit is a Laravel authorization package that builds on Gates and Policies to provide a simple, expressive API for managing user roles and permissions.

## Installation

You can install the package via Composer:

```bash
composer require artisan-toolbox/permit
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="permit"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="permit-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="permit-migrations"
php artisan migrate
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Permit! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Allan Mariucci Carvalho](https://github.com/artisan-toolbox)
- [All Contributors](../../contributors)

## License

Permit is open-sourced software licensed under the [MIT license](LICENSE.md).
