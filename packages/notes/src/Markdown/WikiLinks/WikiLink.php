<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * A [[Note title]] reference in a note body, optionally to a heading
 * ([[Note title#Heading]], or [[#Heading]] in the same note, where the title
 * is "") and shown under other text ([[Note title|shown text]]).
 */
class WikiLink extends AbstractInline
{
    public function __construct(public readonly string $title, public readonly ?string $label = null, public readonly ?string $heading = null)
    {
        parent::__construct();
    }
}
