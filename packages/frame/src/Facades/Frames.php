<?php

namespace Coleus\Frame\Facades;

use Illuminate\Support\Facades\Facade;

/** @mixin \Coleus\Frame\Frames */
class Frames extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'frames';
    }
}
