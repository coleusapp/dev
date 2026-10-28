<?php

use Coleus\Notes\Http\Controllers\ArchiveController;
use Coleus\Notes\Http\Controllers\BookmarkController;
use Coleus\Notes\Http\Controllers\ChecklistController;
use Coleus\Notes\Http\Controllers\FolderController;
use Coleus\Notes\Http\Controllers\IndexController;
use Coleus\Notes\Http\Controllers\MentionController;
use Coleus\Notes\Http\Controllers\NoteController;
use Coleus\Notes\Http\Controllers\PinController;
use Coleus\Notes\Http\Controllers\PropertyController;
use Coleus\Notes\Http\Controllers\RelinkController;
use Coleus\Notes\Http\Controllers\SettingsController;
use Coleus\Notes\Http\Controllers\SmartFolderController;
use Coleus\Notes\Http\Controllers\SortController;
use Coleus\Notes\Http\Controllers\SwitcherController;
use Coleus\Notes\Http\Controllers\TrashController;
use Coleus\Notes\Http\Controllers\VersionController;
use Coleus\Notes\Http\Controllers\WikiLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->name('notes.')
    ->prefix('notes')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
        Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update')->whereNumber('folder');
        Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy')->whereNumber('folder');
        Route::put('/{note}/folder', [FolderController::class, 'moveNote'])->name('folder.move')->whereNumber('note');
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::put('/properties', [PropertyController::class, 'rename'])->name('properties.rename');
        Route::put('/sort', [SortController::class, 'update'])->name('sort.update');
        Route::post('/smart-folders', [SmartFolderController::class, 'store'])->name('smart-folders.store');
        Route::put('/smart-folders/{smartFolder}', [SmartFolderController::class, 'update'])->name('smart-folders.update')->whereNumber('smartFolder');
        Route::delete('/smart-folders/{smartFolder}', [SmartFolderController::class, 'destroy'])->name('smart-folders.destroy')->whereNumber('smartFolder');
        Route::post('/searches', [BookmarkController::class, 'storeSearch'])->name('searches.store');
        Route::put('/bookmarks/{bookmark}', [BookmarkController::class, 'update'])->name('bookmarks.update')->whereNumber('bookmark');
        Route::post('/bookmarks/{bookmark}/move', [BookmarkController::class, 'move'])->name('bookmarks.move')->whereNumber('bookmark');
        Route::delete('/bookmarks/{bookmark}', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy')->whereNumber('bookmark');
        Route::post('/{note}/bookmark', [BookmarkController::class, 'store'])->name('bookmarks.store')->whereNumber('note');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::get('/links/resolve', [WikiLinkController::class, 'resolve'])->name('links.resolve');
        Route::get('/switcher', SwitcherController::class)->name('switcher');
        Route::get('/{note}/headings', [WikiLinkController::class, 'headings'])->name('headings')->whereNumber('note');
        Route::get('/{note}/relink', [RelinkController::class, 'show'])->name('relink.show')->whereNumber('note');
        Route::post('/{note}/relink', [RelinkController::class, 'store'])->name('relink.store')->whereNumber('note');
        Route::get('/{note}/mentions', [MentionController::class, 'index'])->name('mentions.index')->whereNumber('note');
        Route::post('/{note}/mentions/{source}', [MentionController::class, 'store'])->name('mentions.store')->whereNumber(['note', 'source']);
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/{note}', [NoteController::class, 'show'])->name('show')->whereNumber('note')->withTrashed();
        Route::put('/{note}', [NoteController::class, 'update'])->name('update')->whereNumber('note');
        Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy')->whereNumber('note');
        Route::post('/{note}/duplicate', [NoteController::class, 'duplicate'])->name('duplicate')->whereNumber('note');
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
        Route::put('/{note}/checklist/move', [ChecklistController::class, 'move'])->name('checklist.move')->whereNumber('note');
        Route::put('/{note}/checklist/{line}', [ChecklistController::class, 'update'])->name('checklist.update')->whereNumber(['note', 'line']);
    });
