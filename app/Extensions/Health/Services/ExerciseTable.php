<?php

namespace App\Extensions\Health\Services;

use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Extensions\Health\Models\Exercise;

class ExerciseTable extends Table
{
    public static function query(): Builder
    {
        return Exercise::query()
            ->orderBy('created_at', 'desc');
    }
}