<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('counts the notes in each sidebar list for the current user', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
    Note::factory()->count(3)->create();
    Note::factory()->create(['archived_at' => now()]);
    Note::factory()->trashed()->count(2)->create();
    $this->actingAs(User::factory()->create());
    Note::factory()->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts', ['notes' => 1, 'archive' => 0, 'trash' => 0]));
});

it('counts archived and trashed notes separately', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
    Note::factory()->count(3)->create();
    Note::factory()->create(['archived_at' => now()]);
    Note::factory()->trashed()->count(2)->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts', ['notes' => 3, 'archive' => 1, 'trash' => 2]));
});
