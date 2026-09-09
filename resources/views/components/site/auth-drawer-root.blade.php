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
    <x-site.home-header :immediate="true" :auth-overlay="true" />

    @if (! $lazy)
        <x-site.login-drawer :open="$loginShouldOpen" />
        <x-site.register-drawer :open="$registerShouldOpen" />
    @elseif ($initialDrawer === 'login')
        <x-site.login-drawer :open="true" />
    @elseif ($initialDrawer === 'register')
        <x-site.register-drawer :open="true" />
    @endif
</div>
