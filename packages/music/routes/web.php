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
            $size = filesize($path);

            return response()->stream(function () use ($path) {
                $fh = fopen($path, 'rb');
                while (!feof($fh)) {
                    echo fread($fh, 8192);
                    flush();
                }
                fclose($fh);
            }, 200, [
                "Content-Type" => "audio/mpeg",
                "Content-Length" => $size
            ]);
        })->where('file', '.*');
    });
