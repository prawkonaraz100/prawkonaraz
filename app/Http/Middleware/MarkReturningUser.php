<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ReturningUserCookie;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkReturningUser
{
    public function __construct(
        protected ReturningUserCookie $returningUserCookie,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shouldMark = $user instanceof User && ! $request->routeIs('profile.destroy');

        $response = $next($request);

        if ($shouldMark) {
            $this->returningUserCookie->queue($request);
        }

        return $response;
    }
}
