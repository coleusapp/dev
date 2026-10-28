<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Tests\TestCase;

uses(TestCase::class);

it('marks up inline math, keeping the TeX as written', function () {
    expect(NoteMarkdown::toHtml('Area is $\pi r^2$ and $a_1 * b_2$.'))
        ->toBe("<p>Area is <span class=\"math math-inline\" data-tex=\"\\pi r^2\">\$\\pi r^2\$</span> and <span class=\"math math-inline\" data-tex=\"a_1 * b_2\">\$a_1 * b_2\$</span>.</p>\n");
});

it('leaves prices, spaced dollars and code alone', function (string $markdown) {
    expect(NoteMarkdown::toHtml($markdown))->not->toContain('class="math');
})->with([
    'prices' => ['costs $5 and $10 today'],
    'space inside' => ['$ x $'],
    'code' => ['`$x$`'],
    'escaped' => ['\$x\$'],
]);

it('marks up math blocks, multi-line and one-line', function () {
    expect(NoteMarkdown::toHtml("before\n\n\$\$\n\\int_0^1 x\\,dx\n= \\frac12\n\$\$\n\nafter"))
        ->toContain("<div class=\"math math-block\" data-tex=\"\\int_0^1 x\\,dx\n= \\frac12\">")
        ->toContain('<p>after</p>')
        ->and(NoteMarkdown::toHtml('$$E=mc^2$$'))->toContain('<div class="math math-block" data-tex="E=mc^2">')
        ->and(NoteMarkdown::toHtml('$$<script>$$'))->not->toContain('<script>');
});
