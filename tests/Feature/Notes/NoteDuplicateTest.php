<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('duplicates a note into the same folder and opens the copy', function () {
    $work = app(FolderService::class)->create('Work', null);
    $note = Note::factory()->create(['body' => "# Plan\n#work", 'pinned_at' => now(), 'archived_at' => now(), 'created_at' => now()->subYear()]);
    app(FolderService::class)->moveNote($note, $work);

    $response = $this->post(route('notes.duplicate', ['note' => $note, 'folder' => $work->id]));

    $copy = Note::query()->whereKeyNot($note->id)->sole();
    $response->assertRedirect(route('notes.show', ['note' => $copy, 'folder' => $work->id]));
    expect($copy->body)->toBe("# Plan\n#work")
        ->and($copy->title)->toBe('Plan')
        ->and($copy->folder_id)->toBe($work->id)
        ->and($copy->pinned_at)->toBeNull()
        ->and($copy->archived_at)->toBeNull()
        ->and($copy->created_at->isToday())->toBeTrue()
        ->and($copy->tags()->pluck('name')->all())->toBe(['work'])
        ->and($copy->isOwnedBy($this->user->id))->toBeTrue()
        ->and($copy->versions()->count())->toBe(1);
});

it('cannot duplicate another user note or a trashed note', function () {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create();
    $this->actingAs($this->user);
    $trashed = Note::factory()->trashed()->create();

    $this->post(route('notes.duplicate', ['note' => $foreign]))->assertNotFound();
    $this->post(route('notes.duplicate', ['note' => $trashed]))->assertNotFound();
    expect(Note::query()->withTrashed()->count())->toBe(1);
});
