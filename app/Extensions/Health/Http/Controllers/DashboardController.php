<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Resources\MuscleGroupResource;
use App\Extensions\Health\Http\Resources\WeightResource;
use App\Extensions\Health\Http\Resources\WorkoutCategoryResource;
use App\Extensions\Health\Services\Weight\WeightTable;
use App\Extensions\Health\Services\Workout\MuscleGroup\MuscleGroupTable;
use App\Extensions\Health\Services\WorkoutCategoryTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('@health/Dashboard', [
            'weights' => WeightResource::collection(WeightTable::query()->paginate()),
            'workout_categories' => WorkoutCategoryResource::collection(WorkoutCategoryTable::query()->paginate()),
            'muscle_groups' => MuscleGroupResource::collection(MuscleGroupTable::query()->paginate()),
        ]);
    }
}