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

it('shows the alias text of [[Title|shown text]] and links to the title', function () {
    $target = Note::factory()->create(['body' => '# Trip to Lisbon']);
    $note = Note::factory()->create(['body' => 'Plan [[Trip to Lisbon|the trip]] and [[Someday | later ]] and [[Trip to Lisbon|]]']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'data-note-id="'.$target->id.'">the trip</a>')
            && str_contains($html, 'data-wiki-title="Someday" title="Create &quot;Someday&quot;">later</a>')
            && str_contains($html, 'data-note-id="'.$target->id.'">Trip to Lisbon</a>')));
});

it('counts an aliased link as a backlink and reads it as its alias in the list', function () {
    $target = Note::factory()->create(['body' => '# Trip to Lisbon']);
    $source = Note::factory()->create(['body' => "# Plans\nsee [[Trip to Lisbon|the trip]] soon"]);

    $this->get(route('notes.show', ['note' => $target]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.backlinks.0.id', $source->id)
            ->where('notes.data', fn ($notes) => collect($notes)->firstWhere('id', $source->id)['snippet'] === 'see the trip soon'));
});

it('links to a heading in another note or in the same note', function () {
    $target = Note::factory()->create(['body' => "# Trip to Lisbon\n## Day one"]);
    $note = Note::factory()->create(['body' => "# Plan\nsee [[Trip to Lisbon#Day  one]], [[Trip to Lisbon#Day one|day 1]], [[#Packing]], [[Nowhere#Top]] and [[#]]\n## Packing"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'href="/notes/'.$target->id.'?heading=Day%20one" class="wiki-link" data-note-id="'.$target->id.'" data-heading="Day one">Trip to Lisbon &gt; Day one</a>')
            && str_contains($html, 'data-heading="Day one">day 1</a>')
            && str_contains($html, '<a href="#" class="wiki-link" data-heading="Packing">Packing</a>')
            && str_contains($html, 'data-wiki-title="Nowhere" title="Create &quot;Nowhere&quot;">Nowhere &gt; Top</a>')
            && str_contains($html, '[[#]]'))
            ->where('notes.data', fn ($notes) => str_contains(collect($notes)->firstWhere('id', $note->id)['snippet'], 'see Trip to Lisbon > Day one, day 1, Packing')));
});

it('counts a heading link as a backlink, but not a same-note heading link', function () {
    $target = Note::factory()->create(['body' => "# Trip\n## Food"]);
    $source = Note::factory()->create(['body' => "# Plans\n[[Trip#Food]]"]);

    $this->get(route('notes.show', ['note' => $target]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.backlinks', fn ($backlinks) => collect($backlinks)->pluck('id')->all() === [$source->id]));
});
