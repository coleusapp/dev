<?php

namespace Coleus\Music\Http\Middleware;

use Coleus\Support\Http\Middleware\HandleInertiaRequests as BaseHandleInertiaRequests;

class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    protected $rootView = 'music::app';
}
