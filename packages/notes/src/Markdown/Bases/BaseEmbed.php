<?php

namespace Coleus\Notes\Markdown\Bases;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * A line holding only ![[Base name.base]], optionally ![[Base name.base|cards]]
 * or |table: that base's rows shown in the note (O11.3).
 */
class BaseEmbed extends AbstractBlock
{
    public function __construct(public readonly string $name, public readonly ?string $view = null)
    {
        parent::__construct();
    }
}
