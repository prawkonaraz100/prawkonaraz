<script setup lang="ts">
import SessionExpiredNotice from '@/Components/SessionExpiredNotice.vue';
import { useSessionExpiry } from '@/composables/useSessionExpiry';
import questionSourceEmblem from '../../../images/session/question-source-emblem-crop.png';
import SessionExamLayout from '@/Layouts/SessionExamLayout.vue';
import { apiClient } from '@/lib/apiClient';
import { renderInlineFormattedHtml } from '@/utils/explanationFormatting';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface SessionSummary {
    id: number;
    mode: string;
    status: string;
    license_category_id: number | null;
    license_category_code: string | null;
    license_category_name: string | null;
    started_at: string | null;
    completed_at: string | null;
    correct_answers_count: number;
    total_questions_count: number;
    score_percent: number | null;
}

interface ProgressSummary {
    answered: number;
    remaining: number;
    total: number;
}

interface QuestionMedia {
    kind: string;
    url: string | null;
    full_url?: string | null;
    thumb_url?: string | null;
    poster_url: string | null;
    mime_type: string | null;
    duration_seconds?: number | null;
}

interface QuestionOption {
    key: string;
    label: string;
    text: string;
}

interface QuestionTopic {
    id: number;
    key: string;
    name: string;
}

interface CurrentQuestion {
    id: number;
    external_id?: string | null;
    source?: string | null;
    prompt: string;
    explanation: string | null;
    correct_answer: string | null;
    question_type: string;
    structure_scope: string;
    difficulty: number;
    points: number;
    topic: QuestionTopic | null;
    options: QuestionOption[];
    media: QuestionMedia[];
}

interface ExamUiSummary {
    duration_seconds: number;
    remaining_seconds: number;
    started_at: string | null;
    deadline_at: string | null;
    pass_threshold: number;
    max_points: number;
    current_scope: string | null;
    current_points: number | null;
    basic: {
        answered: number;
        total: number;
    };
    specialist: {
        answered: number;
        total: number;
    };
    phase: 'preview' | 'media' | 'answer' | null;
    question_remaining_seconds: number;
    question_started_at: string | null;
    question_deadline_at: string | null;
    preview_duration_seconds: number;
    basic_answer_duration_seconds: number;
    specialist_answer_duration_seconds: number;
}

const props = defineProps<{
    session: SessionSummary;
    questionIds: number[];
    progress: ProgressSummary;
    examUi: ExamUiSummary;
    currentQuestionNumber: number | null;
    currentQuestion: CurrentQuestion | null;
}>();

interface ExamStateResponse {
    session: SessionSummary;
    progress: ProgressSummary;
    examUi: ExamUiSummary;
    currentQuestionNumber: number | null;
    currentQuestion: CurrentQuestion | null;
    completed: boolean;
    redirect: string;
}

const sessionState = ref<SessionSummary>(props.session);
const progressState = ref<ProgressSummary>(props.progress);
const examUiState = ref<ExamUiSummary>(props.examUi);
const currentQuestionState = ref<CurrentQuestion | null>(props.currentQuestion);
const currentQuestionNumberState = ref<number | null>(props.currentQuestionNumber);

const answerForm = useForm<{
    question_id: number;
    selected_answer: string;
    response_time_ms: number | null;
}>({
    question_id: currentQuestionState.value?.id ?? 0,
    selected_answer: '',
    response_time_ms: null,
});

const selectedAnswer = ref<string | null>(null);
const answerSubmitInFlight = ref(false);
const questionStage = ref<'preview' | 'media' | 'answer'>(
    examUiState.value.phase === 'preview'
        ? 'preview'
        : examUiState.value.phase === 'media'
            ? 'media'
            : 'answer',
);
const presentedAt = ref<number | null>(null);
const examRemainingSeconds = ref(Math.max(examUiState.value.remaining_seconds ?? 0, 0));
const questionRemainingSeconds = ref(Math.max(examUiState.value.question_remaining_seconds ?? 0, 0));
const examSyncInFlight = ref(false);
const finishExamInFlight = ref(false);
const examRequestError = ref<string | null>(null);
const viewportWidth = ref(0);
const mobileExamMenuOpen = ref(false);
const mobileAnswerDockRef = ref<HTMLElement | null>(null);
const mobileAnswerDockHeight = ref(0);
let countdownId: ReturnType<typeof setInterval> | null = null;
let mobileAnswerDockResizeObserver: ResizeObserver | null = null;
const {
    handleSessionExpiryError,
    sessionExpired,
} = useSessionExpiry(() => {
    stopCountdown();
    examSyncInFlight.value = false;
    answerSubmitInFlight.value = false;
    finishExamInFlight.value = false;
    mobileExamMenuOpen.value = false;
    examRequestError.value = 'Sesja wygasła. Zatrzymaliśmy egzamin i nie ponawiamy ostatniej operacji.';
});

