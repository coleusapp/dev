<?php

/*
 * InnoDB FULLTEXT indexes only see committed rows, so these tests can't run
 * inside RefreshDatabase's transaction (which tests/Feature always uses).
 * They live here and truncate instead.
 */

use Coleus\Notes\Models\Note;
use Coleus\Notes\Search\NoteSearch;
use Coleus\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

beforeEach(function () {
    if (! in_array(DB::getDriverName(), ['mariadb', 'mysql'], true)) {
        $this->markTestSkipped('FULLTEXT search needs MariaDB or MySQL.');
    }

    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$ids = fn (string $terms): array => Note::query()->search($terms)->orderBy('id')->pluck('id')->all();

it('uses the FULLTEXT index for regular words', function () {
    $sql = Note::query()->search('lisbon')->toSql();

    expect($sql)->toContain('match (`title`, `body`) against (? in boolean mode)');
});

it('matches words in the title or the body', function () use ($ids) {
    $title = Note::factory()->create(['body' => "# Trip to Lisbon\nflights"]);
    $body = Note::factory()->create(['body' => "# Packing\nsunscreen for lisbon"]);
    Note::factory()->create(['body' => '# Groceries']);

    expect($ids('lisbon'))->toBe([$title->id, $body->id]);
});

it('requires every word', function () use ($ids) {
    $both = Note::factory()->create(['body' => 'quarterly planning meeting']);
    Note::factory()->create(['body' => 'quarterly report']);

    expect($ids('planning quarterly'))->toBe([$both->id]);
});

it('matches word prefixes', function () use ($ids) {
    $note = Note::factory()->create(['body' => 'reading about photosynthesis']);

    expect($ids('photo'))->toBe([$note->id]);
});

it('is case-insensitive', function () use ($ids) {
    $note = Note::factory()->create(['body' => 'Barcelona']);

    expect($ids('BARCELONA'))->toBe([$note->id]);
});

it('falls back to LIKE for short words, stopwords and punctuation', function (string $body, string $terms) use ($ids) {
    $note = Note::factory()->create(['body' => $body]);
    Note::factory()->create(['body' => 'unrelated text']);

    expect($ids($terms))->toBe([$note->id]);
})->with([
    'two letters' => ['go to the gym', 'go'],
    'stopword' => ['what the heck', 'what'],
    'hyphenated' => ['my to-do list', 'to-do'],
    'email-like' => ['mail sam@example.test', 'sam@example'],
    'mixed with indexed word' => ['ai research notes', 'ai research'],
]);

it('treats LIKE wildcards literally', function () use ($ids) {
    $note = Note::factory()->create(['body' => '100% done']);
    Note::factory()->create(['body' => '100 items']);

    expect($ids('100%'))->toBe([$note->id]);
});

it('ignores boolean operators typed by the user', function () use ($ids) {
    $note = Note::factory()->create(['body' => 'garden ideas']);

    expect($ids('+garden -ideas'))->toBe([$note->id])
        ->and($ids('"garden"'))->toBe([$note->id]);
});

it('returns everything for an empty search', function () use ($ids) {
    Note::factory()->count(2)->create();

    expect($ids('  '))->toHaveCount(2);
});

it('never returns other users notes', function () use ($ids) {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => 'secret lisbon plans']);
    $this->actingAs($this->user);

    expect($ids('lisbon'))->toBe([]);
});

it('filters the notes page with ?q', function () {
    $match = Note::factory()->create(['body' => '# Lisbon']);
    Note::factory()->create(['body' => '# Porto']);

    $this->get(route('notes.index', ['q' => 'lisbon']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', 'lisbon')
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $match->id));
});

it('rejects an overlong search', function () {
    $this->get(route('notes.index', ['q' => str_repeat('a', 201)]))->assertSessionHasErrors('q');
});

it('splits search words and strips operators', function () {
    expect(NoteSearch::words('  +Foo  -bar "baz" foo (qux) '))->toBe(['foo', 'bar', 'baz', 'qux'])
        ->and(NoteSearch::words(implode(' ', range('a', 'z'))))->toHaveCount(NoteSearch::MAX_WORDS);
});
