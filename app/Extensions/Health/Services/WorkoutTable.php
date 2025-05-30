<?php

namespace App\Extensions\Health\Services;

use App\Extensions\Health\Models\Workout;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkoutTable extends Table
{
    public static function query(): Builder
    {
        return Workout::query()
            ->orderBy('created_at', 'desc');
    }
}
