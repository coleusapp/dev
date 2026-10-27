<?php

use Coleus\Notes\Support\NoteText;

it('builds a snippet from the lines after the title', function (string $body, string $snippet) {
    expect(NoteText::snippet($body))->toBe($snippet);
})->with([
    'plain' => ["Title\nFirst line\nSecond line", 'First line Second line'],
    'leading blank lines' => ["\n\n# Title\n\nBody", 'Body'],
    'title only' => ['# Just a title', ''],
    'empty' => ['', ''],
    'headings and quotes' => ["T\n## Sub\n> quoted", 'Sub quoted'],
    'lists and checklists' => ["T\n- one\n* [ ] two\n- [x] three\n1. four", 'one two three four'],
    'inline formatting' => ["T\n**bold** _it_ *em* ~~gone~~ ==mark== `code`", 'bold it em gone mark code'],
    'links' => ["T\n[site](https://x.test) and [[Other note]] ![img](a.png)", 'site and Other note img'],
    'code fences dropped' => ["T\n```php\necho 1;\n```", 'echo 1;'],
    'windows newlines' => ["T\r\nline", 'line'],
    'snake_case kept' => ["T\nsome_snake_case word", 'some_snake_case word'],
]);

it('limits the snippet length', function () {
    $snippet = NoteText::snippet("Title\n".str_repeat('word ', 100));

    expect(mb_strlen($snippet))->toBeLessThanOrEqual(NoteText::SNIPPET_LENGTH + 3)
        ->and($snippet)->toEndWith('...');
});
