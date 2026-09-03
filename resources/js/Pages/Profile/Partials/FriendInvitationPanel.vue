<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface PendingInvitation {
    public_id: string;
    created_at: string | null;
    expires_at: string | null;
    display_code_last4: string | null;
}

interface ActiveGuest {
    public_id: string;
    name: string | null;
    email: string | null;
    access_expires_at: string | null;
    accepted_at: string | null;
}

interface GeneratedInvitation {
    public_id: string;
    link: string;
    code: string;
    expires_at: string | null;
}

const props = defineProps<{
    invitations: {
        eligible: boolean;
        can_issue: boolean;
        reason: string | null;
        reason_label: string | null;
        pending_limit: number;
        pending_count: number;
        owner_access_expires_at: string | null;
        plan: {
            code: string | null;
            name: string | null;
        };
        active_guest: ActiveGuest | null;
        pending: PendingInvitation[];
        generated: GeneratedInvitation | null;
    };
    status?: string;
    mobileSheet?: boolean;
}>();

const copiedValue = ref<'link' | 'code' | null>(null);

const form = useForm({});
const formErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const formatDate = (value: string | null) => {
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

const canCopy = computed(() => typeof navigator !== 'undefined' && !!navigator.clipboard);

const copy = async (value: string, type: 'link' | 'code') => {
    if (!canCopy.value) {
        return;
    }

    await navigator.clipboard.writeText(value);
    copiedValue.value = type;
    window.setTimeout(() => {
        if (copiedValue.value === type) {
            copiedValue.value = null;
        }
    }, 1800);
};

const generateInvitation = () => {
    form.post(route('friend-invitations.store'), {
        preserveScroll: true,
    });
};

const revokeInvitation = (invitation: PendingInvitation) => {
    router.delete(route('friend-invitations.destroy', { friendInvitation: invitation.public_id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Dostęp Premium
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                Zaproś znajomego
            </h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Właściciele planu 3 miesiące albo rok mogą udostępnić jeden slot nauki znajomej osobie.
            </p>
        </header>

        <p v-else class="text-sm leading-6 text-[#667085]">
            Plan 3-miesięczny lub roczny pozwala udostępnić jeden slot nauki znajomej osobie.
        </p>

        <div :class="props.mobileSheet ? 'mt-5 space-y-4' : 'mt-8 divide-y divide-slate-200 border-y border-slate-200'">
            <div
                class="grid sm:grid-cols-3"
                :class="props.mobileSheet ? 'grid-cols-3 divide-x divide-[#eaecf0] overflow-hidden rounded-lg bg-[#f7f8fa]' : 'gap-4 py-5'"
            >
                <div :class="props.mobileSheet ? 'min-w-0 px-2 py-3 text-center' : ''">
                    <p :class="props.mobileSheet ? 'text-[0.68rem] font-medium text-[#667085]' : 'text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]'">
                        Plan
                    </p>
                    <p class="font-semibold text-slate-950" :class="props.mobileSheet ? 'mt-1 text-[0.78rem] leading-4' : 'mt-2 text-sm'">
                        {{ invitations.plan.name ?? 'Brak kwalifikującego planu' }}
                    </p>
                </div>
                <div :class="props.mobileSheet ? 'min-w-0 px-2 py-3 text-center' : ''">
                    <p :class="props.mobileSheet ? 'text-[0.68rem] font-medium text-[#667085]' : 'text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]'">
                        Slot
                    </p>
                    <p class="font-semibold text-slate-950" :class="props.mobileSheet ? 'mt-1 text-[0.82rem]' : 'mt-2 text-sm'">
                        {{ invitations.active_guest ? 'Zajęty' : 'Wolny' }}
                    </p>
                </div>
                <div :class="props.mobileSheet ? 'min-w-0 px-2 py-3 text-center' : ''">
                    <p :class="props.mobileSheet ? 'text-[0.68rem] font-medium text-[#667085]' : 'text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]'">
                        Oczekujące
                    </p>
                    <p class="font-semibold text-slate-950" :class="props.mobileSheet ? 'mt-1 text-[0.82rem]' : 'mt-2 text-sm'">
                        {{ invitations.pending_count }} / {{ invitations.pending_limit }}
                    </p>
                </div>
            </div>

            <div
                v-if="props.status === 'friend-invitation-created'"
                :class="props.mobileSheet ? 'rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-700' : 'py-5 text-sm font-semibold text-emerald-700'"
            >
                Zaproszenie zostało wygenerowane.
            </div>

            <div
                v-if="props.status === 'friend-invitation-revoked'"
                :class="props.mobileSheet ? 'rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-700' : 'py-5 text-sm font-semibold text-emerald-700'"
            >
                Zaproszenie zostało unieważnione.
            </div>

            <div v-if="invitations.generated" :class="props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6'">
                <p class="text-sm font-semibold text-slate-950">
                    Nowe zaproszenie
                </p>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Skopiuj link albo kod teraz. Dla bezpieczeństwa nie pokazujemy pełnego linku ponownie po odświeżeniu strony.
                </p>

                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem]">
                    <div>
                        <label for="friend-invitation-link" class="text-sm font-semibold text-slate-950">
                            Link
                        </label>
                        <input
                            id="friend-invitation-link"
                            :value="invitations.generated.link"
                            readonly
                            class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-sm text-slate-950 outline-none sm:h-11 sm:rounded-md"
                        >
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg border border-[#d0d5dd] bg-white px-5 text-sm font-semibold text-[#344054] transition hover:bg-white disabled:opacity-60 sm:h-11 sm:rounded-md"
                        :class="props.mobileSheet ? 'w-full' : 'mt-7'"
                        :disabled="!canCopy"
                        @click="copy(invitations.generated.link, 'link')"
                    >
                        {{ copiedValue === 'link' ? 'Skopiowano' : 'Kopiuj link' }}
                    </button>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem]">
                    <div>
                        <label for="friend-invitation-code" class="text-sm font-semibold text-slate-950">
                            Kod
                        </label>
                        <input
                            id="friend-invitation-code"
                            :value="invitations.generated.code"
                            readonly
                            class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-sm font-semibold text-slate-950 outline-none sm:h-11 sm:rounded-md"
                        >
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg border border-[#d0d5dd] bg-white px-5 text-sm font-semibold text-[#344054] transition hover:bg-white disabled:opacity-60 sm:h-11 sm:rounded-md"
                        :class="props.mobileSheet ? 'w-full' : 'mt-7'"
                        :disabled="!canCopy"
                        @click="copy(invitations.generated.code, 'code')"
                    >
                        {{ copiedValue === 'code' ? 'Skopiowano' : 'Kopiuj kod' }}
                    </button>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-600">
                    Zaproszenie wygaśnie: {{ formatDate(invitations.generated.expires_at) }}.
                </p>
            </div>

            <div v-if="invitations.active_guest" :class="props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6'">
                <p class="text-sm font-semibold text-slate-950">
                    Slot zajęty
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Gość:
                    <strong class="font-semibold text-slate-950">
                        {{ invitations.active_guest.name ?? invitations.active_guest.email ?? 'Użytkownik' }}
                    </strong>
                    ma dostęp do {{ formatDate(invitations.active_guest.access_expires_at) }}.
                </p>
            </div>

            <div v-else :class="props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6'">
                <p v-if="invitations.reason_label" class="max-w-3xl text-sm leading-6 text-slate-600">
                    {{ invitations.reason_label }}
                </p>

                <form
                    v-if="!props.mobileSheet || invitations.can_issue"
                    :class="invitations.reason_label ? 'mt-4' : ''"
                    @submit.prevent="generateInvitation"
                >
                    <button
                        type="submit"
                        class="inline-flex h-12 items-center justify-center rounded-lg bg-[#0b5cff] px-6 text-sm font-semibold text-white transition hover:bg-[#084fdc] disabled:cursor-not-allowed disabled:opacity-50"
                        :class="props.mobileSheet ? 'w-full' : 'sm:h-11 sm:w-auto sm:rounded-md sm:bg-[#0d47a1]'"
                        :disabled="!invitations.can_issue || form.processing"
                    >
                        {{ form.processing ? 'Generowanie...' : 'Wygeneruj zaproszenie' }}
                    </button>

                    <InputError class="mt-3" :message="formErrors.invitation" />
                </form>
            </div>

            <div v-if="invitations.pending.length > 0" :class="props.mobileSheet ? 'pt-1' : 'py-6'">
                <p class="text-sm font-semibold text-slate-950">
                    Oczekujące zaproszenia
                </p>
                <div class="mt-4 divide-y divide-slate-200" :class="props.mobileSheet ? 'overflow-hidden rounded-lg bg-[#f7f8fa] px-4' : 'border-y border-slate-200'">
                    <div
                        v-for="invitation in invitations.pending"
                        :key="invitation.public_id"
                        class="grid gap-4 py-4 sm:grid-cols-[minmax(0,1fr)_12rem]"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-950">
                                Wygasa {{ formatDate(invitation.expires_at) }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Utworzone {{ formatDate(invitation.created_at) }}
                                <template v-if="invitation.display_code_last4">
                                    · końcówka kodu: {{ invitation.display_code_last4 }}
                                </template>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-11 items-center justify-center rounded-lg border border-[#d0d5dd] bg-white px-4 text-sm font-semibold text-[#344054] transition hover:bg-white"
                            :class="props.mobileSheet ? 'w-full' : 'sm:w-auto sm:rounded-md'"
                            @click="revokeInvitation(invitation)"
                        >
                            Unieważnij
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
