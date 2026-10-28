<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Tests\TestCase;

uses(TestCase::class);

it('renders a callout with its type, family and title', function () {
    expect(NoteMarkdown::toHtml("> [!Warning] Mind the **gap**\n> Body *text*\n> more"))
        ->toBe("<div class=\"callout callout-warning\" data-callout=\"warning\"><div class=\"callout-title\">Mind the gap</div><div class=\"callout-body\"><p>Body <em>text</em>\nmore</p></div></div>\n");
});

it('titles a callout after its type, and maps aliases to families', function () {
    expect(NoteMarkdown::toHtml("> [!tldr]\n> short"))->toContain('class="callout callout-abstract" data-callout="tldr"><div class="callout-title">Tldr</div>')
        ->and(NoteMarkdown::toHtml('> [!custom-thing] x'))->toContain('callout callout-note" data-callout="custom-thing"')
        ->and(NoteMarkdown::toHtml('> [!note]'))->toBe("<div class=\"callout callout-note\" data-callout=\"note\"><div class=\"callout-title\">Note</div></div>\n");
});

it('folds with - (closed) and + (open)', function () {
    expect(NoteMarkdown::toHtml("> [!tip]- Read me\n> hidden"))->toStartWith('<details class="callout callout-tip" data-callout="tip"><summary class="callout-title">Read me</summary>')
        ->and(NoteMarkdown::toHtml("> [!tip]+\n> shown"))->toStartWith('<details class="callout callout-tip" data-callout="tip" open=""><summary class="callout-title">Tip</summary>');
});

it('leaves ordinary quotes and mid-line markers alone, and keeps checklists working inside', function () {
    expect(NoteMarkdown::toHtml('> just a quote [!note]'))->toBe("<blockquote>\n<p>just a quote [!note]</p>\n</blockquote>\n")
        ->and(NoteMarkdown::toHtml("# T\n\n> [!todo] Today\n> - [ ] milk"))->toContain('data-task-line="4"')
        ->and(NoteMarkdown::toHtml('> [!note] <script>x</script>'))->not->toContain('<script>');
});
