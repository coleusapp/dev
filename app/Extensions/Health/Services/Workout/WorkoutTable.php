<?php

namespace App\Extensions\Health\Services\Workout;

use App\Extensions\Health\Http\Resources\WorkoutResource;
use App\Extensions\Health\Models\Workout;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkoutTable extends Table
{
    public static function columns(): array
    {
        return [
            static::sortableColumn('Date', 'date_string', 'date'),
        ];
    }

    public static function records(): AnonymousResourceCollection
    {
        return WorkoutResource::collection(static::query()->paginate());
    }

    public static function query(): Builder
    {
        return Workout::query()
            ->orderBy('created_at', 'desc');
    }
}
