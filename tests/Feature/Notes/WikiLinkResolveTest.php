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
