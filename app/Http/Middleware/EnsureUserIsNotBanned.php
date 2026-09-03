<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isBanned()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse([
                'message' => 'To konto zostało zablokowane.',
            ], 403);
        }

        return redirect()
            ->route('login')
            ->with('status', 'To konto zostało zablokowane.');
    }
}
