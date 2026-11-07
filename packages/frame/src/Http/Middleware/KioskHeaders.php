<?php

namespace Coleus\Frame\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kiosk URLs carry the frame's secret token, so they must never be cached
 * by shared caches, leaked through a Referer header or indexed. Responses
 * that set their own Cache-Control (the artwork images) keep it.
 */
class KioskHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->hasCacheControlDirective('max-age')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
