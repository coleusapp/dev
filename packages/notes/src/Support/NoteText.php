<?php

namespace Coleus\Notes\Support;

use Illuminate\Support\Str;

/**
 * Plain-text views of a Markdown note body, used by the note list.
 */
class NoteText
{
    public const int SNIPPET_LENGTH = 140;

    public const int TITLE_LENGTH = 255;

    /**
     * The first non-empty line of the body as plain text, like Bear's title.
     */
    public static function title(string $body): string
    {
        $firstLine = collect(preg_split('/\R/', $body) ?: [])
            ->first(fn (string $line) => self::isTextLine($line), '');

        return Str::limit(self::plainLine($firstLine), self::TITLE_LENGTH - 3);
    }

    /**
     * The lines of the body after the first non-empty one (the title line),
     * stripped of Markdown syntax and flattened into one line.
     */
    public static function snippet(string $body, int $length = self::SNIPPET_LENGTH): string
    {
        $lines = collect(preg_split('/\R/', $body) ?: [])
            ->skipUntil(fn (string $line) => self::isTextLine($line))
            ->skip(1)
            ->filter(fn (string $line) => self::isTextLine($line))
            ->map(fn (string $line) => self::plainLine($line))
            ->filter(fn (string $line) => $line !== '');

        return Str::limit($lines->implode(' '), $length);
    }

    /**
     * Whether a line carries readable text, as opposed to blank lines and code fence markers.
     */
    private static function isTextLine(string $line): bool
    {
        return trim($line) !== '' && preg_match('/^\s*(```|~~~)/', $line) !== 1;
    }

    /**
     * One line of Markdown reduced to the text a reader sees.
     */
    public static function plainLine(string $line): string
    {
        $text = preg_replace([
            '/^\s{0,3}#{1,6}\s+/',
            '/^\s*>+\s?/',
            '/^\s*(?:[-*+]|\d+[.)])\s+(?:\[[ xX]\]\s+)?/',
            '/!?\[\[([^\]]+)\]\]/',
            '/!?\[([^\]]*)\]\([^)]*\)/',
            '/(\*\*|__|~~|==)(.+?)\1/',
            '/(?<![\w*])([*_])(?!\s)(.+?)(?<!\s)\1(?![\w*])/',
            '/`([^`]*)`/',
        ], [
            '',
            '',
            '',
            '$1',
            '$1',
            '$2',
            '$2',
            '$1',
        ], $line) ?? $line;

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
