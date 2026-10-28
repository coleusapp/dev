<?php

namespace Coleus\Notes\Markdown;

use Coleus\Notes\Markdown\WikiLinks\WikiLink;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

/**
 * Extracts Bear-style hashtags (#tag, #parent/child) from a note body.
 *
 * Only prose counts: the Markdown is parsed and text is collected from
 * paragraphs, headings and table cells, skipping code spans, code blocks,
 * links (including autolinked URLs), [[wiki links]] and images. Inside that text a tag must
 * start a word, so "#" in "a#b", "/#anchor", "&#38;" or "##" doesn't count,
 * and it needs at least one letter, so "#1" (issue numbers) isn't a tag.
 */
class Hashtags
{
    private const string PATTERN = '/(?<![\p{L}\p{N}_&\/#:.\-])#([\p{L}\p{N}_\-]+(?:\/[\p{L}\p{N}_\-]+)*)/u';

    public const int MAX_LENGTH = 191;

    /**
     * @return list<string> unique, lowercased full tag paths, in order of first appearance
     */
    public static function parse(string $body): array
    {
        if (! str_contains($body, '#')) {
            return [];
        }

        $tags = [];

        foreach (NoteMarkdown::parse(self::hideEscapedHashes($body))->iterator() as $node) {
            if (! $node instanceof AbstractBlock || ! self::holdsInlines($node)) {
                continue;
            }

            preg_match_all(self::PATTERN, self::prose($node), $matches);

            foreach ($matches[1] as $match) {
                $tag = self::normalize($match);

                if ($tag !== null) {
                    $tags[$tag] = true;
                }
            }
        }

        return array_keys($tags);
    }

    /**
     * Tags from the "tags" (or "tag") property, a list or a comma/space
     * separated string, normalized like hashtags ("#Work" → "work").
     *
     * @return list<string>
     */
    public static function fromFrontMatter(string $body): array
    {
        $properties = FrontMatter::parse($body) ?? [];
        $value = $properties['tags'] ?? $properties['tag'] ?? null;
        $raw = is_array($value) ? $value : (is_string($value) ? preg_split('/[\s,]+/', $value) : []);

        return collect($raw ?: [])
            ->map(fn (mixed $tag) => self::normalize(ltrim((string) $tag, '#')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every ancestor path of a tag, outermost first: "a/b/c" gives ["a", "a/b"].
     *
     * @return list<string>
     */
    public static function ancestors(string $tag): array
    {
        $segments = explode('/', $tag);
        $ancestors = [];

        for ($i = 1; $i < count($segments); $i++) {
            $ancestors[] = implode('/', array_slice($segments, 0, $i));
        }

        return $ancestors;
    }

    /**
     * "\#word" is an escaped, literal hash. The parser drops the backslash,
     * so it's swapped for a private-use character first to keep it from
     * reading as a tag. An escaped backslash ("\\#tag") still allows a tag.
     */
    private static function hideEscapedHashes(string $body): string
    {
        return (string) preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)\\\\#/', '$1'."\u{E000}", $body);
    }

    private static function normalize(string $match): ?string
    {
        $segments = array_values(array_filter(
            array_map(fn (string $segment) => trim($segment, '-_'), explode('/', mb_strtolower($match))),
            fn (string $segment) => $segment !== '',
        ));
        $tag = implode('/', $segments);

        if ($tag === '' || preg_match('/\p{L}/u', $tag) !== 1 || mb_strlen($tag) > self::MAX_LENGTH) {
            return null;
        }

        return $tag;
    }

    private static function holdsInlines(AbstractBlock $block): bool
    {
        return $block->firstChild() instanceof AbstractInline;
    }

    /**
     * The block's readable text, with code, links and images replaced by a space.
     */
    private static function prose(Node $node): string
    {
        $text = '';

        foreach ($node->children() as $child) {
            $text .= match (true) {
                $child instanceof Text => $child->getLiteral(),
                $child instanceof Newline => "\n",
                $child instanceof Code, $child instanceof Link, $child instanceof Image, $child instanceof HtmlInline, $child instanceof WikiLink => ' ',
                default => self::prose($child),
            };
        }

        return $text;
    }
}
