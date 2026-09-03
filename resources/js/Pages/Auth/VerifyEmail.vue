<script setup lang="ts">
import { useSafeLogout } from '@/composables/useSafeLogout';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});
const {
    logout,
    logoutError,
    logoutPreparing,
} = useSafeLogout();

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const emailChangeConfirmed = computed(
    () => props.status === 'email-change-confirmed',
);
</script>

<template>
    <GuestLayout cardless>
        <Head title="Potwierdź adres e-mail" />

        <section class="bg-white">
            <div
                class="mx-auto max-w-[81.75rem] px-4 pb-1.5 pt-2 text-sm text-slate-500 sm:px-6 xl:px-0"
            >
                <a href="/" class="transition hover:text-[#0d47a1]">
                    Strona główna
                </a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-700">Weryfikacja e-mail</span>
            </div>
        </section>

        <section class="bg-white">
            <div
                class="mx-auto grid max-w-[81.75rem] gap-8 px-4 py-9 sm:px-6 md:py-12 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)] lg:items-start lg:gap-12 xl:px-0"
            >
                <div class="max-w-4xl">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#0d47a1]">
                        Weryfikacja konta
                    </p>
                    <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                        Potwierdź adres e-mail
                    </h1>
                    <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
                        Link aktywacyjny czeka w skrzynce podanej podczas rejestracji.
                        Po potwierdzeniu konta odblokujemy naukę, powtórki i zakup pełnego dostępu.
                    </p>
                </div>

                <aside
                    class="border-y border-slate-200 py-6 lg:row-span-2 lg:row-start-1 lg:mt-3"
                    aria-labelledby="verify-email-title"
                >
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#0d47a1]">
                        Ostatni krok
                    </p>
                    <h2
                        id="verify-email-title"
                        class="mt-2 text-2xl font-semibold tracking-tight text-slate-950"
                    >
                        Sprawdź pocztę
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Jeśli wiadomość nie dotarła, wyślij nowy link albo sprawdź folder spam.
                    </p>

                    <div
                        v-if="verificationLinkSent || emailChangeConfirmed"
                        class="mt-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                        role="status"
                    >
                        <template v-if="emailChangeConfirmed">
                            Adres e-mail został zmieniony. Wysłaliśmy link weryfikacyjny na nowy adres.
                        </template>
                        <template v-else>
                            Wysłaliśmy nowy link weryfikacyjny na aktualny adres e-mail.
                        </template>
                    </div>

                    <form class="mt-6" @submit.prevent="submit">
                        <button
                            type="submit"
                            class="inline-flex h-11 w-full items-center justify-center rounded-md bg-[#0d47a1] px-5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Wysyłanie...' : 'Wyślij link ponownie' }}
                        </button>

                        <button
                            type="button"
                            class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-950 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#0d47a1]/25 focus:ring-offset-2"
                            :disabled="logoutPreparing"
                            @click="logout(route('logout'))"
                        >
                            {{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj się' }}
                        </button>

                        <p
                            v-if="logoutError"
                            class="mt-3 text-sm font-semibold text-red-700"
                            role="alert"
                        >
                            {{ logoutError }}
                        </p>
                    </form>
                </aside>

                <div class="max-w-3xl lg:col-start-1 lg:row-start-2">
                    <div class="divide-y divide-slate-200 border-y border-slate-200">
                        <div class="py-4">
                            <p class="text-sm font-semibold text-slate-950">
                                1. Otwórz wiadomość
                            </p>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600">
                                Szukaj maila z linkiem aktywacyjnym od prawkonaraz.pl.
                            </p>
                        </div>
                        <div class="py-4">
                            <p class="text-sm font-semibold text-slate-950">
                                2. Kliknij link
                            </p>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600">
                                Po potwierdzeniu wrócisz do serwisu i przejdziesz dalej.
                            </p>
                        </div>
                        <div class="py-4">
                            <p class="text-sm font-semibold text-slate-950">
                                3. Zacznij naukę
                            </p>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600">
                                Konto będzie gotowe do wyboru planu, nauki i powtórek.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
