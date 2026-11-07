<?php

namespace Coleus\Frame\Support;

use Coleus\Frame\Models\Frame;

/**
 * A frame with its plain kiosk token, which exists only here: the database
 * keeps just the hash, so it can be shown to the owner only this once.
 */
class KioskToken
{
    public function __construct(
        public Frame $frame,
        public string $token,
    ) {}
}