const activeQuestion = computed(() => currentQuestionState.value);
const activeMedia = computed(() => activeQuestion.value?.media[0] ?? null);
const hasVideoMedia = computed(() => activeMedia.value?.kind === 'video');
const hasVideoMediaWithoutKnownDuration = computed(() =>
    hasVideoMedia.value && (activeMedia.value?.duration_seconds ?? null) === null,
);
const isCurrentQuestionBasic = computed(() =>
    activeQuestion.value?.structure_scope !== 'SPECJALISTYCZNY',
);
const questionNumber = computed(() =>
    currentQuestionNumberState.value ?? Math.min(progressState.value.answered + 1, progressState.value.total),
);
const questionProgressLabel = computed(() =>
    `${questionNumber.value ?? '-'} / ${progressState.value.total}`,
);
const isMobileExamLayout = computed(() =>
    viewportWidth.value > 0 && viewportWidth.value <= 1023,
);
const hasOfficialGovQuestionSource = computed(() =>
    Boolean(activeQuestion.value?.source?.startsWith('gov.pl')),
);
const isSupplementalPj360Question = computed(() =>
    Boolean(
        activeQuestion.value?.source === 'pj360'
        || activeQuestion.value?.external_id?.startsWith('pj360:'),
    ),
);
const supplementalQuestionReference = computed(() => {
    const externalId = activeQuestion.value?.external_id ?? '';
    const numericSuffix = externalId.match(/(\d+)(?!.*\d)/)?.[1];

    return numericSuffix ?? 'DOD';
});
const currentQuestionSourceBadgeLabel = computed(() => {
    if (hasOfficialGovQuestionSource.value) {
        return 'gov.pl';
    }

    if (isSupplementalPj360Question.value) {
        return 'Pytanie dodatkowe';
    }

    if (activeQuestion.value?.source) {
        return activeQuestion.value.source;
    }

    return 'Źródło pytania';
});
const currentQuestionSourceDescription = computed(() => {
    if (hasOfficialGovQuestionSource.value) {
        return 'Oficjalna baza pytań państwowych';
    }

    if (isSupplementalPj360Question.value) {
        return 'Pytanie uzupełniające do nauki.';
    }

    if (activeQuestion.value?.source) {
        return 'Źródło techniczne';
    }

    return 'Źródło pytania';
});
const currentQuestionSourceNumber = computed(() => {
    if (isSupplementalPj360Question.value) {
        return supplementalQuestionReference.value;
    }

    if (activeQuestion.value?.external_id) {
        return activeQuestion.value.external_id;
    }

    return hasOfficialGovQuestionSource.value
        ? 'Niedostępny'
        : activeQuestion.value?.id?.toString() ?? '-';
});
const currentScopeLabel = computed(() =>
    isCurrentQuestionBasic.value ? 'Pytanie podstawowe' : 'Pytanie specjalistyczne',
);
const isBasicPreviewStage = computed(() =>
    isCurrentQuestionBasic.value && questionStage.value === 'preview',
);
const questionValue = computed(() =>
    examUiState.value.current_points ?? activeQuestion.value?.points ?? null,
);
const examRemainingLabel = computed(() => formatClock(examRemainingSeconds.value));
const questionRemainingLabel = computed(() => formatClock(questionRemainingSeconds.value));
const stageTitle = computed(() =>
    questionStage.value === 'preview'
        ? 'Czas na zapoznanie się z pytaniem'
        : questionStage.value === 'media'
            ? 'Trwa odtwarzanie materiału'
        : 'Czas na odpowiedź',
);
const stageLead = computed(() =>
    questionStage.value === 'preview'
        ? 'Po upływie czasu lub po kliknięciu Start odsłonimy media i odpowiedzi.'
        : questionStage.value === 'media'
            ? 'Czas odpowiedzi ruszy po zakończeniu odtwarzania filmu.'
        : 'Wybierz jedną odpowiedź i przejdź do kolejnego pytania.',
);
const stageCounterLabel = computed(() =>
    questionStage.value === 'media'
        ? 'Film'
        : questionRemainingLabel.value,
);
const shouldPinMobileAnswerPanel = computed(() =>
    isMobileExamLayout.value && Boolean(activeQuestion.value),
);
const canSelectAnswer = computed(() =>
    Boolean(activeQuestion.value)
    && questionStage.value === 'answer'
    && !answerSubmitInFlight.value
    && !finishExamInFlight.value
    && !examSyncInFlight.value
    && !sessionExpired.value
);
const nextButtonDisabled = computed(() =>
    !activeQuestion.value
    || questionStage.value === 'preview'
    || questionStage.value === 'media'
    || selectedAnswer.value === null
    || answerSubmitInFlight.value
    || finishExamInFlight.value
    || examSyncInFlight.value
    || sessionExpired.value
);
const finishButtonDisabled = computed(() =>
    answerSubmitInFlight.value
    || finishExamInFlight.value
    || examSyncInFlight.value
    || sessionExpired.value,
);
const mobileHeaderStatClass =
    'min-w-0 border border-[#e5e9ee] bg-[#fbfcfd] px-2.5 py-1.5';
const mobileHeaderLabelClass =
    'text-[0.58rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]';
const mobileHeaderValueClass =
    'mt-1 text-[0.98rem] font-semibold leading-5 text-[#111827]';
const examTimerClass = computed(() => {
    if (examRemainingSeconds.value <= 120) {
        return 'border-[#dca6a6] bg-[#fff1f1] text-[#8f2f2f]';
    }

    return 'border-[#d7dce2] bg-[#f7f8fa] text-[#223547]';
});

