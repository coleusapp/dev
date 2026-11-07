<?php

use Coleus\Notes\Support\NoteText;

it('builds a snippet from the blocks after the title', function (string $body, string $snippet) {
    expect(NoteText::snippet($body))->toBe($snippet);
})->with([
    'paragraphs' => ['<p>Title</p><p>First line</p><p>Second line</p>', 'First line Second line'],
    'leading empty paragraphs' => ['<p></p><p></p><h1>Title</h1><p>Body</p>', 'Body'],
    'title only' => ['<h1>Just a title</h1>', ''],
    'empty' => ['', ''],
    'editor empty note' => ['<p></p>', ''],
    'headings and quotes' => ['<p>T</p><h2>Sub</h2><blockquote><p>quoted</p></blockquote>', 'Sub quoted'],
    'lists and checklists' => [
        '<p>T</p><ul><li><p>one</p></li></ul><ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><input type="checkbox" checked><span></span></label><div><p>two</p></div></li></ul><ol><li><p>three</p></li></ol>',
        'one two three',
    ],
    'inline formatting' => ['<p>T</p><p><strong>bold</strong> <em>em</em> <s>gone</s> <code>code</code></p>', 'bold em gone code'],
    'links' => ['<p>T</p><p><a href="https://x.test">site</a> and more</p>', 'site and more'],
    'code block' => ["<p>T</p><pre><code>echo 1;\necho 2;</code></pre>", 'echo 1; echo 2;'],
    'entities decoded' => ['<p>T</p><p>pack bags &amp; go&nbsp;</p>', 'pack bags & go'],
    'heading levels 4 to 6' => ['<h4>T</h4><h5>five</h5><h6>six</h6>', 'five six'],
]);

it('limits the snippet length', function () {
    $snippet = NoteText::snippet('<p>Title</p><p>'.str_repeat('word ', 100).'</p>');

    expect(mb_strlen($snippet))->toBeLessThanOrEqual(NoteText::SnippetLength + 3)
        ->and($snippet)->toEndWith('...');
});

it('derives the title from the first block with text', function (string $body, string $title) {
    expect(NoteText::title($body))->toBe($title);
})->with([
    'heading' => ['<h1>Groceries</h1><p>milk</p>', 'Groceries'],
    'paragraph' => ['<p>Just text</p>', 'Just text'],
    'leading empty paragraphs' => ['<p></p><p>&nbsp;</p><h2>Plan</h2><p>body</p>', 'Plan'],
    'formatting stripped' => ['<p><strong>Bold</strong> and <a href="https://x.test">link</a></p>', 'Bold and link'],
    'checklist item' => ['<ul data-type="taskList"><li data-type="taskItem" data-checked="false"><label><input type="checkbox"><span></span></label><div><p>buy milk</p></div></li></ul>', 'buy milk'],
    'hashtag kept' => ['<p>#work/meetings</p>', '#work/meetings'],
    'code block' => ['<pre><code>code</code></pre>', 'code'],
    'heading 6' => ['<h6>Small</h6>', 'Small'],
    'empty' => ['', ''],
    'whitespace only' => ["  \n\t", ''],
]);

it('limits the title to the column length', function () {
    $title = NoteText::title('<p>'.str_repeat('a', 1000).'</p>');

    expect(mb_strlen($title))->toBe(NoteText::TitleLength)
        ->and($title)->toEndWith('...');
});
