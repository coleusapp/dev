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
     * The parts of what's between the brackets:
     * - "Title|shown text": the shown text is after the first "|" (none, or
     *   nothing after it, shows the default text);
     * - "Title#Heading": a heading in that note, "#Heading" one in the same
     *   note (so a "#" can't be part of a linked title, as in Obsidian).
     *
     * @return array{title: string, heading: string|null, label: string|null}
     */
    public static function split(string $inner): array
    {
        [$target, $label] = array_pad(explode('|', $inner, 2), 2, null);
        [$title, $heading] = array_pad(explode('#', (string) $target, 2), 2, null);
        $heading = $heading === null ? '' : self::normalizeTitle($heading);
        $label = $label === null ? '' : trim($label);

        return [
            'title' => self::normalizeTitle((string) $title),
            'heading' => $heading === '' ? null : $heading,
            'label' => $label === '' ? null : $label,
        ];
    }

    /**
     * The text a link shows when it has no alias: "Title", "Title > Heading",
     * or "Heading" within the same note.
     *
     * @param  array{title: string, heading: string|null, label: string|null}  $link
     */
    public static function text(array $link): string
    {
        if ($link['label'] !== null) {
            return $link['label'];
        }

        if ($link['heading'] === null) {
            return $link['title'];
        }

        return $link['title'] === '' ? $link['heading'] : $link['title'].' > '.$link['heading'];
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
            if ($node instanceof WikiLink && $node->title !== '') {
                $titles[mb_strtolower($node->title)] ??= $node->title;
            }
        }

        return array_values($titles);
    }
}
