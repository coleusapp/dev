<?php

use Coleus\Notes\Markdown\Hashtags;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Tests\TestCase;

uses(TestCase::class);

it('finds wiki link titles', function (string $body, array $titles) {
    expect(WikiLinks::titles($body))->toBe($titles);
})->with([
    'one' => ['see [[Trip to Lisbon]]', ['Trip to Lisbon']],
    'several, de-duplicated case-insensitively' => ['[[A]] [[b]] [[a]]', ['A', 'b']],
    'whitespace collapsed' => ['[[  Trip   to  Lisbon ]]', ['Trip to Lisbon']],
    'in lists and headings' => ["# [[Head]]\n- [[Item]]", ['Head', 'Item']],
    'not in inline code' => ['`[[code]]` [[real]]', ['real']],
    'not in fenced code' => ["```\n[[code]]\n```", []],
    'empty brackets' => ['[[ ]] [[]]', []],
    'not across lines' => ["[[a\nb]]", []],
    'none' => ['plain [link](x)', []],
]);

it('renders a resolved link to the note', function () {
    $html = NoteMarkdown::toHtml('see [[Lisbon]]', new WikiLinkResolver(['lisbon' => 42]));

    expect($html)->toContain('<a href="/notes/42" class="wiki-link" data-note-id="42">Lisbon</a>');
});

it('resolves titles case-insensitively', function () {
    expect(NoteMarkdown::toHtml('[[LISBON]]', new WikiLinkResolver(['lisbon' => 7])))->toContain('data-note-id="7"');
});

it('renders a missing link as a create prompt', function () {
    $html = NoteMarkdown::toHtml('[[New idea]]', new WikiLinkResolver([]));

    expect($html)->toContain('class="wiki-link wiki-link-missing" data-wiki-title="New idea"')
        ->toContain('>New idea</a>');
});

it('escapes wiki link titles', function () {
    $html = NoteMarkdown::toHtml('[[<script>alert(1)</script>]] [[" onclick="x]]');

    expect($html)->not->toContain('<script')
        ->toContain('&lt;script&gt;')
        ->not->toContain('" onclick="x"');
});

it('does not turn wiki links into reference links', function () {
    $html = NoteMarkdown::toHtml("[[ref]]\n\n[ref]: https://example.test");

    expect($html)->toContain('data-wiki-title="ref"')->not->toContain('href="https://example.test"');
});

it('does not read hashtags inside wiki links', function () {
    expect(Hashtags::parse('[[#not a tag]] #real'))->toBe(['real']);
});

it('renames links to a title, keeping headings and aliases', function () {
    $body = "---\nsee: \"[[Old]]\"\n---\n# Notes\n[[old]] and [[Old  #Day one]] and [[ OLD |the old one]] and [[Older]]\n`[[Old]]` stays\n```\n[[Old]]\n```\n~~~~\n```\n[[Old]]\n~~~~\nafter [[Old]]";

    expect(WikiLinks::renameTitle($body, 'Old', 'New title'))->toBe(
        "---\nsee: \"[[Old]]\"\n---\n# Notes\n[[New title]] and [[New title#Day one]] and [[New title|the old one]] and [[Older]]\n`[[Old]]` stays\n```\n[[Old]]\n```\n~~~~\n```\n[[Old]]\n~~~~\nafter [[New title]]"
    );
});

it('keeps Windows line endings when renaming links', function () {
    expect(WikiLinks::renameTitle("[[Old]]\r\n[[Old]]", 'old', 'New'))->toBe("[[New]]\r\n[[New]]");
});
