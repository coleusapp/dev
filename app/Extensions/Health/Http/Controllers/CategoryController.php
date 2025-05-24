<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\Category\StoreRequest;
use App\Extensions\Health\Http\Requests\Category\UpdateRequest;
use App\Extensions\Health\Http\Resources\CategoryResource;
use App\Extensions\Health\Models\Category;
use App\Extensions\Health\Services\CategoryTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/workouts/categories/Index', [
            'collection' => CategoryResource::collection(CategoryTable::query()->paginate()),
        ]);
    }

    public function create()
    {
        return Inertia::render('@health/workouts/categories/Create');
    }

    public function store(StoreRequest $request)
    {
        $category = Category::create($request->validated());

        return to_route('health.categories.edit', ['resource' => $category]);
    }

    public function edit(Category $category)
    {
        return Inertia::render('@health/workouts/categories/Edit', [
            'resource' => CategoryResource::make($category),
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

        return back();
    }
}
