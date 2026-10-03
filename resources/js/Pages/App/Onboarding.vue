<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import roadSunset from '../../../images/onboarding/road-sunset.jpg';

const processing = ref(false);

const finishOnboarding = (destination: 'login' | 'register') => {
    if (processing.value) {
        return;
    }

    processing.value = true;
    router.post(route('app.onboarding.complete'), { destination }, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Witaj w PrawkoNaRaz">
        <meta name="robots" content="noindex,nofollow">
    </Head>

    <main class="min-h-[100svh] bg-[#071b33] text-white">
        <div class="onboarding-panel relative mx-auto min-h-[100svh] w-full max-w-[34rem] overflow-hidden bg-[#071b33] shadow-[0_0_50px_rgba(0,0,0,0.22)]">
            <img
                :src="roadSunset"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 h-full w-full object-cover object-center"
                fetchpriority="high"
            >
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(3,15,31,0.8)_0%,rgba(3,15,31,0.45)_35%,rgba(3,15,31,0.02)_58%,rgba(3,15,31,0.22)_77%,rgba(3,15,31,0.9)_100%)]" />

            <div class="onboarding-content relative z-10 flex min-h-[100svh] flex-col px-6 pb-[max(env(safe-area-inset-bottom),1.5rem)] pt-[max(env(safe-area-inset-top),1.5rem)]">
                <header class="onboarding-header flex min-h-11 items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-2.5" aria-label="PrawkoNaRaz">
                        <svg class="h-9 w-9 shrink-0 text-white" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                            <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="3" />
                            <circle cx="20" cy="20" r="3.2" fill="currentColor" />
                            <path d="M5 17.5h30M20 23v12M9.5 29 17 21.5M30.5 29 23 21.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        <span class="truncate text-lg font-bold tracking-[-0.04em]">PrawkoNa<span class="text-[#ffd84d]">Raz</span></span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-11 shrink-0 items-center px-1 text-sm font-medium text-white/90 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd84d]"
                        :disabled="processing"
                        @click="finishOnboarding('login')"
                    >
                        Pomiń
                    </button>
                </header>

                <section class="onboarding-copy mt-[clamp(2.5rem,9svh,6rem)] max-w-[21rem]" aria-labelledby="onboarding-title">
                    <h1 id="onboarding-title" class="text-[clamp(2rem,7vw,2.8rem)] font-bold leading-[1.05] tracking-[-0.045em]">
                        Zdaj prawo jazdy za pierwszym razem
                    </h1>
                    <ul class="mt-7 space-y-3 text-[0.95rem] leading-5 text-white/95">
                        <li class="flex items-center gap-3">
                            <span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="2" /><path d="m7.5 12 3.1 3.1 6-6.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </span>
                            Aktualne pytania egzaminacyjne
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 5.5C9.2 3.9 6.5 3.7 3 4.5V20c3.5-.8 6.2-.6 9 1 2.8-1.6 5.5-1.8 9-1V4.5c-3.5-.8-6.2-.6-9 1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" /><path d="M12 5.5V21" stroke="currentColor" stroke-width="2" /></svg>
                            </span>
                            Proste wyjaśnienia
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 20v-5M9 20V9M14 20v-8M19 20V5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" /></svg>
                            </span>
                            Nauka we własnym tempie
                        </li>
                    </ul>
                </section>

                <div class="onboarding-actions mt-auto pt-12">
                    <button
                        type="button"
                        class="flex min-h-14 w-full items-center justify-center gap-3 rounded-xl bg-[#ffd84d] px-5 text-base font-bold text-[#071b33] shadow-[0_10px_25px_rgba(0,0,0,0.24)] transition hover:bg-[#ffe57a] disabled:cursor-wait disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#071b33]"
                        :disabled="processing"
                        @click="finishOnboarding('register')"
                    >
                        Rozpocznij naukę <span aria-hidden="true">→</span>
                    </button>
                    <p class="mt-4 text-center text-sm text-white/90">
                        Masz już konto?
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center font-semibold text-white underline underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd84d]"
                            :disabled="processing"
                            @click="finishOnboarding('login')"
                        >
                            Zaloguj się
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
@media (orientation: landscape) and (max-height: 500px) and (min-width: 600px) {
    .onboarding-panel {
        max-width: none;
    }

    .onboarding-content {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(15rem, 0.8fr);
        grid-template-rows: auto 1fr;
        align-items: center;
        column-gap: 2rem;
    }

    .onboarding-header {
        grid-column: 1 / -1;
    }

    .onboarding-copy {
        margin-top: 0;
        max-width: 25rem;
    }

    .onboarding-copy h1 {
        font-size: clamp(1.65rem, 4vw, 2.2rem);
    }

    .onboarding-copy ul {
        margin-top: 1rem;
        gap: 0.35rem;
    }

    .onboarding-actions {
        margin-top: 0;
        padding-top: 0;
    }
}
</style>
