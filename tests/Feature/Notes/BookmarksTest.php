<?php

use Coleus\Notes\Models\Bookmark;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\BookmarkService;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$sidebar = function (): array {
    $bookmarks = [];
    $this->get(route('notes.index'))->assertInertia(function (Assert $page) use (&$bookmarks) {
        $bookmarks = $page->toArray()['props']['bookmarks'];
    });

    return $bookmarks;
};

it('bookmarks a note once and a heading in it', function () use ($sidebar) {
    $note = Note::factory()->create(['body' => "# Trip\n\n## Packing"]);

    $this->post(route('notes.bookmarks.store', ['note' => $note]))->assertRedirect();
    $this->post(route('notes.bookmarks.store', ['note' => $note]));
    $this->post(route('notes.bookmarks.store', ['note' => $note]), ['heading' => 'Packing']);

    expect(Bookmark::query()->count())->toBe(2)
        ->and($sidebar->call($this))->toMatchArray([[
            'group' => null,
            'bookmarks' => [
                ['id' => Bookmark::query()->where('type', 'note')->value('id'), 'type' => 'note', 'note_id' => $note->id, 'heading' => null, 'filters' => null, 'label' => null, 'title' => 'Trip', 'group' => null],
                ['id' => Bookmark::query()->where('type', 'heading')->value('id'), 'type' => 'heading', 'note_id' => $note->id, 'heading' => 'Packing', 'filters' => null, 'label' => null, 'title' => 'Packing', 'group' => null],
            ],
        ]]);
});

it('renames, groups and reorders bookmarks', function () use ($sidebar) {
    [$a, $b, $c] = Note::factory()->count(3)->create()->all();
    $service = app(BookmarkService::class);
    $first = $service->bookmarkNote($a, null);
    $second = $service->bookmarkNote($b, null);
    $third = $service->bookmarkNote($c, null);

    $this->put(route('notes.bookmarks.update', ['bookmark' => $third]), ['label' => 'Daily', 'group' => 'Work']);
    $this->post(route('notes.bookmarks.move', ['bookmark' => $second]), ['step' => -1]);

    $groups = $sidebar->call($this);
    expect(collect($groups)->pluck('group')->all())->toBe([null, 'Work'])
        ->and(collect($groups[0]['bookmarks'])->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($groups[1]['bookmarks'][0]['title'])->toBe('Daily');
});

it('hides bookmarks of trashed notes and deletes them with the note', function () use ($sidebar) {
    $note = Note::factory()->create();
    app(BookmarkService::class)->bookmarkNote($note, null);

    $this->delete(route('notes.destroy', ['note' => $note]));
    expect($sidebar->call($this))->toBe([]);

    $this->post(route('notes.restore', ['note' => $note]));
    expect($sidebar->call($this))->toHaveCount(1);

    $note->delete();
    $note->forceDelete();
    expect(Bookmark::query()->count())->toBe(0);
});

it('removes a bookmark', function () {
    $bookmark = app(BookmarkService::class)->bookmarkNote(Note::factory()->create(), null);

    $this->delete(route('notes.bookmarks.destroy', ['bookmark' => $bookmark]))->assertRedirect();

    expect(Bookmark::query()->count())->toBe(0);
});

it('validates bookmark edits and moves', function () {
    $bookmark = app(BookmarkService::class)->bookmarkNote(Note::factory()->create(), null);

    $this->put(route('notes.bookmarks.update', ['bookmark' => $bookmark]), ['label' => str_repeat('a', 101)])->assertSessionHasErrors('label');
    $this->post(route('notes.bookmarks.move', ['bookmark' => $bookmark]), ['step' => 3])->assertSessionHasErrors('step');
});

it('keeps bookmarks private', function () {
    $this->actingAs(User::factory()->create());
    $theirNote = Note::factory()->create();
    $theirs = app(BookmarkService::class)->bookmarkNote($theirNote, null);
    $this->actingAs($this->user);

    $this->post(route('notes.bookmarks.store', ['note' => $theirNote]))->assertNotFound();
    $this->put(route('notes.bookmarks.update', ['bookmark' => $theirs]), ['label' => 'x'])->assertNotFound();
    $this->delete(route('notes.bookmarks.destroy', ['bookmark' => $theirs]))->assertNotFound();
});

it('saves the current filters as a search', function () use ($sidebar) {
    $folder = app(Coleus\Notes\Services\FolderService::class)->create('Work', null);

    $this->post(route('notes.searches.store'), ['label' => 'Urgent work', 'tag' => 'Urgent', 'q' => 'plan', 'view' => 'notes', 'folder' => $folder->id])->assertRedirect();

    $bookmark = $sidebar->call($this)[0]['bookmarks'][0];
    expect($bookmark)->toMatchArray(['type' => 'search', 'title' => 'Urgent work', 'filters' => ['tag' => 'urgent', 'q' => 'plan', 'folder' => $folder->id]]);
});

it('needs a name and some filter to save a search', function () {
    $this->post(route('notes.searches.store'), ['q' => 'plan'])->assertSessionHasErrors('label');
    $this->post(route('notes.searches.store'), ['label' => 'Everything'])->assertSessionHasErrors('label');
    $this->post(route('notes.searches.store'), ['label' => 'x', 'view' => 'nowhere'])->assertSessionHasErrors('view');
    expect(Bookmark::query()->count())->toBe(0);
});
