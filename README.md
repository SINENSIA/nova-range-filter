README.md

# Laravel Nova Range Filter

A customizable range filter for Laravel Nova that supports masked input, number formatting (currency/decimal), and optional validation. Built with [IMask.js](https://imask.js.org/) and designed to work seamlessly within Nova's filters sidebar.

## Features

- Supports min/max range filters
- Dynamic formatting using `Intl.NumberFormat`
- Currency/decimal styles
- Optional negative values
- Built-in validation (`min` cannot be greater than `max`)
- Reacts to Nova's filter reset event

## Installation

```bash
composer require sinensia/nova-range-filter
```

Then publish the frontend assets:

```bash
php artisan nova:publish
```

## Usage

In your Nova resource:

```php
use Sinensia\NovaRangeFilter\RangeFilter;

public function filters(Request $request)
{
    return [
        (new RangeFilter('Price', 'price'))->withMeta([
            'unit' => 'EUR',
            'format' => 'currency', // or 'decimal'
            'locale' => 'es-ES',
            'allowNegative' => false,
        ]),
    ];
}
```

## Customization

You can customize the behavior using the `meta()` options:

| Option          | Type    | Description                           |
| --------------- | ------- | ------------------------------------- |
| `unit`          | string  | Currency code (e.g. `USD`, `EUR`)     |
| `format`        | string  | `'currency'` or `'decimal'`           |
| `locale`        | string  | Locale string (e.g. `en-US`, `es-ES`) |
| `allowNegative` | boolean | Allow negative numbers                |

## Development

To modify or extend this component:

```bash
cd nova-components/RangeFilter
npm install
npm run dev
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

With love, for the open source community
https://www.sinensia.com
