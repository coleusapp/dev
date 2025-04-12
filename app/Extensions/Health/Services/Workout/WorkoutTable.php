<?php

namespace App\Extensions\Health\Services\Workout;

use App\Extensions\Health\Models\Workout;
use App\Packages\Table\Table;

class WorkoutTable extends Table
{
    public static function query()
    {
        return Workout::query()
            ->orderBy('created_at', 'desc');
    }
}