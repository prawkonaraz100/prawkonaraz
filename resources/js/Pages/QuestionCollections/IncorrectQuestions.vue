<script setup lang="ts">
import MobileBackButton from '@/Components/MobileBackButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import IncorrectQuestionRemovalControls from '@/Components/IncorrectQuestionRemovalControls.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
    selection: { ids: number[] };
    stats: { active_count: number };
    preferences: { auto_remove_on_correct: boolean };
    actions: { start_url: string; preference_url: string; bulk_remove_url: string };
    active_session: { id: number; title: string } | null;
}>();

const startForm = useForm({ replace_active_session: false, replace_active_session_id: null as number | null });
const preferenceForm = useForm({
    auto_remove_incorrect_questions_on_correct: props.preferences.auto_remove_on_correct,
});
const showReplaceConfirmation = ref(false);
let pendingSessionId: number | null = null;
const hasQuestions = computed(() => props.stats.active_count > 0);
const selectedIds = ref<number[]>([]);
const confirmationOpen = ref(false);

const singleRemovalProcessing = ref(false);
const bulkNotice = ref('');
const bulkForm = useForm({ ids: [] as number[], page: props.questions.current_page });
const removalProcessing = computed(() => bulkForm.processing || singleRemovalProcessing.value);
const selectedIdSet = computed(() => new Set(selectedIds.value));
const allPageSelected = computed(() => props.questions.data.length > 0 && props.questions.data.every(question => selectedIdSet.value.has(question.id)));
const partlySelected = computed(() => props.questions.data.some(question => selectedIdSet.value.has(question.id)) && !allPageSelected.value);
const allListSelected = computed(() => props.selection.ids.length > 0 && props.selection.ids.every(id => selectedIdSet.value.has(id)));
const bulkError = computed(() => Object.values(bulkForm.errors)[0] ?? null);

watch(() => [props.collection.code, props.questions.current_page, props.questions.data.map(question => question.id).join(','), props.selection.ids.join(',')].join('|'), () => {
    selectedIds.value = [];
    confirmationOpen.value = false;
    bulkForm.clearErrors();
});
const togglePageSelection = (event: Event) => {
    const pageIds = props.questions.data.map(question => question.id);
    selectedIds.value = (event.target as HTMLInputElement).checked
        ? [...new Set([...selectedIds.value, ...pageIds])]
        : selectedIds.value.filter(id => !pageIds.includes(id));
    bulkForm.clearErrors();
    bulkNotice.value = '';
};
const toggleListSelection = () => {
    selectedIds.value = allListSelected.value ? [] : [...props.selection.ids];
    bulkForm.clearErrors();
    bulkNotice.value = '';
};
const removeSelected = () => {
    if (!selectedIds.value.length || removalProcessing.value || startForm.processing) return;
    bulkForm.ids = [...selectedIds.value];
    bulkForm.page = props.questions.current_page;
    bulkForm.delete(props.actions.bulk_remove_url, {
        preserveScroll: true,
        onSuccess: page => {
            selectedIds.value = [];
            confirmationOpen.value = false;
            bulkNotice.value = (page.props.flash as { status?: string } | undefined)?.status ?? 'Zaznaczone pytania usunięto z listy kursu. Historia i postęp pozostają bez zmian.';
        },
        onError: () => { confirmationOpen.value = false; },
    });
};

const startReview = (replaceActiveSession = false) => {
    startForm.replace_active_session = replaceActiveSession;
    startForm.replace_active_session_id = replaceActiveSession ? pendingSessionId : null;
    startForm.post(props.actions.start_url, {
        onFinish: () => {
            startForm.replace_active_session = false;
            startForm.replace_active_session_id = null;
        },
    });
};

const requestStart = () => {
    if (!hasQuestions.value || removalProcessing.value || startForm.processing) {
        return;
    }

    if (props.active_session) {
        pendingSessionId = props.active_session.id;
        showReplaceConfirmation.value = true;

        return;
    }

    startReview();
};

const removeQuestion = (question: CourseQuestion) => {
    if (removalProcessing.value || startForm.processing) return;
    singleRemovalProcessing.value = true;
    bulkNotice.value = '';
    router.delete(question.remove_url, {
        preserveScroll: true,
        onFinish: () => { singleRemovalProcessing.value = false; },
    });
};

const savePreference = () => {
    preferenceForm.patch(props.actions.preference_url, { preserveScroll: true });
};

const primaryMedia = (question: CourseQuestion) => question.media[0] ?? null;
</script>

