<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * A line holding only ![[Note title]] or ![[Note title#Heading]]: the
 * other note (or that section of it) is shown in place (O3.3).
 */
class NoteEmbed extends AbstractBlock
{
    public function __construct(public readonly string $title, public readonly ?string $heading = null, public readonly ?string $label = null)
    {
        parent::__construct();
    }
}
