<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ProductAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $decision = app(ProductAccessResolver::class)->forUser($user);

        if ($decision->allowed) {
            $request->attributes->set('product_access_decision', $decision);

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
}
