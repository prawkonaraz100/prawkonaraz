<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ResultAnswer {
    id: number;
    position: number;
    is_correct: boolean;
    selected_traffic_sign_id: number | null;
    traffic_sign: {
        id: number;
        code: string;
        name: string;
        image_url: string | null;
        public_url: string;
    };
    correct_label: string;
}

interface ConfusionSummary {
    traffic_sign_id: number;
    code: string | null;
    name: string;
    image_url: string | null;
    count: number;
}

const props = defineProps<{
    result: {
        id: number;
        mode: 'recognition' | 'similar_signs' | 'description_to_sign';
        status: string;
        total_signs_count: number;
        correct_answers_count: number;
        score_percent: number;
        confusions: ConfusionSummary[];
        answers: ResultAnswer[];
    };
}>();

const startForm = useForm<{
    mode: 'recognition' | 'similar_signs' | 'description_to_sign';
}>({
    mode: props.result.mode,
});
const continueTraining = () => {
    if (startForm.processing) {
        return;
    }

    startForm.mode = props.result.mode;
    startForm.post(route('traffic-sign-learning.store'));
};

const isAnswersOpen = ref(false);
</script>

<template>
    <Head title="Wynik treningu znaków" />

    <AuthenticatedLayout>
        <section class="min-h-screen bg-[#f7f8fa] pb-[calc(5.5rem+env(safe-area-inset-bottom))] text-[#17191d] md:hidden">
            <header class="border-b border-[#dfe3e8] bg-white px-4 pb-5 pt-[calc(1.25rem+env(safe-area-inset-top))]">
                <p class="text-[0.78rem] font-semibold text-[#6b7280]">Znaki drogowe</p>
                <h1 class="mt-1 text-[1.72rem] font-bold leading-8 text-[#17191d]">Trening zakończony</h1>
                <div class="mt-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[2.8rem] font-bold leading-none tabular-nums text-[#0b63ce]">{{ result.score_percent }}%</p>
                        <p class="mt-2 text-[0.86rem] text-[#667085]">{{ result.correct_answers_count }} poprawnych z {{ result.total_signs_count }}</p>
                    </div>
                    <p class="max-w-[8.5rem] pb-1 text-right text-[0.8rem] leading-5 text-[#667085]">
                        {{ result.mode === 'similar_signs' ? 'Podobne znaki' : result.mode === 'description_to_sign' ? 'Opis do znaku' : 'Rozpoznawanie' }}
                    </p>
                </div>
            </header>

            <section class="border-b border-[#dfe3e8] bg-white px-4 py-4">
                <form @submit.prevent="continueTraining">
                    <button
                        type="submit"
                        class="flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#0b63ce] px-4 text-[0.96rem] font-semibold text-white transition hover:bg-[#084fa8] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                        :disabled="startForm.processing"
                    >
                        Kontynuuj trening
                    </button>
                </form>
                <Link :href="route('session.traffic-signs')" class="mt-3 flex min-h-11 items-center justify-center text-[0.9rem] font-semibold text-[#0b63ce]">
                    Wróć do znaków
                </Link>
            </section>

            <section v-if="result.confusions.length > 0" class="mt-5 px-4" aria-labelledby="signs-confusions-heading">
                <h2 id="signs-confusions-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Warto powtórzyć</h2>
                <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <article v-for="confusion in result.confusions" :key="confusion.traffic_sign_id" class="flex min-h-[4.6rem] items-center gap-3 px-4 py-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[6px] bg-[#f7f8fa] p-1">
                            <img v-if="confusion.image_url" :src="confusion.image_url" :alt="confusion.name" class="max-h-full max-w-full object-contain" loading="lazy">
                            <span v-else class="text-[0.68rem] font-bold text-[#667085]">{{ confusion.code }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[0.9rem] font-semibold text-[#17191d]">{{ confusion.code }}</p>
                            <p class="truncate text-[0.78rem] text-[#667085]">{{ confusion.name }}</p>
                        </div>
                        <span class="text-[0.8rem] font-semibold tabular-nums text-[#b42318]">{{ confusion.count }}x</span>
                    </article>
                </div>
            </section>

            <section class="mt-5 px-4" aria-labelledby="signs-answers-heading">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="signs-answers-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Odpowiedzi</h2>
                    <button type="button" class="min-h-8 text-[0.82rem] font-semibold text-[#0b63ce]" @click="isAnswersOpen = !isAnswersOpen">
                        {{ isAnswersOpen ? 'Ukryj' : `Pokaż ${result.answers.length}` }}
                    </button>
                </div>
                <div v-if="isAnswersOpen" class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <article v-for="answer in result.answers" :key="answer.id" class="flex min-h-[4.5rem] items-center gap-3 px-4 py-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[6px] bg-[#f7f8fa] p-1">
                            <img v-if="answer.traffic_sign.image_url" :src="answer.traffic_sign.image_url" :alt="answer.traffic_sign.name" class="max-h-full max-w-full object-contain" loading="lazy">
                            <span v-else class="text-[0.68rem] font-bold text-[#667085]">{{ answer.traffic_sign.code }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[0.86rem] font-semibold text-[#17191d]">{{ answer.traffic_sign.code }}</p>
                            <p class="truncate text-[0.76rem] text-[#667085]">{{ answer.correct_label }}</p>
                        </div>
                        <span class="text-[0.76rem] font-semibold" :class="answer.is_correct ? 'text-[#157347]' : 'text-[#b42318]'">
                            {{ answer.is_correct ? 'Dobrze' : 'Powtórka' }}
                        </span>
                    </article>
                </div>
            </section>
        </section>

        <main class="hidden min-h-screen bg-[#f6f8fb] text-[#020309] md:block">
            <section class="border-b border-[#e5e7eb] bg-white">
                <div class="mx-auto grid w-full max-w-[76rem] gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_18rem] lg:px-8">
                    <div>
                        <p class="text-[0.76rem] font-semibold uppercase tracking-[0.16em] text-[#023ea4]">
                            Wynik
                        </p>
                        <h1 class="mt-2 text-[2rem] font-bold leading-[2.2rem] tracking-normal text-[#020309]">
                            {{ result.mode === 'similar_signs' ? 'Podobne znaki' : result.mode === 'description_to_sign' ? 'Opis -> znak' : 'Trening znaków drogowych' }}
                        </h1>
                        <p class="mt-3 text-[1rem] font-medium text-[#4b5563]">
                            {{ result.correct_answers_count }} poprawnych z {{ result.total_signs_count }} znaków.
                        </p>
                    </div>

                    <div class="rounded-[0.9rem] border border-[#dbe4f0] bg-white p-5 text-center shadow-[0_18px_42px_rgba(15,23,42,0.08)]">
                        <p class="text-[2.8rem] font-bold leading-none text-[#023ea4]">{{ result.score_percent }}%</p>
                        <p class="mt-2 text-[0.78rem] font-semibold uppercase tracking-[0.14em] text-[#6b7280]">
                            skuteczność
                        </p>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-[76rem] px-4 py-7 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2">
                        <form @submit.prevent="continueTraining">
                            <button
                                type="submit"
                                class="inline-flex h-11 items-center justify-center rounded-[0.72rem] bg-[#023ea4] px-5 text-[0.95rem] font-semibold text-white transition hover:bg-[#012b7a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                                :disabled="startForm.processing"
                            >
                                Kontynuuj trening
                            </button>
                        </form>
                        <Link
                            :href="route('session.traffic-signs')"
                            class="inline-flex h-11 items-center justify-center rounded-[0.72rem] border border-[#d1d5db] bg-white px-5 text-[0.95rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4]"
                        >
                            Panel znaków
                        </Link>
                    </div>
                </div>

                <div
                    v-if="result.confusions.length > 0"
                    class="mt-6 rounded-[0.9rem] border border-[#dbe4f0] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
                >
                    <h2 class="text-[1.05rem] font-bold text-[#020309]">Najczęściej mylisz z</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <article
                            v-for="confusion in result.confusions"
                            :key="confusion.traffic_sign_id"
                            class="flex items-center gap-3 rounded-[0.75rem] border border-[#edf0f4] bg-[#f9fafb] p-3"
                        >
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[0.6rem] bg-white p-1.5">
                                <img
                                    v-if="confusion.image_url"
                                    :src="confusion.image_url"
                                    :alt="confusion.name"
                                    class="max-h-full max-w-full object-contain"
                                    loading="lazy"
                                >
                                <span v-else class="text-[0.68rem] font-bold text-[#6b7280]">{{ confusion.code }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[0.76rem] font-bold uppercase tracking-[0.12em] text-[#b50d13]">
                                    {{ confusion.count }}x
                                </p>
                                <p class="truncate text-[0.92rem] font-bold text-[#020309]">{{ confusion.code }}</p>
                                <p class="truncate text-[0.78rem] font-medium text-[#6b7280]">{{ confusion.name }}</p>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="answer in result.answers"
                        :key="answer.id"
                        class="rounded-[0.85rem] border bg-white p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
                        :class="answer.is_correct ? 'border-[#bbf7d0]' : 'border-[#fecdd3]'"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[0.6rem] bg-[#f9fafb] p-1.5">
                                <img
                                    v-if="answer.traffic_sign.image_url"
                                    :src="answer.traffic_sign.image_url"
                                    :alt="answer.traffic_sign.name"
                                    class="max-h-full max-w-full object-contain"
                                    loading="lazy"
                                >
                                <span v-else class="text-[0.68rem] font-bold text-[#6b7280]">{{ answer.traffic_sign.code }}</span>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[0.76rem] font-bold uppercase tracking-[0.12em]"
                                    :class="answer.is_correct ? 'text-[#0d7b3a]' : 'text-[#b50d13]'"
                                >
                                    {{ answer.is_correct ? 'Dobrze' : 'Powtórka' }}
                                </p>
                                <p class="truncate text-[0.92rem] font-bold text-[#020309]">{{ answer.traffic_sign.code }}</p>
                                <p class="truncate text-[0.78rem] font-medium text-[#6b7280]">{{ answer.correct_label }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
