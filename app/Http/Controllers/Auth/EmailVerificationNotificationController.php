<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\VerificationEmailDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, VerificationEmailDelivery $delivery): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (! $delivery->send($request->user())) {
            return back()->withErrors(['email' => 'Nie udało się wysłać wiadomości. Twoje konto jest zachowane. Spróbuj ponownie za chwilę.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