const formatClock = (value: number | null) => {
    const safeValue = Math.max(Math.round(value ?? 0), 0);
    const minutes = Math.floor(safeValue / 60);
    const seconds = safeValue % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const mediaDisplayUrl = (media: QuestionMedia | null) =>
    media?.kind === 'video'
        ? media.poster_url ?? media.thumb_url ?? media.url ?? null
        : media?.full_url ?? media?.thumb_url ?? media?.url ?? null;

const mobileAnswerDockWrapperClass = computed(() =>
    shouldPinMobileAnswerPanel.value
        ? 'fixed inset-x-0 bottom-0 z-40 border-t border-[#dfe4ea] bg-white px-3 pt-3'
        : '',
);

const mobileAnswerDockWrapperStyle = computed<Record<string, string>>(() => {
    if (!shouldPinMobileAnswerPanel.value) {
        return {} as Record<string, string>;
    }

    return {
        paddingBottom: 'calc(env(safe-area-inset-bottom, 0px) + 0.75rem)',
    };
});

const mobileAnswerDockSpacerStyle = computed<Record<string, string>>(() => {
    if (!shouldPinMobileAnswerPanel.value || mobileAnswerDockHeight.value <= 0) {
        return {} as Record<string, string>;
    }

    return {
        height: `${mobileAnswerDockHeight.value + 12}px`,
    };
});

const mobileResponsiveFrameStyle = computed<Record<string, string>>(() => {
    if (!isMobileExamLayout.value) {
        return {} as Record<string, string>;
    }

    if (viewportWidth.value <= 434) {
        return {
            width: '100%',
            maxWidth: '23rem',
        };
    }

    return {
        width: 'min(760px, 100%)',
        maxWidth: '100%',
    };
});

const mobileAnswerDockInnerStyle = computed<Record<string, string>>(() => {
    if (!shouldPinMobileAnswerPanel.value) {
        return {} as Record<string, string>;
    }

    return mobileResponsiveFrameStyle.value;
});

const mobileAnswerOptionsClass = computed(() =>
    activeQuestion.value?.question_type === 'boolean'
        ? 'grid grid-cols-2 gap-2'
        : 'space-y-2',
);
const examSessionShellClass = computed(() =>
    isMobileExamLayout.value
        ? 'flex h-full min-h-screen flex-col gap-0 max-lg:-mx-3 max-lg:-my-3'
        : 'flex h-full min-h-[calc(100vh-5rem)] flex-col gap-2.5 xl:gap-2',
);
const examMobileHeaderClass = computed(() =>
    'border-b border-[#e5e7eb] bg-white lg:hidden',
);
const examMobileHeaderGridClass = computed(() =>
    'grid grid-cols-[minmax(3.75rem,0.78fr)_minmax(4.25rem,0.78fr)_auto_auto] items-center gap-2 px-4 py-3',
);
const examMobileOptionsButtonClass = computed(() =>
    'inline-flex min-h-[2.45rem] items-center justify-center border border-[#e5e9ee] bg-white px-2.5 py-1.5 text-sm font-medium text-[#5f6f82] transition hover:border-[#cfd7df] hover:bg-[#f8fafc] hover:text-[#111827] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0057a3]',
);
const examMobileFinishButtonClass = computed(() =>
    'inline-flex min-h-[2.45rem] items-center justify-center border border-[#d2b35b] bg-[#efc54f] px-3 py-2 text-sm font-semibold leading-tight text-[#463309] transition hover:bg-[#e8bd4c] disabled:cursor-not-allowed disabled:opacity-60',
);
const examContentGridClass = computed(() =>
    isMobileExamLayout.value
        ? 'grid flex-1 gap-0'
        : 'grid flex-1 gap-3 xl:gap-2.5 lg:grid-cols-[minmax(0,1fr)_11.5rem] xl:grid-cols-[minmax(0,1fr)_12rem] 2xl:grid-cols-[minmax(0,1fr)_13.25rem]',
);
const examQuestionPaneClass = computed(() =>
    isMobileExamLayout.value
        ? 'bg-white'
        : 'border border-[#d1d8df] bg-white',
);
const examScopeHeaderClass = computed(() =>
    isMobileExamLayout.value
        ? 'border-b border-[#e5e7eb] px-4 py-3'
        : 'border-b border-[#d1d8df] px-3 py-2.5 xl:px-3.5 xl:py-2.5',
);
const examMediaOuterClass = computed(() =>
    isMobileExamLayout.value
        ? 'py-0'
        : 'px-3 py-3 xl:px-3.5 xl:py-3',
);
const examMediaShellClass = computed(() =>
    isMobileExamLayout.value
        ? 'bg-white'
        : 'border border-[#d1d8df] bg-white',
);
const examPreviewPanelClass = computed(() =>
    isMobileExamLayout.value
        ? 'flex min-h-[8.75rem] items-center justify-center bg-[#f7f8fa] px-5 py-6 text-center'
        : 'flex items-center justify-center bg-[#f7f8fa] px-5 py-6 text-center min-h-[9.5rem] sm:min-h-[14rem] xl:min-h-[18rem] 2xl:min-h-[24rem]',
);
const examMediaPanelClass = computed(() =>
    isMobileExamLayout.value
        ? 'flex min-h-[12rem] items-center justify-center bg-white'
        : 'flex items-center justify-center bg-white px-3 py-3 min-h-[11rem] sm:min-h-[14rem] xl:min-h-[18rem] 2xl:min-h-[24rem]',
);
const examMediaAssetClass = computed(() =>
    isMobileExamLayout.value
        ? 'max-h-[15rem] w-full object-contain'
        : 'max-h-[13rem] w-full object-contain sm:max-h-[17rem] lg:max-h-[20rem] xl:max-h-[18rem] 2xl:max-h-[28rem]',
);
const examPromptBlockClass = computed(() =>
    isMobileExamLayout.value
        ? 'border-t border-[#e5e7eb] px-4 py-4'
        : 'border-t border-[#d1d8df] px-3 py-3 xl:px-3.5 xl:py-3',
);
const examPromptTextClass = computed(() =>
    isMobileExamLayout.value
        ? 'mt-2.5 text-[1.05rem] leading-7 text-[#111827] [&_strong]:font-semibold [&_strong]:text-inherit'
        : 'mt-2.5 text-[1rem] leading-6 text-[#111827] xl:text-[0.95rem] [&_strong]:font-semibold [&_strong]:text-inherit',
);

const syncMobileAnswerDockHeight = () => {
    if (!shouldPinMobileAnswerPanel.value) {
        mobileAnswerDockHeight.value = 0;
        return;
    }

    mobileAnswerDockHeight.value = mobileAnswerDockRef.value?.offsetHeight ?? 0;
};

const reconnectMobileAnswerDockObserver = () => {
    mobileAnswerDockResizeObserver?.disconnect();
    mobileAnswerDockResizeObserver = null;

    if (
        typeof window === 'undefined'
        || typeof ResizeObserver === 'undefined'
        || !mobileAnswerDockRef.value
        || !shouldPinMobileAnswerPanel.value
    ) {
        return;
    }

    mobileAnswerDockResizeObserver = new ResizeObserver(() => {
        syncMobileAnswerDockHeight();
    });
    mobileAnswerDockResizeObserver.observe(mobileAnswerDockRef.value);
};

const handleViewportResize = () => {
    if (typeof window === 'undefined') {
        return;
    }

    viewportWidth.value = window.innerWidth;
};

const applyExamState = (payload: ExamStateResponse) => {
    sessionState.value = payload.session;
    progressState.value = payload.progress;
    examUiState.value = payload.examUi;
    currentQuestionNumberState.value = payload.currentQuestionNumber;
    currentQuestionState.value = payload.currentQuestion;
};

const resetQuestionStage = (question: CurrentQuestion | null) => {
    selectedAnswer.value = null;
    answerForm.question_id = question?.id ?? 0;
    answerForm.selected_answer = '';
    answerForm.response_time_ms = null;

    if (!question) {
        questionStage.value = 'answer';
        presentedAt.value = null;
        questionRemainingSeconds.value = 0;
        return;
    }

    questionStage.value = examUiState.value.phase === 'preview'
        ? 'preview'
        : examUiState.value.phase === 'media'
            ? 'media'
            : 'answer';
    questionRemainingSeconds.value = Math.max(examUiState.value.question_remaining_seconds ?? 0, 0);
    presentedAt.value = questionStage.value === 'answer' ? Date.now() : null;
};

const syncExamState = async (action: 'sync' | 'start-answer' = 'sync') => {
    if (examSyncInFlight.value || sessionExpired.value) {
        return;
    }

    examSyncInFlight.value = true;
    examRequestError.value = null;

    try {
        const payload = await apiClient.post<ExamStateResponse>(
            route('study-sessions.current.exam.state'),
            action === 'start-answer'
                ? { action: 'start-answer' }
                : {},
        );

        if (payload.completed || payload.session?.status === 'completed') {
            if (typeof window !== 'undefined') {
                window.location.assign(payload.redirect ?? route('study-sessions.current'));
            }

            return;
        }

        applyExamState(payload);
    } catch (error) {
        if (!handleSessionExpiryError(error)) {
            examRequestError.value = 'Nie udało się zsynchronizować stanu egzaminu.';
        }
    } finally {
        examSyncInFlight.value = false;
    }
};

const syncIfTimerExpired = () => {
    if (
        examSyncInFlight.value
        || answerSubmitInFlight.value
        || finishExamInFlight.value
        || sessionExpired.value
        || !activeQuestion.value
    ) {
        return;
    }

    if (examRemainingSeconds.value < 1 || questionRemainingSeconds.value < 1) {
        if (questionStage.value === 'media' && hasVideoMediaWithoutKnownDuration.value && examRemainingSeconds.value > 0) {
            return;
        }

        void syncExamState('sync');
    }
};

const startCountdown = () => {
    stopCountdown();

    if (sessionExpired.value || typeof window === 'undefined') {
        return;
    }

    countdownId = window.setInterval(() => {
        if (examRemainingSeconds.value > 0) {
            examRemainingSeconds.value -= 1;

            if (examRemainingSeconds.value === 0) {
                void syncExamState('sync');
                return;
            }
        }

        if (questionRemainingSeconds.value > 0) {
            if (questionStage.value === 'media' && hasVideoMediaWithoutKnownDuration.value) {
                return;
            }

            questionRemainingSeconds.value -= 1;

            if (questionRemainingSeconds.value === 0) {
                void syncExamState('sync');
            }
        }
    }, 1000);
};

const stopCountdown = () => {
    if (countdownId === null || typeof window === 'undefined') {
        return;
    }

    window.clearInterval(countdownId);
    countdownId = null;
};

const selectAnswer = (answerKey: string) => {
    if (!canSelectAnswer.value || sessionExpired.value) {
        return;
    }

    selectedAnswer.value = answerKey;
};

const submitSelectedAnswer = async () => {
    if (
        sessionExpired.value
        || !activeQuestion.value
        || selectedAnswer.value === null
    ) {
        return;
    }

    if (answerSubmitInFlight.value) {
        return;
    }

    answerForm.question_id = activeQuestion.value.id;
    answerForm.selected_answer = selectedAnswer.value;
    answerForm.response_time_ms = presentedAt.value
        ? Math.max(Date.now() - presentedAt.value, 0)
        : null;

    answerSubmitInFlight.value = true;
    examRequestError.value = null;

    try {
        const payload = await apiClient.post<{
            completed: boolean;
            session?: {
                status?: string | null;
            } | null;
        }>(
            route('study-sessions.current.answers.store'),
            {
                question_id: answerForm.question_id,
                selected_answer: answerForm.selected_answer,
                response_time_ms: answerForm.response_time_ms,
            },
        );

        if (payload?.completed || payload?.session?.status === 'completed') {
            if (typeof window !== 'undefined') {
                window.location.assign(route('study-sessions.show', sessionState.value.id));
            }

            return;
        }

        await syncExamState('sync');
    } catch (error) {
        if (!handleSessionExpiryError(error)) {
            examRequestError.value = 'Nie udało się zapisać odpowiedzi. Spróbuj ponownie.';
        }
    } finally {
        answerSubmitInFlight.value = false;
    }
};

const finishExam = async () => {
    if (finishExamInFlight.value || sessionExpired.value) {
        return;
    }

    finishExamInFlight.value = true;
    examRequestError.value = null;

    try {
        const payload = await apiClient.post<{
            redirect?: string | null;
        }>(route('study-sessions.current.complete'), {});

        if (typeof window !== 'undefined') {
            window.location.assign(payload?.redirect ?? route('study-sessions.show', sessionState.value.id));
        }
    } catch (error) {
        if (!handleSessionExpiryError(error)) {
            examRequestError.value = 'Nie udało się zakończyć egzaminu. Spróbuj ponownie.';
        }
    } finally {
        finishExamInFlight.value = false;
    }
};

const handleMediaEnded = () => {
    if (questionStage.value !== 'media' || !hasVideoMedia.value) {
        return;
    }

    void syncExamState('start-answer');
};

const answerOptionClass = (optionKey: string) =>
    selectedAnswer.value === optionKey
        ? 'border-[#556476] bg-[#f4f6f8] text-[#16202b]'
        : 'border-[#d6dce2] bg-white text-[#111827] hover:border-[#b7c2cf]';

watch(
    () => props.session,
    (value) => {
        sessionState.value = value;
    },
    { deep: true },
);

watch(
    () => props.progress,
    (value) => {
        progressState.value = value;
    },
    { deep: true },
);

watch(
    () => props.currentQuestionNumber,
    (value) => {
        currentQuestionNumberState.value = value;
    },
);

watch(
    () => props.currentQuestion,
    (value) => {
        currentQuestionState.value = value;
    },
    { deep: true },
);

watch(
    () => props.examUi,
    (value) => {
        examUiState.value = value;
    },
    { deep: true },
);

watch(
    () => currentQuestionState.value?.id,
    () => {
        resetQuestionStage(currentQuestionState.value);
    },
    { immediate: true },
);

watch(
    () => examUiState.value.remaining_seconds,
    (value) => {
        examRemainingSeconds.value = Math.max(value ?? 0, 0);
        syncIfTimerExpired();
    },
    { immediate: true },
);

watch(
    () => examUiState.value.question_remaining_seconds,
    (value) => {
        questionRemainingSeconds.value = Math.max(value ?? 0, 0);
        syncIfTimerExpired();
    },
    { immediate: true },
);

watch(
    examRemainingSeconds,
    () => {
        syncIfTimerExpired();
    },
);

watch(
    questionRemainingSeconds,
    () => {
        syncIfTimerExpired();
    },
);

watch(
    () => examUiState.value.phase,
    (value) => {
        questionStage.value = value === 'preview'
            ? 'preview'
            : value === 'media'
                ? 'media'
                : 'answer';
        presentedAt.value = questionStage.value === 'answer' ? Date.now() : null;
    },
    { immediate: true },
);

watch(
    isMobileExamLayout,
    async (isMobile) => {
        if (!isMobile) {
            mobileExamMenuOpen.value = false;
        }

        await nextTick();
        reconnectMobileAnswerDockObserver();
        syncMobileAnswerDockHeight();
    },
    { immediate: true },
);

watch(
    [
        questionStage,
        selectedAnswer,
        () => activeQuestion.value?.id,
        () => activeQuestion.value?.question_type,
        nextButtonDisabled,
    ],
    async () => {
        await nextTick();
        reconnectMobileAnswerDockObserver();
        syncMobileAnswerDockHeight();
    },
);

onMounted(() => {
    handleViewportResize();
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleViewportResize);
    }

    syncIfTimerExpired();
    startCountdown();
});

