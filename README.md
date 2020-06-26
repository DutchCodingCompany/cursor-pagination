# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dutchcodingcompany/cursor-pagination.svg?style=flat-square)](https://packagist.org/packages/dutchcodingcompany/cursor-pagination)
[![Build Status](https://img.shields.io/travis/dutchcodingcompany/cursor-pagination/master.svg?style=flat-square)](https://travis-ci.org/dutchcodingcompany/cursor-pagination)
[![Quality Score](https://img.shields.io/scrutinizer/g/dutchcodingcompany/cursor-pagination.svg?style=flat-square)](https://scrutinizer-ci.com/g/dutchcodingcompany/cursor-pagination)
[![Total Downloads](https://img.shields.io/packagist/dt/dutchcodingcompany/cursor-pagination.svg?style=flat-square)](https://packagist.org/packages/dutchcodingcompany/cursor-pagination)

This Laravel package brings cursor based pagination support to Laravel and [Lighthouse](https://github.com/nuwave/lighthouse) in particular.

## Installation

You can install the package via composer:

```bash
composer require dutchcodingcompany/cursor-pagination
```

## Usage

### GraphQL
Add `'DutchCodingCompany\\CursorPagination\\Directives'` to config/lighthouse.php under  
```
namespaces => [
   'directives' => ['DutchCodingCompany\\CursorPagination\\Directives']
]
```
### Builder
Call `cursorPaginate()` on an Eloquent or Query Builder
``` php
User::orderBy('updated_at', 'asc')->cursorPaginate($limit);
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email m@rcoboe.rs instead of using the issue tracker.

## Credits

- [Marco Boers](https://github.com/dutchcodingcompany)
- [Dutch Coding Company](https://github.com/DutchCodingCompany)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
