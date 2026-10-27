<?php

namespace Coleus\Notes\Markdown;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Highlight\HighlightExtension;

/**
 * Renders a note body to HTML on the server.
 *
 * Raw HTML in a note is escaped, never passed through, and javascript:,
 * vbscript:, file: and data: (non-image) links are dropped, so a note can't
 * inject script into the page that previews it.
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
        return Str::markdown($body, self::OPTIONS, [
            new HighlightExtension,
        ]);
    }
}
