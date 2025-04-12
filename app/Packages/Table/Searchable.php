<?php

namespace App\Packages\Table;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public static string $searchQuery = 'search';

    protected static function hasSearchQuery(): bool
    {
        return request()->has(static::$searchQuery) && strlen(request(static::$searchQuery));
    }

    protected abstract static function searchQuery(Builder $query): Builder;
}
