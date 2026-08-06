<?php

use Coleus\Notes\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix('notes')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
