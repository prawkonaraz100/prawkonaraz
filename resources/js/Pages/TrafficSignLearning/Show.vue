<script setup lang="ts">
import SessionExpiredNotice from '@/Components/SessionExpiredNotice.vue';
import { useSessionExpiry } from '@/composables/useSessionExpiry';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { apiClient } from '@/lib/apiClient';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface TrafficSignOption {
    traffic_sign_id: number;
    label: string;
    code: string | null;
    image_url: string | null;
}

interface TrafficSignQuestion {
    answer_id: number;
    mode: 'recognition' | 'similar_signs' | 'description_to_sign';
    answer_mode: 'sign_to_meaning' | 'meaning_to_sign';
    position: number;
    total: number;
    answered: boolean;
    has_more: boolean;
    sign: {
        id: number;
        code: string;
        name: string;
        image_url: string | null;
        public_url: string;
    };
    prompt: string | null;
    explanation: string;
    options: TrafficSignOption[];
    feedback: null | {
        is_correct: boolean;
        selected_traffic_sign_id: number | null;
        correct_traffic_sign_id: number;
        correct_label: string;
        explanation: string;
    };
    next_url: string;
}

const props = defineProps<{
    session: {
        id: number;
        mode: 'recognition' | 'similar_signs' | 'description_to_sign';
        status: string;
        total_signs_count: number;
    };
    question: TrafficSignQuestion;
    questions: TrafficSignQuestion[];
}>();

interface PendingAnswer {
    answer_id: number;
    selected_traffic_sign_id: number;
    response_time_ms: number | null;
}

interface SyncAnswersResponse {
    completed: boolean;
    redirect_url?: string;
    synced_answer_ids: number[];
}

const SYNC_BATCH_SIZE = 4;
const SYNC_DEBOUNCE_MS = 3500;

const cloneQuestion = (source: TrafficSignQuestion): TrafficSignQuestion => ({
    ...source,
    sign: { ...source.sign },
    options: source.options.map((option) => ({ ...option })),
    feedback: source.feedback ? { ...source.feedback } : null,
});

const initialQuestions = () => (
    props.questions.length > 0
        ? props.questions.map((sessionQuestion) => cloneQuestion(sessionQuestion))
        : [cloneQuestion(props.question)]
);

const allQuestions = ref<TrafficSignQuestion[]>(initialQuestions());
const question = ref<TrafficSignQuestion>(cloneQuestion(props.question));
const startedAt = ref(Date.now());
const selectedTrafficSignId = ref<number | null>(null);
const isLoadingNext = ref(false);
const syncError = ref<string | null>(null);
const isSyncing = ref(false);
const isExplanationOpen = ref(false);
const pendingAnswerQueue = ref<PendingAnswer[]>([]);
let correctAdvanceTimeout: ReturnType<typeof setTimeout> | null = null;
let syncDebounceTimeout: ReturnType<typeof setTimeout> | null = null;
let activeSyncPromise: Promise<boolean> | null = null;
const {
    handleSessionExpiryError,
    sessionExpired,
} = useSessionExpiry(() => {
    clearCorrectAdvanceTimeout();
    clearSyncDebounceTimeout();
    pendingAnswerQueue.value = [];
    isLoadingNext.value = false;
    isSyncing.value = false;
    syncError.value = 'Sesja wygasła. Ostatnia odpowiedź nie została potwierdzona przez serwer.';
});

const clearCorrectAdvanceTimeout = () => {
    if (correctAdvanceTimeout === null) {
        return;
    }

    clearTimeout(correctAdvanceTimeout);
    correctAdvanceTimeout = null;
};

const clearSyncDebounceTimeout = () => {
    if (syncDebounceTimeout === null) {
        return;
    }

    clearTimeout(syncDebounceTimeout);
    syncDebounceTimeout = null;
};

watch(
    () => [props.question, props.questions] as const,
    (nextQuestion) => {
        clearCorrectAdvanceTimeout();
        allQuestions.value = initialQuestions();
        question.value = cloneQuestion(nextQuestion[0]);
        selectedTrafficSignId.value = null;
        isLoadingNext.value = false;
        syncError.value = null;
        isExplanationOpen.value = false;
        startedAt.value = Date.now();
    },
);

