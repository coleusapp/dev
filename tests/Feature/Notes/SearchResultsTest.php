<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Notes\Support\NoteText;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Quoted terms go through LIKE: FULLTEXT can't see rows inside
 * RefreshDatabase's transaction (see tests/Unit/Notes/NoteSearchTest.php).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists the matching lines after the title, with line numbers counting front matter', function () {
    $found = NoteText::matchingLines("---\ntags: [x]\n---\n# Packing list x\n\n- [ ] **sunscreen** x\nnothing\n> pack x again", ['x']);

    expect($found)->toBe([
        'lines' => [
            ['line' => 6, 'text' => 'sunscreen x'],
            ['line' => 8, 'text' => 'pack x again'],
        ],
        'total' => 2,
    ]);
});

it('caps the lines sent but counts them all, clipping long lines around the match', function () {
    $body = "# Title\n".implode("\n", array_fill(0, NoteText::MATCH_LINES + 5, 'match here'))."\n".str_repeat('word ', 40).'needle at the end';
    $found = NoteText::matchingLines($body, ['match', 'needle']);

    expect($found['lines'])->toHaveCount(NoteText::MATCH_LINES)
        ->and($found['total'])->toBe(NoteText::MATCH_LINES + 6)
        ->and(NoteText::matchingLines($body, ['needle'])['lines'][0]['text'])->toStartWith('...')->toContain('needle at the end');
});

it('sends highlighted matching lines with search results only', function () {
    $note = Note::factory()->create(['body' => "# Trip\nbook the ferry\nnothing here\nferry times"]);

    $this->get(route('notes.index', ['q' => '"ferry"']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes.data.0.id', $note->id)
            ->where('notes.data.0.matches.total', 2)
            ->where('notes.data.0.matches.lines.0.line', 2)
            ->where('notes.data.0.matches.lines.0.parts', [['text' => 'book the ', 'match' => false], ['text' => 'ferry', 'match' => true]]));

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('notes.data.0.matches', null));
});

it('puts the best match first, or keeps the list order when asked', function () {
    $once = Note::factory()->create(['body' => "# Groceries\nkiwi", 'updated_at' => now()]);
    $often = Note::factory()->create(['body' => "# Fruit\nkiwi kiwi kiwi", 'updated_at' => now()->subDay()]);
    $title = Note::factory()->create(['body' => "# Kiwi\nnothing", 'updated_at' => now()->subWeek()]);

    $order = function (): array {
        $ids = [];
        $this->get(route('notes.index', ['q' => '"kiwi"']))->assertInertia(function (Assert $page) use (&$ids) {
            $ids = collect($page->toArray()['props']['notes']['data'])->pluck('id')->all();
        });

        return $ids;
    };

    expect($order())->toBe([$title->id, $often->id, $once->id]);

    NotePreferences::save(['search.sort' => 'list']);

    expect($order())->toBe([$once->id, $often->id, $title->id]);
});

it('saves the search order preference', function () {
    $this->from(route('notes.index'))->put(route('notes.settings.update'), ['search_sort' => 'list'])->assertRedirect();

    expect(NotePreferences::get('search.sort'))->toBe('list');

    $this->from(route('notes.index'))->put(route('notes.settings.update'), ['search_sort' => 'best'])->assertSessionHasErrors('search_sort');
});
