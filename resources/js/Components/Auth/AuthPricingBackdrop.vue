<script setup lang="ts">
import SiteHeader from '@/Components/SiteHeader.vue';

const plans = [
    {
        period: '1 miesiąc',
        title: 'Start',
        description: 'Na szybkie przygotowanie albo powtórkę przed egzaminem.',
        price: '39',
        cents: 'PLN',
        days: '30 dni dostępu',
        featured: false,
        features: [
            'Wszystkie pytania egzaminacyjne',
            'Tryb egzaminu państwowego',
            'Nauka tematami',
        ],
    },
    {
        period: '3 miesiące',
        title: 'Spokojna nauka',
        description: 'Najlepszy balans czasu i ceny.',
        price: '69',
        cents: 'PLN',
        days: '90 dni dostępu',
        featured: true,
        badge: 'Najczęściej wybierany',
        features: [
            'Wszystko z planu Start',
            'Statystyki postępów',
            'Inteligentne powtórki błędów',
        ],
    },
    {
        period: 'Rok',
        title: 'Premium',
        description: 'Dostęp bez presji przez cały rok.',
        price: '149',
        cents: 'PLN',
        days: '365 dni dostępu',
        featured: false,
        features: [
            'Wszystko z planu 3 miesiące',
            'Nauka we własnym tempie',
            'Najlepszy wybór cenowy',
        ],
    },
];
</script>

<template>
    <div class="min-h-screen bg-[#f7f8fb] text-[#111827]">
        <SiteHeader />

        <main class="mx-auto max-w-[81.75rem] px-4 py-8 sm:px-6 lg:py-10 xl:px-8">
            <section class="mx-auto max-w-2xl text-center">
                <p class="text-[0.72rem] font-bold uppercase tracking-[0.16em] text-[#d01921]">
                    Cennik prawkonaraz.pl
                </p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Wybierz plan nauki
                </h1>
                <p class="mx-auto mt-3 max-w-xl text-sm font-medium leading-6 text-slate-600 md:text-base md:leading-7">
                    Miesiąc, 3 miesiące albo rok. Pełny dostęp do nauki i powtórek w każdym planie.
                </p>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-3 lg:items-stretch">
                <article
                    v-for="plan in plans"
                    :key="plan.title"
                    class="relative flex min-h-[29rem] flex-col rounded-[8px] border bg-white transition"
                    :class="plan.featured
                        ? 'border-[#0d47a1] shadow-[0_18px_40px_rgba(13,71,161,0.12)] ring-1 ring-[#0d47a1] lg:-mt-4'
                        : 'border-slate-200 shadow-[0_12px_28px_rgba(15,23,42,0.05)]'"
                >
                    <div
                        v-if="plan.featured"
                        class="flex h-10 items-center justify-center rounded-t-[7px] bg-[#0d47a1] px-4 text-xs font-semibold uppercase tracking-[0.12em] text-white"
                    >
                        <svg aria-hidden="true" viewBox="0 0 20 20" class="mr-2 h-4 w-4 fill-current">
                            <path d="M10 1.8 12.4 7l5.6.7-4.1 3.9 1 5.5-4.9-2.7-4.9 2.7 1-5.5L2 7.7 7.6 7 10 1.8Z" />
                        </svg>
                        {{ plan.badge }}
                    </div>

                    <div class="flex flex-1 flex-col p-6">
                        <div class="text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0d47a1]">
                                {{ plan.period }}
                            </p>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
                                {{ plan.title }}
                            </h2>
                            <p class="mx-auto mt-3 min-h-[3rem] max-w-[17rem] text-sm leading-6 text-slate-600">
                                {{ plan.description }}
                            </p>
                        </div>

                        <div class="mt-5 text-center">
                            <div class="flex items-end justify-center gap-2">
                                <span
                                    class="text-4xl font-semibold leading-none tracking-tight"
                                    :class="plan.featured ? 'text-[#d01921]' : 'text-slate-950'"
                                >
                                    {{ plan.price }}
                                </span>
                                <span class="pb-1 text-base font-semibold text-slate-950">
                                    {{ plan.cents }}
                                </span>
                                <span class="pb-1.5 text-xs font-semibold text-slate-500">
                                    brutto
                                </span>
                            </div>

                            <span class="mt-3 inline-flex min-h-7 items-center rounded-full bg-slate-100 px-4 text-xs font-semibold text-slate-700">
                                {{ plan.days }}
                            </span>
                        </div>

                        <div class="my-5 border-t border-slate-200" />

                        <ul class="space-y-2.5 text-sm font-medium leading-5 text-slate-800">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex gap-3"
                            >
                                <svg aria-hidden="true" viewBox="0 0 20 20" class="mt-0.5 h-4 w-4 shrink-0 text-[#0d47a1]" fill="none" stroke="currentColor" stroke-width="2.4">
                                    <path d="m4.5 10.5 3.2 3.2 7.8-8.2" />
                                </svg>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <div class="mt-auto pt-5">
                            <span
                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] px-5 text-sm font-semibold transition"
                                :class="plan.featured
                                    ? 'bg-[#d01921] text-white'
                                    : 'border border-slate-300 bg-white text-slate-950'"
                            >
                                {{ plan.featured ? 'Wybieram ten plan' : 'Rozpocznij naukę' }}
                            </span>
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
            </section>
        </main>
    </div>
</template>
