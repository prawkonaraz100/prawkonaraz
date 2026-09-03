<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->getKey(),
        ]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    protected function resolveRequestId(Request $request): string
    {
        $header = trim((string) $request->header('X-Request-Id', ''));

        if (
            $header !== ''
            && strlen($header) <= 120
            && preg_match('/\A[a-zA-Z0-9._:-]+\z/', $header) === 1
        ) {
            return $header;
        }

        return (string) Str::ulid();
    }
}
