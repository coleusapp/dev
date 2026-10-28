<?php

use Coleus\Notes\Markdown\NoteMarkdown;

it('renders Markdown', function (string $markdown, string $html) {
    expect(NoteMarkdown::toHtml($markdown))->toContain($html);
})->with([
    'heading' => ['# Title', '<h1 id="heading-title">Title'],
    'emphasis' => ['**bold** _it_', '<strong>bold</strong> <em>it</em>'],
    'strikethrough' => ['~~gone~~', '<del>gone</del>'],
    'highlight' => ['==mark==', '<mark>mark</mark>'],
    'safe link' => ['[x](https://example.test)', '<a href="https://example.test">x</a>'],
    'table' => ["| a |\n|---|\n| b |", '<td>b</td>'],
    'fenced code' => ["```\n<b>x</b>\n```", '&lt;b&gt;x&lt;/b&gt;'],
]);

it('escapes raw HTML instead of passing it through', function (string $markdown) {
    $html = NoteMarkdown::toHtml($markdown);

    expect($html)->not->toContain('<script')
        ->not->toContain('<img src=x onerror')
        ->not->toContain('<iframe')
        ->not->toContain('<svg');
})->with([
    'script block' => ['<script>alert(1)</script>'],
    'inline script' => ['hello <script>alert(1)</script> world'],
    'img onerror' => ['<img src=x onerror="alert(1)">'],
    'iframe' => ['<iframe src="https://evil.test"></iframe>'],
    'svg onload' => ['<svg onload="alert(1)"></svg>'],
]);

it('shows escaped script tags as text', function () {
    expect(NoteMarkdown::toHtml('<script>alert(1)</script>'))->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('drops unsafe link targets', function (string $markdown) {
    $html = strtolower(NoteMarkdown::toHtml($markdown));

    expect($html)->not->toContain('href="javascript:')
        ->not->toContain('href="vbscript:')
        ->not->toContain('href="data:text/html')
        ->not->toContain('src="javascript:');
})->with([
    'javascript link' => ['[click](javascript:alert(1))'],
    'uppercase javascript' => ['[click](JAVASCRIPT:alert(1))'],
    'entity encoded javascript' => ['[click](&#106;avascript:alert(1))'],
    'vbscript link' => ['[click](vbscript:msgbox(1))'],
    'data html link' => ['[click](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)'],
    'javascript autolink' => ['<javascript:alert(1)>'],
    'javascript image' => ['![x](javascript:alert(1))'],
    'reference link' => ["[click][x]\n\n[x]: javascript:alert(1)"],
]);

it('renders ++underline++', function (string $markdown, string $html) {
    expect(NoteMarkdown::toHtml($markdown))->toContain($html);
})->with([
    'underline' => ['++under++ line', '<u>under</u> line'],
    'with emphasis inside' => ['++**both**++', '<u><strong>both</strong></u>'],
]);

it('leaves other plus signs alone', function (string $markdown) {
    expect(NoteMarkdown::toHtml($markdown))->not->toContain('<u>');
})->with(['1+1=2', 'C++ and C++', '+++ triple +++', '++ spaced ++']);

it('colours highlights', function (string $markdown, string $html) {
    expect(NoteMarkdown::toHtml($markdown))->toContain($html);
})->with([
    'colour' => ['==hot=={pink} topic', '<mark class="mark-pink">hot</mark> topic'],
    'colour at end' => ['==done=={mint}', '<mark class="mark-mint">done</mark></p>'],
    'no colour' => ['==plain== text', '<mark>plain</mark> text'],
    'unknown colour kept as text' => ['==x=={red}', '<mark>x</mark>{red}'],
    'braces without highlight' => ['just {pink} text', 'just {pink} text'],
]);

it('gives headings unique, prefixed ids with an anchor link', function () {
    $html = NoteMarkdown::toHtml("## Day one\n\ntext\n\n## Day one");

    expect($html)->toContain('<h2 id="heading-day-one">Day one<a href="#heading-day-one" class="heading-anchor"')
        ->toContain('<h2 id="heading-day-one-1">Day one<a href="#heading-day-one-1" class="heading-anchor"');
});

it('puts a table of contents where [TOC] is, and nowhere without it', function () {
    $html = NoteMarkdown::toHtml("# Trip\n\n[TOC]\n\n## Day one\n\n### Food\n\n## Day two");

    expect($html)->toContain('<ul class="table-of-contents">')
        ->toContain('<a href="#heading-day-one">Day one</a>')
        ->toContain('<a href="#heading-food">Food</a>')
        ->and(strpos($html, 'table-of-contents'))->toBeLessThan(strpos($html, '<h2'))
        ->and(NoteMarkdown::toHtml("# Trip\n\n## Day one"))->not->toContain('table-of-contents')
        ->and(NoteMarkdown::toHtml('see [TOC] here'))->toContain('<p>see [TOC] here</p>');
});

it('keeps checklist parsing working next to headings and a table of contents', function () {
    $html = NoteMarkdown::toHtml("# List\n\n[TOC]\n\n- [ ] one\n- [x] two");

    expect($html)->toContain('data-task-line="5"')->toContain('data-task-line="6"');
});

it('renders footnotes, linked both ways', function () {
    $html = NoteMarkdown::toHtml("Lisbon is hilly[^hills] and old[^1].\n\n[^hills]: Seven of them.\n[^1]: Very.");

    expect($html)->toContain('<sup id="fnref:hills"><a class="footnote-ref" href="#fn:hills" role="doc-noteref">1</a></sup>')
        ->toContain('<li class="footnote" id="fn:hills" role="doc-endnote"><p>Seven of them.&nbsp;<a class="footnote-backref" rev="footnote" href="#fnref:hills" role="doc-backlink">↩</a></p></li>')
        ->and(NoteMarkdown::toHtml('no definition[^x]'))->toContain('[^x]');
});

it('allows a few plain formatting tags and escapes everything else', function (string $markdown, string $html) {
    expect(NoteMarkdown::toHtml($markdown))->toContain($html);
})->with([
    'kbd' => ['Press <kbd>Cmd</kbd>+<kbd>K</kbd>', 'Press <kbd>Cmd</kbd>+<kbd>K</kbd>'],
    'upper case stays text' => ['a <KBD>K</KBD>', 'a &lt;KBD&gt;K&lt;/KBD&gt;'],
    'sup and sub' => ['x<sup>2</sup> H<sub>2</sub>O', 'x<sup>2</sup> H<sub>2</sub>O'],
    'line break' => ['a<br/>b', 'a<br>b'],
    'details block' => ["<details open>\n<summary>More</summary>\n\nHidden **text**\n\n</details>", "<details open>\n<summary>More</summary>"],
    'attributes are not allowed' => ['<kbd class="x">K</kbd>', '&lt;kbd class=&quot;x&quot;&gt;K</kbd>'],
    'other tags stay text' => ['<span style="color:red">x</span>', '&lt;span style=&quot;color:red&quot;&gt;x&lt;/span&gt;'],
    'code keeps tags as text' => ['`<kbd>K</kbd>`', '<code>&lt;kbd&gt;K&lt;/kbd&gt;</code>'],
    'event handlers on allowed tags' => ['<details ontoggle="alert(1)">x</details>', '&lt;details ontoggle=&quot;alert(1)&quot;&gt;'],
]);
