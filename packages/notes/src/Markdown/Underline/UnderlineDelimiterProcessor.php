<?php

namespace Coleus\Notes\Markdown\Underline;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\CacheableDelimiterProcessorInterface;
use League\CommonMark\Node\Inline\AbstractStringContainer;

/**
 * Pairs "++" runs into Underline nodes. Only exactly two plus signs on both
 * sides count, so "1+1", "C++" and "+++" stay plain text.
 */
class UnderlineDelimiterProcessor implements CacheableDelimiterProcessorInterface
{
    public function getOpeningCharacter(): string
    {
        return '+';
    }

    public function getClosingCharacter(): string
    {
        return '+';
    }

    public function getMinLength(): int
    {
        return 2;
    }

    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        return $opener->getLength() === 2 && $closer->getLength() === 2 ? 2 : 0;
    }

    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $underline = new Underline;

        $node = $opener->next();
        while ($node !== null && $node !== $closer) {
            $next = $node->next();
            $underline->appendChild($node);
            $node = $next;
        }

        $opener->insertAfter($underline);
    }

    public function getCacheKey(DelimiterInterface $closer): string
    {
        return '+'.$closer->getLength();
    }
}