const applyQuestion = (nextQuestion: TrafficSignQuestion) => {
    clearCorrectAdvanceTimeout();
    question.value = cloneQuestion(nextQuestion);
    selectedTrafficSignId.value = null;
    isLoadingNext.value = false;
    isExplanationOpen.value = false;
    startedAt.value = Date.now();
};

const replaceQuestionInSnapshot = (nextQuestion: TrafficSignQuestion) => {
    allQuestions.value = allQuestions.value.map((sessionQuestion) => (
        sessionQuestion.answer_id === nextQuestion.answer_id
            ? cloneQuestion(nextQuestion)
            : sessionQuestion
    ));
};

const nextLocalQuestion = () => {
    const currentIndex = allQuestions.value.findIndex(
        (sessionQuestion) => sessionQuestion.answer_id === question.value.answer_id,
    );

    if (currentIndex < 0) {
        return null;
    }

    return allQuestions.value[currentIndex + 1] ?? null;
};

const sendPendingBatch = async (): Promise<boolean> => {
    if (pendingAnswerQueue.value.length === 0) {
        return true;
    }

    const batch = pendingAnswerQueue.value.splice(0, pendingAnswerQueue.value.length);

    isSyncing.value = true;

    try {
        await apiClient.post<SyncAnswersResponse>(
            route('traffic-sign-learning.answers.sync'),
            { answers: batch },
        );
        syncError.value = null;

        return true;
    } catch (error) {
        if (handleSessionExpiryError(error)) {
            return false;
        }

        pendingAnswerQueue.value = [...batch, ...pendingAnswerQueue.value];
        syncError.value = 'Nie udało się zapisać odpowiedzi. Sprawdź połączenie i spróbuj przejść dalej jeszcze raz.';

        return false;
    } finally {
        isSyncing.value = false;
    }
};

const flushPendingAnswers = async (): Promise<boolean> => {
    clearSyncDebounceTimeout();

    if (activeSyncPromise !== null) {
        const activeResult = await activeSyncPromise;

        if (!activeResult) {
            return false;
        }
    }

    while (pendingAnswerQueue.value.length > 0) {
        activeSyncPromise = sendPendingBatch();
        const result = await activeSyncPromise;
        activeSyncPromise = null;

        if (!result) {
            return false;
        }
    }

    return true;
};

const schedulePendingSync = () => {
    clearSyncDebounceTimeout();
    syncDebounceTimeout = setTimeout(() => {
        syncDebounceTimeout = null;
        void flushPendingAnswers();
    }, SYNC_DEBOUNCE_MS);
};

const queueAnswerSync = (answer: PendingAnswer) => {
    if (sessionExpired.value) {
        return;
    }

    pendingAnswerQueue.value.push(answer);
    syncError.value = null;

    if (pendingAnswerQueue.value.length >= SYNC_BATCH_SIZE) {
        void flushPendingAnswers();

        return;
    }

    schedulePendingSync();
};

onBeforeUnmount(() => {
    clearCorrectAdvanceTimeout();
    clearSyncDebounceTimeout();

    if (!sessionExpired.value && pendingAnswerQueue.value.length > 0) {
        void flushPendingAnswers();
    }
});

const goNext = async () => {
    if (isLoadingNext.value || sessionExpired.value) {
        return;
    }

    clearCorrectAdvanceTimeout();

    const localNextQuestion = nextLocalQuestion();

    if (localNextQuestion) {
        applyQuestion(localNextQuestion);

        return;
    }

    isLoadingNext.value = true;

    const synced = await flushPendingAnswers();

    isLoadingNext.value = false;

    if (!synced) {
        return;
    }

    router.visit(question.value.next_url);
};

const scheduleCorrectAdvance = () => {
    clearCorrectAdvanceTimeout();
    correctAdvanceTimeout = setTimeout(() => {
        correctAdvanceTimeout = null;
        void goNext();
    }, 450);
};

