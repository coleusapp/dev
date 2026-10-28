<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Search\SearchQuery;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * LIKE-only here (RefreshDatabase's transaction hides rows from FULLTEXT),
 * so the plain-word part of each query uses short or punctuated words.
 *
 * @return list<int>
 */
function searchIds(string $q): array
{
    $ids = [];

    test()->get(route('notes.index', ['q' => $q]))->assertInertia(function (Assert $page) use (&$ids) {
        $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->sort()->values()->all();
    });

    return $ids;
}

it('parses operators, quoted values and phrases out of the words', function () {
    $query = SearchQuery::parse('ab tag:#Work/Meetings folder:"Trip plans" "Exact  Phrase" task:Call task-todo: task-done:"pay rent" cd "unclosed');

    expect($query->words)->toBe(['ab', 'cd', 'unclosed'])
        ->and($query->phrases)->toBe(['exact phrase'])
        ->and($query->tags)->toBe(['work/meetings'])
        ->and($query->folders)->toBe(['Trip plans'])
        ->and($query->tasks)->toBe([
            ['state' => 'any', 'text' => 'call'],
            ['state' => 'todo', 'text' => ''],
            ['state' => 'done', 'text' => 'pay rent'],
        ])
        ->and($query->highlightTerms())->toBe(['ab', 'cd', 'unclosed', 'exact phrase', 'call', 'pay rent']);
});

it('matches an exact phrase, not the words apart', function () {
    $together = Note::factory()->create(['body' => 'the quick brown fox']);
    Note::factory()->create(['body' => 'brown and quick']);

    expect(searchIds('"quick brown"'))->toBe([$together->id])
        ->and(searchIds('"QUICK   brown"'))->toBe([$together->id]);
});

it('filters by tag, including nested tags', function () {
    $work = Note::factory()->create(['body' => '#work/meetings standup']);
    Note::factory()->create(['body' => '#home standup']);

    expect(searchIds('tag:work'))->toBe([$work->id])
        ->and(searchIds('tag:#work/meetings'))->toBe([$work->id])
        ->and(searchIds('tag:wor'))->toBe([]);
});

it('filters by folder name, including sub-folders', function () {
    $trips = app(FolderService::class)->create('Trip plans', null);
    $lisbon = app(FolderService::class)->create('Lisbon', $trips);
    $inTrips = Note::factory()->create(['folder_id' => $trips->id]);
    $inLisbon = Note::factory()->create(['folder_id' => $lisbon->id]);
    Note::factory()->create();

    expect(searchIds('folder:"trip plans"'))->toBe([$inTrips->id, $inLisbon->id])
        ->and(searchIds('folder:Lisbon'))->toBe([$inLisbon->id])
        ->and(searchIds('folder:Nowhere'))->toBe([]);
});

it('matches checklist items by text and state, line by line', function () {
    $open = Note::factory()->create(['body' => "- [ ] call mom\n- [x] buy milk"]);
    $done = Note::factory()->create(['body' => "1. [X] Call the bank\nunrelated"]);
    $prose = Note::factory()->create(['body' => "- [ ] water plants\nremember to call later"]);

    expect(searchIds('task:call'))->toBe([$open->id, $done->id])
        ->and(searchIds('task-todo:call'))->toBe([$open->id])
        ->and(searchIds('task-done:call'))->toBe([$done->id])
        ->and(searchIds('task-done:milk'))->toBe([$open->id])
        ->and(searchIds('task-todo:'))->toBe([$open->id, $prose->id])
        ->and(searchIds('task-done:'))->toBe([$open->id, $done->id]);
});

it('ignores task-like lines in front matter', function () {
    Note::factory()->create(['body' => "---\nnote: \"- [ ] call\"\n---\nbody"]);

    expect(searchIds('task:call'))->toBe([]);
});

it('combines operators with words and highlights the words', function () {
    $folder = app(FolderService::class)->create('Work', null);
    $match = Note::factory()->create(['folder_id' => $folder->id, 'body' => "# Q3\n- [ ] ship v2.1 #release"]);
    Note::factory()->create(['body' => "# Q3\n- [ ] ship v2.1 #release"]);

    $this->get(route('notes.index', ['q' => 'v2.1 folder:work tag:release task-todo:ship']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $match->id)
            ->where('notes.data.0.highlights.snippet', fn ($parts) => collect($parts)->where('match', true)->pluck('text')->map(fn ($text) => strtolower($text))->contains('ship')));
});

it('does not let one user search another users folders', function () {
    $this->actingAs(User::factory()->create());
    $theirs = app(FolderService::class)->create('Shared name', null);
    Note::factory()->create(['folder_id' => $theirs->id]);
    $this->actingAs($this->user);

    expect(searchIds('folder:"shared name"'))->toBe([]);
});

it('finds notes that have checklists, tags, links or properties', function () {
    $checklist = Note::factory()->create(['body' => "# A\n- [x] done"]);
    $tagged = Note::factory()->create(['body' => '# B #inbox']);
    $wiki = Note::factory()->create(['body' => '# C see [[Other]]']);
    $markdown = Note::factory()->create(['body' => '# D [site](https://x.test)']);
    $properties = Note::factory()->create(['body' => "---\nstatus: draft\n---\n# E"]);
    Note::factory()->create(['body' => '# F plain - [ ] not a list item']);

    expect(searchIds('has:checklist'))->toBe([$checklist->id])
        ->and(searchIds('has:tags'))->toBe([$tagged->id])
        ->and(searchIds('has:links'))->toBe([$wiki->id, $markdown->id])
        ->and(searchIds('has:properties'))->toBe([$properties->id])
        ->and(searchIds('has:Tags has:links'))->toBe([])
        ->and(searchIds('has:drawings'))->toBe([])
        ->and(SearchQuery::parse('has:checklist has:tags')->tasks)->toBe([['state' => 'any', 'text' => '']]);
});

it('finds notes with attachments, linked or embedded', function () {
    $linked = Note::factory()->create(['body' => "# A\n![photo](/notes/attachments/0b7b9c1e-1f2a-4c3d-9e8f-123456789abc/photo.png)"]);
    $embedded = Note::factory()->create(['body' => "# B\n![[Clip.MP4|640]]"]);
    Note::factory()->create(['body' => "# C\n![[Other note]] and https://x.test/a.png"]);

    expect(searchIds('has:attachments'))->toBe([$linked->id, $embedded->id]);
});
