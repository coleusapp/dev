<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\Tag;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('moves a deleted note to the trash', function () {
    $note = Note::factory()->create();

    $this->delete(route('notes.destroy', ['note' => $note]))->assertRedirect(route('notes.index'));

    $this->assertSoftDeleted($note);
});

it('lists only trashed notes in the trash view', function () {
    Note::factory()->create();
    $trashed = Note::factory()->trashed()->create();

    $this->get(route('notes.index', ['view' => 'trash']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.view', 'trash')
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $trashed->id)
            ->where('notes.data.0.trashed', true));
});

it('keeps trashed notes out of the main list and archive', function (string $view) {
    Note::factory()->trashed()->create(['archived_at' => now()]);

    $this->get(route('notes.index', ['view' => $view]))
        ->assertInertia(fn (Assert $page) => $page->has('notes.data', 0));
})->with(['notes', 'archive']);

it('opens a trashed note read-only', function () {
    $note = Note::factory()->trashed()->create(['body' => 'old']);

    $this->get(route('notes.show', ['note' => $note, 'view' => 'trash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('note.data.trashed', true));

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'new'])->assertNotFound();
    expect($note->refresh()->body)->toBe('old');
});

it('forbids editing a trashed note through the policy', function () {
    $note = Note::factory()->trashed()->create();

    expect($this->user->can('update', $note))->toBeFalse()
        ->and($this->user->can('delete', $note))->toBeFalse()
        ->and($this->user->can('restore', $note))->toBeTrue()
        ->and($this->user->can('forceDelete', $note))->toBeTrue();
});

it('restores a note with its tags', function () {
    $note = Note::factory()->create(['body' => '#travel lisbon']);
    $this->delete(route('notes.destroy', ['note' => $note]));
    expect(Tag::query()->count())->toBe(0);

    $this->post(route('notes.restore', ['note' => $note]))->assertRedirect(route('notes.show', ['note' => $note]));

    expect($note->refresh()->trashed())->toBeFalse()
        ->and($note->tags()->pluck('name')->all())->toBe(['travel']);
});

it('only restores notes that are in the trash', function () {
    $note = Note::factory()->create();

    $this->post(route('notes.restore', ['note' => $note]))->assertForbidden();
});

it('deletes a trashed note forever', function () {
    $note = Note::factory()->trashed()->create(['body' => '#gone']);

    $this->delete(route('notes.force-delete', ['note' => $note]))->assertRedirect(route('notes.index', ['view' => 'trash']));

    expect(Note::query()->withTrashed()->find($note->id))->toBeNull();
});

it('refuses to delete forever a note that is not in the trash', function () {
    $note = Note::factory()->create();

    $this->delete(route('notes.force-delete', ['note' => $note]))->assertForbidden();
    expect(Note::query()->find($note->id))->not->toBeNull();
});

it('empties the trash', function () {
    $kept = Note::factory()->create(['body' => '#kept']);
    Note::factory()->trashed()->count(2)->create();

    $this->delete(route('notes.trash.empty'))->assertRedirect(route('notes.index', ['view' => 'trash']));

    expect(Note::query()->withTrashed()->pluck('id')->all())->toBe([$kept->id])
        ->and(Tag::query()->pluck('name')->all())->toBe(['kept']);
});

it('leaves other users trash alone', function () {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->trashed()->create();
    $this->actingAs($this->user);

    $this->delete(route('notes.trash.empty'));
    $this->post(route('notes.restore', ['note' => $foreign]))->assertNotFound();
    $this->delete(route('notes.force-delete', ['note' => $foreign]))->assertNotFound();
    $this->get(route('notes.show', ['note' => $foreign]))->assertNotFound();

    expect(Note::query()->withoutGlobalScopes()->find($foreign->id))->not->toBeNull();
});

it('drops tags only trashed notes use from the sidebar', function () {
    Note::factory()->create(['body' => '#kept']);
    $note = Note::factory()->create(['body' => '#kept #trashed-only']);

    $this->delete(route('notes.destroy', ['note' => $note]));

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tags', [['name' => 'kept', 'path' => 'kept', 'count' => 1, 'children' => []]]));
});
