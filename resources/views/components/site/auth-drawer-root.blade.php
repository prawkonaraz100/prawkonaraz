@props(['lazy' => false])

@php
    $authPanel = old('_auth_panel');
    $loginShouldOpen = $authPanel === 'login'
        || ($authPanel === null && ($errors->has('email') || $errors->has('password') || session()->has('status')));
    $registerShouldOpen = $authPanel === 'register';
    $initialDrawer = $registerShouldOpen ? 'register' : ($loginShouldOpen ? 'login' : '');
    $googleIdentityClientId = config('services.google.client_id');
    $googleIdentityEnabled = (bool) config('services.google.identity_enabled') && filled($googleIdentityClientId);
    $googleOneTapEnabled = $googleIdentityEnabled && (bool) config('services.google.one_tap_enabled');
@endphp

<div
    data-public-auth-drawer-root
    data-auth-drawer-lazy="{{ $lazy ? 'true' : 'false' }}"
    data-login-fragment-url="{{ route('public.auth-drawers.show', ['drawer' => 'login'], absolute: false) }}"
    data-register-fragment-url="{{ route('public.auth-drawers.show', ['drawer' => 'register'], absolute: false) }}"
    data-initial-drawer="{{ $initialDrawer }}"
    data-google-identity-enabled="{{ $googleIdentityEnabled ? 'true' : 'false' }}"
    data-google-client-id="{{ $googleIdentityEnabled ? $googleIdentityClientId : '' }}"
    data-google-login-url="{{ $googleIdentityEnabled ? route('google.identity.login', absolute: false) : '' }}"
    data-google-one-tap-enabled="{{ $googleOneTapEnabled ? 'true' : 'false' }}"
>
    @if ($googleOneTapEnabled)
        <aside
            class="google-one-tap-host"
            data-google-one-tap-host
            aria-hidden="true"
        >
            <section class="google-one-tap-consent" aria-labelledby="google-one-tap-consent-title">
                <button
                    type="button"
                    class="google-one-tap-consent__close"
                    aria-label="Zamknij szybkie logowanie Google"
                    data-google-one-tap-close
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
                <h2 id="google-one-tap-consent-title">Wyraź zgodę i dołącz do Prawko na Raz</h2>
                <p>
                    Klikając Kontynuuj, aby dołączyć lub się zalogować, wyrażasz zgodę na
                    <a href="{{ route('legal.terms', absolute: false) }}">Regulamin</a>
                    i potwierdzasz zapoznanie się z
                    <a href="{{ route('legal.privacy', absolute: false) }}">Polityką prywatności</a>.
                </p>
            </section>
            <div class="google-one-tap-fallback" data-google-one-tap-button></div>
        </aside>
    @endif

    @if (! $lazy)
        <x-site.login-drawer :open="$loginShouldOpen" />
        <x-site.register-drawer :open="$registerShouldOpen" />
    @elseif ($initialDrawer === 'login')
        <x-site.login-drawer :open="true" />
    @elseif ($initialDrawer === 'register')
        <x-site.register-drawer :open="true" />
    @endif
</div>
