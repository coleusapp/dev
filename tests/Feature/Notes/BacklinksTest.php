<?php

use Coleus\Notes\Markdown\WikiLinks\Backlinks;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$backlinkIds = fn (Note $note): array => Backlinks::for($note)->pluck('id')->sort()->values()->all();

it('lists notes that link to a note', function () use ($backlinkIds) {
    $target = Note::factory()->create(['body' => '# Lisbon']);
    $a = Note::factory()->create(['body' => "# Trip\nsee [[Lisbon]]"]);
    $b = Note::factory()->create(['body' => "# Budget\nsee [[lisbon]] costs"]);
    Note::factory()->create(['body' => "# Other\nLisbon without brackets"]);

    expect($backlinkIds($target))->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('ignores links inside code and to longer titles', function () use ($backlinkIds) {
    $target = Note::factory()->create(['body' => '# Lisbon']);
    Note::factory()->create(['body' => "# Code\n`[[Lisbon]]`"]);
    Note::factory()->create(['body' => "# Longer\n[[Lisbon trip]]"]);

    expect($backlinkIds($target))->toBe([]);
});

it('excludes trashed notes, other users and the note itself', function () use ($backlinkIds) {
    $target = Note::factory()->create(['body' => "# Lisbon\n[[Lisbon]]"]);
    Note::factory()->trashed()->create(['body' => '[[Lisbon]] trashed']);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '[[Lisbon]] theirs']);
    $this->actingAs($this->user);

    expect($backlinkIds($target))->toBe([]);
});

it('shows backlinks only on the note the link resolves to', function () use ($backlinkIds) {
    $older = Note::factory()->create(['body' => '# Ideas', 'updated_at' => now()->subDay()]);
    $newer = Note::factory()->create(['body' => '# Ideas', 'updated_at' => now()->subHour()]);
    $linking = Note::factory()->create(['body' => "# Hub\n[[Ideas]]"]);

    expect($backlinkIds($newer))->toBe([$linking->id])
        ->and($backlinkIds($older))->toBe([]);
});

it('treats LIKE wildcards in titles literally', function () use ($backlinkIds) {
    $target = Note::factory()->create(['body' => '# 100% done']);
    Note::factory()->create(['body' => '[[100x done]]']);
    $real = Note::factory()->create(['body' => '[[100% done]]']);

    expect($backlinkIds($target))->toBe([$real->id]);
});

it('sends backlinks with the open note', function () {
    $target = Note::factory()->create(['body' => '# Lisbon']);
    $linking = Note::factory()->create(['body' => "# Trip\n[[Lisbon]]"]);

    $this->get(route('notes.show', ['note' => $target]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('note.data.backlinks', 1)
            ->where('note.data.backlinks.0.id', $linking->id)
            ->where('note.data.backlinks.0.title', 'Trip'));
});

it('has no backlinks for an untitled note', function () use ($backlinkIds) {
    $target = Note::factory()->create(['body' => '']);
    Note::factory()->create(['body' => '[[]]']);

    expect($backlinkIds($target))->toBe([]);
});
