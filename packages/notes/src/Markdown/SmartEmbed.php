<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * An allowed service's link, shown as its embed (B6.1, see SmartEmbeds).
 */
class SmartEmbed extends AbstractInline
{
    public function __construct(public readonly string $url, public readonly string $src, public readonly string $provider)
    {
        parent::__construct();
    }
}
