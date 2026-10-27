<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteVersion;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$bodies = fn (Note $note): array => $note->versions()->orderBy('id')->pluck('body')->all();

it('records a version when a note is created', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'first']);

    expect($bodies($note))->toBe(['first']);
});

it('folds saves within the interval into the latest version', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'one']);

    $this->travel(2)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'two']);

    expect($bodies($note))->toBe(['two']);
});

it('starts a new version after the interval', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'one']);

    $this->travel(6)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'two']);

    expect($bodies($note))->toBe(['one', 'two']);
});

it('does not record saves that leave the body unchanged', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'same']);

    $this->travel(10)->minutes();
    $this->post(route('notes.pin', ['note' => $note]));
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'same']);

    expect($bodies($note))->toBe(['same']);
});

it('never overwrites a named version', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'draft']);
    $version = $note->versions()->sole();

    $this->putJson(route('notes.versions.update', ['note' => $note, 'version' => $version]), ['name' => 'Before rewrite'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Before rewrite');

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'rewrite']);

    expect($bodies($note))->toBe(['draft', 'rewrite']);
});

it('keeps only the newest unnamed versions but all named ones', function () use ($bodies) {
    config(['notes.versions.keep' => 2]);
    $note = Note::factory()->create(['body' => 'v1']);
    $note->versions()->sole()->update(['name' => 'kept']);

    foreach (['v2', 'v3', 'v4'] as $body) {
        $this->travel(6)->minutes();
        $this->putJson(route('notes.update', ['note' => $note]), ['body' => $body]);
    }

    expect($bodies($note))->toBe(['v1', 'v3', 'v4']);
});

it('lists versions newest first', function () {
    $note = Note::factory()->create(['body' => 'old']);
    $this->travel(6)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => "# New\nbody"]);

    $this->getJson(route('notes.versions.index', ['note' => $note]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'New')
        ->assertJsonPath('data.1.characters', 3);
});

it('shows a version with a diff against the current note', function () {
    $note = Note::factory()->create(['body' => "a\nb"]);
    $old = $note->versions()->sole();
    $this->travel(6)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => "a\nc"]);

    $this->getJson(route('notes.versions.show', ['note' => $note, 'version' => $old]))
        ->assertOk()
        ->assertJsonPath('data.body', "a\nb")
        ->assertJsonPath('data.compared_with', null)
        ->assertJsonPath('data.stats', ['added' => 1, 'removed' => 1])
        ->assertJsonPath('data.diff', [
            ['type' => 'same', 'text' => 'a'],
            ['type' => 'remove', 'text' => 'b'],
            ['type' => 'add', 'text' => 'c'],
        ]);
});

it('compares two versions', function () {
    $note = Note::factory()->create(['body' => 'one']);
    $first = $note->versions()->sole();
    $this->travel(6)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'two']);
    $second = $note->versions()->latest('id')->first();

    $this->getJson(route('notes.versions.show', ['note' => $note, 'version' => $second, 'compare' => $first->id]))
        ->assertJsonPath('data.compared_with.id', $first->id)
        ->assertJsonPath('data.diff', [
            ['type' => 'remove', 'text' => 'one'],
            ['type' => 'add', 'text' => 'two'],
        ]);
});

it('restores a version as a new edit', function () use ($bodies) {
    $note = Note::factory()->create(['body' => 'original']);
    $original = $note->versions()->sole();
    $this->travel(6)->minutes();
    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'mistake']);
    $this->travel(6)->minutes();

    $this->post(route('notes.versions.restore', ['note' => $note, 'version' => $original]))
        ->assertRedirect(route('notes.show', ['note' => $note]));

    expect($note->refresh()->body)->toBe('original')
        ->and($bodies($note))->toBe(['original', 'mistake', 'original']);
});

it('validates version names', function () {
    $note = Note::factory()->create();
    $version = $note->versions()->sole();

    $this->putJson(route('notes.versions.update', ['note' => $note, 'version' => $version]), ['name' => str_repeat('a', 101)])
        ->assertJsonValidationErrors('name');
    $this->putJson(route('notes.versions.update', ['note' => $note, 'version' => $version]), ['name' => '  '])
        ->assertJsonPath('data.name', null);
});

it('keeps versions private to their note and user', function () {
    $mine = Note::factory()->create();
    $other = Note::factory()->create();
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create();
    $this->actingAs($this->user);

    $this->getJson(route('notes.versions.show', ['note' => $mine, 'version' => $other->versions()->sole()]))->assertNotFound();
    $this->getJson(route('notes.versions.index', ['note' => $foreign]))->assertNotFound();
    $this->getJson(route('notes.versions.show', ['note' => $mine, 'version' => $mine->versions()->sole(), 'compare' => $other->versions()->sole()->id]))->assertNotFound();
    $this->post(route('notes.versions.restore', ['note' => $foreign, 'version' => NoteVersion::query()->where('note_id', $foreign->id)->sole()]))->assertNotFound();
});

it('lets you read but not restore a trashed note history', function () {
    $note = Note::factory()->create(['body' => 'kept']);
    $version = $note->versions()->sole();
    $note->delete();

    $this->getJson(route('notes.versions.index', ['note' => $note]))->assertOk();
    $this->post(route('notes.versions.restore', ['note' => $note, 'version' => $version]))->assertNotFound();
});

it('deletes versions with the note', function () {
    $note = Note::factory()->create();
    $note->forceDelete();

    expect(NoteVersion::query()->count())->toBe(0);
});
