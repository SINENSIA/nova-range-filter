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

## Basic Example

This is a simple usage of the `NovaRangeFilter` to filter Eloquent records by a numeric field (e.g., price). It applies a `whereBetween` clause to a single numeric column.

### Example implementation

```php
<?php

namespace App\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Sinensia\NovaRangeFilter\NovaRangeFilter;

class PriceRangeFilter extends NovaRangeFilter
{
    public function name()
    {
        return __('Price Range');
    }

    public $component = 'nova-range-filter';

    public function apply(NovaRequest $request, $query, $value)
    {
        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        if ($min !== null && $max !== null) {
            return $query->whereBetween('price', [$min, $max]);
        }

        if ($min !== null) {
            return $query->where('price', '>=', $min);
        }

        if ($max !== null) {
            return $query->where('price', '<=', $max);
        }

        return $query;
    }
}
```

## Relational Count Example

This example demonstrates how to use the `NovaRangeFilter` to filter resources based on the **count of related models**, specifically for a `morphMany` relationship like likes on a news post.

It uses `withCount('likes')` to enable filtering based on the total number of likes per resource.

### Example: Filtering News by Like Count

```php
<?php

namespace App\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Sinensia\NovaRangeFilter\NovaRangeFilter;

class NewsLikesRange extends NovaRangeFilter
{
    public function name()
    {
        return __('Likes Range');
    }

    public $component = 'nova-range-filter';

    public function apply(NovaRequest $request, $query, $value)
    {
        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        $query->withCount('likes');

        if ($min !== null && $max !== null) {
            return $query->having('likes_count', '>=', $min)
                         ->having('likes_count', '<=', $max);
        }

        if ($min !== null) {
            return $query->having('likes_count', '>=', $min);
        }

        if ($max !== null) {
            return $query->having('likes_count', '<=', $max);
        }

        return $query;
    }
}
```

This pattern is particularly useful for:

- Articles filtered by number of comments
- Products filtered by review count
- Any entity using `hasMany` or `morphMany` relationships where you want range-based filtering

## Advanced Example

This example demonstrates a real-world use case of the Nova Range Filter for **filtering real estate portfolios by price range with bidirectional overlap logic**. It's particularly useful when both the user's selected range and the stored values are themselves ranges (e.g., `price` is stored as `min;max`).

The filter only returns results if there is a significant overlap (e.g., 30%) between the user's selected range and the stored price range.

### Purpose

This filter is designed for applications where simple value comparisons aren't enough — such as real estate platforms or marketplaces where listings specify a range of prices, and the user's filter should intersect intelligently with those ranges.

### Key logic features:

- Overlap is calculated using `GREATEST` and `LEAST` in raw SQL
- A minimum overlap percentage is enforced in both directions
- Prevents filtering when either min or max is not defined

### Example implementation

```php
<?php

namespace App\Nova\Filters;

use Illuminate\Support\Facades\Log;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Sinensia\NovaRangeFilter\NovaRangeFilter;

class PorfoliosByRangePrice extends NovaRangeFilter
{
    public function name()
    {
        return __('Bidirectional overlap filtering price range');
    }

    public $component = 'nova-range-filter';

    public function apply(NovaRequest $request, $query, $value)
    {
        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        if ($min === null || $max === null) {
            return $query;
        }

        $userRange = $max - $min;
        $minSolapePct = 0.3; // 30% minimum overlap

        $query->whereRaw("
            (
                @interseccion := GREATEST(
                    0,
                    LEAST(CAST(SUBSTRING_INDEX(price, ';', -1) AS DECIMAL(10,2)), ?) -
                    GREATEST(CAST(SUBSTRING_INDEX(price, ';', 1) AS DECIMAL(10,2)), ?)
                )
            )
            AND
            @interseccion / (? - ?) >= ?
            AND
            @interseccion / (
                CAST(SUBSTRING_INDEX(price, ';', -1) AS DECIMAL(10,2)) -
                CAST(SUBSTRING_INDEX(price, ';', 1) AS DECIMAL(10,2))
            ) >= ?
        ", [
            $max,
            $min,
            $max,
            $min,
            $minSolapePct,
            $minSolapePct,
        ]);

        return $query;
    }

    public function value()
    {
        return request()->get('filters')[$this->key()] ?? null;
    }

    public function key()
    {
        return static::class;
    }
}
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

With love, for the open source community
https://www.sinensia.com
