<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\SmartFolder;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$matching = function (array $rules): array {
    $smart = SmartFolder::query()->create(['name' => 'Test', 'rules' => $rules]);
    $ids = [];
    $this->get(route('notes.index', ['smart' => $smart->id]))->assertInertia(function (Assert $page) use (&$ids) {
        $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->sort()->values()->all();
    });

    return $ids;
};

it('matches notes with all or any of the tags', function () use ($matching) {
    $both = Note::factory()->create(['body' => '#work #urgent']);
    $work = Note::factory()->create(['body' => '#work/meetings']);
    $urgent = Note::factory()->create(['body' => '#urgent']);
    Note::factory()->create(['body' => 'none']);

    expect($matching->call($this, ['tags' => ['work', 'urgent']]))->toBe([$both->id])
        ->and($matching->call($this, ['tags' => ['work', 'urgent'], 'tags_match' => 'any']))->toBe(collect([$both->id, $work->id, $urgent->id])->sort()->values()->all());
});

it('matches a folder including its sub-folders', function () use ($matching) {
    $folders = app(FolderService::class);
    $work = $folders->create('Work', null);
    $meetings = $folders->create('Meetings', $work);
    $a = Note::factory()->create();
    $b = Note::factory()->create();
    Note::factory()->create();
    $folders->moveNote($a, $work);
    $folders->moveNote($b, $meetings);

    expect($matching->call($this, ['folder_id' => $work->id]))->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('matches edit and creation dates', function () use ($matching) {
    $recent = Note::factory()->create(['created_at' => now()->subDays(40), 'updated_at' => now()->subDays(2)]);
    $old = Note::factory()->create(['created_at' => now()->subDays(400), 'updated_at' => now()->subDays(300)]);

    expect($matching->call($this, ['edited_within_days' => 7]))->toBe([$recent->id])
        ->and($matching->call($this, ['created_from' => now()->subDays(500)->format('Y-m-d'), 'created_to' => now()->subDays(100)->format('Y-m-d')]))->toBe([$old->id]);
});

it('matches pinned notes and checklists', function () use ($matching) {
    $pinned = Note::factory()->create(['pinned_at' => now(), 'body' => 'p']);
    $open = Note::factory()->create(['body' => "list\n- [ ] milk"]);
    $done = Note::factory()->create(['body' => '1. [X] done']);
    Note::factory()->create(['body' => 'no tasks [ ] here']);

    expect($matching->call($this, ['pinned' => true]))->toBe([$pinned->id])
        ->and($matching->call($this, ['has_open_tasks' => true]))->toBe([$open->id])
        ->and($matching->call($this, ['has_checklist' => true]))->toBe(collect([$open->id, $done->id])->sort()->values()->all());
});

it('combines rules and leaves out archived and trashed notes', function () use ($matching) {
    $match = Note::factory()->create(['body' => "#work\n- [ ] task", 'pinned_at' => now()]);
    Note::factory()->create(['body' => "#work\n- [ ] task"]);
    Note::factory()->create(['body' => "#work\n- [ ] task", 'pinned_at' => now(), 'archived_at' => now()]);
    Note::factory()->trashed()->create(['body' => "#work\n- [ ] task", 'pinned_at' => now()]);

    expect($matching->call($this, ['tags' => ['work'], 'pinned' => true, 'has_open_tasks' => true]))->toBe([$match->id]);
});

it('creates, edits and deletes smart folders, with counts in the sidebar', function () {
    Note::factory()->create(['body' => '#Work']);

    $this->post(route('notes.smart-folders.store'), ['name' => 'Work', 'rules' => ['tags' => ['#Work', ''], 'edited_within_days' => '']])
        ->assertRedirect(route('notes.index', ['smart' => SmartFolder::query()->sole()->id]));
    $smart = SmartFolder::query()->sole();
    expect($smart->rules['tags'])->toBe(['work']);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('smartFolders.0.name', 'Work')->where('smartFolders.0.count', 1));

    $this->put(route('notes.smart-folders.update', ['smartFolder' => $smart]), ['name' => 'Pinned', 'rules' => ['pinned' => true]])->assertRedirect();
    expect($smart->refresh()->name)->toBe('Pinned')->and($smart->rules['tags'])->toBe([]);

    $this->delete(route('notes.smart-folders.destroy', ['smartFolder' => $smart]))->assertRedirect(route('notes.index'));
    expect(SmartFolder::query()->count())->toBe(0);
});

it('validates smart folders', function (array $payload, string $field) {
    $this->post(route('notes.smart-folders.store'), $payload)->assertSessionHasErrors($field);
})->with([
    'no name' => [['name' => '', 'rules' => ['pinned' => true]], 'name'],
    'no rules' => [['name' => 'x', 'rules' => ['tags' => [], 'pinned' => false]], 'rules'],
    'bad days' => [['name' => 'x', 'rules' => ['edited_within_days' => 0]], 'rules.edited_within_days'],
    'bad date' => [['name' => 'x', 'rules' => ['created_from' => '28/10/2026']], 'rules.created_from'],
    'end before start' => [['name' => 'x', 'rules' => ['edited_from' => '2026-10-10', 'edited_to' => '2026-10-01']], 'rules.edited_to'],
    'bad match' => [['name' => 'x', 'rules' => ['tags' => ['a'], 'tags_match' => 'some']], 'rules.tags_match'],
    'unknown folder' => [['name' => 'x', 'rules' => ['folder_id' => 999]], 'rules.folder_id'],
]);

it('keeps smart folders private', function () {
    $this->actingAs(User::factory()->create());
    $theirs = SmartFolder::query()->create(['name' => 'Theirs', 'rules' => ['pinned' => true]]);
    $this->actingAs($this->user);

    $this->get(route('notes.index', ['smart' => $theirs->id]))->assertSessionHasErrors('smart');
    $this->delete(route('notes.smart-folders.destroy', ['smartFolder' => $theirs]))->assertNotFound();
    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('smartFolders', []));
});
