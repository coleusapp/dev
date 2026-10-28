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
     * The target and the shown text of what's between the brackets:
     * "Title|shown text" splits at the first "|"; no "|" (or nothing after
     * it) means the title is shown.
     *
     * @return array{title: string, label: string|null}
     */
    public static function split(string $inner): array
    {
        [$title, $label] = array_pad(explode('|', $inner, 2), 2, null);
        $label = $label === null ? '' : trim($label);

        return ['title' => self::normalizeTitle($title), 'label' => $label === '' ? null : $label];
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
