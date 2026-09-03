<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $passwordLoginWasEnabled = (bool) $user->password_login_enabled;

        $rules = [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];

        if ($passwordLoginWasEnabled) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_login_enabled' => true,
        ]);

        return back()->with('status', $passwordLoginWasEnabled ? 'password-updated' : 'password-set');
    }
}
