<?php

use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Schema;

it('has the columns later features rely on', function () {
    expect(Schema::hasColumns(app(Note::class)->getTable(), [
        'id', 'title', 'body', 'pinned_at', 'archived_at', 'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

it('indexes the columns used for sorting and filtering', function () {
    $indexed = collect(Schema::getIndexes(app(Note::class)->getTable()))->pluck('columns')->map(fn (array $columns) => implode(',', $columns));

    expect($indexed)->toContain('title', 'created_at', 'updated_at', 'pinned_at', 'archived_at', 'deleted_at');
});

it('adds a fulltext index on MariaDB and MySQL', function () {
    $fullText = collect(Schema::getIndexes(app(Note::class)->getTable()))->firstWhere('type', 'fulltext');

    expect($fullText['columns'] ?? null)->toBe(['title', 'body']);
})->skip(fn () => ! in_array(Schema::getConnection()->getDriverName(), ['mariadb', 'mysql'], true), 'Fulltext indexes need MariaDB or MySQL.');

it('casts lifecycle timestamps to dates', function () {
    $note = Note::factory()->create(['pinned_at' => now(), 'archived_at' => now()]);

    expect($note->refresh()->pinned_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($note->archived_at)->toBeInstanceOf(DateTimeInterface::class);
});

it('rolls the lifecycle migration back and forward cleanly', function () {
    $migration = require dirname(__DIR__, 3).'/packages/notes/database/migrations/2026_10_27_000002_add_lifecycle_columns_to_notes_table.php';
    $table = app(Note::class)->getTable();

    $migration->down();
    expect(Schema::hasColumns($table, ['title', 'pinned_at', 'archived_at', 'deleted_at']))->toBeFalse();

    $migration->up();
    expect(Schema::hasColumns($table, ['title', 'pinned_at', 'archived_at', 'deleted_at']))->toBeTrue();
});
