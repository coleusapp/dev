<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * A [[Note title]] reference in a note body, optionally shown under other
 * text: [[Note title|shown text]].
 */
class WikiLink extends AbstractInline
{
    public function __construct(public readonly string $title, public readonly ?string $label = null)
    {
        parent::__construct();
    }
}
