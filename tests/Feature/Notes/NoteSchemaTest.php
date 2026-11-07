<?php

use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Schema;

it('keeps everything in one notes table', function () {
    $table = app(Note::class)->getTable();

    expect(Schema::getColumnListing($table))->toEqualCanonicalizing([
        'id', 'body', 'pinned_at', 'created_at', 'updated_at', 'deleted_at',
    ]);
});

it('indexes the columns used for sorting and filtering', function () {
    $indexed = collect(Schema::getIndexes(app(Note::class)->getTable()))->pluck('columns')->map(fn (array $columns) => implode(',', $columns));

    expect($indexed)->toContain('created_at', 'updated_at', 'pinned_at', 'deleted_at');
});

it('casts the pinned timestamp to a date', function () {
    $note = Note::factory()->pinned()->create();

    expect($note->refresh()->pinned_at)->toBeInstanceOf(DateTimeInterface::class);
});
