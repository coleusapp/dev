<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->apple = Note::factory()->create(['body' => '<h1>apple</h1>', 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(1)]);
    $this->banana = Note::factory()->create(['body' => '<h1>Banana</h1>', 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(3)]);
    $this->cherry = Note::factory()->create(['body' => '<h1>cherry</h1>', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
});

$listIds = function (array $query = []): array {
    $ids = [];
    $this->get(route('notes.index', $query))->assertInertia(function (Assert $page) use (&$ids) {
        $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->all();
    });

    return $ids;
};

it('sorts by last modified, newest first, by default', function () use ($listIds) {
    expect($listIds->call($this))->toBe([$this->apple->id, $this->cherry->id, $this->banana->id]);
});

it('keeps pinned notes first', function () use ($listIds) {
    Note::withoutTimestamps(fn () => $this->banana->forceFill(['pinned_at' => now()])->save());

    expect($listIds->call($this))->toBe([$this->banana->id, $this->apple->id, $this->cherry->id]);
});
