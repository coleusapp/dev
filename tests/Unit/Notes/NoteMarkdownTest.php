<?php

use Coleus\Notes\Markdown\NoteMarkdown;

it('renders Markdown', function (string $markdown, string $html) {
    expect(NoteMarkdown::toHtml($markdown))->toContain($html);
})->with([
    'heading' => ['# Title', '<h1>Title</h1>'],
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
