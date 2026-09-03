<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalHost
{
    /**
     * Redirect alternate production hosts to the public application URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        $canonicalUrl = rtrim((string) config('app.url'), '/');
        $canonicalHost = parse_url($canonicalUrl, PHP_URL_HOST);

        if (! is_string($canonicalHost) || $canonicalHost === '') {
            return $next($request);
        }

        if (strcasecmp($request->getHost(), $canonicalHost) === 0) {
            return $next($request);
        }

        return redirect()->away(
            $canonicalUrl.$request->getRequestUri(),
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }
}
