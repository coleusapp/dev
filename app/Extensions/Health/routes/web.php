<?php

use App\Extensions\Health\Http\Controllers\DashboardController;
use App\Extensions\Health\Http\Controllers\MuscleGroupController;
use App\Extensions\Health\Http\Controllers\WeightController;
use App\Extensions\Health\Http\Controllers\WorkoutCategoryController;
use App\Extensions\Health\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::name('health.')->prefix('health')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('weights', WeightController::class);
    Route::name('workouts.')->prefix('workouts')->group(function () {
        Route::resource('/', WorkoutController::class)->except('show');
        Route::resource('categories', WorkoutCategoryController::class)->except('show');
        Route::resource('muscle-groups', MuscleGroupController::class)->except('show');
    });
})->middleware(['auth', 'verified']);
