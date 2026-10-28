<?php

namespace Coleus\Notes\Markdown\Math;

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * "$$" on its own line opens a math block that runs to the next "$$" line;
 * "$$TeX$$" alone on a line is a one-line block.
 */
class MathBlockParser extends AbstractBlockContinueParser
{
    private bool $finished = false;

    /**
     * The parser is also handed what follows "$$" on the opening line (nothing).
     */
    private bool $openingLine = true;

    public function __construct(private readonly MathBlock $block, bool $oneLine = false)
    {
        $this->finished = $oneLine;
    }

    public function getBlock(): MathBlock
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($this->finished) {
            return BlockContinue::none();
        }

        if (preg_match('/^ {0,3}\$\$[ \t]*$/', $cursor->getLine()) === 1) {
            $this->finished = true;

            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        if ($this->openingLine) {
            $this->openingLine = false;

            return;
        }

        $this->block->lines[] = $line;
    }

    public static function blockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface
        {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                $line = $cursor->getRemainder();

                if (preg_match('/^\$\$[ \t]*$/', $line) === 1) {
                    $cursor->advanceToEnd();

                    return BlockStart::of(new MathBlockParser(new MathBlock))->at($cursor);
                }

                if (preg_match('/^\$\$(.+?)\$\$[ \t]*$/', $line, $match) === 1) {
                    $block = new MathBlock;
                    $block->lines = [$match[1]];
                    $cursor->advanceToEnd();

                    return BlockStart::of(new MathBlockParser($block, true))->at($cursor);
                }

                return BlockStart::none();
            }
        };
    }
}
