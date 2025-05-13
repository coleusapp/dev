<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Enums\DistanceEnum;
use App\Extensions\Health\Enums\DurationEnum;
use App\Extensions\Health\Enums\WeightEnum;
use App\Extensions\Health\Http\Requests\Exercise\SaveRequest;
use App\Extensions\Health\Http\Resources\CategoryAsOptionResource;
use App\Extensions\Health\Http\Resources\ExerciseResource;
use App\Extensions\Health\Http\Resources\MuscleGroupAsOptionResource;
use App\Extensions\Health\Models\Category;
use App\Extensions\Health\Models\Exercise;
use App\Extensions\Health\Models\MuscleGroup;
use App\Extensions\Health\Services\ExerciseTable;
use App\Http\Controllers\Controller;
use App\Packages\Support\Resources\EnumResource;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/exercises/Index', [
            'collection' => ExerciseResource::collection(ExerciseTable::query()->paginate()),
        ]);
    }

    public function create()
    {
        return [
            // 'weight_units' => EnumResource::collection(WeightEnum::cases()),
            // 'distance_units' => EnumResource::collection(DistanceEnum::cases()),
            'duration_units' => EnumResource::collectionWithNull(DurationEnum::cases()),
            // 'muscle_groups' => MuscleGroupAsOptionResource::collection(MuscleGroup::get()),
            // 'categories' => CategoryAsOptionResource::collection(Category::get()),
        ];

        return Inertia::render('@health/exercises/Create', [
            'weight_units' => EnumResource::collection(WeightEnum::cases()),
            'distance_units' => EnumResource::collection(DistanceEnum::cases()),
            'duration_units' => EnumResource::collection(DurationEnum::cases())->additional(['label' => '-- select --', 'value' => null]),
            'muscle_groups' => MuscleGroupAsOptionResource::collection(MuscleGroup::get()),
            'categories' => CategoryAsOptionResource::collection(Category::get()),
        ]);
    }

    public function store(SaveRequest $request)
    {
        $exercise = Exercise::create($request->validated());

        return to_route('health.categories.edit', ['exercise' => $exercise]);
    }

    public function edit(Exercise $exercise)
    {
        return Inertia::render('@health/exercises/Edit', [
            'resource' => ExerciseResource::make($exercise),
            'weight_units' => EnumResource::collection(WeightEnum::cases()),
            'distance_units' => EnumResource::collection(DistanceEnum::cases()),
            'duration_units' => EnumResource::collection(DurationEnum::cases()),
            'muscle_groups' => MuscleGroupAsOptionResource::collection(MuscleGroup::get()),
            'categories' => CategoryAsOptionResource::collection(Category::get()),
        ]);
    }

    public function update(SaveRequest $request, Exercise $exercise)
    {
        $exercise->update($request->validated());

        return back();
    }

    public function destroy(Exercise $exercise)
    {
        $category->delete();

        return to_route('health.workouts.exercises.index');
    }
}
