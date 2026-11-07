<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('moves a deleted note to the trash', function () {
    $note = Note::factory()->create();

    $this->delete(route('notes.destroy', $note))->assertRedirect(route('notes.index'));

    expect($note->refresh()->trashed())->toBeTrue()
        ->and(Note::query()->find($note->id))->toBeNull();
});

it('lists only trashed notes in the trash, and keeps them out of the other lists', function () {
    $trashed = Note::factory()->trashed()->create();
    Note::factory()->create();

    $this->get(route('notes.index', ['view' => 'trash']))
        ->assertInertia(fn (Assert $page) => $page->where('notes.data', fn ($notes) => collect($notes)->pluck('id')->all() === [$trashed->id]));

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('notes.data', fn ($notes) => !collect($notes)->contains('id', $trashed->id)));
});

it('opens a trashed note marked as trashed, and refuses edits to it', function () {
    $note = Note::factory()->trashed()->create(['body' => '<h1>Old</h1>']);

    $this->get(route('notes.show', ['note' => $note, 'view' => 'trash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('note.data.trashed', true)->where('note.data.body', '<h1>Old</h1>'));

    $this->putJson(route('notes.update', $note), ['body' => 'changed'])->assertNotFound();
    $this->post(route('notes.pin', $note))->assertNotFound();
    expect($this->user->can('update', $note))->toBeFalse()
        ->and($this->user->can('delete', $note))->toBeFalse();
});

it('restores a trashed note', function () {
    $note = Note::factory()->trashed()->create();

    $this->post(route('notes.restore', $note))->assertRedirect(route('notes.show', $note));

    expect($note->refresh()->trashed())->toBeFalse();
});

it('deletes a trashed note forever', function () {
    $note = Note::factory()->trashed()->create();

    $this->delete(route('notes.force-delete', $note))->assertRedirect(route('notes.index', ['view' => 'trash']));

    expect(Note::query()->withTrashed()->find($note->id))->toBeNull();
});

it('empties the trash', function () {
    $kept = Note::factory()->create();
    Note::factory()->trashed()->count(2)->create();

    $this->delete(route('notes.trash.empty'))->assertRedirect(route('notes.index', ['view' => 'trash']));

    expect(Note::query()->withTrashed()->pluck('id')->all())->toBe([$kept->id]);
});

it('leaves other users trash alone', function () {
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->trashed()->create();
    $this->actingAs($this->user);

    $this->delete(route('notes.trash.empty'));
    $this->post(route('notes.restore', $theirs))->assertNotFound();
    $this->delete(route('notes.force-delete', $theirs))->assertNotFound();
    $this->get(route('notes.show', $theirs))->assertNotFound();

    expect(Note::query()->withoutGlobalScopes()->find($theirs->id))->not->toBeNull();
});
