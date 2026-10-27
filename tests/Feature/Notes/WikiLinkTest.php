<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('links [[Title]] to the note with that title', function () {
    $target = Note::factory()->create(['body' => "# Trip to Lisbon\nflights"]);
    $note = Note::factory()->create(['body' => 'Plan [[trip to lisbon]] soon']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'href="/notes/'.$target->id.'"')
            && str_contains($html, 'data-note-id="'.$target->id.'"')));
});

it('offers to create a missing note', function () {
    $note = Note::factory()->create(['body' => 'See [[Someday]]']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'data-wiki-title="Someday"')));
});

it('creates the linked note with its title', function () {
    $this->post(route('notes.store'), ['body' => "# Someday\n"]);

    expect(Note::query()->sole()->title)->toBe('Someday');
});

it('does not link to trashed notes or other users notes', function () {
    Note::factory()->trashed()->create(['body' => '# Gone']);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);
    $note = Note::factory()->create(['body' => '[[Gone]] [[Theirs]]']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => ! str_contains($html, 'data-note-id')
            && substr_count($html, 'wiki-link-missing') === 2));
});

it('links to the most recently updated note when titles repeat', function () {
    Note::factory()->create(['body' => '# Ideas', 'updated_at' => now()->subDay()]);
    $newer = Note::factory()->create(['body' => '# Ideas']);
    $note = Note::factory()->create(['body' => '[[Ideas]]']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'data-note-id="'.$newer->id.'"')));
});

it('resolves links in the autosave response', function () {
    $target = Note::factory()->create(['body' => '# Target']);
    $note = Note::factory()->create(['body' => '']);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => '[[Target]]'])
        ->assertJsonPath('data.html', fn (string $html) => str_contains($html, 'data-note-id="'.$target->id.'"'));
});

it('never links a note to itself', function () {
    $note = Note::factory()->create(['body' => "[[Ideas]]\nmore"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => ! str_contains($html, 'data-note-id')));
});
