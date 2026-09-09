<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import type { PageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        passwordLoginEnabled?: boolean;
        mobileSheet?: boolean;
    }>(),
    {
        passwordLoginEnabled: true,
        mobileSheet: false,
    },
);

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);
const emailInput = ref<HTMLInputElement | null>(null);
const page = usePage<PageProps>();

const passwordForm = useForm({
    password: '',
});

const deletionLinkForm = useForm({
    email: '',
});

const accountEmail = computed(() => page.props.auth.user?.email ?? '');

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => {
        if (props.passwordLoginEnabled) {
            passwordInput.value?.focus();
            return;
        }

        emailInput.value?.focus();
    });
};

const deleteUser = () => {
    if (passwordForm.processing) return;
    passwordForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            passwordForm.reset();
        },
    });
};

const sendDeletionLink = () => {
    if (deletionLinkForm.processing) return;
    deletionLinkForm.post(route('profile.deletion.send'), {
        preserveScroll: true,
        onSuccess: () => {
            deletionLinkForm.reset('email');
        },
        onError: () => emailInput.value?.focus(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    passwordForm.clearErrors();
    passwordForm.reset();
    deletionLinkForm.clearErrors();
    deletionLinkForm.reset();

    if (props.mobileSheet) {
        nextTick(() => {
            document.body.style.overflow = 'hidden';
        });
    }
};
</script>

<template>
    <section :class="props.mobileSheet ? 'text-[#101828]' : 'border-y border-red-200 bg-red-50 px-4 py-6 text-red-950 sm:px-6'">
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700">
                Strefa ostrożności
            </p>
            <h2 class="mt-2 text-2xl font-semibold">
                Usuń konto
            </h2>

            <p class="mt-3 text-sm leading-6 text-red-800">
                To trwała operacja. Przed usunięciem upewnij się, że nie potrzebujesz już historii nauki i ustawień konta.
            </p>
        </header>

        <template v-else>
            <p class="text-sm leading-6 text-[#667085]">
                Usunięcie konta jest trwałe i nie można go później cofnąć.
            </p>

            <div class="mt-5 rounded-lg bg-[#fef3f2] p-4">
                <div class="flex gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-[#d92d20]" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v5M12 16.5h.01M10.2 4.8 3.5 17a2 2 0 0 0 1.8 3h13.4a2 2 0 0 0 1.8-3L13.8 4.8a2 2 0 0 0-3.6 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-[#b42318]">Co zostanie usunięte</p>
                        <p class="mt-1 text-sm leading-6 text-[#b42318]">
                            Konto, ustawienia oraz zapisana historia nauki i wyników.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <button
            type="button"
            class="mt-6 inline-flex h-12 items-center justify-center rounded-lg bg-[#d92d20] px-6 text-sm font-semibold text-white transition hover:bg-[#b42318] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d92d20] focus-visible:ring-offset-2"
            :class="props.mobileSheet ? 'w-full' : 'sm:h-11 sm:w-auto sm:rounded-md sm:bg-red-700'"
            @click="confirmUserDeletion"
        >
            {{ props.mobileSheet ? 'Przejdź do usunięcia konta' : 'Usuń konto' }}
        </button>

        <Modal :show="confirmingUserDeletion" max-width="md" @close="closeModal">
            <div :class="props.mobileSheet ? 'p-5' : 'p-6'">
                <h2
                    class="text-xl font-semibold text-slate-950"
                >
                    Na pewno usunąć konto?
                </h2>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    <template v-if="passwordLoginEnabled">
                        Po potwierdzeniu konto zostanie usunięte. Wpisz hasło, żeby potwierdzić tę decyzję.
                    </template>
                    <template v-else>
                        To konto nie ma ustawionego hasła. Wpisz adres e-mail konta, a wyślemy link do ostatecznego potwierdzenia.
                    </template>
                </p>

                <div v-if="passwordLoginEnabled" class="mt-6">
                    <label for="delete_account_password" class="sr-only">
                        Hasło
                    </label>

                    <input
                        id="delete_account_password"
                        ref="passwordInput"
                        v-model="passwordForm.password"
                        type="password"
                        class="h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-slate-950 outline-none transition focus:border-[#d92d20] focus:ring-2 focus:ring-[#d92d20]/10 sm:h-11 sm:w-3/4 sm:rounded-md sm:text-sm"
                        placeholder="Hasło"
                        autocomplete="current-password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="passwordForm.errors.password" class="mt-2" />
                </div>

                <div v-else class="mt-6">
                    <label for="delete_confirmation_email" class="text-sm font-semibold text-slate-950">
                        Adres e-mail konta
                    </label>

                    <input
                        id="delete_confirmation_email"
                        ref="emailInput"
                        v-model="deletionLinkForm.email"
                        type="email"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-slate-950 outline-none transition focus:border-[#d92d20] focus:ring-2 focus:ring-[#d92d20]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        :placeholder="accountEmail"
                        autocomplete="email"
                        @keyup.enter="sendDeletionLink"
                    />

                    <InputError :message="deletionLinkForm.errors.email" class="mt-2" />

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="deletionLinkForm.recentlySuccessful"
                            class="mt-3 text-sm font-medium text-emerald-700"
                        >
                            Wysłaliśmy link potwierdzający usunięcie konta.
                        </p>
                    </Transition>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg border border-[#d0d5dd] px-5 text-sm font-semibold text-[#344054] transition hover:bg-[#f9fafb] sm:h-10 sm:rounded-md"
                        @click="closeModal"
                    >
                        Anuluj
                    </button>

                    <button
                        v-if="passwordLoginEnabled"
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg bg-[#d92d20] px-5 text-sm font-semibold text-white transition hover:bg-[#b42318] disabled:cursor-not-allowed disabled:opacity-50 sm:h-10 sm:rounded-md sm:bg-red-700"
                        :disabled="passwordForm.processing"
                        @click="deleteUser"
                    >
                        Usuń konto
                    </button>

                    <button
                        v-else
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg bg-[#d92d20] px-5 text-sm font-semibold text-white transition hover:bg-[#b42318] disabled:cursor-not-allowed disabled:opacity-50 sm:h-10 sm:rounded-md sm:bg-red-700"
                        :disabled="deletionLinkForm.processing"
                        @click="sendDeletionLink"
                    >
                        Wyślij link
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
