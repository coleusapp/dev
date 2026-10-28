<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('sends parsed properties and keeps front matter out of the title and preview', function () {
    $note = Note::factory()->create(['body' => "---\nstatus: draft\nrating: 3\n---\n# Plan\n\nText"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.properties', ['status' => 'draft', 'rating' => 3])
            ->where('note.data.title', 'Plan')
            ->where('note.data.snippet', 'Text')
            ->where('note.data.html', "<h1>Plan</h1>\n<p>Text</p>\n"));
});

it('sends null properties for front matter it cannot read, and an empty list without any', function () {
    $nested = Note::factory()->create(['body' => "---\nmap:\n  a: 1\n---\nx"]);
    $plain = Note::factory()->create(['body' => 'x']);

    $this->get(route('notes.show', ['note' => $nested]))->assertInertia(fn (Assert $page) => $page->where('note.data.properties', null));
    $this->get(route('notes.show', ['note' => $plain]))->assertInertia(fn (Assert $page) => $page->where('note.data.properties', []));
});

it('merges the tags property with hashtags', function () {
    $this->post(route('notes.store'), ['body' => "---\ntags: [Travel, '#work/meetings', 123]\n---\n# Trip #lisbon"]);

    expect(Note::query()->sole()->tags()->orderBy('name')->pluck('name')->all())->toBe(['lisbon', 'travel', 'work/meetings']);
});

it('reads a tags property written as one string', function () {
    $note = Note::factory()->create(['body' => "---\ntags: a, b c\n---\nx"]);

    expect($note->tags()->orderBy('name')->pluck('name')->all())->toBe(['a', 'b', 'c']);
});

it('links and backlinks through aliases', function () {
    $target = Note::factory()->create(['body' => "---\naliases: [LIS, Lisboa]\n---\n# Lisbon"]);
    $linking = Note::factory()->create(['body' => "# Trip\n\nSee [[lisboa]]"]);

    expect($target->aliases()->pluck('alias')->all())->toBe(['LIS', 'Lisboa']);

    $this->get(route('notes.show', ['note' => $linking]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.html', fn (string $html) => str_contains($html, 'data-note-id="'.$target->id.'"')));

    $this->get(route('notes.show', ['note' => $target]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.backlinks.0.id', $linking->id));

    $this->getJson(route('notes.links.resolve', ['title' => 'LIS']))->assertJsonPath('data.id', $target->id);
});

it('prefers a note titled that way over one that only has it as an alias', function () {
    Note::factory()->create(['body' => "---\naliases: Ideas\n---\n# Brainstorm"]);
    $titled = Note::factory()->create(['body' => '# Ideas', 'updated_at' => now()->subYear()]);

    $this->getJson(route('notes.links.resolve', ['title' => 'ideas']))->assertJsonPath('data.id', $titled->id);
});

it('updates aliases when the property changes and drops them with the note', function () {
    $note = Note::factory()->create(['body' => "---\naliases: [Old]\n---\nx"]);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => "---\naliases: [New, '[[bad]]']\n---\nx"]);
    expect($note->aliases()->pluck('alias')->all())->toBe(['New']);

    $note->forceDelete();
    expect(Coleus\Notes\Models\NoteAlias::query()->count())->toBe(0);
});
