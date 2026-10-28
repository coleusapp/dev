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
