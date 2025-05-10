<?php

namespace App\Packages\Table;

use App\Packages\Table\Contracts\Columns;
use Illuminate\Database\Eloquent\Builder;

abstract class Table implements Columns
{
    use Sortable;
    use Searchable;

    public abstract static function query(): Builder;

    public static function config()
    {
        return [
            ...(property_exists(static::class, 'searchQuery') ? ['search_query' => static::$searchQuery] : []),
            ...(property_exists(static::class, 'sortQuery') ? ['search_query' => static::$searchQuery] : []),
        ];
    }
}
