<?php

/*
 * The same search scope on SQLite, which has no FULLTEXT: every word goes
 * through the LIKE fallback.
 */

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.connections.notes_sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        'database.default' => 'notes_sqlite',
    ]);
    $this->artisan('migrate', ['--database' => 'notes_sqlite'])->assertSuccessful();

    $this->actingAs(User::factory()->create());
});

$ids = fn (string $terms): array => Note::query()->search($terms)->orderBy('id')->pluck('id')->all();

it('searches with LIKE on SQLite', function () use ($ids) {
    $lisbon = Note::factory()->create(['body' => "# Trip to Lisbon\nflights"]);
    $both = Note::factory()->create(['body' => 'Lisbon planning']);
    Note::factory()->create(['body' => 'Porto']);

    expect(Note::query()->search('lisbon')->toSql())->not->toContain('match')
        ->and($ids('lisbon'))->toBe([$lisbon->id, $both->id])
        ->and($ids('LISBON planning'))->toBe([$both->id])
        ->and($ids('lis'))->toBe([$lisbon->id, $both->id]);
});

it('treats LIKE wildcards literally on SQLite', function () use ($ids) {
    $note = Note::factory()->create(['body' => 'a_b']);
    Note::factory()->create(['body' => 'axb']);

    expect($ids('a_b'))->toBe([$note->id]);
});
