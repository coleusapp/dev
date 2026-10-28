<?php

namespace Coleus\Notes\Markdown\Math;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * $TeX$ inside a line (O5.4).
 */
class MathInline extends AbstractInline
{
    public function __construct(public readonly string $tex)
    {
        parent::__construct();
    }
}
