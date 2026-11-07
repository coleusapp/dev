<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

it('counts the notes in each sidebar list for the current user only', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->count(3)->create();
    $this->actingAs(User::factory()->create());
    Note::factory()->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts.data', ['notes' => 1, 'trash' => 0]));
});

it('counts trashed notes apart from the others', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->count(2)->create();
    Note::factory()->pinned()->create();
    Note::factory()->trashed()->count(2)->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts.data', ['notes' => 3, 'trash' => 2]));
});
