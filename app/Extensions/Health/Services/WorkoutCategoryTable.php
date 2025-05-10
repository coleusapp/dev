<?php

namespace App\Extensions\Health\Services;

use App\Extensions\Health\Models\WorkoutCategory;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkoutCategoryTable extends Table
{
    public static function query(): Builder
    {
        return WorkoutCategory::query()
            ->orderBy('created_at', 'desc');
    }
}