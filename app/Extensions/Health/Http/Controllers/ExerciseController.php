<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\Exercise\StoreRequest;
use App\Extensions\Health\Http\Requests\Exercise\UpdateRequest;
use App\Extensions\Health\Http\Resources\ExerciseResource;
use App\Extensions\Health\Models\Exercise;
use App\Extensions\Health\Services\ExerciseTable;
use App\Http\Controllers\Controller;
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
        return Inertia::render('@health/exercises/Create');
    }

    public function store(StoreRequest $request)
    {
        $category = Exercise::create($request->validated());

        return to_route('health.categories.edit', ['resource' => $category]);
    }

    public function edit(Exercise $category)
    {
        return Inertia::render('@health/exercises/Edit', [
            'resource' => ExerciseResource::make($category),
        ]);
    }

    public function update(UpdateRequest $request, Exercise $category)
    {
        $category->update($request->validated());

        return back();
    }

    public function destroy(Exercise $category)
    {
        $category->delete();

        return to_route('health.categories.index');
    }
}
