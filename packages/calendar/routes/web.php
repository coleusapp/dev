<?php

use Coleus\Calendar\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('calendar.')
    ->prefix('calendar')
    ->group(function () {
        Route::redirect('/', '/calendar/month')->name('dashboard');
        Route::get('/year', [CalendarController::class, 'year'])->name('year');
        Route::get('/month', [CalendarController::class, 'month'])->name('month');
        Route::get('/week', [CalendarController::class, 'week'])->name('week');
        Route::get('/day', [CalendarController::class, 'day'])->name('day');
    });
