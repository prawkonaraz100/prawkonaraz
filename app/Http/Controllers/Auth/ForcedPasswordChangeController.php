<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request): RedirectResponse|Response
    {
        if (! $request->user()->mustChangePassword()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ForcePasswordChange');
    }

    public function update(Request $request): RedirectResponse
    {
        if (! $request->user()->mustChangePassword()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'password_login_enabled' => true,
            'requires_password_change' => false,
        ])->save();

        app(AuditLogService::class)->record(
            'user.start_password_changed',
            'user',
            (string) $request->user()->getKey(),
            $request->user(),
        );

        return redirect()
            ->route('dashboard')
            ->with('status', 'Hasło zostało zmienione.');
    }
}
