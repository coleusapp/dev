<?php

namespace Coleus\Notes\Support;

use Illuminate\Support\Str;

/**
 * Plain-text views of a Markdown note body, used by the note list.
 */
class NoteText
{
    public const int SNIPPET_LENGTH = 140;

    /**
     * The lines of the body after the first non-empty one (the title line),
     * stripped of Markdown syntax and flattened into one line.
     */
    public static function snippet(string $body, int $length = self::SNIPPET_LENGTH): string
    {
        $lines = collect(preg_split('/\R/', $body) ?: [])
            ->skipUntil(fn (string $line) => trim($line) !== '')
            ->skip(1)
            ->reject(fn (string $line) => preg_match('/^\s*(```|~~~)/', $line) === 1)
            ->map(fn (string $line) => self::plainLine($line))
            ->filter(fn (string $line) => $line !== '');

        return Str::limit($lines->implode(' '), $length);
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
