<?php

namespace App\Http\Controllers;

use App\Support\AppOnboardingCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AppEntryController extends Controller
{
    public function show(Request $request, AppOnboardingCookie $onboardingCookie): Response
    {
        if ($request->user() !== null) {
            return to_route('dashboard');
        }

        if ($onboardingCookie->wasSeen($request)) {
            return to_route('app.login');
        }

        $response = Inertia::render('App/Onboarding')->toResponse($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    public function complete(Request $request, AppOnboardingCookie $onboardingCookie): RedirectResponse
    {
        if ($request->user() !== null) {
            return to_route('dashboard');
        }

        $validated = $request->validate([
            'destination' => ['required', Rule::in(['login', 'register'])],
        ]);

        $onboardingCookie->queue($request);

        return to_route($validated['destination'] === 'login' ? 'app.login' : 'app.register');
    }
}