const chooseAnswer = async (trafficSignId: number) => {
    if (question.value.answered || sessionExpired.value) {
        return;
    }

    const isCorrect = trafficSignId === question.value.sign.id;
    const responseTimeMs = Date.now() - startedAt.value;
    const answeredQuestion = {
        ...question.value,
        answered: true,
        feedback: {
            is_correct: isCorrect,
            selected_traffic_sign_id: trafficSignId,
            correct_traffic_sign_id: question.value.sign.id,
            correct_label: question.value.sign.name,
            explanation: question.value.explanation,
        },
    };

    selectedTrafficSignId.value = trafficSignId;
    isExplanationOpen.value = false;
    question.value = answeredQuestion;
    replaceQuestionInSnapshot(answeredQuestion);
    queueAnswerSync({
        answer_id: answeredQuestion.answer_id,
        selected_traffic_sign_id: trafficSignId,
        response_time_ms: responseTimeMs,
    });

    if (isCorrect) {
        scheduleCorrectAdvance();
    }
};

const optionClass = (option: TrafficSignOption) => {
    if (!question.value.feedback) {
        return selectedTrafficSignId.value === option.traffic_sign_id
            ? 'border-[#023ea4] bg-[#eef2fa] text-[#012b7a]'
            : 'border-[#dbe4f0] bg-white text-[#020309] hover:border-[#023ea4] hover:bg-[#f8fbff]';
    }

    if (option.traffic_sign_id === question.value.feedback.correct_traffic_sign_id) {
        return 'border-[#109447] bg-[#ecfdf3] text-[#0d7b3a]';
    }

    if (option.traffic_sign_id === question.value.feedback.selected_traffic_sign_id) {
        return 'border-[#df1018] bg-[#fff1f2] text-[#b50d13]';
    }

    return 'border-[#e5e7eb] bg-[#f9fafb] text-[#6b7280]';
};

const showsWrongFeedback = computed(() => question.value.feedback !== null && !question.value.feedback.is_correct);
const showsExplanationCard = computed(() => showsWrongFeedback.value || isExplanationOpen.value);
const explanationTitle = computed(() => (showsWrongFeedback.value ? 'Do powtórki' : 'Wyjaśnienie'));
const explanationTitleClass = computed(() => (showsWrongFeedback.value ? 'text-[#b50d13]' : 'text-[#023ea4]'));
const explanationLabel = computed(() => question.value.feedback?.correct_label ?? question.value.sign.name);
const explanationText = computed(() => question.value.feedback?.explanation || question.value.explanation);
const backToQuestionLabel = computed(() => (
    question.value.answer_mode === 'meaning_to_sign'
        ? 'Wróć do opisu'
        : 'Wróć do znaku'
));
const explanationActionLabel = computed(() => (
    question.value.answer_mode === 'meaning_to_sign'
        ? 'Pokaż wyjaśnienie opisu znaku'
        : 'Pokaż wyjaśnienie znaku'
));

const toggleExplanation = () => {
    if (showsWrongFeedback.value) {
        return;
    }

    isExplanationOpen.value = !isExplanationOpen.value;
};

const closeExplanation = () => {
    isExplanationOpen.value = false;
};
</script>

