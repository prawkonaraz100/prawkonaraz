@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell">
            <div class="pb-16 pt-9 md:pb-20 md:pt-12 lg:pb-24">
                <div class="mx-auto max-w-2xl text-center">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                        {{ $checkout['access_open'] ? 'Nauka jest teraz dostępna bez opłaty' : 'Wybierz plan nauki' }}
                    </h1>
                    <p class="content-muted mx-auto mt-3 max-w-xl text-sm leading-6 md:text-base md:leading-7">
                        {{ $checkout['access_open'] ? 'Załóż konto, potwierdź e-mail i rozpocznij naukę.' : 'Miesiąc, 3 miesiące albo rok. Pełny dostęp do nauki i powtórek w każdym planie.' }}
                    </p>
                </div>

                @if ($checkout['access_open'])
                    @php
                        if ($checkout['has_active_access']) {
                            $openAccessActionUrl = $checkout['session_url'];
                            $openAccessActionLabel = 'Przejdź do nauki';
                        } elseif (auth()->check()) {
                            $openAccessActionUrl = $checkout['verify_email_url'];
                            $openAccessActionLabel = 'Potwierdź e-mail';
                        } else {
                            $openAccessActionUrl = $checkout['register_url'];
                            $openAccessActionLabel = 'Załóż bezpłatne konto';
                        }
                    @endphp

                    <div data-open-access class="mx-auto mt-8 max-w-2xl border border-slate-200 bg-white p-6 text-center shadow-[0_12px_28px_rgba(15,23,42,0.05)] md:p-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0d47a1]">
                            Dostęp otwarty
                        </p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
                            Pełna nauka bez opłaty
                        </h2>
                        <p class="content-muted mx-auto mt-3 max-w-xl text-sm leading-6 md:text-base md:leading-7">
                            Wszystkie dostępne tryby nauki, powtórki i przygotowanie do egzaminu są teraz dostępne po potwierdzeniu adresu e-mail.
                        </p>
                        <a
                            href="{{ $openAccessActionUrl }}"
                            class="mt-6 inline-flex h-11 items-center justify-center rounded-[4px] bg-[#0d47a1] px-5 text-sm font-semibold text-white transition hover:bg-blue-800"
                        >
                            {{ $openAccessActionLabel }}
                        </a>
                    </div>
                @elseif ($plans->isNotEmpty())
                    <ul class="mt-8 grid gap-6 lg:grid-cols-3 lg:items-stretch">
                        @foreach ($plans as $plan)
                            @php
                                $isFeatured = (bool) $plan['is_featured'];
                                $priceParts = preg_match('/^(.+)\s+([A-Z]{3})$/u', $plan['formatted_price'], $matches)
                                    ? [$matches[1], $matches[2]]
                                    : [$plan['formatted_price'], ''];
                                $actionLabel = $plan['cta_label'];

                                if (! $plan['available']) {
                                    $actionType = 'disabled';
                                    $actionLabel = 'Cena wkrótce';
                                } elseif ($checkout['has_active_access']) {
                                    $actionType = 'link';
                                    $actionUrl = route('session.index', absolute: false);
                                    $actionLabel = 'Przejdź do nauki';
                                } elseif ($checkout['can_checkout']) {
                                    $actionType = 'checkout';
                                } else {
                                    $actionType = 'link';
                                    $actionUrl = auth()->check()
                                        ? $checkout['verify_email_url']
                                        : $checkout['login_url'];
                                    $actionLabel = auth()->check()
                                        ? 'Potwierdź e-mail'
                                        : $plan['cta_label'];
                                }
                            @endphp

                        <li class="flex">
                            <article
                                data-pricing-plan="{{ $plan['code'] }}"
                                data-featured="{{ $isFeatured ? 'true' : 'false' }}"
                                class="relative flex w-full flex-col rounded-[8px] border bg-white transition {{ $isFeatured ? 'border-[#0d47a1] shadow-[0_18px_40px_rgba(13,71,161,0.12)] ring-1 ring-[#0d47a1] lg:-mt-4' : 'border-slate-200 shadow-[0_12px_28px_rgba(15,23,42,0.05)] hover:border-slate-300 hover:shadow-[0_18px_34px_rgba(15,23,42,0.07)]' }}"
                            >
                                @if ($isFeatured)
                                    <div class="flex h-10 items-center justify-center rounded-t-[7px] bg-[#0d47a1] px-4 text-xs font-semibold uppercase tracking-[0.12em] text-white">
                                        <svg aria-hidden="true" viewBox="0 0 20 20" class="mr-2 h-4 w-4 fill-current">
                                            <path d="M10 1.8 12.4 7l5.6.7-4.1 3.9 1 5.5-4.9-2.7-4.9 2.7 1-5.5L2 7.7 7.6 7 10 1.8Z" />
                                        </svg>
                                        {{ $plan['badge'] }}
                                    </div>
                                @endif

                                <div class="flex flex-1 flex-col p-6 {{ $isFeatured ? 'md:pt-6' : '' }}">
                                    <div class="text-center">
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0d47a1]">
                                            {{ $plan['period_label'] }}
                                        </p>
                                        <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
                                            {{ $plan['card_title'] }}
                                        </h3>
                                        <p class="content-muted mx-auto mt-3 min-h-[3rem] max-w-[17rem] text-sm leading-6">
                                            {{ $plan['card_description'] }}
                                        </p>
                                    </div>

                                    <div class="mt-5 text-center">
                                        <div class="flex items-end justify-center gap-2" aria-label="{{ $plan['formatted_price'] }}">
                                            <span
                                                class="text-4xl font-semibold leading-none tracking-tight {{ $isFeatured && $plan['available'] ? 'text-[#d01921]' : 'text-slate-950' }}"
                                            >
                                                {{ $priceParts[0] }}
                                            </span>

                                            @if ($priceParts[1] !== '')
                                                <span class="pb-1 text-base font-semibold text-slate-950">
                                                    {{ $priceParts[1] }}
                                                </span>
                                            @endif

                                            @if ($plan['available'])
                                                <span class="pb-1.5 text-xs font-semibold text-slate-500">
                                                    brutto
                                                </span>
                                            @endif
                                        </div>

                                        <span class="mt-3 inline-flex min-h-7 items-center rounded-full bg-slate-100 px-4 text-xs font-semibold text-slate-700">
                                            {{ $plan['access_days'] }} dni dostępu
                                        </span>
                                    </div>

                                    <div class="my-5 border-t border-slate-200"></div>

                                    <ul class="space-y-2.5 text-sm font-medium leading-5 text-slate-800">
                                        @foreach ($plan['features'] as $feature)
                                            <li class="flex gap-3">
                                                <svg aria-hidden="true" viewBox="0 0 20 20" class="mt-0.5 h-4 w-4 shrink-0 text-[#0d47a1]" fill="none" stroke="currentColor" stroke-width="2.4">
                                                    <path d="m4.5 10.5 3.2 3.2 7.8-8.2" />
                                                </svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-auto pt-5">
                                        @if ($actionType === 'disabled')
                                            <button
                                                type="button"
                                                class="inline-flex h-11 w-full cursor-not-allowed items-center justify-center rounded-[4px] border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-500 opacity-70"
                                                disabled
                                            >
                                                {{ $actionLabel }}
                                            </button>
                                        @elseif ($actionType === 'checkout')
                                            <form method="POST" action="{{ route('checkout.store', ['plan' => $plan['code']], false) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-11 w-full items-center justify-center rounded-[4px] px-5 text-sm font-semibold transition {{ $isFeatured ? 'bg-[#d01921] text-white hover:bg-[#b9151c]' : 'border border-slate-300 bg-white text-slate-950 hover:border-slate-400 hover:bg-slate-50' }}"
                                                >
                                                    {{ $actionLabel }}
                                                </button>
                                            </form>
                                        @else
                                            <a
                                                href="{{ $actionUrl }}"
                                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] px-5 text-sm font-semibold transition {{ $isFeatured ? 'bg-[#d01921] text-white hover:bg-[#b9151c]' : 'border border-slate-300 bg-white text-slate-950 hover:border-slate-400 hover:bg-slate-50' }}"
                                            >
                                                {{ $actionLabel }}
                                            </a>
                                        @endif

                                        <p class="mt-3 flex flex-col items-center gap-1 text-center text-xs font-medium leading-5 text-slate-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg aria-hidden="true" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="5" y="8" width="10" height="8" rx="1.5" />
                                                    <path d="M7 8V6a3 3 0 0 1 6 0v2" />
                                                </svg>
                                                Bezpieczna płatność
                                            </span>
                                            <span>Dostęp natychmiast po płatności</span>
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-8 border border-slate-200 bg-slate-50 p-6 md:p-8">
                        <h2 class="text-2xl font-semibold text-slate-950">
                            Cennik jest w przygotowaniu
                        </h2>
                        <p class="content-muted mt-3 max-w-2xl text-base leading-7">
                            Jeszcze ustawiamy cennik. Wróć później albo napisz do nas, jeśli potrzebujesz dostępu już teraz.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
