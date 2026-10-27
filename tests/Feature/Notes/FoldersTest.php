<?php

use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->folders = app(FolderService::class);
});

it('creates a folder and opens it', function () {
    $response = $this->post(route('notes.folders.store'), ['name' => '  Work  ']);

    $folder = Folder::query()->sole();
    $response->assertRedirect(route('notes.index', ['folder' => $folder->id]));
    expect($folder->name)->toBe('Work')
        ->and($folder->parent_id)->toBeNull();
});

it('creates a nested folder', function () {
    $parent = $this->folders->create('Work', null);

    $this->post(route('notes.folders.store'), ['name' => 'Meetings', 'parent_id' => $parent->id]);

    expect(Folder::query()->where('name', 'Meetings')->sole()->parent_id)->toBe($parent->id);
});

it('validates folder names', function (array $payload, string $field) {
    $this->folders->create('Work', null);

    $this->post(route('notes.folders.store'), $payload)->assertSessionHasErrors($field);
})->with([
    'missing' => [['name' => ''], 'name'],
    'too long' => [['name' => str_repeat('a', 101)], 'name'],
    'slash' => [['name' => 'a/b'], 'name'],
    'taken by a sibling, any case' => [['name' => 'work'], 'name'],
    'unknown parent' => [['name' => 'x', 'parent_id' => 999], 'parent_id'],
]);

it('allows the same name under different parents', function () {
    $work = $this->folders->create('Work', null);
    $home = $this->folders->create('Home', null);
    $this->folders->create('Ideas', $work);

    $this->post(route('notes.folders.store'), ['name' => 'Ideas', 'parent_id' => $home->id])->assertSessionHasNoErrors();
});

it('cannot use another user folder as a parent', function () {
    $this->actingAs(User::factory()->create());
    $theirs = app(FolderService::class)->create('Theirs', null);
    $this->actingAs($this->user);

    $this->post(route('notes.folders.store'), ['name' => 'Mine', 'parent_id' => $theirs->id])->assertSessionHasErrors('parent_id');
});

it('sends the folder tree with live note counts, sorted by name', function () {
    $work = $this->folders->create('work', null);
    $meetings = $this->folders->create('Meetings', $work);
    $this->folders->create('Archive', null);
    $this->folders->moveNote(Note::factory()->create(), $work);
    $this->folders->moveNote(Note::factory()->create(), $meetings);
    $this->folders->moveNote(Note::factory()->create(['archived_at' => now()]), $work);
    $this->folders->moveNote(Note::factory()->trashed()->create(), $work);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('folders', [
        ['id' => Folder::query()->where('name', 'Archive')->value('id'), 'name' => 'Archive', 'count' => 0, 'children' => []],
        ['id' => $work->id, 'name' => 'work', 'count' => 1, 'children' => [
            ['id' => $meetings->id, 'name' => 'Meetings', 'count' => 1, 'children' => []],
        ]],
    ]));
});

it('filters the list by folder, without sub-folders', function () {
    $work = $this->folders->create('Work', null);
    $meetings = $this->folders->create('Meetings', $work);
    $inWork = Note::factory()->create();
    $this->folders->moveNote($inWork, $work);
    $this->folders->moveNote(Note::factory()->create(), $meetings);
    Note::factory()->create();

    $this->get(route('notes.index', ['folder' => $work->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.folder', $work->id)
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $inWork->id)
            ->where('notes.data.0.folder_id', $work->id));
});

it('rejects filtering by a folder that is not the user', function () {
    $this->actingAs(User::factory()->create());
    $theirs = app(FolderService::class)->create('Theirs', null);
    $this->actingAs($this->user);

    $this->get(route('notes.index', ['folder' => $theirs->id]))->assertSessionHasErrors('folder');
});

it('creates a note inside the folder being viewed and keeps the filter', function () {
    $work = $this->folders->create('Work', null);

    $this->post(route('notes.store', ['folder' => $work->id]), ['body' => '', 'folder_id' => $work->id])
        ->assertRedirect(route('notes.show', ['note' => Note::query()->sole(), 'folder' => $work->id]));

    expect(Note::query()->sole()->folder_id)->toBe($work->id);
});

it('does not let a note be created in another user folder', function () {
    $this->actingAs(User::factory()->create());
    $theirs = app(FolderService::class)->create('Theirs', null);
    $this->actingAs($this->user);

    $this->post(route('notes.store'), ['body' => 'x', 'folder_id' => $theirs->id])->assertSessionHasErrors('folder_id');
    expect(Note::query()->count())->toBe(0);
});

it('keeps folders private', function () {
    $this->folders->create('Mine', null);
    $this->actingAs(User::factory()->create());

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('folders', []));
});

