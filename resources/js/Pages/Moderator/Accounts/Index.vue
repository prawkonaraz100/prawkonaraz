<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface ModeratorAccountStatus {
    code: 'active' | 'pending_claim' | 'expired' | 'banned';
    label: string;
    tone: 'success' | 'info' | 'warning' | 'danger';
}

interface ModeratorAccount {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
    claimed_at: string | null;
    temporary_account_expires_at: string | null;
    access_expires_at: string | null;
    requires_password_change: boolean;
    is_temporary_account: boolean;
    can_regenerate_start_password: boolean;
    status: ModeratorAccountStatus;
    target_category: {
        id: number;
        code: string;
        name: string;
    } | null;
    moderator_owner: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface ModeratorCategory {
    id: number;
    code: string;
    name: string;
}

interface CreatedModeratorAccount {
    id: number;
    name: string;
    login_email: string;
    account_type: 'full' | 'temporary';
    start_password: string;
    target_category_code: string;
    access_expires_at: string | null;
    temporary_account_expires_at: string | null;
    start_credentials_email_sent: boolean | null;
}

const props = defineProps<{
    panel: {
        is_admin_view: boolean;
        moderator: {
            id: number;
            name: string;
            email: string;
        };
        quota: {
            limit: number | null;
            used: number;
            remaining: number | null;
            percent: number | null;
        };
        summary: {
            total: number;
            active: number;
            pending_claim: number;
            expired: number;
            banned: number;
        };
        can_create_accounts: boolean;
    };
    categories: ModeratorCategory[];
    accounts: ModeratorAccount[];
    createdAccount: CreatedModeratorAccount | null;
}>();

const quotaPercent = computed(() => props.panel.quota.percent ?? 0);
const accountTypeOptions = [
    {
        value: 'full',
        label: 'Konto z e-mailem',
        description:
            'Gdy znasz adres kursanta. Użytkownik zmieni hasło i potwierdzi e-mail przy pierwszym wejściu.',
    },
    {
        value: 'temporary',
        label: 'Konto tymczasowe',
        description:
            'Gdy nie znasz adresu. System nada login techniczny, a kursant poda e-mail przy przejęciu konta.',
    },
] as const;
const form = useForm<{
    account_type: 'full' | 'temporary';
    name: string;
    email: string;
    target_category_id: number | null;
}>({
    account_type: 'full',
    name: '',
    email: '',
    target_category_id: props.categories[0]?.id ?? null,
});
const regenerateForm = useForm<{
    account?: number;
}>({});
const regeneratingAccountId = ref<number | null>(null);

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('pl-PL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusClass = (tone: ModeratorAccountStatus['tone']) => {
    if (tone === 'success') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (tone === 'info') {
        return 'border-blue-200 bg-blue-50 text-[#0d47a1]';
    }

    if (tone === 'warning') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    return 'border-red-200 bg-red-50 text-red-700';
};

const statusSummaryItems = computed(() => [
    {
        label: 'Aktywne',
        value: props.panel.summary.active,
        tone: 'success' as const,
    },
    {
        label: 'Do przejęcia',
        value: props.panel.summary.pending_claim,
        tone: 'info' as const,
    },
    {
        label: 'Wygasłe',
        value: props.panel.summary.expired,
        tone: 'warning' as const,
    },
    {
        label: 'Zablokowane',
        value: props.panel.summary.banned,
        tone: 'danger' as const,
    },
]);

const quotaLabel = computed(() =>
    props.panel.quota.limit === null
        ? `${props.panel.quota.used} kont`
        : `${props.panel.quota.used}/${props.panel.quota.limit}`,
);

const quotaRemainingLabel = computed(() =>
    props.panel.quota.remaining === null
        ? 'Widok administratora obejmuje wszystkie konta moderatorskie.'
        : `${props.panel.quota.remaining} kont pozostało w puli.`,
);

const submit = () => {
    form.post(route('moderator.accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email');
            form.target_category_id = props.categories[0]?.id ?? null;
        },
    });
};

