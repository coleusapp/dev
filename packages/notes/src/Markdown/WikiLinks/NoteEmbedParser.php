<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\Attachments\AttachmentEmbedExtension;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Starts a NoteEmbed for a line that is only "![[…]]" (up to three spaces
 * of indent, trailing spaces allowed). An embed inside a sentence stays a
 * plain link, since a whole note can't sit inside a paragraph.
 */
class NoteEmbedParser extends AbstractBlockContinueParser
{
    public function __construct(private readonly NoteEmbed $block)
    {
        //
    }

    public function getBlock(): NoteEmbed
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::none();
    }

    public static function blockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface
        {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented() || preg_match('/^!'.WikiLinks::PATTERN_BODY.'[ \t]*$/u', $cursor->getRemainder(), $match) !== 1) {
                    return BlockStart::none();
                }

                ['title' => $title, 'heading' => $heading, 'label' => $label] = WikiLinks::split($match[1]);

                // ![[photo.png]] is an attachment (Markdown\Attachments), not a note.
                if ($title === '' || AttachmentEmbedExtension::isFileName($title)) {
                    return BlockStart::none();
                }

                $cursor->advanceToEnd();

                return BlockStart::of(new NoteEmbedParser(new NoteEmbed($title, $heading, $label)))->at($cursor);
            }
        };
    }
}
