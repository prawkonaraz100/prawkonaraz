<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';
import { useSafeLogout } from '@/composables/useSafeLogout';
import { Head, Link } from '@inertiajs/vue3';

const safetyNotes = [
    'Chronimy Cię przed przypadkowym wylogowaniem.',
    'Zewnętrzne strony nie mogą wylogować Cię zwykłym linkiem.',
    'Działamy zgodnie z zasadami bezpieczeństwa aplikacji web.',
];

const {
    logout,
    logoutError,
    logoutPreparing,
} = useSafeLogout();
</script>

<template>
    <Head title="Potwierdź wylogowanie" />

    <div class="flex min-h-screen flex-col bg-[#f8fafc] text-[#0f172a]">
        <PublicTopNavigation />

        <main class="flex-1">
            <section class="mx-auto max-w-[82rem] px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
                <div class="flex items-start gap-5">
                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-[18px] bg-[#2563eb] text-white shadow-[0_16px_34px_rgba(37,99,235,0.2)]">
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 5 6v5c0 4.6 2.9 8.4 7 10 4.1-1.6 7-5.4 7-10V6l-7-3Z" />
                            <path d="M9 12h6v5H9z" />
                            <path d="M10 12v-1.5a2 2 0 0 1 4 0V12" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-[0.72rem] font-bold uppercase tracking-[0.16em] text-[#2563eb]">
                            Bezpieczne wylogowanie
                        </p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#0f172a] sm:text-4xl lg:text-5xl">
                            Jak działa wylogowanie?
                        </h1>
                        <p class="mt-3 max-w-3xl text-base font-medium leading-7 text-[#475569]">
                            Dla Twojego bezpieczeństwa wylogowanie wymaga potwierdzenia. Samo wpisanie adresu w przeglądarce nie kończy sesji.
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <article class="rounded-[10px] border border-[#dbe4f0] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-7">
                        <div class="flex items-start gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#2563eb] text-lg font-bold text-white">
                                1
                            </span>
                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-[#0f172a]">
                                    Wejście na <span class="text-[#2563eb]">/logout</span>
                                </h2>
                                <p class="mt-2 text-sm font-medium leading-6 text-[#475569]">
                                    Gdy wpiszesz adres w przeglądarce lub klikniesz zwykły link:
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-[8px] border border-[#dbe4f0] bg-[#f8fafc] px-4 py-3 shadow-inner">
                            <div class="flex items-center gap-2 text-[#64748b]">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-sm">←</span>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-sm">→</span>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-sm">↻</span>
                                <div class="flex min-w-0 flex-1 items-center gap-2 rounded-[6px] bg-white px-3 py-2 text-sm font-semibold text-[#334155]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-[#64748b]" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="5" y="11" width="14" height="10" rx="2" />
                                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                                    </svg>
                                    <span class="truncate">https://prawkonaraz.pl/logout</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-center">
                            <span class="inline-flex min-h-10 items-center rounded-[7px] bg-emerald-50 px-5 text-base font-bold text-emerald-600">
                                GET /logout
                            </span>
                        </div>

                        <div class="mt-5 rounded-[8px] border border-[#dbe4f0] bg-white p-5 text-center shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full border border-[#93c5fd] bg-white text-[#2563eb]">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="6" y="10" width="12" height="10" rx="2" />
                                    <path d="M8 10V8a4 4 0 0 1 8 0v2" />
                                </svg>
                            </span>

                            <h3 class="mt-4 text-xl font-bold text-[#0f172a]">
                                Potwierdź wylogowanie
                            </h3>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#475569]">
                                Dla bezpieczeństwa wymagamy potwierdzenia tej operacji. Czy na pewno chcesz zakończyć bieżącą sesję?
                            </p>

                            <div class="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    class="inline-flex h-11 min-w-36 items-center justify-center rounded-[6px] bg-[#d01921] px-5 text-sm font-bold text-white transition hover:bg-[#b9151c] focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                                    :disabled="logoutPreparing"
                                    @click="logout(route('logout'))"
                                >
                                    {{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj się' }}
                                </button>
                                <Link
                                    :href="route('dashboard')"
                                    class="inline-flex h-11 min-w-36 items-center justify-center rounded-[6px] border border-[#b8c5d8] bg-white px-5 text-sm font-bold text-[#2563eb] transition hover:border-[#2563eb] hover:bg-[#f8fafc] focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                                >
                                    Anuluj
                                </Link>
                            </div>
                            <p
                                v-if="logoutError"
                                class="mt-4 text-sm font-semibold text-red-700"
                                role="alert"
                            >
                                {{ logoutError }}
                            </p>
                        </div>

                        <div class="mt-4 flex gap-3 rounded-[8px] border border-[#dbeafe] bg-[#eff6ff] px-4 py-3 text-sm leading-6 text-[#1e3a8a]">
                            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full border border-[#60a5fa] bg-white text-base font-bold">i</span>
                            <p>
                                Na tym etapie <strong>nie zostajesz wylogowany.</strong>
                                Twoja sesja nadal jest aktywna.
                            </p>
                        </div>
                    </article>

                    <article class="rounded-[10px] border border-[#dbe4f0] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-7">
                        <div class="flex items-start gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-500 text-lg font-bold text-white">
                                2
                            </span>
                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-[#0f172a]">
                                    Kliknięcie „Wyloguj się”
                                </h2>
                                <p class="mt-2 text-sm font-medium leading-6 text-[#475569]">
                                    Dopiero po kliknięciu przycisku wykonywane jest żądanie:
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <span class="inline-flex min-h-10 items-center rounded-[7px] bg-emerald-50 px-5 text-base font-bold text-emerald-600">
                                POST /logout
                            </span>
                        </div>

                        <div class="mt-5 grid gap-5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-center">
                            <div class="relative mx-auto grid h-28 w-28 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="4" width="16" height="5" rx="1.5" />
                                    <rect x="4" y="10" width="16" height="5" rx="1.5" />
                                    <rect x="4" y="16" width="16" height="4" rx="1.5" />
                                    <path d="M7 6.5h.01M7 12.5h.01M7 18h.01" />
                                </svg>
                            </div>

                            <ul class="space-y-3 text-sm font-semibold leading-6 text-[#334155]">
                                <li class="flex gap-3">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-emerald-400 text-emerald-600">✓</span>
                                    Sesja zostaje unieważniona
                                </li>
                                <li class="flex gap-3">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-emerald-400 text-emerald-600">✓</span>
                                    Token jest odświeżany
                                </li>
                                <li class="flex gap-3">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-emerald-400 text-emerald-600">✓</span>
                                    Zostajesz wylogowany
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 rounded-[8px] border border-[#dbe4f0] bg-white p-8 text-center shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
                            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-500 text-white">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 13 4 4L19 7" />
                                </svg>
                            </span>

                            <h3 class="mt-4 text-xl font-bold text-[#0f172a]">
                                Zostałeś wylogowany
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#475569]">
                                Dziękujemy za korzystanie z serwisu.
                            </p>
                            <a href="/" class="mt-3 inline-flex text-sm font-bold text-[#2563eb] transition hover:text-[#1d4ed8]">
                                Przejdź na stronę główną
                            </a>
                        </div>
                    </article>
                </div>

                <aside class="mt-6 rounded-[10px] border border-[#fde68a] bg-[#fffbeb] p-5 shadow-[0_14px_34px_rgba(120,53,15,0.05)] sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
                        <div class="flex gap-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-[14px] bg-amber-400 text-white">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3 5 6v5c0 4.6 2.9 8.4 7 10 4.1-1.6 7-5.4 7-10V6l-7-3Z" />
                                    <path d="m9 12 2 2 4-5" />
                                </svg>
                            </span>

                            <div>
                                <h2 class="text-base font-bold text-[#0f172a]">
                                    Dlaczego tak działamy?
                                </h2>
                                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-[#334155]">
                                    Adres wpisany w pasek przeglądarki wykonuje żądanie GET, które zgodnie z dobrymi praktykami nie powinno zmieniać stanu konta. Wylogowanie zmienia stan konta, dlatego wymaga świadomego potwierdzenia i jest wykonywane bezpiecznym żądaniem POST.
                                </p>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm font-semibold leading-6 text-[#334155]">
                            <li
                                v-for="note in safetyNotes"
                                :key="note"
                                class="flex gap-3"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#e0ecff] text-[#2563eb]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m5 13 4 4L19 7" />
                                    </svg>
                                </span>
                                {{ note }}
                            </li>
                        </ul>
                    </div>
                </aside>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
