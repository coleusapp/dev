<?php

namespace App\Extensions\Health\Services\Workout\MuscleGroup;

use App\Extensions\Health\Models\MuscleGroup;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class MuscleGroupTable extends Table
{
    public static function query(): Builder
    {
        return MuscleGroup::query()
            ->orderBy('created_at', 'desc');
    }
}