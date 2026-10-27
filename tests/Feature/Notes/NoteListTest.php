<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('sorts notes by last updated, newest first', function () {
    $old = Note::factory()->create(['updated_at' => now()->subDays(2)]);
    $newest = Note::factory()->create(['updated_at' => now()]);
    $middle = Note::factory()->create(['updated_at' => now()->subDay()]);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes.data.0.id', $newest->id)
            ->where('notes.data.1.id', $middle->id)
            ->where('notes.data.2.id', $old->id));
});

it('moves a note to the top after it is edited', function () {
    $edited = Note::factory()->create(['updated_at' => now()->subDay()]);
    Note::factory()->create(['updated_at' => now()->subHour()]);

    $this->put(route('notes.update', ['note' => $edited]), ['body' => 'Edited']);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('notes.data.0.id', $edited->id));
});

it('includes a plain text snippet of the body', function () {
    Note::factory()->create(['body' => "# Groceries\n\n- [ ] **milk**\n- eggs"]);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('notes.data.0.snippet', 'milk eggs'));
});

it('renders the empty state with no notes', function () {
    $this->get(route('notes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('notes.data', 0)->where('note', null));
});
