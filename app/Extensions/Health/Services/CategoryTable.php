<?php

namespace App\Extensions\Health\Services;

use App\Extensions\Health\Models\Category;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryTable extends Table
{
    public static function query(): Builder
    {
        return Category::query()
            ->orderBy('created_at', 'desc');
    }
}