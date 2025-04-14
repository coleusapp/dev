<?php

namespace App\Packages\Table;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Packages\Table\Table
 */
trait Sortable
{
    public static string $sortQuery = 'sort';

    protected static function hasSortQuery(): bool
    {
        return request()->has(static::$sortQuery) && strlen(request(static::$sortQuery));
    }

    protected abstract static function sortQuery(Builder $query): Builder;

    protected static function sortableColumn($label, $value, $sortValue = null): array
    {
        $sortValue = $sortValue ?? $value;

        return [
            ...static::column($label, $sortValue),
            'sort' => [
                ['label' => 'Ascending', 'value' => "$sortValue"],
                ['label' => 'Descending', 'value' => "-$sortValue"],
            ],
        ];
    }
}
