<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$found = function (string $q): array {
    $ids = [];

    $this->get(route('notes.index', ['q' => $q]))->assertInertia(function (Assert $page) use (&$ids) {
        $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->sort()->values()->all();
    });

    return $ids;
};

it('finds notes containing the word anywhere in the body', function () use ($found) {
    $title = Note::factory()->create(['body' => '<h1>Trip to Lisbon</h1><p>flights</p>']);
    $body = Note::factory()->create(['body' => '<h1>Packing</h1><p>sunscreen for lisbon</p>']);
    Note::factory()->create(['body' => '<h1>Groceries</h1>']);

    expect($found->call($this, 'lisbon'))->toBe([$title->id, $body->id]);
});

it('matches the search as one phrase', function () use ($found) {
    $phrase = Note::factory()->create(['body' => '<p>quarterly planning meeting</p>']);
    Note::factory()->create(['body' => '<p>planning the quarterly report</p>']);

    expect($found->call($this, 'quarterly planning'))->toBe([$phrase->id]);
});

it('matches parts of words, ignoring case', function () use ($found) {
    $note = Note::factory()->create(['body' => '<p>Reading about Photosynthesis</p>']);

    expect($found->call($this, 'PHOTO'))->toBe([$note->id]);
});

it('never returns other users notes', function () use ($found) {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '<p>secret lisbon plans</p>']);
    $this->actingAs($this->user);

    expect($found->call($this, 'lisbon'))->toBe([]);
});

it('carries the search in the filters, also on the open note page', function () {
    $note = Note::factory()->create(['body' => '<h1>Lisbon</h1>']);
    Note::factory()->create(['body' => '<h1>Porto</h1>']);

    $this->get(route('notes.show', ['note' => $note, 'q' => 'lisbon']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.data', ['q' => 'lisbon', 'view' => 'notes'])
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $note->id));
});

it('rejects an overlong search', function () {
    $this->get(route('notes.index', ['q' => str_repeat('a', 201)]))->assertSessionHasErrors('q');
});
