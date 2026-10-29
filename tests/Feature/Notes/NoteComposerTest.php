<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('merges a note into another, trashing it and pointing its links at the target', function () {
    $target = Note::factory()->create(['body' => "# Trip\nflights"]);
    $source = Note::factory()->create(['body' => "---\ntags: [x]\n---\n# Packing\n- [ ] sunscreen"]);
    $linking = Note::factory()->create(['body' => "# Plan\nsee [[Packing]]"]);

    $this->post(route('notes.merge', $source), ['into' => $target->id])->assertRedirect(route('notes.show', $target));

    expect($target->fresh()->body)->toBe("# Trip\nflights\n\n# Packing\n- [ ] sunscreen\n")
        ->and($source->fresh()->trashed())->toBeTrue()
        ->and($linking->fresh()->body)->toBe("# Plan\nsee [[Trip]]");
});

it('refuses merging into itself, locked notes and other users notes', function () {
    $note = Note::factory()->create(['body' => '# A']);
    $locked = Note::factory()->create(['body' => '# B']);
    $locked->forceFill(['lock_key_id' => 1, 'body' => ''])->save();

    $this->post(route('notes.merge', $note), ['into' => $note->id])->assertSessionHasErrors('into');
    $this->post(route('notes.merge', $note), ['into' => $locked->id])->assertSessionHasErrors('into');

    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->post(route('notes.merge', $note), ['into' => $theirs->id])->assertNotFound();
});

it('extracts a selection into a new note in the same folder', function () {
    $work = app(FolderService::class)->create('Work', null);

    $this->postJson(route('notes.extract'), ['body' => "## Budget\n- rent", 'folder_id' => $work->id])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Budget');

    $note = Note::query()->latest('id')->first();
    expect($note->folder_id)->toBe($work->id)->and($note->body)->toBe("## Budget\n- rent");

    $this->postJson(route('notes.extract'), ['body' => "\n\n"])->assertUnprocessable();
});
