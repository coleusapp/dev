<?php

use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\NoteController;
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
    });
