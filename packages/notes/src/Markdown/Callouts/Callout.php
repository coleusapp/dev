<?php

namespace Coleus\Notes\Markdown\Callouts;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * An Obsidian callout: a blockquote starting "[!type]", optionally foldable
 * ("+" open, "-" closed) and titled ("> [!tip]- Read me").
 */
class Callout extends AbstractBlock
{
    public function __construct(
        public readonly string $type,
        public readonly string $family,
        public readonly string $fold,
        public readonly string $title,
    ) {
        parent::__construct();
    }
}
