<?php

use Coleus\Music\Http\Controllers\AlbumController;
use Coleus\Music\Http\Controllers\ArtistController;
use Coleus\Music\Http\Controllers\DashboardController;
use Coleus\Music\Http\Controllers\GenreController;
use Coleus\Music\Http\Controllers\PlaylistController;
use Coleus\Music\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('music.')
    ->prefix('music')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('albums', AlbumController::class)->except('show');
        Route::resource('tracks', TrackController::class)->except('show');
        Route::resource('artists', ArtistController::class)->except('show');
        Route::resource('genres', GenreController::class)->except('show');
        Route::resource('playlists', PlaylistController::class)->except('show');
        Route::get('/stream/{file}', function ($file) {
            $path = Storage::path($file);
            $size = filesize($path);

            return response()->stream(function () use ($path) {
                $fh = fopen($path, 'rb');
                while (! feof($fh)) {
                    echo fread($fh, 8192);
                    flush();
                }
                fclose($fh);
            }, 200, [
                'Content-Type' => 'audio/mpeg',
                'Content-Length' => $size,
            ]);
        })->where('file', '.*');
    });
