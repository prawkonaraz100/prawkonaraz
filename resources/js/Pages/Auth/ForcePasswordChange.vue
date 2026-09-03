<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.put(route('password.force.update'), {
        onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout cardless>
        <Head title="Zmień hasło startowe" />

        <section class="border-b border-[#e4eaf3] bg-[#fbfcff]">
            <div class="mx-auto max-w-[90rem] px-4 py-8 sm:px-6 lg:py-12 xl:px-8">
                <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                    Pierwsze logowanie
                </p>
                <h1 class="mt-3 max-w-3xl text-[2rem] font-bold leading-[1.12] tracking-[0] text-[#081331] md:text-[2.6rem]">
                    Ustaw własne hasło
                </h1>
                <p class="mt-4 max-w-2xl text-[1rem] font-medium leading-7 text-[#4e5b78] md:text-[1.08rem]">
                    Hasło startowe od moderatora działa tylko jako pierwszy krok. Ustaw własne hasło, zanim przejdziesz dalej.
                </p>
            </div>
        </section>

        <section class="mx-auto grid max-w-[90rem] gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:py-10 xl:px-8">
            <form class="min-w-0 border-y border-[#e4eaf3] py-6" @submit.prevent="submit">
                <div class="grid gap-5">
                    <div>
                        <label for="current_password" class="text-sm font-bold text-[#081331]">
                            Hasło startowe
                        </label>
                        <div class="relative mt-2">
                            <input
                                id="current_password"
                                v-model="form.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                class="block min-h-12 w-full rounded-[6px] border border-[#c9d2e3] bg-white px-4 pe-12 text-base font-semibold text-[#081331] transition placeholder:text-[#a3acc2] focus:border-[#9aa8bd] focus:outline-none focus:ring-0"
                                required
                                autocomplete="current-password"
                                autofocus
                            >
                            <button
                                type="button"
                                :aria-label="showCurrentPassword ? 'Ukryj hasło startowe' : 'Pokaż hasło startowe'"
                                :aria-pressed="showCurrentPassword"
                                class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#081331] focus:outline-none focus:ring-2 focus:ring-[#081331]/15"
                                @click="showCurrentPassword = !showCurrentPassword"
                            >
                                <svg v-if="!showCurrentPassword" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" />
                                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.1 3.8" />
                                    <path d="M6.6 6.6A15.7 15.7 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 4.2-.9" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.current_password" />
                    </div>

                    <div>
                        <label for="password" class="text-sm font-bold text-[#081331]">
                            Nowe hasło
                        </label>
                        <div class="relative mt-2">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block min-h-12 w-full rounded-[6px] border border-[#c9d2e3] bg-white px-4 pe-12 text-base font-semibold text-[#081331] transition placeholder:text-[#a3acc2] focus:border-[#9aa8bd] focus:outline-none focus:ring-0"
                                required
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                :aria-label="showPassword ? 'Ukryj nowe hasło' : 'Pokaż nowe hasło'"
                                :aria-pressed="showPassword"
                                class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#081331] focus:outline-none focus:ring-2 focus:ring-[#081331]/15"
                                @click="showPassword = !showPassword"
                            >
                                <svg v-if="!showPassword" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" />
                                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.1 3.8" />
                                    <path d="M6.6 6.6A15.7 15.7 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 4.2-.9" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-bold text-[#081331]">
                            Powtórz nowe hasło
                        </label>
                        <div class="relative mt-2">
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                class="block min-h-12 w-full rounded-[6px] border border-[#c9d2e3] bg-white px-4 pe-12 text-base font-semibold text-[#081331] transition placeholder:text-[#a3acc2] focus:border-[#9aa8bd] focus:outline-none focus:ring-0"
                                required
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                :aria-label="showPasswordConfirmation ? 'Ukryj powtórzone hasło' : 'Pokaż powtórzone hasło'"
                                :aria-pressed="showPasswordConfirmation"
                                class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#081331] focus:outline-none focus:ring-2 focus:ring-[#081331]/15"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                            >
                                <svg v-if="!showPasswordConfirmation" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" />
                                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.1 3.8" />
                                    <path d="M6.6 6.6A15.7 15.7 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 4.2-.9" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <button
                    type="submit"
                    class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-[6px] bg-[#e11d2e] px-6 text-center text-sm font-bold text-white transition hover:bg-[#b51222] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Zapisywanie...' : 'Zapisz hasło' }}
                </button>
            </form>

            <aside class="border-y border-[#e4eaf3] py-5 lg:self-start">
                <p class="text-xs font-bold uppercase tracking-[0] text-[#e11d2e]">
                    Co dalej
                </p>
                <div class="mt-3 space-y-3 text-sm font-medium leading-6 text-[#4e5b78]">
                    <p>
                        Po zapisaniu hasła konto będzie działać jak zwykłe konto z logowaniem e-mailem.
                    </p>
                    <p>
                        Stare hasło startowe przestanie być potrzebne.
                    </p>
                    <p>
                        Jeśli nie pamiętasz nowego hasła, użyjesz standardowego resetu hasła.
                    </p>
                </div>
            </aside>
        </section>
    </GuestLayout>
</template>
