<?php

namespace Coleus\Notes\Bases;

use RuntimeException;

/**
 * A formula that can't be read, or a value it can't work with. The message
 * is shown to the user as written.
 */
class FormulaError extends RuntimeException
{
    //
}
