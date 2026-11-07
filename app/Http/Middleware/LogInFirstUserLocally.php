<?php

namespace App\Http\Middleware;

use Closure;
use Coleus\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * On a local environment, signs a guest in as the oldest user so that
 * development never stops at a login screen. Does nothing anywhere else.
 */
class LogInFirstUserLocally
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal() && Auth::guest()) {
            $firstUser = User::query()->orderBy('id')->first();

            if ($firstUser !== null) {
                Auth::login($firstUser);
            }
        }

        return $next($request);
    }
}
