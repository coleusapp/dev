<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\WorkoutCategory\StoreRequest;
use App\Extensions\Health\Http\Requests\WorkoutCategory\UpdateRequest;
use App\Extensions\Health\Http\Resources\WorkoutCategoryResource;
use App\Extensions\Health\Models\WorkoutCategory;
use App\Extensions\Health\Services\WorkoutCategoryTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class WorkoutCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/workouts/categories/Index', [
            'collection' => WorkoutCategoryResource::collection(WorkoutCategoryTable::query()->paginate()),
        ]);
    }

    public function create()
    {
        return Inertia::render('@health/workouts/categories/Create');
    }

    public function store(StoreRequest $request)
    {
        $category = WorkoutCategory::create($request->validated());

        return to_route('health.categories.edit', ['resource' => $category]);
    }

    public function edit(WorkoutCategory $category)
    {
        return Inertia::render('@health/workouts/categories/Edit', [
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

        return to_route('health.categories.index');
    }
}
