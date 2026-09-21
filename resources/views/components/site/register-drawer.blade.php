@props(['open' => null])

@php
    $studyContextService = app(\App\Support\StudyContextService::class);
    $categories = $studyContextService->activeCategories()
        ->map(fn ($category) => [
            'id' => $category->getKey(),
            'code' => $category->code,
            'name' => $category->name,
            'short_name' => $studyContextService->shortCategoryName($category),
        ])
        ->values();
    $shouldOpen = is_bool($open) ? $open : old('_auth_panel') === 'register';
    $selectedCategoryId = old('target_category_id');
    $selectedCategory = $categories->first(
        static fn (array $category): bool => (string) $category['id'] === (string) $selectedCategoryId,
    );
    $friendInvitationsEnabled = app(\App\Support\PaymentRequirementService::class)->requiresPayment();
@endphp

<div
    data-register-drawer
    class="auth-dialog-overlay auth-dialog-overlay--standalone fixed inset-0 z-[90] {{ $shouldOpen ? 'flex' : 'hidden' }} font-[system-ui,-apple-system,'Segoe_UI',Roboto,Helvetica,Arial,sans-serif]"
    role="presentation"
    aria-hidden="{{ $shouldOpen ? 'false' : 'true' }}"
    data-register-drawer-open="{{ $shouldOpen ? 'true' : 'false' }}"
