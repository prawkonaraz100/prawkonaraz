<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    order: {
        public_id: string;
        formatted_amount: string;
        access_days: number;
        plan: {
            name: string | null;
        };
    };
    status: 'paid';
    accessExpiresAt: string | null;
    sessionUrl: string;
    friendInvitationCta: {
        available: boolean;
        profile_url: string;
    };
}>();
</script>

<template>
    <Head title="Dostęp aktywowany" />

    <div class="flex min-h-screen flex-col bg-[#fbfaf7] text-[#211f1b]">
        <SiteHeader />

        <main class="flex-1">
            <section class="mx-auto flex min-h-[calc(100svh-170px)] w-full max-w-[68rem] items-center px-4 py-12 sm:px-6 lg:px-8">
                <div class="w-full rounded-[2rem] border border-[#e1d8c9] bg-white p-8 text-center shadow-[0_26px_70px_rgba(42,36,24,0.1)] sm:p-10">
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.26em] text-[#3f7d4f]">
                        Płatność potwierdzona
                    </p>

                    <h1 class="mx-auto mt-5 max-w-2xl text-4xl font-semibold tracking-[-0.045em] text-[#181713] sm:text-5xl">
                        Dostęp do nauki jest aktywny
                    </h1>

                    <p class="mx-auto mt-5 max-w-xl text-base leading-8 text-[#625b50]">
                        Zamówienie {{ order.public_id }} zostało opłacone. Możesz przejść do produktu i rozpocząć naukę.
                    </p>

                    <dl class="mx-auto mt-8 grid max-w-xl gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-2xl bg-[#f8f5ee] px-4 py-4">
                            <dt class="text-[#756d61]">Plan</dt>
                            <dd class="mt-1 font-semibold text-[#181713]">
                                {{ order.plan.name }}
                            </dd>
                        </div>
                        <div class="rounded-2xl bg-[#f8f5ee] px-4 py-4">
                            <dt class="text-[#756d61]">Kwota</dt>
                            <dd class="mt-1 font-semibold text-[#181713]">
                                {{ order.formatted_amount }}
                            </dd>
                        </div>
                        <div class="rounded-2xl bg-[#f8f5ee] px-4 py-4">
                            <dt class="text-[#756d61]">Dostęp</dt>
                            <dd class="mt-1 font-semibold text-[#181713]">
                                {{ order.access_days }} dni
                            </dd>
                        </div>
                    </dl>

                    <Link
                        :href="sessionUrl"
                        class="mt-9 inline-flex items-center justify-center rounded-full bg-[#1f2a2e] px-7 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(31,42,46,0.16)] transition hover:bg-[#101719]"
                    >
                        Przejdź do nauki
                    </Link>

                    <div
                        v-if="friendInvitationCta.available"
                        class="mx-auto mt-7 max-w-xl border-t border-[#ece4d8] pt-6"
                    >
                        <p class="text-sm leading-6 text-[#625b50]">
                            Ten plan pozwala zaprosić jedną osobę do wspólnej nauki.
                        </p>
                        <Link
                            :href="friendInvitationCta.profile_url"
                            class="mt-3 inline-flex items-center justify-center rounded-full border border-[#d9cfbd] px-5 py-2.5 text-sm font-semibold text-[#211f1b] transition hover:border-[#b9aa92] hover:bg-[#f8f5ee]"
                        >
                            Przejdź do zaproszenia
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
