<?php

namespace Coleus\Notes\Markdown;

/**
 * YAML front matter: a "---" block at the very top of a note holding its
 * properties (Obsidian style). Kept out of the rendered note, the title
 * and the snippet.
 *
 * The parser reads the subset properties use: "key: value" scalars
 * (strings, quoted strings, numbers, booleans, null, dates as strings) and
 * lists, either inline "[a, b]" or as "- item" lines. Anything else
 * (nested maps, multi-line strings) makes parse() return null, and the
 * block is kept as typed.
 */
class FrontMatter
{
    public const string PATTERN = '/\A---\r?\n(.*?)\r?\n(?:---|\.\.\.)[ \t]*(?:\r?\n|\z)/s';

    /**
     * [front matter block including its fences, rest of the note].
     *
     * @return array{0: string, 1: string}
     */
    public static function split(string $body): array
    {
        if (preg_match(self::PATTERN, $body, $match) !== 1) {
            return ['', $body];
        }

        return [$match[0], substr($body, strlen($match[0]))];
    }

    /**
     * The body with every front-matter line replaced by an empty line, so
     * the Markdown parser skips it while line numbers (used by the
     * checklist toggle) stay the same.
     */
    public static function blankOut(string $body): string
    {
        [$block, $rest] = self::split($body);

        return $block === '' ? $body : str_repeat("\n", substr_count($block, "\n")).$rest;
    }

    /**
     * The properties in the front matter, or [] when there is none, or
     * null when it uses YAML this parser doesn't read.
     *
     * @return array<string, mixed>|null
     */
    public static function parse(string $body): ?array
    {
        if (preg_match(self::PATTERN, $body, $match) !== 1) {
            return [];
        }

        $properties = [];
        $listKey = null;

        foreach (preg_split('/\r?\n/', $match[1]) ?: [] as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            if ($listKey !== null && preg_match('/^\s+-\s*(.*)$/', $line, $item) === 1) {
                $properties[$listKey][] = self::scalar($item[1]);

                continue;
            }

            if (preg_match('/^([A-Za-z0-9_][A-Za-z0-9 _.-]*?)\s*:(?:\s+(.*))?$/', $line, $pair) !== 1) {
                return null;
            }

            $key = $pair[1];
            $value = trim($pair[2] ?? '');
            $listKey = null;

            if ($value === '') {
                $properties[$key] = [];
                $listKey = $key;
            } elseif (preg_match('/^\[(.*)\]$/', $value, $inline) === 1) {
                $properties[$key] = trim($inline[1]) === '' ? [] : array_map(self::scalar(...), self::splitInline($inline[1]));
            } elseif (preg_match('/^[|>{&*!]/', $value) === 1) {
                return null;
            } else {
                $properties[$key] = self::scalar($value);
            }
        }

        foreach ($properties as $key => $value) {
            if ($value === []) {
                $properties[$key] = null;
            }
        }

        return $properties;
    }

    private static function scalar(string $value): string|int|float|bool|null
    {
        $value = trim($value);

        if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"(?:\s+#.*)?$/s', $value, $quoted) === 1) {
            return stripcslashes($quoted[1]);
        }
        if (preg_match("/^'((?:[^']|'')*)'(?:\\s+#.*)?$/s", $value, $quoted) === 1) {
            return str_replace("''", "'", $quoted[1]);
        }

        // An unquoted value ends at a " #" comment.
        $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);

        return match (true) {
            $value === '' || $value === '~' || strcasecmp($value, 'null') === 0 => null,
            strcasecmp($value, 'true') === 0 => true,
            strcasecmp($value, 'false') === 0 => false,
            preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            preg_match('/^-?\d*\.\d+$/', $value) === 1 => (float) $value,
            default => $value,
        };
    }

    /**
     * Splits "a, 'b, c', d" on commas outside quotes.
     *
     * @return list<string>
     */
    private static function splitInline(string $list): array
    {
        preg_match_all('/\s*("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[^,]*)\s*(?:,|$)/', $list, $matches);

        return array_values(array_filter($matches[1], fn (string $item) => trim($item) !== ''));
    }
}
