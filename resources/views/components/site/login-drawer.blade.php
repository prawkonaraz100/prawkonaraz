@props(['open' => null])

@php
    $authPanel = old('_auth_panel');
    $shouldOpen = is_bool($open)
        ? $open
        : ($authPanel === 'login'
            || ($authPanel === null && ($errors->has('email') || $errors->has('password') || session()->has('status'))));
@endphp

<div
    data-login-drawer
    class="auth-dialog-overlay auth-dialog-overlay--standalone fixed inset-0 z-[90] {{ $shouldOpen ? 'flex' : 'hidden' }} font-[system-ui,-apple-system,'Segoe_UI',Roboto,Helvetica,Arial,sans-serif]"
    role="presentation"
    aria-hidden="{{ $shouldOpen ? 'false' : 'true' }}"
    data-login-drawer-open="{{ $shouldOpen ? 'true' : 'false' }}"
>
    <aside
        role="dialog"
        aria-modal="true"
        aria-labelledby="public-login-drawer-title"
        class="auth-dialog auth-dialog--login"
    >
        <button
            type="button"
            aria-label="Zamknij logowanie"
            class="auth-dialog__close"
            data-login-drawer-close
        >
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>

        <x-site.auth-trust-panel />

        <div class="auth-dialog__panel">
            <div class="auth-login-drawer-body auth-dialog__content">
            <header class="auth-dialog__header">
                <a href="/" class="auth-dialog__brand" aria-label="PrawkoNaRaz.pl — strona główna">
                    <span class="auth-dialog__brand-name">prawko<strong>naraz.pl</strong></span>
                    <span class="auth-dialog__brand-tagline">Ucz się szybko i zdaj prawko na raz!</span>
                </a>
                <h2
                    id="public-login-drawer-title"
                    class="auth-login-drawer-title auth-dialog__title"
                >
                    Ucz się teorii szybciej i zdaj prawo jazdy <span>na raz!</span>
                </h2>
                <p class="auth-login-drawer-lead auth-dialog__lead">
                    Oficjalne pytania, szczegółowe wyjaśnienia i wygodna nauka — wszystko w jednym miejscu.
                </p>
            </header>

            <div class="auth-dialog__switcher-row">
                <nav class="auth-switcher" aria-label="Wybierz formularz konta">
                    <span class="auth-switcher__tab is-active" aria-current="page">Logowanie</span>
                    <button
                        type="button"
                        class="auth-switcher__tab"
                        data-auth-drawer-switch="register"
                    >
                        Rejestracja
                    </button>
                </nav>
            </div>

            @if (session('status'))
                <div class="auth-dialog__notice" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form
                class="auth-login-drawer-form auth-dialog__form space-y-4"
                method="POST"
                action="{{ route('login') }}"
                data-csrf-refresh-form
                data-csrf-form-kind="login"
            >
                @csrf
                <input type="hidden" name="_auth_panel" value="login">

                <div
                    class="auth-dialog__error hidden"
                    role="alert"
                    data-csrf-form-error
                >
                    Nie udało się odświeżyć sesji.
                    <a
                        href="{{ route('login') }}"
                        class="underline underline-offset-2"
                        data-login-drawer-ignore="true"
                    >
                        Otwórz pełną stronę logowania.
                    </a>
                </div>

                <div>
                    <label for="public-login-email" class="block text-[0.82rem] font-normal text-[#374151]">
                        Adres e-mail
                    </label>
                    <div class="relative mt-2">
                        <input
                            id="public-login-email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            data-testid="login-email"
                            class="auth-login-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                            placeholder="Wpisz adres e-mail"
                            required
                            autocomplete="username"
                        >
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#8b95a1]" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm font-normal text-[#d01921]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="public-login-password" class="block text-[0.82rem] font-normal text-[#374151]">
                        Hasło
                    </label>

                    <div class="relative mt-2">
                        <input
                            id="public-login-password"
                            name="password"
                            type="password"
                            data-login-password
                            data-testid="login-password"
                            class="auth-login-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                            placeholder="Wpisz hasło"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            aria-label="Pokaż hasło"
                            class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                            data-login-password-toggle
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm font-normal text-[#d01921]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-login-options flex items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-3 text-[0.85rem] font-normal text-[#4b5563]">
                        <input
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-[#cfd6e2] text-[#0a66c2] focus:ring-[#0a66c2]/25"
                            @checked(old('remember'))
                        >
                        <span>Zapamiętaj mnie</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request', absolute: false) }}"
                            class="text-[0.82rem] font-normal text-[#0a66c2] underline decoration-[#0a66c2]/35 underline-offset-2 transition hover:text-[#084f96]"
                        >
                            Nie pamiętasz hasła?
                        </a>
                    @endif
                </div>

                <button
                    type="submit"
                    data-testid="login-submit"
                    class="auth-login-drawer-submit inline-flex h-12 w-full items-center justify-center rounded-[6px] bg-[#0a66c2] px-5 text-[0.95rem] font-medium text-white transition hover:bg-[#084f96] disabled:cursor-not-allowed disabled:opacity-60"
                    data-csrf-submit
                >
                    Zaloguj się
                </button>
            </form>

            @php
                $enabledSocialProviders = collect(['google', 'facebook'])
                    ->filter(fn (string $provider): bool => filled(config("services.$provider.client_id")) && filled(config("services.$provider.client_secret")))
                    ->values()
                    ->all();
            @endphp

            @if (count($enabledSocialProviders) > 0)
            <div class="auth-login-drawer-social auth-dialog__social">
                <div class="auth-login-drawer-social-grid grid gap-3">
                    @if (in_array('google', $enabledSocialProviders, true))
                    <a
                        href="{{ route('social.redirect', ['provider' => 'google'], absolute: false) }}"
                        class="auth-google-login-button"
                    >
                        <svg aria-hidden="true" viewBox="0 0 48 48" class="h-[1.65rem] w-[1.65rem] flex-none">
                            <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.1 8.4 3.2l6.3-6.3C34.8 2.8 29.7.8 24 .8 14.8.8 6.9 6 3 13.6l7.3 5.7C12.1 13.6 17.5 9.5 24 9.5Z"/>
                            <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.8-2.2 5.2-4.7 6.8l7.2 5.6c4.2-3.9 7.3-9.6 7.3-16.4Z"/>
                            <path fill="#FBBC05" d="M10.3 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7L3 13.6A23.1 23.1 0 0 0 .5 24c0 3.7.9 7.3 2.5 10.4l7.3-5.7Z"/>
                            <path fill="#34A853" d="M24 47.2c5.7 0 10.6-1.9 14.1-5.1l-6.1-6.8c-1.7 1.1-4 1.9-8 1.9-6.5 0-11.9-4.1-13.7-9.8L3 33.1c3.9 7.8 11.8 14.1 21 14.1Z"/>
                        </svg>
                        <span>Kontynuuj z Google</span>
                    </a>
                    @endif
                    @if (in_array('facebook', $enabledSocialProviders, true))
                    <a
                        href="{{ route('social.redirect', ['provider' => 'facebook'], absolute: false) }}"
                        class="auth-login-drawer-social-button grid h-12 grid-cols-[2.5rem_1fr_2.5rem] items-center rounded-[6px] border border-[#d1d7e0] bg-white px-3 text-[0.9rem] font-normal text-[#111827] shadow-[0_5px_16px_rgba(15,23,42,0.03)] transition hover:border-[#aeb8c7] hover:bg-[#f8fafc]"
                    >
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-[1.25rem] font-medium text-[#1877f2]">f</span>
                        <span class="text-center">Zaloguj się z Facebooka</span>
                        <span aria-hidden="true"></span>
                    </a>
                    @endif
                </div>

                <div class="auth-dialog__divider">
                    <span>LUB</span>
                </div>
            </div>
            @endif

            <footer class="auth-dialog__footer">
                <p class="auth-dialog__legal-consent">
                    <span class="auth-dialog__legal-line">
                        Logując się, akceptujesz <a href="{{ route('legal.terms', absolute: false) }}">regulamin serwisu</a>
                    </span>
                    <span class="auth-dialog__legal-line">
                        oraz <a href="{{ route('legal.privacy', absolute: false) }}">politykę prywatności</a>.
                    </span>
                </p>
            </footer>
            </div>
        </div>
    </aside>
</div>
