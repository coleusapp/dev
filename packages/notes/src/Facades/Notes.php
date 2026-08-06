<?php

namespace Coleus\Notes\Facades;

use Illuminate\Support\Facades\Facade;

class Notes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'notes';
    }
}
