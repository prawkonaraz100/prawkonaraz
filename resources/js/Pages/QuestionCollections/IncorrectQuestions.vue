<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface MediaItem {
    kind: 'image' | 'video';
    url: string | null;
    thumb_url?: string | null;
    poster_url?: string | null;
}

interface CourseQuestion {
    id: number;
    prompt: string;
    media: MediaItem[];
    modules: Array<{ id: number; code: string; name: string }>;
    incorrect_count: number;
    last_incorrect_at_label: string | null;
    remove_url: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: CourseQuestion[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    collection: { code: string; name: string; url: string };
    questions: PaginatedQuestions;
    stats: { active_count: number };
    preferences: { auto_remove_on_correct: boolean };
    actions: { start_url: string; preference_url: string };
    active_session: { id: number; title: string } | null;
}>();

const startForm = useForm({ replace_active_session: false });
const preferenceForm = useForm({
    auto_remove_incorrect_questions_on_correct: props.preferences.auto_remove_on_correct,
});
const showReplaceConfirmation = ref(false);
const hasQuestions = computed(() => props.stats.active_count > 0);

const startReview = (replaceActiveSession = false) => {
    startForm.replace_active_session = replaceActiveSession;
    startForm.post(props.actions.start_url, {
        onFinish: () => {
            startForm.replace_active_session = false;
        },
    });
};

const requestStart = () => {
    if (!hasQuestions.value) {
        return;
    }

    if (props.active_session) {
        showReplaceConfirmation.value = true;

        return;
    }

    startReview();
};

const removeQuestion = (question: CourseQuestion) => {
    router.delete(question.remove_url, { preserveScroll: true });
};

const savePreference = () => {
    preferenceForm.patch(props.actions.preference_url, { preserveScroll: true });
};

const primaryMedia = (question: CourseQuestion) => question.media[0] ?? null;
</script>

<template>
    <Head :title="`Pytania do poprawy - ${collection.name}`" />

    <AuthenticatedLayout>
        <main class="min-h-screen bg-[#fbfcfd] px-6 py-9 xl:px-10 xl:py-12">
            <div class="mx-auto max-w-[88rem]">
                <Link
                    :href="collection.url"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#596671] transition hover:text-[#101820] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                >
                    <span aria-hidden="true">←</span>
                    Wróć do kursu
                </Link>