it('renames a folder', function () {
    $folder = $this->folders->create('Wrk', null);

    $this->put(route('notes.folders.update', ['folder' => $folder]), ['name' => 'Work', 'parent_id' => null])->assertRedirect();

    expect($folder->refresh()->name)->toBe('Work');
});

it('keeps its own name when only changing case', function () {
    $folder = $this->folders->create('work', null);

    $this->put(route('notes.folders.update', ['folder' => $folder]), ['name' => 'Work', 'parent_id' => null])->assertSessionHasNoErrors();
});

it('moves a folder under another one and back to the top', function () {
    $work = $this->folders->create('Work', null);
    $ideas = $this->folders->create('Ideas', null);

    $this->put(route('notes.folders.update', ['folder' => $ideas]), ['name' => 'Ideas', 'parent_id' => $work->id]);
    expect($ideas->refresh()->parent_id)->toBe($work->id);

    $this->put(route('notes.folders.update', ['folder' => $ideas]), ['name' => 'Ideas', 'parent_id' => null]);
    expect($ideas->refresh()->parent_id)->toBeNull();
});

it('refuses to move a folder into itself or its own sub-folder', function () {
    $work = $this->folders->create('Work', null);
    $meetings = $this->folders->create('Meetings', $work);

    $this->put(route('notes.folders.update', ['folder' => $work]), ['name' => 'Work', 'parent_id' => $work->id])->assertSessionHasErrors('parent_id');
    $this->put(route('notes.folders.update', ['folder' => $work]), ['name' => 'Work', 'parent_id' => $meetings->id])->assertSessionHasErrors('parent_id');
});

it('deletes a folder with its sub-folders, moving their notes to the trash', function () {
    $work = $this->folders->create('Work', null);
    $meetings = $this->folders->create('Meetings', $work);
    $kept = $this->folders->create('Kept', null);
    $inWork = Note::factory()->create(['body' => '#gone']);
    $inMeetings = Note::factory()->create();
    $elsewhere = Note::factory()->create();
    $this->folders->moveNote($inWork, $work);
    $this->folders->moveNote($inMeetings, $meetings);
    $this->folders->moveNote($elsewhere, $kept);

    $this->delete(route('notes.folders.destroy', ['folder' => $work]))->assertRedirect(route('notes.index'));

    expect(Folder::query()->pluck('name')->all())->toBe(['Kept'])
        ->and($inWork->refresh()->trashed())->toBeTrue()
        ->and($inMeetings->refresh()->trashed())->toBeTrue()
        ->and($inWork->folder_id)->toBeNull()
        ->and($elsewhere->refresh()->trashed())->toBeFalse()
        ->and(Coleus\Notes\Models\Tag::query()->count())->toBe(0);
});

it('moves a note into a folder and out again without counting as an edit', function () {
    $work = $this->folders->create('Work', null);
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->put(route('notes.folder.move', ['note' => $note]), ['folder_id' => $work->id])->assertRedirect();
    expect($note->refresh()->folder_id)->toBe($work->id)
        ->and($note->updated_at->equalTo($updatedAt))->toBeTrue();

    $this->put(route('notes.folder.move', ['note' => $note]), ['folder_id' => null]);
    expect($note->refresh()->folder_id)->toBeNull();
});

it('puts a restored note back in its folder', function () {
    $work = $this->folders->create('Work', null);
    $note = Note::factory()->create();
    $this->folders->moveNote($note, $work);

    $this->delete(route('notes.destroy', ['note' => $note]));
    $this->post(route('notes.restore', ['note' => $note]));

    expect($note->refresh()->folder_id)->toBe($work->id);
});

it('keeps other users folders and notes out of reach', function () {
    $mine = $this->folders->create('Mine', null);
    $this->actingAs(User::factory()->create());
    $theirFolder = app(FolderService::class)->create('Theirs', null);
    $theirNote = Note::factory()->create();
    $this->actingAs($this->user);

    $this->put(route('notes.folders.update', ['folder' => $theirFolder]), ['name' => 'x', 'parent_id' => null])->assertNotFound();
    $this->delete(route('notes.folders.destroy', ['folder' => $theirFolder]))->assertNotFound();
    $this->put(route('notes.folder.move', ['note' => $theirNote]), ['folder_id' => $mine->id])->assertNotFound();
    $this->put(route('notes.folder.move', ['note' => Note::factory()->create()]), ['folder_id' => $theirFolder->id])->assertSessionHasErrors('folder_id');
});
