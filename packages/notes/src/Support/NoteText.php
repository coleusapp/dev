<?php

namespace Coleus\Notes\Support;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Search\Highlighter;
use Illuminate\Support\Str;

/**
 * Plain-text views of a Markdown note body, used by the note list.
 */
class NoteText
{
    public const int SNIPPET_LENGTH = 140;

    public const int TITLE_LENGTH = 255;

    /**
     * Characters of context shown before a search match in a snippet.
     */
    public const int SEARCH_CONTEXT = 30;

    /**
     * Matching lines sent per note in search results.
     */
    public const int MATCH_LINES = 20;

    /**
     * The first non-empty line of the body as plain text, like Bear's title.
     */
    public static function title(string $body): string
    {
        $firstLine = collect(preg_split('/\R/', FrontMatter::split($body)[1]) ?: [])
            ->first(fn (string $line) => self::isTextLine($line), '');

        return Str::limit(self::plainLine($firstLine), self::TITLE_LENGTH - 3);
    }

    /**
     * The lines of the body after the first non-empty one (the title line),
     * stripped of Markdown syntax and flattened into one line.
     */
    public static function snippet(string $body, int $length = self::SNIPPET_LENGTH): string
    {
        $lines = collect(preg_split('/\R/', FrontMatter::split($body)[1]) ?: [])
            ->skipUntil(fn (string $line) => self::isTextLine($line))
            ->skip(1)
            ->filter(fn (string $line) => self::isTextLine($line))
            ->map(fn (string $line) => self::plainLine($line))
            ->filter(fn (string $line) => $line !== '');

        return Str::limit($lines->implode(' '), $length);
    }

    /**
     * Like snippet(), but when a search word appears in the body the excerpt
     * is moved to show it, with a little context before it.
     *
     * @param  list<string>  $words
     */
    public static function searchSnippet(string $body, array $words, int $length = self::SNIPPET_LENGTH): string
    {
        $text = self::snippet($body, PHP_INT_MAX);

        return self::clipAround($text, Highlighter::firstMatch($text, $words), $length);
    }

    /**
     * The body's lines (after the title line) that contain a search word, as
     * plain text clipped around the first match, with 1-based line numbers
     * that count front matter. At most $limit lines; $total counts them all.
     *
     * @param  list<string>  $words
     * @return array{lines: list<array{line: int, text: string}>, total: int}
     */
    public static function matchingLines(string $body, array $words, int $limit = self::MATCH_LINES): array
    {
        $lines = [];
        $total = 0;
        $pastTitle = false;

        foreach (preg_split('/\R/', FrontMatter::blankOut($body)) ?: [] as $index => $line) {
            if (! self::isTextLine($line)) {
                continue;
            }

            if (! $pastTitle) {
                $pastTitle = true;

                continue;
            }

            $text = self::plainLine($line);
            $offset = Highlighter::firstMatch($text, $words);

            if ($offset === null) {
                continue;
            }

            $total++;

            if (count($lines) < $limit) {
                $lines[] = ['line' => $index + 1, 'text' => self::clipAround($text, $offset)];
            }
        }

        return ['lines' => $lines, 'total' => $total];
    }

    /**
     * Text cut to $length, starting a little before the match (on a word
     * boundary) when the match would otherwise be far in.
     */
    public static function clipAround(string $text, ?int $offset, int $length = self::SNIPPET_LENGTH): string
    {
        if ($offset === null || $offset < self::SEARCH_CONTEXT) {
            return Str::limit($text, $length);
        }

        $start = $offset - self::SEARCH_CONTEXT;
        $wordStart = mb_strrpos(mb_substr($text, 0, $start + 1), ' ');
        $start = $wordStart === false ? $start : $wordStart + 1;

        return '...'.Str::limit(mb_substr($text, $start), $length);
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
        // [[Title|shown text]] reads as its shown text, [[Title#Heading]] as "Title > Heading".
        $line = (string) preg_replace_callback('/!?\[\[([^\[\]\n]+)\]\]/u', function (array $match): string {
            return WikiLinks::text(WikiLinks::split($match[1]));
        }, $line);

        $text = preg_replace([
            '/^\s{0,3}#{1,6}\s+/',
            '/^\s*>+\s?/',
            '/^\s*(?:[-*+]|\d+[.)])\s+(?:\[[ xX]\]\s+)?/',
            '/!?\[\[([^\]]+)\]\]/',
            '/!?\[([^\]]*)\]\([^)]*\)/',
            '/(?<!\\\\)(\*\*|__|~~|==|\+\+)(.+?)(?<!\\\\)\1/',
            '/(?<![\w*\\\\])([*_])(?!\s)(.+?)(?<![\s\\\\])\1(?![\w*])/',
            '/\{(?:pink|purple|orange|mint|blue)\}/',
            '/`([^`]*)`/',
        ], [
            '',
            '',
            '',
            '$1',
            '$1',
            '$2',
            '$2',
            '',
            '$1',
        ], $line) ?? $line;

        // Backslash escapes (\[draft\], snake\_case) show as the plain character, as in the rendered note.
        $text = (string) preg_replace('/\\\\([!-\/:-@\[-`{-~])/', '$1', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
