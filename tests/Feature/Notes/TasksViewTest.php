<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function taskGroups($test, array $query = []): array
{
    $groups = [];
    $test->get(route('notes.tasks.index', $query))->assertInertia(function (Assert $page) use (&$groups) {
        $groups = $page->toArray()['props']['groups'];
    });

    return $groups;
}

it('lists open checklist items across notes, with their lines', function () {
    $trip = Note::factory()->create(['body' => "# Trip #travel\n- [ ] **book** flights\n  - [x] check passport\n- [ ] pack\n```\n- [ ] not a task\n```"]);
    Note::factory()->create(['body' => "# Done list\n- [x] all done"]);
    Note::factory()->create(['body' => "# Old\n- [ ] gone"])->delete();

    expect(taskGroups($this))->toBe([
        ['note' => ['id' => $trip->id, 'title' => 'Trip #travel', 'archived' => false], 'items' => [
            ['line' => 2, 'checked' => false, 'text' => 'book flights', 'depth' => 0],
            ['line' => 4, 'checked' => false, 'text' => 'pack', 'depth' => 0],
        ]],
    ]);
});

it('shows done or all items, and filters by tag and folder', function () {
    $work = app(FolderService::class)->create('Work', null);
    $inWork = Note::factory()->create(['body' => "# Q4 #work\n- [ ] ship\n- [x] plan"]);
    app(FolderService::class)->moveNote($inWork, $work);
    $home = Note::factory()->create(['body' => "# Home #home\n- [ ] paint"]);

    expect(collect(taskGroups($this, ['state' => 'done']))->pluck('items.*.text')->all())->toBe([['plan']])
        ->and(collect(taskGroups($this, ['state' => 'all', 'folder' => $work->id]))->pluck('items.*.text')->all())->toBe([['ship', 'plan']])
        ->and(collect(taskGroups($this, ['tag' => 'home']))->pluck('note.id')->all())->toBe([$home->id]);

    $this->get(route('notes.tasks.index', ['state' => 'nope']))->assertSessionHasErrors('state');
});

it('leaves out locked notes and other users notes', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => "# Theirs\n- [ ] theirs"]);
    $this->actingAs($this->user);
    Note::factory()->create(['body' => "# Secret\n- [ ] hidden"])->forceFill(['lock_key_id' => 1, 'body' => ''])->save();

    expect(taskGroups($this))->toBe([]);
});
