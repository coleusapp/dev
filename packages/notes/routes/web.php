<?php

use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\NoteController;
use Coleus\Notes\Http\Controllers\PinController;
use Coleus\Notes\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix(config('notes.route_prefix'))
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::delete('/trash', [TrashController::class, 'empty'])->name('trash.empty');
        Route::whereNumber('note')->group(function () {
            Route::get('/{note}', [NoteController::class, 'show'])->name('show')->withTrashed();
            Route::put('/{note}', [NoteController::class, 'update'])->name('update');
            Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy');
            Route::post('/{note}/pin', [PinController::class, 'store'])->name('pin');
            Route::delete('/{note}/pin', [PinController::class, 'destroy'])->name('unpin');
            Route::post('/{note}/restore', [TrashController::class, 'restore'])->name('restore')->withTrashed();
            Route::delete('/{note}/force', [TrashController::class, 'destroy'])->name('force-delete')->withTrashed();
        });
    });
