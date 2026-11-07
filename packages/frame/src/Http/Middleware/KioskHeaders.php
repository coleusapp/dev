<?php

namespace Coleus\Frame\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KioskHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response->headers->hasCacheControlDirective('max-age')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
