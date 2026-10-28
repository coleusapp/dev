<?php

namespace Coleus\Notes\Markdown;

use Coleus\Notes\Markdown\Underline\UnderlineExtension;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkExtension;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Highlight\HighlightExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Renders a note body to HTML on the server.
 *
 * Raw HTML in a note is escaped, never passed through, and javascript:,
 * vbscript:, file: and data: (non-image) links are dropped, so a note can't
 * inject script into the page that previews it.
 *
 * Built the same way as Str::markdown (GitHub-flavoured converter plus extra
 * extensions), but kept here so parsing for checklists and rendering share
 * one environment.
 *
 * Headings get ids ("heading-" + slug, unique per note) and an empty anchor
 * the stylesheet shows as "#" on hover; a "[TOC]" line becomes a table of
 * contents linking to them. The prefix keeps a heading like "Notes 2" from
 * clashing with ids on the page around the note.
 */
class NoteMarkdown
{
    /**
     * @var array<string, mixed>
     */
    public const array OPTIONS = [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 50,
        'heading_permalink' => [
            'apply_id_to_heading' => true,
            'id_prefix' => 'heading',
            'fragment_prefix' => 'heading',
            'insert' => 'after',
            'html_class' => 'heading-anchor',
            'symbol' => '',
            'title' => 'Link to this heading',
        ],
        'table_of_contents' => [
            'position' => 'placeholder',
            'placeholder' => '[TOC]',
            'html_class' => 'table-of-contents',
        ],
    ];

    /**
     * Without a resolver every [[wiki link]] renders as a link to a note that
     * doesn't exist yet; pass WikiLinkResolver::forBody() to link real notes.
     */
    public static function toHtml(string $body, ?WikiLinkResolver $wikiLinks = null): string
    {
        return (string) self::converter($wikiLinks)->convert(FrontMatter::blankOut($body));
    }

    public static function parse(string $body): Document
    {
        return new MarkdownParser(self::converter()->getEnvironment())->parse(FrontMatter::blankOut($body));
    }

    private static function converter(?WikiLinkResolver $wikiLinks = null): GithubFlavoredMarkdownConverter
    {
        $converter = new GithubFlavoredMarkdownConverter(self::OPTIONS);

        $converter->getEnvironment()->addExtension(new HighlightExtension);
        $converter->getEnvironment()->addExtension(new ChecklistExtension);
        $converter->getEnvironment()->addExtension(new UnderlineExtension);
        $converter->getEnvironment()->addExtension(new HighlightColors);
        $converter->getEnvironment()->addExtension(new WikiLinkExtension($wikiLinks));
        $converter->getEnvironment()->addExtension(new HeadingPermalinkExtension);
        $converter->getEnvironment()->addExtension(new TableOfContentsExtension);

        return $converter;
    }
}
