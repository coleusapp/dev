<?php

use Coleus\Notes\Search\Highlighter;
use Coleus\Notes\Support\NoteText;

it('splits text into plain and matching parts', function () {
    expect(Highlighter::parts('Trip to Lisbon in lisbon', ['lisbon']))->toBe([
        ['text' => 'Trip to ', 'match' => false],
        ['text' => 'Lisbon', 'match' => true],
        ['text' => ' in ', 'match' => false],
        ['text' => 'lisbon', 'match' => true],
    ]);
});

it('highlights several words, preferring the longest', function () {
    expect(Highlighter::parts('planet plan', ['plan', 'planet']))->toBe([
        ['text' => 'planet', 'match' => true],
        ['text' => ' ', 'match' => false],
        ['text' => 'plan', 'match' => true],
    ]);
});

it('treats regex characters in words literally', function () {
    expect(Highlighter::parts('a.b axb (c)', ['a.b', '(c)']))->toBe([
        ['text' => 'a.b', 'match' => true],
        ['text' => ' axb ', 'match' => false],
        ['text' => '(c)', 'match' => true],
    ]);
});

it('handles unicode case-insensitively', function () {
    expect(Highlighter::parts('ÉTÉ été', ['été']))->toBe([
        ['text' => 'ÉTÉ', 'match' => true],
        ['text' => ' ', 'match' => false],
        ['text' => 'été', 'match' => true],
    ]);
});

it('returns the text unmarked without words, and nothing for empty text', function () {
    expect(Highlighter::parts('abc', []))->toBe([['text' => 'abc', 'match' => false]])
        ->and(Highlighter::parts('', ['a']))->toBe([]);
});

it('keeps the normal snippet when the match is near the start', function () {
    expect(NoteText::searchSnippet("Title\nlisbon trip", ['lisbon']))->toBe('lisbon trip');
});

it('moves the snippet to a match further into the body', function () {
    $body = "Title\n".str_repeat('filler ', 40).'the lisbon flight';
    $snippet = NoteText::searchSnippet($body, ['lisbon']);

    expect($snippet)->toStartWith('...')
        ->toContain('lisbon flight')
        ->and(mb_strlen($snippet))->toBeLessThanOrEqual(NoteText::SNIPPET_LENGTH + 6);
});

it('falls back to the normal snippet when only the title matches', function () {
    expect(NoteText::searchSnippet("Lisbon\nflights and hotels", ['lisbon']))->toBe('flights and hotels');
});
