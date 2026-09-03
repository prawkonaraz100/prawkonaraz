<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PjmFreeAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePjmModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $decision = app(PjmFreeAccessResolver::class)->forUser($user);

        if ($decision->allowed) {
            $request->attributes->set('pjm_access_decision', $decision);

            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            abort(403, 'Dostęp do modułu PJM nie jest dostępny dla tego konta.');
        }

        if ($decision->reason === 'temporary_account_unclaimed') {
            return redirect()->route('account.claim.edit');
        }

        if ($decision->reason === 'password_change_required') {
            return redirect()->route('password.force.edit');
        }

        if ($decision->reason === 'email_unverified') {
            return redirect()->route('verification.notice');
        }

        if ($decision->reason === 'pjm_module_suspended') {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Moduł PJM jest obecnie czasowo zawieszony.');
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Moduł PJM nie jest jeszcze dostępny dla tej kategorii.');
    }
}
