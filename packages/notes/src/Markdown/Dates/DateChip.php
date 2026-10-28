<?php

namespace Coleus\Notes\Markdown\Dates;

use Carbon\CarbonImmutable;
use League\CommonMark\Node\Inline\AbstractInline;

/**
 * An "@2026-10-28" date in a note (B5.4), shown as a chip.
 */
class DateChip extends AbstractInline
{
    public function __construct(public readonly CarbonImmutable $date)
    {
        parent::__construct();
    }
}
