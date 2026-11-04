<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('archives a note without touching updated_at', function () {
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->from(route('notes.show', $note))->post(route('notes.archive', ['note' => $note]))->assertRedirect(route('notes.show', $note));

    expect($note->refresh()->archived_at)->not->toBeNull()
        ->and($note->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('unarchives a note', function () {
    $note = Note::factory()->create(['archived_at' => now()]);

    $this->from(route('notes.show', $note))->delete(route('notes.unarchive', ['note' => $note]))->assertRedirect(route('notes.show', $note));

    expect($note->refresh()->archived_at)->toBeNull();
});

it('hides archived notes from the main list', function () {
    $active = Note::factory()->create();
    Note::factory()->create(['archived_at' => now()]);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.view', 'notes')
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $active->id));
});

it('shows only archived notes in the archive view', function () {
    Note::factory()->create();
    $archived = Note::factory()->create(['archived_at' => now()]);

    $this->get(route('notes.index', ['view' => 'archive']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.view', 'archive')
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $archived->id)
            ->where('notes.data.0.archived', true));
});

it('opens an archived note', function () {
    $note = Note::factory()->create(['archived_at' => now()]);

    $this->get(route('notes.show', ['note' => $note, 'view' => 'archive']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('note.data.archived', true));
});

it('rejects an unknown view', function () {
    $this->get(route('notes.index', ['view' => 'everything']))->assertSessionHasErrors('view');
});

it('cannot archive or unarchive another user note', function (string $method, string $route) {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create();
    $this->actingAs($this->user);

    $this->{$method}(route($route, ['note' => $foreign]))->assertNotFound();
    expect(Note::query()->withoutGlobalScopes()->find($foreign->id)->archived_at)->toBeNull();
})->with([
    'archive' => ['post', 'notes.archive'],
    'unarchive' => ['delete', 'notes.unarchive'],
]);

it('keeps the archive view across a delete redirect', function () {
    $note = Note::factory()->create(['archived_at' => now()]);

    $this->delete(route('notes.destroy', ['note' => $note, 'view' => 'archive']))
        ->assertRedirect(route('notes.index', ['view' => 'archive']));
});
