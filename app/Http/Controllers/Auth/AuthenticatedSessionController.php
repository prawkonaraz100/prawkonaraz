<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ReturningUserCookie;
use App\Support\UserIpHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->boolean('session_expired')
                ? 'Twoja sesja wygasła. Zaloguj się ponownie.'
                : session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ReturningUserCookie $returningUserCookie): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user !== null) {
            app(UserIpHistoryService::class)->record($user, $request, 'logowanie', force: true);
        }

        $returningUserCookie->queue($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function confirmDestroy(): Response
    {
        return Inertia::render('Auth/LogoutConfirm');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|SymfonyResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($request->header('X-Inertia')) {
            return Inertia::location('/');
        }

        return redirect('/');
    }
}
