<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Support\NoteText;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\RawMarkupContainerInterface;
use League\CommonMark\Node\StringContainerInterface;

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
     * Points every [[link]] to the note $from at $to instead, keeping any
     * #heading and |alias exactly as written; titles compare like the
     * resolver (any case, runs of spaces). Front matter, fenced code and
     * `code spans` are left alone.
     */
    public static function renameTitle(string $body, string $from, string $to): string
    {
        $key = mb_strtolower(self::normalizeTitle($from));
        [$frontMatter, $text] = FrontMatter::split($body);
        $fence = null;

        $lines = preg_split('/(\R)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];

        foreach ($lines as $index => $line) {
            if ($index % 2 === 1) {
                continue;
            }

            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $marker) === 1) {
                if ($fence === null) {
                    $fence = $marker[1];
                } elseif ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence)) {
                    $fence = null;
                }

                continue;
            }

            if ($fence !== null) {
                continue;
            }

            $lines[$index] = (string) preg_replace_callback('/(`+).*?\1|'.self::PATTERN_BODY.'/u', function (array $match) use ($key, $to): string {
                if (($match[1] ?? '') !== '' || ! isset($match[2])) {
                    return $match[0];
                }

                $inner = $match[2];
                $cut = strcspn($inner, '#|');

                return mb_strtolower(self::normalizeTitle(substr($inner, 0, $cut))) === $key
                    ? '[['.$to.substr($inner, $cut).']]'
                    : $match[0];
            }, $line);
        }

        return $frontMatter.implode('', $lines);
    }

    /**
     * The part of a body under a heading (found like heading links: exact,
     * then any case and spacing), up to the next heading of the same or a
     * higher level, heading line included; null when there's no such heading.
     */
    public static function section(string $body, string $heading): ?string
    {
        $lines = preg_split('/\R/', FrontMatter::split($body)[1]) ?: [];
        $loose = fn (string $text): string => mb_strtolower(self::normalizeTitle($text));
        $headings = [];
        $fence = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $marker) === 1) {
                $fence = $fence === null ? $marker[1] : ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence) ? null : $fence);
            } elseif ($fence === null && preg_match('/^ {0,3}(#{1,6})\s+(.+?)\s*#*\s*$/u', $line, $match) === 1) {
                $headings[] = ['index' => $index, 'level' => strlen($match[1]), 'text' => NoteText::plainLine($line)];
            }
        }

        $found = collect($headings)->firstWhere('text', self::normalizeTitle($heading))
            ?? collect($headings)->first(fn (array $each) => $loose($each['text']) === $loose($heading));

        if ($found === null) {
            return null;
        }

        $end = collect($headings)->first(fn (array $each) => $each['index'] > $found['index'] && $each['level'] <= $found['level'])['index'] ?? count($lines);

        return implode("\n", array_slice($lines, $found['index'], $end - $found['index']));
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
     * A note's headings as their rendered text (what [[Note#Heading]] and
     * the heading scroll match), in order, without repeats.
     *
     * @return list<string>
     */
    public static function headings(string $body): array
    {
        $headings = [];

        foreach (NoteMarkdown::parse($body)->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading) {
                $text = self::normalizeTitle(self::plainText($node));

                if ($text !== '') {
                    $headings[$text] = true;
                }
            }
        }

        return array_keys($headings);
    }

    /**
     * A node's text as the reader sees it: literal text and code, wiki links
     * by their shown text, raw HTML left out (it renders escaped, not as text
     * worth matching).
     */
    private static function plainText(Node $node): string
    {
        $text = '';

        foreach ($node->children() as $child) {
            $text .= match (true) {
                $child instanceof WikiLink => self::text(['title' => $child->title, 'heading' => $child->heading, 'label' => $child->label]),
                $child instanceof RawMarkupContainerInterface => '',
                $child instanceof StringContainerInterface => $child->getLiteral(),
                default => self::plainText($child),
            };
        }

        return $text;
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
            if (($node instanceof WikiLink || $node instanceof NoteEmbed) && $node->title !== '') {
                $titles[mb_strtolower($node->title)] ??= $node->title;
            }
        }

        return array_values($titles);
    }
}
