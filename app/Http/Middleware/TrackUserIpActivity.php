<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserIpHistoryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserIpActivity
{
    public function __construct(
        protected UserIpHistoryService $userIpHistoryService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shouldTrack = $user instanceof User && ! $this->shouldSkip($request);
        $source = $shouldTrack ? $this->resolveSource($request) : null;

        $response = $next($request);

        if ($shouldTrack && $source !== null) {
            $this->userIpHistoryService->record($user, $request, $source);
        }

        return $response;
    }

    protected function shouldSkip(Request $request): bool
    {
        return $request->routeIs('logout', 'profile.destroy');
    }

    protected function resolveSource(Request $request): string
    {
        if ($request->is('admin*')) {
            return 'panel';
        }

        if ($request->is('api/*')) {
            return 'api';
        }

        if ($request->routeIs('study-sessions.*') || $request->routeIs('session.*')) {
            return 'nauka';
        }

        return 'www';
    }
}
