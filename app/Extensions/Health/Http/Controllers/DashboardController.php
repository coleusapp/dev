<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Resources\ExerciseResource;
use App\Extensions\Health\Http\Resources\MuscleGroupResource;
use App\Extensions\Health\Http\Resources\ToothpasteTypeResource;
use App\Extensions\Health\Http\Resources\WeightResource;
use App\Extensions\Health\Http\Resources\CategoryResource;
use App\Extensions\Health\Services\ExerciseTable;
use App\Extensions\Health\Services\ToothpasteTypeTable;
use App\Extensions\Health\Services\Weight\WeightTable;
use App\Extensions\Health\Services\Workout\MuscleGroup\MuscleGroupTable;
use App\Extensions\Health\Services\CategoryTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('@health/Dashboard', [
            'weights' => WeightResource::collection(WeightTable::query()->paginate()),
            'categories' => CategoryResource::collection(CategoryTable::query()->paginate()),
            'muscle_groups' => MuscleGroupResource::collection(MuscleGroupTable::query()->paginate()),
            'toothpaste_types' => ToothpasteTypeResource::collection(ToothpasteTypeTable::query()->paginate()),
            'exercises' => ExerciseResource::collection(ExerciseTable::query()->paginate()),
        ]);
    }
}