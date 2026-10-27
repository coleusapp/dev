<?php

use Coleus\Notes\Markdown\Checklist;
use Coleus\Notes\Markdown\NoteMarkdown;

it('finds the lines of real task items', function (string $body, array $lines) {
    expect(Checklist::taskLines($body))->toBe($lines);
})->with([
    'simple' => ["# T\n- [ ] a\n- [x] b\n- c", [2, 3]],
    'other markers' => ["* [ ] a\n+ [X] b\n1. [ ] c\n2) [ ] d", [1, 2, 3, 4]],
    'nested' => ["- [ ] a\n  - [ ] b", [1, 2]],
    'blockquote' => ['> - [ ] quoted', [1]],
    'inside fenced code' => ["```\n- [ ] not a task\n```\n- [ ] task", [4]],
    'inside indented code' => ["para\n\n    - [ ] code", []],
    'not at item start' => ['- text [ ] later', []],
    'none' => ['plain', []],
]);

it('checks and unchecks a task line', function () {
    $body = "# Groceries\n- [ ] milk\n- [x] eggs";

    expect(Checklist::toggle($body, 2, true))->toBe("# Groceries\n- [x] milk\n- [x] eggs")
        ->and(Checklist::toggle($body, 3, false))->toBe("# Groceries\n- [ ] milk\n- [ ] eggs");
});

it('is idempotent', function () {
    expect(Checklist::toggle('- [x] done', 1, true))->toBe('- [x] done');
});

it('only touches the marker on the requested line', function () {
    expect(Checklist::toggle("- [ ] keep [ ] this\n- [ ] other", 1, true))->toBe("- [x] keep [ ] this\n- [ ] other");
});

it('preserves windows line endings', function () {
    expect(Checklist::toggle("T\r\n- [ ] a\r\n", 2, true))->toBe("T\r\n- [x] a\r\n");
});

it('toggles items inside blockquotes and ordered lists', function () {
    expect(Checklist::toggle('> 1. [ ] a', 1, true))->toBe('> 1. [x] a');
});

it('refuses lines that are not task items', function (string $body, int $line) {
    expect(Checklist::toggle($body, $line, true))->toBeNull();
})->with([
    'plain line' => ["- [ ] a\nplain", 2],
    'inside code' => ["```\n- [ ] a\n```", 2],
    'out of range' => ['- [ ] a', 9],
    'zero' => ['- [ ] a', 0],
]);

it('renders interactive checkboxes tagged with their line', function () {
    $html = NoteMarkdown::toHtml("# T\n- [ ] a\n- [x] b");

    expect($html)->toContain('<input type="checkbox" data-task-line="2">')
        ->toContain('<input type="checkbox" checked="" data-task-line="3">')
        ->not->toContain('disabled');
});

it('moves a checklist item before another one', function (string $body, int $line, ?int $before, string $expected) {
    expect(Checklist::move($body, $line, $before))->toBe($expected);
})->with([
    'down' => ["- [ ] a\n- [ ] b\n- [ ] c", 1, 3, "- [ ] b\n- [ ] a\n- [ ] c"],
    'up' => ["- [ ] a\n- [ ] b\n- [ ] c", 3, 1, "- [ ] c\n- [ ] a\n- [ ] b"],
    'to the end' => ["- [ ] a\n- [ ] b\n- [ ] c", 1, null, "- [ ] b\n- [ ] c\n- [ ] a"],
    'with sub-items' => ["- [ ] a\n  - [ ] a1\n- [ ] b", 1, null, "- [ ] b\n- [ ] a\n  - [ ] a1"],
    'keeps other text' => ["# T\n- [ ] a\n- [ ] b\n\nend", 3, 2, "# T\n- [ ] b\n- [ ] a\n\nend"],
    'loose list' => ["- [ ] a\n\n- [ ] b", 1, null, "\n- [ ] b\n- [ ] a"],
    'windows newlines' => ["- [ ] a\r\n- [ ] b", 2, 1, "- [ ] b\r\n- [ ] a"],
    'onto itself' => ["- [ ] a\n- [ ] b", 1, 1, "- [ ] a\n- [ ] b"],
    'just before the next one' => ["- [ ] a\n- [ ] b", 1, 2, "- [ ] a\n- [ ] b"],
]);

it('refuses moves between lists or for lines that are not items', function (string $body, int $line, ?int $before) {
    expect(Checklist::move($body, $line, $before))->toBeNull();
})->with([
    'other list' => ["- [ ] a\n\ntext\n\n- [ ] b", 1, 5],
    'nested into parent list' => ["- [ ] a\n  - [ ] a1\n- [ ] b", 2, 3],
    'not an item' => ["text\n- [ ] a", 1, 2],
    'target not an item' => ["- [ ] a\ntext", 1, 2],
    'inside code' => ["```\n- [ ] a\n```\n- [ ] b", 2, 4],
]);
