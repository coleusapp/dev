<?php

namespace Coleus\Notes\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NoteText
{
    public const int SnippetLength = 140;

    public const int TitleLength = 255;

    private const string TextBlocks = 'p, h1, h2, h3, h4, h5, h6, pre';

    public static function title(?string $body): string
    {
        return Str::limit(self::textBlocks($body)->first() ?? '', self::TitleLength - 3);
    }

    public static function snippet(?string $body, int $length = self::SnippetLength): string
    {
        return Str::limit(self::textBlocks($body)->skip(1)->implode(' '), $length);
    }

    /** @return Collection<int, string> */
    private static function textBlocks(?string $body): Collection
    {
        if (blank($body)) {
            return collect();
        }

        $document = HTMLDocument::createFromString($body, LIBXML_NOERROR, 'UTF-8');

        return collect($document->querySelectorAll(self::TextBlocks))
            ->map(fn (Element $block): string => trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $block->textContent))))
            ->filter(fn (string $text): bool => $text !== '')
            ->values();
    }
}
