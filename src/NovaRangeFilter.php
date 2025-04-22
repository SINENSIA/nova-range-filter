<?php

namespace Sinensia\NovaRangeFilter;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class NovaRangeFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'nova-range-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        return $query;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'currentValue' => $this->value ?? null,
        ]);
    }

    public function default()
    {
        return [
            'min' => null,
            'max' => null,
        ];
    }
}
