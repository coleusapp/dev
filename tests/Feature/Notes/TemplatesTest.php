<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->folders = app(FolderService::class);
});

it('lists the notes in the Templates folder and its sub-folders', function () {
    $templates = $this->folders->create('templates', null);
    $meetings = $this->folders->create('Meetings', $templates);
    $nested = $this->folders->create('Templates', $this->folders->create('Work', null));
    $daily = Note::factory()->create(['body' => "# Daily {{date}}\n- [ ] "]);
    $this->folders->moveNote($daily, $templates);
    $standup = Note::factory()->create(['body' => '# Standup']);
    $this->folders->moveNote($standup, $meetings);
    $elsewhere = Note::factory()->create(['body' => '# Not a template']);
    $this->folders->moveNote($elsewhere, $nested);
    $trashed = Note::factory()->create(['body' => '# Old template']);
    $this->folders->moveNote($trashed, $templates);
    $trashed->delete();

    $this->getJson(route('notes.templates.index'))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['id' => $daily->id, 'title' => 'Daily {{date}}', 'body' => "# Daily {{date}}\n- [ ] "],
            ['id' => $standup->id, 'title' => 'Standup', 'body' => '# Standup'],
        ]]);
});

it('has no templates without the folder, and never another users', function () {
    $this->getJson(route('notes.templates.index'))->assertExactJson(['data' => []]);

    $this->actingAs(User::factory()->create());
    $theirs = $this->folders->create('Templates', null);
    $this->folders->moveNote(Note::factory()->create(['body' => '# Theirs']), $theirs);
    $this->actingAs($this->user);

    $this->getJson(route('notes.templates.index'))->assertExactJson(['data' => []]);
});

it('creates a note from a filled template, front matter included, in the folder being viewed', function () {
    $work = $this->folders->create('Work', null);

    $this->post(route('notes.store', ['folder' => $work->id]), ['body' => "---\ntype: meeting\n---\n# Standup 2026-10-28", 'folder_id' => $work->id])->assertRedirect();

    $note = Note::query()->latest('id')->first();
    expect($note->folder_id)->toBe($work->id)
        ->and($note->body)->toBe("---\ntype: meeting\n---\n# Standup 2026-10-28")
        ->and($note->properties()->pluck('key')->all())->toBe(['type']);
});