const regenerateStartPassword = (account: ModeratorAccount) => {
    regeneratingAccountId.value = account.id;

    regenerateForm.post(
        route('moderator.accounts.start-password.regenerate', {
            account: account.id,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                regeneratingAccountId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Panel moderatora" />

    <AuthenticatedLayout>
        <template #header>
            <div class="max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                    Panel moderatora
                </p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                    {{
                        panel.is_admin_view
                            ? 'Konta tworzone przez moderatorów'
                            : 'Twoje konta kursantów'
                    }}
                </h1>
                <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
                    {{
                        panel.is_admin_view
                            ? 'Przeglądaj konta utworzone przez moderatorów i kontroluj ich status bez wchodzenia w pełny panel użytkowników.'
                            : 'Twórz konta kursantów, przekazuj dane startowe i pilnuj swojej puli w jednym uporządkowanym miejscu.'
                    }}
                </p>
            </div>
        </template>

        <section class="space-y-12">
            <section>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            Przegląd
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            Pula i status kont
                        </h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Statusy pokazują, które konta są gotowe do nauki, które czekają na przejęcie i gdzie kończy się dostęp.
                        </p>
                    </div>

                    <p class="text-sm text-slate-500">
                        {{ panel.summary.total }} kont
                    </p>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
                    <div class="divide-y divide-slate-200 border-y border-slate-200">
                        <div class="flex flex-col gap-4 py-6 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">
                                    Wykorzystanie puli
                                </p>
                                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                                    {{ quotaLabel }}
                                </p>
                            </div>

                            <p class="max-w-sm text-sm leading-6 text-slate-600 sm:text-right">
                                {{ quotaRemainingLabel }}
                            </p>
                        </div>

                        <div v-if="panel.quota.limit !== null" class="py-6">
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-500">
                                <span>0</span>
                                <span>{{ panel.quota.limit }}</span>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden bg-slate-100">
                                <div
                                    class="h-full bg-[#0d47a1] transition-[width] duration-500"
                                    :style="{ width: `${quotaPercent}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-200 border-y border-slate-200">
                        <div
                            v-for="item in statusSummaryItems"
                            :key="item.label"
                            class="flex items-center justify-between gap-5 py-4"
                        >
                            <span
                                class="inline-flex rounded-sm border px-2.5 py-1 text-xs font-semibold"
                                :class="statusClass(item.tone)"
                            >
                                {{ item.label }}
                            </span>
                            <span class="text-2xl font-semibold tracking-tight text-slate-950">
                                {{ item.value }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="createdAccount"
                class="border-y border-emerald-200 bg-emerald-50 px-4 py-6 text-emerald-950 sm:px-6"
            >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">
                            Hasło startowe widoczne tylko teraz
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight">
                            Dane startowe dla konta {{ createdAccount.name }}
                        </h2>
                        <p
                            v-if="createdAccount.account_type === 'full' && createdAccount.start_credentials_email_sent === true"
                            class="mt-3 max-w-2xl text-sm leading-6 text-emerald-800"
                        >
                            Wysłaliśmy login, hasło startowe i link potwierdzenia e-mail na adres użytkownika. Dane zostają tutaj widoczne tylko awaryjnie do odświeżenia strony.
                        </p>
                        <p
                            v-else-if="createdAccount.account_type === 'full' && createdAccount.start_credentials_email_sent === false"
                            class="mt-3 max-w-2xl text-sm leading-6 text-emerald-800"
                        >
                            Nie udało się wysłać wiadomości. Przekaż użytkownikowi login i hasło ręcznie albo wygeneruj nowe hasło startowe później z listy kont.
                        </p>
                        <p
                            v-else
                            class="mt-3 max-w-2xl text-sm leading-6 text-emerald-800"
                        >
                            Przekaż użytkownikowi login i hasło. Po odświeżeniu strony hasło nie będzie już widoczne w panelu.
                        </p>
                    </div>

                    <dl class="grid gap-3 border border-emerald-200 bg-white p-4 text-sm sm:min-w-[24rem]">
                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700">Login</dt>
                            <dd class="font-semibold">{{ createdAccount.login_email }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700">Hasło</dt>
                            <dd class="font-mono font-semibold tracking-wide">
                                {{ createdAccount.start_password }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700">Kategoria</dt>
                            <dd class="font-semibold">{{ createdAccount.target_category_code }}</dd>
                        </div>
                        <div
                            v-if="createdAccount.account_type === 'full'"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-emerald-700">E-mail</dt>
                            <dd class="font-semibold">
                                {{
                                    createdAccount.start_credentials_email_sent
                                        ? 'wysłany'
                                        : 'nie wysłano'
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section
                v-if="!panel.is_admin_view"
                id="nowe-konto"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            Nowe konto
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            Dodaj kursanta do swojej puli
                        </h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-slate-600">
                        Każde konto dostaje dostęp na 90 dni. Konto tymczasowe musi zostać przejęte przez użytkownika w ciągu 14 dni.
                    </p>
                </div>

                <form class="mt-8 divide-y divide-slate-200 border-y border-slate-200" @submit.prevent="submit">
                    <div class="py-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="option in accountTypeOptions"
                                :key="option.value"
                                type="button"
                                class="border px-4 py-4 text-left transition"
                                :class="
                                    form.account_type === option.value
                                        ? 'border-[#0d47a1] bg-blue-50 text-slate-950'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                                "
                                @click="form.account_type = option.value"
                            >
                                <span class="text-base font-semibold">{{ option.label }}</span>
                                <span class="mt-1 block text-sm leading-5">{{ option.description }}</span>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.account_type" />
                    </div>

                    <div class="grid gap-4 py-6 lg:grid-cols-3">
                        <div>
                            <label for="moderator-account-name" class="text-sm font-semibold text-slate-950">
                                Imię i nazwisko
                            </label>
                            <input
                                id="moderator-account-name"
                                v-model="form.name"
                                type="text"
                                class="mt-2 h-11 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition focus:border-[#0d47a1] focus:ring-2 focus:ring-[#0d47a1]/10"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div v-if="form.account_type === 'full'">
                            <label for="moderator-account-email" class="text-sm font-semibold text-slate-950">
                                E-mail użytkownika
                            </label>
                            <input
                                id="moderator-account-email"
                                v-model="form.email"
                                type="email"
                                class="mt-2 h-11 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition focus:border-[#0d47a1] focus:ring-2 focus:ring-[#0d47a1]/10"
                                :required="form.account_type === 'full'"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <label for="moderator-account-category" class="text-sm font-semibold text-slate-950">
                                Kategoria nauki
                            </label>
                            <select
                                id="moderator-account-category"
                                v-model.number="form.target_category_id"
                                class="mt-2 h-11 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition focus:border-[#0d47a1] focus:ring-2 focus:ring-[#0d47a1]/10"
                                required
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.code }} - {{ category.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.target_category_id" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-2xl text-sm leading-6 text-slate-600">
                            Hasło startowe pokażemy raz po utworzeniu konta. Nie zapisujemy go w bazie w jawnej postaci.
                        </p>
                        <button
                            type="submit"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-[#0d47a1] px-6 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing || !panel.can_create_accounts"
                        >
                            {{ form.processing ? 'Tworzenie...' : 'Utwórz konto' }}
                        </button>
                    </div>
                </form>
            </section>

            <section>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            Lista kont
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            {{
                                panel.is_admin_view
                                    ? 'Konta moderatorów'
                                    : 'Konta w puli moderatora'
                            }}
                        </h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Tutaj widzisz konta pełne i tymczasowe utworzone w ramach puli moderatora.
                        </p>
                        <InputError class="mt-3" :message="regenerateForm.errors.account" />
                    </div>

                    <p class="text-sm text-slate-500">
                        {{ accounts.length }} pozycji
                    </p>
                </div>

                <div v-if="accounts.length > 0" class="mt-8 overflow-x-auto border-y border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold sm:px-6">Konto</th>
                                <th class="px-5 py-3 font-semibold sm:px-6">Kategoria</th>
                                <th class="px-5 py-3 font-semibold sm:px-6">Status</th>
                                <th class="px-5 py-3 font-semibold sm:px-6">Dostęp do</th>
                                <th v-if="panel.is_admin_view" class="px-5 py-3 font-semibold sm:px-6">Moderator</th>
                                <th v-if="!panel.is_admin_view" class="px-5 py-3 font-semibold sm:px-6">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr
                                v-for="account in accounts"
                                :key="account.id"
                                class="align-top transition hover:bg-slate-50"
                            >
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-semibold text-slate-950">{{ account.name }}</p>
                                    <p class="mt-1 text-slate-600">{{ account.email }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Utworzono: {{ formatDate(account.created_at) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span
                                        v-if="account.target_category"
                                        class="font-semibold text-slate-950"
                                    >
                                        {{ account.target_category.code }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span
                                        class="inline-flex rounded-sm border px-2.5 py-1 text-xs font-semibold"
                                        :class="statusClass(account.status.tone)"
                                    >
                                        {{ account.status.label }}
                                    </span>
                                    <p
                                        v-if="account.requires_password_change"
                                        class="mt-2 text-xs text-slate-500"
                                    >
                                        Wymaga zmiany hasła
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-slate-600 sm:px-6">
                                    {{ formatDate(account.access_expires_at) }}
                                </td>
                                <td
                                    v-if="panel.is_admin_view"
                                    class="px-5 py-4 text-slate-600 sm:px-6"
                                >
                                    <span v-if="account.moderator_owner">
                                        {{ account.moderator_owner.name }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td
                                    v-if="!panel.is_admin_view"
                                    class="px-5 py-4 sm:px-6"
                                >
                                    <button
                                        v-if="account.can_regenerate_start_password"
                                        type="button"
                                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-[#0d47a1] px-3 py-2 text-xs font-semibold text-[#0d47a1] transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="regenerateForm.processing"
                                        @click="regenerateStartPassword(account)"
                                    >
                                        {{
                                            regenerateForm.processing && regeneratingAccountId === account.id
                                                ? 'Generowanie...'
                                                : 'Wyślij nowe hasło'
                                        }}
                                    </button>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="mt-8 border-y border-slate-200 px-6 py-12 text-center">
                    <p class="text-lg font-semibold text-slate-950">
                        Nie masz jeszcze przypisanych kont.
                    </p>
                    <p class="mt-2 text-sm text-slate-600">
                        Użyj formularza powyżej, żeby utworzyć pierwsze konto pełne albo tymczasowe.
                    </p>
                </div>
            </section>
        </section>
    </AuthenticatedLayout>
</template>
