<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Extension\Highlight\HighlightExtension;
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
    ];

    public static function toHtml(string $body): string
    {
        return (string) self::converter()->convert($body);
    }

    public static function parse(string $body): Document
    {
        return new MarkdownParser(self::converter()->getEnvironment())->parse($body);
    }

    private static function converter(): GithubFlavoredMarkdownConverter
    {
        $converter = new GithubFlavoredMarkdownConverter(self::OPTIONS);

        $converter->getEnvironment()->addExtension(new HighlightExtension);
        $converter->getEnvironment()->addExtension(new ChecklistExtension);

        return $converter;
    }
}
