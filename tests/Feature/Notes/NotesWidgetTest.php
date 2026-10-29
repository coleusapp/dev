<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Widgets\NotesWidget;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('gives a dashboard the pinned and recently opened notes and where to start one', function () {
    $pinned = Note::factory()->create(['body' => '# Pinned', 'pinned_at' => now()]);
    $opened = Note::factory()->create(['body' => '# Opened', 'opened_at' => now()]);
    $both = Note::factory()->create(['body' => '# Both', 'pinned_at' => now()->subMinute(), 'opened_at' => now()->subMinute()]);
    Note::factory()->create(['body' => '# Never opened']);

    $data = NotesWidget::getData();

    expect(array_column($data['pinned'], 'title'))->toBe(['Pinned', 'Both'])
        ->and(array_column($data['recent'], 'title'))->toBe(['Opened'])
        ->and($data['pinned'][0]['url'])->toBe(route('notes.show', ['note' => $pinned->id]))
        ->and($data['new_note_url'])->toBe(route('notes.quick.create'))
        ->and($opened->id)->not->toBe($both->id);
});

it('shows only the users own notes', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '# Theirs', 'pinned_at' => now(), 'opened_at' => now()]);
    $this->actingAs($this->user);

    expect(NotesWidget::getData())->toMatchArray(['pinned' => [], 'recent' => []]);
});
