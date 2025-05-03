<?php

namespace App\Packages\Table;

use App\Packages\Table\Contracts\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

abstract class Table implements Columns
{
    use Sortable;
    use Searchable;

    public abstract static function query(): Builder;

    public abstract static function columns(): array;

    public static function column($label, $value): array
    {
        return [
            'label' => $label,
            'value' => $value,
        ];
    }

    public abstract static function records(): AnonymousResourceCollection;

    public static function config()
    {
        return [
            ...(property_exists(static::class, 'searchQuery') ? ['search_query' => static::$searchQuery] : []),
            ...(property_exists(static::class, 'sortQuery') ? ['search_query' => static::$searchQuery] : []),
        ];
    }

    public static function resource(): array
    {
        return [
            'records' => static::records(),
            // 'columns' => static::columns(),
            // 'config' => static::config(),
        ];
    }
}
