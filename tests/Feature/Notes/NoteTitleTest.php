<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('derives the title from the first line when a note is created', function () {
    $this->post(route('notes.store'), ['body' => "# Trip to Lisbon\n\nPack light"]);

    expect(Note::query()->sole()->title)->toBe('Trip to Lisbon');
});

it('updates the title when the first line changes', function () {
    $note = Note::factory()->create(['body' => "# Old\nbody"]);

    $this->put(route('notes.update', ['note' => $note]), ['body' => "# New\nbody"]);

    expect($note->refresh()->title)->toBe('New');
});

it('ignores a title sent by the client', function () {
    $this->post(route('notes.store'), ['body' => 'Real title', 'title' => 'Forged']);

    expect(Note::query()->sole()->title)->toBe('Real title');
});

it('sends the derived title to the page', function () {
    $note = Note::factory()->create(['body' => "## Reading list\n- Dune"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.title', 'Reading list')
            ->where('notes.data.0.title', 'Reading list'));
});

it('has an empty title for an empty note', function () {
    $this->post(route('notes.store'), ['body' => '']);

    expect(Note::query()->sole()->title)->toBe('');
});
