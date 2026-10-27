<?php

use Coleus\Notes\Markdown\Hashtags;

it('parses hashtags from prose', function (string $body, array $tags) {
    expect(Hashtags::parse($body))->toBe($tags);
})->with([
    'single' => ['Plan #work', ['work']],
    'start of note' => ["#inbox\nbody", ['inbox']],
    'nested' => ['see #work/meetings/weekly', ['work/meetings/weekly']],
    'several, de-duplicated in order' => ['#b #a #b', ['b', 'a']],
    'lowercased' => ['#Work and #WORK', ['work']],
    'unicode' => ['#café #日記', ['café', '日記']],
    'trailing punctuation' => ['Done #todo. Next #later, then #now!', ['todo', 'later', 'now']],
    'trailing slash' => ['#work/', ['work']],
    'hyphen and underscore' => ['#to-do #my_tag', ['to-do', 'my_tag']],
    'inside parentheses' => ['(#idea)', ['idea']],
    'in a heading' => ['## Plans #q3', ['q3']],
    'in a list and checklist' => ["- item #a\n- [ ] task #b", ['a', 'b']],
    'in a blockquote' => ['> quote #c', ['c']],
    'in a table cell' => ["| a |\n|---|\n| #cell |", ['cell']],
    'inside emphasis' => ['**#bold** _#it_', ['bold', 'it']],
    'none' => ['no tags here', []],
    'empty' => ['', []],
]);

it('ignores things that only look like hashtags', function (string $body) {
    expect(Hashtags::parse($body))->toBe([]);
})->with([
    'heading marker' => ['# Heading'],
    'heading level two' => ['## Heading'],
    'numbers only' => ['Fixes #123'],
    'mid word' => ['C#sharp and a#b'],
    'double hash' => ['##notatag'],
    'html entity' => ['&#38; and &#x26;'],
    'inline code' => ['use `#define` here'],
    'fenced code' => ["```\n#include <stdio.h>\n```"],
    'tilde fence' => ["~~~\n#comment\n~~~"],
    'indented code' => ["para\n\n    #comment"],
    'bare url fragment' => ['https://example.test/page#section'],
    'url path' => ['https://example.test/#/route'],
    'www autolink' => ['www.example.test/#frag'],
    'angle autolink' => ['<https://example.test/#x>'],
    'markdown link text and url' => ['[#label](https://example.test/#x)'],
    'image' => ['![#alt](pic.png#frag)'],
    'escaped hash' => ['\\#nottag'],
    'colon before' => ['a:#b'],
]);

it('lists ancestors outermost first', function () {
    expect(Hashtags::ancestors('a/b/c'))->toBe(['a', 'a/b'])
        ->and(Hashtags::ancestors('a'))->toBe([]);
});

it('treats only a single backslash as escaping the hash', function () {
    expect(Hashtags::parse('\\#escaped'))->toBe([])
        ->and(Hashtags::parse('\\\\ #tag'))->toBe(['tag'])
        ->and(Hashtags::parse('\\\\#tag'))->toBe(['tag']);
});