                <header class="mt-8 border-b border-[#d7dde1] pb-8 2xl:grid 2xl:grid-cols-[minmax(0,1fr)_26rem] 2xl:gap-x-14">
                    <div class="max-w-3xl">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-[#ef3b26]">{{ collection.name }}</p>
                        <h1 class="mt-3 text-3xl font-semibold leading-tight text-[#101820] xl:text-[2.55rem]">Pytania do poprawy</h1>
                        <p class="mt-4 text-base leading-7 text-[#475467]">
                            Wróć do pytań, przy których odpowiedź sprawiła Ci trudność. Rozwiązuj je po kolei, aż znikną z listy.
                        </p>
                    </div>
                    <div class="mt-8 border-y border-[#d7dde1] py-4 2xl:mt-0 2xl:self-end">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#78838d]">Do powtórki</p>
                        <div class="mt-2 flex items-center justify-between gap-5">
                            <p class="text-2xl font-semibold text-[#101820]">
                                {{ stats.active_count }} <span class="text-sm font-medium text-[#667085]">{{ stats.active_count === 1 ? 'pytanie' : 'pytań' }}</span>
                            </p>
                        <button
                            type="button"
                                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:cursor-not-allowed disabled:bg-[#98a2b3]"
                            :disabled="!hasQuestions || startForm.processing"
                            @click="requestStart"
                        >
                                {{ startForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }} <span v-if="!startForm.processing" aria-hidden="true">→</span>
                        </button>
                        </div>
                    </div>
                </header>

                <div class="grid gap-10 py-9 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
                    <section aria-labelledby="course-incorrect-list-heading" class="min-w-0">
                        <div class="flex items-center justify-between gap-4 border-b border-[#d7dde1] pb-5">
                            <div>
                                <h2 id="course-incorrect-list-heading" class="text-2xl font-semibold text-[#101828]">Twoja lista</h2>
                                <p class="mt-2 text-sm leading-6 text-[#667085]">Pytania zostają tutaj, dopóki ich nie usuniesz lub nie rozwiążesz poprawnie.</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-[#475467]">{{ stats.active_count }}</span>
                        </div>

                        <div v-if="questions.data.length" class="mt-6 divide-y divide-[#dfe3e8] border-y border-[#dfe3e8] bg-white">
                            <article
                                v-for="question in questions.data"
                                :key="question.id"
                                class="group grid gap-5 px-5 py-6 transition-colors hover:bg-[#fbfcfd] sm:grid-cols-[9rem_minmax(0,1fr)_auto] sm:items-center sm:px-6 xl:px-8"
                            >
                                <div class="overflow-hidden bg-[#eef2f6]">
                                    <img
                                        v-if="primaryMedia(question)?.kind === 'image'"
                                        :src="primaryMedia(question)?.thumb_url || primaryMedia(question)?.url || ''"
                                        alt=""
                                        class="aspect-[4/3] h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                    >
                                    <img
                                        v-else-if="primaryMedia(question)?.poster_url"
                                        :src="primaryMedia(question)?.poster_url || ''"
                                        alt=""
                                        class="aspect-[4/3] h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                    >
                                    <div v-else class="grid aspect-[4/3] place-items-center px-3 text-center text-xs font-semibold text-[#8491a3]">
                                        Pytanie tekstowe
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <p
                                        :title="question.modules.map((module) => `Moduł ${module.code}`).join(' · ') || 'Kurs zawodowy'"
                                        class="line-clamp-2 text-[0.64rem] font-medium uppercase leading-4 tracking-[0.08em] text-[#7b8790]"
                                    >
                                        {{ question.modules.map((module) => `Moduł ${module.code}`).join(' · ') || 'Kurs zawodowy' }}
                                    </p>
                                    <h3 class="mt-2 text-base font-semibold leading-6 text-[#101828] xl:text-[1.05rem]">{{ question.prompt }}</h3>
                                    <p class="mt-3 text-sm leading-6 text-[#667085]">
                                        <span v-if="question.last_incorrect_at_label">Ostatni błąd: {{ question.last_incorrect_at_label }}</span>
                                        <span v-if="question.incorrect_count > 0" class="font-medium text-[#b42318]"> · Błędne odpowiedzi: {{ question.incorrect_count }}</span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="self-start text-sm font-semibold text-[#b42318] underline decoration-[#f0b8b2] underline-offset-4 transition hover:text-[#8e1d15] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] sm:self-center"
                                    @click="removeQuestion(question)"
                                >
                                    Usuń <span class="sr-only">z listy</span>
                                </button>
                            </article>
                        </div>

                        <div v-else class="mt-6 border-y border-[#dfe3e8] bg-white px-6 py-14 text-center">
                            <h3 class="text-lg font-semibold text-[#101828]">Nie masz teraz pytań do poprawy</h3>
                            <p class="mt-2 text-sm leading-6 text-[#667085]">
                                Błędnie rozwiązane pytanie z modułu kursu pojawi się tutaj automatycznie.
                            </p>
                            <Link :href="collection.url" class="mt-5 inline-flex min-h-10 items-center font-semibold text-[#0b5cff] hover:text-[#0646bf]">
                                Wróć do modułów →
                            </Link>
                        </div>

                        <nav v-if="questions.last_page > 1" class="mt-6 flex flex-wrap gap-2" aria-label="Strony listy pytań do poprawy">
                            <Link
                                v-for="link in questions.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-[5px] border px-3 text-sm font-semibold"
                                :class="[
                                    link.active ? 'border-[#101820] bg-[#101820] text-white' : 'border-[#d0d5dd] bg-white text-[#475467]',
                                    !link.url ? 'pointer-events-none opacity-40' : 'hover:border-[#98a2b3]',
                                ]"
                                preserve-scroll
                            >
                                <span v-html="link.label" />
                            </Link>
                        </nav>
                    </section>

                    <aside class="border-t border-[#d7dde1] pt-6 xl:sticky xl:top-8 xl:border-l xl:border-t-0 xl:pl-8 xl:pt-1">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#78838d]">Ustawienie konta</p>
                        <h2 class="mt-2 text-lg font-semibold text-[#101828]">Automatyczne porządkowanie</h2>
                        <p class="mt-3 text-sm leading-6 text-[#667085]">
                            Po poprawnej odpowiedzi pytanie może automatycznie zniknąć z tej listy.
                        </p>
                        <label class="mt-6 flex cursor-pointer items-center gap-3">
                            <input
                                v-model="preferenceForm.auto_remove_incorrect_questions_on_correct"
                                type="checkbox"
                                class="peer sr-only"
                                @change="savePreference"
                            >
                            <span class="relative h-6 w-11 shrink-0 rounded-full bg-[#cbd3d9] transition peer-checked:bg-[#ef3b26] peer-focus-visible:ring-2 peer-focus-visible:ring-[#0b5cff] peer-focus-visible:ring-offset-2 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5" aria-hidden="true" />
                            <span class="text-sm font-semibold leading-6 text-[#344054]">
                                Usuwaj po poprawnej odpowiedzi
                            </span>
                        </label>
                        <p class="mt-3 text-xs leading-5 text-[#78838d]">To ustawienie działa także na innych listach pytań do poprawy.</p>
                        <p v-if="preferenceForm.processing" class="mt-3 text-xs text-[#667085]">Zapisywanie...</p>
                        <p v-if="preferenceForm.hasErrors" class="mt-3 text-xs font-semibold text-[#b42318]">
                            Nie udało się zapisać ustawienia.
                        </p>
                    </aside>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>

    <div v-if="showReplaceConfirmation" class="fixed inset-0 z-50 flex items-center justify-center bg-[#101828]/45 p-5" role="presentation">
        <section class="w-full max-w-md rounded-[6px] bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="replace-course-session-title">
            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-[#667085]">Bieżąca sesja</p>
            <h2 id="replace-course-session-title" class="mt-2 text-xl font-semibold text-[#101828]">Rozpocząć powtórkę?</h2>
            <p class="mt-3 text-sm leading-6 text-[#475467]">
                Masz rozpoczętą sesję: {{ active_session?.title }}. Powtórka zakończy ją w obecnym miejscu.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="min-h-11 rounded-[5px] border border-[#d0d5dd] px-4 text-sm font-semibold text-[#344054] transition hover:bg-[#f9fafb] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]" :disabled="startForm.processing" @click="showReplaceConfirmation = false">
                    Zostań przy sesji
                </button>
                <button type="button" class="min-h-11 rounded-[5px] bg-[#101820] px-4 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:cursor-not-allowed disabled:bg-[#98a2b3]" :disabled="startForm.processing" @click="startReview(true)">
                    Rozpocznij powtórkę
                </button>
            </div>
            <p v-if="startForm.errors.replace_active_session" class="mt-4 text-sm text-[#b42318]">
                {{ startForm.errors.replace_active_session }}
            </p>
        </section>
    </div>
</template>
