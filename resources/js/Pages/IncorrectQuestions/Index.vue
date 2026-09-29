<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Category {
    id: number;
    code: string;
    name: string;
    short_name: string;
}

interface Topic {
    id: number;
    name: string;
}

interface MediaItem {
    kind: 'image' | 'video';
    url: string | null;
    thumb_url?: string | null;
    poster_url?: string | null;
}

interface IncorrectQuestion {
    id: number;
    question_id: number;
    external_id: string;
    prompt: string;
    topic: Topic | null;
    media: MediaItem[];
    incorrect_count: number;
    last_incorrect_at_label: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: IncorrectQuestion[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    category: Category | null;
    filters: { topic: number | null };
    topics: Topic[];
    questions: PaginatedQuestions;
    stats: { active_count: number };
    preferences: { auto_remove_on_correct: boolean };
    actions: {
        study_home_url: string;
        start_session_url: string;
        preference_url: string;
    };
}>();

const preferenceForm = useForm({
    auto_remove_incorrect_questions_on_correct: props.preferences.auto_remove_on_correct,
});
const sessionForm = useForm({
    license_category_id: props.category?.id ?? null,
    mode: 'learn',
    ui_shell: 'exam_like',
    question_topic_id: props.filters.topic,
    question_scope: 'all',
    question_status: 'mistake_list',
    randomize_order: false,
    question_count: Math.max(props.stats.active_count, 1),
});

const hasQuestions = computed(() => props.stats.active_count > 0);

const selectTopic = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    router.get(
        route('incorrect-questions.index'),
        value ? { topic: Number(value) } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const savePreference = () => {
    preferenceForm.patch(props.actions.preference_url, {
        preserveScroll: true,
    });
};

const removeQuestion = (question: IncorrectQuestion) => {
    router.delete(route('incorrect-questions.destroy', question.id), {
        preserveScroll: true,
    });
};

const startSession = () => {
    if (!props.category || !hasQuestions.value) {
        return;
    }

    sessionForm.question_topic_id = props.filters.topic;
    sessionForm.question_count = Math.max(props.questions.total, 1);
    sessionForm.post(props.actions.start_session_url);
};

const primaryMedia = (question: IncorrectQuestion) => question.media[0] ?? null;
</script>

<template>
    <Head title="Pytania do poprawy" />

    <AuthenticatedLayout>
        <main class="min-h-screen bg-[#fbfcfd] px-4 py-5 sm:px-6 xl:py-6">
            <div class="mx-auto max-w-[72rem]">
                <Link
                    :href="actions.study_home_url"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#596671] transition hover:text-[#101820] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                >
                    <span aria-hidden="true">←</span>
                    Wróć do nauki
                </Link>

                <header class="mt-4 flex flex-wrap items-end justify-between gap-4 border-b border-[#d7dde1] pb-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-[#667085]">Kategoria {{ category?.short_name ?? '—' }}</p>
                        <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h1 class="text-2xl font-semibold leading-tight text-[#101820]">Pytania do poprawy</h1>
                            <span class="text-sm font-medium text-[#667085]">{{ stats.active_count }} {{ stats.active_count === 1 ? 'pytanie' : 'pytań' }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-[5px] bg-[#101820] px-4 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:cursor-not-allowed disabled:bg-[#98a2b3]"
                        :disabled="!hasQuestions || sessionForm.processing"
                        @click="startSession"
                    >
                        {{ sessionForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }}
                        <span v-if="!sessionForm.processing" aria-hidden="true">→</span>
                    </button>
                </header>

                <div class="py-4">
                    <section aria-labelledby="incorrect-list-heading">
                        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pb-3">
                            <h2 id="incorrect-list-heading" class="text-base font-semibold text-[#101828]">Twoja lista</h2>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                <label class="flex items-center gap-2 text-xs font-medium text-[#475467]">
                                    <span>Temat</span>
                                    <select
                                        class="min-h-9 w-44 rounded-[5px] border-[#d0d5dd] bg-white py-1 pr-8 text-xs font-medium text-[#344054] focus:border-[#0b5cff] focus:ring-[#0b5cff] sm:w-52"
                                        :value="filters.topic ?? ''"
                                        @change="selectTopic"
                                    >
                                        <option value="">Wszystkie tematy</option>
                                        <option v-for="topic in topics" :key="topic.id" :value="topic.id">
                                            {{ topic.name }}
                                        </option>
                                    </select>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input
                                        v-model="preferenceForm.auto_remove_incorrect_questions_on_correct"
                                        type="checkbox"
                                        class="peer sr-only"
                                        @change="savePreference"
                                    >
                                    <span class="relative h-5 w-9 shrink-0 rounded-full bg-[#cbd3d9] transition peer-checked:bg-[#344054] peer-focus-visible:ring-2 peer-focus-visible:ring-[#0b5cff] peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-4" aria-hidden="true" />
                                    <span class="text-xs font-medium text-[#475467]">Usuwaj po poprawnej odpowiedzi</span>
                                    <span class="sr-only">To ustawienie działa także na innych listach pytań do poprawy.</span>
                                </label>
                                <span v-if="preferenceForm.processing" class="text-xs text-[#667085]">Zapisywanie...</span>
                                <span v-if="preferenceForm.hasErrors" class="text-xs font-semibold text-[#b42318]">Nie udało się zapisać ustawienia.</span>
                            </div>
                        </div>

                        <div v-if="questions.data.length" class="space-y-2">
                            <article
                                v-for="question in questions.data"
                                :key="question.id"
                                class="group grid grid-cols-[4.5rem_minmax(0,1fr)] items-center gap-x-3 gap-y-2 bg-white px-3 py-2.5 transition-colors hover:bg-[#f8fbfd] sm:grid-cols-[6rem_minmax(0,1fr)_auto] sm:gap-x-4"
                            >
                                <div class="h-[3.375rem] w-[4.5rem] overflow-hidden bg-[#eef2f6] sm:h-[4.5rem] sm:w-[6rem]">
                                    <img
                                        v-if="primaryMedia(question)?.kind === 'image'"
                                        :src="primaryMedia(question)?.thumb_url || primaryMedia(question)?.url || ''"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                    >
                                    <img
                                        v-else-if="primaryMedia(question)?.poster_url"
                                        :src="primaryMedia(question)?.poster_url || ''"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                    >
                                    <div v-else class="grid h-full place-items-center px-1 text-center text-[0.65rem] font-semibold text-[#8491a3]">
                                        Pytanie tekstowe
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <p
                                        :title="question.topic?.name ?? 'Bez przypisanego tematu'"
                                        class="truncate text-[0.65rem] font-medium uppercase leading-4 tracking-[0.08em] text-[#7b8790]"
                                    >
                                        {{ question.topic?.name ?? 'Bez przypisanego tematu' }}
                                    </p>
                                    <h3 class="mt-0.5 line-clamp-2 text-sm font-semibold leading-5 text-[#101828]" :title="question.prompt">{{ question.prompt }}</h3>
                                    <p class="mt-1 text-xs leading-4 text-[#667085]">
                                        <span v-if="question.last_incorrect_at_label">Ostatni błąd: {{ question.last_incorrect_at_label }}</span>
                                        <span v-if="question.incorrect_count > 0" class="font-medium text-[#b42318]">{{ question.last_incorrect_at_label ? ' · ' : '' }}Błędy: {{ question.incorrect_count }}</span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="col-start-2 justify-self-start text-xs font-semibold text-[#b42318] underline decoration-[#f0b8b2] underline-offset-4 transition hover:text-[#8e1d15] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] sm:col-start-auto sm:justify-self-auto"
                                    :aria-label="`Usuń z listy: ${question.prompt}`"
                                    @click="removeQuestion(question)"
                                >
                                    Usuń <span class="sr-only">z listy</span>
                                </button>
                            </article>
                        </div>

                        <div v-else class="bg-white px-6 py-10 text-center">
                            <h3 class="text-lg font-semibold text-[#101828]">Nie masz teraz pytań do poprawy</h3>
                            <p class="mt-2 text-sm leading-6 text-[#667085]">
                                Błędnie rozwiązane pytania pojawią się na tej liście automatycznie.
                            </p>
                            <Link :href="actions.study_home_url" class="mt-5 inline-flex min-h-10 items-center font-semibold text-[#0b5cff] hover:text-[#0646bf]">
                                Wróć do nauki →
                            </Link>
                        </div>

                        <nav v-if="questions.last_page > 1" class="mt-4 flex flex-wrap gap-2" aria-label="Strony listy pytań do poprawy">
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
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
