<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('resolves a title to the user note', function () {
    $target = Note::factory()->create(['body' => '# Trip to Lisbon']);

    $this->getJson(route('notes.links.resolve', ['title' => 'trip to lisbon']))
        ->assertOk()
        ->assertJsonPath('data.id', $target->id);
});

it('returns null for a missing note, a trashed note or another user note', function (string $title) {
    Note::factory()->trashed()->create(['body' => '# Gone']);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.links.resolve', ['title' => $title]))->assertJsonPath('data.id', null);
})->with(['Nope', 'Gone', 'Theirs']);

it('does not resolve to the linking note itself', function () {
    $note = Note::factory()->create(['body' => '[[Ideas]]']);

    $this->getJson(route('notes.links.resolve', ['title' => 'Ideas', 'from' => $note->id]))->assertJsonPath('data.id', null);
});

it('validates the title', function (array $query) {
    $this->getJson(route('notes.links.resolve', $query))->assertJsonValidationErrors('title');
})->with([
    'missing' => [[]],
    'brackets' => [['title' => 'a]]b']],
    'too long' => [['title' => str_repeat('a', 256)]],
]);

it('lists a note headings as rendered text, without repeats', function () {
    $note = Note::factory()->create(['body' => "---\ntitle: x\n---\n# Trip\n\n## **Day** one\n\n```\n# not a heading\n```\n\n## Day one\n### [[Food|Eating]] ==out=="]);

    $this->getJson(route('notes.headings', $note))
        ->assertOk()
        ->assertExactJson(['data' => ['Trip', 'Day one', 'Eating out']]);
});

it('does not list headings of another users note', function () {
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create(['body' => '# Secret']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.headings', $theirs))->assertNotFound();
});

it('previews the note a link leads to, rendered', function () {
    $target = Note::factory()->create(['body' => "---\naliases: [Lisbon]\n---\n# Trip to Lisbon\n**Day** one, see [[Missing]]"]);

    $this->getJson(route('notes.links.preview', ['title' => 'lisbon']))
        ->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.title', 'Trip to Lisbon')
        ->assertJsonPath('data.html', fn (string $html) => str_contains($html, '<strong>Day</strong>') && str_contains($html, 'wiki-link-missing') && ! str_contains($html, 'aliases'));

    $this->getJson(route('notes.links.preview', ['title' => 'Nowhere']))->assertOk()->assertJsonPath('data', null);
});

it('does not preview another users note or the linking note itself', function () {
    $own = Note::factory()->create(['body' => '# Self']);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.links.preview', ['title' => 'Theirs']))->assertJsonPath('data', null);
    $this->getJson(route('notes.links.preview', ['title' => 'Self', 'from' => $own->id]))->assertJsonPath('data', null);
});

it('previews by note id, only the users own notes, and needs a title or id', function () {
    $own = Note::factory()->create(['body' => '# Mine']);
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.links.preview', ['note' => $own->id]))->assertJsonPath('data.title', 'Mine');
    $this->getJson(route('notes.links.preview', ['note' => $theirs->id]))->assertJsonPath('data', null);
    $this->getJson(route('notes.links.preview'))->assertUnprocessable();
});
