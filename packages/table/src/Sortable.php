<?php

namespace Coleus\Table;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Coleus\Table\src\Table
 */
trait Sortable
{
    public static string $sortQuery = 'sort';

    protected static function hasSortQuery(): bool
    {
        return request()->has(static::$sortQuery) && strlen(request(static::$sortQuery));
    }

    protected static function sortQuery(Builder $query): Builder
    {
        return match (request(static::$sortQuery)) {
            default => $query->orderBy('created_at', 'desc'),
        };
    }

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
