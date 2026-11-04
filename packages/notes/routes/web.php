<?php

use Coleus\Notes\Http\Controllers\ArchiveController;
use Coleus\Notes\Http\Controllers\BookmarkController;
use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\NoteController;
use Coleus\Notes\Http\Controllers\PinController;
use Coleus\Notes\Http\Controllers\SettingsController;
use Coleus\Notes\Http\Controllers\SortController;
use Coleus\Notes\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix(config('notes.route_prefix'))
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::put('/sort', [SortController::class, 'update'])->name('sort.update');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::delete('/trash', [TrashController::class, 'empty'])->name('trash.empty');
        Route::whereNumber('note')->group(function () {
            Route::get('/{note}', [NoteController::class, 'show'])->name('show')->withTrashed();
            Route::put('/{note}', [NoteController::class, 'update'])->name('update');
            Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy');
            Route::post('/{note}/pin', [PinController::class, 'store'])->name('pin');
            Route::delete('/{note}/pin', [PinController::class, 'destroy'])->name('unpin');
            Route::post('/{note}/bookmark', [BookmarkController::class, 'store'])->name('bookmark');
            Route::delete('/{note}/bookmark', [BookmarkController::class, 'destroy'])->name('unbookmark');
            Route::post('/{note}/archive', [ArchiveController::class, 'store'])->name('archive');
            Route::delete('/{note}/archive', [ArchiveController::class, 'destroy'])->name('unarchive');
            Route::post('/{note}/restore', [TrashController::class, 'restore'])->name('restore')->withTrashed();
            Route::delete('/{note}/force', [TrashController::class, 'destroy'])->name('force-delete')->withTrashed();
        });
    });