<template>
    <Head :title="`Pytania do poprawy - ${collection.name}`" />

    <AuthenticatedLayout>
        <main class="min-h-screen bg-[#fbfcfd] px-4 py-5 max-md:pt-[max(env(safe-area-inset-top),1rem)] sm:px-6 xl:py-6">
            <div class="mx-auto max-w-[72rem]">
                <Link
                    :href="collection.url"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#596671] transition hover:text-[#101820] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] max-md:hidden"
                >
                    <span aria-hidden="true">←</span>
                    Wróć do kursu
                </Link>

                <header class="mt-4 flex flex-wrap items-end justify-between gap-4 border-b border-[#d7dde1] pb-4 max-md:mt-0">
                    <div class="flex min-w-0 items-center gap-3">
                    <MobileBackButton :href="collection.url" label="Wróć do kursu" />
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-[#667085]">{{ collection.name }}</p>
                        <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h1 class="text-2xl font-semibold leading-tight text-[#101820] max-md:text-[1.75rem] max-md:font-bold max-md:leading-[1.1] max-md:tracking-[-0.015em]">Pytania do poprawy</h1>
                            <span class="text-sm font-medium text-[#667085]">{{ stats.active_count }} {{ stats.active_count === 1 ? 'pytanie' : 'pytań' }}</span>
                        </div>
                    </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-[5px] bg-[#101820] px-4 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:cursor-not-allowed disabled:bg-[#98a2b3]"
                        :disabled="!hasQuestions || startForm.processing || removalProcessing"
                        @click="requestStart"
                    >
                        {{ startForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }} <span v-if="!startForm.processing" aria-hidden="true">→</span>
                    </button>
                </header>

                <div class="py-4">
                    <section aria-labelledby="course-incorrect-list-heading">
                        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pb-3">
                            <h2 id="course-incorrect-list-heading" class="text-base font-semibold text-[#101828]">Twoja lista</h2>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input
                                        v-model="preferenceForm.auto_remove_incorrect_questions_on_correct"
                                        type="checkbox"
                                        class="peer sr-only"
                                        :disabled="removalProcessing || preferenceForm.processing"
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

                        <IncorrectQuestionRemovalControls
                            :page-count="questions.data.length"
                            :total="questions.total"
                            :selection-count="selection.ids.length"
                            :selected-count="selectedIds.length"
                            :all-page-selected="allPageSelected"
                            :partly-selected="partlySelected"
                            :all-list-selected="allListSelected"
                            :disabled="removalProcessing || startForm.processing"
                            :processing="bulkForm.processing"
                            :confirmation-open="confirmationOpen"
                            :context-label="collection.name"
                            :notice="bulkNotice"
                            :error="bulkError"
                            @toggle-page="togglePageSelection"
                            @toggle-list="toggleListSelection"
                            @open="confirmationOpen = true; bulkForm.clearErrors()"
                            @close="confirmationOpen = false"
                            @confirm="removeSelected"
                        />

                        <div v-if="questions.data.length" class="space-y-2">
                            <article
                                v-for="question in questions.data"
                                :key="question.id"
                                class="group grid grid-cols-[2.75rem_4.5rem_minmax(0,1fr)] items-center gap-x-2 gap-y-2 bg-white px-3 py-2.5 transition-colors hover:bg-[#f8fbfd] sm:grid-cols-[2.75rem_6rem_minmax(0,1fr)_auto] sm:gap-x-4"
                            >
                                <label class="row-span-2 grid min-h-11 w-11 cursor-pointer place-items-center sm:row-span-1">
                                    <input v-model="selectedIds" type="checkbox" :value="question.id" :disabled="removalProcessing || startForm.processing" :aria-label="`Zaznacz pytanie: ${question.prompt}`" class="h-4 w-4 rounded border-[#98a2b3] text-[#344054] focus:ring-[#0b5cff]">
                                </label>
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
                                        :title="question.modules.map((module) => `Moduł ${module.code}`).join(' · ') || 'Kurs zawodowy'"
                                        class="truncate text-[0.65rem] font-medium uppercase leading-4 tracking-[0.08em] text-[#7b8790]"
                                    >
                                        {{ question.modules.map((module) => `Moduł ${module.code}`).join(' · ') || 'Kurs zawodowy' }}
                                    </p>
                                    <h3 class="mt-0.5 line-clamp-2 text-sm font-semibold leading-5 text-[#101828]" :title="question.prompt">{{ question.prompt }}</h3>
                                    <p class="mt-1 text-xs leading-4 text-[#667085]">
                                        <span v-if="question.last_incorrect_at_label">Ostatni błąd: {{ question.last_incorrect_at_label }}</span>
                                        <span v-if="question.incorrect_count > 0" class="font-medium text-[#b42318]"> · Błędy: {{ question.incorrect_count }}</span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="col-start-3 min-h-11 justify-self-start text-xs font-semibold text-[#b42318] underline decoration-[#f0b8b2] underline-offset-4 transition hover:text-[#8e1d15] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:opacity-50 sm:col-start-auto sm:justify-self-auto"
                                    :disabled="removalProcessing || startForm.processing"
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
                                Błędnie rozwiązane pytanie z modułu kursu pojawi się tutaj automatycznie.
                            </p>
                            <Link :href="collection.url" class="mt-5 inline-flex min-h-10 items-center font-semibold text-[#0b5cff] hover:text-[#0646bf]">
                                Wróć do modułów →
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
                                    !link.url || removalProcessing || startForm.processing ? 'pointer-events-none opacity-40' : 'hover:border-[#98a2b3]',
                                ]"
                                :aria-disabled="!link.url || removalProcessing || startForm.processing"
                                @click="($event) => { if (removalProcessing || startForm.processing) $event.preventDefault(); }"
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
