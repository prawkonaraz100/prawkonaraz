<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const lastSubmissionWasInitialPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    if (form.processing) return;
    lastSubmissionWasInitialPassword.value = !props.passwordLoginEnabled;

    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (props.passwordLoginEnabled && form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};

const heading = computed(() =>
    props.passwordLoginEnabled ? 'Zmień hasło' : 'Ustaw hasło',
);

const description = computed(() =>
    props.passwordLoginEnabled
        ? 'Ustaw mocne hasło, jeśli logujesz się e-mailem albo chcesz odświeżyć zapasową metodę dostępu do konta.'
        : 'Korzystasz z logowania przez konto społecznościowe. Ustaw hasło, aby logować się również e-mailem.',
);

const submitLabel = computed(() =>
    props.passwordLoginEnabled ? 'Zmień hasło' : 'Ustaw hasło',
);

const successMessage = computed(() =>
    lastSubmissionWasInitialPassword.value
        ? 'Hasło zostało ustawione.'
        : 'Hasło zostało zmienione.',
);

const passwordGridClass = computed(() =>
    props.passwordLoginEnabled ? 'lg:grid-cols-3' : 'lg:grid-cols-2',
);
</script>

<template>
    <section>
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Bezpieczeństwo
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                {{ heading }}
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                {{ description }}
            </p>
        </header>

        <p v-else class="text-sm leading-6 text-[#667085]">
            {{ description }}
        </p>

        <form
            @submit.prevent="updatePassword"
            :class="props.mobileSheet ? 'mt-5 space-y-4' : 'mt-8 divide-y divide-slate-200 border-y border-slate-200'"
        >
            <div
                class="grid gap-4"
                :class="[passwordGridClass, props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6']"
            >
                <div v-if="passwordLoginEnabled">
                    <label for="current_password" class="text-sm font-medium text-[#344054]">
                        Obecne hasło
                    </label>

                    <input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        autocomplete="current-password"
                        required
                    />

                    <InputError
                        :message="form.errors.current_password"
                        class="mt-2"
                    />
                </div>

                <div>
                    <label for="password" class="text-sm font-medium text-[#344054]">
                        Nowe hasło
                    </label>

                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        autocomplete="new-password"
                        required
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <label for="password_confirmation" class="text-sm font-medium text-[#344054]">
                        Powtórz nowe hasło
                    </label>

                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        autocomplete="new-password"
                        required
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2"
                    />
                </div>
            </div>

            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center"
                :class="props.mobileSheet ? 'pt-1' : 'py-6'"
            >
                <button
                    type="submit"
                    class="inline-flex h-12 items-center justify-center rounded-lg bg-[#0b5cff] px-6 text-sm font-semibold text-white transition hover:bg-[#084fdc] disabled:cursor-not-allowed disabled:opacity-50"
                    :class="props.mobileSheet ? 'w-full' : 'sm:h-11 sm:w-auto sm:rounded-md sm:bg-[#0d47a1]'"
                    :disabled="form.processing"
                >
                    {{ submitLabel }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        role="status"
                        class="text-sm font-medium text-emerald-700"
                    >
                        {{ successMessage }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
