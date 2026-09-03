<script setup lang="ts">
import type { PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SocialConnection {
    provider: 'google' | 'facebook';
    label: string;
    connected: boolean;
    email: string | null;
    linked_at: string | null;
}

const props = defineProps<{
    socialConnections: {
        password_login_enabled: boolean;
        providers: SocialConnection[];
    };
    status?: string;
    mobileSheet?: boolean;
}>();

const page = usePage<PageProps & { errors?: Record<string, string> }>();

const connectedCount = computed(
    () => props.socialConnections.providers.filter((provider) => provider.connected).length,
);

const providerError = computed(() => page.props.errors?.provider ?? '');

const successMessage = computed(() => {
    if (props.status === 'Metoda logowania została podpięta.') {
        return props.status;
    }

    if (props.status === 'Metoda logowania została odpięta.') {
        return props.status;
    }

    return '';
});

const canDisconnect = (connection: SocialConnection) => {
    if (!connection.connected) {
        return false;
    }

    return props.socialConnections.password_login_enabled || connectedCount.value > 1;
};

const disconnect = (connection: SocialConnection) => {
    if (!canDisconnect(connection)) {
        return;
    }

    router.delete(route('profile.social.destroy', { provider: connection.provider }), {
        preserveScroll: true,
    });
};

const connectionStatusLabel = (connection: SocialConnection) => {
    if (!connection.connected) {
        return 'Niepołączone';
    }

    if (connection.email) {
        return `Połączone: ${connection.email}`;
    }

    return 'Połączone';
};

const disconnectHint = (connection: SocialConnection) => {
    if (!connection.connected) {
        return 'Połącz tę metodę tylko wtedy, gdy konto społecznościowe ma ten sam adres e-mail.';
    }

    if (canDisconnect(connection)) {
        return props.socialConnections.password_login_enabled
            ? 'Możesz odłączyć tę metodę, bo konto ma ustawione hasło.'
            : 'Możesz odłączyć tę metodę, bo masz podpiętą inną metodę społecznościową.';
    }

    return 'Nie możesz odłączyć ostatniej metody logowania. Najpierw ustaw hasło do konta.';
};
</script>

<template>
    <section>
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Logowanie
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                Połączenia społecznościowe
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                Zarządzaj metodami logowania. Nie pozwolimy odłączyć ostatniej metody dostępu do konta.
            </p>
        </header>

        <p v-else class="text-sm leading-6 text-[#667085]">
            <template v-if="socialConnections.providers.length > 0">
                Połącz dodatkową metodę logowania. Ostatniej aktywnej metody nie można odłączyć bez ustawienia hasła.
            </template>
            <template v-else>
                Tutaj możesz zarządzać dodatkowymi metodami logowania, gdy są dostępne.
            </template>
        </p>

        <div
            v-if="providerError"
            class="mt-5 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800"
            :class="props.mobileSheet ? 'rounded-lg' : 'border-y border-red-200'"
            role="alert"
        >
            {{ providerError }}
        </div>

        <div
            v-if="successMessage"
            class="mt-5 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800"
            :class="props.mobileSheet ? 'rounded-lg' : 'border-y border-emerald-200'"
            role="status"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="socialConnections.providers.length > 0"
            class="divide-y divide-slate-200"
            :class="props.mobileSheet ? 'mt-5 overflow-hidden rounded-lg bg-[#f7f8fa] px-4' : 'mt-8 border-y border-slate-200'"
        >
            <div
                v-for="connection in socialConnections.providers"
                :key="connection.provider"
                class="flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 gap-3">
                    <span
                        v-if="props.mobileSheet"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-sm font-semibold text-[#344054] ring-1 ring-[#eaecf0]"
                        aria-hidden="true"
                    >
                        {{ connection.label.slice(0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-950">
                                {{ connection.label }}
                            </p>
                            <span
                                v-if="connection.connected && !canDisconnect(connection)"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800"
                            >
                                Ostatnia metoda
                            </span>
                        </div>
                        <p class="mt-1 break-words text-sm leading-6 text-slate-600">
                            {{ connectionStatusLabel(connection) }}
                        </p>
                        <p class="mt-1 max-w-xl text-xs leading-5 text-slate-500">
                            {{ disconnectHint(connection) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2" :class="props.mobileSheet ? 'pl-[3.25rem]' : ''">
                    <Link
                        v-if="!connection.connected"
                        :href="route('social.redirect', { provider: connection.provider, intent: 'link' })"
                        class="inline-flex h-11 items-center justify-center rounded-lg bg-[#0b5cff] px-5 text-sm font-semibold text-white transition hover:bg-[#084fdc]"
                        :class="props.mobileSheet ? 'w-full' : 'sm:w-auto sm:rounded-md sm:bg-[#0d47a1]'"
                    >
                        Połącz
                    </Link>

                    <button
                        v-else
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-lg border border-[#d0d5dd] bg-white px-5 text-sm font-semibold text-[#344054] transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                        :class="props.mobileSheet ? 'w-full' : 'sm:w-auto sm:rounded-md'"
                        :disabled="!canDisconnect(connection)"
                        :title="disconnectHint(connection)"
                        @click="disconnect(connection)"
                    >
                        Odłącz
                    </button>
                </div>
            </div>
        </div>

        <div
            v-else-if="props.mobileSheet"
            class="mt-5 rounded-lg bg-[#f7f8fa] px-5 py-8 text-center"
        >
            <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-white text-[#667085] ring-1 ring-[#eaecf0]" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                    <path d="M8.5 12.5 11 15l4.5-5M7 4.5h10a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <p class="mt-3 text-sm font-semibold text-[#101828]">Brak dostępnych połączeń</p>
            <p class="mt-1 text-sm leading-6 text-[#667085]">
                Dodatkowe metody logowania nie są obecnie skonfigurowane.
            </p>
        </div>

        <p
            v-if="socialConnections.providers.length > 0 && !socialConnections.password_login_enabled && connectedCount <= 1"
            class="mt-5 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800"
            :class="props.mobileSheet ? 'rounded-lg' : 'border-y border-amber-200'"
        >
            To jest Twoja ostatnia metoda logowania. Ustaw hasło, jeśli chcesz ją odłączyć.
        </p>
    </section>
</template>
