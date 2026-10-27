<?php

namespace Coleus\Notes\Markdown\Underline;

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\DelimitedInterface;

/**
 * ++underlined++ text, the syntax Tiptap's underline mark writes.
 */
class Underline extends AbstractInline implements DelimitedInterface
{
    public function getOpeningDelimiter(): string
    {
        return '++';
    }

    public function getClosingDelimiter(): string
    {
        return '++';
    }
}
