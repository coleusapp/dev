<?php

use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Schema;

it('keeps everything in one notes table', function () {
    $table = app(Note::class)->getTable();

    expect(Schema::getColumnListing($table))->toEqualCanonicalizing([
        'id', 'body', 'pinned_at', 'archived_at', 'bookmarked_at', 'created_at', 'updated_at', 'deleted_at',
    ]);
});

it('indexes the columns used for sorting and filtering', function () {
    $indexed = collect(Schema::getIndexes(app(Note::class)->getTable()))->pluck('columns')->map(fn (array $columns) => implode(',', $columns));

    expect($indexed)->toContain('created_at', 'updated_at', 'pinned_at', 'archived_at', 'bookmarked_at', 'deleted_at');
});

it('adds a fulltext index on the body on MariaDB and MySQL', function () {
    $fullText = collect(Schema::getIndexes(app(Note::class)->getTable()))->firstWhere('type', 'fulltext');

    expect($fullText['columns'] ?? null)->toBe(['body']);
})->skip(fn () => ! in_array(Schema::getConnection()->getDriverName(), ['mariadb', 'mysql'], true), 'Fulltext indexes need MariaDB or MySQL.');

it('casts the filing timestamps to dates', function () {
    $note = Note::factory()->pinned()->archived()->bookmarked()->create();

    expect($note->refresh()->pinned_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($note->archived_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($note->bookmarked_at)->toBeInstanceOf(DateTimeInterface::class);
});
