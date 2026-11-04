<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

it('counts the notes in each sidebar list for the current user only', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->count(3)->create();
    Note::factory()->archived()->create();
    $this->actingAs(User::factory()->create());
    Note::factory()->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts', ['notes' => 1, 'bookmarks' => 0, 'archive' => 0, 'trash' => 0]));
});

it('counts bookmarked, archived and trashed notes', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->count(2)->create();
    Note::factory()->bookmarked()->create();
    Note::factory()->archived()->bookmarked()->create();
    Note::factory()->trashed()->count(2)->create();

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('counts', ['notes' => 3, 'bookmarks' => 2, 'archive' => 1, 'trash' => 2]));
});
