<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->apple = Note::factory()->create(['body' => '# apple', 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(1)]);
    $this->banana = Note::factory()->create(['body' => '# Banana', 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(3)]);
    $this->cherry = Note::factory()->create(['body' => '# cherry', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
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

it('sorts by created date, either direction, and remembers it', function (string $sort, string $direction, array $names) use ($listIds) {
    $this->put(route('notes.sort.update'), ['sort' => $sort, 'direction' => $direction])->assertRedirect();

    expect($listIds->call($this))->toBe(array_map(fn (string $name) => $this->{$name}->id, $names))
        ->and(NotePreferences::get('list.sort'))->toBe($sort);
})->with([
    'created, newest first' => ['created', 'desc', ['banana', 'cherry', 'apple']],
    'created, oldest first' => ['created', 'asc', ['apple', 'cherry', 'banana']],
]);

it('keeps pinned notes first', function () use ($listIds) {
    $this->banana->forceFill(['pinned_at' => now()])->save();

    $this->put(route('notes.sort.update'), ['sort' => 'created', 'direction' => 'asc']);

    expect($listIds->call($this))->toBe([$this->banana->id, $this->apple->id, $this->cherry->id]);
});

it('validates sort input', function (array $payload, string $field) {
    $this->put(route('notes.sort.update'), $payload)->assertSessionHasErrors($field);
})->with([
    'unknown sort' => [['sort' => 'title'], 'sort'],
    'unknown direction' => [['sort' => 'created', 'direction' => 'up'], 'direction'],
    'no sort' => [['direction' => 'asc'], 'sort'],
]);
