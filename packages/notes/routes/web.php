<?php

use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\NoteController;
use Coleus\Notes\Http\Controllers\PinController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix('notes')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::get('/{note}', [NoteController::class, 'show'])->name('show')->whereNumber('note');
        Route::put('/{note}', [NoteController::class, 'update'])->name('update')->whereNumber('note');
        Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy')->whereNumber('note');
        Route::post('/{note}/pin', [PinController::class, 'store'])->name('pin')->whereNumber('note');
        Route::delete('/{note}/pin', [PinController::class, 'destroy'])->name('unpin')->whereNumber('note');
    });