<template>
    <Head title="Trening znaków drogowych" />

    <SessionExpiredNotice v-if="sessionExpired" />

    <AuthenticatedLayout>
        <section class="min-h-[100svh] bg-[#f7f8fa] text-[#17191d] md:hidden">
            <header class="border-b border-[#dfe3e8] bg-white px-4 pb-4 pt-[calc(1rem+env(safe-area-inset-top))]">
                <div class="flex items-center justify-between gap-4">
                    <Link
                        :href="route('session.traffic-signs')"
                        aria-label="Wróć do znaków drogowych"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-[#344054] transition hover:bg-[#f2f4f7] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce]"
                    >
                        <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <path d="m14.5 5-7 7 7 7" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                        </svg>
                    </Link>
                    <div class="min-w-0 flex-1 text-center">
                        <p class="text-[0.8rem] font-semibold text-[#17191d]">Znaki drogowe</p>
                        <p class="mt-0.5 text-[0.72rem] text-[#667085]">
                            {{ question.mode === 'similar_signs' ? 'Podobne znaki' : question.answer_mode === 'meaning_to_sign' ? 'Opis do znaku' : 'Rozpoznawanie' }}
                        </p>
                    </div>
                    <p class="w-10 text-right text-[0.82rem] font-semibold tabular-nums text-[#344054]">
                        {{ question.position }}/{{ question.total }}
                    </p>
                </div>
                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-[#e8edf3]" aria-label="Postęp treningu">
                    <div
                        class="h-full rounded-full bg-[#0b63ce] transition-[width] duration-300 ease-out"
                        :style="{ width: `${Math.min((question.position / question.total) * 100, 100)}%` }"
                    />
                </div>
            </header>

            <Transition name="sign-player-question" mode="out-in">
                <section :key="question.answer_id" class="mx-auto w-full max-w-xl px-4 pb-[calc(1.5rem+env(safe-area-inset-bottom))] pt-6">
                    <div class="mb-5">
                        <p class="text-[0.76rem] font-semibold text-[#667085]">
                            {{ question.answer_mode === 'meaning_to_sign' ? 'Wybierz właściwy znak' : 'Wybierz właściwe znaczenie' }}
                        </p>
                        <h1 class="mt-1 text-[1.35rem] font-bold leading-7 text-[#17191d]">
                            {{ question.answer_mode === 'meaning_to_sign' ? 'Wybierz znak pasujący do opisu' : 'Co oznacza ten znak?' }}
                        </h1>
                    </div>

                    <section
                        v-if="question.answer_mode === 'meaning_to_sign'"
                        class="border-y border-[#dfe3e8] bg-white px-4 py-5"
                        aria-label="Opis znaku"
                    >
                        <p class="text-[0.76rem] font-semibold text-[#667085]">Opis</p>
                        <p class="mt-2 text-[1.02rem] font-medium leading-6 text-[#344054]">{{ question.prompt }}</p>
                    </section>

                    <section
                        v-else
                        class="flex min-h-[17rem] items-center justify-center border-y border-[#dfe3e8] bg-white px-6 py-7"
                        aria-label="Znak do rozpoznania"
                    >
                        <img
                            v-if="question.sign.image_url"
                            :src="question.sign.image_url"
                            alt="Znak drogowy do rozpoznania"
                            class="max-h-[15rem] max-w-full object-contain"
                        >
                        <p v-else class="text-center text-[2rem] font-bold text-[#0b63ce]">{{ question.sign.code }}</p>
                    </section>

                    <div class="mt-5 grid gap-3" :class="question.answer_mode === 'meaning_to_sign' ? 'grid-cols-2' : ''">
                        <button
                            v-for="option in question.options"
                            :key="option.traffic_sign_id"
                            type="button"
                            class="border text-[0.96rem] font-semibold transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-default"
                            :class="[
                                optionClass(option),
                                question.answer_mode === 'meaning_to_sign'
                                    ? 'flex min-h-[9.5rem] items-center justify-center rounded-[8px] p-3'
                                    : 'min-h-[4rem] rounded-[8px] px-4 py-3 text-left leading-5',
                            ]"
                            :disabled="question.answered"
                            @click="chooseAnswer(option.traffic_sign_id)"
                        >
                            <template v-if="question.answer_mode === 'meaning_to_sign'">
                                <span class="sr-only">{{ option.label }}</span>
                                <img
                                    v-if="option.image_url"
                                    :src="option.image_url"
                                    alt=""
                                    class="max-h-[7.5rem] max-w-full object-contain"
                                >
                                <span v-else class="text-[1.35rem] font-bold text-[#0b63ce]">{{ option.code }}</span>
                            </template>
                            <template v-else>
                                {{ option.label }}
                            </template>
                        </button>
                    </div>

                    <Transition name="sign-player-feedback">
                        <section
                            v-if="question.feedback?.is_correct"
                            class="mt-4 flex min-h-12 items-center gap-3 border-y border-[#b7ebc6] bg-[#effcf3] px-4 py-3 text-[#157347]"
                            aria-live="polite"
                        >
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#157347] text-[0.8rem] font-bold text-white">✓</span>
                            <p class="text-[0.9rem] font-semibold">Dobrze. Przechodzimy do następnego znaku.</p>
                        </section>
                    </Transition>

                    <button
                        v-if="!question.answered && !isExplanationOpen"
                        type="button"
                        class="mt-5 min-h-10 text-[0.86rem] font-semibold text-[#0b63ce]"
                        @click="toggleExplanation"
                    >
                        Wyjaśnienie znaku
                    </button>

                    <p v-if="syncError" class="mt-4 text-[0.86rem] font-semibold text-[#b42318]">{{ syncError }}</p>
                </section>
            </Transition>

            <Teleport to="body">
                <Transition name="sign-player-sheet">
                    <div v-if="showsExplanationCard" class="fixed inset-0 z-[70] md:hidden" aria-live="polite">
                        <button
                            v-if="!showsWrongFeedback"
                            type="button"
                            class="absolute inset-0 bg-[#101828]/35"
                            aria-label="Zamknij wyjaśnienie"
                            @click="closeExplanation"
                        />
                        <div v-else class="absolute inset-0 bg-[#101828]/35" />

                        <section
                            role="dialog"
                            aria-modal="true"
                            :aria-label="explanationTitle"
                            class="absolute inset-x-0 bottom-0 max-h-[78svh] overflow-y-auto rounded-t-[14px] bg-white pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-18px_45px_rgba(16,24,40,0.22)]"
                        >
                            <div class="sticky top-0 z-10 relative flex h-14 items-center justify-center border-b border-[#e4e7ec] bg-white px-4">
                                <div class="h-1.5 w-10 rounded-full bg-[#d0d5dd]" aria-hidden="true" />
                                <button
                                    v-if="!showsWrongFeedback"
                                    type="button"
                                    class="absolute right-3 grid h-10 w-10 place-items-center rounded-full text-[#475467] transition hover:bg-[#f2f4f7] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce]"
                                    aria-label="Zamknij wyjaśnienie"
                                    @click="closeExplanation"
                                >
                                    <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-linecap="round" stroke-width="2" />
                                    </svg>
                                </button>
                            </div>

                            <div class="px-5 pb-3 pt-5">
                                <p class="text-[0.78rem] font-semibold" :class="showsWrongFeedback ? 'text-[#b42318]' : 'text-[#0b63ce]'">
                                    {{ explanationTitle }}
                                </p>
                                <h2 class="mt-2 text-[1.3rem] font-bold leading-7 text-[#17191d]">{{ explanationLabel }}</h2>
                                <p v-if="explanationText" class="mt-3 text-[0.94rem] leading-6 text-[#475467]">{{ explanationText }}</p>
                                <p v-else class="mt-3 text-[0.94rem] leading-6 text-[#475467]">Do tego znaku nie mamy jeszcze gotowego wyjaśnienia.</p>

                                <p v-if="isSyncing || pendingAnswerQueue.length > 0" class="mt-5 text-[0.82rem] font-semibold text-[#475467]">
                                    Zapisuję odpowiedź...
                                </p>
                                <button
                                    v-if="showsWrongFeedback"
                                    type="button"
                                    :disabled="isLoadingNext"
                                    class="mt-5 flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#0b63ce] px-4 text-[0.96rem] font-semibold text-white transition hover:bg-[#084fa8] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="goNext"
                                >
                                    {{ isLoadingNext ? 'Zapisywanie...' : question.has_more ? 'Następny znak' : 'Zobacz wynik' }}
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="mt-5 min-h-10 text-[0.9rem] font-semibold text-[#0b63ce]"
                                    @click="closeExplanation"
                                >
                                    {{ backToQuestionLabel }}
                                </button>
                            </div>
                        </section>
                    </div>
                </Transition>
            </Teleport>
        </section>

        <main class="hidden min-h-screen bg-[#f6f8fb] text-[#020309] md:block">
            <section class="border-b border-[#e5e7eb] bg-white">
                <div class="mx-auto flex w-full max-w-[76rem] flex-col gap-4 px-4 py-5 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between gap-3">
                        <Link
                            :href="route('session.traffic-signs')"
                            class="inline-flex h-10 items-center justify-center rounded-[0.65rem] border border-[#d1d5db] bg-white px-4 text-[0.9rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4]"
                        >
                            Znaki drogowe
                        </Link>
                        <p class="text-[0.9rem] font-bold text-[#023ea4]">
                            {{ question.position }} / {{ question.total }}
                        </p>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-[#eef2f7]">
                        <div
                            class="h-full rounded-full bg-[#023ea4]"
                            :style="{ width: `${Math.min((question.position / question.total) * 100, 100)}%` }"
                        />
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-[76rem] px-4 py-7 sm:px-6 lg:px-8">
                <div class="mb-6 max-w-[46rem]">
                    <p class="text-[0.76rem] font-semibold uppercase tracking-[0.16em] text-[#023ea4]">
                        {{ question.mode === 'similar_signs' ? 'Podobne znaki' : question.answer_mode === 'meaning_to_sign' ? 'Opis -> znak' : 'Co oznacza ten znak?' }}
                    </p>
                    <h1 class="mt-2 text-[1.9rem] font-bold leading-[2.15rem] tracking-normal text-[#020309]">
                        {{ question.answer_mode === 'meaning_to_sign' ? 'Wybierz znak pasujący do opisu' : 'Wybierz poprawną odpowiedź' }}
                    </h1>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,26rem)_1fr] lg:items-start">
                    <div
                        v-if="question.answer_mode === 'meaning_to_sign'"
                        class="traffic-sign-flip-card h-[27.75rem] lg:self-start"
                        :class="{ 'traffic-sign-flip-card--flipped': showsExplanationCard }"
                    >
                    <div class="traffic-sign-flip-card__inner">
                        <div
                            class="traffic-sign-flip-card__face flex cursor-pointer flex-col justify-start rounded-[0.9rem] border border-[#dbe4f0] bg-white p-7 shadow-[0_18px_42px_rgba(15,23,42,0.08)] transition hover:border-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 sm:p-8"
                            role="button"
                            tabindex="0"
                            :aria-label="explanationActionLabel"
                            :aria-pressed="isExplanationOpen ? 'true' : 'false'"
                            @click="toggleExplanation"
                            @keydown.enter.prevent="toggleExplanation"
                            @keydown.space.prevent="toggleExplanation"
                        >
                            <div class="min-h-0 overflow-y-auto pr-1">
                                <p class="text-[0.76rem] font-semibold uppercase tracking-[0.16em] text-[#023ea4]">
                                    Opis znaku
                                </p>
                                <p class="mt-4 text-[1.35rem] font-bold leading-[1.75rem] text-[#020309]">
                                    {{ question.prompt }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="traffic-sign-flip-card__face traffic-sign-flip-card__face--back flex flex-col justify-between gap-4 rounded-[0.9rem] border p-6 shadow-[0_18px_42px_rgba(15,23,42,0.08)]"
                            :class="showsWrongFeedback ? 'border-[#fecdd3] bg-[#fff7f8]' : 'border-[#dbe4f0] bg-white'"
                        >
                            <div class="min-h-0 overflow-y-auto pr-1">
                                <p
                                    class="text-[0.76rem] font-semibold uppercase tracking-[0.16em]"
                                    :class="explanationTitleClass"
                                >
                                    {{ explanationTitle }}
                                </p>
                                <p class="mt-4 text-[1.18rem] font-bold leading-[1.45rem] text-[#020309]">
                                    {{ explanationLabel }}
                                </p>
                                <p
                                    v-if="explanationText"
                                    class="mt-3 text-[0.98rem] font-semibold leading-[1.45rem] text-[#4b5563]"
                                >
                                    {{ explanationText }}
                                </p>
                                <p v-else class="mt-3 text-[0.98rem] font-semibold leading-[1.45rem] text-[#4b5563]">
                                    Do tego znaku nie mamy jeszcze gotowego wyjaśnienia.
                                </p>
                            </div>

                            <div>
                                <template v-if="showsWrongFeedback">
                                    <p v-if="isSyncing || pendingAnswerQueue.length > 0" class="mb-2 text-[0.82rem] font-semibold text-[#4b5563]">
                                        Zapisuję odpowiedź...
                                    </p>
                                    <button
                                        type="button"
                                        :disabled="isLoadingNext"
                                        class="inline-flex h-11 w-full items-center justify-center rounded-[0.72rem] bg-[#023ea4] px-5 text-[0.95rem] font-semibold text-white transition hover:bg-[#012b7a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        :class="{ 'cursor-wait opacity-75': isLoadingNext }"
                                        @click="goNext"
                                    >
                                        {{ isLoadingNext ? 'Zapisywanie...' : question.has_more ? 'Dalej' : 'Zobacz wynik' }}
                                    </button>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex h-11 w-full items-center justify-center rounded-[0.72rem] border border-[#d1d5db] bg-white px-5 text-[0.95rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                    @click="closeExplanation"
                                >
                                    {{ backToQuestionLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                    <div
                        v-else
                        class="traffic-sign-flip-card h-[26.25rem] lg:self-start"
                        :class="{ 'traffic-sign-flip-card--flipped': showsExplanationCard }"
                    >
                    <div class="traffic-sign-flip-card__inner">
                        <div
                            class="traffic-sign-flip-card__face flex cursor-pointer items-center justify-center rounded-[0.9rem] border border-[#dbe4f0] bg-white p-6 shadow-[0_18px_42px_rgba(15,23,42,0.08)] transition hover:border-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                            role="button"
                            tabindex="0"
                            :aria-label="explanationActionLabel"
                            :aria-pressed="isExplanationOpen ? 'true' : 'false'"
                            @click="toggleExplanation"
                            @keydown.enter.prevent="toggleExplanation"
                            @keydown.space.prevent="toggleExplanation"
                        >
                            <img
                                v-if="question.sign.image_url"
                                :src="question.sign.image_url"
                                alt="Znak drogowy do rozpoznania"
                                class="max-h-[21rem] max-w-full object-contain"
                            >
                            <div v-else class="text-center text-[2rem] font-bold text-[#023ea4]">
                                {{ question.sign.code }}
                            </div>
                        </div>

                        <div
                            class="traffic-sign-flip-card__face traffic-sign-flip-card__face--back flex flex-col justify-between gap-4 rounded-[0.9rem] border p-6 shadow-[0_18px_42px_rgba(15,23,42,0.08)]"
                            :class="showsWrongFeedback ? 'border-[#fecdd3] bg-[#fff7f8]' : 'border-[#dbe4f0] bg-white'"
                        >
                            <div class="min-h-0 overflow-y-auto pr-1">
                                <p
                                    class="text-[0.76rem] font-semibold uppercase tracking-[0.16em]"
                                    :class="explanationTitleClass"
                                >
                                    {{ explanationTitle }}
                                </p>
                                <p class="mt-4 text-[1.18rem] font-bold leading-[1.45rem] text-[#020309]">
                                    {{ explanationLabel }}
                                </p>
                                <p
                                    v-if="explanationText"
                                    class="mt-3 text-[0.98rem] font-semibold leading-[1.45rem] text-[#4b5563]"
                                >
                                    {{ explanationText }}
                                </p>
                                <p v-else class="mt-3 text-[0.98rem] font-semibold leading-[1.45rem] text-[#4b5563]">
                                    Do tego znaku nie mamy jeszcze gotowego wyjaśnienia.
                                </p>
                            </div>

                            <div>
                                <template v-if="showsWrongFeedback">
                                    <p v-if="isSyncing || pendingAnswerQueue.length > 0" class="mb-2 text-[0.82rem] font-semibold text-[#4b5563]">
                                        Zapisuję odpowiedź...
                                    </p>
                                    <button
                                        type="button"
                                        :disabled="isLoadingNext"
                                        class="inline-flex h-11 w-full items-center justify-center rounded-[0.72rem] bg-[#023ea4] px-5 text-[0.95rem] font-semibold text-white transition hover:bg-[#012b7a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        :class="{ 'cursor-wait opacity-75': isLoadingNext }"
                                        @click="goNext"
                                    >
                                        {{ isLoadingNext ? 'Zapisywanie...' : question.has_more ? 'Dalej' : 'Zobacz wynik' }}
                                    </button>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex h-11 w-full items-center justify-center rounded-[0.72rem] border border-[#d1d5db] bg-white px-5 text-[0.95rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                    @click="closeExplanation"
                                >
                                    {{ backToQuestionLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="self-start">
                    <div class="grid gap-3" :class="question.answer_mode === 'meaning_to_sign' ? 'sm:grid-cols-2' : ''">
                        <button
                            v-for="option in question.options"
                            :key="option.traffic_sign_id"
                            type="button"
                            class="rounded-[0.9rem] border text-[1rem] font-bold leading-[1.25rem] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-default"
                            :class="[
                                optionClass(option),
                                question.answer_mode === 'meaning_to_sign'
                                    ? 'min-h-[13.5rem] px-3 py-4 text-center'
                                    : 'min-h-[4.2rem] px-4 py-3 text-left',
                            ]"
                            :disabled="question.answered"
                            @click="chooseAnswer(option.traffic_sign_id)"
                        >
                            <template v-if="question.answer_mode === 'meaning_to_sign'">
                                <span class="sr-only">{{ option.label }}</span>
                                <span class="flex h-36 items-center justify-center rounded-[0.6rem] bg-white p-2 sm:h-40">
                                    <img
                                        v-if="option.image_url"
                                        :src="option.image_url"
                                        alt=""
                                        class="max-h-full max-w-full scale-[1.35] object-contain"
                                    >
                                    <span v-else class="text-[2rem] font-bold text-[#023ea4]">{{ option.code }}</span>
                                </span>
                            </template>
                            <template v-else>
                                {{ option.label }}
                            </template>
                        </button>
                    </div>

                    <p v-if="syncError" class="mt-3 text-[0.88rem] font-semibold text-[#b50d13]">
                        {{ syncError }}
                    </p>

                    </div>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.traffic-sign-flip-card {
    perspective: 1200px;
}

.traffic-sign-flip-card__inner {
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 320ms ease;
    width: 100%;
}

.traffic-sign-flip-card--flipped .traffic-sign-flip-card__inner {
    transform: rotateY(180deg);
}

.traffic-sign-flip-card__face {
    backface-visibility: hidden;
    inset: 0;
    position: absolute;
}

.traffic-sign-flip-card__face--back {
    transform: rotateY(180deg);
}

.sign-player-question-enter-active,
.sign-player-question-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.sign-player-question-enter-from,
.sign-player-question-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.sign-player-feedback-enter-active,
.sign-player-feedback-leave-active {
    transition: opacity 160ms ease, transform 160ms ease;
}

.sign-player-feedback-enter-from,
.sign-player-feedback-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

.sign-player-sheet-enter-active,
.sign-player-sheet-leave-active {
    transition: opacity 180ms ease;
}

.sign-player-sheet-enter-active section,
.sign-player-sheet-leave-active section {
    transition: transform 220ms ease;
}

.sign-player-sheet-enter-from,
.sign-player-sheet-leave-to {
    opacity: 0;
}

.sign-player-sheet-enter-from section,
.sign-player-sheet-leave-to section {
    transform: translateY(100%);
}

@media (prefers-reduced-motion: reduce) {
    .traffic-sign-flip-card__inner,
    .sign-player-question-enter-active,
    .sign-player-question-leave-active,
    .sign-player-feedback-enter-active,
    .sign-player-feedback-leave-active,
    .sign-player-sheet-enter-active,
    .sign-player-sheet-leave-active,
    .sign-player-sheet-enter-active section,
    .sign-player-sheet-leave-active section {
        transition: none;
    }
}
</style>
