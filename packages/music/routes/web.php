<?php

use Coleus\Music\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('music.')
    ->prefix('music')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
    });
