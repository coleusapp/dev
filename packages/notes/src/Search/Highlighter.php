<?php

namespace Coleus\Notes\Search;

/**
 * Splits text into plain and matching parts for highlighting search words.
 *
 * Returned as data, not HTML, so the page renders it with text
 * interpolation and nothing from a note is ever injected as markup.
 */
class Highlighter
{
    /**
     * @param  list<string>  $words
     * @return list<array{text: string, match: bool}>
     */
    public static function parts(string $text, array $words): array
    {
        $pattern = self::pattern($words);

        if ($pattern === null || $text === '') {
            return $text === '' ? [] : [['text' => $text, 'match' => false]];
        }

        $pieces = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$text];
        $parts = [];

        foreach ($pieces as $piece) {
            $parts[] = ['text' => $piece, 'match' => preg_match($pattern, $piece) === 1 && self::isWholeMatch($pattern, $piece)];
        }

        return $parts;
    }

    /**
     * Character offset of the first match, or null.
     *
     * @param  list<string>  $words
     */
    public static function firstMatch(string $text, array $words): ?int
    {
        $pattern = self::pattern($words);

        if ($pattern === null || preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return mb_strlen(substr($text, 0, $match[0][1]));
    }

    /**
     * @param  list<string>  $words
     */
    private static function pattern(array $words): ?string
    {
        $words = array_filter($words, fn (string $word) => $word !== '');

        if ($words === []) {
            return null;
        }

        usort($words, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return '/('.implode('|', array_map(fn (string $word) => preg_quote($word, '/'), $words)).')/iu';
    }

    private static function isWholeMatch(string $pattern, string $piece): bool
    {
        return preg_replace($pattern, '', $piece) === '';
    }
}
