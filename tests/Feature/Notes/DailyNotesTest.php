<?php

use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('makes the day’s note once, in the Daily notes folder, then opens it again', function () {
    $this->post(route('notes.daily.store'), ['date' => '2026-10-28', 'body' => "# 2026-10-28\n- [ ] "])->assertRedirect();

    $note = Note::query()->sole();
    expect($note->daily_on->toDateString())->toBe('2026-10-28')
        ->and($note->title)->toBe('2026-10-28')
        ->and($note->folder->name)->toBe('Daily notes');

    $note->update(['body' => '# Renamed day']);

    $this->post(route('notes.daily.store'), ['date' => '2026-10-28', 'body' => 'ignored'])->assertRedirect(route('notes.show', $note));
    expect(Note::query()->count())->toBe(1)->and(Folder::query()->count())->toBe(1);
});

it('reuses an existing Daily notes folder and brings a trashed day back', function () {
    $this->post(route('notes.folders.store'), ['name' => 'daily NOTES']);
    $this->post(route('notes.daily.store'), ['date' => '2026-10-27', 'body' => '# Yesterday']);
    $yesterday = Note::query()->sole();
    $yesterday->delete();

    $this->post(route('notes.daily.store'), ['date' => '2026-10-27', 'body' => 'new'])->assertRedirect(route('notes.show', $yesterday));

    expect($yesterday->fresh()->trashed())->toBeFalse()->and(Folder::query()->count())->toBe(1);
});

it('validates the date, and keeps users apart', function () {
    $this->post(route('notes.daily.store'), ['date' => '28/10/2026', 'body' => ''])->assertSessionHasErrors('date');

    $this->actingAs(User::factory()->create());
    $this->post(route('notes.daily.store'), ['date' => '2026-10-28', 'body' => '# Theirs']);
    $this->actingAs($this->user);
    $this->post(route('notes.daily.store'), ['date' => '2026-10-28', 'body' => '# Mine']);

    expect(Note::query()->sole()->title)->toBe('Mine');
});

it('knows the daily notes before and after a day, skipping gaps and the trash', function () {
    foreach (['2026-10-20', '2026-10-25', '2026-10-28', '2026-11-02'] as $date) {
        $this->post(route('notes.daily.store'), ['date' => $date, 'body' => "# {$date}"]);
    }
    $ids = Note::query()->orderBy('daily_on')->pluck('id', 'title');
    Note::query()->find($ids['2026-11-02'])->delete();
    $plain = Note::factory()->create(['body' => '# Plain']);

    $this->get(route('notes.show', $ids['2026-10-25']))->assertInertia(fn ($page) => $page
        ->where('note.data.daily', ['date' => '2026-10-25', 'previous' => $ids['2026-10-20'], 'next' => $ids['2026-10-28']]));
    $this->get(route('notes.show', $ids['2026-10-28']))->assertInertia(fn ($page) => $page->where('note.data.daily.next', null));
    $this->get(route('notes.show', $plain))->assertInertia(fn ($page) => $page->where('note.data.daily', null));
});
