<script setup lang="ts">
import { expiredSessionLoginUrl } from '@/lib/csrfSession';

withDefaults(
    defineProps<{
        publicDemo?: boolean;
        restartHref?: string;
    }>(),
    {
        publicDemo: false,
        restartHref: '/',
    },
);

const reloadPage = () => {
    window.location.reload();
};
</script>

<template>
    <div
        class="fixed inset-0 z-[200] grid place-items-center bg-slate-950/65 px-4 backdrop-blur-sm"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="session-expired-title"
    >
        <section class="w-full max-w-lg rounded-xl bg-white p-6 text-slate-950 shadow-2xl sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#d01921]">
                Sesja wygasła
            </p>
            <h2 id="session-expired-title" class="mt-3 text-2xl font-bold">
                Nie zapisaliśmy ostatniej operacji
            </h2>
            <p class="mt-3 text-sm font-medium leading-6 text-slate-600">
                Zatrzymaliśmy kolejne odpowiedzi i synchronizację, żeby nie zapisać danych podwójnie.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <a
                    v-if="publicDemo"
                    :href="restartHref"
                    class="inline-flex h-11 items-center justify-center rounded-md bg-[#d01921] px-5 text-sm font-bold text-white transition hover:bg-[#b9151c]"
                >
                    Uruchom demo od początku
                </a>
                <a
                    v-else
                    :href="expiredSessionLoginUrl()"
                    class="inline-flex h-11 items-center justify-center rounded-md bg-[#d01921] px-5 text-sm font-bold text-white transition hover:bg-[#b9151c]"
                >
                    Zaloguj się ponownie
                </a>
                <button
                    type="button"
                    class="inline-flex h-11 items-center justify-center rounded-md border border-slate-300 px-5 text-sm font-bold text-slate-800 transition hover:bg-slate-50"
                    @click="reloadPage"
                >
                    Odśwież stronę
                </button>
            </div>
        </section>
    </div>
</template>
