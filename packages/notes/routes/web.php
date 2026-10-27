<?php

use Coleus\Notes\Http\Controllers\ArchiveController;
use Coleus\Notes\Http\Controllers\ChecklistController;
use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\NoteController;
use Coleus\Notes\Http\Controllers\PinController;
use Coleus\Notes\Http\Controllers\TrashController;
use Coleus\Notes\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix('notes')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::get('/{note}', [NoteController::class, 'show'])->name('show')->whereNumber('note')->withTrashed();
        Route::put('/{note}', [NoteController::class, 'update'])->name('update')->whereNumber('note');
        Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy')->whereNumber('note');
        Route::post('/{note}/pin', [PinController::class, 'store'])->name('pin')->whereNumber('note');
        Route::delete('/{note}/pin', [PinController::class, 'destroy'])->name('unpin')->whereNumber('note');
        Route::post('/{note}/archive', [ArchiveController::class, 'store'])->name('archive')->whereNumber('note');
        Route::delete('/{note}/archive', [ArchiveController::class, 'destroy'])->name('unarchive')->whereNumber('note');
        Route::delete('/trash', [TrashController::class, 'empty'])->name('trash.empty');
        Route::post('/{note}/restore', [TrashController::class, 'restore'])->name('restore')->whereNumber('note')->withTrashed();
        Route::delete('/{note}/force', [TrashController::class, 'destroy'])->name('force-delete')->whereNumber('note')->withTrashed();
        Route::scopeBindings()->whereNumber(['note', 'version'])->group(function () {
            Route::get('/{note}/versions', [VersionController::class, 'index'])->name('versions.index')->withTrashed();
            Route::get('/{note}/versions/{version}', [VersionController::class, 'show'])->name('versions.show')->withTrashed();
            Route::put('/{note}/versions/{version}', [VersionController::class, 'update'])->name('versions.update');
            Route::post('/{note}/versions/{version}/restore', [VersionController::class, 'restore'])->name('versions.restore');
        });
        Route::put('/{note}/checklist/{line}', [ChecklistController::class, 'update'])->name('checklist.update')->whereNumber(['note', 'line']);
    });
