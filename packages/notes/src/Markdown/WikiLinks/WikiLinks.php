<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\NoteMarkdown;

/**
 * [[Note title]] syntax shared by the parser, the resolver and backlinks.
 */
class WikiLinks
{
    /**
     * One line, no brackets inside: "[[Trip to Lisbon]]".
     */
    public const string PATTERN_BODY = '\[\[([^\[\]\n]{1,255})\]\]';

    public static function normalizeTitle(string $title): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $title));
    }

    /**
     * The distinct titles a body links to, as the Markdown parser sees them
     * (so [[...]] inside code doesn't count), in order of appearance.
     *
     * @return list<string>
     */
    public static function titles(string $body): array
    {
        if (! str_contains($body, '[[')) {
            return [];
        }

        $titles = [];

        foreach (NoteMarkdown::parse($body)->iterator() as $node) {
            if ($node instanceof WikiLink) {
                $titles[mb_strtolower($node->title)] ??= $node->title;
            }
        }

        return array_values($titles);
    }
}
