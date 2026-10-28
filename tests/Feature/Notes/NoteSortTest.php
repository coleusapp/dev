<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Notes\Services\FolderService;
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

it('sorts by created date or title, either direction, and remembers it', function (string $sort, string $direction, array $names) use ($listIds) {
    $this->put(route('notes.sort.update'), ['sort' => $sort, 'direction' => $direction])->assertRedirect();

    expect($listIds->call($this))->toBe(array_map(fn (string $name) => $this->{$name}->id, $names))
        ->and(NotePreferences::get('list.sort'))->toBe($sort);
})->with([
    'created, newest first' => ['created', 'desc', ['banana', 'cherry', 'apple']],
    'created, oldest first' => ['created', 'asc', ['apple', 'cherry', 'banana']],
    'title A–Z, case-insensitive' => ['title', 'asc', ['apple', 'banana', 'cherry']],
    'title Z–A' => ['title', 'desc', ['cherry', 'banana', 'apple']],
]);

it('keeps pinned notes first and untitled notes last by title', function () use ($listIds) {
    $untitled = Note::factory()->create(['body' => '']);
    $this->cherry->forceFill(['pinned_at' => now()])->save();

    $this->put(route('notes.sort.update'), ['sort' => 'title', 'direction' => 'asc']);

    expect($listIds->call($this))->toBe([$this->cherry->id, $this->apple->id, $this->banana->id, $untitled->id]);
});

it('gives a folder its own sort and can reset it', function () use ($listIds) {
    $folders = app(FolderService::class);
    $work = $folders->create('Work', null);
    foreach ([$this->apple, $this->banana, $this->cherry] as $note) {
        $folders->moveNote($note, $work);
    }

    $this->put(route('notes.sort.update'), ['sort' => 'title', 'direction' => 'desc', 'folder_id' => $work->id]);

    expect($listIds->call($this, ['folder' => $work->id]))->toBe([$this->cherry->id, $this->banana->id, $this->apple->id])
        ->and($listIds->call($this))->toBe([$this->apple->id, $this->cherry->id, $this->banana->id]);

    $this->get(route('notes.index', ['folder' => $work->id]))
        ->assertInertia(fn (Assert $page) => $page->where('sort', ['sort' => 'title', 'direction' => 'desc', 'scope' => 'folder']));

    $this->put(route('notes.sort.update'), ['sort' => null, 'folder_id' => $work->id]);
    $this->get(route('notes.index', ['folder' => $work->id]))
        ->assertInertia(fn (Assert $page) => $page->where('sort.scope', 'default'));
});

it('validates sort input and folder ownership', function (array $payload, string $field) {
    $this->put(route('notes.sort.update'), $payload)->assertSessionHasErrors($field);
})->with([
    'unknown sort' => [['sort' => 'size'], 'sort'],
    'unknown direction' => [['sort' => 'title', 'direction' => 'up'], 'direction'],
    'unknown folder' => [['sort' => 'title', 'folder_id' => 999], 'folder_id'],
]);
