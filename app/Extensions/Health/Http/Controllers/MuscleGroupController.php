<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\WorkoutCategory\StoreRequest;
use App\Extensions\Health\Http\Requests\WorkoutCategory\UpdateRequest;
use App\Extensions\Health\Http\Resources\WorkoutCategoryResource;
use App\Extensions\Health\Models\WorkoutCategory;
use App\Extensions\Health\Services\Workout\MuscleGroup\MuscleGroupTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class MuscleGroupController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/workouts/muscleGroups/Index', [
            'collection' => WorkoutCategoryResource::collection(MuscleGroupTable::query()->paginate()),
        ]);
    }

    public function create()
    {
        return Inertia::render('@health/workouts/muscleGroups/Create');
    }

    public function store(StoreRequest $request)
    {
        $category = WorkoutCategory::create($request->validated());

        return to_route('health.muscleGroups.edit', ['resource' => $category]);
    }

    public function edit(WorkoutCategory $category)
    {
        return Inertia::render('@health/workouts/muscleGroups/Edit', [
            'resource' => WorkoutCategoryResource::make($category),
        ]);
    }

    public function update(UpdateRequest $request, WorkoutCategory $category)
    {
        $category->update($request->validated());

        return back();
    }

    public function destroy(WorkoutCategory $category)
    {
        $category->delete();

        return to_route('health.muscleGroups.index');
    }
}
