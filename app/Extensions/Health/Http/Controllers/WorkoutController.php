<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Resources\CategoryResource;
use App\Extensions\Health\Http\Resources\WorkoutResource;
use App\Extensions\Health\Services\Category\CategoryTable;
use App\Extensions\Health\Services\Workout\WorkoutTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class WorkoutController extends Controller
{
    public function index()
    {
        return Inertia::render('@health/workouts/Index', [
            'table' => WorkoutTable::resource(),
            // 'category_table' => [
            //     'records' => CategoryResource::collection(CategoryTable::query()->paginate()),
            //     'columns' => ['name'],
            //     'headers' => ['Name'],
            // ]
        ]);
    }
}
