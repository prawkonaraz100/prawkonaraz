<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import type { PageProps } from '@/types';
import friendInvitationHero from '../../../images/invitations/friend-invitation-hero.png';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    invitation: {
        active: boolean;
        inviter_name?: string | null;
        access_expires_at?: string | null;
        invitation_expires_at?: string | null;
        inactive_reason?: string | null;
    };
    status?: string;
    loginUrl: string;
    registerUrl: string;
    codeUrl: string;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const form = useForm({});
const formErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const formatDate = (value?: string | null) => {
    if (!value) {
        return 'brak daty';
    }

    return new Intl.DateTimeFormat('pl-PL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const title = computed(() => {
    if (!props.invitation.active) {
        return 'To zaproszenie jest nieaktywne';
    }

    return `${props.invitation.inviter_name ?? 'Znajomy'} Cię zaprasza`;
});

const accept = () => {
    form.post(route('friend-invitations.pending.accept'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Zaproszenie do nauki" />

    <div class="flex min-h-screen flex-col bg-white text-[#081331]">
        <SiteHeader />

        <main class="flex-1">
            <section class="border-b border-[#e4eaf3] bg-[#fbfcff]">
                <div class="mx-auto grid max-w-[90rem] gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(18rem,0.55fr)] lg:items-center lg:py-12 xl:px-8">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                            Zaproszenie Premium
                        </p>
                        <h1 class="mt-3 max-w-4xl text-[2rem] font-bold leading-[1.12] tracking-[0] text-[#081331] md:text-[2.6rem]">
                            {{ title }}
                        </h1>
                        <p v-if="invitation.active" class="mt-4 max-w-3xl text-[1rem] font-medium leading-7 text-[#4e5b78] md:text-[1.08rem]">
                            Przyjmij zaproszenie, żeby korzystać z nauki Premium do końca okresu osoby zapraszającej.
                        </p>
                        <p v-else class="mt-4 max-w-3xl text-[1rem] font-medium leading-7 text-[#4e5b78] md:text-[1.08rem]">
                            {{ invitation.inactive_reason ?? 'Link albo kod wygasł, został unieważniony albo slot zajął już inny znajomy.' }}
                        </p>
                        <p
                            v-if="status"
                            class="mt-5 border-l-2 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
                        >
                            {{ status }}
                        </p>
                    </div>

                    <div class="hidden min-h-[12rem] items-end justify-center lg:flex">
                        <img
                            :src="friendInvitationHero"
                            alt=""
                            class="h-[15rem] w-full max-w-[26rem] object-contain object-right-bottom"
                        >
                    </div>
                </div>
            </section>

            <section>
                <div class="mx-auto grid max-w-[90rem] gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:py-10 xl:px-8">
                    <div class="min-w-0">
                        <div v-if="invitation.active" class="divide-y divide-[#e4eaf3] border-y border-[#e4eaf3]">
                            <div class="grid gap-5 py-5 md:grid-cols-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                                        Zaprasza
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-[#081331]">
                                        {{ invitation.inviter_name ?? 'Znajomy' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                                        Dostęp do
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-[#081331]">
                                        {{ formatDate(invitation.access_expires_at) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                                        Link ważny do
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-[#081331]">
                                        {{ formatDate(invitation.invitation_expires_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="py-5">
                                <p class="text-sm font-medium leading-6 text-[#4e5b78]">
                                    Uwaga: nie możesz przyjąć zaproszenia, jeśli masz już aktywny plan Premium albo jesteś gościem u innej osoby.
                                </p>
                            </div>

                            <div class="py-6">
                                <form v-if="user" @submit.prevent="accept">
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-11 items-center justify-center rounded-[6px] bg-[#e11d2e] px-6 text-sm font-bold text-white transition hover:bg-[#b51222] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        {{ form.processing ? 'Aktywowanie...' : 'Akceptuj i zacznij' }}
                                    </button>

                                    <p v-if="formErrors.invitation" class="mt-3 text-sm font-bold text-[#b51222]">
                                        {{ formErrors.invitation }}
                                    </p>
                                </form>

                                <div v-else class="flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        :href="loginUrl"
                                        class="inline-flex min-h-11 items-center justify-center rounded-[6px] bg-[#e11d2e] px-6 text-sm font-bold text-white transition hover:bg-[#b51222] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2"
                                    >
                                        Zaloguj się i zaakceptuj
                                    </Link>
                                    <Link
                                        :href="registerUrl"
                                        class="inline-flex min-h-11 items-center justify-center rounded-[6px] border border-[#c9d2e3] px-6 text-sm font-bold text-[#081331] transition hover:border-[#9aa8bd] hover:bg-[#f7faff] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#081331] focus-visible:ring-offset-2"
                                    >
                                        Utwórz konto
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="border-y border-[#e4eaf3] py-6">
                            <p class="text-sm font-medium leading-6 text-[#4e5b78]">
                                Poproś osobę zapraszającą o wygenerowanie nowego zaproszenia albo wpisz kod, jeśli dostałeś inny.
                            </p>
                            <Link
                                :href="codeUrl"
                                class="mt-5 inline-flex min-h-11 items-center justify-center rounded-[6px] border border-[#c9d2e3] px-6 text-sm font-bold text-[#081331] transition hover:border-[#9aa8bd] hover:bg-[#f7faff] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#081331] focus-visible:ring-offset-2"
                            >
                                Mam kod zaproszenia
                            </Link>
                        </div>
                    </div>

                    <aside class="border-y border-[#e4eaf3] py-5 lg:self-start">
                        <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                            Ważność
                        </p>
                        <p class="mt-2 text-sm font-medium leading-6 text-[#4e5b78]">
                            Zaproszenie oczekujące wygasa po 14 dniach od wygenerowania.
                        </p>
                        <p v-if="invitation.active" class="mt-3 text-sm font-bold text-[#081331]">
                            Link ważny do {{ formatDate(invitation.invitation_expires_at) }}.
                        </p>
                    </aside>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
