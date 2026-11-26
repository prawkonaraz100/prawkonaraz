<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ProductAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        // API rankingu jest wywoływane przez każdego gracza co kilka sekund. Pozytywną decyzję
        // trzymamy krótko w cache; ban sprawdza osobno EnsureUserIsNotBanned przy każdym żądaniu,
        // a wygaśnięcie albo cofnięcie dostępu zadziała tu najpóźniej po RANKED_ACCESS_CACHE_SECONDS.
        $rankedAccessCacheKey = $request->routeIs('api.v1.ranked.*')
            ? 'product-access:ranked:'.$user->getKey()
            : null;
        $rankedAccessCacheSeconds = self::rankedAccessCacheSeconds();

        if ($rankedAccessCacheKey !== null && $rankedAccessCacheSeconds > 0 && Cache::get($rankedAccessCacheKey) === true) {
            return $next($request);
        }

        $decision = app(ProductAccessResolver::class)->forUser($user);

        if ($decision->allowed) {
            $request->attributes->set('product_access_decision', $decision);

            if ($rankedAccessCacheKey !== null && $rankedAccessCacheSeconds > 0) {
                Cache::put($rankedAccessCacheKey, true, now()->addSeconds($rankedAccessCacheSeconds));
            }

            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            abort(403, 'Aktywny dostęp do produktu jest wymagany.');
        }

        if ($decision->reason === 'temporary_account_unclaimed') {
            return redirect()->route('account.claim.edit');
        }

        if ($decision->reason === 'password_change_required') {
            return redirect()->route('password.force.edit');
        }

        return redirect()
            ->route('access.activate')
            ->with('status', 'Aktywuj dostęp do produktu, żeby kontynuować naukę.');
    }

    protected static function rankedAccessCacheSeconds(): int
    {
        return max(0, (int) config('ranked.access_cache_seconds', 60));
    }
}
