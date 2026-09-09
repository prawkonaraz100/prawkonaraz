<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    order: {
        public_id: string;
        status: string;
        provider: string;
        formatted_amount: string;
        access_days: number;
        plan: {
            code: string | null;
            name: string | null;
            description: string | null;
        };
    };
    sandboxEnabled: boolean;
    pricingUrl: string;
}>();

const form = useForm({});

const completeSandboxPayment = () => {
    form.post(route('checkout.sandbox.complete', { order: props.order.public_id }));
};

const cancelOrder = () => {
    form.post(route('checkout.cancel', { order: props.order.public_id }));
};
</script>

<template>
    <Head title="Checkout" />

    <div class="flex min-h-screen flex-col bg-[#fbfaf7] text-[#211f1b]">
        <PublicTopNavigation />

        <main class="flex-1">
            <section class="mx-auto grid min-h-[calc(100svh-170px)] w-full max-w-[76rem] items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
                <div>
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.26em] text-[#8a6b33]">
                        Checkout
                    </p>

                    <h1 class="mt-5 text-4xl font-semibold tracking-[-0.045em] text-[#181713] sm:text-5xl">
                        Potwierdź zakup dostępu
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-8 text-[#625b50]">
                        Zamówienie jest przypisane do Twojego konta. Dostęp do produktu zostanie aktywowany dopiero po potwierdzeniu płatności po stronie serwera.
                    </p>
                </div>

                <aside class="rounded-[1.75rem] border border-[#e1d8c9] bg-white p-6 shadow-[0_24px_70px_rgba(42,36,24,0.09)] sm:p-8">
                    <div class="border-b border-[#eee7dc] pb-6">
                        <p class="text-sm font-medium text-[#756d61]">
                            Zamówienie {{ order.public_id }}
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-[#181713]">
                            {{ order.plan.name }}
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-[#625b50]">
                            {{ order.plan.description }}
                        </p>
                    </div>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-5">
                            <dt class="text-[#756d61]">Kwota</dt>
                            <dd class="font-semibold text-[#211f1b]">
                                {{ order.formatted_amount }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-5">
                            <dt class="text-[#756d61]">Dostęp</dt>
                            <dd class="font-semibold text-[#211f1b]">
                                {{ order.access_days }} dni
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-5">
                            <dt class="text-[#756d61]">Operator</dt>
                            <dd class="font-semibold text-[#211f1b]">
                                {{ order.provider }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-5">
                            <dt class="text-[#756d61]">Status</dt>
                            <dd class="font-semibold text-[#8a5d00]">
                                {{ order.status }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        v-if="sandboxEnabled"
                        class="mt-7 rounded-2xl bg-[#f8f5ee] px-5 py-4 text-sm leading-6 text-[#625b50]"
                    >
                        Tryb sandbox jest włączony. To techniczne potwierdzenie płatności do czasu podpięcia finalnego operatora.
                    </div>
                    <div
                        v-else
                        class="mt-7 rounded-2xl bg-[#fff4d8] px-5 py-4 text-sm leading-6 text-[#7a5200]"
                    >
                        Finalny operator płatności nie jest jeszcze skonfigurowany. Zamówienie pozostaje oczekujące.
                    </div>

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        <button
                            v-if="sandboxEnabled"
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-full bg-[#1f2a2e] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#101719] disabled:cursor-not-allowed disabled:opacity-55"
                            :disabled="form.processing"
                            @click="completeSandboxPayment"
                        >
                            Potwierdź płatność testową
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d8cfbf] px-6 py-3 text-sm font-semibold text-[#1f1d18] transition hover:bg-[#f6f1e8] disabled:cursor-not-allowed disabled:opacity-55"
                            :disabled="form.processing"
                            @click="cancelOrder"
                        >
                            Anuluj
                        </button>
                    </div>

                    <a
                        :href="pricingUrl"
                        class="mt-4 inline-flex text-sm font-semibold text-[#6d654f] underline underline-offset-4 transition hover:text-[#1f1d18]"
                    >
                        Wróć do cennika
                    </a>
                </aside>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
