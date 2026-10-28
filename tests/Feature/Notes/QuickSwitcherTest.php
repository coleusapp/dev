<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists live notes with aliases, recently opened first', function () {
    $edited = Note::factory()->create(['body' => '# Edited', 'updated_at' => now()->subDay()]);
    $opened = Note::factory()->create(['body' => "---\naliases: [Lisbon, PT]\n---\n# Trip"]);
    $opened->forceFill(['opened_at' => now()])->saveQuietly();
    $archived = Note::factory()->create(['body' => '# Old', 'archived_at' => now(), 'updated_at' => now()->subWeek()]);

    $this->getJson(route('notes.switcher'))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['id' => $opened->id, 'title' => 'Trip', 'aliases' => ['Lisbon', 'PT'], 'archived' => false],
            ['id' => $edited->id, 'title' => 'Edited', 'aliases' => [], 'archived' => false],
            ['id' => $archived->id, 'title' => 'Old', 'aliases' => [], 'archived' => true],
        ]]);
});

it('leaves out trashed notes and other users notes', function () {
    Note::factory()->trashed()->create(['body' => '# Gone']);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.switcher'))->assertOk()->assertExactJson(['data' => []]);
});

it('requires sign-in', function () {
    auth()->logout();

    $this->getJson(route('notes.switcher'))->assertUnauthorized();
});
