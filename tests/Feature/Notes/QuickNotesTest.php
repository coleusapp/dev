<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('saves a quick note in the Quick Notes folder', function () {
    $this->postJson(route('notes.quick.store'), ['body' => "Call the plumber\n"])
        ->assertCreated()->assertJsonPath('data.title', 'Call the plumber');

    expect(Note::query()->sole()->folder->name)->toBe('Quick Notes');

    $this->postJson(route('notes.quick.store'), ['body' => 'Second']);
    expect(Note::query()->count())->toBe(2)->and(Note::query()->pluck('folder_id')->unique())->toHaveCount(1);
});

it('links back to the Coleus page it was started from, only on this site', function () {
    $this->postJson(route('notes.quick.store'), ['body' => 'Agenda', 'from' => '/calendar/events/3', 'from_title' => 'Team [sync]']);
    expect(Note::query()->latest('id')->first()->body)->toBe("Agenda\n\nFrom [Team (sync)](/calendar/events/3)\n");

    foreach (['https://evil.test/x', '//evil.test', 'javascript:alert(1)', '/\\evil.test'] as $from) {
        $this->postJson(route('notes.quick.store'), ['body' => 'x', 'from' => $from]);
        expect(Note::query()->latest('id')->first()->body)->toBe('x');
    }
});

it('opens Notes with the composer from a link anywhere in Coleus', function () {
    $this->get(route('notes.quick.create', ['from' => '/health', 'title' => 'Health']))
        ->assertRedirect(route('notes.index', ['quick' => 1, 'from' => '/health', 'from_title' => 'Health']));

    $this->get(route('notes.quick.create', ['from' => 'https://evil.test', 'title' => 'x']))
        ->assertRedirect(route('notes.index', ['quick' => 1]));
});
