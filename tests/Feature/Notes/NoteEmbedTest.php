<?php

use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function embedHtml(Note $note): string
{
    $html = '';
    test()->get(route('notes.show', $note))->assertInertia(function (Assert $page) use (&$html) {
        $html = $page->toArray()['props']['note']['data']['html'];
    });

    return $html;
}

it('shows an embedded note in place, under a link to it', function () {
    $target = Note::factory()->create(['body' => "# Packing\n- [ ] **sunscreen**"]);
    $note = Note::factory()->create(['body' => "# Trip\n\n![[packing]]\n\nafter"]);

    $html = embedHtml($note);

    expect($html)->toContain('<div class="note-embed" data-embed-id="'.$target->id.'"><div class="note-embed-title"><a href="/notes/'.$target->id.'" class="wiki-link" data-note-id="'.$target->id.'">packing</a></div>')
        ->toContain('<strong>sunscreen</strong>')
        ->toContain('disabled=""')
        ->not->toContain('data-task-line="2"')
        ->toContain('<p>after</p>');
});

it('embeds just one section for ![[Note#Heading]]', function () {
    Note::factory()->create(['body' => "# Trip\n## Day one\nmuseum\n### Lunch\nfish\n## Day two\nbeach"]);
    $note = Note::factory()->create(['body' => "# Plan\n![[Trip#day ONE]]\n![[Trip#Nope]]"]);

    $html = embedHtml($note);

    expect($html)->toContain('museum')->toContain('fish')->not->toContain('beach')
        ->toContain('No heading “Nope” in this note.');
});

it('keeps an embed inside a sentence a link, and a missing note a create prompt', function () {
    $note = Note::factory()->create(['body' => "# Plan\nsee ![[Elsewhere]] here\n\n![[Missing note]]"]);

    $html = embedHtml($note);

    expect($html)->not->toContain('note-embed"')
        ->toContain('data-wiki-title="Missing note"');
});

it('stops loops and deep chains', function () {
    $a = Note::factory()->create(['body' => "# Alpha\nfrom a\n\n![[Beta]]"]);
    Note::factory()->create(['body' => "# Beta\nfrom b\n\n![[Alpha]]"]);

    $html = embedHtml($a);

    expect(substr_count($html, 'from b'))->toBe(1)
        ->and(substr_count($html, 'from a'))->toBe(1)
        ->and($html)->toContain('note-embed-skipped');
});

it('counts an embed as a link and finds sections like heading links', function () {
    $target = Note::factory()->create(['body' => '# Packing']);
    $source = Note::factory()->create(['body' => "# Trip\n![[Packing]]"]);

    $this->get(route('notes.show', $target))->assertInertia(fn (Assert $page) => $page->where('note.data.backlinks.0.id', $source->id));

    expect(WikiLinks::section("---\nx: 1\n---\n# A\n```\n# not\n```\ntext\n# B", 'a'))->toBe("# A\n```\n# not\n```\ntext");
});

it('does not embed another users note', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => "# Secret\nhidden text"]);
    $this->actingAs($this->user);
    $note = Note::factory()->create(['body' => "# Mine\n![[Secret]]"]);

    expect(embedHtml($note))->not->toContain('hidden text');
});
