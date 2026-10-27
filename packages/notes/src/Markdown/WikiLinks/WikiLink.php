<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * A [[Note title]] reference in a note body.
 */
class WikiLink extends AbstractInline
{
    public function __construct(public readonly string $title)
    {
        parent::__construct();
    }
}
