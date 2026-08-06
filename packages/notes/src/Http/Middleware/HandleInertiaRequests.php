<?php

namespace Coleus\Notes\Http\Middleware;

use Coleus\Support\Http\Middleware\HandleInertiaRequests as BaseHandleInertiaRequests;

class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    protected $rootView = 'notes::app';
}
