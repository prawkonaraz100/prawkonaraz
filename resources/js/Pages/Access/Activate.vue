<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    access: {
        allowed: boolean;
        reason: string | null;
        source: string | null;
    };
    pricingUrl: string;
    contactUrl: string;
}>();

const accessStatusLabel = computed(() =>
    props.access.allowed ? 'Aktywny dostęp' : 'Do aktywacji',
);

const accessSourceLabel = computed(() => props.access.source ?? 'Brak');

const accessReasonLabel = computed(() => {
    if (props.access.reason === 'missing_access') {
        return 'Brak aktywnego planu';
    }

    return props.access.reason ?? 'Nie dotyczy';
});

const nextSteps = [
    {
        title: 'Wybierz plan',
        description:
            'Przejdź do cennika i wybierz dostęp na miesiąc, trzy miesiące albo rok.',
    },
    {
        title: 'Aktywuj dostęp',
        description:
            'Po potwierdzeniu zakupu odblokujemy naukę, powtórki, statystyki i ranking.',
    },
    {
        title: 'Wróć do nauki',
        description:
            'Po aktywacji konto wraca do normalnego flow bez dodatkowej konfiguracji.',
    },
];
</script>

<template>
    <Head title="Aktywuj dostęp" />

    <div class="flex min-h-screen flex-col bg-white text-slate-950">
        <PublicTopNavigation />

        <main class="flex-1">
            <section class="border-b border-slate-200">
                <div class="mx-auto max-w-[75rem] px-4 py-10 sm:px-6 lg:px-0 lg:py-12">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                        Aktywacja dostępu
                    </p>

                    <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                        Konto jest gotowe. Teraz wybierz dostęp do nauki.
                    </h1>

                    <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
                        Pełna nauka jest zablokowana do czasu aktywacji planu.
                        Wybierz dostęp w cenniku albo napisz do nas, jeśli
                        dostęp powinien zostać przypisany ręcznie.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            :href="pricingUrl"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-[#0d47a1] px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-[#0d47a1] focus:ring-offset-2"
                        >
                            Przejdź do cennika
                        </a>

                        <a
                            :href="contactUrl"
                            class="inline-flex h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-950 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                        >
                            Napisz do nas
                        </a>
                    </div>
                </div>
            </section>

            <section>
                <div class="mx-auto grid max-w-[75rem] gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-0 lg:py-12">
                    <div>
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                                    Co dalej?
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                                    Aktywacja w trzech krokach
                                </h2>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                    Bez ustawiania wszystkiego od nowa. Po
                                    opłaceniu planu wracasz do nauki na swoim
                                    koncie i przypisanej kategorii.
                                </p>
                            </div>
                        </div>

                        <ol class="mt-8 divide-y divide-slate-200 border-y border-slate-200">
                            <li
                                v-for="(step, index) in nextSteps"
                                :key="step.title"
                                class="grid gap-4 py-5 sm:grid-cols-[3rem_minmax(0,1fr)]"
                            >
                                <span class="text-sm font-semibold text-slate-400">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">
                                        {{ step.title }}
                                    </h3>
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                        {{ step.description }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <aside class="lg:sticky lg:top-28 lg:self-start">
                        <div class="divide-y divide-slate-200 border-y border-slate-200">
                            <div class="py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">
                                    Status
                                </p>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-950">
                                    {{ accessStatusLabel }}
                                </p>
                            </div>

                            <div class="py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">
                                    Źródło
                                </p>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-950">
                                    {{ accessSourceLabel }}
                                </p>
                            </div>

                            <div class="py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">
                                    Powód
                                </p>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-950">
                                    {{ accessReasonLabel }}
                                </p>
                            </div>
                        </div>

                        <p class="mt-6 text-sm leading-6 text-slate-600">
                            Konta testowe, administratorzy i moderatorzy nie
                            muszą kupować planu. Jeśli Twoje konto powinno mieć
                            dostęp nadany ręcznie, skontaktuj się z nami.
                        </p>
                    </aside>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
