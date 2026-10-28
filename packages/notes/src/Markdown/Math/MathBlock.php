<?php

namespace Coleus\Notes\Markdown\Math;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * A $$…$$ block of TeX, shown centred on its own (O5.4).
 */
class MathBlock extends AbstractBlock
{
    /**
     * @var list<string>
     */
    public array $lines = [];
}
