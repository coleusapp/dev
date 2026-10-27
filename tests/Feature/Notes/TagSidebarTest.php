<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('builds a tag tree with note counts that include nested tags', function () {
    Note::factory()->create(['body' => '#work/meetings #work/meetings/weekly']);
    Note::factory()->create(['body' => '#work']);
    Note::factory()->create(['body' => '#home']);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tags', [
                ['name' => 'home', 'path' => 'home', 'count' => 1, 'children' => []],
                ['name' => 'work', 'path' => 'work', 'count' => 2, 'children' => [
                    ['name' => 'meetings', 'path' => 'work/meetings', 'count' => 1, 'children' => [
                        ['name' => 'weekly', 'path' => 'work/meetings/weekly', 'count' => 1, 'children' => []],
                    ]],
                ]],
            ]));
});

it('shows a parent that is only used as a prefix', function () {
    Note::factory()->create(['body' => '#a/b']);

    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tags.0.path', 'a')
            ->where('tags.0.count', 1)
            ->where('tags.0.children.0.path', 'a/b'));
});

it('drops tags from the tree once no note uses them', function () {
    $note = Note::factory()->create(['body' => '#temp']);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => 'untagged']);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('tags', []));
});

it('does not show other users tags', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '#private']);
    $this->actingAs($this->user);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('tags', []));
});

it('filters the list by a tag and its nested tags', function () {
    $parent = Note::factory()->create(['body' => '#work']);
    $child = Note::factory()->create(['body' => '#work/meetings']);
    Note::factory()->create(['body' => '#workout']);
    Note::factory()->create(['body' => 'untagged']);

    $this->get(route('notes.index', ['tag' => 'work']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.tag', 'work')
            ->has('notes.data', 2)
            ->where('notes.data', fn ($notes) => collect($notes)->pluck('id')->sort()->values()->all() === collect([$parent->id, $child->id])->sort()->values()->all()));
});

it('filters a nested tag without matching its parent', function () {
    Note::factory()->create(['body' => '#work']);
    $child = Note::factory()->create(['body' => '#work/meetings']);

    $this->get(route('notes.index', ['tag' => 'work/meetings']))
        ->assertInertia(fn (Assert $page) => $page->has('notes.data', 1)->where('notes.data.0.id', $child->id));
});

it('treats underscores and percents in the filter literally', function () {
    Note::factory()->create(['body' => '#a_b/c']);
    Note::factory()->create(['body' => '#axb/c']);

    $this->get(route('notes.index', ['tag' => 'a_b']))
        ->assertInertia(fn (Assert $page) => $page->has('notes.data', 1));

    $this->get(route('notes.index', ['tag' => '%']))
        ->assertInertia(fn (Assert $page) => $page->has('notes.data', 0));
});

it('normalizes the filter', function () {
    $note = Note::factory()->create(['body' => '#work']);

    $this->get(route('notes.index', ['tag' => '#Work/']))
        ->assertInertia(fn (Assert $page) => $page->where('filters.tag', 'work')->where('notes.data.0.id', $note->id));
});

it('keeps the filter on the note page', function () {
    $note = Note::factory()->create(['body' => '#a']);
    Note::factory()->create(['body' => '#b']);

    $this->get(route('notes.show', ['note' => $note, 'tag' => 'a']))
        ->assertInertia(fn (Assert $page) => $page->where('filters.tag', 'a')->has('notes.data', 1)->where('note.data.id', $note->id));
});

it('has no filter by default', function () {
    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('filters.tag', null));
});

it('rejects an overlong tag filter', function () {
    $this->get(route('notes.index', ['tag' => str_repeat('a', 200)]))->assertSessionHasErrors('tag');
});

it('keeps the filter when creating and deleting from a filtered list', function () {
    $this->post(route('notes.store', ['tag' => 'work']), ['body' => "\n\n#work"])
        ->assertRedirect(route('notes.show', ['note' => Note::query()->sole(), 'tag' => 'work']));

    $this->delete(route('notes.destroy', ['note' => Note::query()->sole(), 'tag' => 'work']))
        ->assertRedirect(route('notes.index', ['tag' => 'work']));
});
