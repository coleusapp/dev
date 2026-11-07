<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('derives the title from the first line when a note is created', function () {
    $this->post(route('notes.store'), ['body' => '<h1>Trip to Lisbon</h1><p>Pack light</p>']);

    expect(Note::query()->sole()->title)->toBe('Trip to Lisbon');
});

it('updates the title when the first line changes', function () {
    $note = Note::factory()->create(['body' => '<h1>Old</h1><p>body</p>']);

    $this->put(route('notes.update', ['note' => $note]), ['body' => '<h1>New</h1><p>body</p>']);

    expect($note->refresh()->title)->toBe('New');
});

it('ignores a title sent by the client', function () {
    $this->post(route('notes.store'), ['body' => '<p>Real title</p>', 'title' => 'Forged']);

    expect(Note::query()->sole()->title)->toBe('Real title');
});

it('sends the derived title to the page', function () {
    $note = Note::factory()->create(['body' => '<h2>Reading list</h2><ul><li><p>Dune</p></li></ul>']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.title', 'Reading list')
            ->where('notes.data.0.title', 'Reading list'));
});

it('has an empty title for an empty note', function () {
    $this->post(route('notes.store'), ['body' => '']);

    expect(Note::query()->sole()->title)->toBe('');
});
