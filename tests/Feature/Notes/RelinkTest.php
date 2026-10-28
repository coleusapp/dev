<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('counts the other notes whose links to the old title now lead nowhere', function () {
    $renamed = Note::factory()->create(['body' => '# New title']);
    Note::factory()->create(['body' => 'see [[Old title]]']);
    Note::factory()->create(['body' => 'see [[old  TITLE#Day|x]]']);
    Note::factory()->create(['body' => 'see `[[Old title]]` only in code']);
    Note::factory()->create(['body' => 'no links']);

    $this->getJson(route('notes.relink.show', ['note' => $renamed, 'title' => 'Old title']))
        ->assertOk()
        ->assertJsonPath('data.count', 2);
});

it('does not count links the old title still resolves', function () {
    $renamed = Note::factory()->create(['body' => "---\naliases: [Old title]\n---\n# New title"]);
    Note::factory()->create(['body' => 'see [[Old title]]']);

    $this->getJson(route('notes.relink.show', ['note' => $renamed, 'title' => 'Old title']))->assertJsonPath('data.count', 0);

    $other = Note::factory()->create(['body' => '# Taken']);
    Note::factory()->create(['body' => 'see [[Taken]]']);

    $this->getJson(route('notes.relink.show', ['note' => $renamed, 'title' => 'Taken']))->assertJsonPath('data.count', 0);
    expect($other->title)->toBe('Taken');
});

it('rewrites those links to the new title, as normal edits', function () {
    $renamed = Note::factory()->create(['body' => '# New title']);
    $linking = Note::factory()->create(['body' => "# Plans\n[[Old title]], [[Old title#Day|day]] and [[Unrelated]]"]);
    // A note whose first line is a link would itself be titled "Old title", so give it its own title.
    $theirs = Note::factory()->create(['body' => "# Other\n[[Old title]]"]);

    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create(['body' => 'mine [[Old title]]']);
    $this->actingAs($this->user);

    $this->postJson(route('notes.relink.store', $renamed), ['title' => 'Old title'])
        ->assertOk()
        ->assertJsonPath('data.count', 2);

    expect($linking->fresh()->body)->toBe("# Plans\n[[New title]], [[New title#Day|day]] and [[Unrelated]]")
        ->and($theirs->fresh()->body)->toBe("# Other\n[[New title]]")
        ->and($linking->fresh()->versions()->count())->toBeGreaterThan(0)
        ->and(Note::query()->withoutGlobalScopes()->find($foreign->id)->body)->toBe('mine [[Old title]]');

    $this->get(route('notes.show', $renamed))->assertInertia(fn ($page) => $page->where('note.data.backlinks', fn ($backlinks) => count($backlinks) === 2));
});

it('refuses titles links cannot carry, and checks input', function () {
    $hash = Note::factory()->create(['body' => '# C# tips']);
    Note::factory()->create(['body' => '[[Old]]']);

    $this->postJson(route('notes.relink.store', $hash), ['title' => 'Old'])->assertUnprocessable();
    $this->getJson(route('notes.relink.show', ['note' => $hash, 'title' => 'a]]b']))->assertUnprocessable();
    $this->getJson(route('notes.relink.show', ['note' => $hash]))->assertUnprocessable();
});

it('does not let another user relink a note', function () {
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create(['body' => '# Theirs']);
    $this->actingAs($this->user);

    $this->postJson(route('notes.relink.store', $theirs), ['title' => 'Old'])->assertNotFound();
});
