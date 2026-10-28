<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('locks and unlocks a note without counting as an edit', function () {
    $note = Note::factory()->create(['updated_at' => now()->subDay()]);
    $updated = $note->updated_at->toIso8601String();

    $this->post(route('notes.read-only.store', $note))->assertRedirect();
    expect($note->fresh()->read_only_at)->not->toBeNull()
        ->and($note->fresh()->updated_at->toIso8601String())->toBe($updated);

    $this->get(route('notes.show', $note))->assertInertia(fn (Assert $page) => $page->where('note.data.read_only', true));

    $this->delete(route('notes.read-only.destroy', $note))->assertRedirect();
    expect($note->fresh()->read_only_at)->toBeNull();
});

it('refuses edits to a read-only note, but still lets it be filed', function () {
    $note = Note::factory()->create(['body' => "# Locked\n- [ ] task", 'read_only_at' => now()]);
    $version = $note->versions()->first();

    $this->putJson(route('notes.update', $note), ['body' => 'changed'])->assertForbidden();
    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => 2]), ['checked' => true])->assertForbidden();
    $this->post(route('notes.versions.restore', ['note' => $note, 'version' => $version]))->assertForbidden();

    $this->post(route('notes.pin', $note))->assertRedirect();
    $this->post(route('notes.archive', $note))->assertRedirect();

    expect($note->fresh()->body)->toBe("# Locked\n- [ ] task")
        ->and($note->fresh()->pinned_at)->not->toBeNull();
});

it('does not link mentions inside a read-only note', function () {
    $target = Note::factory()->create(['body' => '# Garden']);
    $source = Note::factory()->create(['body' => "# Plans\nmy garden", 'read_only_at' => now()]);

    $this->postJson(route('notes.mentions.store', ['note' => $target, 'source' => $source]))->assertForbidden();
});

it('does not let another user lock a note', function () {
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create();
    $this->actingAs($this->user);

    $this->post(route('notes.read-only.store', $theirs))->assertNotFound();
});
