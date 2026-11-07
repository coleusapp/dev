<?php

namespace Coleus\Frame\Http\Middleware;

use Coleus\Support\Http\Middleware\HandleInertiaRequests as BaseHandleInertiaRequests;

class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    protected $rootView = 'frame::app';
}
