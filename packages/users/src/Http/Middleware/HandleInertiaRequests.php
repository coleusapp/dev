<?php

namespace Coleus\Users\Http\Middleware;

use Coleus\Support\Http\Middleware\HandleInertiaRequests as BaseHandleInertiaRequests;

class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    protected $rootView = 'users::app';
}
