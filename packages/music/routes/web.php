<?php

use Coleus\Music\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('music.')
    ->prefix('music')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/stream/{file}', function ($file) {
            $path = Storage::path($file);

            return response()->stream(function () use ($path) {
                $stream = fopen($path, 'rb');
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                "Content-Type" => "audio/mpeg",
                "Accept-Ranges" => "bytes",
            ]);
        })->where('file', '.*');
    });
