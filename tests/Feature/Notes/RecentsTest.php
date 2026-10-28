<?php

use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$recentIds = function (): array {
    $ids = [];
    $this->get(route('notes.index', ['view' => 'recent']))->assertInertia(function (Assert $page) use (&$ids) {
        $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->all();
    });

    return $ids;
};

it('records when a note is opened without touching updated_at', function () {
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->get(route('notes.show', ['note' => $note]))->assertOk();

    expect($note->refresh()->opened_at)->not->toBeNull()
        ->and($note->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($note->versions()->count())->toBe(1);
});

it('lists opened notes, most recently opened first, including archived ones', function () use ($recentIds) {
    [$a, $b] = Note::factory()->count(2)->create();
    $archived = Note::factory()->create(['archived_at' => now()]);
    Note::factory()->create();

    $this->get(route('notes.show', ['note' => $a]));
    $this->travel(1)->minutes();
    $this->get(route('notes.show', ['note' => $archived]));
    $this->travel(1)->minutes();
    $this->get(route('notes.show', ['note' => $b]));
    $this->travel(1)->minutes();
    $this->get(route('notes.show', ['note' => $a]));

    expect($recentIds->call($this))->toBe([$a->id, $b->id, $archived->id]);
});

it('ignores the sort setting and keeps the list short', function () use ($recentIds) {
    $notes = Note::factory()->count(NoteView::RECENT_LIMIT + 2)->create();
    foreach ($notes as $index => $note) {
        $note->forceFill(['opened_at' => now()->addMinutes($index)])->saveQuietly();
    }
    $this->put(route('notes.sort.update'), ['sort' => 'title', 'direction' => 'asc']);

    $ids = $recentIds->call($this);

    expect($ids)->toHaveCount(NoteView::RECENT_LIMIT)
        ->and($ids[0])->toBe($notes->last()->id);
});

it('does not record opening a trashed note and leaves it out', function () use ($recentIds) {
    $note = Note::factory()->trashed()->create();

    $this->get(route('notes.show', ['note' => $note, 'view' => 'trash']));

    expect($note->refresh()->opened_at)->toBeNull()
        ->and($recentIds->call($this))->toBe([]);
});
