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

$tagNames = fn (Note $note): array => $note->tags()->orderBy('name')->pluck('name')->all();

it('syncs tags when a note is created', function () use ($tagNames) {
    $this->post(route('notes.store'), ['body' => "# Plan\n#work #work/meetings"]);

    expect($tagNames(Note::query()->sole()))->toBe(['work', 'work/meetings']);
});

it('adds and removes tags as the body changes', function () use ($tagNames) {
    $note = Note::factory()->create(['body' => '#a #b']);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => '#b #c']);

    expect($tagNames($note))->toBe(['b', 'c']);
});

it('deletes tags no note uses any more', function () {
    $note = Note::factory()->create(['body' => '#gone #kept']);
    Note::factory()->create(['body' => '#kept']);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'no tags']);

    expect(Tag::query()->pluck('name')->all())->toBe(['kept']);
});

it('deletes unused tags when a note is deleted', function () {
    $note = Note::factory()->create(['body' => '#only-here']);

    $this->delete(route('notes.destroy', ['note' => $note]));

    expect(Tag::query()->count())->toBe(0);
});

it('reuses one tag across notes', function () {
    Note::factory()->create(['body' => '#shared']);
    Note::factory()->create(['body' => '#Shared']);

    expect(Tag::query()->sole()->notes()->count())->toBe(2);
});

it('keeps each user tags separate', function () {
    Note::factory()->create(['body' => '#work']);
    $other = User::factory()->create();
    $this->actingAs($other);
    Note::factory()->create(['body' => '#work']);

    expect(Tag::query()->count())->toBe(1)
        ->and(Tag::query()->withoutGlobalScopes()->count())->toBe(2);

    $this->actingAs($this->user);
    expect(Tag::query()->sole()->notes()->count())->toBe(1);
});

it('does not touch tags when the body is unchanged', function () {
    $note = Note::factory()->create(['body' => '#a']);
    $tag = Tag::query()->sole();

    $this->post(route('notes.pin', ['note' => $note]));

    expect(Tag::query()->sole()->is($tag))->toBeTrue();
});

it('syncs tags after a checklist toggle', function () use ($tagNames) {
    $note = Note::factory()->create(['body' => '- [ ] call mom #family']);

    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => 1]), ['checked' => true])->assertOk();

    expect($tagNames($note))->toBe(['family']);
});

it('ignores tags in code and links', function () use ($tagNames) {
    $note = Note::factory()->create(['body' => "`#code`\n\n```\n#fence\n```\n\nhttps://x.test/#frag #real"]);

    expect($tagNames($note))->toBe(['real']);
});

it('sends the open note tags to the page', function () {
    $note = Note::factory()->create(['body' => '#b #a']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->where('note.data.tags', ['a', 'b']));
});
