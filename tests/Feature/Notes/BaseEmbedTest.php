<?php

use Coleus\Notes\Markdown\Bases\BaseEmbedRenderer;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Models\Base;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('embeds a base as a table with a link to the base', function () {
    $note = Note::factory()->create(['body' => "---\nauthor: Le Guin\nread: true\ngenres: [fantasy, sea]\nfinished: 2026-11-01\n---\n# Earthsea <b>"]);
    $base = Base::query()->create(['name' => 'Books', 'definition' => ['columns' => ['author', 'read', 'genres', 'finished', 'formula.bad'], 'formulas' => [['name' => 'bad', 'expression' => 'author * 2']]]]);

    $html = NoteMarkdown::toHtml("Intro\n\n![[books.base]]\n\nAfter");

    expect($html)->toContain('<div class="base-embed" data-base-id="'.$base->id.'"><div class="base-embed-title"><a href="/notes/bases/'.$base->id.'">Books</a><span class="base-embed-count">1 note</span></div>')
        ->and($html)->toContain('<thead><tr><th scope="col">Title</th><th scope="col">author</th><th scope="col">read</th><th scope="col">genres</th><th scope="col">finished</th><th scope="col">bad</th></tr></thead>')
        ->and($html)->toContain('<th scope="row"><a href="/notes/'.$note->id.'" class="wiki-link" data-note-id="'.$note->id.'">Earthsea &lt;b&gt;</a></th>')
        ->and($html)->toContain('<td>Le Guin</td><td><span aria-label="Yes">✓</span></td><td><span class="base-embed-chip">fantasy</span> <span class="base-embed-chip">sea</span></td><td>Nov 1, 2026</td>')
        ->and($html)->toContain('<td><span class="base-embed-error" title="* needs a number, not &quot;Le Guin&quot;.">Error</span></td>')
        ->and($html)->toStartWith("<p>Intro</p>\n")
        ->and($html)->toEndWith("<p>After</p>\n");
});

it('shows cards and groups, and the view can be overridden in the embed', function () {
    Note::factory()->create(['body' => "---\ngenre: sea\n---\n# Earthsea\nA wizard's tale"]);
    Note::factory()->create(['body' => '# Untyped']);
    Base::query()->create(['name' => 'Books', 'definition' => ['view' => 'table', 'group_by' => 'genre', 'columns' => ['genre']]]);

    $cards = NoteMarkdown::toHtml('![[Books.base|cards]]');
    $table = NoteMarkdown::toHtml('![[Books.base]]');

    expect($cards)->toContain('<h4 class="base-embed-group"><span class="base-embed-group-by">genre:</span> sea <span class="base-embed-count">1</span></h4>')
        ->and($cards)->toContain("<li class=\"base-embed-card\"><p class=\"base-embed-snippet\">A wizard's tale</p><div class=\"base-embed-card-title\">")
        ->and($cards)->toContain('<dl><div><dt>genre</dt><dd>sea</dd></div></dl>')
        ->and($cards)->toContain('<span class="base-embed-group-by">genre:</span> None')
        ->and($table)->toContain('<tr class="base-embed-group"><th scope="rowgroup" colspan="2"><span class="base-embed-group-by">genre:</span> sea <span class="base-embed-count">1</span></th></tr>')
        ->and($table)->not->toContain('base-embed-card');
});

it('says when the base is missing or matches nothing, and caps the rows', function () {
    Base::query()->create(['name' => 'Empty', 'definition' => ['query' => 'tag:nothing']]);
    Note::factory()->count(BaseEmbedRenderer::EMBED_ROWS + 2)->create();
    $all = Base::query()->create(['name' => 'All', 'definition' => []]);

    expect(NoteMarkdown::toHtml('![[Nope.base]]'))->toBe("<p class=\"base-embed-missing\">No base named “Nope”.</p>\n")
        ->and(NoteMarkdown::toHtml('![[Empty.base]]'))->toContain('<p class="base-embed-missing">No notes match.</p>')
        ->and(NoteMarkdown::toHtml('![[All.base]]'))->toContain('<p class="base-embed-more"><a href="/notes/bases/'.$all->id.'">Showing 50 of 52. Open the base</a></p>')
        ->and(substr_count(NoteMarkdown::toHtml('![[All.base]]'), '<th scope="row">'))->toBe(BaseEmbedRenderer::EMBED_ROWS);
});

it('only embeds a line of its own, and leaves other embeds alone', function () {
    Base::query()->create(['name' => 'Books', 'definition' => []]);
    Note::factory()->create();
    expect(NoteMarkdown::toHtml('see ![[Books.base]] here'))->not->toContain('base-embed')
        ->and(NoteMarkdown::toHtml('    ![[Books.base]]'))->toContain('<pre><code>')
        ->and(NoteMarkdown::toHtml('![[Plan]]'))->not->toContain('base-embed')
        ->and(NoteMarkdown::toHtml('![[Books.base|kanban]]'))->toContain('base-embed-table');
});

it("never shows another user's base", function () {
    Base::query()->create(['name' => 'Books', 'definition' => []]);
    $this->actingAs(User::factory()->create());

    expect(NoteMarkdown::toHtml('![[Books.base]]'))->toContain('No base named “Books”.');
});

it('writes dates and values as text', function () {
    expect(BaseEmbedRenderer::text('2026-11-01T09:05'))->toBe('Nov 1, 2026, 9:05 AM')
        ->and(BaseEmbedRenderer::text('2026-02-30'))->toBe('2026-02-30')
        ->and(BaseEmbedRenderer::text(2.0))->toBe('2')
        ->and(BaseEmbedRenderer::text(['a', '2026-01-02']))->toBe('a, Jan 2, 2026');
});

it('renders in the note page beside note embeds', function () {
    $this->withoutVite();
    $plan = Note::factory()->create(['body' => "# Plan\nsecret plan"]);
    $base = Base::query()->create(['name' => 'Books', 'definition' => []]);
    $note = Note::factory()->create(['body' => "# Trip\n\n![[Plan]]\n\n![[Books.base]]"]);

    $this->get(route('notes.show', $note))->assertInertia(fn (Assert $page) => $page
        ->where('note.data.html', fn (string $html) => str_contains($html, 'class="note-embed" data-embed-id="'.$plan->id.'"')
            && str_contains($html, '<div class="base-embed" data-base-id="'.$base->id.'">')));
});
