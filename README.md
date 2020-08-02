# thenping.me - hands-free scheduled task monitoring

![](./.github/logo.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/thenpingme/laravel.svg?style=flat-square)](https://packagist.org/packages/thenpingme/laravel)
[![Build Status](https://img.shields.io/travis/thenpingme/laravel/master.svg?style=flat-square)](https://travis-ci.org/thenpingme/laravel)
[![Quality Score](https://img.shields.io/scrutinizer/g/thenpingme/laravel.svg?style=flat-square)](https://scrutinizer-ci.com/g/thenpingme/laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/thenpingme/laravel.svg?style=flat-square)](https://packagist.org/packages/thenpingme/laravel)

thenping.me is a hands-free scheduled task monitoring application for your Laravel projects.

You need to have a [thenping.me](https://thenping.me) account in order to make use of the monitoring aspect, however, you are free to use the list command to identify your application's scheduled tasks.

* If using Laravel >=6.0.0 < 6.0.4, use version 1.0.0
* If using Laravel >=6.0.4 < 6.4.1, use version 1.1.0
* If using Laravel >=6.4.1 < 7.0.0, use version 1.2.0
* Version 1.3.0 is the first public-release of this companion package.

In order to avoid collisions between monitored scheduled tasks when using scheduled closures, you must ensure that each has a unique `description()` set.

## Installation

You can install the package via composer:

```bash
composer require thenpingme/laravel
```

## Usage
Once you have created a new project within [thenping.me](https://thenping.me), you will need to run the installation command.

``` php
php artisan thenpingme:setup <project-id>
```

This will automatically compile your scheduled tasks, check they are valid and unique, and sync them with thenping.me, in order to be able to monitor them.

Each time you deploy your application, you should include the `thenpingme:sync` command as part of the deployment strategy, in order to ensure any new tasks that were added in the latest release are monitored.

```
php artisan thenpingme:sync
```

**Note:** Any tasks that are changed as part of a sync operation will replace their monitored counterpart, as it is not possible to track the configuration of a scheduled task between releases.

You will be notified of any changes to your monitored tasks via email notification.

If you would like to check on your application's configured tasks, you may run the `thenpingme:schedule` command.

To ensure that your tasks can be uniquely identified by thenping.me, use the `thenpingme:verify` command.

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email support@thenping.me instead of using the issue tracker.

## Credits

- [Michael Dyrynda](https://github.com/michaeldyrynda)
- [Jake Bennett](https://github.com/JacobBennett)
- [All Contributors](../../contributors)

## License

The MIT. Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
