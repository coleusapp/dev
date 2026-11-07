<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('pins a note without touching updated_at', function () {
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->from(route('notes.show', ['note' => $note]))
        ->post(route('notes.pin', ['note' => $note]))
        ->assertRedirect(route('notes.show', ['note' => $note]));

    expect($note->refresh()->pinned_at)->not->toBeNull()
        ->and($note->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('keeps the original pin time when pinned twice', function () {
    $pinnedAt = now()->subDay()->startOfSecond();
    $note = Note::factory()->create(['pinned_at' => $pinnedAt]);

    $this->post(route('notes.pin', ['note' => $note]));

    expect($note->refresh()->pinned_at->equalTo($pinnedAt))->toBeTrue();
});

it('unpins a note', function () {
    $note = Note::factory()->create(['pinned_at' => now()]);

    $this->delete(route('notes.unpin', ['note' => $note]))->assertRedirect();

    expect($note->refresh()->pinned_at)->toBeNull();
});

it('lists pinned notes first, each group by last updated', function () {
    $recent = Note::factory()->create(['updated_at' => now()]);
    $oldPinned = Note::factory()->create(['updated_at' => now()->subWeek(), 'pinned_at' => now()]);
    $newerPinned = Note::factory()->create(['updated_at' => now()->subDay(), 'pinned_at' => now()->subYear()]);
    $old = Note::factory()->create(['updated_at' => now()->subMonth()]);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes.data.0.id', $newerPinned->id)
            ->where('notes.data.0.pinned', true)
            ->where('notes.data.1.id', $oldPinned->id)
            ->where('notes.data.2.id', $recent->id)
            ->where('notes.data.2.pinned', false)
            ->where('notes.data.3.id', $old->id));
});

it('cannot pin or unpin another user note', function (string $method, string $route) {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create();
    $this->actingAs($this->user);

    $this->{$method}(route($route, ['note' => $foreign]))->assertNotFound();
})->with([
    'pin' => ['post', 'notes.pin'],
    'unpin' => ['delete', 'notes.unpin'],
]);

it('ignores pinned_at sent through the note update', function () {
    $note = Note::factory()->create();

    $this->put(route('notes.update', ['note' => $note]), ['body' => 'x', 'pinned_at' => now()->toDateTimeString()]);

    expect($note->refresh()->pinned_at)->toBeNull();
});