>
    <aside
        role="dialog"
        aria-modal="true"
        aria-labelledby="public-register-drawer-title"
        class="auth-dialog auth-dialog--register"
    >
        <button
            type="button"
            aria-label="Zamknij rejestrację"
            class="auth-dialog__close"
            data-register-drawer-close
        >
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>

        <x-site.auth-trust-panel />

        <div class="auth-dialog__panel">
            <div class="auth-register-drawer-body auth-dialog__content auth-dialog__content--register">
            <header class="auth-register-drawer-header auth-dialog__header">
                <a href="/" class="auth-dialog__brand" aria-label="PrawkoNaRaz.pl — strona główna">
                    <span class="auth-dialog__brand-name">prawko<strong>naraz.pl</strong></span>
                    <span class="auth-dialog__brand-tagline">Ucz się szybko i zdaj prawko na raz!</span>
                </a>
                <h2
                    id="public-register-drawer-title"
                    class="auth-register-drawer-title auth-dialog__title"
                >
                    Ucz się teorii szybciej i zdaj prawo jazdy <span>na raz!</span>
                </h2>
                <p class="auth-register-drawer-lead auth-dialog__lead">
                    Oficjalne pytania, szczegółowe wyjaśnienia i wygodna nauka — wszystko w jednym miejscu.
                </p>
            </header>

            <div class="auth-dialog__switcher-row">
                <nav class="auth-switcher" aria-label="Wybierz formularz konta">
                    <button
                        type="button"
                        class="auth-switcher__tab"
                        data-auth-drawer-switch="login"
                    >
                        Logowanie
                    </button>
                    <span class="auth-switcher__tab is-active" aria-current="page">Rejestracja</span>
                </nav>
            </div>

            <form
                class="auth-register-drawer-form auth-dialog__form space-y-4"
                method="POST"
                action="{{ route('register') }}"
                data-csrf-refresh-form
                data-csrf-form-kind="register"
            >
                @csrf
                <input type="hidden" name="_auth_panel" value="register">
                <input
                    type="hidden"
                    name="preferred_learning_track"
                    value="{{ \App\Models\UserProfile::LEARNING_TRACK_CLASSIC }}"
                >

                <div
                    class="auth-dialog__error hidden"
                    role="alert"
                    data-csrf-form-error
                >
                    Nie udało się odświeżyć sesji.
                    <a
                        href="{{ route('register') }}"
                        class="underline underline-offset-2"
                        data-register-drawer-ignore="true"
                    >
                        Otwórz pełną stronę rejestracji.
                    </a>
                </div>

                <div>
                    <p class="auth-register-section-label block text-[0.82rem] font-medium text-[#374151]">
                        Kategoria prawa jazdy
                    </p>
                    <input
                        type="hidden"
                        name="target_category_id"
                        value="{{ $selectedCategoryId }}"
                        data-register-category-value
                    >
                    <div class="relative mt-2" data-register-category-dropdown>
                        <button
                            type="button"
                            data-register-category-trigger
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            class="auth-register-category-trigger flex h-12 w-full items-center justify-between rounded-[6px] border border-[#d8dee8] bg-white px-4 text-left text-[0.95rem] font-medium text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition hover:border-[#c6ceda] focus:outline-none disabled:cursor-not-allowed disabled:bg-[#f8fafc] disabled:text-[#8b95a1]"
                            @disabled($categories->isEmpty())
                        >
                            <span
                                data-register-category-label
                                class="{{ $selectedCategory ? 'text-[#111827]' : 'text-[#9aa3af]' }}"
                            >
                                {{ $selectedCategory['name'] ?? 'Wybierz kategorię' }}
                            </span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-[#6b7280] transition" data-register-category-chevron fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m5 7 5 5 5-5" />
                            </svg>
                        </button>

                        <div
                            class="absolute left-0 right-0 top-full z-30 mt-2 hidden max-h-[15rem] overflow-y-auto rounded-[6px] border border-[#d8dee8] bg-white p-1.5 shadow-[0_18px_42px_rgba(15,23,42,0.16)]"
                            role="listbox"
                            data-register-category-list
                        >
                            @foreach ($categories as $category)
                                @php
                                    $isSelectedCategory = (string) $selectedCategoryId === (string) $category['id'];
                                @endphp
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected="{{ $isSelectedCategory ? 'true' : 'false' }}"
                                    data-register-category-option
                                    data-category-id="{{ $category['id'] }}"
                                    data-category-name="{{ $category['name'] }}"
                                    class="flex min-h-10 w-full items-center rounded-[5px] px-3 text-left text-[0.9rem] font-medium transition focus:outline-none focus:ring-2 focus:ring-[#111827]/15 {{ $isSelectedCategory ? 'bg-[#0a66c2] text-white' : 'bg-white text-[#374151] hover:bg-[#f2f7ff] hover:text-[#111827]' }}"
                                >
                                    {{ $category['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @if ($categories->isEmpty())
                        <p class="mt-2 text-sm font-semibold text-[#d01921]">
                            Kategorie nie są teraz dostępne. Spróbuj odświeżyć stronę.
                        </p>
                    @endif
                    <p class="mt-2 hidden text-sm font-semibold text-[#d01921]" data-register-category-client-error>
                        Wybierz kategorię przed rejestracją społecznościową.
                    </p>
                    @error('target_category_id')
                        <p class="mt-2 text-sm font-semibold text-[#d01921]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-register-field-grid grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="public-register-name" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                            Imię i nazwisko
                        </label>
                        <input
                            id="public-register-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            class="auth-register-field mt-2 block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                            placeholder="Wpisz imię"
                            required
                            autocomplete="name"
                        >
                        @error('name')
                            <p class="mt-2 text-sm font-semibold text-[#d01921]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="public-register-email" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                            Adres e-mail
                        </label>
                        <input
                            id="public-register-email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            class="auth-register-field mt-2 block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                            placeholder="Wpisz e-mail"
                            required
                            autocomplete="username"
                        >
                        @error('email')
                            <p class="mt-2 text-sm font-semibold text-[#d01921]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="auth-register-field-grid grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="public-register-password" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                            Hasło
                        </label>
                        <div class="relative mt-2">
                            <input
                                id="public-register-password"
                                name="password"
                                type="password"
                                data-register-password
                                class="auth-register-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                placeholder="Wpisz hasło"
                                required
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                aria-label="Pokaż hasło"
                                class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                data-register-password-toggle
                                data-register-password-target="public-register-password"
                            >
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm font-semibold text-[#d01921]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="public-register-password-confirmation" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                            Powtórz hasło
                        </label>
                        <div class="relative mt-2">
                            <input
                                id="public-register-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                data-register-password
                                class="auth-register-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                placeholder="Powtórz hasło"
                                required
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                aria-label="Pokaż powtórzone hasło"
                                class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                data-register-password-toggle
                                data-register-password-target="public-register-password-confirmation"
                            >
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="auth-register-submit inline-flex h-12 w-full items-center justify-center rounded-[6px] bg-[#0a66c2] px-5 text-[0.95rem] font-medium text-white transition hover:bg-[#084f96] disabled:cursor-not-allowed disabled:opacity-60"
                    @disabled($categories->isEmpty())
                    data-csrf-submit
                >
                    Załóż konto
                </button>
            </form>

            @php
                $enabledSocialProviders = collect(['google', 'facebook'])
                    ->filter(fn (string $provider): bool => filled(config("services.$provider.client_id")) && filled(config("services.$provider.client_secret")))
                    ->values()
                    ->all();
            @endphp

            @if (count($enabledSocialProviders) > 0)
            <div class="auth-register-social mt-6">
                <div class="flex items-center gap-4">
                    <div class="h-px flex-1 bg-[#e2e7ee]"></div>
                    <span class="text-[0.82rem] font-normal text-[#8b95a1]">LUB</span>
                    <div class="h-px flex-1 bg-[#e2e7ee]"></div>
                </div>

                <div class="auth-register-social-grid mt-5 grid gap-3 {{ count($enabledSocialProviders) > 1 ? 'sm:grid-cols-2' : '' }}">
                    @if (in_array('google', $enabledSocialProviders, true))
                    <button
                        type="button"
                        data-register-social-provider="google"
                        class="auth-google-login-button sm:col-span-2"
                    >
                        <svg aria-hidden="true" viewBox="0 0 48 48" class="h-[1.65rem] w-[1.65rem] flex-none">
                            <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.1 8.4 3.2l6.3-6.3C34.8 2.8 29.7.8 24 .8 14.8.8 6.9 6 3 13.6l7.3 5.7C12.1 13.6 17.5 9.5 24 9.5Z"/>
                            <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.8-2.2 5.2-4.7 6.8l7.2 5.6c4.2-3.9 7.3-9.6 7.3-16.4Z"/>
                            <path fill="#FBBC05" d="M10.3 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7L3 13.6A23.1 23.1 0 0 0 .5 24c0 3.7.9 7.3 2.5 10.4l7.3-5.7Z"/>
                            <path fill="#34A853" d="M24 47.2c5.7 0 10.6-1.9 14.1-5.1l-6.1-6.8c-1.7 1.1-4 1.9-8 1.9-6.5 0-11.9-4.1-13.7-9.8L3 33.1c3.9 7.8 11.8 14.1 21 14.1Z"/>
                        </svg>
                        <span>Kontynuuj z Google</span>
                    </button>
                    @endif
                    @if (in_array('facebook', $enabledSocialProviders, true))
                    <button
                        type="button"
                        data-register-social-provider="facebook"
                        class="auth-register-social-button grid h-12 grid-cols-[2.5rem_1fr_2.5rem] items-center rounded-[6px] border border-[#d1d7e0] bg-white px-3 text-[0.9rem] font-normal text-[#111827] shadow-[0_5px_16px_rgba(15,23,42,0.03)] transition hover:border-[#aeb8c7] hover:bg-[#f8fafc]"
                    >
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-[1.25rem] font-medium text-[#1877f2]">f</span>
                        <span class="text-center">Facebook</span>
                        <span aria-hidden="true"></span>
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <footer class="auth-dialog__footer">
                @if ($friendInvitationsEnabled)
                    <p class="auth-register-invite">
                        Masz kod od znajomego?
                        <a
                            href="{{ route('friend-invitations.code.create', absolute: false) }}"
                            class="font-normal text-[#0a66c2] underline decoration-[#0a66c2]/35 underline-offset-2 transition hover:text-[#084f96]"
                        >
                            Wpisz kod zaproszenia
                        </a>
                    </p>
                @endif

                @if (in_array('google', $enabledSocialProviders, true))
                    <p class="auth-dialog__legal-consent">
                        <span class="auth-dialog__legal-line">
                            Kontynuując z Google, akceptujesz <a href="{{ route('legal.terms', absolute: false) }}">Regulamin</a>
                        </span>
                        <span class="auth-dialog__legal-line">
                            i potwierdzasz zapoznanie się z <a href="{{ route('legal.privacy', absolute: false) }}">Polityką prywatności</a>.
                        </span>
                    </p>
                @else
                    <p class="auth-dialog__legal-consent">
                        <span class="auth-dialog__legal-line">
                            Zakładając konto, akceptujesz <a href="{{ route('legal.terms', absolute: false) }}">regulamin serwisu</a>
                        </span>
                        <span class="auth-dialog__legal-line">
                            oraz <a href="{{ route('legal.privacy', absolute: false) }}">politykę prywatności</a>.
                        </span>
                    </p>
                @endif
            </footer>
            </div>
        </div>
    </aside>
</div>
