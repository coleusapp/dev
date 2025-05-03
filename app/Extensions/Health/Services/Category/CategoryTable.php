<?php

namespace App\Extensions\Health\Services\Category;

use App\Extensions\Health\Models\Category;
use App\Packages\Table\Table;

class CategoryTable extends Table
{
    public static function query()
    {
        return Category::query()
            ->orderBy('created_at', 'desc');
    }

    public static function columns(): array
    {
        return [];
    }
}