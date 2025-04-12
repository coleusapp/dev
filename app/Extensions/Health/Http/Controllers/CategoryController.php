<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\Categories\StoreRequest;
use App\Extensions\Health\Http\Requests\Categories\UpdateRequest;
use App\Extensions\Health\Http\Resources\CategoryResource;
use App\Extensions\Health\Models\Category;
use App\Extensions\Health\Services\Category\CategoryTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/workouts/categories/Index', [
            'table' => [
                'records' => CategoryResource::collection(CategoryTable::query()->paginate()),
                'columns' => ['name'],
                'headers' => ['Name'],
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('@health/workouts/categories/Create');
    }

    public function store(StoreRequest $request)
    {
        $category = Category::create($request->validated());

        return to_route('health.categories.edit', ['category' => $category]);
    }

    public function edit(Category $category)
    {
        return Inertia::render('@health/workouts/categories/Edit', [
            'category' => new CategoryResource($category),
        ]);
    }

    public function update(UpdateRequest $request, Category $category)
    {
        $category->update($request->validated());

        return back();
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return to_route('health.categories.index');
    }
}
