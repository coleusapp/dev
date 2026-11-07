<?php

use Coleus\Frame\Http\Controllers\FrameController;
use Coleus\Frame\Http\Controllers\KioskController;
use Coleus\Frame\Http\Middleware\KioskHeaders;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('frame.')
    ->prefix(config('frame.route_prefix'))
    ->group(function () {
        Route::get('/', [FrameController::class, 'index'])->name('index');
        Route::get('/create', [FrameController::class, 'create'])->name('create');
        Route::post('/', [FrameController::class, 'store'])->name('store');
        Route::whereNumber('frame')->group(function () {
            Route::get('/{frame}/edit', [FrameController::class, 'edit'])->name('edit');
            Route::put('/{frame}', [FrameController::class, 'update'])->name('update');
            Route::delete('/{frame}', [FrameController::class, 'destroy'])->name('destroy');
            Route::post('/{frame}/token', [FrameController::class, 'regenerateToken'])->name('token.regenerate');
            Route::post('/{frame}/fetch', [FrameController::class, 'fetch'])->name('fetch');
        });
    });

/*
 * Kiosk: no session, cookies or CSRF (not in the web group); the token in
 * the URL is the only credential.
 */
Route::middleware([KioskHeaders::class, 'throttle:'.config('frame.kiosk_rate_limit')])
    ->name('frame.kiosk.')
    ->prefix(config('frame.route_prefix'))
    ->where(['token' => '[A-Za-z0-9]{48}', 'artwork' => '[0-9]+'])
    ->group(function () {
        Route::get('/{token}', [KioskController::class, 'show'])->name('show');
        Route::get('/{token}/next', [KioskController::class, 'next'])->name('next');
        Route::get('/{token}/image/{artwork}', [KioskController::class, 'image'])->name('image');
    });
