<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('bookmarks a note without touching updated_at, and keeps the first bookmark time', function () {
    $note = Note::factory()->create(['updated_at' => now()->subWeek()]);
    $updatedAt = $note->refresh()->updated_at;

    $this->from(route('notes.show', $note))->post(route('notes.bookmark', $note))->assertRedirect(route('notes.show', $note));
    $bookmarkedAt = $note->refresh()->bookmarked_at;
    $this->travel(1)->minute();
    $this->post(route('notes.bookmark', $note));

    expect($bookmarkedAt)->not->toBeNull()
        ->and($note->refresh()->bookmarked_at->equalTo($bookmarkedAt))->toBeTrue()
        ->and($note->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('removes a bookmark', function () {
    $note = Note::factory()->bookmarked()->create();

    $this->delete(route('notes.unbookmark', $note))->assertRedirect();

    expect($note->refresh()->bookmarked_at)->toBeNull();
});

it('lists bookmarked notes, archived ones included, and marks them in every list', function () {
    $bookmarked = Note::factory()->bookmarked()->create(['updated_at' => now()->subDay()]);
    $archived = Note::factory()->bookmarked()->archived()->create();
    Note::factory()->create();

    $this->get(route('notes.index', ['view' => 'bookmarks']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes.data', fn ($notes) => collect($notes)->pluck('id')->all() === [$archived->id, $bookmarked->id])
            ->where('filters.view', 'bookmarks'));

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('notes.data', fn ($notes) => collect($notes)->firstWhere('id', $bookmarked->id)['bookmarked'] === true));
});

it('keeps bookmarks private', function (string $method, string $route) {
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create();
    $this->actingAs($this->user);

    $this->{$method}(route($route, $theirs))->assertNotFound();
})->with([
    'bookmark' => ['post', 'notes.bookmark'],
    'remove' => ['delete', 'notes.unbookmark'],
]);
