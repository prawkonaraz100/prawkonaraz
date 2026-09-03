<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TemporaryAccountClaimController extends Controller
{
    public function edit(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if (! $user->isUnclaimedTemporaryAccount()) {
            return redirect()->route('dashboard');
        }

        if ($user->temporaryAccountExpired()) {
            return $this->expiredResponse($request);
        }

        return Inertia::render('Auth/ClaimTemporaryAccount', [
            'technicalEmail' => $user->email,
            'expiresAt' => $user->temporary_account_expires_at?->toIso8601String(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isUnclaimedTemporaryAccount()) {
            return redirect()->route('dashboard');
        }

        if ($user->temporaryAccountExpired()) {
            return $this->expiredResponse($request);
        }

        if ($request->filled('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->getKey()),
            ],
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'email.unique' => 'Konto z takim adresem e-mail już istnieje.',
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'email' => strtolower(trim((string) $validated['email'])),
                'password' => Hash::make($validated['password']),
                'password_login_enabled' => true,
                'email_verified_at' => null,
                'requires_password_change' => false,
                'is_temporary_account' => false,
                'temporary_account_expires_at' => null,
                'claimed_at' => now(),
            ])->save();

            $user->sendEmailVerificationNotification();

            app(AuditLogService::class)->record(
                'moderator.temporary_account_claimed',
                'user',
                (string) $user->getKey(),
                $user,
            );
        });

        return redirect()
            ->route('verification.notice')
            ->with('status', 'verification-link-sent');
    }

    protected function expiredResponse(Request $request): RedirectResponse
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => 'To konto tymczasowe wygasło. Skontaktuj się z moderatorem.',
        ]);
    }
}
