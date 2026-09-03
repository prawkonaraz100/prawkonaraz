<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import type { PageProps } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        mustVerifyEmail?: boolean;
        passwordLoginEnabled?: boolean;
        status?: string;
        mobileSheet?: boolean;
    }>(),
    {
        passwordLoginEnabled: true,
        mobileSheet: false,
    },
);

const user = usePage<PageProps>().props.auth.user!;

const form = useForm({
    name: user.name,
    email: user.email,
    current_password: '',
});

const normalizedEmail = (email: string) => email.trim().toLowerCase();

const emailChanged = computed(
    () => normalizedEmail(form.email) !== normalizedEmail(user.email),
);

const updateProfileInformation = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onFinish: () => form.reset('current_password'),
    });
};
</script>

<template>
    <section>
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Dane konta
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                Podstawowe informacje
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                To są dane używane do logowania i komunikacji z serwisem.
            </p>
        </header>

        <p v-else class="text-sm leading-6 text-[#667085]">
            Te dane służą do logowania, odzyskiwania konta i komunikacji z serwisem.
        </p>

        <form
            @submit.prevent="updateProfileInformation"
            :class="props.mobileSheet ? 'mt-5 space-y-4' : 'mt-8 divide-y divide-slate-200 border-y border-slate-200'"
        >
            <div
                class="grid gap-4 lg:grid-cols-2"
                :class="props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6'"
            >
                <div>
                    <label for="name" class="text-sm font-medium text-[#344054]">
                        Imię i nazwisko
                    </label>

                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        required
                        :autofocus="!props.mobileSheet"
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <label for="email" class="text-sm font-medium text-[#344054]">
                        E-mail
                    </label>

                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <div
                v-if="emailChanged"
                :class="props.mobileSheet ? 'rounded-lg bg-[#f7f8fa] p-4' : 'py-6'"
            >
                <div v-if="props.passwordLoginEnabled" class="max-w-md">
                    <label for="profile_current_password" class="text-sm font-medium text-[#344054]">
                        Obecne hasło
                    </label>

                    <input
                        id="profile_current_password"
                        v-model="form.current_password"
                        type="password"
                        class="mt-2 h-12 w-full rounded-lg border border-[#d0d5dd] bg-white px-4 text-base text-[#101828] outline-none transition focus:border-[#0b5cff] focus:ring-2 focus:ring-[#0b5cff]/10 sm:h-11 sm:rounded-md sm:text-sm"
                        required
                        autocomplete="current-password"
                    />

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Wymagamy hasła, ponieważ zmiana adresu e-mail wpływa na logowanie i odzyskiwanie konta.
                    </p>

                    <InputError class="mt-2" :message="form.errors.current_password" />
                </div>

                <p
                    v-else
                    class="max-w-3xl text-sm leading-6 text-slate-700"
                >
                    To konto nie ma jeszcze hasła, więc wyślemy link potwierdzający zmianę na obecny adres:
                    <strong class="font-semibold text-slate-950">{{ user.email }}</strong>.
                    Nowy adres będzie aktywny dopiero po kliknięciu linku.
                </p>
            </div>

            <div
                v-if="mustVerifyEmail && user.email_verified_at === null"
                :class="props.mobileSheet ? 'rounded-lg bg-[#fffaeb] p-4' : 'py-6'"
            >
                <p class="text-sm leading-6 text-slate-700">
                    Ten adres e-mail nie jest jeszcze potwierdzony.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-semibold text-[#0d47a1] underline decoration-[#0d47a1]/30 underline-offset-4 transition hover:text-blue-800"
                    >
                        Wyślij ponownie link potwierdzający.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-3 text-sm font-semibold text-emerald-700"
                >
                    Wysłaliśmy nowy link potwierdzający na Twój adres e-mail.
                </div>
            </div>

            <div
                v-show="status === 'email-change-confirmation-sent'"
                :class="props.mobileSheet ? 'rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-700' : 'py-6 text-sm font-semibold text-emerald-700'"
            >
                Wysłaliśmy link potwierdzający zmianę na Twój obecny adres e-mail.
            </div>

            <div
                v-show="status === 'email-change-confirmed'"
                :class="props.mobileSheet ? 'rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-700' : 'py-6 text-sm font-semibold text-emerald-700'"
            >
                Adres e-mail został zmieniony. Sprawdź nową skrzynkę i potwierdź adres.
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
                    Zapisz dane
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-emerald-700"
                    >
                        Zapisano.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
