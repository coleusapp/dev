<?php

use App\Extensions\Health\Http\Controllers\CategoryController;
use App\Extensions\Health\Http\Controllers\DashboardController;
use App\Extensions\Health\Http\Controllers\WeightController;
use App\Extensions\Health\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::name('health.')->prefix('health')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('weights', WeightController::class);
    Route::resource('workouts', WorkoutController::class)->except('show');
    Route::resource('workouts/categories', CategoryController::class)->except('show');
})->middleware(['auth', 'verified']);
