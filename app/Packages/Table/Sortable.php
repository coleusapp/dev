<?php

namespace App\Packages\Table;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    public static string $sortQuery = 'sort';

    protected static function hasSortQuery(): bool
    {
        return request()->has(static::$sortQuery) && strlen(request(static::$sortQuery));
    }

    protected abstract static function sortQuery(Builder $query): Builder;
}