onBeforeUnmount(() => {
    stopCountdown();
    mobileAnswerDockResizeObserver?.disconnect();
    mobileAnswerDockResizeObserver = null;

    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleViewportResize);
    }
});
</script>

<template>
    <Head title="Egzamin teoretyczny" />

    <SessionExpiredNotice v-if="sessionExpired" />

    <SessionExamLayout :lock-viewport="Boolean(activeQuestion)">
        <section :class="examSessionShellClass">
            <p
                v-if="examRequestError && !sessionExpired"
                class="mx-auto mb-3 w-full max-w-4xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
                role="alert"
            >
                {{ examRequestError }}
            </p>

            <header v-if="isMobileExamLayout" :class="examMobileHeaderClass">
                <div :class="examMobileHeaderGridClass">
                    <div :class="mobileHeaderStatClass">
                        <p :class="mobileHeaderLabelClass">Czas</p>
                        <p :class="mobileHeaderValueClass" class="tabular-nums">{{ examRemainingLabel }}</p>
                    </div>
                    <div :class="mobileHeaderStatClass">
                        <p :class="mobileHeaderLabelClass">Pyt</p>
                        <p :class="mobileHeaderValueClass">{{ questionProgressLabel }}</p>
                    </div>
                    <button
                        type="button"
                        :class="examMobileOptionsButtonClass"
                        @click="mobileExamMenuOpen = true"
                    >
                        Opcje
                    </button>
                    <button
                        type="button"
                        :class="examMobileFinishButtonClass"
                        :disabled="finishButtonDisabled"
                        @click="finishExam"
                    >
                        {{ finishExamInFlight ? 'Kończę...' : 'Zakończ' }}
                    </button>
                </div>
            </header>

            <header class="hidden border border-[#d1d8df] bg-white lg:block">
                <div class="flex flex-col gap-2.5 px-3 py-2.5 xl:gap-2 xl:px-2.5 xl:py-2 lg:flex-row lg:items-start lg:justify-between">
                    <div class="grid gap-2 sm:grid-cols-[11rem_12rem_12rem]">
                        <div class="border border-[#d1d8df] bg-white px-3 py-2 xl:px-2.5 xl:py-1.5">
                            <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                Wartość punktowa
                            </p>
                            <p class="mt-1 inline-flex min-w-[2rem] items-center justify-center border border-[#d7dce2] bg-[#f7f8fa] px-2 py-0.5 text-base font-semibold text-[#223547]">
                                {{ questionValue ?? '-' }}
                            </p>
                        </div>

                        <div class="border border-[#d1d8df] bg-white px-3 py-2 xl:px-2.5 xl:py-1.5">
                            <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                Aktualna kategoria
                            </p>
                            <p class="mt-1 inline-flex min-w-[2rem] items-center justify-center border border-[#d7dce2] bg-[#f7f8fa] px-2 py-0.5 text-base font-semibold text-[#223547]">
                                {{ sessionState.license_category_code ? sessionState.license_category_code : '-' }}
                            </p>
                        </div>

                        <div class="border px-3 py-2 xl:px-2.5 xl:py-1.5" :class="examTimerClass">
                            <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em]">
                                Czas do końca egzaminu
                            </p>
                            <p class="mt-1 inline-flex min-w-[3.5rem] items-center justify-center border border-current bg-white px-2 py-0.5 text-base font-semibold tabular-nums">
                                {{ examRemainingLabel }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <div class="border border-[#d1d8df] bg-white px-3 py-2 text-sm font-medium text-[#4b5563] xl:px-2.5 xl:py-1.5">
                            Pytanie {{ questionProgressLabel }}
                        </div>
                        <button
                            type="button"
                            class="border border-[#d2b35b] bg-[#efc54f] px-5 py-2 text-sm font-semibold text-[#463309] transition hover:bg-[#e8bd4c] disabled:cursor-not-allowed disabled:opacity-60 xl:px-4 xl:py-1.5"
                            :disabled="finishButtonDisabled"
                            @click="finishExam"
                        >
                            {{ finishExamInFlight ? 'Kończę egzamin...' : 'Zakończ egzamin' }}
                        </button>
                    </div>
                </div>
            </header>

            <div :class="examContentGridClass">
                <section :class="examQuestionPaneClass">
                    <div :class="examScopeHeaderClass">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                    {{ currentScopeLabel }}
                                </p>
                                <p class="mt-1 text-sm font-medium text-[#374151]">
                                    {{ activeQuestion?.topic?.name ?? 'Zakres egzaminacyjny' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div :class="examMediaOuterClass">
                        <div :class="examMediaShellClass">
                            <div
                                v-if="isBasicPreviewStage"
                                :class="examPreviewPanelClass"
                            >
                                <div class="max-w-md space-y-3">
                                    <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                        Materiał pytania jest chwilowo zasłonięty
                                    </p>
                                    <p class="text-sm leading-6 text-[#4b5563]">
                                        Zapoznaj się z treścią pytania. Media odsłonimy po zakończeniu odliczania lub po kliknięciu Start.
                                    </p>
                                </div>
                            </div>

                            <template v-else>
                                <div
                                    v-if="activeMedia && mediaDisplayUrl(activeMedia)"
                                    :class="examMediaPanelClass"
                                >
                                    <img
                                        v-if="activeMedia.kind !== 'video'"
                                        :src="mediaDisplayUrl(activeMedia) ?? undefined"
                                        alt=""
                                        :class="examMediaAssetClass"
                                    />

                                    <video
                                        v-else
                                        autoplay
                                        muted
                                        preload="metadata"
                                        playsinline
                                        :poster="activeMedia.poster_url ?? undefined"
                                        disablepictureinpicture
                                        controlslist="nodownload noplaybackrate noremoteplayback nofullscreen"
                                        :class="examMediaAssetClass"
                                        @ended="handleMediaEnded"
                                    >
                                        <source :src="activeMedia.url ?? undefined" :type="activeMedia.mime_type ?? undefined" />
                                    </video>
                                </div>

                                <div
                                    v-else
                                    class="flex items-center justify-center px-5 py-7 text-center text-sm text-[#6b7280] min-h-[11rem] sm:min-h-[14rem] xl:min-h-[18rem] 2xl:min-h-[24rem]"
                                >
                                    Brak materialu graficznego dla tego pytania.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div :class="examPromptBlockClass">
                        <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Treść pytania
                        </p>
                        <p
                            :class="examPromptTextClass"
                            v-html="renderInlineFormattedHtml(activeQuestion?.prompt ?? 'Trwa przygotowanie pytania.')"
                        />
                    </div>

                    <div v-if="!isMobileExamLayout" class="border-t border-[#d1d8df] px-4 py-4 xl:px-3.5 xl:py-3">
                        <div
                            v-if="isBasicPreviewStage && activeQuestion?.question_type === 'boolean'"
                            class="grid gap-2 sm:grid-cols-2"
                        >
                            <button
                                v-for="option in activeQuestion?.options ?? []"
                                :key="option.key"
                                type="button"
                                disabled
                                class="flex w-full cursor-not-allowed items-start gap-0 border border-[#d6dce2] bg-[#f7f8fa] text-left text-sm text-[#7b8794]"
                            >
                                <span class="inline-flex min-w-[2.5rem] items-center justify-center self-stretch border-r border-[#d1d8df] bg-[#f0f1f3] px-2 py-2.5 text-xs font-semibold text-[#7b8794]">
                                    {{ option.label }}
                                </span>
                                <span class="px-3 py-2.5 leading-5">
                                    {{ option.text.toUpperCase() }}
                                </span>
                            </button>
                        </div>

                        <div v-else-if="isBasicPreviewStage" class="border border-dashed border-[#cbd5df] bg-[#f7f8fa] px-4 py-5 text-sm text-[#5f6f82]">
                            Odpowiedzi zostaną odsłonięte po zakończeniu etapu zapoznania się z pytaniem.
                        </div>

                        <div
                            v-else
                            :class="activeQuestion?.question_type === 'boolean' ? 'grid gap-2 sm:grid-cols-2' : 'space-y-2'"
                        >
                            <button
                                v-for="option in activeQuestion?.options ?? []"
                                :key="option.key"
                                type="button"
                                class="flex w-full items-start gap-0 border text-left text-sm transition"
                                :class="answerOptionClass(option.key)"
                                :disabled="!canSelectAnswer"
                                @click="selectAnswer(option.key)"
                            >
                                <span class="inline-flex min-w-[2.5rem] items-center justify-center self-stretch border-r border-[#d1d8df] bg-[#334155] px-2 py-2.5 text-xs font-semibold text-white">
                                    {{ option.label }}
                                </span>
                                <span class="px-3 py-2.5 leading-5">
                                    {{
                                        activeQuestion?.question_type === 'boolean'
                                            ? option.text.toUpperCase()
                                            : option.text
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </section>

                <aside class="hidden space-y-3 lg:block">
                    <div class="border border-[#d1d8df] bg-white px-3 py-3 xl:px-2.5 xl:py-2.5">
                        <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Bieżące pytanie
                        </p>
                        <div class="mt-3 space-y-3 text-sm text-[#111827]">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[#6b7280]">Nr w sesji</span>
                                <span class="inline-flex min-w-[4.4rem] items-center justify-center border border-[#d7dce2] bg-[#f7f8fa] px-2 py-0.5 font-semibold text-[#223547]">
                                    {{ questionProgressLabel }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[#6b7280]">Źródło</span>
                                <div class="min-w-0 max-w-[9rem]">
                                    <span class="inline-flex max-w-full items-center gap-1.5 border border-[#d7dce2] bg-[#f7f8fa] px-2 py-1 text-[0.72rem] font-semibold text-[#223547]">
                                        <img
                                            :src="questionSourceEmblem"
                                            alt=""
                                            class="h-4 w-4 shrink-0 object-contain"
                                            aria-hidden="true"
                                        />
                                        <span class="truncate">{{ currentQuestionSourceBadgeLabel }}</span>
                                        <span class="text-[#98a2ad]">-</span>
                                        <span class="shrink-0 text-[#111827]">{{ currentQuestionSourceNumber }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-[#d1d8df] bg-white px-3 py-3 xl:px-2.5 xl:py-2.5">
                        <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Pytania podstawowe
                        </p>
                        <p class="mt-2 inline-flex min-w-[3.8rem] items-center justify-center border border-[#d7dce2] bg-[#f7f8fa] px-2 py-0.5 text-sm font-semibold text-[#223547]">
                            {{ examUiState.basic.answered }} / {{ examUiState.basic.total }}
                        </p>
                    </div>

                    <div class="border border-[#d1d8df] bg-white px-3 py-3 xl:px-2.5 xl:py-2.5">
                        <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Pytania specjalistyczne
                        </p>
                        <p class="mt-2 inline-flex min-w-[3.8rem] items-center justify-center border border-[#d7dce2] bg-[#f7f8fa] px-2 py-0.5 text-sm font-semibold text-[#223547]">
                            {{ examUiState.specialist.answered }} / {{ examUiState.specialist.total }}
                        </p>
                    </div>

                    <div class="border border-[#d1d8df] bg-white px-3 py-3 xl:px-2.5 xl:py-2.5">
                        <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            {{ stageTitle }}
                        </p>

                        <div v-if="questionStage === 'preview'" class="mt-3 grid grid-cols-[minmax(0,1fr)_4rem] gap-2">
                            <button
                                type="button"
                                class="border border-[#334155] bg-[#334155] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#273445] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="examSyncInFlight"
                                @click="() => void syncExamState('start-answer')"
                            >
                                {{ examSyncInFlight ? 'Ładowanie...' : 'Start' }}
                            </button>

                            <div class="border border-[#d2b35b] bg-[#efc54f] px-2 py-2 text-center text-sm font-semibold tabular-nums text-[#463309]">
                                {{ questionRemainingLabel }}
                            </div>
                        </div>

                        <div v-else class="mt-3 border border-[#d2b35b] bg-[#efc54f] px-3 py-2 text-center text-lg font-semibold tabular-nums text-[#463309]">
                            {{ stageCounterLabel }}
                        </div>

                        <p class="mt-3 text-xs leading-5 text-[#6b7280]">
                            {{ stageLead }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="w-full border border-[#d2b35b] bg-[#efc54f] px-4 py-3 text-sm font-semibold text-[#463309] transition hover:bg-[#e8bd4c] disabled:cursor-not-allowed disabled:border-[#d1d5db] disabled:bg-[#f3f4f6] disabled:text-[#9ca3af] xl:px-3 xl:py-2.5"
                        :disabled="nextButtonDisabled"
                        @click="submitSelectedAnswer"
                    >
                        {{ answerSubmitInFlight ? 'Zapisywanie...' : 'Następne pytanie' }}
                    </button>

                    <div class="border border-[#d1d8df] bg-white px-3 py-3 text-xs leading-5 text-[#6b7280] xl:px-2.5 xl:py-2.5">
                        Odpowiedź zostanie zapisana po kliknięciu przycisku przejścia do kolejnego pytania.
                    </div>

                    <div class="border border-[#d1d8df] bg-white px-3 py-3 xl:px-2.5 xl:py-2.5">
                        <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Wynik bieżący
                        </p>
                        <p class="mt-2 text-sm font-semibold text-[#111827]">
                            {{ sessionState.correct_answers_count }} poprawnych odpowiedzi
                        </p>
                        <p class="mt-2 text-xs leading-5 text-[#6b7280]">
                            Do zaliczenia potrzeba {{ examUiState.pass_threshold }} z {{ examUiState.max_points }} punktow.
                        </p>
                    </div>
                </aside>
            </div>

            <div v-if="isMobileExamLayout" class="lg:hidden" :style="mobileAnswerDockSpacerStyle" />

            <div
                v-if="isMobileExamLayout"
                ref="mobileAnswerDockRef"
                :class="mobileAnswerDockWrapperClass"
                :style="mobileAnswerDockWrapperStyle"
            >
                <div class="mx-auto space-y-3" :style="mobileAnswerDockInnerStyle">
                    <template v-if="questionStage === 'answer'">
                        <div>
                            <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Odpowiedź
                            </p>
                        </div>

                        <div :class="mobileAnswerOptionsClass">
                            <button
                                v-for="option in activeQuestion?.options ?? []"
                                :key="option.key"
                                type="button"
                                class="border text-sm transition"
                                :class="[
                                    answerOptionClass(option.key),
                                    activeQuestion?.question_type === 'boolean'
                                        ? 'min-h-[3.75rem] px-4 py-2 text-center text-base font-semibold'
                                        : 'w-full px-4 py-3 text-left leading-5',
                                ]"
                                :disabled="!canSelectAnswer"
                                @click="selectAnswer(option.key)"
                            >
                                <template v-if="activeQuestion?.question_type === 'boolean'">
                                    {{ option.text.toUpperCase() }}
                                </template>
                                <template v-else>
                                    <span class="flex items-start gap-3">
                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center border border-[#d1d8df] bg-[#334155] text-xs font-semibold text-white">
                                            {{ option.label }}
                                        </span>
                                        <span>{{ option.text }}</span>
                                    </span>
                                </template>
                            </button>
                        </div>

                        <button
                            type="button"
                            class="w-full border border-[#d2b35b] bg-[#efc54f] px-4 py-3 text-sm font-semibold text-[#463309] transition hover:bg-[#e8bd4c] disabled:cursor-not-allowed disabled:border-[#d1d5db] disabled:bg-[#f3f4f6] disabled:text-[#9ca3af]"
                            :disabled="nextButtonDisabled"
                            @click="submitSelectedAnswer"
                        >
                            {{ answerSubmitInFlight ? 'Zapisywanie...' : 'Następne pytanie' }}
                        </button>
                    </template>

                    <template v-else-if="questionStage === 'preview'">
                        <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Etap pytania
                        </p>
                        <div class="grid grid-cols-[minmax(0,1fr)_5rem] gap-2">
                            <button
                                type="button"
                                class="border border-[#334155] bg-[#334155] px-3 py-3 text-sm font-semibold text-white transition hover:bg-[#273445] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="examSyncInFlight"
                                @click="() => void syncExamState('start-answer')"
                            >
                                {{ examSyncInFlight ? 'Ładowanie...' : 'Start' }}
                            </button>
                            <div class="border border-[#d2b35b] bg-[#efc54f] px-2 py-3 text-center text-sm font-semibold tabular-nums text-[#463309]">
                                {{ questionRemainingLabel }}
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                            Etap pytania
                        </p>
                        <div class="border border-[#d2b35b] bg-[#efc54f] px-3 py-3 text-center text-base font-semibold tabular-nums text-[#463309]">
                            {{ stageCounterLabel }}
                        </div>
                    </template>
                </div>
            </div>

            <div
                v-if="mobileExamMenuOpen && isMobileExamLayout"
                class="fixed inset-0 z-50 flex items-end bg-[#111827]/45 lg:hidden"
                @click.self="mobileExamMenuOpen = false"
            >
                <div class="w-full rounded-t-[1.5rem] border border-[#d9dfe6] bg-white px-4 pt-4 pb-5 shadow-[0_-18px_40px_rgba(15,23,42,0.14)]">
                    <div class="mx-auto mb-4 h-1.5 w-14 rounded-full bg-[#d6dce2]" />

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                Opcje egzaminu
                            </p>
                            <p class="mt-1 text-sm text-[#4b5563]">
                                Szczegoly pytania i postep egzaminu.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="border border-[#d9dfe6] bg-white px-3 py-2 text-sm font-medium text-[#223547]"
                            @click="mobileExamMenuOpen = false"
                        >
                            Zamknij
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                    Kategoria
                                </p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">
                                    {{ sessionState.license_category_code ? sessionState.license_category_code : '-' }}
                                </p>
                            </div>
                            <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                    Punkty
                                </p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">
                                    {{ questionValue ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                            <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                Bieżące pytanie
                            </p>
                            <div class="mt-3 space-y-3 text-sm text-[#111827]">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Nr w sesji</span>
                                    <span class="inline-flex min-w-[4.4rem] items-center justify-center border border-[#d7dce2] bg-white px-2 py-0.5 font-semibold text-[#223547]">
                                        {{ questionProgressLabel }}
                                    </span>
                                </div>
                                <div class="rounded-sm border border-[#d7dce2] bg-white px-3 py-2.5">
                                    <div class="flex items-start gap-2.5">
                                        <img
                                            :src="questionSourceEmblem"
                                            alt=""
                                            class="mt-0.5 h-5 w-5 shrink-0 object-contain"
                                            aria-hidden="true"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                                Źródło
                                            </p>
                                            <p class="mt-1 text-sm font-semibold text-[#223547]">
                                                {{ currentQuestionSourceBadgeLabel }}
                                            </p>
                                            <p class="mt-1 text-sm text-[#4b5563]">
                                                {{ currentQuestionSourceDescription }}
                                            </p>
                                            <p class="mt-1 whitespace-nowrap text-[0.92rem] font-semibold tracking-[0.02em] text-[#111827]">
                                                {{ currentQuestionSourceNumber }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                    Podstawowe
                                </p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">
                                    {{ examUiState.basic.answered }} / {{ examUiState.basic.total }}
                                </p>
                            </div>
                            <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                    Specjalistyczne
                                </p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">
                                    {{ examUiState.specialist.answered }} / {{ examUiState.specialist.total }}
                                </p>
                            </div>
                        </div>

                        <div class="border border-[#d9dfe6] bg-[#fbfcfd] px-3 py-3">
                            <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#5f6f82]">
                                Wynik bieżący
                            </p>
                            <p class="mt-2 text-sm font-semibold text-[#111827]">
                                {{ sessionState.correct_answers_count }} poprawnych odpowiedzi
                            </p>
                            <p class="mt-2 text-xs leading-5 text-[#6b7280]">
                                Do zaliczenia potrzeba {{ examUiState.pass_threshold }} z {{ examUiState.max_points }} punktow.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </SessionExamLayout>
</template>
