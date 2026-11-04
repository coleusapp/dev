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

it('lists notes by when they were last edited, pinned or not, without archived ones', function () use ($recentIds) {
    $older = Note::factory()->create(['updated_at' => now()->subDays(2)]);
    $newer = Note::factory()->create(['updated_at' => now()->subDay()]);
    $pinned = Note::factory()->pinned()->create(['updated_at' => now()->subDays(3)]);
    Note::factory()->archived()->create();

    expect($recentIds->call($this))->toBe([$newer->id, $older->id, $pinned->id]);
});

it('ignores the sort setting and keeps the list short', function () use ($recentIds) {
    $this->put(route('notes.sort.update'), ['sort' => 'created', 'direction' => 'asc']);
    Note::factory()->count(NoteView::RECENT_LIMIT + 2)->create();
    $newest = Note::factory()->create(['updated_at' => now()->addMinute()]);

    $ids = $recentIds->call($this);

    expect($ids)->toHaveCount(NoteView::RECENT_LIMIT)->and($ids[0])->toBe($newest->id);
});

it('does not touch updated_at when a note is opened', function () {
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->get(route('notes.show', $note))->assertOk();

    expect($note->refresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});
