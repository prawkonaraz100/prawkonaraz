<script setup lang="ts">
import LoginDrawer from '@/Components/Auth/LoginDrawer.vue';
import AuthTopNavigation from '@/Components/Auth/AuthTopNavigation.vue';
import MobileAppBar from '@/Components/MobileAppBar.vue';
import MobileBottomNavigation from '@/Components/MobileBottomNavigation.vue';
import Modal from '@/Components/Modal.vue';
import PjmVideoBlock from '@/Components/PjmVideoBlock.vue';
import QuestionAudioControl from '@/Components/QuestionAudioControl.vue';
import QuestionImageWithAnnotations from '@/Components/QuestionImageWithAnnotations.vue';
import QuestionExplanationRuntimeBlock from '@/Components/QuestionExplanationRuntimeBlock.vue';
import QuestionResultMediaFallback from '@/Components/QuestionResultMediaFallback.vue';
import QuestionVideoFrameWithAnnotations from '@/Components/QuestionVideoFrameWithAnnotations.vue';
import RegisterDrawer from '@/Components/Auth/RegisterDrawer.vue';
import SessionExpiredNotice from '@/Components/SessionExpiredNotice.vue';
import { useSessionExpiry } from '@/composables/useSessionExpiry';
import SessionExamLayout from '@/Layouts/SessionExamLayout.vue';
import SessionZenLayout from '@/Layouts/SessionZenLayout.vue';
import headThoughtIcon from '../../../images/session/head-thought.svg';
import questionSourceEmblem from '../../../images/session/question-source-emblem-crop.png';
import { apiClient, isApiClientError } from '@/lib/apiClient';
import { topicArtworkForKey } from '@/lib/topicArtwork';
import type { PageProps } from '@/types';
import {
    normalizeExplanationPlainText,
    renderExplanationHtml,
    renderInlineFormattedHtml,
    type InlineFormattingPalette,
} from '@/utils/explanationFormatting';
import {
    applyInlineFormattingSelection,
    type InlineFormattingMarker,
} from '@/utils/inlineFormattingSelection';
import {
    resolveImageAnnotations,
    resolveVideoFrameAnnotations,
} from '@/utils/explanationAnnotations';
import {
    resolveResultMediaPreviewUrl,
    resolveStudyQuestionImageUrl,
} from '@/utils/questionMediaSources';
import {
    resolveQuestionVideoFramePreviewPresentationClass,
    shouldShowPosterWhilePreparingQuestionVideoFrame,
    shouldRenderQuestionVideoFramePreview,
} from '@/utils/questionVideoFramePreview';
import {
    canToggleStudySessionExplanationOnDemand,
    hasOnDemandExplanationContent,
    resolveStudySessionExplanation,
    shouldShowStudySessionExplanationCard,
} from '@/utils/studySessionExplanation';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    type ComponentPublicInstance,
    type Ref,
} from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ArrowUpRight, Bold, Check, CircleCheck, CirclePlay, ExternalLink, FastForward, Gauge, Info, Keyboard, LibraryBig, Lightbulb, MessageSquareText, Palette, Repeat2, Settings, Speech, SquarePlay, SquareX, Trophy, Volume2, X } from '@lucide/vue';

interface SessionContext {
    type: string;
    label: string;
    title: string | null;
    collection_name: string | null;
    return_url: string;
}

interface SessionSummary {
    id: number;
    mode: string;
    ui_shell: string | null;
    status: string;
    license_category_id: number | null;
    license_category_code: string | null;
    license_category_name: string | null;
    context: SessionContext | null;
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
    bytes?: number | null;
    width?: number | null;
    height?: number | null;
    variant?: string | null;
}

interface SignLanguageAsset {
    id: number;
    external_id: string | null;
    role: string;
    url: string | null;
    mime_type: string | null;
    duration_seconds?: number | null;
    bytes?: number | null;
    width?: number | null;
    height?: number | null;
    variant?: string | null;
    processing_status?: string | null;
    review_required?: boolean;
}

interface QuestionAudioAsset {
    type: string;
    url: string | null;
    duration_seconds: number | null;
    encoding_format: string | null;
    transcript: string | null;
    asset_key?: string | null;
}

interface QuestionAudioPayload {
    question?: QuestionAudioAsset | null;
    correct_answer?: QuestionAudioAsset | null;
}

interface ExplanationAnnotation {
    id: number;
    target_kind: string;
    frame_time_seconds: number | null;
    annotation_type: string;
    x_percent: number;
    y_percent: number;
    width_percent: number | null;
    height_percent: number | null;
    arrow_length_percent: number | null;
    arrow_angle_degrees: number | null;
    arrow_stroke_percent: number | null;
    arrow_head_percent: number | null;
    label: string | null;
    tone: string | null;
    position: number;
}

interface ExplanationAsset {
    id: number;
    kind: string;
    title: string | null;
    body: string | null;
    caption: string | null;
    alt_text: string | null;
    image_url: string | null;
}

interface ExplanationSignReference {
    code: string;
    name: string;
    title: string;
    image_url: string;
    alt_text: string;
    url: string | null;
    source?: string;
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
    public_explanation_url?: string | null;
    source?: string | null;
    shared_explanation_has_conflict?: boolean;
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
    sign_language_assets?: SignLanguageAsset[];
    audio?: QuestionAudioPayload;
    explanation_asset: ExplanationAsset | null;
    explanation_sign_references?: ExplanationSignReference[];
    explanation_annotations: ExplanationAnnotation[];
}

type StudySessionAnswerKind = 'choice' | 'unknown' | 'timeout' | 'skipped';

interface ResultItem {
    id: number;
    external_id?: string | null;
    shared_explanation_has_conflict?: boolean;
    sequence_number: number;
    prompt: string | null;
    explanation: string | null;
    correct_answer: string | null;
    correct_answer_text: string | null;
    selected_answer: string | null;
    answer_kind: StudySessionAnswerKind | null;
    selected_answer_text: string | null;
    is_correct: boolean | null;
    response_time_ms: number | null;
    topic: QuestionTopic | null;
    media: QuestionMedia[];
    sign_language_assets?: SignLanguageAsset[];
    explanation_asset: ExplanationAsset | null;
    explanation_sign_references?: ExplanationSignReference[];
    explanation_annotations: ExplanationAnnotation[];
}

const ANSWER_KIND_CHOICE: StudySessionAnswerKind = 'choice';
const ANSWER_KIND_UNKNOWN: StudySessionAnswerKind = 'unknown';

const isUnknownAnswerKind = (answerKind: string | null | undefined) =>
    answerKind === ANSWER_KIND_UNKNOWN;

const isAnsweredResult = (result: ResultItem) =>
    result.selected_answer !== null || isUnknownAnswerKind(result.answer_kind);

interface CompletionTimingComparison {
    previous_duration_seconds: number | null;
    previous_average_correct_response_time_ms: number | null;
    same_question_count: boolean;
    score_not_worse: boolean;
    saved_duration_seconds: number | null;
    saved_average_correct_response_time_ms: number | null;
    show_reward: boolean;
}

interface CompletionTiming {
    duration_seconds: number | null;
    average_correct_response_time_ms: number | null;
    comparison: CompletionTimingComparison;
}

interface PjmCompletionCoverage {
    code: string;
    name: string;
    total_questions: number;
    pjm_questions: number;
    missing_questions: number;
    coverage_percent: number;
}

interface PjmCompletionTopicOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
    answered_count: number;
    progress_percent: number;
}

interface PjmCompletionTopicGroup {
    label: string;
    options: PjmCompletionTopicOption[];
}

interface PjmCompletionProgress {
    total_questions: number;
    answered_questions: number;
    unanswered_questions: number;
    incorrect_questions: number;
    correct_questions: number;
    memorized_questions: number;
    progress_percent: number;
    current_topic_id: number | null;
    review_topic_id: number | null;
    topic_groups: PjmCompletionTopicGroup[];
}

interface PjmCompletionSummary {
    coverage: PjmCompletionCoverage | null;
    progress: PjmCompletionProgress | null;
    has_full_product_access: boolean;
    pricing_url: string;
    session_index_url: string;
    pjm_index_url: string;
}

interface ReviewCompletionSummary {
    version: string;
    memory_signal_version?: string;
    verified_memory_signal_version?: string;
    total_questions_count: number;
    answered_count: number;
    correct_answers_count: number;
    incorrect_answers_count: number;
    unknown_answers_count: number;
    choice_incorrect_answers_count: number;
    needs_recovery_answers_count: number;
    recovery_count: number;
    learning_count: number;
    stable_count: number;
    segment_counts: {
        overdue: number;
        risky: number;
        reinforce: number;
    };
    memory_state_counts: {
        new: number;
        learning: number;
        review: number;
        relearning: number;
        mastered: number;
        leech: number;
    };
    coach_message: string;
    next_review_label: string;
    next_step: {
        tone: 'recovery' | 'learning' | 'stable';
        headline: string;
        message: string;
        primary_action_label: string;
        time_label: string;
    };
}

type PjmQuestionCountStrategy = 'fixed' | 'topic_remaining';

interface ExamUiSummary {
    duration_seconds: number;
    basic: {
        answered: number;
        total: number;
    };
    specialist: {
        answered: number;
        total: number;
    };
}

interface SessionFilters {
    question_topic_id: number | null;
    question_scope: 'all' | 'basic' | 'specialist';
    question_status: string;
    randomize_order: boolean;
    question_count: number;
}

interface GroupOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
}

interface GroupBucket {
    label: string;
    options: GroupOption[];
}

const topicArtworkFor = (topic: GroupOption): string | null => topicArtworkForKey(topic.key);
const topicArtworkImageClass = 'absolute left-0 top-1/2 h-20 w-20 -translate-y-1/2 object-contain';
const topicArtworkContentClass = 'relative pl-24';
const topicArtworkRowClass = 'min-h-24 items-center';

type TopicRecordState = 'none' | 'first_record' | 'improved_record';

interface TopicCompletionOverviewItem {
    topic_id: number;
    key: string;
    label: string;
    bucket_label: string;
    questions_count: number;
    is_current_topic: boolean;
    last_duration_seconds: number | null;
    last_score_percent: number | null;
    best_duration_seconds: number | null;
    completion_count: number;
    perfect_completion_count: number;
    record_state: TopicRecordState;
    saved_duration_seconds: number | null;
    previous_best_duration_seconds: number | null;
}

interface TopicCompletionOverview {
    category_id: number;
    question_scope: 'all' | 'basic' | 'specialist';
    current_topic_id: number | null;
    items: TopicCompletionOverviewItem[];
    learning_path: {
        total_topics: number;
        mastered_topics: number;
        mastered_topic_ids: number[];
        current_position: number | null;
        remaining_after_current: number | null;
    };
}

interface StatusOption {
    value: string;
    label: string;
}

interface RoadmapTopicOption extends GroupOption {
    bucketLabel: string;
}

interface SessionRoadmapItem {
    id: number;
    key: string;
    label: string;
    bucketLabel: string;
    position: number;
    totalQuestions: number;
    masteredQuestions: number;
    remainingQuestions: number;
    progressPercent: number;
    status: 'completed' | 'current' | 'next' | 'upcoming';
    badge: string;
}

interface CompletionProgressPanelItem extends SessionRoadmapItem {
    displayPercent: number;
    displayTimeLabel: string | null;
    isActive: boolean;
    isMastered: boolean;
    statusLabel: string;
}

type VideoPlaybackRate = 1 | 2 | 4;
type HintTimingPreference = 'normal' | 'plus2' | 'plus4' | 'plus6';
type FeedbackMode = 'instant' | 'instant_explanation' | 'review';
type VisualExplanationsMode = 'before_answer' | 'after_incorrect' | 'off';
type SessionPreferences = {
    autoAdvance: boolean;
    autoPlayVideos: boolean;
    showQuestionAudioControl: boolean;
    autoPlayQuestionAudio: boolean;
    autoPlayCorrectAnswerAudio: boolean;
    videoPlaybackRate: VideoPlaybackRate;
    autoJumpToVideoEnding: boolean;
    hintTimingPreference: HintTimingPreference;
    feedbackMode: FeedbackMode;
    enableInlineBold: boolean;
    enableInlineColors: boolean;
    showVisualAnnotations: boolean;
};

interface PublicDemoRoutes {
    demo: string;
    current?: string | null;
    answer: string;
    complete: string;
    restart: string;
    landing: string;
    pricing: string;
    register: string;
    learning: string;
    activate: string;
    questions?: string | null;
}

interface PublicDemoGate {
    enabled: boolean;
    primary_label: string;
    secondary_label: string;
}

interface PublicDemoContext {
    enabled: boolean;
    mode?: 'frozen_packet' | 'session_backed';
    routes: PublicDemoRoutes;
    viewer: {
        authenticated: boolean;
        has_full_access: boolean;
    };
    gate?: PublicDemoGate | null;
}

const props = withDefaults(defineProps<{
    session: SessionSummary;
    questionIds: number[];
    progress: ProgressSummary;
    examUi: ExamUiSummary;
    currentQuestionNumber: number | null;
    currentQuestion: CurrentQuestion | null;
    questionPool: CurrentQuestion[];
    questionPoolMode: 'full' | 'windowed';
    questionBatchSize: number;
    prefetchedQuestions: CurrentQuestion[];
    sessionFilters?: SessionFilters;
    topicGroups?: GroupBucket[];
    topicCompletionOverview?: TopicCompletionOverview | null;
    statusOptions?: StatusOption[];
    results: ResultItem[];
    completionTiming?: CompletionTiming;
    pjmCompletion?: PjmCompletionSummary | null;
    reviewCompletion?: ReviewCompletionSummary | null;
    publicDemo?: PublicDemoContext | null;
}>(), {
    sessionFilters: () => ({
        question_topic_id: null,
        question_scope: 'all',
        question_status: 'all',
        randomize_order: false,
        question_count: 1,
    }),
    topicGroups: () => [],
    topicCompletionOverview: null,
    statusOptions: () => [
        { value: 'all', label: 'Wszystkie z tego działu' },
        { value: 'unanswered', label: 'Jeszcze nieprzerobione' },
        { value: 'incorrect', label: 'Z błędami' },
        { value: 'correct', label: 'Dobrze rozwiązane' },
        { value: 'memorized', label: 'Utrwalone' },
    ],
    completionTiming: () => ({
        duration_seconds: null,
        average_correct_response_time_ms: null,
        comparison: {
            previous_duration_seconds: null,
            previous_average_correct_response_time_ms: null,
            same_question_count: false,
            score_not_worse: false,
            saved_duration_seconds: null,
            saved_average_correct_response_time_ms: null,
            show_reward: false,
        },
    }),
    pjmCompletion: null,
    reviewCompletion: null,
    publicDemo: null,
});
const page = usePage<PageProps>();

const answerForm = useForm<{
    question_id: number;
    selected_answer: string | null;
    answer_kind: StudySessionAnswerKind;
    response_time_ms: number | null;
}>({
    question_id: props.currentQuestion?.id ?? 0,
    selected_answer: '',
    answer_kind: ANSWER_KIND_CHOICE,
    response_time_ms: null,
});

const completeForm = useForm({});
const pjmSessionForm = useForm<{
    question_topic_id: number | null;
    question_count: number;
    question_count_strategy: PjmQuestionCountStrategy;
    question_status: string;
    randomize_order: boolean;
}>({
    question_topic_id: null,
    question_count: 40,
    question_count_strategy: 'fixed',
    question_status: 'unanswered',
    randomize_order: false,
});
const switchTopicForm = useForm<{
    license_category_id: number | null;
    mode: 'learn';
    ui_shell: 'exam_like' | 'zen';
    question_topic_id: number | null;
    question_scope: 'all' | 'basic' | 'specialist';
    question_status: string;
    randomize_order: boolean;
    question_count: number;
}>({
    license_category_id: props.session.license_category_id,
    mode: 'learn',
    ui_shell: props.session.ui_shell === 'exam_like' ? 'exam_like' : 'zen',
    question_topic_id: props.sessionFilters.question_topic_id,
    question_scope: props.sessionFilters.question_scope,
    question_status: props.sessionFilters.question_status,
    randomize_order: props.sessionFilters.randomize_order,
    question_count: props.sessionFilters.question_count,
});
const followUpSessionForm = useForm<{
    license_category_id: number | null;
    mode: 'learn';
    ui_shell: 'exam_like' | 'zen';
    question_topic_id: number | null;
    question_scope: 'all' | 'basic' | 'specialist';
    question_status: string;
    randomize_order: boolean;
    question_count: number;
}>({
    license_category_id: props.session.license_category_id,
    mode: 'learn',
    ui_shell: props.session.ui_shell === 'exam_like' ? 'exam_like' : 'zen',
    question_topic_id: props.sessionFilters.question_topic_id,
    question_scope: props.sessionFilters.question_scope,
    question_status: 'all',
    randomize_order: props.sessionFilters.randomize_order,
    question_count: props.sessionFilters.question_count,
});
const selectedAnswer = ref<string | null>(null);
const presentedAt = ref<number | null>(null);
const questionStage = ref<'preview' | 'answer'>('answer');
const autoAdvance = ref(true);
const autoPlayVideos = ref(true);
const showQuestionAudioControl = ref(true);
const autoPlayQuestionAudio = ref(false);
const autoPlayCorrectAnswerAudio = ref(false);
const videoPlaybackRate = ref<VideoPlaybackRate>(2);
const autoJumpToVideoEnding = ref(false);
const hintTimingPreference = ref<HintTimingPreference>('plus2');
const feedbackMode = ref<FeedbackMode>('instant_explanation');
const enableInlineBold = ref(true);
const enableInlineColors = ref(true);
const showVisualAnnotations = ref(true);
const videoPlaybackState = ref<Record<string, boolean>>({});
const videoActivatedState = ref<Record<string, boolean>>({});
const keyboardSelectedOptionKey = ref<string | null>(null);
const isSettingsOpen = ref(false);
const isTopicPickerOpen = ref(false);
const isMobileSessionMenuOpen = ref(false);
const isLearningGuideVisible = ref(false);
const isTopicGuideVisible = ref(false);
const showCompletionResults = ref(props.session.status === 'completed');
const showCorrectCompletionResults = ref(props.session.status === 'completed');
const showReviewTrainerQuestionDetails = ref(false);
const isPublicDemoCompletionPromptDismissed = ref(false);
const isPublicDemoGateDismissed = ref(false);
const skipPublicDemoGateNextTime = ref(false);
const showExplanation = ref(false);
const showExplanationOnDemand = ref(false);
const visualExplanationsMode = ref<VisualExplanationsMode>('after_incorrect');
const answerSyncFailedQuestionId = ref<number | null>(null);
const syncError = ref<string | null>(null);
const localSessionCompleted = ref(props.session.status === 'completed');
const activeQuestionState = ref<CurrentQuestion | null>(props.currentQuestion);
const preparedNextQuestion = ref<CurrentQuestion | null>(null);
const preparedNextQuestionNumber = ref<number | null>(null);
const currentQuestionNumberState = ref<number | null>(props.currentQuestionNumber);
const pjmCompletionState = ref<PjmCompletionSummary | null>(props.pjmCompletion ?? null);
const reviewCompletionState = ref<ReviewCompletionSummary | null>(props.reviewCompletion ?? null);
const topicCompletionOverviewState = ref<TopicCompletionOverview | null>(props.topicCompletionOverview ?? null);
const questionCache = ref<Record<number, CurrentQuestion>>(
    [props.currentQuestion, ...props.questionPool, ...props.prefetchedQuestions].reduce<Record<number, CurrentQuestion>>(
        (carry, question) => {
            if (question) {
                carry[question.id] = question;
            }

            return carry;
        },
        {},
    ),
);
const progressState = ref<ProgressSummary>({ ...props.progress });
const sessionState = ref<SessionSummary>({ ...props.session });
const learningSyncInFlight = ref(false);
const topicSwitchInFlight = ref(false);
const followUpActionInFlight = ref(false);
const pendingAnswerSyncCount = ref(0);
const questionBatchFetchInFlight = ref(false);
const answerInteractionLocked = ref(false);
const questionTransitionInFlight = ref(false);
const {
    handleSessionExpiryError,
    sessionExpired,
} = useSessionExpiry(() => {
    clearAutoAdvanceTimeout();
    answerInteractionLocked.value = true;
    questionTransitionInFlight.value = false;
    topicSwitchInFlight.value = false;
    followUpActionInFlight.value = false;
    learningSyncInFlight.value = false;
    syncError.value = 'Sesja wygasła. Ostatnia operacja nie została potwierdzona przez serwer.';
});
const completionResultsRef = ref<HTMLElement | null>(null);
const completionProgressPanelScroller = ref<HTMLElement | null>(null);
const mobileAnswerDockRef = ref<HTMLElement | null>(null);
const mobileAnswerDockHeight = ref(0);
type QuestionAudioControlInstance = ComponentPublicInstance & {
    pause: () => void;
    play: () => Promise<void>;
    stop: () => void;
};
const questionAudioControlRef = ref<QuestionAudioControlInstance | null>(null);
const correctAnswerAudioElement = ref<HTMLAudioElement | null>(null);
const correctAnswerAudioSourceKey = ref<string | null>(null);
const localResults = ref<Record<number, ResultItem>>(
    props.results.reduce<Record<number, ResultItem>>((carry, result) => {
        if (isAnsweredResult(result)) {
            carry[result.id] = { ...result };
        }

        return carry;
    }, {}),
);
const isExplanationEditorOpen = ref(false);
const explanationEditorQuestionId = ref<number | null>(null);
const explanationEditorQuestionPrompt = ref<string | null>(null);
const explanationEditorQuestionExternalId = ref<string | null>(null);
const explanationEditorDraft = ref('');
const explanationEditorError = ref<string | null>(null);
const explanationEditorSaving = ref(false);
const explanationEditorApplyScope = ref<'single' | 'shared_external_id'>('single');
const explanationEditorHasSharedConflict = ref(false);
const explanationEditorTextareaRef = ref<HTMLTextAreaElement | null>(null);
const isQuestionEditorOpen = ref(false);
const questionEditorQuestionId = ref<number | null>(null);
const questionEditorQuestionPrompt = ref<string | null>(null);
const questionEditorQuestionExternalId = ref<string | null>(null);
const questionEditorDraft = ref('');
const questionEditorError = ref<string | null>(null);
const questionEditorSaving = ref(false);
const questionEditorApplyScope = ref<'single' | 'shared_external_id'>('single');
const questionEditorTextareaRef = ref<HTMLTextAreaElement | null>(null);
const pendingSyncRequests = new Set<Promise<void>>();
const questionIdsBeingFetched = new Set<number>();
let pendingQuestionBatchRequest: Promise<void> | null = null;
let answerSyncQueue: Promise<void> = Promise.resolve();
let autoAdvanceTimeoutId: ReturnType<typeof setTimeout> | null = null;
let mobileAnswerDockResizeObserver: ResizeObserver | null = null;
const videoElements = new Map<string, HTMLVideoElement>();

watch(() => props.topicCompletionOverview, (overview) => {
    topicCompletionOverviewState.value = overview ?? null;
});

const SESSION_PREFERENCES_KEY = 'study-session-preferences';
const SESSION_VISUAL_EXPLANATIONS_KEY_PREFIX = 'study-session-visual-explanations:';
const SESSION_LEARNING_PANEL_SEEN_KEY = 'study-session-learning-panel-seen';
const SESSION_LEARNING_GUIDE_DISMISSED_KEY = 'study-session-learning-guide-dismissed';
const SESSION_TOPIC_GUIDE_DISMISSED_KEY = 'study-session-topic-guide-dismissed';
const PUBLIC_DEMO_GATE_DISMISSED_KEY = 'public-demo-learning-intro-dismissed';
const INSTANT_FEEDBACK_GLANCE_MS = 90;
const INSTRUCTOR_HINT_MIN_GLANCE_MS = 4200;
const INSTRUCTOR_HINT_MAX_GLANCE_MS = 6800;
const INSTRUCTOR_HINT_FALLBACK_GLANCE_MS = 3000;
const INSTRUCTOR_HINT_LIMIT = 145;
const VIDEO_END_PREVIEW_SECONDS = 2;
const SAVE_ANSWER_SYNC_ERROR_MESSAGE = 'Trwają prace na serwerze i aktualnie jest problem z zapisem odpowiedzi.';
type InlineEditorSelection = {
    start: number;
    end: number;
};
const explanationEditorSelection = ref<InlineEditorSelection>({
    start: 0,
    end: 0,
});
const questionEditorSelection = ref<InlineEditorSelection>({
    start: 0,
    end: 0,
});
const inlineEditorFormattingActions: Array<{
    marker: InlineFormattingMarker;
    label: string;
    class: string;
}> = [
    {
        marker: 'bold',
        label: 'Pogrub',
        class: 'border-[#d1d5db] bg-white text-[#161414] hover:border-[#9ca3af]',
    },
    {
        marker: 'green',
        label: 'Zielony',
        class: 'border-[#cfe0d2] bg-[#f5faf6] text-[#163222] hover:border-[#9fbcaa]',
    },
    {
        marker: 'red',
        label: 'Czerwony',
        class: 'border-[#e5cdcd] bg-[#fbf1f1] text-[#612d2d] hover:border-[#cda3a3]',
    },
];
const FIRST_VISIT_SESSION_PREFERENCES: SessionPreferences = {
    autoAdvance: true,
    autoPlayVideos: true,
    showQuestionAudioControl: true,
    autoPlayQuestionAudio: false,
    autoPlayCorrectAnswerAudio: false,
    videoPlaybackRate: 2,
    autoJumpToVideoEnding: false,
    hintTimingPreference: 'plus2',
    feedbackMode: 'instant_explanation',
    enableInlineBold: true,
    enableInlineColors: true,
    showVisualAnnotations: true,
};
const viewportWidth = ref(0);
const viewportHeight = ref(0);
const reservesExplanationSpace = computed(() => feedbackMode.value === 'instant_explanation');
const globalVisualExplanationsMode = computed<VisualExplanationsMode>(() =>
    page.props.studyContext?.visualExplanationsMode ?? 'after_incorrect',
);
const visualExplanationsStorageKey = computed(() =>
    sessionState.value.id
        ? `${SESSION_VISUAL_EXPLANATIONS_KEY_PREFIX}${sessionState.value.id}`
        : null,
);

const defaultAutoAdvanceForFeedbackMode = (mode: FeedbackMode) =>
    mode !== 'review';

const isVideoPlaybackRate = (value: unknown): value is VideoPlaybackRate =>
    value === 1 || value === 2 || value === 4;

const hintTimingOptions: Array<{
    value: HintTimingPreference;
    label: string;
    offsetMs: number;
}> = [
    { value: 'normal', label: '0s', offsetMs: 0 },
    { value: 'plus2', label: '+2s', offsetMs: 2000 },
    { value: 'plus4', label: '+4s', offsetMs: 4000 },
    { value: 'plus6', label: '+6s', offsetMs: 6000 },
];

const isHintTimingPreference = (value: unknown): value is HintTimingPreference =>
    hintTimingOptions.some((option) => option.value === value);

const isVisualExplanationsMode = (value: unknown): value is VisualExplanationsMode =>
    value === 'before_answer' || value === 'after_incorrect' || value === 'off';

const effectiveVideoPlaybackRate = computed<VideoPlaybackRate>(() =>
    autoJumpToVideoEnding.value ? 1 : videoPlaybackRate.value,
);

const hintTimingOffsetMs = computed(() =>
    hintTimingOptions.find((option) => option.value === hintTimingPreference.value)?.offsetMs ?? 0,
);

const hintTimingSliderIndex = computed(() =>
    Math.max(
        hintTimingOptions.findIndex((option) => option.value === hintTimingPreference.value),
        0,
    ),
);

const setHintTimingPreferenceByIndex = (value: number | string) => {
    const parsedIndex = typeof value === 'number' ? value : Number(value);
    const clampedIndex = Math.min(
        Math.max(Math.round(Number.isFinite(parsedIndex) ? parsedIndex : 0), 0),
        hintTimingOptions.length - 1,
    );
    const selectedOption = hintTimingOptions[clampedIndex];

    if (!selectedOption) {
        return;
    }

    hintTimingPreference.value = selectedOption.value;
};

const stripHintBoilerplate = (text: string) =>
    text
        .replace(/\([^)]*art\.[^)]*\)/giu, ' ')
        .replace(/^(zgodnie z|na podstawie|w myśl|stosownie do|w świetle|na mocy)\b[^.?!:;]{0,140}[:;,-]\s*/iu, '')
        .replace(/^(prawidłowa|poprawna)\s+odpowiedź\s+(to|brzmi)\s*/iu, '')
        .replace(/^odpowiedź\s+jest\s+poprawna,?\s*bo\s*/iu, '')
        .replace(/^należy\s+pamiętać,\s+że\s*/iu, '')
        .replace(/^(w tej sytuacji|w tym pytaniu)\s*/iu, '')
        .replace(/\s+/g, ' ')
        .trim();

const finalizeHintSentence = (text: string) => {
    const sanitized = stripHintBoilerplate(text)
        .replace(/^[—–-]\s*/u, '')
        .replace(/[,:;]\s*$/u, '')
        .trim();

    if (!sanitized) {
        return '';
    }

    return /[.!?]$/u.test(sanitized)
        ? sanitized
        : `${sanitized}.`;
};

const normalizeExplanationRichText = (explanation: string | null | undefined) =>
    (explanation ?? '')
        .replace(/\r\n?/g, '\n')
        .replace(/[ \t]+/g, ' ')
        .trim();

const buildInstructorHintPayload = (explanation: string) => {
    const normalizedExplanation = normalizeExplanationPlainText(explanation);
    const normalizedRichExplanation = normalizeExplanationRichText(explanation);

    if (!normalizedExplanation) {
        return {
            text: '',
            richText: '',
        };
    }

    const richSentences = normalizedRichExplanation
        .split(/(?<=[.!?])\s+/u)
        .map(finalizeHintSentence)
        .filter(Boolean);
    const sentences = richSentences
        .map((sentence) => normalizeExplanationPlainText(sentence))
        .filter(Boolean);

    const resolveRichSentence = (plainSentence: string) => {
        const resolved = richSentences.find(
            (sentence) => normalizeExplanationPlainText(sentence) === plainSentence,
        );

        return resolved ?? plainSentence;
    };

    const firstSentence = sentences[0] ?? '';
    const secondSentence = sentences[1] ?? '';
    const firstSentenceRich = resolveRichSentence(firstSentence);
    const secondSentenceRich = resolveRichSentence(secondSentence);

    if (firstSentence && firstSentence.length <= INSTRUCTOR_HINT_LIMIT) {
        if (
            secondSentence
            && firstSentence.length < 55
            && `${firstSentence} ${secondSentence}`.length <= INSTRUCTOR_HINT_LIMIT
        ) {
            return {
                text: `${firstSentence} ${secondSentence}`.trim(),
                richText: `${firstSentenceRich} ${secondSentenceRich}`.trim(),
            };
        }

        return {
            text: firstSentence,
            richText: firstSentenceRich,
        };
    }

    const preferredSentence = sentences.find((sentence) =>
        sentence.length >= 55 && sentence.length <= INSTRUCTOR_HINT_LIMIT,
    ) ?? sentences.find((sentence) => sentence.length <= INSTRUCTOR_HINT_LIMIT)
        ?? finalizeHintSentence(normalizedExplanation);
    const preferredSentenceRich = resolveRichSentence(preferredSentence);

    if (preferredSentence.length <= INSTRUCTOR_HINT_LIMIT) {
        return {
            text: preferredSentence,
            richText: preferredSentenceRich,
        };
    }

    const candidate = preferredSentence.slice(0, INSTRUCTOR_HINT_LIMIT - 1);
    const lastWordBoundary = candidate.lastIndexOf(' ');
    const safePreview = lastWordBoundary >= 90
        ? candidate.slice(0, lastWordBoundary)
        : candidate;

    return {
        text: `${safePreview.replace(/[,:;]\s*$/u, '').trim()}…`,
        richText: `${safePreview.replace(/[,:;]\s*$/u, '').trim()}…`,
    };
};

const resolveInstructorHintGlanceMs = (
    hint: string,
    hasContent: boolean,
    offsetMs: number,
) => {
    if (!hasContent) {
        return INSTRUCTOR_HINT_FALLBACK_GLANCE_MS + offsetMs;
    }

    const readingDelay = INSTRUCTOR_HINT_MIN_GLANCE_MS + Math.round(Math.min(hint.length, INSTRUCTOR_HINT_LIMIT) * 14);

    return Math.min(readingDelay, INSTRUCTOR_HINT_MAX_GLANCE_MS) + offsetMs;
};

const syncViewport = () => {
    if (typeof window === 'undefined') {
        return;
    }

    viewportWidth.value = window.innerWidth;
    viewportHeight.value = window.innerHeight;
};

const isCompactSessionViewport = computed(
    () =>
        viewportWidth.value >= 1024
        && (viewportHeight.value > 0 && viewportHeight.value < 860
            || viewportWidth.value < 1450),
);
const isPhoneViewport = computed(
    () => viewportWidth.value > 0 && viewportWidth.value <= 1023,
);
const isNarrowPhoneViewport = computed(
    () => viewportWidth.value > 0 && viewportWidth.value < 640,
);
const useShortMobileSessionLabels = computed(
    () => viewportWidth.value > 0 && viewportWidth.value <= 440,
);
const mobileSessionQuestionLabel = computed(
    () => (useShortMobileSessionLabels.value ? 'PYT' : 'PYTANIE'),
);
const mobileSessionCategoryLabel = computed(
    () => (useShortMobileSessionLabels.value ? 'KAT' : 'KATEGORIA'),
);
const hasExplanationCallout = computed(() => shouldShowExplanationCard.value);
const isExplanationCompactViewport = computed(
    () => reservesExplanationSpace.value && viewportHeight.value > 0 && viewportHeight.value < 860,
);
const hasQuestionMedia = computed(
    () => (activeQuestion.value?.media.length ?? 0) > 0,
);
const hasVideoQuestionMedia = computed(
    () => activeQuestion.value?.media.some((media) => media.kind === 'video' && Boolean(media.url)) ?? false,
);
const isLongPromptOnPhone = computed(
    () => isPhoneViewport.value && (activeQuestion.value?.prompt.length ?? 0) >= 160,
);
const isExamMode = computed(() => props.session.mode === 'exam');
const isPjmMode = computed(() => props.session.mode === 'pjm');
const isReviewTrainerMode = computed(() => props.session.mode === 'sr_review' || props.session.mode === 'review');
const isLocalLearningMode = computed(() => props.session.mode === 'learn' || isPjmMode.value || isReviewTrainerMode.value);
const sessionContextLabel = computed(() => sessionState.value.context?.label ?? null);
const currentQuestionContextLabel = computed(() =>
    sessionContextLabel.value
    ?? activeQuestion.value?.topic?.name
    ?? (activeQuestion.value ? scopeLabel(activeQuestion.value.structure_scope) : ''),
);
const canUseQuestionAudio = computed(() => props.session.mode === 'learn');
const currentQuestionAudio = computed<QuestionAudioAsset | null>(() =>
    canUseQuestionAudio.value
        ? activeQuestion.value?.audio?.question ?? null
        : null,
);
const currentCorrectAnswerAudio = computed<QuestionAudioAsset | null>(() =>
    canUseQuestionAudio.value
        ? activeQuestion.value?.audio?.correct_answer ?? null
        : null,
);
const shouldShowQuestionAudioControl = computed(() =>
    Boolean(showQuestionAudioControl.value && currentQuestionAudio.value?.url),
);
const shouldAutoPlayQuestionAudio = computed(() =>
    Boolean(showQuestionAudioControl.value && autoPlayQuestionAudio.value && currentQuestionAudio.value?.url),
);
const questionAudioAutoplayKey = computed(() =>
    shouldAutoPlayQuestionAudio.value
        ? `${activeQuestion.value?.id ?? 'none'}:${currentQuestionAudio.value?.asset_key ?? currentQuestionAudio.value?.url ?? ''}`
        : null,
);
const shouldAutoPlayCorrectAnswerAudioAfterMistake = computed(() =>
    Boolean(canUseQuestionAudio.value && autoPlayCorrectAnswerAudio.value && currentCorrectAnswerAudio.value?.url),
);
const isPublicDemoMode = computed(() => Boolean(props.publicDemo?.enabled));
const usesFrozenPublicDemo = computed(() =>
    Boolean(isPublicDemoMode.value && props.publicDemo?.mode === 'frozen_packet'),
);
const isPublicDemoGate = computed(() =>
    Boolean(props.publicDemo?.gate?.enabled) && !isPublicDemoGateDismissed.value,
);
const pageTitle = computed(() =>
    isPublicDemoGate.value
        ? 'Testy na prawo jazdy 2026 - demo odtwarzacza'
        : sessionContextLabel.value ?? 'Sesja',
);
const isTightLaptopViewport = computed(
    () => viewportWidth.value >= 1024
        && viewportWidth.value < 1450
        && viewportHeight.value > 0
        && viewportHeight.value < 800,
);
const isTightExamLikeLaptopViewport = computed(
    () => isExamLikeShell.value && isTightLaptopViewport.value,
);
const usePinnedDesktopShell = computed(
    () =>
        isExamMode.value
        && isExamLikeShell.value
        && (
            viewportWidth.value >= 1536
            || (viewportWidth.value >= 1280
                && viewportHeight.value > 0
                && viewportHeight.value >= 900)
        ),
);
const isCompactExamLikeDesktopViewport = computed(
    () => isExamLikeShell.value && viewportWidth.value >= 1280 && !usePinnedDesktopShell.value,
);

const workspaceHeightClass = computed(() =>
    !usePinnedDesktopShell.value
        ? ''
        : isCompactSessionViewport.value
        ? 'xl:h-[calc(100dvh-5.75rem)]'
        : 'xl:h-[calc(100dvh-6.5rem)]',
);

const contentColumnClass = computed(() => {
    if (isExamLikeShell.value) {
        return usePinnedDesktopShell.value
            ? 'xl:grid xl:h-full xl:min-h-0 xl:grid-rows-[auto_minmax(0,1fr)_auto_auto] xl:gap-0'
            : 'flex min-h-0 flex-col';
    }

    if (reservesExplanationSpace.value && isCompactSessionViewport.value) {
        return 'xl:grid xl:h-full xl:min-h-0 xl:grid-rows-[minmax(0,1fr)_8rem_9.25rem] xl:gap-2.25';
    }

    if (reservesExplanationSpace.value) {
        return 'xl:grid xl:h-full xl:min-h-0 xl:grid-rows-[minmax(0,1fr)_8.75rem_9.5rem] xl:gap-2.25';
    }

    if (isCompactSessionViewport.value) {
        return 'xl:grid xl:h-full xl:min-h-0 xl:grid-rows-[minmax(0,1fr)_7.25rem_9.25rem] xl:gap-2.25';
    }

    return 'xl:grid xl:h-full xl:min-h-0 xl:grid-rows-[minmax(0,1fr)_8rem_9.5rem] xl:gap-2.25';
});

const activeSessionLayout = computed(() =>
    sessionState.value.ui_shell === 'exam_like'
        ? SessionExamLayout
        : SessionZenLayout,
);

const isExamLikeShell = computed(() => sessionState.value.ui_shell === 'exam_like');
const isZenLearningShell = computed(
    () => !isExamLikeShell.value && sessionState.value.mode === 'learn',
);
const isReviewTrainerLearningShell = computed(
    () => !isExamLikeShell.value && isReviewTrainerMode.value,
);
const useFlatPhoneExamWorkspace = computed(
    () => isExamLikeShell.value && isNarrowPhoneViewport.value,
);
const useFlatPhoneZenWorkspace = computed(
    () => isZenLearningShell.value && isNarrowPhoneViewport.value && !localSessionCompleted.value,
);
const useFlatPhoneReviewWorkspace = computed(
    () => isReviewTrainerLearningShell.value && isNarrowPhoneViewport.value && !localSessionCompleted.value,
);
const useFlatPhoneFocusWorkspace = computed(
    () => useFlatPhoneZenWorkspace.value || useFlatPhoneReviewWorkspace.value,
);
const useEdgeToEdgeSessionMedia = computed(
    () =>
        (useFlatPhoneExamWorkspace.value || useFlatPhoneFocusWorkspace.value)
        && hasQuestionMedia.value,
);
const inlineFormattingPalette = computed<InlineFormattingPalette>(() =>
    isExamLikeShell.value ? 'classic' : 'zen',
);

const questionHeaderBarClass = computed(() =>
    isExamLikeShell.value
        ? 'flex items-center gap-3 rounded-none border border-[#e5e7eb] bg-white px-4 py-3 text-sm text-[#111827] shadow-[0_16px_32px_rgba(15,23,42,0.04)]'
        : 'flex items-center gap-3 bg-white/58 px-4 py-3 text-sm text-neutral-900 shadow-[0_8px_24px_rgba(36,33,28,0.035)] backdrop-blur-[7px] transition-[background-color,box-shadow] duration-300 ease-out',
);

const questionHeaderMetaClass = computed(() =>
    isExamLikeShell.value ? 'min-w-0 flex-1 truncate whitespace-nowrap text-[#6b7280]' : 'min-w-0 flex-1 truncate whitespace-nowrap text-[#6a6458]',
);

const finishSessionButtonClass = computed(() =>
    isExamLikeShell.value
        ? 'whitespace-nowrap rounded-none border border-[#d2b35b] bg-[#efc54f] px-3.5 py-1.5 font-semibold text-[#463309] transition hover:border-[#c8a949] hover:bg-[#e8bd4c]'
        : 'whitespace-nowrap bg-white/86 px-3.5 py-1.5 font-medium text-[#4f493f] shadow-[0_8px_20px_rgba(36,33,28,0.06)] transition hover:bg-white hover:text-neutral-900',
);

const sessionOptionsButtonClass = computed(() =>
    isExamLikeShell.value
        ? 'self-start whitespace-nowrap rounded-none border border-[#d1d5db] bg-white px-3 py-2 text-sm font-semibold text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#161414]'
        : useFlatPhoneFocusWorkspace.value
        ? 'self-start whitespace-nowrap px-2.5 py-1.5 text-sm font-medium text-[#6b7280] transition hover:bg-white/50 hover:text-[#161414] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0057a3]'
        : 'self-start whitespace-nowrap border border-[#d9e2ec] bg-white px-3 py-1.5 text-sm font-medium text-[#4b5563] transition hover:border-[#bcc7d3] hover:bg-[#f8fafc] hover:text-[#161414]',
);

const workspaceShellClass = computed(() =>
    isExamLikeShell.value
        ? useFlatPhoneExamWorkspace.value
            ? 'relative bg-white'
            : usePinnedDesktopShell.value
            ? 'relative h-full overflow-hidden rounded-none border border-[#e5e7eb] bg-white shadow-[0_22px_54px_rgba(15,23,42,0.05)]'
            : 'relative rounded-none border border-[#e5e7eb] bg-white shadow-[0_22px_54px_rgba(15,23,42,0.05)]'
        : useFlatPhoneFocusWorkspace.value
        ? 'relative bg-white'
        : 'relative h-full overflow-hidden shadow-[0_30px_72px_rgba(36,33,28,0.055),0_12px_24px_rgba(36,33,28,0.025)] transition-[box-shadow,transform] duration-300 ease-out',
);

const workspaceBackdropClass = computed(() =>
    isExamLikeShell.value
        ? 'hidden'
        : isPhoneViewport.value
        ? 'hidden'
        : 'pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_32%,rgba(255,255,255,0.9)_0%,rgba(255,255,255,0.82)_26%,rgba(249,247,241,0.62)_56%,rgba(243,240,233,0.16)_100%)]',
);

const examLikeScopeHeaderClass = computed(() =>
    isCompactExamLikeDesktopViewport.value
        ? 'border-b border-[#eceff3] bg-[#fafafa] px-3 py-1.5 xl:px-3 xl:py-1.5'
        : 'border-b border-[#eceff3] bg-[#fafafa] px-3.5 py-2 xl:px-3.5 xl:py-2',
);

const examLikeScopeLabelClass = computed(() =>
    isCompactExamLikeDesktopViewport.value
        ? 'text-[0.56rem] font-medium uppercase leading-none tracking-[0.08em] text-[#6b7280]'
        : 'text-[0.6rem] font-medium uppercase leading-none tracking-[0.08em] text-[#6b7280]',
);

const examLikeScopeValueClass = computed(() =>
    isCompactExamLikeDesktopViewport.value
        ? 'mt-0.5 truncate text-[0.8rem] leading-tight font-medium text-[#374151]'
        : 'mt-0.5 truncate text-[0.88rem] leading-tight font-medium text-[#374151]',
);

const mediaFrameClass = computed(() =>
    isExamLikeShell.value && isPhoneViewport.value && shouldShowExplanationCard.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[10.75rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[10.75rem] sm:min-h-[19rem]'
        : isExamLikeShell.value && isPhoneViewport.value && hasVideoQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[10.25rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[10.25rem] sm:min-h-[19rem]'
        : isExamLikeShell.value && isPhoneViewport.value && !hasVideoQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[11.25rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[11.25rem] sm:min-h-[19rem]'
        : isPhoneViewport.value && !hasQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[8.5rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[8.5rem] sm:min-h-[19rem]'
        : isPhoneViewport.value && isLongPromptOnPhone.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[13.5rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[13.5rem] sm:min-h-[19rem]'
        : isPhoneViewport.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[13rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
            : 'min-h-[13rem] sm:min-h-[19rem]'
        : isCompactSessionViewport.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[14rem] sm:min-h-[16rem] xl:h-full xl:min-h-0'
            : 'min-h-[14rem] sm:min-h-[16rem]'
        : usePinnedDesktopShell.value
        ? 'min-h-[17rem] sm:min-h-[19rem] xl:h-full xl:min-h-0'
        : 'min-h-[17rem] sm:min-h-[19rem]',
);

const mediaViewportClass = computed(() =>
    isExamLikeShell.value && isPhoneViewport.value && shouldShowExplanationCard.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[10rem] sm:min-h-[18rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[10rem] sm:min-h-[18rem] xl:flex xl:items-center xl:justify-center'
        : isExamLikeShell.value && isPhoneViewport.value && hasVideoQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[10.5rem] sm:min-h-[18rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[10.5rem] sm:min-h-[18rem] xl:flex xl:items-center xl:justify-center'
        : isExamLikeShell.value && isPhoneViewport.value && !hasVideoQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[12rem] sm:min-h-[18rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[12rem] sm:min-h-[18rem] xl:flex xl:items-center xl:justify-center'
        : isExamLikeShell.value && !hasQuestionMedia.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[5rem] sm:min-h-[18rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[5rem] sm:min-h-[18rem] xl:flex xl:items-center xl:justify-center'
        : isExamLikeShell.value && isTightLaptopViewport.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[13.75rem] sm:min-h-[14.5rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[13.75rem] sm:min-h-[14.5rem] xl:flex xl:items-center xl:justify-center'
        : isExamLikeShell.value
        ? usePinnedDesktopShell.value
            ? 'min-h-[16rem] sm:min-h-[18rem] xl:flex xl:h-full xl:min-h-0 xl:items-start xl:justify-center'
            : 'min-h-[16rem] sm:min-h-[18rem] xl:flex xl:items-center xl:justify-center'
        : isPhoneViewport.value && !hasQuestionMedia.value
        ? 'min-h-[5rem] sm:min-h-[18.75rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center'
        : isTightLaptopViewport.value
        ? 'min-h-[13.75rem] sm:min-h-[14.5rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center'
        : isPhoneViewport.value && isLongPromptOnPhone.value
        ? 'min-h-[10.75rem] sm:min-h-[18.75rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center'
        : isPhoneViewport.value
        ? 'min-h-[12rem] sm:min-h-[18.75rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center'
        : isCompactSessionViewport.value
        ? 'min-h-[14.75rem] sm:min-h-[15.75rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center'
        : 'min-h-[16.75rem] sm:min-h-[18.75rem] xl:flex xl:h-full xl:min-h-0 xl:items-center xl:justify-center',
);

const mediaStageClass = computed(() =>
    useEdgeToEdgeSessionMedia.value
        ? 'mx-auto flex w-full items-center justify-center overflow-hidden rounded-none bg-white'
        : isExamLikeShell.value
        ? hasVideoQuestionMedia.value
            ? 'mx-auto flex w-full items-center justify-center overflow-hidden rounded-none bg-transparent'
            : 'mx-auto flex w-full items-center justify-center overflow-hidden rounded-none border border-[#e5e7eb] bg-[#fafafa] shadow-[inset_0_1px_0_rgba(255,255,255,0.75)]'
        : isCompactSessionViewport.value
        ? 'mx-auto flex w-full items-center justify-center overflow-hidden bg-white'
        : 'mx-auto flex w-full items-center justify-center overflow-hidden bg-white',
);

const imagePresentationClass = computed(() =>
    isCompactSessionViewport.value
        ? 'mx-auto flex h-full w-full items-center justify-center'
        : 'mx-auto flex h-full w-full items-center justify-center',
);

const videoPresentationClass = computed(() =>
    isCompactSessionViewport.value
        ? 'relative mx-auto flex h-full min-h-0 w-full items-center justify-center'
        : 'relative mx-auto flex h-full min-h-0 w-full items-center justify-center',
);

const mediaWorkspacePaneClass = computed(() =>
    usePinnedDesktopShell.value
        ? 'relative min-h-0 overflow-hidden bg-transparent'
        : 'relative bg-transparent',
);

const mediaStageSectionClass = computed(() =>
    useEdgeToEdgeSessionMedia.value
        ? 'flex min-h-0 flex-col -mx-3'
        : usePinnedDesktopShell.value
        ? 'flex h-full min-h-0 flex-col'
        : 'flex min-h-0 flex-col',
);

const sessionMediaChromeClass = computed(() =>
    isExamLikeShell.value
        ? examLikeMediaChromePaddingClass.value
        : useEdgeToEdgeSessionMedia.value
        ? 'p-0'
        : 'p-3 sm:p-5',
);

const examLikeMediaChromePaddingClass = computed(() =>
    useEdgeToEdgeSessionMedia.value
        ? 'px-0 pt-0 pb-0'
        : isCompactExamLikeDesktopViewport.value
        ? 'px-3.5 pt-2.5 pb-1.5 xl:px-3 xl:pt-2.5 xl:pb-1.5'
        : 'px-4 pt-4 pb-2 xl:px-3.5 xl:pt-3.5 xl:pb-1.5',
);

const previewStageClass = computed(() =>
    usePinnedDesktopShell.value
        ? 'flex h-full flex-col items-center justify-center gap-5 px-8 text-center'
        : 'flex flex-col items-center justify-center gap-5 px-8 py-12 text-center',
);

const scaledDesktopMediaFrameStyle = (
    targetWidth: number,
    targetHeight: number,
): Record<string, string> => {
    const viewportOffset = reservesExplanationSpace.value
        ? (isCompactExamLikeDesktopViewport.value ? 333 : 355)
        : (isCompactExamLikeDesktopViewport.value ? 317 : 339);
    const availableHeight = viewportHeight.value > 0
        ? Math.max(viewportHeight.value - viewportOffset, 260)
        : targetHeight;
    const scale = Math.min(1, availableHeight / targetHeight);

    return {
        width: `min(${Math.round(targetWidth * scale)}px, 100%)`,
        maxWidth: '100%',
        maxHeight: `${Math.round(targetHeight * scale)}px`,
        aspectRatio: `${targetWidth} / ${targetHeight}`,
    };
};

const createResponsiveMediaFrameStyle = (
    targetWidth: number,
    targetHeight: number,
    availableHeightOffset: number,
): Record<string, string> => {
    const availableHeight = viewportHeight.value > 0
        ? Math.max(viewportHeight.value - availableHeightOffset, 260)
        : targetHeight;
    const scale = Math.min(1, availableHeight / targetHeight);

    return {
        width: `min(${Math.round(targetWidth * scale)}px, 100%)`,
        maxWidth: '100%',
        maxHeight: `${Math.round(targetHeight * scale)}px`,
        aspectRatio: `${targetWidth} / ${targetHeight}`,
    };
};

const mediaFrameStyle = computed<Record<string, string>>(() => {
    if (viewportHeight.value <= 0) {
        return {
            width: '100%',
            height: 'auto',
            maxWidth: '100%',
            maxHeight: '100%',
            aspectRatio: '885 / 500',
        };
    }

    if (viewportWidth.value >= 768 && viewportWidth.value < 1280) {
        if (viewportWidth.value >= 1024 && viewportHeight.value > 0 && viewportHeight.value < 800) {
            return createResponsiveMediaFrameStyle(700, 392, 338);
        }

        if (reservesExplanationSpace.value) {
            return createResponsiveMediaFrameStyle(675, 380, 355);
        }

        return createResponsiveMediaFrameStyle(760, 429, 339);
    }

    if (viewportWidth.value < 768) {
        return {
            width: '100%',
            height: 'auto',
            maxWidth: '100%',
            maxHeight: '100%',
            aspectRatio: '885 / 500',
        };
    }

    if (usePinnedDesktopShell.value && viewportWidth.value >= 1600 && viewportHeight.value >= 980) {
        return hasVideoQuestionMedia.value
            ? scaledDesktopMediaFrameStyle(1085, 610)
            : scaledDesktopMediaFrameStyle(1085, 700);
    }

    if (usePinnedDesktopShell.value && isExplanationCompactViewport.value) {
        return scaledDesktopMediaFrameStyle(744, 420);
    }

    return scaledDesktopMediaFrameStyle(885, 500);
});

const imageAssetClass = computed(() =>
    isExamLikeShell.value
        ? 'mx-auto block h-full w-full max-h-full max-w-full object-contain object-top bg-white'
        : isCompactSessionViewport.value
        ? 'mx-auto block h-full w-full max-h-full max-w-full object-contain bg-white'
        : 'mx-auto block h-full w-full max-h-full max-w-full object-contain bg-white',
);

const videoAssetClass = computed(() =>
    isExamLikeShell.value
        ? isPhoneViewport.value
            ? 'mx-auto block h-full w-full max-h-full max-w-full object-contain bg-white'
            : 'mx-auto block h-full w-full max-h-full max-w-full object-contain object-top bg-white'
        : isCompactSessionViewport.value
        ? 'mx-auto block h-full w-full max-h-full max-w-full object-contain bg-white'
        : 'mx-auto block h-full w-full max-h-full max-w-full object-contain bg-white',
);

const promptContainerClass = computed(() =>
    isExamLikeShell.value
        ? isPhoneViewport.value && shouldShowExplanationCard.value
            ? 'relative min-h-0 border-t border-[#eceff3] bg-white px-3 py-2 xl:px-4 xl:py-4'
            : isPhoneViewport.value
            ? 'relative min-h-0 border-t border-[#eceff3] bg-white px-3 py-2.5 xl:px-4 xl:py-4'
            : 'relative min-h-0 border-t border-[#eceff3] bg-white px-4 pt-2.5 pb-4 xl:px-4 xl:pt-2.5 xl:pb-4'
        : isPhoneViewport.value
        ? useFlatPhoneFocusWorkspace.value
            ? 'relative min-h-0 border-t border-[#eceff3] bg-white px-3 py-2.5'
            : reservesExplanationSpace.value
            ? 'relative min-h-0 bg-white/56 px-3 py-1.5 shadow-[0_10px_24px_rgba(36,33,28,0.028)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out'
            : 'relative min-h-0 bg-white/56 px-3 py-2 shadow-[0_10px_24px_rgba(36,33,28,0.028)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out'
        : shouldShowExplanationCard.value && reservesExplanationSpace.value && isCompactSessionViewport.value
        ? 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 pt-0 pb-1.5 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[8rem] max-h-[8rem] sm:h-[8.25rem] sm:max-h-[8.25rem] xl:h-[8rem] xl:max-h-[8rem]'
        : shouldShowExplanationCard.value && reservesExplanationSpace.value
        ? 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 pt-0 pb-2 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[8.75rem] max-h-[8.75rem] sm:h-[9rem] sm:max-h-[9rem] xl:h-[8.75rem] xl:max-h-[8.75rem]'
        : reservesExplanationSpace.value && isCompactSessionViewport.value
        ? 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 py-1.5 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[8rem] max-h-[8rem] sm:h-[8.25rem] sm:max-h-[8.25rem] xl:h-[8rem] xl:max-h-[8rem]'
        : reservesExplanationSpace.value
        ? 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 py-2 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[8.75rem] max-h-[8.75rem] sm:h-[9rem] sm:max-h-[9rem] xl:h-[8.75rem] xl:max-h-[8.75rem]'
        : isCompactSessionViewport.value
        ? 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 py-1.5 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[7.25rem] max-h-[7.25rem] sm:h-[7.5rem] sm:max-h-[7.5rem] xl:h-[7.25rem] xl:max-h-[7.25rem]'
        : 'relative flex min-h-0 items-start overflow-hidden bg-white/56 px-3 py-2 shadow-[0_12px_28px_rgba(36,33,28,0.032)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out h-[8rem] max-h-[8rem] sm:h-[8.25rem] sm:max-h-[8.25rem] xl:h-[8rem] xl:max-h-[8rem]',
);

const promptStackClass = computed(() => 'flex h-full min-h-0 flex-col justify-start');

const promptFrameStyle = computed<Record<string, string>>(() => {
    if (!isExamLikeShell.value && !isPhoneViewport.value && hasQuestionMedia.value) {
        return {
            width: mediaFrameStyle.value.width ?? '100%',
            maxWidth: mediaFrameStyle.value.maxWidth ?? '100%',
        };
    }

    if (isPhoneViewport.value) {
        if (useFlatPhoneFocusWorkspace.value) {
            return {
                width: '100%',
                maxWidth: '100%',
            };
        }

        if (viewportWidth.value > 434) {
            if (reservesExplanationSpace.value) {
                return {
                    width: 'min(675px, 100%)',
                    maxWidth: '100%',
                };
            }

            return {
                width: 'min(760px, 100%)',
                maxWidth: '100%',
            };
        }

        return {
            width: '100%',
            maxWidth: '23rem',
        };
    }

    if (viewportWidth.value >= 768 && viewportWidth.value < 1280) {
        if (viewportWidth.value >= 1024 && viewportHeight.value > 0 && viewportHeight.value < 800) {
            return {
                width: 'min(700px, 100%)',
                maxWidth: '100%',
            };
        }

        if (reservesExplanationSpace.value) {
            return {
                width: 'min(675px, 100%)',
                maxWidth: '100%',
            };
        }

        return {
            width: 'min(760px, 100%)',
            maxWidth: '100%',
        };
    }

    if (viewportWidth.value >= 1600 && viewportHeight.value >= 980) {
        return {
            width: '100%',
            maxWidth: '1085px',
        };
    }

    if (isExplanationCompactViewport.value) {
        return {
            width: '100%',
            maxWidth: '744px',
        };
    }

    return {
        width: '100%',
        maxWidth: '885px',
    };
});

const promptScrollClass = computed(() => {
    if (isPhoneViewport.value) {
        return 'mx-auto w-full overflow-visible pr-1';
    }

    if (isCompactSessionViewport.value) {
        return 'mx-auto min-h-0 w-full flex-1 overflow-hidden pr-1';
    }

    return 'mx-auto min-h-0 w-full flex-1 overflow-hidden pr-1';
});

const answerButtonClass = computed(() => {
    const isCompact = isCompactSessionViewport.value;
    const isBooleanQuestion = activeQuestion.value?.question_type === 'boolean';

    if (isBooleanQuestion) {
        return isCompact
            ? 'min-h-[3.4rem] px-4 py-2.5 text-[1.08rem] leading-6 sm:text-[1.16rem]'
            : 'min-h-[4rem] px-4 py-3 text-[1.12rem] leading-6 sm:text-[1.2rem]';
    }

    return isCompact
        ? 'min-h-[3.3rem] px-4 py-2.5 text-[1.02rem] leading-6 sm:text-[1.08rem]'
        : 'min-h-[3.9rem] px-4 py-3 text-[1.05rem] leading-6 sm:text-[1.12rem]';
});

const useCompactAnswerActions = computed(
    () => useCompactMobileDockActions.value || isTightExamLikeLaptopViewport.value,
);

const examLikeBooleanAnswerButtonClass = computed(() =>
    isTightExamLikeLaptopViewport.value
        ? 'flex min-h-[3.25rem] w-full items-center justify-center rounded-none border text-center text-[0.94rem] font-semibold uppercase tracking-[0.05em] sm:min-h-[3.5rem] sm:text-[0.98rem]'
        : 'flex min-h-[3.75rem] w-full items-center justify-center rounded-none border text-center text-[0.96rem] font-semibold uppercase tracking-[0.05em] sm:min-h-[4rem] sm:text-[1rem]',
);

const examLikeBooleanAnswerTextClass = computed(() =>
    isTightExamLikeLaptopViewport.value
        ? 'px-3 py-2.5 sm:py-3'
        : 'px-3 py-3.5 sm:py-4',
);

const shouldPinMobileAnswerPanel = computed(
    () => isPhoneViewport.value && !localSessionCompleted.value,
);

const useCompactMobileDockActions = computed(
    () =>
        (isExamLikeShell.value || useFlatPhoneFocusWorkspace.value)
        && isNarrowPhoneViewport.value
        && shouldPinMobileAnswerPanel.value,
);

const mobileAnswerDockWrapperClass = computed(() => {
    if (shouldPinMobileAnswerPanel.value) {
        if (isExamLikeShell.value) {
            return useCompactMobileDockActions.value
                ? 'fixed inset-x-0 bottom-0 z-40 border-t border-[#dfe4ea] bg-white/98 px-3 pt-2 shadow-[0_-12px_28px_rgba(15,23,42,0.07)] backdrop-blur-[10px]'
                : 'fixed inset-x-0 bottom-0 z-40 border-t border-[#dfe4ea] bg-white/98 px-3 pt-3 shadow-[0_-18px_36px_rgba(15,23,42,0.08)] backdrop-blur-[10px]';
        }

        if (useFlatPhoneFocusWorkspace.value) {
            return 'fixed inset-x-0 bottom-0 z-40 border-t border-[#dfe4ea] bg-white/98 px-3 pt-2 shadow-[0_-12px_28px_rgba(15,23,42,0.06)] backdrop-blur-[10px]';
        }

        return 'fixed inset-x-0 bottom-0 z-40 border-t border-[#e5e7eb] bg-white px-3 pt-3';
    }

    return isExamLikeShell.value
        ? 'px-0 pb-0'
        : 'px-1 pb-1.5 sm:px-2 sm:pb-2';
});

const mobileAnswerDockWrapperStyle = computed<Record<string, string>>((): Record<string, string> => {
    if (!shouldPinMobileAnswerPanel.value) {
        return {};
    }

    return {
        paddingBottom: `calc(env(safe-area-inset-bottom, 0px) + ${useCompactMobileDockActions.value ? '0.5rem' : '0.75rem'})`,
    };
});

const answerSectionClass = computed(() => {
    if (shouldPinMobileAnswerPanel.value) {
        return useCompactMobileDockActions.value
            ? 'mx-auto max-w-[30rem] min-h-0 space-y-2'
            : 'mx-auto max-w-[30rem] min-h-0 space-y-3';
    }

    return isExamLikeShell.value
        ? isTightExamLikeLaptopViewport.value
            ? 'relative z-10 min-h-0 space-y-2 border-t border-[#eceff3] bg-white px-4 pt-3 pb-2.5'
            : 'relative z-10 min-h-0 space-y-3 border-t border-[#eceff3] bg-white px-4 py-4 xl:px-4 xl:py-4'
        : isCompactSessionViewport.value
        ? 'relative z-10 min-h-0 space-y-2 bg-white/48 px-2.5 pt-2.5 pb-2 shadow-[0_10px_24px_rgba(36,33,28,0.022)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out xl:h-[9.25rem] xl:overflow-hidden'
        : 'relative z-10 min-h-0 space-y-3 bg-white/48 px-3 pt-3 pb-2.5 shadow-[0_10px_24px_rgba(36,33,28,0.022)] backdrop-blur-[3px] transition-[background-color,box-shadow,transform] duration-300 ease-out xl:h-[9.5rem] xl:overflow-hidden';
});

const activeQuestionSectionClass = computed(() =>
    'space-y-3 sm:space-y-4 xl:space-y-3',
);

const activeQuestionShellClass = computed(() =>
    isExamLikeShell.value
        ? 'max-w-[79rem] min-[1024px]:grid min-[1024px]:grid-cols-[minmax(0,1fr)_16rem] min-[1024px]:items-start min-[1024px]:gap-4 xl:grid-cols-[minmax(0,1fr)_18rem] xl:gap-5'
        : 'max-w-[78rem] space-y-3 xl:space-y-2.5',
);

const activeQuestionInnerClass = computed(() =>
    isExamLikeShell.value
        ? 'space-y-3 min-[1024px]:space-y-2 xl:space-y-2'
        : '',
);

const promptOuterClass = computed(() =>
    isExamLikeShell.value
        ? 'px-0'
        : useFlatPhoneFocusWorkspace.value
        ? 'px-0'
        : ['px-1 sm:px-2', shouldShowExplanationCard.value ? 'pt-0' : reservesExplanationSpace.value ? 'pt-1' : 'pt-0.5'],
);

const questionSourceBadgeClass = computed(() =>
    useFlatPhoneFocusWorkspace.value
        ? 'hidden shrink-0 items-center gap-1.5 text-xs font-medium uppercase tracking-[0.08em] text-[#6b7280] min-[480px]:inline-flex'
        : 'inline-flex shrink-0 items-center gap-1.5 text-xs font-medium uppercase tracking-[0.08em] text-[#6b7280]',
);

const useFlatPhoneCompletionSummary = computed(
    () =>
        (isExamLikeShell.value || isZenLearningShell.value || isReviewTrainerLearningShell.value)
        && isNarrowPhoneViewport.value
        && localSessionCompleted.value,
);
const useNativeMobileSessionResult = computed(
    () =>
        useFlatPhoneCompletionSummary.value
        && props.session.mode === 'learn'
        && !isPublicDemoMode.value
        && !isPjmMode.value
        && !isReviewTrainerMode.value,
);
watch(useNativeMobileSessionResult, (isNativeMobileResult) => {
    if (isNativeMobileResult) {
        showCorrectCompletionResults.value = false;
    }
});
const completionShellClass = computed(() =>
    useNativeMobileSessionResult.value
        ? 'mx-auto w-full max-w-none space-y-4 pb-[calc(4.75rem+env(safe-area-inset-bottom))]'
        : useFlatPhoneCompletionSummary.value
        ? 'mx-auto w-full max-w-none space-y-4'
        : 'mx-auto max-w-[96rem] space-y-6 sm:space-y-7 2xl:max-w-[102rem]',
);
const showCompletionProgressPanel = computed(() =>
    props.session.mode === 'learn'
    && localSessionCompleted.value
    && !useFlatPhoneCompletionSummary.value
    && sessionRoadmap.value.length > 0,
);
const completionLayoutClass = computed(() =>
    showCompletionProgressPanel.value
        ? 'grid gap-5 xl:grid-cols-[20rem_minmax(0,1fr)] 2xl:grid-cols-[21rem_minmax(0,1fr)]'
        : 'space-y-5',
);
const completionMainColumnClass = computed(() =>
    showCompletionProgressPanel.value ? 'min-w-0 space-y-5' : 'space-y-5',
);
const completionSummaryCardClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'overflow-hidden bg-white'
        : 'overflow-hidden border border-[#dce3ec] bg-white shadow-[0_16px_38px_rgba(15,23,42,0.035)]',
);
const completionSummaryCardBodyClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'px-0 py-4'
        : 'px-5 py-5 sm:px-7 lg:px-8',
);
const completionQuickNavClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'grid gap-2'
        : 'grid gap-3 sm:grid-cols-2 xl:grid-cols-3',
);
const completionQuickNavButtonClass = computed(() =>
    'inline-flex min-h-[3.15rem] items-center justify-center gap-2.5 border border-[#dbe3ec] bg-white px-4 py-3 text-center text-[0.88rem] font-semibold leading-tight text-[#334155] transition hover:border-[#b8c5d6] hover:bg-[#f8fafc] disabled:cursor-not-allowed disabled:text-neutral-400',
);
const completionQuickNavPrimaryButtonClass = computed(() =>
    'inline-flex min-h-[3.15rem] items-center justify-center gap-2.5 border border-[#0b5cff] bg-[#0b5cff] px-4 py-3 text-center text-[0.88rem] font-semibold leading-tight text-white shadow-[0_14px_34px_rgba(11,92,255,0.18)] transition hover:border-[#0646c8] hover:bg-[#0646c8] disabled:cursor-not-allowed disabled:border-[#d1d5db] disabled:bg-[#d1d5db]',
);
const completionQuickNavDarkButtonClass = computed(() =>
    'inline-flex min-h-[3.15rem] items-center justify-center gap-2.5 border border-[#111827] bg-[#111827] px-4 py-3 text-center text-[0.88rem] font-semibold leading-tight text-white transition hover:border-black hover:bg-black',
);
const completionReviewSectionClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'mx-auto w-full max-w-none bg-white'
        : 'space-y-4 pt-3',
);
const completionReviewHeaderClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'border-t border-[#e5e7eb] pt-4 pb-3'
        : 'px-0 py-0',
);
const completionReviewBodyClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'py-0'
        : 'px-0 py-0',
);
const completionReviewListClass = computed(() =>
    useNativeMobileSessionResult.value
        ? 'border-b border-[#e4e7ec]'
        : useFlatPhoneCompletionSummary.value
        ? 'space-y-0'
        : 'space-y-5',
);
const completionReviewArticleClass = computed(() =>
    useNativeMobileSessionResult.value
        ? 'grid gap-3 bg-white pb-5'
        : useFlatPhoneCompletionSummary.value
        ? 'grid gap-3 border-t border-[#e5e7eb] bg-white py-4'
        : 'grid gap-5 border border-[#e5e7eb] bg-white px-4 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] md:px-5 md:py-5 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,0.42fr)]',
);
const completionReviewMediaFrameClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'flex min-h-[9.5rem] items-center justify-center bg-white py-2'
        : 'flex min-h-[13rem] items-center justify-center bg-white px-3 py-3 md:min-h-[17rem] md:px-4 md:py-4',
);
const completionReviewMediaAssetClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'max-h-[10.5rem] w-full object-contain'
        : 'max-h-[12.5rem] w-full object-contain md:max-h-[16rem]',
);
const completionReviewContentClass = computed(() =>
    useFlatPhoneCompletionSummary.value ? 'flex flex-col gap-3' : 'flex flex-col gap-4',
);
const completionReviewPromptClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'mt-2 text-[0.98rem] font-semibold leading-6 text-[#161414]'
        : 'mt-2 text-base font-semibold leading-7 text-[#161414] sm:text-[1.02rem]',
);
const completionReviewAnswerGridClass = computed(() =>
    useFlatPhoneCompletionSummary.value ? 'grid gap-2.5' : 'grid gap-3 md:grid-cols-2',
);
const completionReviewAnswerCardBaseClass = computed(() =>
    useFlatPhoneCompletionSummary.value ? 'border px-3 py-3' : 'border px-4 py-3',
);
const completionReviewNeutralAnswerCardClass = computed(() =>
    useFlatPhoneCompletionSummary.value
        ? 'border border-[#e5e7eb] bg-white px-3 py-3 text-neutral-950'
        : 'border border-[#e5e7eb] bg-white px-4 py-3 text-neutral-950',
);
const completionMetricsGridClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'grid grid-cols-2 gap-2'
        : 'grid grid-cols-2 gap-y-4 sm:grid-cols-3 lg:grid-cols-6 lg:divide-x lg:divide-[#e5eaf0]',
);
const completionMetricCardClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'border border-[#e5e7eb] bg-[#fafafa] px-2.5 py-2 text-center'
        : 'min-w-0 px-3 py-1 text-left sm:px-4 lg:px-5',
);
const completionMetricHighlightCardClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? completionMetricCardClass.value
        : completionMetricCardClass.value,
);
const completionMetricHighlightLabelClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]'
        : 'text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]',
);
const completionMetricHighlightValueClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-1 text-[0.96rem] font-semibold tracking-tight text-[#161414] tabular-nums'
        : 'mt-1 text-[1.05rem] font-semibold tracking-tight text-[#161414] tabular-nums',
);
const reviewCompletionSummaryClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'grid gap-4 border-t border-[#e5e7eb] pt-4'
        : 'grid gap-5 border-t border-[#e5e7eb] pt-5 lg:grid-cols-[minmax(0,1fr)_minmax(24rem,0.95fr)] lg:items-start',
);
const reviewCompletionHeadingClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-1 text-[1.18rem] font-semibold tracking-tight text-[#161414]'
        : 'mt-2 text-[1.35rem] font-semibold tracking-tight text-[#161414] sm:text-[1.55rem]',
);
const reviewCompletionCoachMessageClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-2 text-[0.95rem] leading-6 text-[#4b5563]'
        : 'mt-3 text-[0.98rem] leading-7 text-[#4b5563]',
);
const reviewCompletionCountTextClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-2 text-[0.84rem] leading-5 text-[#5b6675]'
        : 'mt-3 text-[0.9rem] leading-6 text-[#5b6675]',
);
const reviewCompletionNextStepClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-3 border-l-2 border-[#0057a3] bg-[#f8fbff] px-3 py-3'
        : 'mt-4 border border-[#d9e8f7] bg-[#f3f8fd] px-4 py-3',
);
const reviewCompletionToggleButtonClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-3 inline-flex w-full items-center justify-center rounded-none border border-[#d1d5db] bg-white px-4 py-2.5 text-[0.84rem] font-semibold text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#f8f8f8] hover:text-[#161414]'
        : 'mt-4 inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-4 py-2.5 text-[0.84rem] font-semibold text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#f8f8f8] hover:text-[#161414]',
);
const reviewCompletionItemsGridClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'grid grid-cols-2 gap-2'
        : 'grid gap-2 sm:grid-cols-3 lg:grid-cols-1',
);
const reviewCompletionItemCardClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'bg-[#fafafa] px-3 py-2.5'
        : 'border border-[#e5e7eb] bg-[#fafafa] px-4 py-3',
);
const reviewCompletionItemDescriptionClass = computed(() =>
    useFlatPhoneCompletionSummary.value && isReviewTrainerMode.value
        ? 'mt-1 text-[0.75rem] leading-4 text-[#5b6675]'
        : 'mt-1 text-[0.82rem] leading-5 text-[#5b6675]',
);

const mobileAnswerDockSpacerStyle = computed<Record<string, string>>((): Record<string, string> => {
    if (!shouldPinMobileAnswerPanel.value || mobileAnswerDockHeight.value <= 0) {
        return {};
    }

    return {
        height: `${mobileAnswerDockHeight.value + 12}px`,
    };
});

const explanationCalloutClass = computed(() =>
    hasExplanationCallout.value
        ? 'relative mx-auto flex w-full min-w-0 flex-col'
        : '',
);

const formatMode = (mode: string) =>
    ({
        exam: 'Egzamin teoretyczny',
        learn: isExamLikeShell.value ? 'Nauka klasyczna' : 'Panel do nauki',
        review: 'Powtorka',
        sr_review: 'Trener pamięci',
        hard: 'Trudne pytania',
        quick: 'Szybka seria',
    })[mode] ?? mode;

const formatStatus = (status: string) =>
    ({
        in_progress: 'W toku',
        completed: 'Zakonczona',
    })[status] ?? status;

const classicFeedbackModeLabel = computed(() =>
    ({
        instant: 'Szybki tryb (dobrze/źle)',
        instant_explanation: 'Z wyjaśnieniem',
        review: 'Wynik',
    })[feedbackMode.value],
);

const classicAdvanceModeLabel = computed(() =>
    autoAdvance.value ? 'Automatycznie' : 'Sam klikam dalej',
);

const classicMediaModeLabel = computed(() => {
    if (autoJumpToVideoEnding.value) {
        return 'Pokaż końcówkę filmu';
    }

    if (autoPlayVideos.value) {
        return `Auto · ${videoPlaybackRate.value}x`;
    }

    return 'Start ręczny';
});

const classicQuestionAudioModeLabel = computed(() => {
    if (!showQuestionAudioControl.value) {
        return autoPlayCorrectAnswerAudio.value ? 'Odpowiedź po błędzie' : 'Wyłączone';
    }

    if (autoPlayQuestionAudio.value && autoPlayCorrectAnswerAudio.value) {
        return 'Auto + odpowiedź';
    }

    if (autoPlayCorrectAnswerAudio.value) {
        return 'Odpowiedź po błędzie';
    }

    return autoPlayQuestionAudio.value ? 'Audio automatyczne' : 'Przycisk odsłuchu';
});

const classicInlineFormattingLabel = computed(() => {
    if (enableInlineBold.value && enableInlineColors.value) {
        return 'Kolory + pogrubienie';
    }

    if (enableInlineBold.value) {
        return 'Tylko pogrubienie';
    }

    if (enableInlineColors.value) {
        return 'Tylko kolory';
    }

    return 'Wyłączone';
});

const classicAnnotationsLabel = computed(() =>
    showVisualAnnotations.value ? 'Widoczne' : 'Ukryte',
);

const showMobileSessionMenuTrigger = computed(
    () => isPhoneViewport.value && !localSessionCompleted.value,
);

const topicHandleClass = computed(() =>
    'fixed z-30 -translate-y-1/2 px-3 py-4 text-[0.68rem] font-semibold uppercase tracking-[0.18em] transition sm:px-3 sm:py-4 md:px-3.5 md:py-4.5 xl:px-3.5 xl:py-4.5 xl:text-[0.72rem]'
    + (
        isExamLikeShell.value
            ? ' border border-[#d9e2ec] bg-white text-[#4b5563] shadow-[0_10px_24px_rgba(15,23,42,0.08)] backdrop-blur-[4px] hover:bg-[#f8fafc] hover:text-[#161414]'
            : ' border border-l-0 border-[#e5e7eb] bg-white text-[#4b5563] shadow-[0_8px_20px_rgba(15,23,42,0.06)] hover:bg-[#fafafa] hover:text-[#161414]'
    ),
);

const settingsHandleClass = computed(() =>
    'fixed z-30 -translate-y-1/2 px-3 py-4 text-[0.68rem] font-semibold uppercase tracking-[0.18em] transition sm:px-3 sm:py-4 md:px-3.5 md:py-4.5 xl:px-3.5 xl:py-4.5 xl:text-[0.72rem]'
    + (
        isExamLikeShell.value
            ? ' border border-[#d9e2ec] bg-white text-[#4b5563] shadow-[0_10px_24px_rgba(15,23,42,0.08)] backdrop-blur-[4px] hover:bg-[#f8fafc] hover:text-[#161414]'
            : ' border border-r-0 border-[#e5e7eb] bg-white text-[#4b5563] shadow-[0_8px_20px_rgba(15,23,42,0.06)] hover:bg-[#fafafa] hover:text-[#161414]'
    ),
);

const mobileSessionMenuSheetClass = computed(() =>
    isExamLikeShell.value
        ? 'fixed inset-x-0 bottom-0 z-50 border-t border-[#d9e2ec] bg-white px-4 pt-4 shadow-[0_-18px_44px_rgba(15,23,42,0.12)] min-[1024px]:hidden'
        : 'fixed inset-x-0 bottom-0 z-50 border border-b-0 border-[#e5e7eb] bg-white px-4 pt-4 shadow-[0_-14px_36px_rgba(15,23,42,0.08)] min-[1024px]:hidden',
);

const mobileSessionMenuActionButtonClass = computed(() =>
    isExamLikeShell.value
        ? 'w-full rounded-none border border-[#d1d5db] bg-[#f3f4f6] px-3 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#c7cfd8] hover:bg-[#e5e7eb]'
        : 'w-full rounded-none border border-[#e5e7eb] bg-white px-3 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#d1d5db] hover:bg-[#fafafa]',
);

const sidePanelSurfaceClass = computed(() =>
    isExamLikeShell.value
        ? 'fixed inset-y-0 z-50 flex flex-col bg-white shadow-[18px_0_40px_rgba(15,23,42,0.08)]'
        : 'fixed inset-y-0 z-50 flex flex-col bg-[#fbfaf7] shadow-[16px_0_36px_rgba(36,33,28,0.07)]',
);

const topicPickerPanelClass = computed(() =>
    `${sidePanelSurfaceClass.value} left-0 w-[min(28rem,calc(100vw-2rem))] ${
        isExamLikeShell.value
            ? 'border-r border-[#e5e7eb]'
            : 'border-r border-[#ece8df]'
    }`,
);

const settingsPanelClass = computed(() =>
    `${sidePanelSurfaceClass.value} right-0 w-[min(28rem,calc(100vw-2rem))] ${
        isExamLikeShell.value
            ? 'border-l border-[#e5e7eb]'
            : 'border-l border-[#ece8df]'
    }`,
);

const sidePanelHeaderClass = computed(() =>
    isExamLikeShell.value
        ? 'flex items-start justify-between gap-4 border-b border-[#e5e7eb] px-5 py-5'
        : 'flex items-start justify-between gap-4 border-b border-[#ece8df] bg-[#fcfbf8] px-6 py-6',
);

const sidePanelEyebrowClass = computed(() =>
    isExamLikeShell.value
        ? 'text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]'
        : 'text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#8a7f6d]',
);

const sidePanelSectionTitleClass = computed(() =>
    isExamLikeShell.value
        ? 'text-[0.8rem] font-bold uppercase tracking-[0.13em] text-[#374151]'
        : 'text-[0.8rem] font-bold uppercase tracking-[0.13em] text-[#51483d]',
);

const settingsFeatureSectionTitleClass = computed(() =>
    isExamLikeShell.value
        ? 'text-[0.9rem] font-bold uppercase tracking-[0.12em] text-[#374151]'
        : 'text-[0.9rem] font-bold uppercase tracking-[0.12em] text-[#51483d]',
);

const sidePanelTitleClass = computed(() =>
    isExamLikeShell.value
        ? 'text-lg font-semibold text-[#161414]'
        : 'text-[1.18rem] font-semibold tracking-tight text-[#1f1a16]',
);

const sidePanelDescriptionClass = computed(() =>
    'max-w-sm text-sm leading-6 text-[#4b5563]',
);

const settingsCardContentClass = 'min-w-0 flex-1 space-y-2';
const settingsCardTitleClass = 'block text-[0.95rem] font-semibold leading-6 text-[#161414]';
const settingsCardDescriptionClass = 'block text-sm leading-6 text-[#4b5563]';
const settingsCardIconSize = 28;
const settingsSectionIconSize = 20;

const sidePanelCloseButtonClass = computed(() =>
    isExamLikeShell.value
        ? 'shrink-0 border border-[#d1d5db] bg-white px-3 py-1.5 text-sm font-medium text-[#4b5563] transition hover:border-[#d2b35b] hover:bg-[#fff8df] hover:text-[#463309]'
        : 'shrink-0 border border-[#ddd6c8] bg-white px-3.5 py-1.5 text-sm font-medium text-[#5f584d] transition hover:border-[#cfc6b7] hover:bg-[#f7f4ee] hover:text-[#201d1a]',
);

const sidePanelIconCloseButtonClass =
    'inline-flex h-9 w-9 shrink-0 items-center justify-center bg-transparent text-[#6b7280] transition-colors hover:bg-[#e53935] hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fecaca]';

const sidePanelBodyClass = computed(() =>
    isExamLikeShell.value
        ? 'side-panel-scroll flex-1 space-y-5 overflow-y-auto px-5 py-5 text-sm'
        : 'side-panel-scroll flex-1 space-y-6 overflow-y-auto bg-[#fbfaf7] px-6 py-6 text-sm text-[#2a2621]',
);

const topicPickerBodyClass = computed(() =>
    isExamLikeShell.value
        ? 'side-panel-scroll side-panel-scroll-left flex-1 overflow-y-auto py-5'
        : 'side-panel-scroll side-panel-scroll-left flex-1 overflow-y-auto bg-[#fbfaf7] py-6',
);

const topicPickerBodyInnerClass = computed(() =>
    isExamLikeShell.value
        ? 'side-panel-scroll-inner space-y-5 px-5 text-sm'
        : 'side-panel-scroll-inner space-y-6 px-6 text-sm text-[#2a2621]',
);

const settingsSectionDividerClass = computed(() =>
    isExamLikeShell.value
        ? 'space-y-4 pt-7'
        : 'space-y-4 pt-7',
);

const settingsSelectableCardClass = (isSelected: boolean) =>
    isExamLikeShell.value
        ? isSelected
            ? 'border-[#d2b35b] bg-[#fffaf0] shadow-sm'
            : 'border-[#e5e7eb] bg-white hover:border-[#d1d5db] hover:bg-[#fafafa]'
        : isSelected
            ? 'border-[#161414] bg-white shadow-sm'
            : 'border-[#e5e7eb] bg-white hover:border-[#d1d5db] hover:bg-[#fafafa]';

const settingsSelectionIndicatorClass = (isSelected: boolean) =>
    isExamLikeShell.value
        ? isSelected
            ? 'border-[#d2b35b] bg-[#d2b35b] text-[#463309]'
            : 'border-[#d1d5db] text-transparent'
        : isSelected
            ? 'border-[#161414] bg-[#161414] text-white'
            : 'border-[#cbd5e1] text-transparent';

const settingsStaticCardClass = 'flex items-start gap-3 border border-[#e5e7eb] bg-white px-4 py-3.5';

const settingsInsetCardClass = 'border-t border-[#e5e7eb] bg-[#fafafa] px-4 py-3';

const settingsGroupedCardClass = 'space-y-4 border border-[#e5e7eb] bg-white px-4 py-3.5';

const settingsSegmentButtonClass = (isActive: boolean, isDisabled = false) => {
    if (isDisabled) {
        return isExamLikeShell.value
            ? 'border-[#e5e7eb] bg-[#f7f7f8] text-[#9ca3af] opacity-70'
            : 'border-[#e5e7eb] bg-[#f7f7f8] text-[#9ca3af] opacity-70';
    }

    return isExamLikeShell.value
        ? isActive
            ? 'border-[#1f6fbf] bg-white text-[#16538d] shadow-sm'
            : 'border-[#e5e7eb] bg-white text-[#4b5563] hover:border-[#d1d5db] hover:bg-[#fafafa]'
        : isActive
            ? 'border-[#161414] bg-white text-[#111827] shadow-sm'
            : 'border-[#e5e7eb] bg-white text-[#4b5563] hover:border-[#d1d5db] hover:bg-[#fafafa]';
};

const topicOptionStatus = (topic: GroupOption): SessionRoadmapItem['status'] => {
    if (isTopicCompleted(topic)) {
        return 'completed';
    }

    if (topic.id === activeSessionTopicId.value) {
        return 'current';
    }

    if (topic.id === nextSessionTopic.value?.id) {
        return 'next';
    }

    return 'upcoming';
};

const topicOptionStatusLabel = (topic: GroupOption) => {
    const status = topicOptionStatus(topic);

    if (status === 'completed') {
        return 'Zaliczony';
    }

    if (status === 'current') {
        return 'Teraz';
    }

    if (status === 'next') {
        return 'Dalej';
    }

    return null;
};

const topicOptionTotalQuestionCount = (topic: GroupOption) =>
    questionCountForTopicStatus(topic, 'all');

const topicOptionProgressPercent = (topic: GroupOption) => {
    const totalQuestions = topicOptionTotalQuestionCount(topic);

    return totalQuestions > 0
        ? Math.min(Math.round((topicMasteredQuestionCount(topic) / totalQuestions) * 100), 100)
        : 0;
};

const topicOptionButtonClass = (topic: GroupOption) => {
    const available = currentStatusQuestionCountForTopic(topic) > 0;
    const status = topicOptionStatus(topic);

    if (isExamLikeShell.value) {
        if (!available && status !== 'current') {
            return 'border-[#e5e7eb] bg-[#f7f7f8] text-[#9ca3af] opacity-70';
        }

        if (status === 'completed') {
            return 'border-[#cfe0d2] bg-[#f5faf6] hover:border-[#bdd5c1] hover:bg-[#f2f9f4]';
        }

        if (status === 'current') {
            return 'border-[#d2b35b] bg-[#fffaf0] shadow-sm';
        }

        if (status === 'next') {
            return 'border-[#d8e6f6] bg-[#f5f9ff] hover:border-[#bfd5ee] hover:bg-[#eff6ff]';
        }

        return 'border-[#e5e7eb] bg-white hover:border-[#d1d5db] hover:bg-[#fafafa]';
    }

    if (!available && status !== 'current') {
        return 'border-[#ece8df] bg-[#f8f7f4] text-[#a39b8f] opacity-75';
    }

    if (status === 'completed') {
        return 'border-[#dbe8dd] bg-[#f7fbf7] hover:border-[#cadecf] hover:bg-[#f2f8f2]';
    }

    if (status === 'current') {
        return 'border-[#d8d0c4] bg-[#fcfbf8] shadow-[0_10px_24px_rgba(36,33,28,0.06)]';
    }

    if (status === 'next') {
        return 'border-[#ece8df] bg-[#faf8f3] hover:border-[#ddd6c8] hover:bg-[#f7f4ee]';
    }

    return 'border-[#ece8df] bg-white hover:border-[#ddd6c8] hover:bg-[#fcfbf8]';
};

const topicOptionIndicatorClass = (topic: GroupOption) => {
    const status = topicOptionStatus(topic);

    if (isExamLikeShell.value) {
        if (status === 'completed') {
            return 'border-[#3f9b58] bg-[#f2fbf5] text-[#166534]';
        }

        if (status === 'current') {
            return 'border-[#d2b35b] bg-[#fff9e8] text-[#8a6a00]';
        }

        if (status === 'next') {
            return 'border-[#0071ce] bg-[#f5f9ff] text-[#0057a3]';
        }

        return 'border-[#d1d5db] bg-white text-[#6b7280]';
    }

    if (status === 'completed') {
        return 'border-[#9bb8a0] bg-[#f7fbf7] text-[#56725d]';
    }

    if (status === 'current') {
        return 'border-[#c9bfb0] bg-[#fffdf9] text-[#3f3a33]';
    }

    if (status === 'next') {
        return 'border-[#ddd6c8] bg-[#faf8f3] text-[#6f675c]';
    }

    return 'border-[#e7e1d7] bg-white text-[#9a9285]';
};

const topicOptionIndicatorInnerClass = (topic: GroupOption) => {
    const status = topicOptionStatus(topic);

    if (isExamLikeShell.value) {
        if (status === 'current') {
            return 'bg-[#8a6a00]';
        }

        if (status === 'next') {
            return 'border-[#0071ce]';
        }

        return 'bg-[#cbd5e1]';
    }

    if (status === 'current') {
        return 'bg-[#3f3a33]';
    }

    if (status === 'next') {
        return 'border-[#8b8377]';
    }

    return 'bg-[#d7d1c7]';
};

const topicOptionBadgeClass = (topic: GroupOption) => {
    const status = topicOptionStatus(topic);

    if (isExamLikeShell.value) {
        if (status === 'completed') {
            return 'inline-flex items-center rounded-full bg-[#eaf6ec] px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#166534]';
        }

        if (status === 'current') {
            return 'inline-flex items-center rounded-full bg-[#fff3cc] px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#6b5208]';
        }

        if (status === 'next') {
            return 'inline-flex items-center rounded-full bg-[#eff6ff] px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#1d4ed8]';
        }

        return 'inline-flex items-center rounded-full bg-[#f3f4f6] px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#6b7280]';
    }

    if (status === 'completed') {
        return 'inline-flex items-center border border-[#dbe8dd] bg-[#f7fbf7] px-2.5 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#56725d]';
    }

    if (status === 'current') {
        return 'inline-flex items-center border border-[#d8d0c4] bg-[#fffdf9] px-2.5 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#4f493f]';
    }

    if (status === 'next') {
        return 'inline-flex items-center border border-[#e7dfd3] bg-[#faf7f2] px-2.5 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#6f675c]';
    }

    return 'inline-flex items-center border border-[#ece8df] bg-[#fafafa] px-2.5 py-0.5 text-[0.68rem] font-medium tracking-[0.02em] text-[#7b7468]';
};

const topicOptionProgressTrackClass = computed(() =>
    isExamLikeShell.value ? 'bg-[#e9eef4]' : 'bg-[#efe9df]',
);

const topicOptionProgressFillClass = (topic: GroupOption) => {
    const status = topicOptionStatus(topic);

    if (isExamLikeShell.value) {
        if (status === 'completed') {
            return 'bg-[#3f9b58]';
        }

        if (status === 'current') {
            return 'bg-[#d2b35b]';
        }

        if (status === 'next') {
            return 'bg-[#0071ce]';
        }

        return 'bg-[#cbd5e1]';
    }

    if (status === 'completed') {
        return 'bg-[#8aa08d]';
    }

    if (status === 'current') {
        return 'bg-[#4f493f]';
    }

    if (status === 'next') {
        return 'bg-[#bcae97]';
    }

    return 'bg-[#d8d2c7]';
};

const topicOptionItemPaddingClass = computed(() =>
    isExamLikeShell.value ? 'px-4 py-3.5' : 'px-5 py-4.5',
);

const topicOptionIndicatorSizeClass = computed(() =>
    isExamLikeShell.value
        ? 'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-[0.8rem] font-semibold'
        : 'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-[0.76rem] font-semibold',
);

const topicOptionTitleClass = computed(() =>
    isExamLikeShell.value
        ? 'font-medium leading-6 text-[#161414]'
        : 'font-semibold leading-6 tracking-[0.01em] text-[#2a2621]',
);

const topicOptionMetaClass = computed(() =>
    isExamLikeShell.value
        ? 'text-sm leading-6 text-[#6b7280]'
        : 'text-[0.92rem] leading-6 text-[#746b5f]',
);

const topicOptionCountClass = computed(() =>
    isExamLikeShell.value
        ? 'font-semibold text-[#374151]'
        : 'font-semibold text-[#4f493f]',
);

const topicOptionProgressBarHeightClass = computed(() =>
    isExamLikeShell.value ? 'h-1.5' : 'h-1',
);

const formatResponseTime = (value: number | null) =>
    value === null ? '-' : `${(value / 1000).toFixed(1)} s`;

const resultExplanationText = (result: ResultItem) =>
    normalizeExplanationPlainText(result.explanation);
const renderSessionExplanationHtml = (value: string | null | undefined) =>
    renderExplanationHtml(value, {
        palette: inlineFormattingPalette.value,
        enableBold: enableInlineBold.value,
        enableColors: enableInlineColors.value,
    });
const renderSessionInlineFormattedHtml = (value: string | null | undefined) =>
    renderInlineFormattedHtml(value, {
        palette: inlineFormattingPalette.value,
        enableBold: enableInlineBold.value,
        enableColors: enableInlineColors.value,
    });
const resultExplanationHtml = (result: ResultItem) =>
    renderSessionExplanationHtml(result.explanation);
const resultImageAnnotations = (result: ResultItem) =>
    resolveImageAnnotations(result.explanation_annotations);
const resultVideoFrameAnnotations = (result: ResultItem) =>
    resolveVideoFrameAnnotations(result.explanation_annotations);
const hasResultExplanationAnnotations = (result: ResultItem) =>
    visualExplanationsMode.value !== 'off'
    && showVisualAnnotations.value
    && result.media[0]?.kind === 'image'
    && resultImageAnnotations(result).length > 0;
const hasResultVideoFrameAnnotations = (result: ResultItem) =>
    visualExplanationsMode.value !== 'off'
    && showVisualAnnotations.value
    && result.media[0]?.kind === 'video'
    && resultVideoFrameAnnotations(result).length > 0;
const resultVideoFrameTimeSeconds = (result: ResultItem) =>
    resultVideoFrameAnnotations(result).find((annotation) => annotation.frame_time_seconds !== null)?.frame_time_seconds ?? 0;
const resultReferenceImageUrl = (result: ResultItem) =>
    result.explanation_asset?.image_url ?? null;
const resultReferenceImageAlt = (result: ResultItem) =>
    result.explanation_asset?.alt_text
    ?? result.explanation_asset?.title
    ?? 'Grafika pomocnicza do wyjaśnienia';

const polishPlural = (
    count: number,
    singular: string,
    paucal: string,
    plural: string,
) => {
    const absoluteCount = Math.abs(count);
    const mod10 = absoluteCount % 10;
    const mod100 = absoluteCount % 100;

    if (absoluteCount === 1) {
        return singular;
    }

    if (mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14)) {
        return paucal;
    }

    return plural;
};

const formatDuration = (value: number | null) => {
    if (value === null) {
        return '-';
    }

    const seconds = Math.max(Math.round(value), 0);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (minutes <= 0) {
        return `${remainingSeconds} s`;
    }

    if (remainingSeconds === 0) {
        return `${minutes} min`;
    }

    return `${minutes} min ${remainingSeconds} s`;
};

const scopeLabel = (scope: string | null | undefined) =>
    String(scope).toUpperCase() === 'SPECJALISTYCZNY'
        ? 'Pytanie specjalistyczne'
        : 'Pytanie podstawowe';

const isAdminUser = computed(() =>
    Boolean(
        (
            page.props.auth as {
                user?: {
                    is_admin?: boolean;
                } | null;
            } | undefined
        )?.user?.is_admin,
    ),
);
const canInlineEditExplanation = computed(() =>
    isLocalLearningMode.value && isAdminUser.value,
);
const canShowInlineEditControls = computed(() =>
    canInlineEditExplanation.value && !isNarrowPhoneViewport.value,
);
const activeQuestion = computed<CurrentQuestion | null>(() => {
    if (!isLocalLearningMode.value) {
        return props.currentQuestion;
    }

    if (localSessionCompleted.value) {
        return null;
    }

    return activeQuestionState.value;
});
const currentAnswerResult = computed<ResultItem | null>(() => {
    if (!activeQuestion.value) {
        return null;
    }

    return localResults.value[activeQuestion.value.id] ?? null;
});
const currentResolvedExplanation = computed(() =>
    resolveStudySessionExplanation<ExplanationAsset, ExplanationAnnotation>({
        answerExplanation: currentAnswerResult.value?.explanation ?? null,
        questionExplanation: activeQuestion.value?.explanation ?? null,
        answerExplanationAsset: currentAnswerResult.value?.explanation_asset ?? null,
        questionExplanationAsset: activeQuestion.value?.explanation_asset ?? null,
        answerExplanationAnnotations: currentAnswerResult.value?.explanation_annotations ?? null,
        questionExplanationAnnotations: activeQuestion.value?.explanation_annotations ?? [],
    }),
);
const currentQuestionExplanationAsset = computed(() =>
    currentResolvedExplanation.value.explanationAsset,
);
const currentQuestionExplanationSignReferences = computed(() =>
    currentAnswerResult.value?.explanation_sign_references
    ?? activeQuestion.value?.explanation_sign_references
    ?? [],
);
const currentQuestionExplanationAnnotations = computed<ExplanationAnnotation[]>(() =>
    currentResolvedExplanation.value.explanationAnnotations,
);
const currentQuestionImageAnnotations = computed<ExplanationAnnotation[]>(() =>
    resolveImageAnnotations(currentQuestionExplanationAnnotations.value),
);
const currentQuestionVideoFrameAnnotations = computed<ExplanationAnnotation[]>(() => {
    if (activeQuestion.value?.media[0]?.kind === 'video') {
        return resolveVideoFrameAnnotations(currentQuestionExplanationAnnotations.value);
    }

    return [];
});
const currentQuestionVideoFrameTimeSeconds = computed(() =>
    currentQuestionVideoFrameAnnotations.value.find((annotation) => annotation.frame_time_seconds !== null)?.frame_time_seconds ?? 0,
);
const pjmQuestionAsset = computed<SignLanguageAsset | null>(() => {
    if (!isPjmMode.value) {
        return null;
    }

    return (activeQuestion.value?.sign_language_assets ?? []).find((asset) => asset.role === 'question' && Boolean(asset.url)) ?? null;
});
const shouldShowQuestionMediaTextPlaceholder = computed(() =>
    Boolean(pjmQuestionAsset.value) && !hasQuestionMedia.value,
);
const questionMediaLayoutClass = computed(() =>
    pjmQuestionAsset.value
        ? 'grid w-full max-w-[92rem] gap-3 md:grid-cols-[minmax(0,1fr)_minmax(18rem,0.56fr)] md:items-stretch xl:gap-4'
        : 'flex w-full items-center justify-center',
);
const questionMediaPanelStyle = computed<Record<string, string>>(() =>
    pjmQuestionAsset.value
        ? {
            width: '100%',
            maxWidth: '100%',
            aspectRatio: mediaFrameStyle.value.aspectRatio ?? '885 / 500',
        }
        : mediaFrameStyle.value,
);
const questionMediaPlaceholderPanelStyle = computed<Record<string, string>>(() =>
    pjmQuestionAsset.value
        ? {
            width: '100%',
            maxWidth: '100%',
        }
        : mediaFrameStyle.value,
);
const shouldRenderCurrentQuestionVideoFramePreview = (index: number) =>
    shouldRenderQuestionVideoFramePreview({
        shouldShowVideoFrameAnnotations: shouldShowCurrentVideoFrameAnnotations.value,
        isVideoPlaying: Boolean(videoPlaybackState.value[activeVideoRefKey(index)]),
    });
const resolveEditorQuestionNumber = (questionId: number | null) => {
    if (questionId === null) {
        return null;
    }

    const activeNumber = currentQuestionNumber.value;

    if (activeQuestion.value?.id === questionId && activeNumber !== null) {
        return activeNumber;
    }

    const result = displayResults.value.find((item) => item.id === questionId);

    return result?.sequence_number ?? null;
};
const explanationEditorQuestionNumber = computed(() =>
    resolveEditorQuestionNumber(explanationEditorQuestionId.value),
);
const questionEditorQuestionNumber = computed(() =>
    resolveEditorQuestionNumber(questionEditorQuestionId.value),
);
const displayResults = computed<ResultItem[]>(() => {
    if (activeQuestion.value) {
        return [];
    }

    if (!isLocalLearningMode.value) {
        return props.results;
    }

    return props.questionIds.map((questionId) => {
        const answer = localResults.value[questionId];

        return {
            id: questionId,
            external_id: answer?.external_id ?? null,
            shared_explanation_has_conflict: answer?.shared_explanation_has_conflict ?? false,
            sequence_number: answer?.sequence_number ?? props.questionIds.indexOf(questionId) + 1,
            prompt: answer?.prompt ?? null,
            explanation: answer?.explanation ?? null,
            correct_answer: answer?.correct_answer ?? null,
            correct_answer_text: answer?.correct_answer_text ?? null,
            selected_answer: answer?.selected_answer ?? null,
            answer_kind: answer?.answer_kind ?? null,
            selected_answer_text: answer?.selected_answer_text ?? null,
            is_correct: answer?.is_correct ?? null,
            response_time_ms: answer?.response_time_ms ?? null,
            topic: answer?.topic ?? null,
            media: answer?.media ?? [],
            sign_language_assets: answer?.sign_language_assets ?? [],
            explanation_asset: answer?.explanation_asset ?? null,
            explanation_sign_references: answer?.explanation_sign_references ?? [],
            explanation_annotations: answer?.explanation_annotations ?? [],
        };
    });
});
const shouldIncludeResultInCompletion = (result: ResultItem) =>
    props.session.mode === 'exam'
        ? result.is_correct !== null
        : isAnsweredResult(result);
const displayAnsweredResults = computed(() =>
    displayResults.value
        .filter((result) => shouldIncludeResultInCompletion(result))
        .slice()
        .sort((left, right) => {
            const leftRank = left.is_correct === false ? 0 : 1;
            const rightRank = right.is_correct === false ? 0 : 1;

            if (leftRank !== rightRank) {
                return leftRank - rightRank;
            }

            return left.sequence_number - right.sequence_number;
        }),
);
const incorrectAnsweredResults = computed(() =>
    displayAnsweredResults.value.filter((result) => result.is_correct === false),
);
const correctAnsweredResults = computed(() =>
    displayAnsweredResults.value.filter((result) => result.is_correct !== false),
);
const completionReviewResults = computed(() =>
    hasIncorrectAnswers.value
        ? incorrectAnsweredResults.value
        : displayAnsweredResults.value,
);
const reviewTrainerQuestionDetailsCount = computed(() => completionReviewResults.value.length);
const shouldShowCompletionQuestionReview = computed(() =>
    !activeQuestion.value
    && showCompletionResults.value
    && completionReviewResults.value.length > 0
    && (!isReviewTrainerMode.value || showReviewTrainerQuestionDetails.value),
);
const displayProgress = computed<ProgressSummary>(() => {
    if (!isLocalLearningMode.value) {
        return props.progress;
    }

    return progressState.value;
});
const displayExamUi = computed<ExamUiSummary>(() => {
    return props.examUi;
});
const displayCorrectAnswersCount = computed(() => {
    if (isReviewTrainerMode.value && reviewCompletionState.value) {
        return reviewCompletionState.value.correct_answers_count;
    }

    if (!isLocalLearningMode.value) {
        return props.session.correct_answers_count;
    }

    return sessionState.value.correct_answers_count;
});
const displayAnsweredCount = computed(() => {
    if (isReviewTrainerMode.value && reviewCompletionState.value) {
        return reviewCompletionState.value.answered_count;
    }

    return displayAnsweredResults.value.length;
});
const displaySessionProgressCount = computed(() => {
    if (currentQuestionNumber.value !== null) {
        return currentQuestionNumber.value;
    }

    if (activeQuestion.value) {
        return Math.min(displayAnsweredCount.value + 1, displayProgress.value.total);
    }

    return displayAnsweredCount.value;
});
const displayIncorrectAnswersCount = computed(() => {
    if (isReviewTrainerMode.value && reviewCompletionState.value) {
        return reviewCompletionState.value.needs_recovery_answers_count
            ?? reviewCompletionState.value.incorrect_answers_count;
    }

    return displayAnsweredResults.value.filter((result) => result.is_correct === false).length;
});
const hasIncorrectAnswers = computed(() => displayIncorrectAnswersCount.value > 0);
const displayRemainingCount = computed(() =>
    Math.max(displayProgress.value.remaining, 0),
);
const completionIssueMetricLabel = computed(() =>
    isReviewTrainerMode.value
        ? 'Do odzyskania'
        : 'Błędne',
);
const completionResultsHeading = computed(() =>
    hasIncorrectAnswers.value
        ? (isReviewTrainerMode.value ? 'Materiał do odzyskania' : 'Pytania do poprawki')
        : 'Przegląd odpowiedzi',
);
const reviewTrainerDetailsToggleLabel = computed(() => {
    if (showReviewTrainerQuestionDetails.value) {
        return 'Ukryj przegląd pytań';
    }

    const count = reviewTrainerQuestionDetailsCount.value;

    return hasIncorrectAnswers.value
        ? `Pokaż materiał do odzyskania (${count})`
        : `Pokaż pytania z sesji (${count})`;
});
const resultStatusLabel = (result: ResultItem) => {
    if (isUnknownAnswerKind(result.answer_kind)) {
        return 'Do odzyskania';
    }

    if (isReviewTrainerMode.value && result.is_correct === false) {
        return 'Do odzyskania';
    }

    return result.is_correct ? 'Dobrze' : 'Do poprawki';
};
const resultStatusClass = (result: ResultItem) => {
    if (isUnknownAnswerKind(result.answer_kind)) {
        return 'border border-[#c7d7eb] bg-[#f2f6fb] text-[#2b486b]';
    }

    return result.is_correct
        ? 'border border-[#e5e7eb] bg-[#f7f7f7] text-[#5f6368]'
        : 'border border-[#efc9c5] bg-[#fdf1f0] text-[#9f3131]';
};
const resultUserAnswerCardClass = (result: ResultItem) => {
    if (isUnknownAnswerKind(result.answer_kind)) {
        return 'border-[#c7d7eb] bg-[#f2f6fb] text-neutral-950';
    }

    return result.is_correct
        ? 'border-[#e5e7eb] bg-white text-neutral-950'
        : 'border-[#efc9c5] bg-[#fdf1f0] text-neutral-950';
};
const resultUserAnswerLabel = (result: ResultItem) => {
    if (isUnknownAnswerKind(result.answer_kind)) {
        return 'Nie wiem';
    }

    return result.selected_answer
        ? `Odpowiedź ${result.selected_answer}`
        : 'Brak odpowiedzi';
};
const resultUserAnswerText = (result: ResultItem) => {
    if (isUnknownAnswerKind(result.answer_kind)) {
        return 'Oznaczone świadomie jako materiał do odzyskania w Trenerze pamięci.';
    }

    return result.selected_answer_text ?? 'Nie udało się odczytać treści tej odpowiedzi.';
};
const currentPjmCompletion = computed(() => pjmCompletionState.value);
const pjmCoverage = computed(() => currentPjmCompletion.value?.coverage ?? null);
const pjmUserProgress = computed(() => currentPjmCompletion.value?.progress ?? null);
const pjmCoveredQuestionCount = computed(() =>
    pjmCoverage.value?.pjm_questions ?? displayProgress.value.total,
);
const pjmTotalQuestionCount = computed(() =>
    pjmCoverage.value?.total_questions
    ?? Math.max(displayProgress.value.total, pjmCoveredQuestionCount.value),
);
const pjmMissingQuestionCount = computed(() =>
    pjmCoverage.value?.missing_questions
    ?? Math.max(pjmTotalQuestionCount.value - pjmCoveredQuestionCount.value, 0),
);
const pjmCoveragePercent = computed(() =>
    pjmCoverage.value?.coverage_percent
    ?? (pjmTotalQuestionCount.value > 0
        ? Math.round((pjmCoveredQuestionCount.value / pjmTotalQuestionCount.value) * 100)
        : 0),
);
const pjmAnsweredQuestionCount = computed(() =>
    pjmUserProgress.value?.answered_questions ?? displayAnsweredCount.value,
);
const pjmProgressQuestionCount = computed(() =>
    pjmUserProgress.value?.total_questions ?? pjmCoveredQuestionCount.value,
);
const pjmUnansweredQuestionCount = computed(() =>
    pjmUserProgress.value?.unanswered_questions
    ?? Math.max(pjmProgressQuestionCount.value - pjmAnsweredQuestionCount.value, 0),
);
const pjmIncorrectQuestionCount = computed(() =>
    pjmUserProgress.value?.incorrect_questions ?? displayIncorrectAnswersCount.value,
);
const pjmLearningProgressPercent = computed(() =>
    pjmUserProgress.value?.progress_percent
    ?? (pjmProgressQuestionCount.value > 0
        ? Math.round((pjmAnsweredQuestionCount.value / pjmProgressQuestionCount.value) * 100)
        : 0),
);
const pjmCompletionTopicOptions = computed(() =>
    pjmUserProgress.value?.topic_groups.flatMap((group) => group.options) ?? [],
);
const pjmCurrentTopic = computed<PjmCompletionTopicOption | null>(() => {
    const currentTopicId = pjmUserProgress.value?.current_topic_id ?? null;

    return pjmCompletionTopicOptions.value.find((topic) => topic.id === currentTopicId)
        ?? pjmCompletionTopicOptions.value.find((topic) => (topic.counts.unanswered ?? 0) > 0)
        ?? null;
});
const pjmCurrentTopicLabel = computed(() =>
    pjmCurrentTopic.value?.label ?? 'moduł PJM',
);
const pjmContinuationQuestionCount = computed(() => {
    const unansweredInTopic = pjmCurrentTopic.value?.counts.unanswered ?? pjmUnansweredQuestionCount.value;

    return Math.max(unansweredInTopic, 1);
});
const hasPjmUnansweredQuestions = computed(() =>
    pjmUnansweredQuestionCount.value > 0,
);
const pjmPrimaryActionLabel = computed(() =>
    hasPjmUnansweredQuestions.value ? 'Kontynuuj PJM' : 'Powtórz PJM',
);
const pjmPrimaryActionTitle = computed(() =>
    hasPjmUnansweredQuestions.value
        ? `Kontynuuj dział: ${pjmCurrentTopicLabel.value}`
        : 'Powtórz pytania PJM',
);
const pjmCategoryLabel = computed(() =>
    props.session.license_category_code
        ? ` kategorii ${props.session.license_category_code}`
        : ' kategorii',
);
const pjmCompletionMessage = computed(() => {
    if (hasPjmUnansweredQuestions.value) {
        return `Ukończyłeś tę sesję PJM. W module masz już ${pjmAnsweredQuestionCount.value} z ${pjmProgressQuestionCount.value} pytań z tłumaczeniem PJM. Zostało ${pjmUnansweredQuestionCount.value} ${polishPlural(pjmUnansweredQuestionCount.value, 'pytanie', 'pytania', 'pytań')}, a kolejny etap to ${pjmCurrentTopicLabel.value}.`;
    }

    if (pjmIncorrectQuestionCount.value > 0) {
        return `Ukończyłeś pierwsze przejście przez pytania PJM w tej kategorii. Masz jeszcze ${pjmIncorrectQuestionCount.value} ${polishPlural(pjmIncorrectQuestionCount.value, 'pytanie', 'pytania', 'pytań')} do poprawki.`;
    }

    if (pjmMissingQuestionCount.value > 0) {
        return `Ukończyłeś dostępne pytania PJM w tej kategorii. W całej${pjmCategoryLabel.value} jest jeszcze ${pjmMissingQuestionCount.value} ${polishPlural(pjmMissingQuestionCount.value, 'pytanie', 'pytania', 'pytań')} bez filmu PJM, więc pokazujemy to jasno.`;
    }

    return 'Ukończyłeś dostępne pytania PJM w tej kategorii. Możesz wrócić do modułu, powtórzyć materiał albo przejść do dalszej nauki.';
});

watch(() => props.pjmCompletion, (pjmCompletion) => {
    pjmCompletionState.value = pjmCompletion ?? null;
});

watch(() => props.reviewCompletion, (reviewCompletion) => {
    reviewCompletionState.value = reviewCompletion ?? null;
});

const currentReviewCompletion = computed(() => reviewCompletionState.value);
const reviewCompletionItems = computed(() => {
    const summary = currentReviewCompletion.value;

    return [
        {
            label: 'Do odzyskania',
            value: summary?.needs_recovery_answers_count ?? summary?.recovery_count ?? 0,
            description: 'Niepewne lub błędne odpowiedzi z tej sesji.',
        },
        {
            label: 'Nie wiem',
            value: summary?.unknown_answers_count ?? 0,
            description: 'Uczciwie oznaczone bez zgadywania.',
        },
        {
            label: 'W nauce',
            value: summary?.learning_count ?? 0,
            description: 'Materiał, który buduje stabilny rytm.',
        },
        {
            label: 'Stabilne',
            value: summary?.stable_count ?? 0,
            description: 'Pytania utrwalone lub w zwykłym cyklu.',
        },
    ];
});
const completionReturnHref = computed(() => {
    if (isPublicDemoMode.value) {
        return props.publicDemo?.routes.landing ?? '/testy-na-prawo-jazdy';
    }

    if (sessionState.value.context?.return_url) {
        return sessionState.value.context.return_url;
    }

    if (isPjmMode.value) {
        return currentPjmCompletion.value?.pjm_index_url ?? route('session.index');
    }

    if (isReviewTrainerMode.value) {
        return route('review-queue.index');
    }

    return route('session.index');
});
const completionReturnLabel = computed(() => {
    if (isPublicDemoMode.value) {
        return 'Wróć do demo';
    }

    if (isPjmMode.value) {
        return 'Wróć do modułu PJM';
    }

    if (isReviewTrainerMode.value) {
        return currentReviewCompletion.value?.next_step.primary_action_label ?? 'Wróć do trenera';
    }

    return 'Wróć do panelu nauki';
});
const completionAnsweredScopeLabel = computed(() =>
    isPublicDemoMode.value
        ? 'pytań próbnych'
        : isPjmMode.value
        ? 'pytań z tłumaczeniem PJM'
        : isReviewTrainerMode.value
            ? 'pytań w treningu pamięci'
            : 'pytań w tym dziale',
);
const publicDemoPrimaryHref = computed(() => {
    if (!props.publicDemo) {
        return '/register';
    }

    if (!props.publicDemo.viewer.authenticated) {
        return props.publicDemo.routes.register;
    }

    return props.publicDemo.viewer.has_full_access
        ? props.publicDemo.routes.learning
        : props.publicDemo.routes.activate;
});
const publicDemoPrimaryUsesRegisterDrawer = computed(() =>
    Boolean(props.publicDemo && !props.publicDemo.viewer.authenticated),
);
const publicDemoPrimaryLabel = computed(() => 'Załóż konto i kontynuuj');
const publicDemoPricingHref = computed(() => props.publicDemo?.routes.pricing ?? '/cennik');
const publicDemoPaymentRequired = computed(() => page.props.authDrawers.paymentRequired);
const publicDemoCompletionPrimaryHref = computed(() =>
    publicDemoPaymentRequired.value
        ? publicDemoPricingHref.value
        : publicDemoPrimaryHref.value,
);
const publicDemoCompletionPrimaryLabel = computed(() => {
    if (publicDemoPaymentRequired.value) {
        return 'Zobacz pełny dostęp';
    }

    return props.publicDemo?.viewer.authenticated
        ? 'Przejdź do nauki'
        : 'Załóż konto i ucz się dalej';
});
const publicDemoGateDemoHref = computed(() => props.publicDemo?.routes.demo ?? '/testy-na-prawo-jazdy/demo?fresh=1');
const rememberPublicDemoGatePreference = () => {
    if (
        !skipPublicDemoGateNextTime.value
        || typeof window === 'undefined'
    ) {
        return;
    }

    window.localStorage.setItem(PUBLIC_DEMO_GATE_DISMISSED_KEY, '1');
};
const dismissPublicDemoGate = () => {
    rememberPublicDemoGatePreference();
    isPublicDemoGateDismissed.value = true;
};
const customizePublicDemoSettings = () => {
    dismissPublicDemoGate();
    openSettingsPopover({ markSeen: false });
};
const showPublicDemoCompletionPrompt = computed(() =>
    isPublicDemoMode.value
    && localSessionCompleted.value
    && !isPublicDemoCompletionPromptDismissed.value,
);
const dismissPublicDemoCompletionPrompt = () => {
    isPublicDemoCompletionPromptDismissed.value = true;
};
const publicDemoLoginDrawerOpen = ref(false);
const publicDemoRegisterDrawerOpen = ref(false);
const publicDemoAuthDrawerOpen = computed(
    () => publicDemoLoginDrawerOpen.value || publicDemoRegisterDrawerOpen.value,
);
const publicDemoAuthDrawerVisualHost = ref<'login' | 'register' | null>(null);
const publicDemoRegistrationCategories = computed(
    () => page.props.authDrawers.registrationCategories,
);
const closePublicDemoAuthDrawers = () => {
    publicDemoLoginDrawerOpen.value = false;
    publicDemoRegisterDrawerOpen.value = false;
    publicDemoAuthDrawerVisualHost.value = null;
};
const openPublicDemoLoginDrawer = () => {
    if (publicDemoAuthDrawerVisualHost.value === null) {
        publicDemoAuthDrawerVisualHost.value = 'login';
    }

    publicDemoRegisterDrawerOpen.value = false;
    publicDemoLoginDrawerOpen.value = true;
};
const openPublicDemoRegisterDrawer = () => {
    if (publicDemoAuthDrawerVisualHost.value === null) {
        publicDemoAuthDrawerVisualHost.value = 'register';
    }

    publicDemoLoginDrawerOpen.value = false;
    publicDemoRegisterDrawerOpen.value = true;
};
const publicDemoRetainsAuthDrawerVisual = (drawer: 'login' | 'register') =>
    publicDemoAuthDrawerVisualHost.value === drawer
    && (drawer === 'login' ? publicDemoRegisterDrawerOpen.value : publicDemoLoginDrawerOpen.value);
const publicDemoHidesAuthDrawerVisual = (drawer: 'login' | 'register') =>
    publicDemoAuthDrawerVisualHost.value !== null
    && publicDemoAuthDrawerVisualHost.value !== drawer
    && (drawer === 'login' ? publicDemoLoginDrawerOpen.value : publicDemoRegisterDrawerOpen.value);

const completionTiming = computed<CompletionTiming>(() => props.completionTiming);
const completionTimerLabel = computed(() =>
    completionTiming.value.duration_seconds !== null
        ? formatDuration(completionTiming.value.duration_seconds)
        : null,
);
const completionRewardMessage = computed(() => {
    const comparison = completionTiming.value.comparison;

    if (!comparison.show_reward) {
        return null;
    }

    const savedDuration = comparison.same_question_count
        && comparison.saved_duration_seconds !== null
            ? formatDuration(comparison.saved_duration_seconds)
            : null;
    const savedAverage = comparison.saved_average_correct_response_time_ms !== null
        ? formatResponseTime(comparison.saved_average_correct_response_time_ms)
        : null;

    if (savedDuration && savedAverage) {
        return `Świetnie. Ten dział poszedł Ci szybciej niż ostatnio: cała sesja krócej o ${savedDuration}, a średnio na poprawne pytanie potrzebowałeś o ${savedAverage} mniej.`;
    }

    if (savedAverage) {
        return `Świetnie. Poprawne odpowiedzi wchodziły Ci sprawniej niż ostatnio: średnio o ${savedAverage} szybciej na pytanie.`;
    }

    if (savedDuration) {
        return `Świetnie. Ten dział zamknąłeś szybciej niż ostatnio, krócej o ${savedDuration}.`;
    }

    return null;
});
const topicCompletionOverview = computed(() => topicCompletionOverviewState.value);
const topicCompletionRows = computed(() => topicCompletionOverview.value?.items ?? []);
const showTopicCompletionOverview = computed(() =>
    props.session.mode === 'learn'
    && localSessionCompleted.value
    && topicCompletionRows.value.length > 0,
);
const topicCompletionScopeLabel = computed(() => {
    const scope = topicCompletionOverview.value?.question_scope ?? props.sessionFilters.question_scope;

    if (scope === 'basic') {
        return 'Pytania podstawowe';
    }

    if (scope === 'specialist') {
        return 'Pytania specjalistyczne';
    }

    return 'Wszystkie pytania';
});
const topicCompletionPrimaryTimeLabel = (item: TopicCompletionOverviewItem) => {
    if (item.best_duration_seconds !== null) {
        return formatDuration(item.best_duration_seconds);
    }

    if (item.last_duration_seconds !== null) {
        return formatDuration(item.last_duration_seconds);
    }

    return '-';
};
const topicCompletionTimeMetaLabel = (item: TopicCompletionOverviewItem) => {
    if (item.best_duration_seconds !== null) {
        return 'Najlepszy wynik 100%';
    }

    if (item.last_duration_seconds !== null) {
        return 'Ostatnie ukończenie';
    }

    return 'Brak ukończenia';
};
const topicCompletionAttemptsLabel = (item: TopicCompletionOverviewItem) => {
    if (item.completion_count <= 0) {
        return '-';
    }

    return `${item.perfect_completion_count} / ${item.completion_count}`;
};
const topicCompletionAttemptsMetaLabel = (item: TopicCompletionOverviewItem) =>
    item.completion_count > 0 ? '100% / ukończenia' : 'Brak prób';
const topicCompletionStatusLabel = (item: TopicCompletionOverviewItem) => {
    if (item.record_state === 'first_record') {
        return 'Pierwszy rekord działu!';
    }

    if (item.record_state === 'improved_record') {
        const savedDuration = item.saved_duration_seconds !== null
            ? ` Szybciej o ${formatDuration(item.saved_duration_seconds)}.`
            : '';

        return `Nowy rekord!${savedDuration}`;
    }

    if (item.best_duration_seconds !== null) {
        return 'Rekord zapisany';
    }

    if (item.last_duration_seconds !== null) {
        return 'Ukończony bez rekordu';
    }

    return 'Jeszcze bez czasu';
};
const topicCompletionStatusClass = (item: TopicCompletionOverviewItem) => {
    if (item.record_state === 'first_record' || item.record_state === 'improved_record') {
        return 'border-[#cfe8d4] bg-[#f2fbf5] text-[#166534]';
    }

    if (item.best_duration_seconds !== null) {
        return 'border-[#d8e6f8] bg-[#f5f9ff] text-[#0057a3]';
    }

    if (item.last_duration_seconds !== null) {
        return 'border-[#eadfbd] bg-[#fff9e8] text-[#7a6314]';
    }

    return 'border-[#e5e7eb] bg-[#f8f8f8] text-[#6b7280]';
};
const displayScorePercent = computed(() => {
    if (!isLocalLearningMode.value) {
        return props.session.score_percent;
    }

    return sessionState.value.score_percent;
});
const filteredTopicGroups = computed(() => {
    if (props.sessionFilters.question_scope === 'basic') {
        return props.topicGroups.filter((group) => group.label === 'Pytania podstawowe');
    }

    if (props.sessionFilters.question_scope === 'specialist') {
        return props.topicGroups.filter((group) => group.label === 'Pytania specjalistyczne');
    }

    return props.topicGroups;
});
const canUseTopicPicker = computed(() =>
    !isPjmMode.value && !isReviewTrainerMode.value && filteredTopicGroups.value.length > 0,
);
const allSessionTopicOptions = computed<RoadmapTopicOption[]>(() =>
    props.topicGroups.flatMap((group) =>
        group.options.map((option) => ({
            ...option,
            bucketLabel: group.label,
        })),
    ),
);
const sessionTopicOptions = computed(() =>
    filteredTopicGroups.value.flatMap((group) => group.options),
);
const activeSessionTopicId = computed(() =>
    props.sessionFilters.question_topic_id
    ?? activeQuestion.value?.topic?.id
    ?? null,
);
const activeSessionTopic = computed<GroupOption | null>(() =>
    sessionTopicOptions.value.find((topic) => topic.id === activeSessionTopicId.value) ?? null,
);
const nextSessionTopic = computed<GroupOption | null>(() => {
    const orderedTopics = sessionTopicOptions.value
        .filter((topic) => questionCountForTopicStatus(topic, 'all') > 0);

    if (orderedTopics.length === 0) {
        return null;
    }

    if (activeSessionTopicId.value === null) {
        return orderedTopics[0] ?? null;
    }

    const currentIndex = orderedTopics.findIndex((topic) => topic.id === activeSessionTopicId.value);

    if (currentIndex === -1) {
        return orderedTopics[0] ?? null;
    }

    return orderedTopics[currentIndex + 1] ?? null;
});
const topicMasteredQuestionCount = (topic: GroupOption | null) =>
    topic
        ? ((topic.counts.correct ?? 0) + (topic.counts.memorized ?? 0))
        : 0;
const topicRemainingQuestionCount = (topic: GroupOption | null) =>
    topic
        ? Math.max((topic.questions_count ?? 0) - topicMasteredQuestionCount(topic), 0)
        : 0;
const masteredRoadmapTopicIds = computed(
    () => new Set(topicCompletionOverview.value?.learning_path.mastered_topic_ids ?? []),
);
const isTopicCompleted = (topic: GroupOption | null) =>
    Boolean(
        topic
        && (
            topicCompletionOverview.value?.learning_path
                ? masteredRoadmapTopicIds.value.has(topic.id)
                : (topic.questions_count ?? 0) > 0 && topicRemainingQuestionCount(topic) === 0
        ),
    );
const roadmapTopicOptions = computed(() =>
    allSessionTopicOptions.value.filter((topic) => questionCountForTopicStatus(topic, 'all') > 0),
);
const roadmapCurrentTopicId = computed<number | null>(() => {
    if (activeSessionTopicId.value !== null) {
        return activeSessionTopicId.value;
    }

    const firstIncompleteTopic = roadmapTopicOptions.value.find((topic) => !isTopicCompleted(topic));

    return firstIncompleteTopic?.id ?? roadmapTopicOptions.value[0]?.id ?? null;
});
const roadmapCurrentTopic = computed<RoadmapTopicOption | null>(() =>
    roadmapTopicOptions.value.find((topic) => topic.id === roadmapCurrentTopicId.value) ?? null,
);
const roadmapNextTopic = computed<RoadmapTopicOption | null>(() => {
    if (roadmapTopicOptions.value.length === 0) {
        return null;
    }

    const currentIndex = roadmapTopicOptions.value.findIndex((topic) => topic.id === roadmapCurrentTopicId.value);
    const laterTopics = currentIndex === -1
        ? roadmapTopicOptions.value
        : roadmapTopicOptions.value.slice(currentIndex + 1);

    return laterTopics.find((topic) => !isTopicCompleted(topic)) ?? null;
});
const completedRoadmapTopicsCount = computed(() =>
    topicCompletionOverview.value?.learning_path.mastered_topics
    ?? roadmapTopicOptions.value.filter((topic) => isTopicCompleted(topic)).length,
);
const roadmapTotalQuestions = computed(() =>
    roadmapTopicOptions.value.reduce((sum, topic) => sum + questionCountForTopicStatus(topic, 'all'), 0),
);
const roadmapMasteredQuestionsTotal = computed(() =>
    roadmapTopicOptions.value.reduce((sum, topic) => sum + topicMasteredQuestionCount(topic), 0),
);
const roadmapRemainingQuestionsTotal = computed(() =>
    Math.max(roadmapTotalQuestions.value - roadmapMasteredQuestionsTotal.value, 0),
);
const roadmapOverallProgressPercent = computed(() =>
    roadmapTotalQuestions.value > 0
        ? Math.min(Math.round((roadmapMasteredQuestionsTotal.value / roadmapTotalQuestions.value) * 100), 100)
        : 0,
);
const showSessionRoadmap = computed(() =>
    props.session.mode === 'learn'
    && localSessionCompleted.value
    && roadmapTopicOptions.value.length > 0,
);
const isSessionRoadmapLegendOpen = ref(false);
const sessionRoadmapLegendRef = ref<HTMLElement | null>(null);
const sessionRoadmapScroller = ref<HTMLElement | null>(null);
const canScrollSessionRoadmapBackward = ref(false);
const canScrollSessionRoadmapForward = ref(false);
const isSessionRoadmapDragging = ref(false);
const sessionRoadmapDragPointerId = ref<number | null>(null);
const sessionRoadmapDragStartX = ref(0);
const sessionRoadmapDragStartScrollLeft = ref(0);
const hasAutoFocusedSessionRoadmap = ref(false);
const hasAutoFocusedCompletionProgressPanel = ref(false);

const syncSessionRoadmapScrollState = () => {
    const scroller = sessionRoadmapScroller.value;

    if (!scroller) {
        canScrollSessionRoadmapBackward.value = false;
        canScrollSessionRoadmapForward.value = false;
        return;
    }

    const maxScrollLeft = Math.max(scroller.scrollWidth - scroller.clientWidth, 0);

    canScrollSessionRoadmapBackward.value = scroller.scrollLeft > 8;
    canScrollSessionRoadmapForward.value = scroller.scrollLeft < maxScrollLeft - 8;
};

const scrollSessionRoadmap = (direction: 'prev' | 'next') => {
    const scroller = sessionRoadmapScroller.value;

    if (!scroller) {
        return;
    }

    const cards = Array.from(
        scroller.querySelectorAll<HTMLElement>('[data-session-roadmap-card]'),
    );

    let step = Math.round(scroller.clientWidth * 0.82);

    if (cards.length > 1) {
        step = Math.round(cards[1].offsetLeft - cards[0].offsetLeft);
    } else if (cards[0]) {
        step = cards[0].offsetWidth;
    }

    scroller.scrollBy({
        left: direction === 'next' ? step : -step,
        behavior: 'smooth',
    });
};

const startSessionRoadmapDrag = (event: PointerEvent) => {
    if (event.pointerType !== 'mouse') {
        return;
    }

    const scroller = sessionRoadmapScroller.value;

    if (!scroller) {
        return;
    }

    isSessionRoadmapDragging.value = true;
    sessionRoadmapDragPointerId.value = event.pointerId;
    sessionRoadmapDragStartX.value = event.clientX;
    sessionRoadmapDragStartScrollLeft.value = scroller.scrollLeft;

    scroller.setPointerCapture?.(event.pointerId);
};

const moveSessionRoadmapDrag = (event: PointerEvent) => {
    const scroller = sessionRoadmapScroller.value;

    if (
        !isSessionRoadmapDragging.value
        || !scroller
        || sessionRoadmapDragPointerId.value !== event.pointerId
    ) {
        return;
    }

    const deltaX = event.clientX - sessionRoadmapDragStartX.value;
    scroller.scrollLeft = sessionRoadmapDragStartScrollLeft.value - deltaX;
    syncSessionRoadmapScrollState();
};

const endSessionRoadmapDrag = (event?: PointerEvent) => {
    const scroller = sessionRoadmapScroller.value;

    if (event && scroller && sessionRoadmapDragPointerId.value === event.pointerId) {
        scroller.releasePointerCapture?.(event.pointerId);
    }

    isSessionRoadmapDragging.value = false;
    sessionRoadmapDragPointerId.value = null;
};

const handleSessionRoadmapLegendOutsideClick = (event: MouseEvent) => {
    if (!isSessionRoadmapLegendOpen.value) {
        return;
    }

    const legendRoot = sessionRoadmapLegendRef.value;
    const target = event.target;

    if (!legendRoot || !(target instanceof Node)) {
        return;
    }

    if (!legendRoot.contains(target)) {
        isSessionRoadmapLegendOpen.value = false;
    }
};

const sessionRoadmap = computed<SessionRoadmapItem[]>(() => {
    const nextTopicId = roadmapNextTopic.value?.id ?? null;

    return roadmapTopicOptions.value.map((topic, index) => {
        const totalQuestions = questionCountForTopicStatus(topic, 'all');
        const masteredQuestions = topicMasteredQuestionCount(topic);
        const remainingQuestions = topicRemainingQuestionCount(topic);
        const completed = isTopicCompleted(topic);
        const status: SessionRoadmapItem['status'] = completed
            ? 'completed'
            : topic.id === roadmapCurrentTopicId.value
                ? 'current'
                : topic.id === nextTopicId
                    ? 'next'
                    : 'upcoming';

        return {
            id: topic.id,
            key: topic.key,
            label: topic.label,
            bucketLabel: topic.bucketLabel,
            position: index + 1,
            totalQuestions,
            masteredQuestions,
            remainingQuestions,
            progressPercent: totalQuestions > 0
                ? Math.min(Math.round((masteredQuestions / totalQuestions) * 100), 100)
                : 0,
            status,
            badge: status === 'completed'
                ? 'Zaliczony'
                : status === 'current'
                    ? 'Tu jesteś teraz'
                    : status === 'next'
                        ? 'Dalej'
                        : 'Przed Tobą',
        };
    });
});
const mobileSessionRoadmapCurrentItem = computed<SessionRoadmapItem | null>(() =>
    sessionRoadmap.value.find((topic) => topic.id === activeSessionTopicId.value)
    ?? sessionRoadmap.value.find((topic) => topic.status === 'current')
    ?? sessionRoadmap.value.find((topic) => topic.status === 'next')
    ?? sessionRoadmap.value[sessionRoadmap.value.length - 1]
    ?? sessionRoadmap.value[0]
    ?? null,
);
const mobileSessionRoadmapNextItem = computed<SessionRoadmapItem | null>(() =>
    sessionRoadmap.value.find((topic) => topic.status === 'next') ?? null,
);
const mobileSessionRoadmapCurrentBadge = computed(() => {
    const currentItem = mobileSessionRoadmapCurrentItem.value;

    if (!currentItem) {
        return 'Ścieżka';
    }

    if (currentItem.status === 'completed') {
        return 'Zaliczony';
    }

    if (currentItem.status === 'next') {
        return 'Dalej';
    }

    return 'Tu jesteś teraz';
});
const mobileSessionRoadmapCurrentSummary = computed(() => {
    const currentItem = mobileSessionRoadmapCurrentItem.value;

    if (!currentItem) {
        return '';
    }

    if (currentItem.remainingQuestions === 0) {
        return 'Gotowe';
    }

    return `${currentItem.masteredQuestions} / ${currentItem.totalQuestions}`;
});
const mobileSessionRoadmapCurrentTimeLabel = computed(() => {
    const currentItem = mobileSessionRoadmapCurrentItem.value;

    if (!currentItem) {
        return null;
    }

    if (currentItem.id === activeSessionTopicId.value && completionTimerLabel.value) {
        return `Czas tej serii: ${completionTimerLabel.value}`;
    }

    const record = topicCompletionRows.value.find((item) => item.topic_id === currentItem.id) ?? null;

    if (!record || (record.best_duration_seconds === null && record.last_duration_seconds === null)) {
        return null;
    }

    return `${topicCompletionTimeMetaLabel(record)}: ${topicCompletionPrimaryTimeLabel(record)}`;
});
const mobileSessionRoadmapPositionLabel = computed(() => {
    const currentItem = mobileSessionRoadmapCurrentItem.value;

    if (!currentItem) {
        return `${completedRoadmapTopicsCount.value} z ${roadmapTopicOptions.value.length} działów`;
    }

    return `Dział ${currentItem.position} z ${sessionRoadmap.value.length}`;
});
const sessionRoadmapFocusTopicId = computed<number | null>(() => {
    const currentTopic = sessionRoadmap.value.find((topic) => topic.status === 'current');

    if (currentTopic) {
        return currentTopic.id;
    }

    const nextTopic = sessionRoadmap.value.find((topic) => topic.status === 'next');

    if (nextTopic) {
        return nextTopic.id;
    }

    const firstOpenTopic = sessionRoadmap.value.find((topic) => topic.status !== 'completed');

    if (firstOpenTopic) {
        return firstOpenTopic.id;
    }

    return sessionRoadmap.value[sessionRoadmap.value.length - 1]?.id ?? null;
});
const scrollSessionRoadmapToFocusedTopic = (behavior: ScrollBehavior = 'auto') => {
    const scroller = sessionRoadmapScroller.value;
    const focusTopicId = sessionRoadmapFocusTopicId.value;

    if (!scroller || focusTopicId === null) {
        syncSessionRoadmapScrollState();
        return false;
    }

    const targetCard = scroller.querySelector<HTMLElement>(
        `[data-session-roadmap-topic-id="${focusTopicId}"]`,
    );

    if (!targetCard) {
        syncSessionRoadmapScrollState();
        return false;
    }

    const maxScrollLeft = Math.max(scroller.scrollWidth - scroller.clientWidth, 0);
    const centeredOffset = Math.max((scroller.clientWidth - targetCard.offsetWidth) / 2, 0);
    const targetScrollLeft = Math.min(
        Math.max(targetCard.offsetLeft - centeredOffset, 0),
        maxScrollLeft,
    );

    scroller.scrollTo({
        left: targetScrollLeft,
        behavior,
    });

    if (typeof window !== 'undefined') {
        window.requestAnimationFrame(syncSessionRoadmapScrollState);
    } else {
        syncSessionRoadmapScrollState();
    }

    return true;
};
const autoFocusSessionRoadmap = async () => {
    if (!showSessionRoadmap.value || hasAutoFocusedSessionRoadmap.value) {
        return;
    }

    await nextTick();

    hasAutoFocusedSessionRoadmap.value = scrollSessionRoadmapToFocusedTopic();
};
const roadmapLead = computed(() => {
    const currentTopic = roadmapCurrentTopic.value;

    if (!currentTopic) {
        return 'Krok po kroku widzisz, ile działów zostało jeszcze do końca nauki.';
    }

    if (isTopicCompleted(currentTopic) && roadmapNextTopic.value) {
        return `${currentTopic.label} masz już za sobą. Następny krok to ${roadmapNextTopic.value.label}.`;
    }

    if (isTopicCompleted(currentTopic)) {
        return `Masz już zaliczone wszystkie pytania z działu ${currentTopic.label}.`;
    }

    return `W dziale ${currentTopic.label} masz opanowane ${topicMasteredQuestionCount(currentTopic)} z ${questionCountForTopicStatus(currentTopic, 'all')} pytań.`;
});
const isAnySidePanelOpen = computed(
    () => isSettingsOpen.value || isTopicPickerOpen.value || isMobileSessionMenuOpen.value,
);
const canSwitchTopics = computed(() =>
    canUseTopicPicker.value
    && !topicSwitchInFlight.value
    && !learningSyncInFlight.value
    && !questionTransitionInFlight.value
    && !answerForm.processing,
);
const usesReviewAtEndMode = computed(() =>
    isLocalLearningMode.value && feedbackMode.value === 'review',
);
const usesExplanationFeedbackMode = computed(() =>
    isLocalLearningMode.value && feedbackMode.value === 'instant_explanation',
);
const usesInstantFeedbackMode = computed(() =>
    isLocalLearningMode.value && feedbackMode.value !== 'review',
);
const displaySessionStatus = computed(() =>
    localSessionCompleted.value
        ? 'Zakonczona'
        : formatStatus(sessionState.value.status),
);
const completionDecisionTitle = computed(() =>
    isReviewTrainerMode.value
        ? (hasIncorrectAnswers.value
            ? 'Najlepiej wrócić do materiału do odzyskania.'
            : 'Możesz wrócić do planu pamięci.')
        : hasIncorrectAnswers.value
            ? 'Najlepiej od razu poprawić błędne pytania.'
            : 'Możesz od razu przejść do kolejnego działu.',
);
const completionDecisionLead = computed(() => {
    if (isReviewTrainerMode.value) {
        return hasIncorrectAnswers.value
            ? 'To nie kara za błąd, tylko czysty sygnał: tutaj pamięć wymaga kolejnego spokojnego odzyskania.'
            : 'Plan pamięci użyje tego wyniku przy kolejnej kolejce powtórek.';
    }

    if (hasIncorrectAnswers.value) {
        return 'To najszybsza droga, żeby nie zostawić złego wariantu w pamięci i szybko wrócić do dobrego rytmu nauki.';
    }

    if (displayRemainingCount.value > 0) {
        return `W tym zakresie zostało jeszcze ${displayRemainingCount.value} ${polishPlural(displayRemainingCount.value, 'pytanie', 'pytania', 'pytań')}, ale nie musisz wracać do początku, żeby ruszyć dalej.`;
    }

    return 'Jeśli chcesz, możesz od razu wejść w kolejny dział albo powtórzyć ten sam dla pełnego utrwalenia.';
});
const completionCorrectResultsToggleLabel = computed(() =>
    showCorrectCompletionResults.value
        ? 'Ukryj dobrze rozwiązane pytania'
        : `Pokaż dobrze rozwiązane pytania (${correctAnsweredResults.value.length})`,
);
const completionAccuracyPercent = computed(() => {
    if (displayAnsweredCount.value <= 0) {
        return null;
    }

    return Math.round((displayCorrectAnswersCount.value / displayAnsweredCount.value) * 10000) / 100;
});
const mobileCompletionProgressWidth = computed(
    () => `${Math.min(Math.max(completionAccuracyPercent.value ?? 0, 0), 100)}%`,
);
const completionProgressRecordByTopicId = computed(() => {
    const records = new Map<number, TopicCompletionOverviewItem>();

    topicCompletionRows.value.forEach((record) => {
        records.set(record.topic_id, record);
    });

    return records;
});
const completionProgressTotalTopics = computed(() =>
    topicCompletionOverview.value?.learning_path.total_topics
    ?? roadmapTopicOptions.value.length,
);
const completionProgressRemainingTopics = computed<number | null>(() => {
    const learningPath = topicCompletionOverview.value?.learning_path;

    if (learningPath) {
        return learningPath.remaining_after_current;
    }

    const currentIndex = sessionRoadmap.value.findIndex((topic) => topic.id === activeSessionTopicId.value);

    return currentIndex >= 0
        ? Math.max(sessionRoadmap.value.length - currentIndex - 1, 0)
        : null;
});
const completionProgressPanelItems = computed<CompletionProgressPanelItem[]>(() =>
    sessionRoadmap.value.map((topic) => {
        const record = completionProgressRecordByTopicId.value.get(topic.id) ?? null;
        const isActive = activeSessionTopicId.value === topic.id;
        const isMastered = masteredRoadmapTopicIds.value.has(topic.id)
            || (!topicCompletionOverview.value?.learning_path && topic.status === 'completed');
        const displayPercent = isActive && completionAccuracyPercent.value !== null
            ? Math.round(completionAccuracyPercent.value)
            : isMastered
                ? 100
                : topic.progressPercent;
        const displayTimeLabel = isActive && completionTimerLabel.value
            ? completionTimerLabel.value
            : record && (record.best_duration_seconds !== null || record.last_duration_seconds !== null)
                ? topicCompletionPrimaryTimeLabel(record)
                : null;

        return {
            ...topic,
            displayPercent,
            displayTimeLabel,
            isActive,
            isMastered,
            statusLabel: isMastered
                ? 'Zaliczony'
                : isActive
                    ? 'Aktualny wynik'
                    : 'Przed Tobą',
        };
    }),
);
const completionProgressItemClass = (item: CompletionProgressPanelItem) => {
    if (item.isActive) {
        return 'border-[#d7dee8] bg-[#f8fafc] shadow-[0_12px_24px_rgba(15,23,42,0.05)]';
    }

    if (item.isMastered) {
        return 'border-transparent bg-white';
    }

    return 'border-transparent bg-white text-[#5b6675]';
};
const completionProgressIndicatorClass = (item: CompletionProgressPanelItem) => {
    if (item.isActive) {
        return 'border-[#334155] bg-[#334155] text-white';
    }

    if (item.isMastered) {
        return 'border-[#22b35f] bg-[#22b35f] text-white';
    }

    return 'border-[#e5e7eb] bg-[#f4f6f8] text-[#7b8794]';
};
const scrollCompletionProgressPanelToActiveTopic = (behavior: ScrollBehavior = 'auto') => {
    const scroller = completionProgressPanelScroller.value;
    const activeTopicId = activeSessionTopicId.value;

    if (!scroller || activeTopicId === null) {
        return false;
    }

    const targetItem = scroller.querySelector<HTMLElement>(
        `[data-completion-progress-topic-id="${activeTopicId}"]`,
    );

    if (!targetItem) {
        return false;
    }

    const scrollerRect = scroller.getBoundingClientRect();
    const targetRect = targetItem.getBoundingClientRect();
    const maxScrollTop = Math.max(scroller.scrollHeight - scroller.clientHeight, 0);
    const centeredOffset = Math.max((scroller.clientHeight - targetRect.height) / 2, 0);
    const targetScrollTop = Math.min(
        Math.max(scroller.scrollTop + targetRect.top - scrollerRect.top - centeredOffset, 0),
        maxScrollTop,
    );

    scroller.scrollTo({
        top: targetScrollTop,
        behavior,
    });

    return true;
};
const autoFocusCompletionProgressPanel = async (behavior: ScrollBehavior = 'auto') => {
    if (!showCompletionProgressPanel.value || hasAutoFocusedCompletionProgressPanel.value) {
        return;
    }

    await nextTick();

    hasAutoFocusedCompletionProgressPanel.value = scrollCompletionProgressPanelToActiveTopic(behavior);
};
const hasOnDemandExplanationAvailable = computed(() =>
    hasOnDemandExplanationContent({
        explanationText: explanationText.value,
        explanationAssetBody: currentQuestionExplanationAsset.value?.body ?? null,
        explanationAssetImageUrl: currentQuestionExplanationAsset.value?.image_url ?? null,
    }),
);
const shouldShowExplanationCard = computed(() =>
    shouldShowStudySessionExplanationCard({
        showExplanation: showExplanation.value,
        showExplanationOnDemand: showExplanationOnDemand.value,
        usesExplanationFeedbackMode: usesExplanationFeedbackMode.value,
        currentAnswerIsCorrect: currentAnswerResult.value?.is_correct ?? null,
        hasOnDemandExplanationContent: hasOnDemandExplanationAvailable.value,
    }),
);
const isManualExplanationVisible = computed(() =>
    Boolean(showExplanationOnDemand.value && shouldShowExplanationCard.value),
);
const isAutomaticExplanationVisible = computed(() =>
    Boolean(
        showExplanation.value
        && !showExplanationOnDemand.value
        && usesExplanationFeedbackMode.value
        && currentAnswerResult.value?.is_correct === false,
    ),
);
const canToggleExplanationOnDemand = computed(() =>
    canToggleStudySessionExplanationOnDemand({
        isLocalLearningMode: isLocalLearningMode.value,
        questionStage: questionStage.value,
        localSessionCompleted: localSessionCompleted.value,
        hasOnDemandExplanationContent: hasOnDemandExplanationAvailable.value,
        isManualExplanationVisible: isManualExplanationVisible.value,
        isAutoExplanationVisible: isAutomaticExplanationVisible.value,
    }),
);
const explanationToggleLabel = computed(() =>
    isManualExplanationVisible.value ? 'Pokaż pytanie' : 'Pokaż wyjaśnienie',
);
const shouldShowCurrentImageAnnotations = computed(() =>
    Boolean(
        visualExplanationsMode.value !== 'off'
        && showVisualAnnotations.value
        && isLocalLearningMode.value
        && !localSessionCompleted.value
        && activeQuestion.value?.media[0]?.kind === 'image'
        && currentQuestionImageAnnotations.value.length > 0,
    ),
);
const shouldShowCurrentVideoFrameAnnotations = computed(() =>
    Boolean(
        visualExplanationsMode.value !== 'off'
        && showVisualAnnotations.value
        && isLocalLearningMode.value
        && !localSessionCompleted.value
        && activeQuestion.value?.media[0]?.kind === 'video'
        && currentQuestionVideoFrameAnnotations.value.length > 0,
    ),
);
const explanationText = computed(() =>
    normalizeExplanationPlainText(currentResolvedExplanation.value.explanation),
);
const currentQuestionPromptHtml = computed(() =>
    renderSessionInlineFormattedHtml(
        activeQuestion.value?.prompt ?? 'Trwa przygotowanie pytania.',
    ),
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
        return `Źródło techniczne: ${activeQuestion.value.source}`;
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

    return 'Numer źródłowy nie jest dostępny dla tego pytania.';
});
const currentQuestionSourceNumberLabel = computed(() => {
    if (hasOfficialGovQuestionSource.value) {
        return 'Numer źródłowy';
    }

    if (isSupplementalPj360Question.value) {
        return 'Numer referencyjny';
    }

    return 'Numer pytania';
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
const currentExplanationHtml = computed(() =>
    renderSessionExplanationHtml(currentResolvedExplanation.value.explanation),
);
const publicExplanationUrl = computed(() => activeQuestion.value?.public_explanation_url ?? null);
const hasExplanationContent = computed(() => explanationText.value.length > 0);
const instructorHintPayload = computed(() => {
    if (hasExplanationContent.value) {
        return buildInstructorHintPayload(currentResolvedExplanation.value.explanation ?? '');
    }

    return {
        text: 'Spójrz na zielony wariant. To on pokazuje, jaki sygnał był tu najważniejszy.',
        richText: 'Spójrz na zielony wariant. To on pokazuje, jaki sygnał był tu najważniejszy.',
    };
});
const instructorHintText = computed(() => {
    return instructorHintPayload.value.text;
});
const instructorHintGlanceMs = computed(() =>
    resolveInstructorHintGlanceMs(
        instructorHintText.value,
        hasExplanationContent.value,
        hintTimingOffsetMs.value,
    ),
);
const isCurrentQuestionBasic = computed(
    () => activeQuestion.value?.structure_scope !== 'SPECJALISTYCZNY',
);
const examLikeAnswerOptionsClass = computed(() => {
    if (activeQuestion.value?.question_type === 'boolean') {
        return 'grid gap-2 sm:grid-cols-2';
    }

    if (props.session.mode === 'learn' && !isCurrentQuestionBasic.value) {
        return 'grid gap-2 md:grid-cols-3';
    }

    return 'space-y-2';
});
const progressPercent = computed(() => {
    if (displayProgress.value.total === 0) {
        return 0;
    }

    return Math.round(
        (displayProgress.value.answered / displayProgress.value.total) * 100,
    );
});
const answerOptions = computed(() => activeQuestion.value?.options ?? []);

const currentQuestionNumber = computed(() => {
    if (!activeQuestion.value) {
        return null;
    }

    if (isLocalLearningMode.value) {
        return currentQuestionNumberState.value;
    }

    const index = props.results.findIndex(
        (result) => result.id === activeQuestion.value?.id,
    );

    return index >= 0 ? index + 1 : null;
});
const selectedAnswerKey = computed(() =>
    currentAnswerResult.value?.selected_answer?.toLowerCase()
    ?? selectedAnswer.value,
);
const correctAnswerKey = computed(() =>
    currentAnswerResult.value?.correct_answer?.toLowerCase()
    ?? activeQuestion.value?.correct_answer?.toLowerCase()
    ?? null,
);
const shouldShowInstantAnswerFeedback = computed(() =>
    usesInstantFeedbackMode.value
    && currentAnswerResult.value !== null,
);
const currentQuestionPromptFormattingWeight = computed(() => {
    const prompt = activeQuestion.value?.prompt ?? '';

    if (prompt === '') {
        return 0;
    }

    const boldMarkers = (prompt.match(/\*\*/g) ?? []).length / 2;
    const redMarkers = (prompt.match(/\[red\]/g) ?? []).length;
    const greenMarkers = (prompt.match(/\[green\]/g) ?? []).length;

    return boldMarkers + redMarkers + greenMarkers;
});
const questionPromptClass = computed(() => {
    const length = activeQuestion.value?.prompt.length ?? 0;
    const isCompact = isCompactSessionViewport.value;
    const hasDenseInlineFormatting = !isExamLikeShell.value && currentQuestionPromptFormattingWeight.value >= 4;

    if (hasDenseInlineFormatting) {
        return isCompact
            ? 'text-[1rem] leading-[1.48] sm:text-[1.04rem]'
            : 'text-[1.04rem] leading-[1.48] sm:text-[1.1rem]';
    }

    if (length >= 260) {
        return isCompact
            ? 'text-[1.06rem] leading-[1.4] sm:text-[1.06rem]'
            : 'text-[1.06rem] leading-[1.4] sm:text-[1.12rem]';
    }

    if (length >= 190) {
        return isCompact
            ? 'text-[1.125rem] leading-[1.4] sm:text-[1.125rem]'
            : 'text-[1.125rem] leading-[1.4] sm:text-[1.125rem]';
    }

    return isCompact
        ? 'text-[1.125rem] leading-[1.4] sm:text-[1.125rem]'
        : 'text-[1.125rem] leading-[1.4] sm:text-[1.1875rem]';
});

const phaseTitle = computed(() =>
    !isExamMode.value
        ? 'Tryb nauki'
        : questionStage.value === 'preview'
        ? 'Czas na zapoznanie sie z pytaniem'
        : 'Czas na udzielenie odpowiedzi',
);

const phaseDurationLabel = computed(() => {
    if (!activeQuestion.value) {
        return '-';
    }

    if (!isExamMode.value) {
        return 'Bez limitu';
    }

    if (questionStage.value === 'preview') {
        return '20 s';
    }

    return activeQuestion.value.structure_scope === 'SPECJALISTYCZNY'
        ? '50 s'
        : '15 s';
});

const finishSessionIdleLabel = computed(() => (isPublicDemoMode.value ? 'Zakończ demo' : 'Kończę naukę'));
const finishSessionProcessingLabel = computed(() => (isPublicDemoMode.value ? 'Kończę demo...' : 'Kończę...'));
const finishSessionButtonLabel = computed(() =>
    completeForm.processing ? finishSessionProcessingLabel.value : finishSessionIdleLabel.value,
);

const primaryActionLabel = computed(() => {
    if (questionStage.value === 'preview') {
        return 'Przejdź do odpowiedzi';
    }

    if (answerForm.processing) {
        return 'Zapisywanie...';
    }

    if (
        isLocalLearningMode.value
        && currentQuestionNumber.value === displayProgress.value.total
    ) {
        if (isPublicDemoMode.value) {
            return 'Zakończ demo';
        }

        return 'Kończę naukę';
    }

    return 'Następne pytanie';
});
const explanationToggleDisplayLabel = computed(() => {
    if (!useCompactMobileDockActions.value) {
        return explanationToggleLabel.value;
    }

    return isManualExplanationVisible.value ? 'Pytanie' : 'Wyjaśnienie';
});
const primaryActionDisplayLabel = computed(() =>
    useCompactMobileDockActions.value ? 'Następne' : primaryActionLabel.value,
);

const canSelectAnswer = computed(() =>
    Boolean(activeQuestion.value)
    && questionStage.value === 'answer'
    && !answerForm.processing
    && !answerInteractionLocked.value
    && !questionTransitionInFlight.value
    && (!isLocalLearningMode.value || currentAnswerResult.value === null),
);
const canMarkUnknownAnswer = computed(() =>
    props.session.mode === 'sr_review'
    && canSelectAnswer.value,
);

const primaryActionDisabled = computed(() => {
    if (!activeQuestion.value) {
        return true;
    }

    if (questionStage.value === 'preview') {
        return answerForm.processing || questionTransitionInFlight.value;
    }

    if (isLocalLearningMode.value) {
        const nextQuestionMeta = resolveNextQuestionMeta();
        const hasReadyNextQuestion = Boolean(
            preparedNextQuestion.value
            || (nextQuestionMeta && questionCache.value[nextQuestionMeta.id]),
        );
        const hasBlockingAnswerSyncError = answerSyncFailedQuestionId.value === activeQuestion.value?.id;

        return currentAnswerResult.value === null
            || hasBlockingAnswerSyncError
            || questionTransitionInFlight.value
            || (!hasReadyNextQuestion && learningSyncInFlight.value);
        }

    return answerForm.processing || selectedAnswer.value === null;
});
const previousActionDisabled = computed(() => {
    if (!isLocalLearningMode.value || !activeQuestion.value || localSessionCompleted.value) {
        return true;
    }

    const previousQuestionMeta = resolvePreviousQuestionMeta();
    const hasBlockingAnswerSyncError = answerSyncFailedQuestionId.value === activeQuestion.value.id;

    return !previousQuestionMeta
        || questionTransitionInFlight.value
        || hasBlockingAnswerSyncError;
});

const videoRefKey = (questionId: number, index: number) => `${questionId}-${index}`;
const activeVideoRefKey = (index: number) =>
    activeQuestion.value
        ? videoRefKey(activeQuestion.value.id, index)
        : `inactive-${index}`;

const clearAutoAdvanceTimeout = () => {
    if (autoAdvanceTimeoutId === null || typeof window === 'undefined') {
        return;
    }

    window.clearTimeout(autoAdvanceTimeoutId);
    autoAdvanceTimeoutId = null;
};

const isTypingInInteractiveElement = (target: EventTarget | null) => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (target.isContentEditable) {
        return true;
    }

    return Boolean(target.closest('input, textarea, select, button, a, summary, details'));
};

const moveKeyboardSelection = (direction: 'previous' | 'next') => {
    const options = answerOptions.value;

    if (options.length === 0) {
        return;
    }

    const currentIndex = options.findIndex(
        (option) => option.key === keyboardSelectedOptionKey.value,
    );

    if (currentIndex < 0) {
        keyboardSelectedOptionKey.value = direction === 'next'
            ? options[0]?.key ?? null
            : options[options.length - 1]?.key ?? null;
        return;
    }

    const nextIndex = direction === 'next'
        ? Math.min(currentIndex + 1, options.length - 1)
        : Math.max(currentIndex - 1, 0);

    keyboardSelectedOptionKey.value = options[nextIndex]?.key ?? null;
};

const markLearningPanelAsSeen = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(SESSION_LEARNING_PANEL_SEEN_KEY, '1');
};

const shouldShowLearningGuide = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(SESSION_LEARNING_GUIDE_DISMISSED_KEY) !== '1';
};

const shouldShowTopicGuide = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return canUseTopicPicker.value
        && window.localStorage.getItem(SESSION_TOPIC_GUIDE_DISMISSED_KEY) !== '1';
};

const dismissLearningGuide = () => {
    isLearningGuideVisible.value = false;

    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(SESSION_LEARNING_GUIDE_DISMISSED_KEY, '1');
};

const dismissTopicGuide = () => {
    isTopicGuideVisible.value = false;

    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(SESSION_TOPIC_GUIDE_DISMISSED_KEY, '1');
};

const openSettingsPopover = ({ markSeen = true }: { markSeen?: boolean } = {}) => {
    isMobileSessionMenuOpen.value = false;
    isTopicPickerOpen.value = false;
    isTopicGuideVisible.value = false;
    isSettingsOpen.value = true;

    if (markSeen) {
        markLearningPanelAsSeen();
    }
};

const closeSettingsPopover = () => {
    if (isLearningGuideVisible.value) {
        dismissLearningGuide();

        if (shouldShowTopicGuide()) {
            openTopicPicker({ showGuide: true });
            return;
        }
    }

    isSettingsOpen.value = false;
};

const openTopicPicker = ({ showGuide = false }: { showGuide?: boolean } = {}) => {
    if (!canUseTopicPicker.value) {
        return;
    }

    isMobileSessionMenuOpen.value = false;
    isSettingsOpen.value = false;
    isLearningGuideVisible.value = false;
    isTopicGuideVisible.value = showGuide;
    isTopicPickerOpen.value = true;
};

const openMobileSessionMenu = () => {
    isSettingsOpen.value = false;
    isTopicPickerOpen.value = false;
    isLearningGuideVisible.value = false;
    isTopicGuideVisible.value = false;
    isMobileSessionMenuOpen.value = true;
};

const closeMobileSessionMenu = () => {
    isMobileSessionMenuOpen.value = false;
};

const closeTopicPicker = () => {
    if (isTopicGuideVisible.value) {
        dismissTopicGuide();
    }

    isTopicPickerOpen.value = false;
};

const closeSidePanels = () => {
    if (isMobileSessionMenuOpen.value) {
        closeMobileSessionMenu();
        return;
    }

    if (isSettingsOpen.value) {
        closeSettingsPopover();
        return;
    }

    if (isTopicPickerOpen.value) {
        closeTopicPicker();
    }
};

const applyFeedbackModePreset = (mode: FeedbackMode) => {
    feedbackMode.value = mode;
    autoAdvance.value = defaultAutoAdvanceForFeedbackMode(mode);
};

const handleSessionKeydown = (event: KeyboardEvent) => {
    if (
        sessionExpired.value
        || !activeQuestion.value
        || localSessionCompleted.value
    ) {
        return;
    }

    if (isAnySidePanelOpen.value) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeSidePanels();
        }

        return;
    }

    if (isTypingInInteractiveElement(event.target)) {
        return;
    }

    if (questionStage.value === 'preview') {
        if ((event.key === 'Enter' || event.key === ' ') && !primaryActionDisabled.value) {
            event.preventDefault();
            handlePrimaryAction();
        }

        return;
    }

    if (questionStage.value !== 'answer') {
        return;
    }

    const options = answerOptions.value;
    const shortcutKey = event.key.toLowerCase();

    if (options.length === 0) {
        return;
    }

    if (canSelectAnswer.value) {
        if (options.length === 2) {
            if (['arrowleft', 'arrowup', 'a', 'w'].includes(shortcutKey)) {
                event.preventDefault();
                keyboardSelectedOptionKey.value = options[0].key;
                selectAnswer(options[0].key);
                return;
            }

            if (['arrowright', 'arrowdown', 's', 'd'].includes(shortcutKey)) {
                event.preventDefault();
                keyboardSelectedOptionKey.value = options[1].key;
                selectAnswer(options[1].key);
                return;
            }
        }

        if (options.length === 3) {
            const optionIndex = ['arrowleft', 'a'].includes(shortcutKey)
                ? 0
                : ['arrowup', 'arrowdown', 'w', 's'].includes(shortcutKey)
                    ? 1
                    : ['arrowright', 'd'].includes(shortcutKey)
                        ? 2
                        : undefined;

            if (optionIndex !== undefined) {
                event.preventDefault();
                keyboardSelectedOptionKey.value = options[optionIndex].key;
                selectAnswer(options[optionIndex].key);
                return;
            }
        }

        if (['arrowleft', 'arrowup', 'a', 'w'].includes(shortcutKey)) {
            event.preventDefault();
            moveKeyboardSelection('previous');
            return;
        }

        if (['arrowright', 'arrowdown', 's', 'd'].includes(shortcutKey)) {
            event.preventDefault();
            moveKeyboardSelection('next');
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            const selectedOptionKey = keyboardSelectedOptionKey.value ?? options[0]?.key ?? null;

            if (selectedOptionKey) {
                selectAnswer(selectedOptionKey);
            }
        }

        return;
    }

    if (
        currentAnswerResult.value
        && !primaryActionDisabled.value
        && (shortcutKey === 'enter' || shortcutKey === ' ' || shortcutKey === 'arrowright' || shortcutKey === 'd')
    ) {
        event.preventDefault();
        handlePrimaryAction();
    }
};

type AnswerOptionVisualState = 'idle' | 'selected' | 'correct' | 'incorrect';

const answerOptionVisualState = (optionKey: string): AnswerOptionVisualState => {
    const isSelected = selectedAnswerKey.value === optionKey;

    if (!shouldShowInstantAnswerFeedback.value) {
        return isSelected ? 'selected' : 'idle';
    }

    const answerState = currentAnswerResult.value?.is_correct;

    if (isSelected && answerState === true) {
        return 'correct';
    }

    if (isSelected && answerState === false) {
        return 'incorrect';
    }

    if (answerState === false && correctAnswerKey.value === optionKey) {
        return 'correct';
    }

    return isSelected ? 'selected' : 'idle';
};

const answerOptionStateClass = (optionKey: string) => {
    const visualState = answerOptionVisualState(optionKey);
    const isSelected = selectedAnswerKey.value === optionKey;
    const isKeyboardSelected = keyboardSelectedOptionKey.value === optionKey;

    if (!shouldShowInstantAnswerFeedback.value) {
        if (visualState === 'idle' && isKeyboardSelected && canSelectAnswer.value) {
            return 'bg-white/90 text-neutral-950 ring-2 ring-neutral-500 shadow-[0_8px_18px_rgba(36,33,28,0.08)]';
        }

        return visualState === 'selected'
            ? 'bg-white/92 text-neutral-950 ring-2 ring-neutral-900 shadow-[0_8px_18px_rgba(36,33,28,0.08)]'
            : 'bg-white/64 text-neutral-900 shadow-[0_6px_16px_rgba(36,33,28,0.04)] hover:bg-white/84 hover:shadow-[0_8px_18px_rgba(36,33,28,0.06)]';
    }

    if (visualState === 'correct') {
        return isSelected
            ? 'bg-[#edf4ef]/92 text-[#163222] ring-2 ring-[#406a54] shadow-[0_8px_18px_rgba(25,64,41,0.08)]'
            : 'bg-[#edf4ef]/86 text-[#163222] shadow-[0_8px_18px_rgba(25,64,41,0.06)]';
    }

    if (visualState === 'incorrect') {
        return 'bg-[#f7eeee]/94 text-[#4e2727] ring-2 ring-[#8a4949] shadow-[0_8px_18px_rgba(97,34,34,0.08)]';
    }

    return visualState === 'selected'
        ? 'bg-white/92 text-neutral-950 ring-2 ring-neutral-900 shadow-[0_8px_18px_rgba(36,33,28,0.08)]'
        : 'bg-white/64 text-neutral-900 shadow-[0_6px_16px_rgba(36,33,28,0.04)] hover:bg-white/84 hover:shadow-[0_8px_18px_rgba(36,33,28,0.06)]';
};

const examLikeAnswerOptionClass = (optionKey: string) => {
    const visualState = answerOptionVisualState(optionKey);
    const isSelected = selectedAnswerKey.value === optionKey;

    if (!shouldShowInstantAnswerFeedback.value) {
        if (usesInstantFeedbackMode.value && visualState === 'selected') {
            return 'border-[#cbd5e1] bg-[#f8fafc] text-[#334155] shadow-[0_8px_18px_rgba(15,23,42,0.06)]';
        }

        return visualState === 'selected'
            ? 'border-[#b8c6d2] bg-[#f7fafc] text-[#263746] shadow-[0_6px_14px_rgba(15,23,42,0.045)]'
            : 'border-[#d1d5db] bg-white text-[#111827] hover:border-[#9ca3af] hover:bg-[#fafafa]';
    }

    if (visualState === 'correct') {
        return isSelected
            ? 'border-[#93b6a0] bg-[#edf4ef] text-[#163222]'
            : 'border-[#b8d0c0] bg-[#f1f7f3] text-[#163222]';
    }

    if (visualState === 'incorrect') {
        return 'border-[#d9b7b7] bg-[#fbf1f1] text-[#612d2d]';
    }

    return visualState === 'selected'
        ? 'border-[#cbd5e1] bg-[#f8fafc] text-[#334155] shadow-[0_8px_18px_rgba(15,23,42,0.06)]'
        : 'border-[#d1d5db] bg-white text-[#111827] hover:border-[#9ca3af] hover:bg-[#fafafa]';
};

const examLikeAnswerLabelClass = (optionKey: string) => {
    const visualState = answerOptionVisualState(optionKey);

    if (visualState === 'correct') {
        return 'border-[#2f7d4a] bg-[#2f7d4a]';
    }

    if (visualState === 'incorrect') {
        return 'border-[#b94a48] bg-[#b94a48]';
    }

    return 'border-[#d1d5db] bg-[#0071ce]';
};

const sessionAnswersRoute = computed(() =>
    props.publicDemo?.routes.answer
        ?? (isLocalLearningMode.value
        ? route('study-sessions.current.answers.store')
        : route('study-sessions.answers.store', props.session.id)),
);

const sessionCompleteRoute = computed(() =>
    props.publicDemo?.routes.complete
        ?? (isLocalLearningMode.value
        ? route('study-sessions.current.complete')
        : route('study-sessions.complete', props.session.id)),
);
const sessionQuestionBatchRoute = computed(() =>
    props.publicDemo?.routes.questions
        ?? (isLocalLearningMode.value
        ? route('study-sessions.current.questions.index')
        : route('study-sessions.questions.index', props.session.id)),
);
const questionCountForTopicStatus = (
    topic: GroupOption | null,
    questionStatus: string,
) => {
    if (!topic) {
        if (questionStatus === 'incorrect') {
            return displayIncorrectAnswersCount.value;
        }

        return props.sessionFilters.question_count > 0
            ? props.sessionFilters.question_count
            : displayProgress.value.total;
    }

    if (questionStatus === 'all') {
        return topic.questions_count ?? 0;
    }

    const topicStatusCount = topic.counts[questionStatus] ?? 0;

    if (questionStatus === 'incorrect' && topic.id === activeSessionTopicId.value) {
        return Math.max(topicStatusCount, displayIncorrectAnswersCount.value);
    }

    return topicStatusCount;
};
const currentStatusQuestionCountForTopic = (topic: GroupOption) =>
    questionCountForTopicStatus(topic, props.sessionFilters.question_status);
const isCurrentTopicOption = (topic: GroupOption) =>
    topic.id === activeSessionTopicId.value;
watch(showSessionRoadmap, async (visible) => {
    if (!visible) {
        isSessionRoadmapLegendOpen.value = false;
        canScrollSessionRoadmapBackward.value = false;
        canScrollSessionRoadmapForward.value = false;
        hasAutoFocusedSessionRoadmap.value = false;
        return;
    }

    await autoFocusSessionRoadmap();
    syncSessionRoadmapScrollState();
});

watch(showCompletionProgressPanel, async (visible) => {
    if (!visible) {
        hasAutoFocusedCompletionProgressPanel.value = false;
        return;
    }

    await autoFocusCompletionProgressPanel();
});

watch([() => sessionRoadmap.value.length, sessionRoadmapFocusTopicId], async () => {
    if (!showSessionRoadmap.value) {
        return;
    }

    await autoFocusSessionRoadmap();
    syncSessionRoadmapScrollState();
});

watch([() => completionProgressPanelItems.value.length, activeSessionTopicId], async () => {
    if (!showCompletionProgressPanel.value) {
        return;
    }

    hasAutoFocusedCompletionProgressPanel.value = false;
    await autoFocusCompletionProgressPanel();
});
const canUseCompletionActions = computed(() =>
    !followUpActionInFlight.value
    && !completeForm.processing
    && !pjmSessionForm.processing
    && !topicSwitchInFlight.value
    && !sessionExpired.value
    && syncError.value === null,
);
const showSaveAnswerErrorModal = computed(() =>
    Boolean(syncError.value)
    && answerSyncFailedQuestionId.value === activeQuestion.value?.id,
);
const showInlineSyncError = computed(() =>
    Boolean(syncError.value) && !showSaveAnswerErrorModal.value,
);
const reviewIncorrectQuestionCount = computed(() =>
    questionCountForTopicStatus(activeSessionTopic.value, 'incorrect'),
);
const restartTopicQuestionCount = computed(() =>
    questionCountForTopicStatus(activeSessionTopic.value, 'all'),
);
const nextTopicQuestionCount = computed(() =>
    questionCountForTopicStatus(nextSessionTopic.value, 'all'),
);

const startTopicSession = ({
    topic,
    questionStatus,
    questionCount,
    questionScope = props.sessionFilters.question_scope,
    requestSource,
    errorMessage,
    closePickerFirst,
}: {
    topic: GroupOption;
    questionStatus: SessionFilters['question_status'];
    questionCount: number;
    questionScope?: SessionFilters['question_scope'];
    requestSource: 'topic' | 'topic-next';
    errorMessage: string;
    closePickerFirst: boolean;
}) => {
    if (
        sessionExpired.value
        || !props.session.license_category_id
        || questionCount <= 0
    ) {
        return;
    }

    if (closePickerFirst) {
        closeTopicPicker();
    }

    clearAutoAdvanceTimeout();
    syncError.value = null;
    topicSwitchInFlight.value = true;

    switchTopicForm.license_category_id = props.session.license_category_id;
    switchTopicForm.ui_shell = props.session.ui_shell === 'exam_like' ? 'exam_like' : 'zen';
    switchTopicForm.question_topic_id = topic.id;
    switchTopicForm.question_scope = questionScope;
    switchTopicForm.question_status = questionStatus;
    switchTopicForm.randomize_order = props.sessionFilters.randomize_order;
    switchTopicForm.question_count = questionCount;

    void apiClient.post<{ redirect?: string | null }>(
        route('study-sessions.store'),
        {
            license_category_id: switchTopicForm.license_category_id,
            mode: switchTopicForm.mode,
            ui_shell: switchTopicForm.ui_shell,
            question_topic_id: switchTopicForm.question_topic_id,
            question_scope: switchTopicForm.question_scope,
            question_status: switchTopicForm.question_status,
            randomize_order: switchTopicForm.randomize_order,
            question_count: switchTopicForm.question_count,
        },
        {
            headers: {
                'X-Study-Session-Switch': requestSource,
            },
        },
    ).then((payload) => {
        const redirectUrl = payload?.redirect ?? route('study-sessions.current');

        if (typeof window !== 'undefined') {
            window.location.assign(redirectUrl);
        }
    }).catch((error) => {
        if (!handleSessionExpiryError(error)) {
            syncError.value = errorMessage;
        }
    }).finally(() => {
        topicSwitchInFlight.value = false;
    });
};

const startFollowUpSession = (questionStatus: SessionFilters['question_status']) => {
    if (!props.session.license_category_id || !canUseCompletionActions.value) {
        return;
    }

    const questionCount = questionCountForTopicStatus(activeSessionTopic.value, questionStatus);

    if (questionCount <= 0) {
        return;
    }

    followUpSessionForm.defaults({
        license_category_id: props.session.license_category_id,
        mode: 'learn',
        ui_shell: props.session.ui_shell === 'exam_like' ? 'exam_like' : 'zen',
        question_topic_id: activeSessionTopic.value?.id ?? props.sessionFilters.question_topic_id,
        question_scope: props.sessionFilters.question_scope,
        question_status: questionStatus,
        randomize_order: props.sessionFilters.randomize_order,
        question_count: questionCount,
    });

    followUpSessionForm.license_category_id = props.session.license_category_id;
    followUpSessionForm.mode = 'learn';
    followUpSessionForm.ui_shell = props.session.ui_shell === 'exam_like' ? 'exam_like' : 'zen';
    followUpSessionForm.question_topic_id = activeSessionTopic.value?.id ?? props.sessionFilters.question_topic_id;
    followUpSessionForm.question_scope = props.sessionFilters.question_scope;
    followUpSessionForm.question_status = questionStatus;
    followUpSessionForm.randomize_order = props.sessionFilters.randomize_order;
    followUpSessionForm.question_count = questionCount;

    syncError.value = null;
    followUpActionInFlight.value = true;

    void apiClient.post<{ redirect?: string | null }>(
        route('study-sessions.store'),
        {
            license_category_id: followUpSessionForm.license_category_id,
            mode: followUpSessionForm.mode,
            ui_shell: followUpSessionForm.ui_shell,
            question_topic_id: followUpSessionForm.question_topic_id,
            question_scope: followUpSessionForm.question_scope,
            question_status: followUpSessionForm.question_status,
            randomize_order: followUpSessionForm.randomize_order,
            question_count: followUpSessionForm.question_count,
        },
        {
            headers: {
                'X-Study-Session-Switch': 'follow-up',
            },
        },
    ).then((payload) => {
        const redirectUrl = payload?.redirect ?? route('study-sessions.current');

        if (typeof window !== 'undefined') {
            window.location.assign(redirectUrl);
        }
    }).catch((error) => {
        if (!handleSessionExpiryError(error)) {
            syncError.value = isApiClientError(error)
                ? ((error.data as { errors?: { license_category_id?: string[] } })?.errors?.license_category_id?.[0]
                    ?? 'Nie udało się rozpocząć kolejnej sesji. Spróbuj ponownie za chwilę.')
                : 'Nie udało się rozpocząć kolejnej sesji. Spróbuj ponownie za chwilę.';
        }
    }).finally(() => {
        followUpActionInFlight.value = false;
    });
};

const startPjmFollowUpSession = (
    questionStatus: SessionFilters['question_status'],
    questionTopicId: number | null = null,
    questionCountOverride: number | null = null,
    questionCountStrategy: PjmQuestionCountStrategy = 'fixed',
) => {
    if (!isPjmMode.value || !canUseCompletionActions.value) {
        return;
    }

    const useTopicRemaining = questionCountStrategy === 'topic_remaining' && questionTopicId !== null;
    const questionCount = questionCountOverride
        ?? (questionStatus === 'incorrect'
            ? Math.max(pjmIncorrectQuestionCount.value || displayIncorrectAnswersCount.value, 1)
            : (questionStatus === 'unanswered'
                ? pjmContinuationQuestionCount.value
                : 40));
    const randomizeOrder = questionStatus === 'all' && questionTopicId === null;
    const submittedQuestionCount = useTopicRemaining ? 1 : Math.min(questionCount, 40);

    pjmSessionForm.defaults({
        question_topic_id: questionTopicId,
        question_count: submittedQuestionCount,
        question_count_strategy: useTopicRemaining ? 'topic_remaining' : 'fixed',
        question_status: questionStatus,
        randomize_order: randomizeOrder,
    });

    pjmSessionForm.question_topic_id = questionTopicId;
    pjmSessionForm.question_count = submittedQuestionCount;
    pjmSessionForm.question_count_strategy = useTopicRemaining ? 'topic_remaining' : 'fixed';
    pjmSessionForm.question_status = questionStatus;
    pjmSessionForm.randomize_order = randomizeOrder;
    syncError.value = null;

    pjmSessionForm.post(route('session.pjm.store'), {
        preserveScroll: false,
        preserveState: false,
        onError: () => {
            syncError.value = 'Nie udało się rozpocząć kolejnej sesji PJM. Spróbuj ponownie za chwilę.';
        },
    });
};

const startPjmPrimarySession = () => {
    if (hasPjmUnansweredQuestions.value) {
        startPjmFollowUpSession(
            'unanswered',
            pjmCurrentTopic.value?.id ?? null,
            pjmContinuationQuestionCount.value,
            'topic_remaining',
        );

        return;
    }

    startPjmFollowUpSession('all');
};

const scrollToCompletionResults = () => {
    completionResultsRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const toggleCorrectCompletionResults = async () => {
    showCorrectCompletionResults.value = !showCorrectCompletionResults.value;

    if (showCorrectCompletionResults.value) {
        await nextTick();
        scrollToCompletionResults();
    }
};

const toggleReviewTrainerQuestionDetails = async () => {
    showReviewTrainerQuestionDetails.value = !showReviewTrainerQuestionDetails.value;

    if (showReviewTrainerQuestionDetails.value) {
        await nextTick();
        scrollToCompletionResults();
    }
};

const switchTopicSession = (topic: GroupOption) => {
    if (!canSwitchTopics.value || !props.session.license_category_id || isCurrentTopicOption(topic)) {
        return;
    }

    const questionCount = currentStatusQuestionCountForTopic(topic);

    if (questionCount <= 0) {
        return;
    }

    startTopicSession({
        topic,
        questionStatus: props.sessionFilters.question_status,
        questionCount,
        requestSource: 'topic',
        errorMessage: 'Nie udało się przełączyć działu. Spróbuj ponownie za chwilę.',
        closePickerFirst: true,
    });
};

const startNextTopicSession = () => {
    if (!canUseCompletionActions.value || !nextSessionTopic.value) {
        return;
    }

    startTopicSession({
        topic: nextSessionTopic.value,
        questionStatus: 'all',
        questionCount: nextTopicQuestionCount.value,
        requestSource: 'topic-next',
        errorMessage: 'Nie udało się uruchomić następnego działu. Spróbuj ponownie za chwilę.',
        closePickerFirst: false,
    });
};

const completionProgressTopicOption = (item: CompletionProgressPanelItem) =>
    allSessionTopicOptions.value.find((topic) => topic.id === item.id) ?? null;

const completionProgressTopicQuestionCount = (item: CompletionProgressPanelItem) =>
    questionCountForTopicStatus(completionProgressTopicOption(item), 'all');

const canStartCompletionProgressTopic = (item: CompletionProgressPanelItem) =>
    canUseTopicPicker.value
    && canUseCompletionActions.value
    && completionProgressTopicQuestionCount(item) > 0;

const completionProgressItemTitle = (item: CompletionProgressPanelItem) =>
    canStartCompletionProgressTopic(item)
        ? `Rozpocznij dział: ${item.label}`
        : item.label;

const startCompletionProgressTopicSession = (item: CompletionProgressPanelItem) => {
    if (!canStartCompletionProgressTopic(item)) {
        return;
    }

    const topic = completionProgressTopicOption(item);

    if (!topic) {
        return;
    }

    startTopicSession({
        topic,
        questionStatus: 'all',
        questionCount: completionProgressTopicQuestionCount(item),
        questionScope: 'all',
        requestSource: 'topic',
        errorMessage: 'Nie udało się uruchomić wybranego działu. Spróbuj ponownie za chwilę.',
        closePickerFirst: false,
    });
};

const displayImageUrl = (media: QuestionMedia, preferFull = false) =>
    resolveStudyQuestionImageUrl(media, preferFull);

const resultMediaPreviewUrl = (media: QuestionMedia) =>
    resolveResultMediaPreviewUrl(media);

const prepareResultVideoPreview = (event: Event) => {
    const video = event.currentTarget;

    if (!(video instanceof HTMLVideoElement)) {
        return;
    }

    if (video.dataset.resultPreviewPrepared === '1') {
        return;
    }

    const duration = Number.isFinite(video.duration) ? video.duration : 0;
    const previewTime = Math.max(duration - 0.2, 0);

    video.dataset.resultPreviewPrepared = '1';
    video.muted = true;

    try {
        video.currentTime = previewTime;
    } catch {
        video.pause();
    }
};

const freezeResultVideoPreview = (event: Event) => {
    const video = event.currentTarget;

    if (!(video instanceof HTMLVideoElement)) {
        return;
    }

    video.pause();
};

const cacheQuestion = (question: CurrentQuestion | null) => {
    if (!question) {
        return;
    }

    const existingQuestion = questionCache.value[question.id] ?? null;

    if (!usesFrozenPublicDemo.value || !existingQuestion) {
        questionCache.value[question.id] = question;
        return;
    }

    questionCache.value[question.id] = {
        ...question,
        correct_answer: question.correct_answer ?? existingQuestion.correct_answer,
        explanation: question.explanation ?? existingQuestion.explanation,
        explanation_asset: question.explanation_asset ?? existingQuestion.explanation_asset,
        explanation_sign_references: question.explanation_sign_references ?? existingQuestion.explanation_sign_references ?? [],
        explanation_annotations: question.explanation_annotations.length > 0
            ? question.explanation_annotations
            : existingQuestion.explanation_annotations,
    };
};

const cacheQuestions = (questions: CurrentQuestion[]) => {
    questions.forEach((question) => cacheQuestion(question));
};

const primeQuestionMedia = (question: CurrentQuestion | null) => {
    if (!question || typeof window === 'undefined') {
        return;
    }

    question.media.forEach((media) => {
        const source = media.kind === 'video'
            ? media.poster_url
            : displayImageUrl(media);

        if (!source) {
            return;
        }

        const image = new window.Image();
        image.decoding = 'async';
        image.src = source;
    });
};

const questionIndexById = (questionId: number | null) => {
    if (questionId === null) {
        return -1;
    }

    return props.questionIds.indexOf(questionId);
};

const questionIndexFor = (questionId: number | null = activeQuestion.value?.id ?? null) => {
    if (questionId !== null) {
        const explicitIndex = questionIndexById(questionId);

        if (explicitIndex >= 0) {
            return explicitIndex;
        }
    }

    if (currentQuestionNumberState.value) {
        return currentQuestionNumberState.value - 1;
    }

    return questionIndexById(activeQuestion.value?.id ?? null);
};

const resolveNextQuestionMeta = (questionId: number | null = activeQuestion.value?.id ?? null) => {
    const currentIndex = questionIndexFor(questionId);

    if (currentIndex < 0) {
        return null;
    }

    if (isReviewTrainerMode.value) {
        const searchOrder = [
            ...props.questionIds.slice(currentIndex + 1),
            ...props.questionIds.slice(0, currentIndex),
        ];
        const nextQuestionId = searchOrder.find((candidateQuestionId) => {
            const result = localResults.value[candidateQuestionId] ?? null;

            return result === null || !isAnsweredResult(result);
        }) ?? null;

        if (!nextQuestionId) {
            return null;
        }

        return {
            id: nextQuestionId,
            number: questionIndexById(nextQuestionId) + 1,
        };
    }

    const nextQuestionId = props.questionIds[currentIndex + 1] ?? null;

    if (!nextQuestionId) {
        return null;
    }

    return {
        id: nextQuestionId,
        number: currentIndex + 2,
    };
};

const resolvePreviousQuestionMeta = (questionId: number | null = activeQuestion.value?.id ?? null) => {
    const currentIndex = questionIndexFor(questionId);

    if (currentIndex <= 0) {
        return null;
    }

    const previousQuestionId = props.questionIds[currentIndex - 1] ?? null;

    if (!previousQuestionId) {
        return null;
    }

    return {
        id: previousQuestionId,
        number: currentIndex,
    };
};

const nextQuestionIdsToFetch = (questionId: number | null = activeQuestion.value?.id ?? null) => {
    const currentIndex = questionIndexFor(questionId);

    if (currentIndex < 0) {
        return [] as number[];
    }

    return props.questionIds
        .slice(currentIndex + 1, currentIndex + 1 + props.questionBatchSize)
        .filter((nextQuestionId) =>
            !questionCache.value[nextQuestionId]
            && !questionIdsBeingFetched.has(nextQuestionId),
        );
};

const syncPreparedNextQuestion = (questionId: number | null = activeQuestion.value?.id ?? null) => {
    if (!isLocalLearningMode.value || localSessionCompleted.value) {
        return;
    }

    const nextQuestionMeta = resolveNextQuestionMeta(questionId);

    if (!nextQuestionMeta) {
        preparedNextQuestion.value = null;
        preparedNextQuestionNumber.value = null;
        return;
    }

    const cachedQuestion = questionCache.value[nextQuestionMeta.id] ?? null;

    if (!cachedQuestion) {
        preparedNextQuestion.value = null;
        preparedNextQuestionNumber.value = null;
        return;
    }

    preparedNextQuestion.value = cachedQuestion;
    preparedNextQuestionNumber.value = nextQuestionMeta.number;
    primeQuestionMedia(cachedQuestion);
};

const activateQuestion = (
    question: CurrentQuestion,
    questionNumber: number | null,
    options: { clearPrepared?: boolean } = {},
) => {
    activeQuestionState.value = question;
    currentQuestionNumberState.value = questionNumber;
    syncError.value = null;
    questionTransitionInFlight.value = false;

    if (options.clearPrepared) {
        preparedNextQuestion.value = null;
        preparedNextQuestionNumber.value = null;
    }
};

const activatePreparedNextQuestion = (expectedQuestionId: number | null = null) => {
    const preparedQuestion = preparedNextQuestion.value;
    const preparedQuestionNumber = preparedNextQuestionNumber.value;

    if (!preparedQuestion || preparedQuestionNumber === null) {
        return false;
    }

    if (expectedQuestionId !== null && preparedQuestion.id !== expectedQuestionId) {
        return false;
    }

    activateQuestion(preparedQuestion, preparedQuestionNumber, { clearPrepared: true });

    return true;
};

const fetchQuestionBatch = async (questionIds: number[]) => {
    if (questionIds.length === 0) {
        if (pendingQuestionBatchRequest) {
            await pendingQuestionBatchRequest;
        }

        return;
    }

    questionIds.forEach((questionId) => questionIdsBeingFetched.add(questionId));
    questionBatchFetchInFlight.value = true;

    const request = (async () => {
        try {
            const params = new URLSearchParams();

            questionIds.forEach((questionId) => {
                params.append('ids[]', String(questionId));
            });

            const payload = await apiClient.get<{
                questions?: CurrentQuestion[];
            }>(`${sessionQuestionBatchRoute.value}?${params.toString()}`);

            cacheQuestions(payload.questions ?? []);
        } catch (error) {
            if (!handleSessionExpiryError(error)) {
                syncError.value = 'Nie udalo sie doladowac kolejnych pytan.';
            }
        } finally {
            questionIds.forEach((questionId) => questionIdsBeingFetched.delete(questionId));
            questionBatchFetchInFlight.value = questionIdsBeingFetched.size > 0;

            if (questionIdsBeingFetched.size === 0) {
                pendingQuestionBatchRequest = null;
            }
        }
    })();

    pendingQuestionBatchRequest = request;

    await request;
};

const prepareUpcomingQuestion = async (questionId: number | null = activeQuestion.value?.id ?? null) => {
    syncPreparedNextQuestion(questionId);

    if (props.questionPoolMode === 'full' || isReviewTrainerMode.value) {
        return;
    }

    const questionIds = nextQuestionIdsToFetch(questionId);

    if (questionIds.length === 0) {
        if (pendingQuestionBatchRequest) {
            await pendingQuestionBatchRequest;
            syncPreparedNextQuestion(questionId);
        }

        return;
    }

    await fetchQuestionBatch(questionIds);
    syncPreparedNextQuestion(questionId);
};

const scheduleUpcomingQuestionPreparation = (questionId: number) => {
    if (typeof window === 'undefined') {
        void prepareUpcomingQuestion(questionId);
        return;
    }

    window.requestAnimationFrame(() => {
        void prepareUpcomingQuestion(questionId);
    });
};

const registerVideoElement = (
    key: string,
    element: Element | ComponentPublicInstance | null,
) => {
    if (element === null) {
        videoElements.delete(key);

        return;
    }

    if (!(element instanceof HTMLVideoElement)) {
        return;
    }

    videoElements.set(key, element);
    element.defaultPlaybackRate = effectiveVideoPlaybackRate.value;
    element.playbackRate = effectiveVideoPlaybackRate.value;
};

const setVideoPlaybackState = (key: string, isPlaying: boolean) => {
    videoPlaybackState.value = {
        ...videoPlaybackState.value,
        [key]: isPlaying,
    };
};

const ensureVideoActivated = (key: string) => {
    videoActivatedState.value = {
        ...videoActivatedState.value,
        [key]: true,
    };
};

const pauseQuestionAudio = () => {
    questionAudioControlRef.value?.pause();
};

const stopQuestionAudio = () => {
    questionAudioControlRef.value?.stop();
};

const stopCorrectAnswerAudio = () => {
    const audio = correctAnswerAudioElement.value;

    if (!audio) {
        return;
    }

    audio.pause();
    audio.currentTime = 0;
};

const pauseAudibleQuestionVideos = () => {
    videoElements.forEach((video) => {
        if (!video.paused && !video.muted) {
            video.pause();
        }
    });
};

const shouldPlayCorrectAnswerAudioForResult = (result: ResultItem) =>
    Boolean(
        shouldAutoPlayCorrectAnswerAudioAfterMistake.value
        && usesInstantFeedbackMode.value
        && result.answer_kind === ANSWER_KIND_CHOICE
        && result.is_correct === false
    );

const correctAnswerAudioDelayMs = (result: ResultItem) => {
    if (!shouldPlayCorrectAnswerAudioForResult(result)) {
        return 0;
    }

    const durationSeconds = currentCorrectAnswerAudio.value?.duration_seconds;

    return Math.ceil((durationSeconds && durationSeconds > 0 ? durationSeconds : 1.8) * 1000) + 250;
};

const playCorrectAnswerAudio = async () => {
    const asset = currentCorrectAnswerAudio.value;
    const audio = correctAnswerAudioElement.value;

    if (!asset?.url || !audio || !shouldAutoPlayCorrectAnswerAudioAfterMistake.value) {
        return;
    }

    await nextTick();

    const sourceKey = asset.asset_key ?? asset.url;

    if (correctAnswerAudioSourceKey.value !== sourceKey) {
        audio.src = asset.url;
        audio.load();
        correctAnswerAudioSourceKey.value = sourceKey;
    }

    try {
        stopQuestionAudio();
        pauseAudibleQuestionVideos();
        audio.currentTime = 0;
        await audio.play();
    } catch {
        // Browser autoplay policy can still block this; the session continues normally.
    }
};

const requestVideoPlayback = (video: HTMLVideoElement) => {
    video.defaultPlaybackRate = effectiveVideoPlaybackRate.value;
    video.playbackRate = effectiveVideoPlaybackRate.value;

    if (!video.muted) {
        pauseQuestionAudio();
    }

    const playbackRequest = video.play();

    if (playbackRequest && typeof playbackRequest.catch === 'function') {
        playbackRequest.catch(() => {
            // Ignore browser playback rejections and keep the poster/overlay state intact.
        });
    }
};

const waitForVideoMetadata = async (video: HTMLVideoElement) => {
    if (Number.isFinite(video.duration) && video.duration > 0) {
        return;
    }

    await new Promise<void>((resolve) => {
        const complete = () => {
            video.removeEventListener('loadedmetadata', complete);
            video.removeEventListener('durationchange', complete);
            video.removeEventListener('error', complete);
            resolve();
        };

        video.addEventListener('loadedmetadata', complete, { once: true });
        video.addEventListener('durationchange', complete, { once: true });
        video.addEventListener('error', complete, { once: true });
    });
};

const seekVideoToEndingPreview = async (video: HTMLVideoElement) => {
    if (video.readyState === 0) {
        video.load();
    }

    await waitForVideoMetadata(video);

    const seekTo = Number.isFinite(video.duration) && video.duration > VIDEO_END_PREVIEW_SECONDS
        ? Math.max(video.duration - VIDEO_END_PREVIEW_SECONDS, 0)
        : 0;

    if (Number.isFinite(seekTo)) {
        video.currentTime = seekTo;
    }
};

const waitForVideoMount = async () => {
    await nextTick();

    if (typeof window === 'undefined') {
        return;
    }

    await new Promise<void>((resolve) => {
        window.requestAnimationFrame(() => resolve());
    });
};

const toggleVideoPlayback = async (
    key: string,
    options: { playFromBeginning?: boolean } = {},
) => {
    const mountedVideo = videoElements.get(key);

    if (mountedVideo && !mountedVideo.paused && !mountedVideo.ended) {
        mountedVideo.pause();
        return;
    }

    ensureVideoActivated(key);

    if (!mountedVideo) {
        await waitForVideoMount();
    }

    const video = videoElements.get(key);

    if (!video) {
        return;
    }

    try {
        if (options.playFromBeginning) {
            video.currentTime = 0;
        } else if (autoJumpToVideoEnding.value) {
            await seekVideoToEndingPreview(video);
        } else if (video.ended) {
            video.currentTime = 0;
        }

        if (video.readyState === 0) {
            video.load();
        }

        video.muted = autoPlayVideos.value;
        requestVideoPlayback(video);
    } catch {
        // Ignore playback errors caused by browser policies or pending source changes.
    }
};

const jumpToVideoEnding = async (key: string) => {
    ensureVideoActivated(key);

    await waitForVideoMount();

    const video = videoElements.get(key);

    if (!video) {
        return;
    }

    try {
        await seekVideoToEndingPreview(video);
        requestVideoPlayback(video);
    } catch {
        // Ignore seek/playback issues caused by source loading timing.
    }
};

const persistPreferences = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(
        SESSION_PREFERENCES_KEY,
        JSON.stringify({
            autoAdvance: autoAdvance.value,
            autoPlayVideos: autoPlayVideos.value,
            showQuestionAudioControl: showQuestionAudioControl.value,
            autoPlayQuestionAudio: autoPlayQuestionAudio.value,
            autoPlayCorrectAnswerAudio: autoPlayCorrectAnswerAudio.value,
            videoPlaybackRate: videoPlaybackRate.value,
            autoJumpToVideoEnding: autoJumpToVideoEnding.value,
            hintTimingPreference: hintTimingPreference.value,
            feedbackMode: feedbackMode.value,
            enableInlineBold: enableInlineBold.value,
            enableInlineColors: enableInlineColors.value,
            showVisualAnnotations: showVisualAnnotations.value,
        }),
    );
};

const applySessionPreferences = (preferences: SessionPreferences) => {
    feedbackMode.value = preferences.feedbackMode;
    autoAdvance.value = preferences.autoAdvance;
    autoPlayVideos.value = preferences.autoPlayVideos;
    showQuestionAudioControl.value = preferences.showQuestionAudioControl;
    autoPlayQuestionAudio.value = preferences.autoPlayQuestionAudio;
    autoPlayCorrectAnswerAudio.value = preferences.autoPlayCorrectAnswerAudio;
    autoJumpToVideoEnding.value = preferences.autoJumpToVideoEnding;
    hintTimingPreference.value = preferences.hintTimingPreference;
    videoPlaybackRate.value = preferences.videoPlaybackRate;
    enableInlineBold.value = preferences.enableInlineBold;
    enableInlineColors.value = preferences.enableInlineColors;
    showVisualAnnotations.value = preferences.showVisualAnnotations;
};

const loadPreferences = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const rawPreferences = window.localStorage.getItem(SESSION_PREFERENCES_KEY);

    if (!rawPreferences) {
        applySessionPreferences(FIRST_VISIT_SESSION_PREFERENCES);
        persistPreferences();
        return;
    }

    try {
        const parsedPreferences = JSON.parse(rawPreferences) as {
            autoAdvance?: boolean;
            autoPlayVideos?: boolean;
            showQuestionAudioControl?: boolean;
            autoPlayQuestionAudio?: boolean;
            autoPlayCorrectAnswerAudio?: boolean;
            videoPlaybackRate?: number;
            autoJumpToVideoEnding?: boolean;
            hintTimingPreference?: HintTimingPreference;
            feedbackMode?: FeedbackMode;
            enableInlineBold?: boolean;
            enableInlineColors?: boolean;
            showVisualAnnotations?: boolean;
        };

        const parsedMode = ['instant', 'instant_explanation', 'review'].includes(parsedPreferences.feedbackMode ?? '')
            ? parsedPreferences.feedbackMode ?? FIRST_VISIT_SESSION_PREFERENCES.feedbackMode
            : FIRST_VISIT_SESSION_PREFERENCES.feedbackMode;

        applySessionPreferences({
            feedbackMode: parsedMode,
            autoAdvance: parsedPreferences.autoAdvance === undefined
                ? defaultAutoAdvanceForFeedbackMode(parsedMode)
                : parsedPreferences.autoAdvance === true,
            autoPlayVideos: parsedPreferences.autoPlayVideos === true,
            showQuestionAudioControl: parsedPreferences.showQuestionAudioControl === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.showQuestionAudioControl
                : parsedPreferences.showQuestionAudioControl === true,
            autoPlayQuestionAudio: parsedPreferences.autoPlayQuestionAudio === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.autoPlayQuestionAudio
                : parsedPreferences.autoPlayQuestionAudio === true,
            autoPlayCorrectAnswerAudio: parsedPreferences.autoPlayCorrectAnswerAudio === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.autoPlayCorrectAnswerAudio
                : parsedPreferences.autoPlayCorrectAnswerAudio === true,
            autoJumpToVideoEnding: parsedPreferences.autoJumpToVideoEnding === true,
            hintTimingPreference: isHintTimingPreference(parsedPreferences.hintTimingPreference)
                ? parsedPreferences.hintTimingPreference
                : FIRST_VISIT_SESSION_PREFERENCES.hintTimingPreference,
            videoPlaybackRate: isVideoPlaybackRate(parsedPreferences.videoPlaybackRate)
                ? parsedPreferences.videoPlaybackRate
                : FIRST_VISIT_SESSION_PREFERENCES.videoPlaybackRate,
            enableInlineBold: parsedPreferences.enableInlineBold === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.enableInlineBold
                : parsedPreferences.enableInlineBold === true,
            enableInlineColors: parsedPreferences.enableInlineColors === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.enableInlineColors
                : parsedPreferences.enableInlineColors === true,
            showVisualAnnotations: parsedPreferences.showVisualAnnotations === undefined
                ? FIRST_VISIT_SESSION_PREFERENCES.showVisualAnnotations
                : parsedPreferences.showVisualAnnotations === true,
        });
    } catch {
        window.localStorage.removeItem(SESSION_PREFERENCES_KEY);
        applySessionPreferences(FIRST_VISIT_SESSION_PREFERENCES);
        persistPreferences();
    }
};

const persistVisualExplanationsPreference = () => {
    if (typeof window === 'undefined') {
        return;
    }

    if (!visualExplanationsStorageKey.value) {
        return;
    }

    window.localStorage.setItem(
        visualExplanationsStorageKey.value,
        JSON.stringify(visualExplanationsMode.value),
    );
};

const restoreVisualExplanationsPreference = () => {
    visualExplanationsMode.value = globalVisualExplanationsMode.value;

    if (typeof window === 'undefined') {
        return;
    }

    if (!visualExplanationsStorageKey.value) {
        return;
    }

    const rawPreference = window.localStorage.getItem(visualExplanationsStorageKey.value);

    if (rawPreference === null) {
        return;
    }

    try {
        const parsedPreference = JSON.parse(rawPreference);

        if (isVisualExplanationsMode(parsedPreference)) {
            visualExplanationsMode.value = parsedPreference;
            return;
        }

        if (typeof parsedPreference === 'boolean') {
            visualExplanationsMode.value = parsedPreference
                ? globalVisualExplanationsMode.value
                : 'off';
            return;
        }
    } catch {
        // Ignore malformed stored values and reset below.
    }

    window.localStorage.removeItem(visualExplanationsStorageKey.value);
};

const playVideosIfEnabled = async () => {
    if (!autoPlayVideos.value) {
        return;
    }

    const question = activeQuestion.value;

    if (!question) {
        return;
    }

    const activeMediaKeys = question.media
        .map((media, index) =>
            media.kind === 'video' ? activeVideoRefKey(index) : null,
        )
        .filter((key): key is string => key !== null);

    if (activeMediaKeys.length === 0) {
        return;
    }

    activeMediaKeys.forEach((key) => ensureVideoActivated(key));
    await waitForVideoMount();

    if (!autoPlayVideos.value || activeQuestion.value?.id !== question.id) {
        return;
    }

    for (const key of activeMediaKeys) {
        const video = videoElements.get(key);

        if (!video) {
            continue;
        }

        try {
            video.muted = true;
            if (autoJumpToVideoEnding.value) {
                await seekVideoToEndingPreview(video);
            } else {
                video.currentTime = 0;
            }
            requestVideoPlayback(video);
        } catch {
            // Browser autoplay policies may block playback until a user gesture occurs.
        }
    }
};

const scheduleVideoPlayback = (question: CurrentQuestion | null = activeQuestion.value) => {
    if (!autoPlayVideos.value || !question) {
        return;
    }

    if (!question.media.some((media) => media.kind === 'video')) {
        return;
    }

    const scheduledQuestionId = question.id;

    if (typeof window === 'undefined') {
        void playVideosIfEnabled();
        return;
    }

    window.requestAnimationFrame(() => {
        if (activeQuestion.value?.id !== scheduledQuestionId) {
            return;
        }

        void playVideosIfEnabled();
    });
};

const registerPendingSyncRequest = (promise: Promise<void>) => {
    pendingSyncRequests.add(promise);

    promise.finally(() => {
        pendingSyncRequests.delete(promise);
    });
};

const enqueueAnswerSync = (task: () => Promise<void>) => {
    if (sessionExpired.value) {
        return Promise.reject(new Error('session_expired'));
    }

    pendingAnswerSyncCount.value += 1;
    learningSyncInFlight.value = true;

    const request = answerSyncQueue
        .catch(() => {})
        .then(() => {
            if (sessionExpired.value) {
                throw new Error('session_expired');
            }

            return task();
        })
        .finally(() => {
            pendingAnswerSyncCount.value = Math.max(pendingAnswerSyncCount.value - 1, 0);
            learningSyncInFlight.value = pendingAnswerSyncCount.value > 0;
        });

    answerSyncQueue = request;
    registerPendingSyncRequest(request);

    return request;
};

const waitForPendingSyncRequests = async () => {
    if (pendingSyncRequests.size === 0) {
        return;
    }

    await Promise.allSettled(Array.from(pendingSyncRequests));
};

const calculateScorePercent = (correctAnswersCount: number, totalQuestionsCount: number) => {
    if (totalQuestionsCount <= 0) {
        return null;
    }

    return Math.round((correctAnswersCount / totalQuestionsCount) * 10000) / 100;
};

const publicDemoCompletionAnswers = () =>
    props.questionIds
        .map((questionId) => localResults.value[questionId] ?? null)
        .filter((result): result is ResultItem =>
            Boolean(result && result.answer_kind === ANSWER_KIND_CHOICE && result.selected_answer !== null),
        )
        .map((result) => ({
            question_id: result.id,
            selected_answer: result.selected_answer,
            answer_kind: result.answer_kind,
            response_time_ms: result.response_time_ms,
        }));

const applyOptimisticAnswerProgress = (result: ResultItem) => {
    progressState.value = {
        ...progressState.value,
        answered: Math.min(progressState.value.answered + 1, progressState.value.total),
        remaining: Math.max(progressState.value.remaining - 1, 0),
    };

    const nextCorrectAnswersCount = sessionState.value.correct_answers_count
        + (result.is_correct ? 1 : 0);

    sessionState.value = {
        ...sessionState.value,
        correct_answers_count: nextCorrectAnswersCount,
        score_percent: calculateScorePercent(
            nextCorrectAnswersCount,
            sessionState.value.total_questions_count,
        ),
    };
};

const syncCompletion = async () => {
    if (sessionExpired.value) {
        return;
    }

    await waitForPendingSyncRequests();

    if (sessionExpired.value) {
        return;
    }

    try {
        const payload = await apiClient.post<{
            session?: Partial<SessionSummary>;
            redirect?: string | null;
            topicCompletionOverview?: TopicCompletionOverview | null;
            reviewCompletion?: ReviewCompletionSummary | null;
        }>(
            sessionCompleteRoute.value,
            usesFrozenPublicDemo.value
                ? { answers: publicDemoCompletionAnswers() }
                : {},
        );

        sessionState.value = {
            ...sessionState.value,
            ...(payload.session ?? {}),
            status: payload.session?.status ?? 'completed',
        };
        syncError.value = null;
        if (payload.reviewCompletion !== undefined) {
            reviewCompletionState.value = payload.reviewCompletion;
        }
        if (payload.topicCompletionOverview !== undefined) {
            topicCompletionOverviewState.value = payload.topicCompletionOverview;
        }

        if (isPublicDemoMode.value) {
            if (typeof window !== 'undefined' && payload.redirect) {
                window.history.replaceState(window.history.state, '', payload.redirect);
            }

            return;
        }

        if (typeof window !== 'undefined') {
            window.location.assign(
                payload.redirect
                    ?? props.publicDemo?.routes.landing
                    ?? route('study-sessions.show', sessionState.value.id),
            );
        }
    } catch (error) {
        if (!handleSessionExpiryError(error)) {
            syncError.value = 'Nie udalo sie zsynchronizowac zakonczenia sesji.';
        }
    }
};

const buildLocalResult = (
    question: CurrentQuestion,
    answerKey: string | null,
    answerKind: StudySessionAnswerKind = ANSWER_KIND_CHOICE,
): ResultItem => {
    const responseTimeMs = presentedAt.value
        ? Math.max(Date.now() - presentedAt.value, 0)
        : null;

    const normalizedAnswer = answerKey?.toUpperCase() ?? null;
    const normalizedCorrectAnswer = question.correct_answer?.toUpperCase() ?? null;
    const selectedOption = normalizedAnswer
        ? question.options.find((option) => option.label === normalizedAnswer)
        : null;
    const correctOption = question.options.find((option) => option.label === normalizedCorrectAnswer);

    return {
        id: question.id,
        sequence_number: props.questionIds.indexOf(question.id) + 1,
        prompt: question.prompt,
        explanation: question.explanation,
        correct_answer: normalizedCorrectAnswer,
        correct_answer_text: correctOption?.text ?? null,
        selected_answer: answerKind === ANSWER_KIND_CHOICE ? normalizedAnswer : null,
        answer_kind: answerKind,
        selected_answer_text: selectedOption?.text ?? null,
        is_correct: answerKind === ANSWER_KIND_UNKNOWN
            ? false
            : normalizedCorrectAnswer && normalizedAnswer
            ? normalizedAnswer === normalizedCorrectAnswer
            : null,
        response_time_ms: responseTimeMs,
        topic: question.topic,
        media: question.media,
        sign_language_assets: question.sign_language_assets ?? [],
        explanation_asset: question.explanation_asset,
        explanation_sign_references: question.explanation_sign_references ?? [],
        explanation_annotations: question.explanation_annotations,
    };
};

const hasResultExplanationContent = (result: ResultItem) =>
    hasOnDemandExplanationContent({
        explanationText: result.explanation,
        explanationAssetBody: result.explanation_asset?.body ?? null,
        explanationAssetImageUrl: result.explanation_asset?.image_url ?? null,
    });

const shouldShowAutomaticExplanationForResult = (result: ResultItem) =>
    Boolean(
        usesExplanationFeedbackMode.value
        && result.is_correct === false
        && (!isReviewTrainerMode.value || hasResultExplanationContent(result))
    );

const persistLocalAnswer = (
    question: CurrentQuestion,
    answerKey: string | null,
    answerKind: StudySessionAnswerKind = ANSWER_KIND_CHOICE,
) => {
    if (sessionExpired.value) {
        return null;
    }

    const existingAnswer = localResults.value[question.id];

    if (existingAnswer) {
        return existingAnswer;
    }

    if (answerKind === ANSWER_KIND_CHOICE && answerKey === null) {
        return null;
    }

    const result = buildLocalResult(question, answerKey, answerKind);

    localResults.value[question.id] = result;
    applyOptimisticAnswerProgress(result);
    showExplanationOnDemand.value = false;
    showExplanation.value = shouldShowAutomaticExplanationForResult(result);
    answerSyncFailedQuestionId.value = null;

    if (shouldPlayCorrectAnswerAudioForResult(result)) {
        void playCorrectAnswerAudio();
    }

    const nextQuestionMeta = resolveNextQuestionMeta(question.id);
    const immediatelyPreparedQuestion = nextQuestionMeta
        ? preparedNextQuestion.value?.id === nextQuestionMeta.id
            ? preparedNextQuestion.value
            : questionCache.value[nextQuestionMeta.id] ?? null
        : null;

    const advanceToNextQuestion = () => {
        if (!autoAdvance.value || localSessionCompleted.value) {
            return;
        }

        clearAutoAdvanceTimeout();

        if (immediatelyPreparedQuestion && nextQuestionMeta) {
            activateQuestion(immediatelyPreparedQuestion, nextQuestionMeta.number, { clearPrepared: true });
            return;
        }

        void moveToNextQuestion(question.id);
    };

    const resolveAutoAdvanceDelay = () => Math.max(
        showExplanation.value
            ? instructorHintGlanceMs.value
            : shouldShowInstantAnswerFeedback.value
            ? INSTANT_FEEDBACK_GLANCE_MS
            : 0,
        correctAnswerAudioDelayMs(result),
    );
    const answeredAt = Date.now();

    if (usesFrozenPublicDemo.value) {
        syncError.value = null;

        const cachedPreparedQuestion = nextQuestionMeta
            ? questionCache.value[nextQuestionMeta.id] ?? null
            : null;

        if (cachedPreparedQuestion && nextQuestionMeta) {
            preparedNextQuestion.value = cachedPreparedQuestion;
            preparedNextQuestionNumber.value = nextQuestionMeta.number;
            primeQuestionMedia(cachedPreparedQuestion);
        }

        const completeFrozenDemo = () => {
            sessionState.value = {
                ...sessionState.value,
                status: 'completed',
                completed_at: new Date().toISOString(),
            };
            progressState.value = {
                ...progressState.value,
                answered: progressState.value.total,
                remaining: 0,
            };
            preparedNextQuestion.value = null;
            preparedNextQuestionNumber.value = null;
            activeQuestionState.value = null;
            currentQuestionNumberState.value = null;
            localSessionCompleted.value = true;
            showCompletionResults.value = true;
            showCorrectCompletionResults.value = true;
            questionTransitionInFlight.value = false;
            answerSyncFailedQuestionId.value = null;
            void syncCompletion();
        };

        if (!nextQuestionMeta) {
            const completionDelay = resolveAutoAdvanceDelay();
            questionTransitionInFlight.value = completionDelay > 0;

            if (typeof window !== 'undefined' && completionDelay > 0) {
                clearAutoAdvanceTimeout();
                autoAdvanceTimeoutId = window.setTimeout(completeFrozenDemo, completionDelay);
                return result;
            }

            completeFrozenDemo();
            return result;
        }

        if (autoAdvance.value) {
            const remainingDelay = Math.max(resolveAutoAdvanceDelay() - Math.max(Date.now() - answeredAt, 0), 0);

            if (typeof window !== 'undefined') {
                clearAutoAdvanceTimeout();
                autoAdvanceTimeoutId = window.setTimeout(() => {
                    advanceToNextQuestion();
                }, remainingDelay);
                return result;
            }

            advanceToNextQuestion();
        }

        return result;
    }

    const syncRequest = enqueueAnswerSync(async () => {
        const payload = await apiClient.post<{
            answer: {
                question_id: number;
                selected_answer: string | null;
                answer_kind: StudySessionAnswerKind | null;
                is_correct: boolean | null;
                response_time_ms: number | null;
                correct_answer?: string | null;
                correct_answer_text?: string | null;
                explanation?: string | null;
                explanation_asset?: ExplanationAsset | null;
                explanation_sign_references?: ExplanationSignReference[];
                explanation_annotations?: ExplanationAnnotation[];
            };
            session: {
                status: string;
                correct_answers_count: number;
                total_questions_count: number;
                score_percent: number | null;
                completed_at: string | null;
            };
            progress: ProgressSummary;
            completed: boolean;
            nextQuestion?: CurrentQuestion | null;
            nextQuestionNumber?: number | null;
            topicCompletionOverview?: TopicCompletionOverview | null;
            pjmCompletion?: PjmCompletionSummary | null;
            reviewCompletion?: ReviewCompletionSummary | null;
        }>(
            sessionAnswersRoute.value,
            {
                question_id: question.id,
                selected_answer: answerKind === ANSWER_KIND_CHOICE ? answerKey : null,
                answer_kind: answerKind,
                response_time_ms: result.response_time_ms,
            },
        );

        syncError.value = null;
        answerSyncFailedQuestionId.value = null;
        localResults.value[question.id] = {
            ...localResults.value[question.id],
            selected_answer: payload.answer.selected_answer?.toUpperCase() ?? result.selected_answer,
            answer_kind: payload.answer.answer_kind ?? result.answer_kind,
            is_correct: payload.answer.is_correct,
            response_time_ms: payload.answer.response_time_ms,
            correct_answer: payload.answer.correct_answer?.toUpperCase()
                ?? localResults.value[question.id]?.correct_answer
                ?? result.correct_answer,
            correct_answer_text: payload.answer.correct_answer_text
                ?? localResults.value[question.id]?.correct_answer_text
                ?? result.correct_answer_text,
            explanation: payload.answer.explanation
                ?? localResults.value[question.id]?.explanation
                ?? result.explanation,
            explanation_asset: payload.answer.explanation_asset
                ?? localResults.value[question.id]?.explanation_asset
                ?? result.explanation_asset,
            explanation_sign_references: payload.answer.explanation_sign_references
                ?? localResults.value[question.id]?.explanation_sign_references
                ?? result.explanation_sign_references
                ?? [],
            explanation_annotations: payload.answer.explanation_annotations
                ?? localResults.value[question.id]?.explanation_annotations
                ?? result.explanation_annotations,
        };
        showExplanation.value = localResults.value[question.id]
            ? shouldShowAutomaticExplanationForResult(localResults.value[question.id])
            : false;
        sessionState.value = {
            ...sessionState.value,
            status: payload.session.status,
            correct_answers_count: payload.session.correct_answers_count,
            total_questions_count: payload.session.total_questions_count,
            score_percent: payload.session.score_percent,
            completed_at: payload.session.completed_at,
        };
        progressState.value = payload.progress;
        if (payload.pjmCompletion !== undefined) {
            pjmCompletionState.value = payload.pjmCompletion;
        }
        if (payload.reviewCompletion !== undefined) {
            reviewCompletionState.value = payload.reviewCompletion;
        }
        if (payload.topicCompletionOverview !== undefined) {
            topicCompletionOverviewState.value = payload.topicCompletionOverview;
        }
        if (payload.nextQuestion) {
            const nextQuestionNumber = payload.nextQuestionNumber
                ?? questionIndexById(payload.nextQuestion.id) + 1;

            cacheQuestion(payload.nextQuestion);
            preparedNextQuestion.value = payload.nextQuestion;
            preparedNextQuestionNumber.value = nextQuestionNumber > 0 ? nextQuestionNumber : null;
            primeQuestionMedia(payload.nextQuestion);
        }

        if (payload.completed) {
            preparedNextQuestion.value = null;
            preparedNextQuestionNumber.value = null;
            activeQuestionState.value = null;
            currentQuestionNumberState.value = null;
            localSessionCompleted.value = true;
            showCompletionResults.value = true;
            showCorrectCompletionResults.value = true;
        }
    });

    syncRequest.then(() => {
        if (!autoAdvance.value) {
            return;
        }

        const elapsedMs = Math.max(Date.now() - answeredAt, 0);
        const remainingDelay = Math.max(resolveAutoAdvanceDelay() - elapsedMs, 0);

        if (typeof window !== 'undefined') {
            clearAutoAdvanceTimeout();
            autoAdvanceTimeoutId = window.setTimeout(() => {
                advanceToNextQuestion();
            }, remainingDelay);
            return;
        }

        advanceToNextQuestion();
    }).catch((error) => {
        answerSyncFailedQuestionId.value = question.id;
        if (!handleSessionExpiryError(error)) {
            syncError.value = SAVE_ANSWER_SYNC_ERROR_MESSAGE;
        }
        clearAutoAdvanceTimeout();
        questionTransitionInFlight.value = false;
        preparedNextQuestion.value = null;
        preparedNextQuestionNumber.value = null;
        if (activeQuestionState.value?.id !== question.id) {
            activeQuestionState.value = question;
            currentQuestionNumberState.value = questionIndexById(question.id) + 1;
        }
        localSessionCompleted.value = false;
        showCompletionResults.value = false;
        showCorrectCompletionResults.value = false;
    });

    if (!autoAdvance.value && typeof window !== 'undefined') {
        clearAutoAdvanceTimeout();
    }

    return result;
};

const toggleExplanationOnDemand = () => {
    if (!canToggleExplanationOnDemand.value) {
        return;
    }

    if (isManualExplanationVisible.value) {
        showExplanationOnDemand.value = false;
        showExplanation.value = false;

        return;
    }

    showExplanationOnDemand.value = true;
    showExplanation.value = true;
};

const beginPreviewStage = () => {
    questionStage.value = 'preview';
    presentedAt.value = null;
};

const beginAnswerStage = (question: CurrentQuestion | null) => {
    if (!question) {
        return;
    }

    questionStage.value = 'answer';
    presentedAt.value = Date.now();
};

const submitSelectedAnswer = () => {
    if (
        sessionExpired.value
        || !activeQuestion.value
        || selectedAnswer.value === null
    ) {
        return;
    }

    if (isLocalLearningMode.value) {
        persistLocalAnswer(activeQuestion.value, selectedAnswer.value, ANSWER_KIND_CHOICE);
        return;
    }

    answerForm.question_id = activeQuestion.value.id;
    answerForm.selected_answer = selectedAnswer.value;
    answerForm.answer_kind = ANSWER_KIND_CHOICE;
    answerForm.response_time_ms = presentedAt.value
        ? Math.max(Date.now() - presentedAt.value, 0)
        : null;

    answerForm.post(sessionAnswersRoute.value, {
        preserveScroll: true,
    });
};

const selectAnswer = (answerKey: string) => {
    if (!canSelectAnswer.value || sessionExpired.value) {
        return;
    }

    answerInteractionLocked.value = true;

    selectedAnswer.value = answerKey;

    if (isLocalLearningMode.value && activeQuestion.value) {
        persistLocalAnswer(activeQuestion.value, answerKey, ANSWER_KIND_CHOICE);

        return;
    }

    if (autoAdvance.value && !isExamMode.value) {
        submitSelectedAnswer();
    }
};

const markUnknownAnswer = () => {
    if (
        sessionExpired.value
        || !canMarkUnknownAnswer.value
        || !activeQuestion.value
    ) {
        return;
    }

    answerInteractionLocked.value = true;
    selectedAnswer.value = null;
    keyboardSelectedOptionKey.value = null;

    persistLocalAnswer(activeQuestion.value, null, ANSWER_KIND_UNKNOWN);
};

const moveToNextQuestion = async (questionId: number | null = activeQuestion.value?.id ?? null) => {
    if (!isLocalLearningMode.value || sessionExpired.value) {
        return;
    }

    clearAutoAdvanceTimeout();

    if (questionTransitionInFlight.value) {
        return;
    }

    questionTransitionInFlight.value = true;

    showExplanation.value = false;
    showExplanationOnDemand.value = false;

    const nextQuestionMeta = resolveNextQuestionMeta(questionId);
    const cachedNextQuestion = nextQuestionMeta
        ? questionCache.value[nextQuestionMeta.id] ?? null
        : null;

    if (activatePreparedNextQuestion(nextQuestionMeta?.id ?? null)) {
        return;
    }

    if (cachedNextQuestion && nextQuestionMeta) {
        activateQuestion(cachedNextQuestion, nextQuestionMeta.number);
        return;
    }

    if (localSessionCompleted.value) {
        activeQuestionState.value = null;
        currentQuestionNumberState.value = null;
        questionTransitionInFlight.value = false;
        return;
    }

    await prepareUpcomingQuestion(questionId);

    const refreshedNextQuestionMeta = resolveNextQuestionMeta(questionId);
    const refreshedNextQuestion = refreshedNextQuestionMeta
        ? questionCache.value[refreshedNextQuestionMeta.id] ?? null
        : null;

    if (activatePreparedNextQuestion(refreshedNextQuestionMeta?.id ?? null)) {
        return;
    }

    if (refreshedNextQuestion && refreshedNextQuestionMeta) {
        activateQuestion(refreshedNextQuestion, refreshedNextQuestionMeta.number);
        return;
    }

    syncError.value = questionBatchFetchInFlight.value
        ? 'Laduje kolejne pytanie...'
        : 'Kolejne pytanie nie jest jeszcze gotowe. Sprobuj ponownie za chwile.';
    questionTransitionInFlight.value = false;
};

const moveToPreviousQuestion = async (questionId: number | null = activeQuestion.value?.id ?? null) => {
    if (!isLocalLearningMode.value || sessionExpired.value) {
        return;
    }

    clearAutoAdvanceTimeout();

    if (questionTransitionInFlight.value) {
        return;
    }

    const previousQuestionMeta = resolvePreviousQuestionMeta(questionId);

    if (!previousQuestionMeta) {
        return;
    }

    questionTransitionInFlight.value = true;
    showExplanation.value = false;
    showExplanationOnDemand.value = false;

    const cachedPreviousQuestion = questionCache.value[previousQuestionMeta.id] ?? null;

    if (cachedPreviousQuestion) {
        activateQuestion(cachedPreviousQuestion, previousQuestionMeta.number, { clearPrepared: true });
        return;
    }

    await fetchQuestionBatch([previousQuestionMeta.id]);

    const fetchedPreviousQuestion = questionCache.value[previousQuestionMeta.id] ?? null;

    if (fetchedPreviousQuestion) {
        activateQuestion(fetchedPreviousQuestion, previousQuestionMeta.number, { clearPrepared: true });
        return;
    }

    syncError.value = 'Nie udalo sie otworzyc poprzedniego pytania. Sprobuj ponownie za chwile.';
    questionTransitionInFlight.value = false;
};

const handlePrimaryAction = () => {
    if (sessionExpired.value || !activeQuestion.value) {
        return;
    }

    if (questionStage.value === 'preview') {
        beginAnswerStage(activeQuestion.value);
        return;
    }

    if (isLocalLearningMode.value) {
        if (!currentAnswerResult.value) {
            return;
        }

        void moveToNextQuestion();
        return;
    }

    submitSelectedAnswer();
};

const reloadCurrentPage = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.location.reload();
};

const selectInlineEditorRange = async (
    textareaRef: Ref<HTMLTextAreaElement | null>,
    selection: InlineEditorSelection,
) => {
    await nextTick();

    const textarea = textareaRef.value;

    if (!textarea) {
        return;
    }

    textarea.focus();
    textarea.setSelectionRange(selection.start, selection.end);
};

const syncInlineEditorSelection = (
    textareaRef: Ref<HTMLTextAreaElement | null>,
    selectionRef: Ref<InlineEditorSelection>,
) => {
    const textarea = textareaRef.value;

    if (!textarea) {
        return;
    }

    selectionRef.value = {
        start: textarea.selectionStart ?? 0,
        end: textarea.selectionEnd ?? textarea.selectionStart ?? 0,
    };
};

const applyInlineFormattingToEditor = async (
    marker: InlineFormattingMarker,
    draftRef: Ref<string>,
    textareaRef: Ref<HTMLTextAreaElement | null>,
    selectionRef: Ref<InlineEditorSelection>,
) => {
    const textarea = textareaRef.value;
    const selection = textarea
        ? {
            start: textarea.selectionStart ?? 0,
            end: textarea.selectionEnd ?? textarea.selectionStart ?? 0,
        }
        : selectionRef.value;
    const result = applyInlineFormattingSelection({
        value: draftRef.value,
        selectionStart: selection.start,
        selectionEnd: selection.end,
        marker,
    });

    draftRef.value = result.value;
    selectionRef.value = {
        start: result.selectionStart,
        end: result.selectionEnd,
    };

    await selectInlineEditorRange(textareaRef, selectionRef.value);
};

const applyExplanationEditorFormatting = (marker: InlineFormattingMarker) => {
    void applyInlineFormattingToEditor(
        marker,
        explanationEditorDraft,
        explanationEditorTextareaRef,
        explanationEditorSelection,
    );
};

const applyQuestionEditorFormatting = (marker: InlineFormattingMarker) => {
    void applyInlineFormattingToEditor(
        marker,
        questionEditorDraft,
        questionEditorTextareaRef,
        questionEditorSelection,
    );
};

const syncExplanationEditorSelection = () => {
    syncInlineEditorSelection(explanationEditorTextareaRef, explanationEditorSelection);
};

const syncQuestionEditorSelection = () => {
    syncInlineEditorSelection(questionEditorTextareaRef, questionEditorSelection);
};

const openExplanationEditor = (question: {
    id: number;
    external_id?: string | null;
    shared_explanation_has_conflict?: boolean;
    prompt: string | null;
    explanation: string | null;
}) => {
    if (!canInlineEditExplanation.value) {
        return;
    }

    explanationEditorQuestionId.value = question.id;
    explanationEditorQuestionPrompt.value = question.prompt;
    explanationEditorQuestionExternalId.value = question.external_id ?? null;
    explanationEditorDraft.value = question.explanation ?? '';
    explanationEditorError.value = null;
    explanationEditorHasSharedConflict.value = Boolean(question.shared_explanation_has_conflict);
    explanationEditorApplyScope.value = question.external_id && !question.shared_explanation_has_conflict
        ? 'shared_external_id'
        : 'single';
    explanationEditorSelection.value = {
        start: explanationEditorDraft.value.length,
        end: explanationEditorDraft.value.length,
    };
    isExplanationEditorOpen.value = true;
    void selectInlineEditorRange(explanationEditorTextareaRef, explanationEditorSelection.value);
};

const openQuestionEditor = (question: {
    id: number;
    external_id?: string | null;
    prompt: string | null;
}) => {
    if (!canInlineEditExplanation.value) {
        return;
    }

    questionEditorQuestionId.value = question.id;
    questionEditorQuestionPrompt.value = question.prompt;
    questionEditorQuestionExternalId.value = question.external_id ?? null;
    questionEditorDraft.value = question.prompt ?? '';
    questionEditorError.value = null;
    questionEditorApplyScope.value = question.external_id ? 'shared_external_id' : 'single';
    questionEditorSelection.value = {
        start: questionEditorDraft.value.length,
        end: questionEditorDraft.value.length,
    };
    isQuestionEditorOpen.value = true;
    void selectInlineEditorRange(questionEditorTextareaRef, questionEditorSelection.value);
};

const closeExplanationEditor = () => {
    if (explanationEditorSaving.value) {
        return;
    }

    isExplanationEditorOpen.value = false;
    explanationEditorError.value = null;
    explanationEditorQuestionExternalId.value = null;
    explanationEditorApplyScope.value = 'single';
    explanationEditorHasSharedConflict.value = false;
};

const closeQuestionEditor = () => {
    if (questionEditorSaving.value) {
        return;
    }

    isQuestionEditorOpen.value = false;
    questionEditorError.value = null;
    questionEditorQuestionExternalId.value = null;
    questionEditorApplyScope.value = 'single';
};

const applyUpdatedPromptLocally = (questionIds: number[], prompt: string) => {
    let nextQuestionCache = questionCache.value;
    let nextLocalResults = localResults.value;
    let nextActiveQuestion = activeQuestionState.value;
    let nextPreparedQuestion = preparedNextQuestion.value;

    questionIds.forEach((questionId) => {
        const cachedQuestion = nextQuestionCache[questionId];

        if (cachedQuestion) {
            nextQuestionCache = {
                ...nextQuestionCache,
                [questionId]: {
                    ...cachedQuestion,
                    prompt,
                },
            };
        }

        if (nextActiveQuestion?.id === questionId) {
            nextActiveQuestion = {
                ...nextActiveQuestion,
                prompt,
            };
        }

        if (nextPreparedQuestion?.id === questionId) {
            nextPreparedQuestion = {
                ...nextPreparedQuestion,
                prompt,
            };
        }

        if (nextLocalResults[questionId]) {
            nextLocalResults = {
                ...nextLocalResults,
                [questionId]: {
                    ...nextLocalResults[questionId],
                    prompt,
                },
            };
        }

        if (questionEditorQuestionId.value === questionId) {
            questionEditorQuestionPrompt.value = prompt;
        }

        if (explanationEditorQuestionId.value === questionId) {
            explanationEditorQuestionPrompt.value = prompt;
        }
    });

    questionCache.value = nextQuestionCache;
    activeQuestionState.value = nextActiveQuestion;
    preparedNextQuestion.value = nextPreparedQuestion;
    localResults.value = nextLocalResults;
};

const applyUpdatedExplanationLocally = (
    questionIds: number[],
    explanation: string | null,
    resolveSharedConflict = false,
) => {
    let nextQuestionCache = questionCache.value;
    let nextLocalResults = localResults.value;
    let nextActiveQuestion = activeQuestionState.value;
    let nextPreparedQuestion = preparedNextQuestion.value;

    questionIds.forEach((questionId) => {
        const cachedQuestion = nextQuestionCache[questionId];

        if (cachedQuestion) {
            nextQuestionCache = {
                ...nextQuestionCache,
                [questionId]: {
                    ...cachedQuestion,
                    explanation,
                    ...(resolveSharedConflict ? { shared_explanation_has_conflict: false } : {}),
                },
            };
        }

        if (nextActiveQuestion?.id === questionId) {
            nextActiveQuestion = {
                ...nextActiveQuestion,
                explanation,
                ...(resolveSharedConflict ? { shared_explanation_has_conflict: false } : {}),
            };
        }

        if (nextPreparedQuestion?.id === questionId) {
            nextPreparedQuestion = {
                ...nextPreparedQuestion,
                explanation,
                ...(resolveSharedConflict ? { shared_explanation_has_conflict: false } : {}),
            };
        }

        if (nextLocalResults[questionId]) {
            nextLocalResults = {
                ...nextLocalResults,
                [questionId]: {
                    ...nextLocalResults[questionId],
                    explanation,
                    ...(resolveSharedConflict ? { shared_explanation_has_conflict: false } : {}),
                },
            };
        }
    });

    questionCache.value = nextQuestionCache;
    activeQuestionState.value = nextActiveQuestion;
    preparedNextQuestion.value = nextPreparedQuestion;
    localResults.value = nextLocalResults;
};

const saveQuestionEditor = async () => {
    if (questionEditorQuestionId.value === null || questionEditorSaving.value) {
        return;
    }

    questionEditorSaving.value = true;
    questionEditorError.value = null;

    try {
        const promptUpdateUrl = (() => {
            try {
                return route('api.v1.admin.questions.prompt.update', questionEditorQuestionId.value!);
            } catch {
                return `/api/v1/admin/questions/${questionEditorQuestionId.value}/prompt`;
            }
        })();

        const payload = await apiClient.patch<{
            data?: {
                question?: {
                    external_id?: string | null;
                    prompt?: string | null;
                } | null;
                affected_questions?: Array<{
                    id: number;
                    external_id?: string | null;
                    prompt?: string | null;
                }> | null;
                apply_scope?: string | null;
            } | null;
        }>(
            promptUpdateUrl,
            {
                prompt: questionEditorDraft.value,
                apply_scope: questionEditorApplyScope.value,
            },
        );

        const prompt = payload?.data?.question?.prompt ?? '';
        const affectedQuestionIds = payload?.data?.affected_questions?.map((question) => question.id) ?? [
            questionEditorQuestionId.value,
        ];

        applyUpdatedPromptLocally(affectedQuestionIds, prompt);
        isQuestionEditorOpen.value = false;
    } catch (error) {
        if (isApiClientError(error)) {
            const errorData = error.data as {
                errors?: {
                    prompt?: string[];
                };
                message?: string;
            };
            const validationMessage = errorData?.errors?.prompt?.[0];

            questionEditorError.value = validationMessage
                ?? errorData?.message
                ?? 'Nie udalo sie zapisac tresci pytania. Sprobuj ponownie za chwile.';
        } else {
            questionEditorError.value = 'Nie udalo sie zapisac tresci pytania. Sprobuj ponownie za chwile.';
        }
    } finally {
        questionEditorSaving.value = false;
    }
};

const saveExplanationEditor = async () => {
    if (explanationEditorQuestionId.value === null || explanationEditorSaving.value) {
        return;
    }

    explanationEditorSaving.value = true;
    explanationEditorError.value = null;

    try {
        const payload = await apiClient.patch<{
            data?: {
                question?: {
                    external_id?: string | null;
                    explanation?: string | null;
                } | null;
                affected_questions?: Array<{
                    id: number;
                    external_id?: string | null;
                    explanation?: string | null;
                }> | null;
                apply_scope?: string | null;
                shared_group_had_conflicts_before_update?: boolean | null;
            } | null;
        }>(
            route('api.v1.admin.questions.explanation.update', explanationEditorQuestionId.value),
            {
                explanation: explanationEditorDraft.value,
                apply_scope: explanationEditorApplyScope.value,
            },
        );

        const explanation = payload?.data?.question?.explanation ?? null;
        const affectedQuestionIds = payload?.data?.affected_questions?.map((question) => question.id) ?? [
            explanationEditorQuestionId.value,
        ];

        applyUpdatedExplanationLocally(
            affectedQuestionIds,
            explanation,
            payload?.data?.apply_scope === 'shared_external_id',
        );
        isExplanationEditorOpen.value = false;
    } catch (error) {
        if (isApiClientError(error)) {
            const errorData = error.data as {
                errors?: {
                    explanation?: string[];
                };
                message?: string;
            };
            const validationMessage = errorData?.errors?.explanation?.[0];

            explanationEditorError.value = validationMessage
                ?? errorData?.message
                ?? 'Nie udalo sie zapisac wyjasnienia. Sprobuj ponownie za chwile.';
        } else {
            explanationEditorError.value = 'Nie udalo sie zapisac wyjasnienia. Sprobuj ponownie za chwile.';
        }
    } finally {
        explanationEditorSaving.value = false;
    }
};

const finishSession = () => {
    clearAutoAdvanceTimeout();
    closeSidePanels();
    isLearningGuideVisible.value = false;
    isTopicGuideVisible.value = false;

    if (isLocalLearningMode.value) {
        sessionState.value = {
            ...sessionState.value,
            status: 'completed',
            completed_at: new Date().toISOString(),
        };
        activeQuestionState.value = null;
        currentQuestionNumberState.value = null;
        localSessionCompleted.value = true;
        showCompletionResults.value = true;
        showCorrectCompletionResults.value = true;
        void syncCompletion();
        return;
    }

    completeForm.post(sessionCompleteRoute.value, {
        preserveScroll: true,
    });
};

const disconnectMobileAnswerDockObserver = () => {
    mobileAnswerDockResizeObserver?.disconnect();
    mobileAnswerDockResizeObserver = null;
};

const syncMobileAnswerDockHeight = () => {
    if (
        typeof window === 'undefined'
        || !shouldPinMobileAnswerPanel.value
        || !mobileAnswerDockRef.value
    ) {
        mobileAnswerDockHeight.value = 0;
        return;
    }

    mobileAnswerDockHeight.value = Math.ceil(
        mobileAnswerDockRef.value.getBoundingClientRect().height,
    );
};

const bindMobileAnswerDockObserver = () => {
    disconnectMobileAnswerDockObserver();

    if (
        typeof window === 'undefined'
        || !shouldPinMobileAnswerPanel.value
        || !mobileAnswerDockRef.value
    ) {
        syncMobileAnswerDockHeight();
        return;
    }

    if (typeof ResizeObserver !== 'undefined') {
        mobileAnswerDockResizeObserver = new ResizeObserver(() => {
            syncMobileAnswerDockHeight();
        });
        mobileAnswerDockResizeObserver.observe(mobileAnswerDockRef.value);
    }

    syncMobileAnswerDockHeight();
};

onMounted(() => {
    loadPreferences();
    restoreVisualExplanationsPreference();
    syncViewport();

    if (typeof window !== 'undefined') {
        if (
            props.publicDemo?.gate?.enabled
            && window.localStorage.getItem(PUBLIC_DEMO_GATE_DISMISSED_KEY) === '1'
        ) {
            isPublicDemoGateDismissed.value = true;
            skipPublicDemoGateNextTime.value = true;
        }

        if (
            sessionState.value.status === 'in_progress'
            && (
                window.location.pathname.startsWith('/study-sessions/')
                || window.location.pathname.startsWith('/nauka/wynik/')
            )
        ) {
            window.history.replaceState({}, '', route('study-sessions.current'));
        }

        if (
            sessionState.value.status === 'in_progress'
            && !isExamLikeShell.value
            && window.localStorage.getItem(SESSION_LEARNING_PANEL_SEEN_KEY) !== '1'
        ) {
            openSettingsPopover();
            isLearningGuideVisible.value = shouldShowLearningGuide();
        }

        window.addEventListener('resize', syncViewport);
        window.addEventListener('resize', syncSessionRoadmapScrollState);
        window.addEventListener('keydown', handleSessionKeydown);
        document.addEventListener('click', handleSessionRoadmapLegendOutsideClick);
    }

    void nextTick(() => {
        bindMobileAnswerDockObserver();
        void autoFocusSessionRoadmap();
        void autoFocusCompletionProgressPanel();
        syncSessionRoadmapScrollState();
    });
});

onBeforeUnmount(() => {
    clearAutoAdvanceTimeout();
    stopCorrectAnswerAudio();
    disconnectMobileAnswerDockObserver();

    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', syncViewport);
        window.removeEventListener('resize', syncSessionRoadmapScrollState);
        window.removeEventListener('keydown', handleSessionKeydown);
        document.removeEventListener('click', handleSessionRoadmapLegendOutsideClick);
    }
});

watch([autoAdvance, autoPlayVideos, showQuestionAudioControl, autoPlayQuestionAudio, autoPlayCorrectAnswerAudio, videoPlaybackRate, autoJumpToVideoEnding, hintTimingPreference, feedbackMode, enableInlineBold, enableInlineColors, showVisualAnnotations], () => {
    persistPreferences();

    if (isLearningGuideVisible.value) {
        dismissLearningGuide();
    }
});

watch(
    () => visualExplanationsMode.value,
    () => {
        persistVisualExplanationsPreference();
    },
);

watch(
    [
        shouldPinMobileAnswerPanel,
        () => mobileAnswerDockRef.value,
        () => activeQuestion.value?.id ?? null,
        () => activeQuestion.value?.question_type ?? null,
        questionStage,
        isLocalLearningMode,
    ],
    () => {
        void nextTick(() => {
            bindMobileAnswerDockObserver();
        });
    },
);

watch([() => videoPlaybackRate.value, () => autoJumpToVideoEnding.value], () => {
    const rate = effectiveVideoPlaybackRate.value;

    videoElements.forEach((video) => {
        video.defaultPlaybackRate = rate;
        video.playbackRate = rate;
    });
});

watch(
    () => feedbackMode.value,
    (mode) => {
        if (mode !== 'instant_explanation') {
            showExplanation.value = false;
            showExplanationOnDemand.value = false;
        } else if (
            currentAnswerResult.value
            && currentAnswerResult.value.is_correct === false
        ) {
            showExplanation.value = true;
            showExplanationOnDemand.value = false;
        }
    },
);

watch(
    activeQuestion,
    async (question) => {
        stopQuestionAudio();
        stopCorrectAnswerAudio();
        correctAnswerAudioSourceKey.value = null;
        videoElements.clear();
        clearAutoAdvanceTimeout();
        videoPlaybackState.value = {};
        videoActivatedState.value = {};
        cacheQuestion(question);
        answerForm.question_id = question?.id ?? 0;
        answerForm.selected_answer = '';
        answerForm.answer_kind = ANSWER_KIND_CHOICE;
        answerForm.response_time_ms = null;
        selectedAnswer.value = question
            ? localResults.value[question.id]?.selected_answer ?? null
            : null;
        keyboardSelectedOptionKey.value = null;
        answerSyncFailedQuestionId.value = null;
        showExplanation.value = false;
        showExplanationOnDemand.value = false;
        answerInteractionLocked.value = false;
        questionTransitionInFlight.value = false;

        if (!question) {
            presentedAt.value = null;
            questionStage.value = 'answer';
            return;
        }

        showReviewTrainerQuestionDetails.value = false;

        if (isExamMode.value && question.structure_scope !== 'SPECJALISTYCZNY') {
            beginPreviewStage();
            return;
        }

        beginAnswerStage(question);
        scheduleVideoPlayback(question);
        scheduleUpcomingQuestionPreparation(question.id);
    },
    { immediate: true },
);

watch(
    () => autoPlayVideos.value,
    async (enabled) => {
        if (!enabled || !activeQuestion.value) {
            return;
        }

        scheduleVideoPlayback(activeQuestion.value);
    },
);

watch(
    () => showQuestionAudioControl.value,
    (enabled) => {
        if (!enabled) {
            stopQuestionAudio();
        }
    },
);
</script>

<template>
    <Head :title="pageTitle" />

    <audio
        v-if="canUseQuestionAudio"
        ref="correctAnswerAudioElement"
        class="hidden"
        preload="none"
        controlslist="nodownload noremoteplayback"
        disableremoteplayback
        @contextmenu.prevent
        @dragstart.prevent
    />

    <SessionExpiredNotice
        v-if="sessionExpired"
        :public-demo="isPublicDemoMode"
        :restart-href="props.publicDemo?.routes.landing ?? '/testy-na-prawo-jazdy'"
    />

    <component :is="activeSessionLayout" :lock-viewport="Boolean(activeQuestion)">
        <div class="relative">
            <Transition name="topics-handle">
                <button
                    v-if="!isExamLikeShell && !isPhoneViewport && !localSessionCompleted && canUseTopicPicker && !isTopicPickerOpen && !isSettingsOpen"
                    type="button"
                    class="topics-handle-trigger fixed left-0 top-[23%] border-l-0 sm:top-[27%] md:top-[32%] xl:top-[38%]"
                    :class="topicHandleClass"
                    @click="openTopicPicker()"
                >
                    <span class="sm:hidden">Działy</span>
                    <span class="hidden sm:inline">Działy</span>
                </button>
            </Transition>
            <Transition name="learning-handle">
                <button
                    v-if="!isExamLikeShell && !isPhoneViewport && !localSessionCompleted && !isSettingsOpen && !isTopicPickerOpen"
                    type="button"
                    class="learning-handle-trigger fixed right-0 top-[23%] sm:top-[27%] md:top-[32%] xl:top-[38%]"
                    :class="settingsHandleClass"
                    @click="openSettingsPopover()"
                >
                    <span class="sm:hidden">Tryb</span>
                    <span class="hidden sm:inline">Jak się uczyć</span>
                </button>
            </Transition>

            <Transition name="learning-backdrop">
                <button
                    v-if="isTopicPickerOpen || (!localSessionCompleted && isSettingsOpen) || isMobileSessionMenuOpen"
                    type="button"
                    class="fixed inset-0 z-40 bg-[rgba(15,23,42,0.22)] backdrop-blur-[3px]"
                    aria-label="Zamknij panel pomocniczy"
                    @click="closeSidePanels"
                />
            </Transition>

            <Transition name="learning-panel">
                <div
                    v-if="showMobileSessionMenuTrigger && isMobileSessionMenuOpen"
                    :class="mobileSessionMenuSheetClass"
                    style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 1rem);"
                >
                    <div class="mx-auto max-w-sm space-y-3">
                        <div
                            class="mx-auto h-1.5 w-12"
                            :class="isExamLikeShell ? 'bg-[#d1d5db]' : 'bg-[#d1d5db]'"
                            aria-hidden="true"
                        />
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p
                                    class="text-[0.68rem] font-semibold uppercase tracking-[0.16em]"
                                    :class="isExamLikeShell ? 'text-[#6b7280]' : 'text-[#6b7280]'"
                                >
                                    Opcje sesji
                                </p>
                                <p
                                    class="text-sm leading-6"
                                    :class="isExamLikeShell ? 'text-[#4b5563]' : 'text-[#4b5563]'"
                                >
                                    Szybki dostęp do działów i ustawień nauki.
                                </p>
                            </div>

                            <button
                                type="button"
                                :class="sidePanelCloseButtonClass"
                                @click="closeMobileSessionMenu"
                            >
                                Zamknij
                            </button>
                        </div>

                        <div class="grid gap-2.5">
                            <button
                                v-if="canUseTopicPicker"
                                type="button"
                                :class="mobileSessionMenuActionButtonClass"
                                @click="openTopicPicker()"
                            >
                                Działy
                            </button>

                            <button
                                type="button"
                                :class="mobileSessionMenuActionButtonClass"
                                @click="openSettingsPopover({ markSeen: false })"
                            >
                                Ustawienia nauki
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="topics-panel">
                <aside
                    v-if="isTopicPickerOpen"
                    :class="topicPickerPanelClass"
                >
                    <div :class="sidePanelHeaderClass">
                        <div class="space-y-1">
                            <span
                                v-if="isTopicGuideVisible"
                                class="inline-flex items-center px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em]"
                                :class="isExamLikeShell ? 'bg-[#f3f5f7] text-[#4b5563]' : 'border border-[#e5e7eb] bg-[#fafafa] text-[#4b5563]'"
                            >
                                Krok 2 z 2
                            </span>
                            <h2 :class="sidePanelTitleClass">
                                {{ localSessionCompleted ? 'Wybierz inny dział' : 'Wybierz dział' }}
                            </h2>
                            <p :class="sidePanelDescriptionClass">
                                {{ localSessionCompleted
                                    ? 'Wybierz, co chcesz opanować dalej.'
                                    : 'Zmieniaj dział bez wychodzenia z nauki.' }}
                            </p>
                            <div
                                v-if="isTopicGuideVisible"
                                class="max-w-xs pt-1"
                            >
                                <p
                                    class="inline-flex items-start gap-2 text-[0.82rem] leading-5"
                                    :class="isExamLikeShell ? 'text-[#4b5563]' : 'text-[#4b5563]'"
                                >
                                    <span
                                        class="mt-[0.42rem] h-1.5 w-1.5 shrink-0 rounded-full"
                                        :class="isExamLikeShell ? 'bg-[#cbd5e1]' : 'bg-[#cbd5e1]'"
                                    />
                                    <span>Tutaj zmienisz dział bez wychodzenia z nauki.</span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Zamknij panel działów"
                            title="Zamknij panel działów"
                            :class="sidePanelIconCloseButtonClass"
                            @click="closeTopicPicker"
                        >
                            <SquareX :size="26" :stroke-width="1.8" aria-hidden="true" />
                        </button>
                    </div>

                    <div :class="topicPickerBodyClass">
                        <div :class="topicPickerBodyInnerClass">
                            <section
                                v-for="group in filteredTopicGroups"
                                :key="group.label"
                                class="space-y-3"
                            >
                                <div class="space-y-1">
                                    <p :class="sidePanelEyebrowClass">
                                        {{ group.label }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <button
                                        v-for="topic in group.options"
                                        :key="topic.id"
                                        type="button"
                                        class="block w-full border text-left transition"
                                        :class="topicOptionButtonClass(topic)"
                                        :disabled="!canSwitchTopics || currentStatusQuestionCountForTopic(topic) <= 0 || isCurrentTopicOption(topic)"
                                        @click="switchTopicSession(topic)"
                                    >
                                        <template v-if="isExamLikeShell">
                                            <div
                                                class="flex gap-4"
                                                :class="[
                                                    topicOptionItemPaddingClass,
                                                    topicArtworkFor(topic) ? topicArtworkRowClass : 'items-start',
                                                ]"
                                            >
                                                <span
                                                    v-if="!topicArtworkFor(topic)"
                                                    :class="[topicOptionIndicatorSizeClass, topicOptionIndicatorClass(topic)]"
                                                >
                                                    <span v-if="topicOptionStatus(topic) === 'completed'">✓</span>
                                                    <span
                                                        v-else-if="topicOptionStatus(topic) === 'current'"
                                                        class="h-2.5 w-2.5 rounded-full"
                                                        :class="topicOptionIndicatorInnerClass(topic)"
                                                    />
                                                    <span
                                                        v-else-if="topicOptionStatus(topic) === 'next'"
                                                        class="h-2.5 w-2.5 rounded-full border-2 bg-transparent"
                                                        :class="topicOptionIndicatorInnerClass(topic)"
                                                    />
                                                    <span
                                                        v-else
                                                        class="h-1.5 w-1.5 rounded-full"
                                                        :class="topicOptionIndicatorInnerClass(topic)"
                                                    />
                                                </span>

                                                <div
                                                    class="min-w-0 flex-1"
                                                    :class="topicArtworkFor(topic) ? topicArtworkContentClass : ''"
                                                >
                                                    <img
                                                        v-if="topicArtworkFor(topic)"
                                                        :src="topicArtworkFor(topic) ?? ''"
                                                        alt=""
                                                        :class="topicArtworkImageClass"
                                                        aria-hidden="true"
                                                    />
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p :class="topicOptionTitleClass">
                                                            {{ topic.label }}
                                                        </p>

                                                        <span
                                                            v-if="topicOptionStatusLabel(topic)"
                                                            :class="topicOptionBadgeClass(topic)"
                                                        >
                                                            {{ topicOptionStatusLabel(topic) }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-1.5 flex items-end justify-between gap-3">
                                                        <p :class="topicOptionMetaClass">
                                                            <span :class="topicOptionCountClass">
                                                                {{ topicMasteredQuestionCount(topic) }} / {{ topicOptionTotalQuestionCount(topic) }}
                                                            </span>
                                                            opanowane
                                                        </p>

                                                        <span
                                                            v-if="currentStatusQuestionCountForTopic(topic) <= 0 && !isCurrentTopicOption(topic)"
                                                            class="text-[0.72rem] leading-5 text-[#9ca3af]"
                                                        >
                                                            Brak pytań
                                                        </span>
                                                    </div>

                                                    <div
                                                        class="mt-2.5 w-full overflow-hidden"
                                                        :class="[topicOptionProgressTrackClass, topicOptionProgressBarHeightClass]"
                                                    >
                                                        <div
                                                            class="h-full transition-[width]"
                                                            :class="topicOptionProgressFillClass(topic)"
                                                            :style="{ width: `${topicOptionProgressPercent(topic)}%` }"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <div class="px-5 py-4">
                                                <div
                                                    class="flex gap-4"
                                                    :class="topicArtworkFor(topic) ? topicArtworkRowClass : 'items-start'"
                                                >
                                                    <span
                                                        v-if="!topicArtworkFor(topic)"
                                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-[0.78rem] font-semibold"
                                                        :class="topicOptionIndicatorClass(topic)"
                                                    >
                                                        <span v-if="topicOptionStatus(topic) === 'completed'">✓</span>
                                                        <span
                                                            v-else-if="topicOptionStatus(topic) === 'current'"
                                                            class="h-2.5 w-2.5 rounded-full"
                                                            :class="topicOptionIndicatorInnerClass(topic)"
                                                        />
                                                        <span
                                                            v-else-if="topicOptionStatus(topic) === 'next'"
                                                            class="h-2.5 w-2.5 rounded-full border-2 bg-transparent"
                                                            :class="topicOptionIndicatorInnerClass(topic)"
                                                        />
                                                        <span
                                                            v-else
                                                            class="h-1.5 w-1.5 rounded-full"
                                                            :class="topicOptionIndicatorInnerClass(topic)"
                                                        />
                                                    </span>

                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div
                                                                class="min-w-0"
                                                                :class="topicArtworkFor(topic) ? topicArtworkContentClass : ''"
                                                            >
                                                                <img
                                                                    v-if="topicArtworkFor(topic)"
                                                                    :src="topicArtworkFor(topic) ?? ''"
                                                                    alt=""
                                                                    :class="topicArtworkImageClass"
                                                                    aria-hidden="true"
                                                                />
                                                                <p class="font-semibold leading-6 tracking-[0.01em] text-[#2a2621]">
                                                                    {{ topic.label }}
                                                                </p>
                                                                <p class="mt-1 text-[0.86rem] leading-6 text-[#746b5f]">
                                                                    <span class="font-semibold text-[#4f493f]">
                                                                        {{ topicMasteredQuestionCount(topic) }} / {{ topicOptionTotalQuestionCount(topic) }}
                                                                    </span>
                                                                    opanowane
                                                                </p>
                                                            </div>

                                                            <span
                                                                v-if="topicOptionStatusLabel(topic)"
                                                                :class="topicOptionBadgeClass(topic)"
                                                            >
                                                                {{ topicOptionStatusLabel(topic) }}
                                                            </span>
                                                        </div>

                                                        <div class="mt-3 flex items-center justify-between gap-3">
                                                            <span
                                                                v-if="currentStatusQuestionCountForTopic(topic) <= 0 && !isCurrentTopicOption(topic)"
                                                                class="text-[0.72rem] leading-5 text-[#a59a8b]"
                                                            >
                                                                Brak pytań
                                                            </span>
                                                            <span
                                                                v-else
                                                                class="text-[0.72rem] leading-5 text-[#8d8478]"
                                                            >
                                                                {{ topicOptionProgressPercent(topic) }}% ścieżki działu
                                                            </span>

                                                            <span class="text-[0.72rem] font-medium leading-5 text-[#6f675c]">
                                                                {{ Math.max(topicOptionTotalQuestionCount(topic) - topicMasteredQuestionCount(topic), 0) }} zostało
                                                            </span>
                                                        </div>

                                                        <div class="mt-2.5 h-1 w-full overflow-hidden bg-[#efe9df]">
                                                            <div
                                                                class="h-full transition-[width]"
                                                                :class="topicOptionProgressFillClass(topic)"
                                                                :style="{ width: `${topicOptionProgressPercent(topic)}%` }"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </button>
                                </div>
                            </section>
                        </div>
                    </div>
                </aside>
            </Transition>

            <Transition name="learning-panel">
                <aside
                    v-if="!localSessionCompleted && isSettingsOpen"
                    :class="settingsPanelClass"
                >
                    <div
                        v-if="isLearningGuideVisible"
                        class="absolute right-full top-8 z-10 hidden w-[19.5rem] items-start gap-2.5 pr-3 xl:flex"
                    >
                        <div class="relative mt-10 h-24 w-16 shrink-0">
                            <svg
                                viewBox="0 0 88 120"
                                class="h-full w-full text-[#4b5563]"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle cx="43" cy="21" r="14" fill="#ffffff" stroke="currentColor" stroke-width="2" />
                                <path d="M28 49c3-8 11-13 20-13s17 5 20 13l7 22c1 4-1 8-5 8H16c-4 0-6-4-5-8l7-22Z" fill="#f8fafc" stroke="currentColor" stroke-width="2" />
                                <path d="M43 50v24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                <path d="M27 95l11-23" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                <path d="M59 95L48 72" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                <path d="M58 52c6 4 12 7 18 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                <path d="M74 57l5 4-6 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <div
                            class="relative flex-1 p-3.5"
                            :class="isExamLikeShell
                                ? 'border border-[#e5e7eb] bg-white shadow-[0_8px_20px_rgba(15,23,42,0.06)]'
                                : 'border border-[#e5e7eb] bg-white shadow-[0_8px_20px_rgba(15,23,42,0.06)]'"
                        >
                            <span
                                class="absolute -right-[0.4rem] top-8 h-3 w-3 rotate-45"
                                :class="isExamLikeShell
                                    ? 'border-r border-t border-[#e5e7eb] bg-white'
                                    : 'border-r border-t border-[#e5e7eb] bg-white'"
                            />
                            <div class="space-y-2.5">
                                <div class="space-y-1">
                                    <p
                                        class="text-[0.68rem] font-semibold uppercase tracking-[0.16em]"
                                        :class="isExamLikeShell ? 'text-[#6b7280]' : 'text-[#6b7280]'"
                                    >
                                        {{ canUseTopicPicker ? 'Krok 1 z 2' : 'Ustawienia nauki' }}
                                    </p>
                                    <p
                                        class="text-[0.9rem] font-semibold"
                                        :class="isExamLikeShell ? 'text-[#161414]' : 'text-[#161414]'"
                                    >
                                        Ustaw sesję pod siebie
                                    </p>
                                    <p
                                        class="text-[0.82rem] leading-5"
                                        :class="isExamLikeShell ? 'text-[#4b5563]' : 'text-[#4b5563]'"
                                    >
                                        Każdy kursant uczy się trochę inaczej. Tutaj wybierzesz sposób nauki, który dziś najbardziej Ci pomaga.
                                    </p>
                                        <p
                                            class="text-[0.82rem] leading-5"
                                            :class="isExamLikeShell ? 'text-[#4b5563]' : 'text-[#4b5563]'"
                                        >
                                            Jeśli chcesz iść szybciej, możesz odpowiadać też klawiaturą.
                                        </p>
                                </div>

                                <button
                                    type="button"
                                    :class="sidePanelCloseButtonClass"
                                    @click="closeSettingsPopover"
                                >
                                    {{ canUseTopicPicker ? 'Pokaż działy' : 'Zaczynam naukę' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div :class="sidePanelHeaderClass">
                        <div class="space-y-1">
                            <p :class="sidePanelEyebrowClass">
                                USTAWIENIA SESJI
                            </p>
                            <h2 :class="sidePanelTitleClass">
                                Jak chcesz się uczyć?
                            </h2>
                            <p :class="sidePanelDescriptionClass">
                                Ustaw sesję tak, żeby pasowała do Twojego tempa i sposobu nauki.
                            </p>
                        </div>

                        <button
                            type="button"
                            aria-label="Zamknij panel ustawień"
                            title="Zamknij panel ustawień"
                            :class="sidePanelIconCloseButtonClass"
                            @click="closeSettingsPopover"
                        >
                            <SquareX :size="26" :stroke-width="1.8" aria-hidden="true" />
                        </button>
                    </div>

                    <div :class="sidePanelBodyClass">
                        <section class="space-y-3">
                            <div class="space-y-2">
                                <p :class="sidePanelSectionTitleClass">
                                    Po odpowiedzi
                                </p>
                                <p :class="sidePanelDescriptionClass">
                                    Co ma się dziać po kliknięciu
                                </p>
                            </div>

                            <label
                                class="block cursor-pointer border px-4 py-3.5 transition"
                                :class="settingsSelectableCardClass(feedbackMode === 'instant')"
                            >
                                <input
                                    type="radio"
                                    :checked="feedbackMode === 'instant'"
                                    class="sr-only"
                                    @change="applyFeedbackModePreset('instant')"
                                />
                                <span class="flex items-start gap-3">
                                    <span
                                        class="-mt-[4px] -ml-[3px] inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition"
                                        :class="settingsSelectionIndicatorClass(feedbackMode === 'instant')"
                                    >
                                        <Check :size="11" :stroke-width="2.4" aria-hidden="true" />
                                    </span>
                                    <Gauge
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0"
                                        :class="feedbackMode === 'instant' ? 'text-[#b8891d]' : 'text-[#334155]'"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Szybki tryb (dobrze/źle)</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Po kliknięciu od razu widzisz kolor odpowiedzi i sesja szybko leci dalej.
                                        </span>
                                        <span class="flex flex-wrap items-center gap-1.5 pt-1" aria-label="Sterowanie strzałkami oraz klawiszami A, W, S i D">
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">←</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">↑</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">↓</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">→</kbd>
                                            <span class="mx-0.5 h-4 w-px bg-[#d1d5db]" aria-hidden="true"></span>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">A</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">W</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">S</kbd>
                                            <kbd class="rounded border border-[#e5e7eb] bg-[#f8fafc] px-2 py-1 text-[0.72rem] font-semibold text-[#4b5563]">D</kbd>
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <div
                                class="border transition"
                                :class="settingsSelectableCardClass(feedbackMode === 'instant_explanation')"
                            >
                                <label class="block cursor-pointer px-4 py-3.5">
                                    <input
                                        type="radio"
                                        :checked="feedbackMode === 'instant_explanation'"
                                        class="sr-only"
                                        @change="applyFeedbackModePreset('instant_explanation')"
                                    />
                                    <span class="flex items-start gap-3">
                                        <span
                                            class="-mt-[4px] -ml-[3px] inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition"
                                            :class="settingsSelectionIndicatorClass(feedbackMode === 'instant_explanation')"
                                        >
                                            <Check :size="11" :stroke-width="2.4" aria-hidden="true" />
                                        </span>
                                        <MessageSquareText
                                            :size="settingsCardIconSize"
                                            :stroke-width="1.9"
                                            class="-mt-[3px] shrink-0"
                                            :class="feedbackMode === 'instant_explanation' ? 'text-[#b8891d]' : 'text-[#334155]'"
                                            aria-hidden="true"
                                        />
                                        <span :class="settingsCardContentClass">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span :class="settingsCardTitleClass">Z wyjaśnieniem</span>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 text-[0.68rem] font-medium tracking-[0.02em]"
                                                    :class="isExamLikeShell ? 'bg-[#fff3cc] text-[#6b5208]' : 'border border-[#e5e7eb] bg-[#fafafa] text-[#4b5563]'"
                                                >
                                                    Polecany na start
                                                </span>
                                            </span>
                                            <span :class="settingsCardDescriptionClass">
                                                Po błędnej odpowiedzi zobaczysz poprawną odpowiedź i wyjaśnienie pytania.
                                            </span>
                                        </span>
                                    </span>
                                </label>

                                <div
                                    v-if="feedbackMode === 'instant_explanation'"
                                    :class="settingsInsetCardClass"
                                >
                                    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 space-y-2">
                                            <p :class="settingsCardTitleClass">Jak długo pokazywać wyjaśnienie?</p>
                                            <p :class="settingsCardDescriptionClass">
                                                Domyślnie dopasowujemy czas do długości tekstu.
                                            </p>
                                        </div>

                                        <div class="w-full px-0.5 sm:max-w-[19rem]">
                                            <input
                                                type="range"
                                                min="0"
                                                :max="hintTimingOptions.length - 1"
                                                step="1"
                                                :value="hintTimingSliderIndex"
                                                class="hint-timing-slider w-full"
                                                @input="setHintTimingPreferenceByIndex(($event.target as HTMLInputElement).value)"
                                            />

                                            <div class="mt-2.5 grid grid-cols-4 gap-1.5 text-center">
                                                <div
                                                    v-for="(option, index) in hintTimingOptions"
                                                    :key="option.value"
                                                    class="space-y-1"
                                                >
                                                    <span
                                                        class="mx-auto block h-1.5 w-1.5 rounded-full transition"
                                                        :class="option.value === hintTimingPreference
                                                            ? 'bg-[#161414]'
                                                            : index <= hintTimingSliderIndex
                                                            ? 'bg-[#6b7280]'
                                                            : 'bg-[#cbd5e1]'"
                                                    />
                                                    <span
                                                        class="block whitespace-nowrap text-[0.72rem] leading-none transition"
                                                        :class="option.value === hintTimingPreference
                                                            ? 'font-semibold text-[#161414]'
                                                            : 'text-[#6b7280]'"
                                                    >
                                                        {{ option.label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <label
                                class="block cursor-pointer border px-4 py-3.5 transition"
                                :class="settingsSelectableCardClass(feedbackMode === 'review')"
                            >
                                <input
                                    type="radio"
                                    :checked="feedbackMode === 'review'"
                                    class="sr-only"
                                    @change="applyFeedbackModePreset('review')"
                                />
                                <span class="flex items-start gap-3">
                                    <span
                                        class="-mt-[4px] -ml-[3px] inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition"
                                        :class="settingsSelectionIndicatorClass(feedbackMode === 'review')"
                                    >
                                        <Check :size="11" :stroke-width="2.4" aria-hidden="true" />
                                    </span>
                                    <Trophy
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0"
                                        :class="feedbackMode === 'review' ? 'text-[#b8891d]' : 'text-[#334155]'"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Wynik</span>
                                        <span :class="settingsCardDescriptionClass">
                                            W trakcie sesji nic Ci nie podpowiada. Wynik i omówienie zobaczysz dopiero na końcu.
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </section>

                        <section
                            v-if="canUseQuestionAudio"
                            :class="settingsSectionDividerClass"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Speech
                                        :size="settingsSectionIconSize"
                                        :stroke-width="1.9"
                                        class="-translate-y-[2px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <p :class="settingsFeatureSectionTitleClass">
                                        Czytanie pytania na głos
                                    </p>
                                </div>
                                <p :class="sidePanelDescriptionClass">
                                    Wybierz, czy pytanie ma być przeczytane na głos.
                                </p>
                            </div>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="showQuestionAudioControl"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] mr-2 h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <Volume2
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Pokaż ikonę głośnika</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Przy pytaniu pojawi się ikona głośnika. Kliknij ją, aby usłyszeć pytanie.
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <label
                                :class="[
                                    settingsStaticCardClass,
                                    !showQuestionAudioControl ? 'opacity-60' : '',
                                ]"
                            >
                                <input
                                    v-model="autoPlayQuestionAudio"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] mr-2 h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0 disabled:cursor-not-allowed"
                                    :disabled="!showQuestionAudioControl"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <Repeat2
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Czytaj kolejne pytania automatycznie</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Po przejściu do następnego pytania jego treść zostanie przeczytana na głos.
                                        </span>
                                        <span :class="settingsCardDescriptionClass">
                                            Jeśli dźwięk nie włączy się sam, kliknij ikonę głośnika.
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="autoPlayCorrectAnswerAudio"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] mr-2 h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <CircleCheck
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">
                                            Po błędzie przeczytaj poprawną odpowiedź (tylko pytania specjalistyczne)
                                        </span>
                                        <span :class="settingsCardDescriptionClass">
                                            Gdy wybierzesz złą odpowiedź, usłyszysz tę poprawną.
                                        </span>
                                        <span :class="settingsCardDescriptionClass">
                                            W trybie Wynik poprawną odpowiedź poznasz dopiero w podsumowaniu.
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </section>

                        <section :class="settingsSectionDividerClass">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <img
                                        :src="headThoughtIcon"
                                        alt=""
                                        class="-translate-y-[2px] h-7 w-7 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <p :class="settingsFeatureSectionTitleClass">
                                        Wskazówki do pytania
                                    </p>
                                </div>
                                <p :class="sidePanelDescriptionClass">
                                    Strzałki, pogrubienia i kolory pomagają szybciej zauważyć to, co ważne w pytaniu.
                                </p>
                            </div>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="enableInlineBold"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <Bold
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Pogrubienia</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Do szybkiego czytania pytań. Pokazuj wyróżnione słowa i fragmenty.
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="enableInlineColors"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <Palette
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Kolory</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Do kojarzenia i zapamiętywania. Pokazuj zielone i czerwone akcenty w treści.
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="showVisualAnnotations"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <ArrowUpRight
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">Strzałki</span>
                                        <span :class="settingsCardDescriptionClass">
                                            Pomocne strzałki pokazujące na co masz zwrócić uwagę w pytaniu.
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </section>

                        <section :class="settingsSectionDividerClass">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <SquarePlay
                                        :size="settingsSectionIconSize"
                                        :stroke-width="1.9"
                                        class="-translate-y-[2px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <p :class="settingsFeatureSectionTitleClass">
                                        Pytania z filmem
                                    </p>
                                </div>
                                <p :class="sidePanelDescriptionClass">
                                    Zdecyduj, czy film ma ruszać sam, czy chcesz włączać go ręcznie.
                                </p>
                            </div>

                            <label :class="settingsStaticCardClass">
                                <input
                                    v-model="autoPlayVideos"
                                    type="checkbox"
                                    class="-mt-[2px] -ml-[3px] mr-2 h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                />
                                <span class="flex min-w-0 flex-1 items-start gap-3">
                                    <CirclePlay
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <span :class="settingsCardContentClass">
                                        <span :class="settingsCardTitleClass">
                                            Odtwarzaj filmy automatycznie
                                        </span>
                                        <span :class="settingsCardDescriptionClass">
                                            Wyłącz to, jeśli wolisz sam uruchamiać filmy i mieć nad nimi pełną kontrolę.
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <div :class="settingsGroupedCardClass">
                                <div class="flex items-start gap-3">
                                    <Gauge
                                        :size="settingsCardIconSize"
                                        :stroke-width="1.9"
                                        class="-mt-[3px] shrink-0 text-[#334155]"
                                        aria-hidden="true"
                                    />
                                    <div :class="settingsCardContentClass">
                                        <p :class="settingsCardTitleClass">Prędkość filmu</p>
                                        <p :class="settingsCardDescriptionClass">
                                            Gdy pytanie trzyma znak na końcu filmu, możesz przyspieszyć odtwarzanie i nie czekać bez sensu.
                                        </p>
                                        <p
                                            v-if="autoJumpToVideoEnding"
                                            :class="settingsCardDescriptionClass"
                                        >
                                            Przy pokazywaniu końcówki wybór prędkości jest tymczasowo wyłączony, a film zawsze odtwarza się w normalnym tempie.
                                        </p>
                                    </div>
                                </div>

                                <div class="ml-[2.125rem] flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        class="min-w-[4.25rem] border px-3 py-2 text-center text-sm font-medium transition disabled:cursor-not-allowed"
                                        :class="settingsSegmentButtonClass(videoPlaybackRate === 1, autoJumpToVideoEnding)"
                                        :disabled="autoJumpToVideoEnding"
                                        @click="videoPlaybackRate = 1"
                                    >
                                        1x
                                    </button>
                                    <button
                                        type="button"
                                        class="min-w-[4.25rem] border px-3 py-2 text-center text-sm font-medium transition disabled:cursor-not-allowed"
                                        :class="settingsSegmentButtonClass(videoPlaybackRate === 2, autoJumpToVideoEnding)"
                                        :disabled="autoJumpToVideoEnding"
                                        @click="videoPlaybackRate = 2"
                                    >
                                        2x
                                    </button>
                                    <button
                                        type="button"
                                        class="min-w-[4.25rem] border px-3 py-2 text-center text-sm font-medium transition disabled:cursor-not-allowed"
                                        :class="settingsSegmentButtonClass(videoPlaybackRate === 4, autoJumpToVideoEnding)"
                                        :disabled="autoJumpToVideoEnding"
                                        @click="videoPlaybackRate = 4"
                                    >
                                        4x
                                    </button>
                                </div>

                                <label :class="settingsStaticCardClass">
                                    <input
                                        v-model="autoJumpToVideoEnding"
                                        type="checkbox"
                                        class="-mt-[2px] -ml-[3px] mr-2 h-4 w-4 rounded-none border-[#9ca3af] text-[#161414] focus:ring-0"
                                    />
                                    <span class="flex min-w-0 flex-1 items-start gap-3">
                                        <FastForward
                                            :size="settingsCardIconSize"
                                            :stroke-width="1.9"
                                            class="-mt-[3px] shrink-0 text-[#334155]"
                                            aria-hidden="true"
                                        />
                                        <span :class="settingsCardContentClass">
                                            <span :class="settingsCardTitleClass">
                                                Pokaż końcówkę filmu
                                            </span>
                                            <span :class="settingsCardDescriptionClass">
                                                Gdy to włączysz, każdy nowy film od razu przeskoczy do ostatnich 2 sekund.
                                            </span>
                                            <span :class="settingsCardDescriptionClass">
                                                Jeśli chcesz, nadal możesz odtworzyć cały film albo kliknąć końcówkę ręcznie na samym kadrze.
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </section>
                    </div>
                </aside>
            </Transition>

            <section
                v-if="activeQuestion"
                data-testid="session-current-question"
                :class="activeQuestionSectionClass"
            >
                <div
                    class="mx-auto"
                    :class="activeQuestionShellClass"
                >
                    <div :class="activeQuestionInnerClass">
                        <div
                            v-if="isExamLikeShell"
                            class="mx-0 border-b border-[#e5e7eb] bg-white px-4 min-[1024px]:rounded-none min-[1024px]:border min-[1024px]:border-[#e5e7eb] min-[1024px]:px-0 min-[1024px]:shadow-[0_16px_32px_rgba(15,23,42,0.04)]"
                        >
                            <div class="flex items-center justify-between gap-3 py-3 min-[1024px]:items-start min-[1024px]:px-3 min-[1024px]:py-2.5">
                                <div class="grid min-w-0 flex-1 grid-cols-[auto_auto] items-center gap-x-5 gap-y-0 min-[1024px]:grid-cols-[6.5rem_6.5rem_8.75rem_minmax(0,13rem)] min-[1024px]:gap-2 xl:grid-cols-[6.25rem_6.25rem_8.5rem_minmax(0,14rem)]">
                                    <div class="min-w-[2.8rem] px-0 py-0 text-sm min-[1024px]:min-w-0 min-[1024px]:rounded-none min-[1024px]:border min-[1024px]:border-[#eceff3] min-[1024px]:bg-[#fafafa] min-[1024px]:px-3 min-[1024px]:py-2">
                                        <p class="text-[0.58rem] font-medium uppercase tracking-[0.08em] text-[#6b7280] min-[1024px]:text-[0.62rem]">
                                            {{ mobileSessionQuestionLabel }}
                                        </p>
                                        <p class="mt-0.5 font-semibold leading-tight text-[#111827] min-[1024px]:mt-1">
                                            {{ currentQuestionNumber ?? displayProgress.answered + 1 }} / {{ displayProgress.total }}
                                        </p>
                                    </div>

                                    <div class="min-w-[2.2rem] px-0 py-0 text-sm min-[1024px]:min-w-0 min-[1024px]:rounded-none min-[1024px]:border min-[1024px]:border-[#eceff3] min-[1024px]:bg-[#fafafa] min-[1024px]:px-3 min-[1024px]:py-2">
                                        <p class="text-[0.58rem] font-medium uppercase tracking-[0.08em] text-[#6b7280] min-[1024px]:text-[0.62rem]">
                                            {{ mobileSessionCategoryLabel }}
                                        </p>
                                        <p class="mt-0.5 font-semibold leading-tight text-[#111827] min-[1024px]:mt-1">
                                            {{ sessionState.license_category_code ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="hidden rounded-none border border-[#eceff3] bg-[#fafafa] px-3 py-2 text-sm min-[1024px]:block">
                                        <p class="text-[0.62rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]">
                                            Tryb
                                        </p>
                                        <p class="mt-1 font-semibold text-[#111827]">
                                            {{ formatMode(sessionState.mode) }}
                                        </p>
                                    </div>

                                    <div class="hidden min-w-0 rounded-none border border-[#eceff3] bg-[#fafafa] px-3 py-2 text-sm min-[1024px]:block">
                                        <p class="text-[0.62rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]">
                                            Dział
                                        </p>
                                        <p class="mt-1 truncate font-semibold text-[#111827]">
                                            {{ currentQuestionContextLabel }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-start gap-2.5">
                                    <button
                                        v-if="showMobileSessionMenuTrigger"
                                        type="button"
                                        :class="sessionOptionsButtonClass"
                                        @click="openMobileSessionMenu"
                                    >
                                        Opcje
                                    </button>

                                    <button
                                        type="button"
                                        class="shrink-0 self-start whitespace-nowrap rounded-none border border-[#d2b35b] bg-[#efc54f] px-3.5 py-2 text-sm font-semibold text-[#463309] transition hover:border-[#c8a949] hover:bg-[#e8bd4c] disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="completeForm.processing"
                                        @click="finishSession"
                                    >
                                        {{ finishSessionButtonLabel }}
                                    </button>
                                </div>

                            </div>
                        </div>

                        <div v-else :class="questionHeaderBarClass">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="shrink-0 font-semibold text-[#1f1d18]">
                                    {{ currentQuestionNumber ?? displayProgress.answered + 1 }} / {{ displayProgress.total }}
                                </span>
                                <span :class="questionHeaderMetaClass">
                                    {{ currentQuestionContextLabel }}
                                </span>
                                <span
                                    :class="questionSourceBadgeClass"
                                >
                                    <img
                                        :src="questionSourceEmblem"
                                        alt=""
                                        class="h-4 w-4 object-contain"
                                        aria-hidden="true"
                                    />
                                    <span>{{ currentQuestionSourceBadgeLabel }}</span>
                                    <span class="font-semibold text-[#1f1d18]">
                                        {{ currentQuestionSourceNumber }}
                                    </span>
                                </span>
                            </div>

                            <div class="ml-auto flex shrink-0 items-start gap-2 text-xs sm:text-sm">
                                <button
                                    v-if="showMobileSessionMenuTrigger"
                                    type="button"
                                    :class="sessionOptionsButtonClass"
                                    @click="openMobileSessionMenu"
                                >
                                    Opcje
                                </button>
                                <button
                                    type="button"
                                    :class="finishSessionButtonClass"
                                    :disabled="completeForm.processing"
                                    @click="finishSession"
                                >
                                    {{ finishSessionButtonLabel }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="xl:min-h-0"
                            :class="workspaceHeightClass"
                        >
                        <div :class="workspaceShellClass">
                            <div aria-hidden="true" :class="workspaceBackdropClass" />
                            <div class="relative" :class="contentColumnClass">
                        <div :class="mediaWorkspacePaneClass">
                            <div
                                v-if="questionStage === 'preview' && isExamMode"
                                :class="[previewStageClass, mediaFrameClass]"
                            >
                                <p class="text-sm text-neutral-600">
                                    Czas na zapoznanie sie z trescia pytania.
                                </p>
                                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-neutral-300 text-neutral-800">
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-8 w-8"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path d="M9 4h6l1.5 2H20a2 2 0 0 1 2 2v8a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8a2 2 0 0 1 2-2h3.5L9 4Z" />
                                        <circle cx="12" cy="13" r="4" />
                                    </svg>
                                </div>
                                <p class="max-w-md text-lg font-medium">
                                    Kliknij <span class="font-bold">Start</span>, aby przejść do odpowiedzi.
                                </p>
                            </div>

                            <div
                                v-else
                                :class="[mediaStageSectionClass, mediaFrameClass]"
                            >
                                <div
                                    class="flex-1"
                                    :class="sessionMediaChromeClass"
                                >
                                    <div
                                        v-if="pjmQuestionAsset || activeQuestion.media.length"
                                        :class="[
                                            mediaViewportClass,
                                            pjmQuestionAsset ? 'overflow-visible md:overflow-hidden' : 'overflow-hidden',
                                        ]"
                                    >
                                        <div
                                            :class="questionMediaLayoutClass"
                                        >
                                            <div
                                                v-if="activeQuestion.media.length"
                                                :class="mediaStageClass"
                                                :style="questionMediaPanelStyle"
                                            >
                                            <div
                                                v-for="(media, index) in activeQuestion.media"
                                                :key="`${activeQuestion.id}-${index}`"
                                                class="relative flex h-full w-full items-center justify-center overflow-hidden"
                                            >
                                                <div
                                                    v-if="media.kind === 'image' && media.url"
                                                    :class="imagePresentationClass"
                                                >
                                                    <QuestionImageWithAnnotations
                                                        test-id="session-question-image"
                                                        :src="displayImageUrl(media, shouldShowCurrentImageAnnotations)"
                                                        alt=""
                                                        loading="eager"
                                                        :natural-width="media.width ?? null"
                                                        :natural-height="media.height ?? null"
                                                        :annotations="shouldShowCurrentImageAnnotations ? currentQuestionImageAnnotations : []"
                                                        :presentation-class="'h-full w-full'"
                                                        :image-class="imageAssetClass"
                                                    />
                                                </div>
                                                <template v-else-if="media.kind === 'video' && media.url">
                                                    <div :class="videoPresentationClass">
                                                        <video
                                                            v-if="videoActivatedState[activeVideoRefKey(index)]"
                                                            data-testid="session-question-video"
                                                            :ref="(element) => registerVideoElement(activeVideoRefKey(index), element)"
                                                            :autoplay="autoPlayVideos"
                                                            :muted="autoPlayVideos"
                                                            preload="none"
                                                            playsinline
                                                            :poster="media.poster_url ?? undefined"
                                                            disablepictureinpicture
                                                            controlslist="nodownload noplaybackrate noremoteplayback nofullscreen"
                                                            :class="videoAssetClass"
                                                            @play="setVideoPlaybackState(activeVideoRefKey(index), true)"
                                                            @pause="setVideoPlaybackState(activeVideoRefKey(index), false)"
                                                            @ended="setVideoPlaybackState(activeVideoRefKey(index), false)"
                                                        >
                                                            <source :src="media.url" :type="media.mime_type ?? undefined" />
                                                        </video>
                                                        <QuestionVideoFrameWithAnnotations
                                                            v-if="shouldRenderCurrentQuestionVideoFramePreview(index)"
                                                            test-id="session-question-video-frame"
                                                            :src="media.url"
                                                            :poster="media.poster_url ?? null"
                                                            :frame-time-seconds="currentQuestionVideoFrameTimeSeconds"
                                                            :natural-width="media.width ?? null"
                                                            :natural-height="media.height ?? null"
                                                            :annotations="currentQuestionVideoFrameAnnotations"
                                                            :show-poster-while-preparing="shouldShowPosterWhilePreparingQuestionVideoFrame({
                                                                isVideoActivated: Boolean(videoActivatedState[activeVideoRefKey(index)]),
                                                            })"
                                                            :presentation-class="resolveQuestionVideoFramePreviewPresentationClass({
                                                                isVideoActivated: Boolean(videoActivatedState[activeVideoRefKey(index)]),
                                                            })"
                                                            :video-class="videoAssetClass"
                                                        />
                                                        <img
                                                            v-else-if="!videoActivatedState[activeVideoRefKey(index)] && media.poster_url"
                                                            data-testid="session-question-video-poster"
                                                            :src="media.poster_url"
                                                            alt=""
                                                            loading="eager"
                                                            :class="videoAssetClass"
                                                        />
                                                        <div
                                                            v-else-if="!videoActivatedState[activeVideoRefKey(index)]"
                                                            class="relative flex h-full w-full items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_50%_35%,rgba(255,255,255,0.9)_0%,rgba(245,242,235,0.9)_42%,rgba(233,228,216,0.96)_100%)] px-6 text-center text-neutral-700"
                                                        >
                                                            <div
                                                                aria-hidden="true"
                                                                class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.12),rgba(255,255,255,0)_42%,rgba(90,82,70,0.06)_100%)]"
                                                            />
                                                            <div class="relative flex max-w-[18rem] flex-col items-center gap-2.5">
                                                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/72 text-[#3d392f] shadow-[0_10px_24px_rgba(36,33,28,0.08)] ring-1 ring-black/5">
                                                                    <svg
                                                                        aria-hidden="true"
                                                                        viewBox="0 0 24 24"
                                                                        class="h-5 w-5"
                                                                        fill="none"
                                                                        stroke="currentColor"
                                                                        stroke-width="1.7"
                                                                    >
                                                                        <rect x="3.75" y="5.25" width="16.5" height="13.5" rx="1.5" />
                                                                        <path d="M8.5 9.25v5.5l4.75-2.75-4.75-2.75Z" fill="currentColor" stroke="none" />
                                                                    </svg>
                                                                </span>
                                                                <div class="space-y-1">
                                                                    <p class="text-sm font-medium text-[#3b372f]">
                                                                        To pytanie ma krótki film.
                                                                    </p>
                                                                    <p class="text-[0.82rem] leading-5 text-[#6a6458]">
                                                                        Kliknij, aby obejrzeć sytuację na nagraniu.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-if="!videoPlaybackState[activeVideoRefKey(index)]"
                                                        class="pointer-events-none absolute bottom-1.5 left-1/2 z-20 flex w-auto max-w-[13rem] -translate-x-1/2 items-center justify-center gap-1.5 sm:bottom-5 sm:w-auto sm:max-w-none sm:gap-2"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="pointer-events-auto inline-flex min-h-[1.85rem] w-[6.25rem] flex-none items-center justify-center gap-0.5 rounded-full border border-white/35 bg-[#111827]/28 px-1.5 py-1 text-[0.6rem] font-semibold tracking-[0.01em] text-white shadow-[0_4px_10px_rgba(0,0,0,0.1)] backdrop-blur-[3px] transition hover:bg-[#111827]/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/85 focus-visible:ring-offset-2 focus-visible:ring-offset-black/30 sm:min-h-[2.45rem] sm:w-auto sm:gap-1.5 sm:border-white/30 sm:bg-[#111827]/72 sm:px-4 sm:py-2 sm:text-[0.8rem] sm:shadow-[0_12px_28px_rgba(0,0,0,0.18)] sm:backdrop-blur-[8px] sm:hover:bg-[#111827]/84"
                                                            @click.stop="void toggleVideoPlayback(activeVideoRefKey(index), { playFromBeginning: true })"
                                                        >
                                                            <svg
                                                                aria-hidden="true"
                                                                viewBox="0 0 24 24"
                                                                class="h-3 w-3 shrink-0 translate-x-[1px] fill-current sm:h-4 sm:w-4"
                                                            >
                                                                <path d="M8 6.5v11l9-5.5-9-5.5Z" />
                                                            </svg>
                                                            <span class="sm:hidden">Cały film</span>
                                                            <span class="hidden sm:inline">Odtwórz cały film</span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="pointer-events-auto inline-flex min-h-[1.85rem] w-[6.25rem] flex-none items-center justify-center gap-0.5 rounded-full border border-white/35 bg-[#111827]/28 px-1.5 py-1 text-[0.6rem] font-semibold tracking-[0.01em] text-white shadow-[0_4px_10px_rgba(0,0,0,0.1)] backdrop-blur-[3px] transition hover:bg-[#111827]/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/85 focus-visible:ring-offset-2 focus-visible:ring-offset-black/30 sm:min-h-[2.45rem] sm:w-auto sm:gap-1.5 sm:border-white/30 sm:bg-[#111827]/72 sm:px-4 sm:py-2 sm:text-[0.8rem] sm:shadow-[0_12px_28px_rgba(0,0,0,0.18)] sm:backdrop-blur-[8px] sm:hover:bg-[#111827]/84"
                                                            @click.stop="void jumpToVideoEnding(activeVideoRefKey(index))"
                                                        >
                                                            <svg
                                                                aria-hidden="true"
                                                                viewBox="0 0 24 24"
                                                                class="h-3 w-3 shrink-0 fill-current sm:h-4 sm:w-4"
                                                            >
                                                                <path d="M5.5 6.75v10.5L13.75 12 5.5 6.75Z" />
                                                                <path d="M14.25 6.75v10.5L20 12l-5.75-5.25Z" />
                                                            </svg>
                                                            <span class="sm:hidden">Końcówka</span>
                                                            <span class="hidden sm:inline">Pokaż końcówkę filmu</span>
                                                        </button>
                                                    </div>
                                                </template>
                                                <div
                                                    v-else
                                                    class="flex h-full items-center justify-center p-6 text-sm text-neutral-600"
                                                >
                                                    Nieobslugiwany typ medium.
                                                </div>
                                            </div>
                                        </div>

                                            <div
                                                v-else-if="shouldShowQuestionMediaTextPlaceholder"
                                                :class="[mediaStageClass, 'min-h-[8rem] lg:min-h-0']"
                                                :style="questionMediaPlaceholderPanelStyle"
                                            >
                                                <div class="flex h-full w-full items-center justify-center overflow-hidden bg-[#f7f8fa] px-6 text-center text-[#374151]">
                                                    <div class="max-w-[22rem] space-y-3">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0f4da2]">
                                                            Pytanie tekstowe GOV.PL
                                                        </p>
                                                        <p class="text-lg font-semibold text-[#111827]">
                                                            To pytanie GOV.PL jest tekstowe.
                                                        </p>
                                                        <p class="text-sm leading-6 text-[#4b5563]">
                                                            W bazie nie ma do niego zdjęcia ani filmu. Materiałem egzaminacyjnym jest tutaj treść pytania i odpowiedzi.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                v-if="pjmQuestionAsset"
                                                :class="mediaStageClass"
                                                :style="questionMediaPanelStyle"
                                            >
                                                <PjmVideoBlock
                                                    :asset="pjmQuestionAsset"
                                                    label="Tłumaczenie PJM pytania"
                                                    :compact="shouldShowQuestionMediaTextPlaceholder"
                                                    test-id="pjm-question-video"
                                                    class="h-full w-full"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="flex items-center justify-center px-6 text-center"
                                        :class="mediaViewportClass"
                                    >
                                        <div class="space-y-2">
                                            <p class="text-sm uppercase tracking-[0.16em] text-neutral-600">
                                                Brak medium
                                            </p>
                                            <p class="text-lg font-medium">
                                                To pytanie wyswietla tylko tresc i odpowiedzi.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            :class="promptOuterClass"
                        >
                        <div :class="promptContainerClass">
                            <div class="w-full min-w-0" :class="promptStackClass">
                                <Transition name="focus-copy" mode="out-in">
                                    <div
                                        v-if="shouldShowExplanationCard"
                                        key="hint"
                                        class="overflow-hidden"
                                        :class="explanationCalloutClass"
                                        :style="promptFrameStyle"
                                    >
                                        <div
                                            v-if="canShowInlineEditControls && activeQuestion"
                                            class="mb-3 flex flex-wrap justify-end gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                                @click="openQuestionEditor(activeQuestion)"
                                            >
                                                Edytuj pytanie
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                                @click="openExplanationEditor(activeQuestion)"
                                            >
                                                Edytuj wyjaśnienie
                                            </button>
                                            <a
                                                :href="`/admin/questions/${activeQuestion.id}/edit`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                            >
                                                Edytuj grafikę
                                            </a>
                                        </div>
                                        <QuestionExplanationRuntimeBlock
                                            :explanation-html="currentExplanationHtml"
                                            :fallback-text="currentQuestionExplanationAsset?.body ?? null"
                                            :asset="currentQuestionExplanationAsset"
                                            :sign-references="currentQuestionExplanationSignReferences"
                                            :show-image="visualExplanationsMode !== 'off'"
                                            :show-sign-references="visualExplanationsMode !== 'off'"
                                            :palette="inlineFormattingPalette"
                                            :enable-bold-formatting="enableInlineBold"
                                            :enable-color-formatting="enableInlineColors"
                                        />
                                        <div
                                            v-if="shouldShowExplanationCard && publicExplanationUrl"
                                            class="absolute right-0 bottom-0 z-10"
                                        >
                                            <a
                                                :href="publicExplanationUrl"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1.5 bg-white/95 px-2 py-1 text-xs font-semibold text-[#374151] shadow-[-6px_-6px_14px_rgba(255,255,255,0.96)] transition hover:text-[#d9251b]"
                                            >
                                                Zobacz pełne wyjaśnienie
                                                <ExternalLink :size="14" :stroke-width="1.8" aria-hidden="true" />
                                            </a>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        key="prompt"
                                        class="w-full min-w-0"
                                        :class="promptScrollClass"
                                        :style="promptFrameStyle"
                                    >
                                        <div
                                            v-if="isExamLikeShell || canShowInlineEditControls"
                                            class="flex flex-wrap items-center justify-between gap-3"
                                        >
                                            <div
                                                v-if="isExamLikeShell"
                                                class="flex min-w-0 items-center gap-2"
                                            >
                                                <p class="text-[0.64rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]">
                                                    Treść pytania
                                                </p>
                                                <QuestionAudioControl
                                                    v-if="shouldShowQuestionAudioControl"
                                                    :key="`question-audio-classic-${activeQuestion.id}-${currentQuestionAudio?.asset_key ?? currentQuestionAudio?.url ?? 'audio'}`"
                                                    ref="questionAudioControlRef"
                                                    class="shrink-0"
                                                    :asset="currentQuestionAudio"
                                                    variant="classic"
                                                    :autoplay="shouldAutoPlayQuestionAudio"
                                                    :autoplay-key="questionAudioAutoplayKey"
                                                    @play="pauseAudibleQuestionVideos"
                                                />
                                            </div>
                                            <div
                                                v-if="canShowInlineEditControls && activeQuestion && !shouldShowExplanationCard"
                                                class="ml-auto flex flex-wrap items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                                    @click="openQuestionEditor(activeQuestion)"
                                                >
                                                    Edytuj pytanie
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                                    @click="openExplanationEditor(activeQuestion)"
                                                >
                                                    Edytuj wyjaśnienie
                                                </button>
                                                <a
                                                    :href="`/admin/questions/${activeQuestion.id}/edit`"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                                >
                                                    Edytuj grafikę
                                                </a>
                                            </div>
                                        </div>
                                        <div
                                            v-if="!isExamLikeShell && shouldShowQuestionAudioControl"
                                            class="flex items-start gap-2.5"
                                            :class="canShowInlineEditControls ? 'mt-2.5' : ''"
                                        >
                                            <QuestionAudioControl
                                                :key="`question-audio-zen-${activeQuestion.id}-${currentQuestionAudio?.asset_key ?? currentQuestionAudio?.url ?? 'audio'}`"
                                                ref="questionAudioControlRef"
                                                class="mt-[0.125rem] shrink-0"
                                                :asset="currentQuestionAudio"
                                                variant="zen"
                                                :autoplay="shouldAutoPlayQuestionAudio"
                                                :autoplay-key="questionAudioAutoplayKey"
                                                @play="pauseAudibleQuestionVideos"
                                            />
                                            <h2
                                                data-testid="session-question-prompt"
                                                class="question-copy min-w-0 flex-1 break-words font-medium tracking-tight [overflow-wrap:anywhere] [&_strong]:font-semibold [&_strong]:text-inherit"
                                                :class="questionPromptClass"
                                                v-html="currentQuestionPromptHtml"
                                            />
                                        </div>
                                        <h2
                                            v-else
                                            data-testid="session-question-prompt"
                                            class="question-copy break-words font-medium tracking-tight [overflow-wrap:anywhere] [&_strong]:font-semibold [&_strong]:text-inherit"
                                            :class="[questionPromptClass, (isExamLikeShell || canShowInlineEditControls) ? 'mt-2.5' : '']"
                                            v-html="currentQuestionPromptHtml"
                                        />
                                    </div>
                                </Transition>
                            </div>
                        </div>
                        </div>

                        <div
                            ref="mobileAnswerDockRef"
                            :class="mobileAnswerDockWrapperClass"
                            :style="mobileAnswerDockWrapperStyle"
                        >
                        <div :class="answerSectionClass">
                            <div
                                v-if="questionStage !== 'preview' || !isExamMode"
                            >
                                <div
                                    v-if="isExamLikeShell"
                                    :class="examLikeAnswerOptionsClass"
                                >
                                    <button
                                        v-for="option in activeQuestion.options"
                                        :key="option.key"
                                        type="button"
                                        :data-testid="`answer-option-${option.key}`"
                                        :data-answer-state="answerOptionVisualState(option.key)"
                                        class="transition disabled:cursor-not-allowed"
                                        :class="activeQuestion.question_type === 'boolean'
                                            ? [
                                                examLikeBooleanAnswerButtonClass,
                                                examLikeAnswerOptionClass(option.key),
                                            ]
                                            : [
                                                'flex w-full items-start gap-0 overflow-hidden rounded-none border text-left text-[0.95rem]',
                                                examLikeAnswerOptionClass(option.key),
                                            ]"
                                        :disabled="!canSelectAnswer"
                                        @click="selectAnswer(option.key)"
                                    >
                                        <span
                                            v-if="activeQuestion.question_type !== 'boolean'"
                                            class="inline-flex min-w-[2.5rem] items-center justify-center self-stretch border-r px-2 py-2.5 text-xs font-semibold text-white transition-colors duration-200"
                                            :class="examLikeAnswerLabelClass(option.key)"
                                        >
                                            {{ option.label }}
                                        </span>
                                        <span
                                            class="block w-full"
                                            :class="activeQuestion.question_type === 'boolean'
                                                ? examLikeBooleanAnswerTextClass
                                                : 'px-3 py-3 leading-6'"
                                        >
                                            {{
                                                activeQuestion.question_type === 'boolean'
                                                    ? option.text.toUpperCase()
                                                    : option.text
                                            }}
                                        </span>
                                    </button>
                                </div>

                                <div
                                    v-else
                                    class="grid gap-2.5 sm:gap-3"
                                    :class="activeQuestion.question_type === 'boolean'
                                        ? 'sm:grid-cols-2'
                                        : 'sm:grid-cols-3'"
                                >
                                    <button
                                        v-for="option in activeQuestion.options"
                                        :key="option.key"
                                        type="button"
                                        :data-testid="`answer-option-${option.key}`"
                                        :data-answer-state="answerOptionVisualState(option.key)"
                                        class="text-center font-semibold tracking-tight text-neutral-900 transition-[background-color,box-shadow,transform,color] duration-200 ease-out disabled:cursor-not-allowed"
                                        :class="[
                                            answerButtonClass,
                                            answerOptionStateClass(option.key),
                                        ]"
                                        :disabled="!canSelectAnswer"
                                        @click="selectAnswer(option.key)"
                                    >
                                        {{
                                            activeQuestion.question_type === 'boolean'
                                                ? option.text.toUpperCase()
                                                : `${option.label}. ${option.text}`
                                        }}
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="props.session.mode === 'sr_review'"
                                class="mx-auto max-w-[42rem]"
                            >
                                <button
                                    type="button"
                                    :class="isExamLikeShell
                                        ? 'flex min-h-[3.25rem] w-full items-center justify-center rounded-none border border-[#d1d5db] bg-white px-4 py-3 text-[0.95rem] font-semibold tracking-tight text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#111827] disabled:cursor-not-allowed disabled:opacity-35'
                                        : 'flex min-h-[3.25rem] w-full items-center justify-center rounded-none border border-[#e5e7eb] bg-white/72 px-4 py-3 text-[0.95rem] font-semibold tracking-tight text-neutral-700 transition hover:bg-white hover:text-neutral-900 disabled:cursor-not-allowed disabled:opacity-35'"
                                    :disabled="!canMarkUnknownAnswer"
                                    aria-label="Oznacz pytanie jako Nie wiem"
                                    @click="markUnknownAnswer"
                                >
                                    <span>Nie wiem</span>
                                </button>
                            </div>

                            <div
                                class="mx-auto grid text-sm"
                                :class="isLocalLearningMode
                                    ? useCompactAnswerActions
                                        ? 'max-w-[42rem] grid-cols-3 gap-1.5'
                                        : 'max-w-[42rem] grid-cols-3 gap-2'
                                    : 'max-w-[30rem] grid-cols-1 gap-2'"
                            >
                                <button
                                    v-if="isLocalLearningMode"
                                    type="button"
                                    aria-label="Poprzednie pytanie"
                                    :class="isExamLikeShell
                                        ? useCompactAnswerActions
                                            ? 'inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-2 py-1.5 text-[0.78rem] font-medium leading-5 text-[#4b5563] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#111827] disabled:cursor-not-allowed disabled:opacity-35'
                                            : 'inline-flex items-center justify-center rounded-none border border-[#d1d5db] bg-white px-3 py-2 text-[0.84rem] font-medium text-[#4b5563] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#111827] disabled:cursor-not-allowed disabled:opacity-35'
                                        : 'inline-flex items-center justify-center px-3 py-1.5 text-[0.84rem] font-medium text-neutral-500 transition-[background-color,color] duration-200 ease-out hover:bg-white/34 hover:text-neutral-800 disabled:cursor-not-allowed disabled:opacity-35'"
                                    :disabled="previousActionDisabled"
                                    @click="() => void moveToPreviousQuestion()"
                                >
                                    <ArrowLeft :size="18" :stroke-width="1.8" aria-hidden="true" />
                                </button>

                                <button
                                    v-if="isLocalLearningMode"
                                    type="button"
                                    :class="isExamLikeShell
                                        ? useCompactAnswerActions
                                            ? 'rounded-none border border-[#d1d5db] bg-white px-2 py-1.5 text-[0.78rem] font-medium leading-5 text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#111827] disabled:cursor-not-allowed disabled:opacity-35'
                                            : 'rounded-none border border-[#d1d5db] bg-white px-3 py-2 text-[0.84rem] font-medium text-[#374151] transition hover:border-[#9ca3af] hover:bg-[#fafafa] hover:text-[#111827] disabled:cursor-not-allowed disabled:opacity-35'
                                        : 'px-3 py-1.5 text-[0.84rem] font-medium text-neutral-500 transition-[background-color,color] duration-200 ease-out hover:bg-white/34 hover:text-neutral-800 disabled:cursor-not-allowed disabled:opacity-35'"
                                    :disabled="!canToggleExplanationOnDemand"
                                    @click="toggleExplanationOnDemand"
                                >
                                    {{ explanationToggleDisplayLabel }}
                                </button>

                                <button
                                    type="button"
                                    :aria-label="primaryActionLabel === 'Następne pytanie' ? primaryActionLabel : undefined"
                                    :class="isExamLikeShell
                                        ? useCompactAnswerActions
                                            ? 'inline-flex items-center justify-center rounded-none border border-[#0071ce] bg-[#0071ce] px-2 py-1.5 text-[0.78rem] font-semibold leading-5 text-white transition hover:border-[#005fae] hover:bg-[#005fae] disabled:cursor-not-allowed disabled:border-[#d1d5db] disabled:bg-[#f3f4f6] disabled:text-[#9ca3af]'
                                            : 'inline-flex items-center justify-center rounded-none border border-[#0071ce] bg-[#0071ce] px-3 py-2 text-[0.84rem] font-semibold text-white transition hover:border-[#005fae] hover:bg-[#005fae] disabled:cursor-not-allowed disabled:border-[#d1d5db] disabled:bg-[#f3f4f6] disabled:text-[#9ca3af]'
                                        : 'inline-flex items-center justify-center px-3 py-1.5 text-[0.84rem] font-medium text-neutral-600 transition-[background-color,color] duration-200 ease-out hover:bg-white/38 hover:text-neutral-900 disabled:cursor-not-allowed disabled:opacity-35'"
                                    :disabled="primaryActionDisabled"
                                    @click="handlePrimaryAction"
                                >
                                    <ArrowRight
                                        v-if="primaryActionLabel === 'Następne pytanie'"
                                        :size="18"
                                        :stroke-width="1.8"
                                        aria-hidden="true"
                                    />
                                    <span v-else>{{ primaryActionDisplayLabel }}</span>
                                </button>
                            </div>

                            <p
                                v-if="answerForm.errors.selected_answer"
                                class="text-sm font-medium"
                            >
                                {{ answerForm.errors.selected_answer }}
                            </p>
                            <p
                                v-if="answerForm.errors.answer_kind"
                                class="text-sm font-medium"
                            >
                                {{ answerForm.errors.answer_kind }}
                            </p>
                            <p
                                v-if="syncError"
                                class="sr-only"
                            >
                                {{ syncError }}
                            </p>
                            <div
                                v-if="showInlineSyncError"
                                class="flex items-start gap-3 border border-[#e4c7c1] bg-[#fff8f6] px-4 py-3 text-left shadow-[0_10px_22px_rgba(127,29,29,0.04)]"
                            >
                                <span
                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f5ddd8] text-[0.95rem] font-semibold text-[#8f3f32]"
                                    aria-hidden="true"
                                >
                                    !
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#7a2f25]">
                                        Problem z zapisem odpowiedzi
                                    </p>
                                    <p class="mt-1 text-sm leading-6 text-[#7a2f25]/90">
                                        {{ syncError }}
                                    </p>
                                </div>
                            </div>

                            <Modal
                                :show="showSaveAnswerErrorModal"
                                max-width="lg"
                                :closeable="false"
                            >
                                <div class="bg-white">
                                    <div class="grid gap-0 sm:grid-cols-[14rem_minmax(0,1fr)]">
                                        <div class="border-b border-[#e5e7eb] bg-[#f7f7f7] px-6 py-6 sm:border-b-0 sm:border-r sm:px-5 sm:py-7">
                                            <span
                                                class="inline-flex h-11 w-11 items-center justify-center border border-[#e7d0cb] bg-white text-lg font-semibold text-[#9f3131]"
                                                aria-hidden="true"
                                            >
                                                !
                                            </span>
                                            <p class="mt-4 text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Stan systemu
                                            </p>
                                            <p class="mt-2 text-base font-semibold leading-7 text-[#161414]">
                                                Zapis odpowiedzi jest chwilowo wstrzymany.
                                            </p>
                                            <p class="mt-3 text-sm leading-6 text-[#4b5563]">
                                                To pytanie pozostaje otwarte, więc możesz bezpiecznie wrócić do niego po odświeżeniu.
                                            </p>
                                        </div>

                                        <div class="px-6 py-6 sm:px-7 sm:py-7">
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Problem z zapisem odpowiedzi
                                            </p>
                                            <h3 class="mt-2 text-[1.45rem] font-semibold leading-9 text-[#161414]">
                                                Trwają prace na serwerze
                                            </h3>
                                            <p class="mt-3 text-sm leading-7 text-[#1f2937]">
                                                {{ syncError }}
                                            </p>

                                            <div class="mt-6 border-t border-[#e5e7eb] pt-4">
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    Co zrobić teraz
                                                </p>
                                                <div class="mt-3 space-y-2 text-sm leading-6 text-[#374151]">
                                                    <p class="flex items-start gap-3">
                                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#161414]" aria-hidden="true" />
                                                        <span>Zostań na tym pytaniu, żeby nie zgubić kontekstu odpowiedzi.</span>
                                                    </p>
                                                    <p class="flex items-start gap-3">
                                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#161414]" aria-hidden="true" />
                                                        <span>Odśwież stronę, gdy serwer wróci do stabilnego zapisu.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex min-w-[11.5rem] items-center justify-center border border-[#d1d5db] bg-[#161414] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0f172a]"
                                                    @click="reloadCurrentPage"
                                                >
                                                    Odśwież stronę
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Modal>

                            <Modal
                                :show="isExplanationEditorOpen"
                                max-width="xl"
                                @close="closeExplanationEditor"
                            >
                                <div class="bg-white">
                                    <div class="grid gap-0 sm:grid-cols-[13rem_minmax(0,1fr)]">
                                        <div class="border-b border-[#e5e7eb] bg-[#f7f7f7] px-6 py-6 sm:border-b-0 sm:border-r sm:px-5 sm:py-7">
                                            <span
                                                class="inline-flex h-11 w-11 items-center justify-center border border-[#d1d5db] bg-white text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[#161414]"
                                                aria-hidden="true"
                                            >
                                                Admin
                                            </span>
                                            <p class="mt-4 text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Szybka korekta
                                            </p>
                                            <p class="mt-2 text-base font-semibold leading-7 text-[#161414]">
                                                Edycja wyjaśnienia bez opuszczania trybu nauki.
                                            </p>
                                            <p class="mt-3 text-sm leading-6 text-[#4b5563]">
                                                Zapis nadpisuje pole wyjaśnienia w bazie i od razu odświeża bieżący widok pytania.
                                            </p>
                                        </div>

                                        <div class="px-6 py-6 sm:px-7 sm:py-7">
                                            <div class="flex flex-col gap-2">
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    {{
                                                        explanationEditorQuestionNumber !== null
                                                            ? `Pytanie ${explanationEditorQuestionNumber}`
                                                            : 'Pytanie'
                                                    }}
                                                </p>
                                                <h3 class="text-[1.32rem] font-semibold leading-8 text-[#161414]">
                                                    Wyjaśnienie pytania
                                                </h3>
                                                <p class="text-sm leading-7 text-[#4b5563]">
                                                    {{ explanationEditorQuestionPrompt ?? 'Edytujesz wyjaśnienie dla aktualnie wybranego pytania.' }}
                                                </p>
                                            </div>

                                            <div
                                                v-if="explanationEditorQuestionExternalId"
                                                class="mt-5 border border-[#e5e7eb] bg-[#fafafa] px-4 py-4"
                                            >
                                                <div class="flex flex-col gap-2">
                                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                        Zakres edycji
                                                    </p>
                                                    <div class="mt-1 grid gap-2">
                                                        <label class="flex items-start gap-3 text-sm leading-6 text-[#161414]">
                                                            <input
                                                                v-model="explanationEditorApplyScope"
                                                                type="radio"
                                                                class="mt-1 h-4 w-4 border-[#9ca3af] text-[#161414] focus:ring-[#161414]"
                                                                value="single"
                                                            >
                                                            <span>Tylko to pytanie</span>
                                                        </label>
                                                        <label class="flex items-start gap-3 text-sm leading-6 text-[#161414]">
                                                            <input
                                                                v-model="explanationEditorApplyScope"
                                                                type="radio"
                                                                class="mt-1 h-4 w-4 border-[#9ca3af] text-[#161414] focus:ring-[#161414]"
                                                                value="shared_external_id"
                                                            >
                                                            <span>
                                                                Wszystkie pytania z tym samym numerem źródłowym
                                                                <span class="block text-xs text-[#6b7280]">
                                                                    External ID: {{ explanationEditorQuestionExternalId }}
                                                                </span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                    <p
                                                        v-if="explanationEditorHasSharedConflict"
                                                        class="text-xs leading-5 text-[#92400e]"
                                                    >
                                                        Ta grupa ma już różne wersje wyjaśnienia w innych kategoriach. Dla bezpieczeństwa domyślnie ustawiliśmy edycję tylko tego pytania.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-5">
                                                <label
                                                    for="admin-inline-explanation"
                                                    class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]"
                                                >
                                                    Treść wyjaśnienia
                                                </label>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <button
                                                        v-for="action in inlineEditorFormattingActions"
                                                        :key="`explanation-${action.marker}`"
                                                        type="button"
                                                        class="inline-flex items-center justify-center border px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] transition"
                                                        :class="action.class"
                                                        :disabled="explanationEditorSaving"
                                                        @mousedown.prevent
                                                        @click="applyExplanationEditorFormatting(action.marker)"
                                                    >
                                                        {{ action.label }}
                                                    </button>
                                                </div>
                                                <textarea
                                                    id="admin-inline-explanation"
                                                    ref="explanationEditorTextareaRef"
                                                    v-model="explanationEditorDraft"
                                                    rows="8"
                                                    class="mt-2 block w-full border border-[#d1d5db] px-4 py-3 text-[0.98rem] leading-7 text-[#161414] outline-none transition focus:border-[#9ca3af]"
                                                    placeholder="Wpisz nowe wyjaśnienie pytania..."
                                                    @focus="syncExplanationEditorSelection"
                                                    @mouseup="syncExplanationEditorSelection"
                                                    @keyup="syncExplanationEditorSelection"
                                                    @select="syncExplanationEditorSelection"
                                                />
                                                <p class="mt-2 text-xs leading-5 text-[#6b7280]">
                                                    Zaznacz fragment i kliknij przycisk formatowania. Możesz łączyć pogrubienie z zielonym albo czerwonym akcentem na tym samym zaznaczeniu.
                                                </p>
                                                <p
                                                    v-if="explanationEditorError"
                                                    class="mt-3 text-sm font-medium text-[#b91c1c]"
                                                >
                                                    {{ explanationEditorError }}
                                                </p>
                                            </div>

                                            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[#e5e7eb] pt-5 sm:flex-row sm:items-center sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center border border-[#d1d5db] bg-white px-4 py-2.5 text-sm font-semibold text-[#161414] transition hover:border-[#9ca3af]"
                                                    :disabled="explanationEditorSaving"
                                                    @click="closeExplanationEditor"
                                                >
                                                    Anuluj
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-w-[11.5rem] items-center justify-center border border-[#d1d5db] bg-[#161414] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0f172a] disabled:cursor-not-allowed disabled:opacity-70"
                                                    :disabled="explanationEditorSaving"
                                                    @click="saveExplanationEditor"
                                                >
                                                    {{ explanationEditorSaving ? 'Zapisywanie...' : 'Zapisz wyjaśnienie' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Modal>
                            <Modal
                                :show="isQuestionEditorOpen"
                                max-width="xl"
                                @close="closeQuestionEditor"
                            >
                                <div class="bg-white">
                                    <div class="grid gap-0 sm:grid-cols-[13rem_minmax(0,1fr)]">
                                        <div class="border-b border-[#e5e7eb] bg-[#f7f7f7] px-6 py-6 sm:border-b-0 sm:border-r sm:px-5 sm:py-7">
                                            <span
                                                class="inline-flex h-11 w-11 items-center justify-center border border-[#d1d5db] bg-white text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[#161414]"
                                                aria-hidden="true"
                                            >
                                                Admin
                                            </span>
                                            <p class="mt-4 text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Szybka korekta
                                            </p>
                                            <p class="mt-2 text-base font-semibold leading-7 text-[#161414]">
                                                Edycja treści pytania bez opuszczania trybu nauki.
                                            </p>
                                            <p class="mt-3 text-sm leading-6 text-[#4b5563]">
                                                Zapis nadpisuje treść pytania w bazie i od razu odświeża bieżący widok.
                                            </p>
                                        </div>

                                        <div class="px-6 py-6 sm:px-7 sm:py-7">
                                            <div class="flex flex-col gap-2">
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    {{
                                                        questionEditorQuestionNumber !== null
                                                            ? `Pytanie ${questionEditorQuestionNumber}`
                                                            : 'Pytanie'
                                                    }}
                                                </p>
                                                <h3 class="text-[1.32rem] font-semibold leading-8 text-[#161414]">
                                                    Treść pytania
                                                </h3>
                                                <p class="text-sm leading-7 text-[#4b5563]">
                                                    {{ questionEditorQuestionPrompt ?? 'Edytujesz treść aktualnie wybranego pytania.' }}
                                                </p>
                                            </div>

                                            <div
                                                v-if="questionEditorQuestionExternalId"
                                                class="mt-5 border border-[#e5e7eb] bg-[#f7f7f7] px-4 py-4"
                                            >
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    Zakres edycji
                                                </p>
                                                <div class="mt-3 grid gap-2">
                                                    <label class="flex items-start gap-3 text-sm leading-6 text-[#161414]">
                                                        <input
                                                            v-model="questionEditorApplyScope"
                                                            type="radio"
                                                            class="mt-1 h-4 w-4 border-[#9ca3af] text-[#161414] focus:ring-[#161414]"
                                                            value="single"
                                                        >
                                                        <span>Tylko to pytanie</span>
                                                    </label>
                                                    <label class="flex items-start gap-3 text-sm leading-6 text-[#161414]">
                                                        <input
                                                            v-model="questionEditorApplyScope"
                                                            type="radio"
                                                            class="mt-1 h-4 w-4 border-[#9ca3af] text-[#161414] focus:ring-[#161414]"
                                                            value="shared_external_id"
                                                        >
                                                        <span>
                                                            Wszystkie pytania z tym samym numerem źródłowym
                                                            <span class="block text-xs text-[#6b7280]">
                                                                External ID: {{ questionEditorQuestionExternalId }}
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mt-5">
                                                <label
                                                    for="admin-inline-question-prompt"
                                                    class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]"
                                                >
                                                    Treść pytania
                                                </label>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <button
                                                        v-for="action in inlineEditorFormattingActions"
                                                        :key="`question-${action.marker}`"
                                                        type="button"
                                                        class="inline-flex items-center justify-center border px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] transition"
                                                        :class="action.class"
                                                        :disabled="questionEditorSaving"
                                                        @mousedown.prevent
                                                        @click="applyQuestionEditorFormatting(action.marker)"
                                                    >
                                                        {{ action.label }}
                                                    </button>
                                                </div>
                                                <textarea
                                                    id="admin-inline-question-prompt"
                                                    ref="questionEditorTextareaRef"
                                                    v-model="questionEditorDraft"
                                                    rows="8"
                                                    class="mt-2 block w-full border border-[#d1d5db] px-4 py-3 text-[0.98rem] leading-7 text-[#161414] outline-none transition focus:border-[#9ca3af]"
                                                    placeholder="Wpisz nową treść pytania..."
                                                    @focus="syncQuestionEditorSelection"
                                                    @mouseup="syncQuestionEditorSelection"
                                                    @keyup="syncQuestionEditorSelection"
                                                    @select="syncQuestionEditorSelection"
                                                />
                                                <p class="mt-2 text-xs leading-5 text-[#6b7280]">
                                                    Zaznacz fragment i kliknij przycisk formatowania. Możesz łączyć pogrubienie z zielonym albo czerwonym akcentem na tym samym zaznaczeniu.
                                                </p>
                                                <p
                                                    v-if="questionEditorError"
                                                    class="mt-3 text-sm font-medium text-[#b91c1c]"
                                                >
                                                    {{ questionEditorError }}
                                                </p>
                                            </div>

                                            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[#e5e7eb] pt-5 sm:flex-row sm:items-center sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center border border-[#d1d5db] bg-white px-4 py-2.5 text-sm font-semibold text-[#161414] transition hover:border-[#9ca3af]"
                                                    :disabled="questionEditorSaving"
                                                    @click="closeQuestionEditor"
                                                >
                                                    Anuluj
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-w-[11.5rem] items-center justify-center border border-[#d1d5db] bg-[#161414] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0f172a] disabled:cursor-not-allowed disabled:opacity-70"
                                                    :disabled="questionEditorSaving"
                                                    @click="saveQuestionEditor"
                                                >
                                                    {{ questionEditorSaving ? 'Zapisywanie...' : 'Zapisz pytanie' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Modal>
                        </div>
                        </div>
                        <div
                            v-if="shouldPinMobileAnswerPanel"
                            aria-hidden="true"
                            :style="mobileAnswerDockSpacerStyle"
                        />
                        </div>
                        </div>
                        </div>
                    </div>

                    <aside
                        v-if="isExamLikeShell && !isPhoneViewport"
                        class="mt-3 space-y-3 min-[1024px]:mt-0"
                    >
                        <div class="rounded-none border border-[#e5e7eb] bg-white px-3.5 py-3.5 shadow-[0_14px_30px_rgba(15,23,42,0.04)] xl:px-4 xl:py-3.5">
                            <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]">
                                Panel sesji
                            </p>
                            <div class="mt-3 space-y-2">
                                <button
                                    v-if="canUseTopicPicker"
                                    type="button"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-none border border-[#d1d5db] bg-[#f3f4f6] px-3 py-2 text-sm font-semibold text-[#111827] transition hover:border-[#c7cfd8] hover:bg-[#e5e7eb]"
                                    @click="openTopicPicker()"
                                >
                                    <LibraryBig :size="16" :stroke-width="1.8" aria-hidden="true" />
                                    Działy
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-none border border-[#d1d5db] bg-[#f3f4f6] px-3 py-2 text-sm font-semibold text-[#111827] transition hover:border-[#c7cfd8] hover:bg-[#e5e7eb]"
                                    @click="openSettingsPopover({ markSeen: false })"
                                >
                                    <Settings :size="16" :stroke-width="1.8" aria-hidden="true" />
                                    Ustawienia
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!isPhoneViewport"
                            class="overflow-hidden rounded-none border border-[#e5e7eb] bg-white shadow-[0_14px_30px_rgba(15,23,42,0.04)]"
                        >
                            <div class="text-sm text-[#111827]">
                                <div class="relative overflow-hidden border-b border-[#d7dce2] bg-[#fcfcfb] px-3.5 py-3.5 text-left">
                                    <span class="absolute inset-y-0 left-0 w-[3px] bg-[#1f3b5b]" aria-hidden="true"></span>
                                    <div
                                        class="pointer-events-none absolute right-3 top-3 flex h-14 w-14 items-center justify-center overflow-hidden"
                                        aria-hidden="true"
                                    >
                                        <img
                                            :src="questionSourceEmblem"
                                            alt=""
                                            class="h-full w-full object-contain"
                                        />
                                    </div>
                                    <div class="pl-1 pr-16">
                                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.16em] text-[#697586]">
                                            Bieżąca sesja
                                        </p>
                                        <p class="mt-1 text-[1rem] font-semibold leading-none tracking-[0.02em] text-[#111827]">
                                            {{ currentQuestionSourceBadgeLabel }}
                                        </p>
                                        <p class="mt-1 text-[0.67rem] font-semibold uppercase tracking-[0.12em] text-[#4b5563]">
                                            {{ currentQuestionSourceDescription }}
                                        </p>
                                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#e5e7eb] pt-2.5">
                                            <span class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">{{ currentQuestionSourceNumberLabel }}</span>
                                            <span class="inline-flex min-w-[5rem] items-center justify-center whitespace-nowrap rounded-none border border-[#d1d5db] bg-white px-2.5 py-1 text-[0.92rem] font-semibold tracking-[0.02em] text-[#111827]">
                                                {{ currentQuestionSourceNumber }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3 px-3.5 py-3.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[#6b7280]">Postęp</span>
                                        <span class="inline-flex min-w-[4.4rem] items-center justify-center rounded-none border border-[#d1d5db] bg-white px-2.5 py-1 font-semibold text-[#111827]">{{ displaySessionProgressCount }} / {{ displayProgress.total }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[#6b7280]">Prawidłowe</span>
                                        <span class="inline-flex min-w-[4.4rem] items-center justify-center rounded-none border border-[#d1d5db] bg-white px-2.5 py-1 font-semibold text-[#111827]">{{ displayCorrectAnswersCount }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[#6b7280]">Pozostało</span>
                                        <span class="inline-flex min-w-[4.4rem] items-center justify-center rounded-none border border-[#d1d5db] bg-white px-2.5 py-1 font-semibold text-[#111827]">{{ displayRemainingCount }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[#6b7280]">Skuteczność</span>
                                        <span class="inline-flex min-w-[4.4rem] items-center justify-center rounded-none border border-[#d1d5db] bg-white px-2.5 py-1 font-semibold text-[#111827]">{{ displayScorePercent !== null ? `${displayScorePercent.toFixed(0)}%` : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-none border border-[#e5e7eb] bg-white px-3.5 py-3.5 shadow-[0_14px_30px_rgba(15,23,42,0.04)]">
                            <p class="text-[0.63rem] font-medium uppercase tracking-[0.08em] text-[#6b7280]">
                                Ustawienia
                            </p>
                            <div class="mt-3 space-y-3 text-sm text-[#111827]">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Po odpowiedzi</span>
                                    <span class="text-right font-semibold">{{ classicFeedbackModeLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Przejście dalej</span>
                                    <span class="text-right font-semibold">{{ classicAdvanceModeLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Prędkość odtwarzania</span>
                                    <span class="text-right font-semibold">{{ classicMediaModeLabel }}</span>
                                </div>
                                <div
                                    v-if="canUseQuestionAudio"
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-[#6b7280]">Audio pytania</span>
                                    <span class="text-right font-semibold">{{ classicQuestionAudioModeLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Wskazówki</span>
                                    <span class="text-right font-semibold">{{ classicInlineFormattingLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[#6b7280]">Strzałki pomocnicze</span>
                                    <span class="text-right font-semibold">{{ classicAnnotationsLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section
                v-else
                data-testid="session-complete"
                :class="completionShellClass"
            >
                <div :class="completionLayoutClass">
                    <aside
                        v-if="showCompletionProgressPanel"
                        class="min-w-0 overflow-hidden border border-[#e5e7eb] bg-white shadow-[0_22px_52px_rgba(15,23,42,0.04),0_10px_22px_rgba(15,23,42,0.02)] xl:sticky xl:top-5 xl:max-h-[calc(100svh-2.5rem)]"
                    >
                        <div class="border-b border-[#eef1f4] px-5 py-5">
                            <p class="text-[1.02rem] font-semibold tracking-tight text-[#161414]">
                                Twój postęp w nauce
                            </p>
                            <dl class="mt-4 grid grid-cols-2 gap-2.5">
                                <div class="border border-[#e5e7eb] bg-[#f8fafc] px-3 py-3">
                                    <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">
                                        Opanowane
                                    </dt>
                                    <dd class="mt-1 text-[1.15rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                        {{ completedRoadmapTopicsCount }} / {{ completionProgressTotalTopics }}
                                    </dd>
                                </div>
                                <div class="border border-[#e5e7eb] bg-[#f8fafc] px-3 py-3">
                                    <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">
                                        Do końca
                                    </dt>
                                    <dd class="mt-1 text-[1.15rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                        {{ completionProgressRemainingTopics ?? '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div
                            ref="completionProgressPanelScroller"
                            class="side-panel-scroll max-h-[32rem] overflow-y-auto px-3 py-4 xl:max-h-[calc(100svh-10rem)]"
                        >
                            <ol class="relative space-y-1">
                                <li
                                    v-for="item in completionProgressPanelItems"
                                    :key="`completion-progress-${item.id}`"
                                    :data-completion-progress-topic-id="item.id"
                                    class="relative grid grid-cols-[2.45rem_minmax(0,1fr)] gap-2.5"
                                >
                                    <div class="relative flex justify-center">
                                        <span
                                            v-if="item.position < completionProgressPanelItems.length"
                                            class="absolute bottom-[-0.35rem] top-8 w-px"
                                            :class="item.isMastered ? 'bg-[#22b35f]' : 'bg-[#e5e7eb]'"
                                            aria-hidden="true"
                                        />
                                        <span
                                            class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border text-[0.76rem] font-semibold tabular-nums"
                                            :class="completionProgressIndicatorClass(item)"
                                        >
                                            <span v-if="item.isMastered" aria-hidden="true">✓</span>
                                            <span v-else>{{ item.position }}</span>
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        class="group min-w-0 w-full border px-3 py-3 text-left transition enabled:hover:border-[#d7dee8] enabled:hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#94a3b8]/45 disabled:cursor-not-allowed disabled:opacity-75"
                                        :class="completionProgressItemClass(item)"
                                        :disabled="!canStartCompletionProgressTopic(item)"
                                        :aria-label="completionProgressItemTitle(item)"
                                        :title="completionProgressItemTitle(item)"
                                        @click="startCompletionProgressTopicSession(item)"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-[0.86rem] font-semibold leading-5 text-[#1f2937]">
                                                    {{ item.label }}
                                                </p>
                                                <p class="mt-0.5 text-[0.74rem] leading-5 text-[#64748b]">
                                                    {{ item.totalQuestions }} pytań
                                                </p>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-2">
                                                <div class="text-right">
                                                    <p
                                                        v-if="item.isMastered || item.isActive || item.displayPercent > 0"
                                                        class="text-[0.78rem] font-semibold tabular-nums"
                                                        :class="item.isMastered ? 'text-[#16a34a]' : item.isActive ? 'text-[#334155]' : 'text-[#64748b]'"
                                                    >
                                                        {{ item.displayPercent }}%
                                                    </p>
                                                    <p
                                                        v-if="item.displayTimeLabel"
                                                        class="mt-0.5 text-[0.74rem] font-medium tabular-nums"
                                                        :class="item.isActive ? 'text-[#334155]' : 'text-[#16a34a]'"
                                                    >
                                                        {{ item.displayTimeLabel }}
                                                    </p>
                                                </div>

                                                <svg
                                                    class="h-4 w-4 text-[#94a3b8] transition group-hover:translate-x-0.5 group-hover:text-[#475569]"
                                                    viewBox="0 0 20 20"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M7.5 4.5 12.5 10l-5 5.5"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                            </div>
                                        </div>

                                        <div
                                            v-if="item.isActive"
                                            class="mt-3"
                                        >
                                            <div class="flex items-center justify-between gap-3 text-[0.72rem] font-semibold text-[#334155]">
                                                <span>{{ displayAnsweredCount }} / {{ displayProgress.total }} przerobione</span>
                                                <span>{{ item.statusLabel }}</span>
                                            </div>
                                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#e2e8f0]">
                                                <div
                                                    class="h-full rounded-full bg-[#334155] transition-[width]"
                                                    :style="{ width: `${Math.min(Math.max(displayAnsweredCount / Math.max(displayProgress.total, 1) * 100, 0), 100)}%` }"
                                                />
                                            </div>
                                        </div>
                                    </button>
                                </li>
                            </ol>
                        </div>
                    </aside>

                    <div :class="completionMainColumnClass">
                        <div
                            v-if="useNativeMobileSessionResult"
                            class="-mx-4 -mt-4 overflow-hidden bg-white text-[#101828]"
                            data-testid="mobile-session-result"
                        >
                            <MobileAppBar
                                title="Wynik sesji"
                                :subtitle="sessionContextLabel ?? (sessionState.license_category_code
                                    ? `Kategoria ${sessionState.license_category_code}`
                                    : 'Tryb nauki')"
                                :back-href="completionReturnHref"
                                back-label="Wróć do nauki"
                            >
                                <template #action>
                                    <span class="inline-flex h-9 items-center rounded-[0.5rem] bg-[#f2f4f7] px-3 text-[0.72rem] font-semibold tabular-nums text-[#475467]">
                                        {{ displayAnsweredCount }}/{{ displayProgress.total }}
                                    </span>
                                </template>
                            </MobileAppBar>

                            <main>
                                <section
                                    class="border-b px-5 pb-6 pt-7 text-center"
                                    :class="hasIncorrectAnswers
                                        ? 'border-[#fee4e2] bg-[#fff7f6]'
                                        : 'border-[#d1fadf] bg-[#f3fbf5]'"
                                    aria-labelledby="mobile-session-result-title"
                                >
                                    <span
                                        class="mx-auto grid h-14 w-14 place-items-center rounded-full"
                                        :class="hasIncorrectAnswers
                                            ? 'bg-[#fee4e2] text-[#d92d20]'
                                            : 'bg-[#d1fadf] text-[#067647]'"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            v-if="hasIncorrectAnswers"
                                            class="h-7 w-7"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                        >
                                            <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-7 w-7"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                        >
                                            <path d="m5.5 12.5 4.1 4.1L18.8 7.4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>

                                    <p
                                        class="mt-4 text-[0.7rem] font-semibold uppercase text-[#667085]"
                                    >
                                        {{ hasIncorrectAnswers ? 'Warto poprawić' : 'Sesja ukończona' }}
                                    </p>
                                    <h1
                                        id="mobile-session-result-title"
                                        class="mt-1 text-[1.45rem] font-semibold leading-7 text-[#101828]"
                                    >
                                        <template v-if="hasIncorrectAnswers">
                                            {{ displayIncorrectAnswersCount }}
                                            {{ polishPlural(displayIncorrectAnswersCount, 'pytanie', 'pytania', 'pytań') }} do poprawki
                                        </template>
                                        <template v-else>
                                            Bez błędnych odpowiedzi
                                        </template>
                                    </h1>
                                    <p class="mx-auto mt-2 max-w-[21rem] text-[0.88rem] leading-5 text-[#667085]">
                                        {{ completionDecisionTitle }}
                                    </p>

                                    <div class="mt-5">
                                        <p class="text-[2.85rem] font-semibold leading-none tabular-nums text-[#101828]">
                                            {{ completionAccuracyPercent?.toFixed(0) ?? 0 }}%
                                        </p>
                                        <p class="mt-1 text-[0.72rem] font-medium text-[#667085]">
                                            Skuteczność
                                            <template v-if="activeSessionTopic"> · {{ activeSessionTopic.label }}</template>
                                        </p>
                                        <div class="mx-auto mt-4 h-2 max-w-[21rem] overflow-hidden rounded-full bg-black/10">
                                            <div
                                                class="h-full rounded-full transition-[width]"
                                                :class="hasIncorrectAnswers ? 'bg-[#f04438]' : 'bg-[#12b76a]'"
                                                :style="{ width: mobileCompletionProgressWidth }"
                                            />
                                        </div>
                                    </div>

                                    <div class="mx-auto mt-6 grid max-w-[21rem] gap-2.5">
                                        <button
                                            v-if="hasIncorrectAnswers"
                                            type="button"
                                            class="inline-flex min-h-12 items-center justify-center rounded-[0.5rem] bg-[#0b5cff] px-5 py-3 text-[0.9rem] font-semibold text-white transition-colors hover:bg-[#0646c8] disabled:cursor-not-allowed disabled:bg-[#d0d5dd]"
                                            :disabled="!canUseCompletionActions || reviewIncorrectQuestionCount <= 0"
                                            @click="startFollowUpSession('incorrect')"
                                        >
                                            Popraw błędne pytania
                                        </button>
                                        <button
                                            v-else-if="nextSessionTopic"
                                            type="button"
                                            class="inline-flex min-h-12 items-center justify-center rounded-[0.5rem] bg-[#0b5cff] px-5 py-3 text-[0.9rem] font-semibold text-white transition-colors hover:bg-[#0646c8] disabled:cursor-not-allowed disabled:bg-[#d0d5dd]"
                                            :disabled="!canUseCompletionActions || nextTopicQuestionCount <= 0"
                                            @click="startNextTopicSession()"
                                        >
                                            Przejdź do następnego działu
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            class="inline-flex min-h-12 items-center justify-center rounded-[0.5rem] bg-[#0b5cff] px-5 py-3 text-[0.9rem] font-semibold text-white transition-colors hover:bg-[#0646c8] disabled:cursor-not-allowed disabled:bg-[#d0d5dd]"
                                            :disabled="!canUseCompletionActions || restartTopicQuestionCount <= 0"
                                            @click="startFollowUpSession('all')"
                                        >
                                            Powtórz ten dział
                                        </button>
                                        <Link
                                            :href="completionReturnHref"
                                            class="inline-flex min-h-12 items-center justify-center rounded-[0.5rem] border border-[#d0d5dd] bg-white px-5 py-3 text-[0.9rem] font-semibold text-[#344054] transition-colors hover:bg-[#f9fafb]"
                                        >
                                            {{ completionReturnLabel }}
                                        </Link>
                                    </div>
                                </section>

                                <section class="border-b border-[#eaecf0] px-5 py-5" aria-labelledby="mobile-answer-summary-title">
                                    <h2 id="mobile-answer-summary-title" class="text-[1rem] font-semibold text-[#101828]">
                                        Odpowiedzi
                                    </h2>
                                    <div class="mt-4 grid grid-cols-3 divide-x divide-[#eaecf0] text-center">
                                        <div class="px-2">
                                            <p class="text-[1.35rem] font-semibold tabular-nums text-[#067647]">{{ displayCorrectAnswersCount }}</p>
                                            <p class="mt-1 text-[0.68rem] text-[#667085]">Dobrze</p>
                                        </div>
                                        <div class="px-2">
                                            <p class="text-[1.35rem] font-semibold tabular-nums text-[#d92d20]">{{ displayIncorrectAnswersCount }}</p>
                                            <p class="mt-1 text-[0.68rem] text-[#667085]">Błędne</p>
                                        </div>
                                        <div class="px-2">
                                            <p class="text-[1.35rem] font-semibold tabular-nums text-[#475467]">{{ displayRemainingCount }}</p>
                                            <p class="mt-1 text-[0.68rem] text-[#667085]">Pozostałe</p>
                                        </div>
                                    </div>

                                    <dl
                                        v-if="completionTimerLabel || completionTiming.average_correct_response_time_ms !== null"
                                        class="mt-5 divide-y divide-[#eaecf0] border-y border-[#eaecf0]"
                                    >
                                        <div v-if="completionTimerLabel" class="flex min-h-11 items-center justify-between gap-4 py-2 text-[0.82rem]">
                                            <dt class="text-[#667085]">Czas sesji</dt>
                                            <dd class="font-semibold tabular-nums text-[#344054]">{{ completionTimerLabel }}</dd>
                                        </div>
                                        <div
                                            v-if="completionTiming.average_correct_response_time_ms !== null"
                                            class="flex min-h-11 items-center justify-between gap-4 py-2 text-[0.82rem]"
                                        >
                                            <dt class="text-[#667085]">Średni czas odpowiedzi</dt>
                                            <dd class="font-semibold tabular-nums text-[#344054]">
                                                {{ formatResponseTime(completionTiming.average_correct_response_time_ms) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <section
                                    v-if="hasIncorrectAnswers || nextSessionTopic"
                                    class="border-b border-[#eaecf0] px-5 py-5"
                                    aria-labelledby="mobile-next-step-title"
                                >
                                    <h2 id="mobile-next-step-title" class="text-[1rem] font-semibold text-[#101828]">
                                        Dalsza nauka
                                    </h2>
                                    <div class="mt-3 divide-y divide-[#eaecf0] border-y border-[#eaecf0]">
                                        <button
                                            type="button"
                                            class="flex min-h-14 w-full items-center justify-between gap-3 py-3 text-left text-[0.86rem] font-semibold text-[#344054] disabled:text-[#98a2b3]"
                                            :disabled="!canUseCompletionActions || restartTopicQuestionCount <= 0"
                                            @click="startFollowUpSession('all')"
                                        >
                                            <span>Powtórz ten dział</span>
                                            <span aria-hidden="true" class="text-lg font-normal text-[#98a2b3]">›</span>
                                        </button>
                                        <button
                                            v-if="hasIncorrectAnswers && nextSessionTopic"
                                            type="button"
                                            class="flex min-h-14 w-full items-center justify-between gap-3 py-3 text-left text-[0.86rem] font-semibold text-[#344054] disabled:text-[#98a2b3]"
                                            :disabled="!canUseCompletionActions || nextTopicQuestionCount <= 0"
                                            @click="startNextTopicSession()"
                                        >
                                            <span class="min-w-0">
                                                <span class="block">Następny dział</span>
                                                <span class="mt-0.5 block truncate text-[0.72rem] font-normal text-[#667085]">{{ nextSessionTopic.label }}</span>
                                            </span>
                                            <span aria-hidden="true" class="shrink-0 text-lg font-normal text-[#98a2b3]">›</span>
                                        </button>
                                    </div>
                                </section>
                            </main>

                            <MobileBottomNavigation />
                        </div>

                        <template v-else>
                        <div
                            class="flex flex-col gap-3 pt-5 lg:flex-row lg:items-center lg:justify-between"
                            :class="useFlatPhoneCompletionSummary ? 'border-b border-[#e5e7eb] pb-4' : ''"
                        >
                            <div>
                                <p
                                    v-if="useFlatPhoneCompletionSummary"
                                    class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#0057a3]"
                                >
                                    Sesja zakończona
                                </p>
                                <h1
                                    :class="useFlatPhoneCompletionSummary
                                        ? 'mt-1 text-[1.5rem] font-semibold leading-tight text-[#161414]'
                                        : 'text-[0.95rem] font-bold uppercase tracking-[0.12em] text-[#161414]'"
                                >
                                    Wynik sesji
                                </h1>
                                <p
                                    v-if="useFlatPhoneCompletionSummary"
                                    class="mt-1 text-[0.88rem] leading-5 text-[#5b6675]"
                                >
                                    {{ completionDecisionTitle }}
                                </p>
                            </div>

                            <Link
                                v-if="!isPublicDemoMode"
                                :href="completionReturnHref"
                                class="inline-flex min-h-9 items-center justify-center gap-2 self-start border border-[#dbe3ec] bg-white px-3 py-2 text-[0.78rem] font-semibold leading-tight text-[#42526a] transition hover:border-[#b8c5d6] hover:bg-[#f8fafc] hover:text-[#111827] lg:self-auto"
                            >
                                <span aria-hidden="true" class="text-[0.95rem]">←</span>
                                {{ completionReturnLabel }}
                            </Link>
                        </div>

                        <div :class="completionSummaryCardClass">
                        <div :class="completionSummaryCardBodyClass">
                            <div>
                                <div class="space-y-5">
                                    <div :class="completionMetricsGridClass">
                                        <div :class="completionMetricCardClass">
                                            <div class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Przerobione
                                            </div>
                                            <div class="mt-1 text-[1.18rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                {{ displayAnsweredCount }} / {{ displayProgress.total }}
                                            </div>
                                        </div>

                                        <div
                                            v-if="completionAccuracyPercent !== null"
                                            :class="completionMetricCardClass"
                                        >
                                            <div class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Skuteczność
                                            </div>
                                            <div class="mt-1 text-[1.18rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                {{ completionAccuracyPercent.toFixed(0) }}%
                                            </div>
                                        </div>

                                        <div :class="completionMetricCardClass">
                                            <div class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Dobrze
                                            </div>
                                            <div class="mt-1 text-[1.18rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                {{ displayCorrectAnswersCount }}
                                            </div>
                                        </div>

                                        <div :class="completionMetricCardClass">
                                            <div class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                {{ completionIssueMetricLabel }}
                                            </div>
                                            <div class="mt-1 text-[1.18rem] font-semibold tracking-tight text-[#dc2626] tabular-nums">
                                                {{ displayIncorrectAnswersCount }}
                                            </div>
                                        </div>

                                        <div
                                            v-if="completionTimerLabel"
                                            :class="completionMetricHighlightCardClass"
                                        >
                                            <div :class="completionMetricHighlightLabelClass">
                                                Czas sesji
                                            </div>
                                            <div :class="completionMetricHighlightValueClass">
                                                {{ completionTimerLabel }}
                                            </div>
                                        </div>

                                        <div
                                            v-if="completionTiming.average_correct_response_time_ms !== null"
                                            :class="completionMetricHighlightCardClass"
                                        >
                                            <div :class="completionMetricHighlightLabelClass">
                                                Średnio
                                            </div>
                                            <div :class="completionMetricHighlightValueClass">
                                                {{ formatResponseTime(completionTiming.average_correct_response_time_ms) }}
                                            </div>
                                        </div>

                                    </div>

                                    <div class="flex flex-col gap-3 border-t border-[#e5eaf0] pt-4 text-[0.92rem] leading-6 text-[#4b5563] lg:flex-row lg:items-center lg:justify-between">
                                        <p class="text-[1.04rem] font-bold tracking-tight text-[#161414]">
                                            <template v-if="hasIncorrectAnswers">
                                                <span class="text-[1.16rem] font-bold tabular-nums text-[#dc2626]">{{ displayIncorrectAnswersCount }}</span>
                                                {{ polishPlural(displayIncorrectAnswersCount, 'pytanie', 'pytania', 'pytań') }} do poprawki
                                            </template>
                                            <template v-else>
                                                Bez pytań do poprawki
                                            </template>
                                        </p>

                                        <p class="text-[#42526a] lg:text-right">
                                            <span v-if="activeSessionTopic" class="font-semibold text-[#334155]">
                                                {{ activeSessionTopic.label }}
                                            </span>
                                            <span v-if="activeSessionTopic"> · </span>
                                            <span>{{ displayAnsweredCount }} z {{ displayProgress.total }} {{ completionAnsweredScopeLabel }}</span>
                                        </p>
                                    </div>

                                    <div
                                        v-if="isReviewTrainerMode && currentReviewCompletion"
                                        data-testid="review-completion-summary"
                                        :class="reviewCompletionSummaryClass"
                                    >
                                        <div class="max-w-3xl text-left">
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#0057a3]">
                                                Trener pamięci
                                            </p>
                                            <h3 :class="reviewCompletionHeadingClass">
                                                Mapa po tej sesji
                                            </h3>
                                            <p :class="reviewCompletionCoachMessageClass">
                                                {{ currentReviewCompletion.coach_message }}
                                            </p>
                                            <p :class="reviewCompletionCountTextClass">
                                                Odpowiedziałeś na {{ currentReviewCompletion.answered_count }} z {{ currentReviewCompletion.total_questions_count }} pytań. System przeliczył ich stan pamięci i użyje tego przy kolejnych powtórkach.
                                            </p>
                                            <div :class="reviewCompletionNextStepClass">
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#4b6f91]">
                                                    {{ currentReviewCompletion.next_step.time_label }}
                                                </p>
                                                <p class="mt-1 text-[1rem] font-semibold tracking-tight text-[#16324d]">
                                                    {{ currentReviewCompletion.next_step.headline }}
                                                </p>
                                                <p class="mt-1 text-[0.88rem] leading-6 text-[#4b6f91]">
                                                    {{ currentReviewCompletion.next_step.message }}
                                                </p>
                                            </div>
                                            <button
                                                v-if="reviewTrainerQuestionDetailsCount > 0"
                                                type="button"
                                                :class="reviewCompletionToggleButtonClass"
                                                :aria-expanded="showReviewTrainerQuestionDetails"
                                                @click="() => void toggleReviewTrainerQuestionDetails()"
                                            >
                                                {{ reviewTrainerDetailsToggleLabel }}
                                            </button>
                                        </div>

                                        <div :class="reviewCompletionItemsGridClass">
                                            <div
                                                v-for="item in reviewCompletionItems"
                                                :key="item.label"
                                                :class="reviewCompletionItemCardClass"
                                            >
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    {{ item.label }}
                                                </p>
                                                <p class="mt-1 text-[1.1rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                    {{ item.value }}
                                                </p>
                                                <p :class="reviewCompletionItemDescriptionClass">
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="isPjmMode"
                                        data-testid="pjm-completion-summary"
                                        class="grid gap-5 border-t border-[#e5e7eb] pt-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(22rem,0.9fr)] lg:items-start"
                                    >
                                        <div class="max-w-3xl text-left">
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#0057a3]">
                                                Moduł PJM
                                            </p>
                                            <h3 class="mt-2 text-[1.35rem] font-semibold tracking-tight text-[#161414] sm:text-[1.55rem]">
                                                Ukończyłeś sesję PJM
                                            </h3>
                                            <p class="mt-3 text-[0.98rem] leading-7 text-[#4b5563]">
                                                {{ pjmCompletionMessage }}
                                            </p>
                                            <div class="mt-4 max-w-xl">
                                                <div class="flex items-center justify-between gap-3 text-[0.78rem] font-medium text-[#5b6675]">
                                                    <span>Postęp w module PJM</span>
                                                    <span>{{ pjmLearningProgressPercent.toFixed(0) }}%</span>
                                                </div>
                                                <div class="mt-2 h-3 overflow-hidden rounded-full bg-[#e5e7eb]">
                                                    <div
                                                        class="h-full rounded-full bg-[#0071ce] transition-[width] duration-300"
                                                        :style="{ width: `${Math.min(Math.max(pjmLearningProgressPercent, 0), 100)}%` }"
                                                    />
                                                </div>
                                                <p
                                                    v-if="hasPjmUnansweredQuestions"
                                                    class="mt-2 text-[0.86rem] leading-5 text-[#5b6675]"
                                                >
                                                    Następny dział: <span class="font-semibold text-[#161414]">{{ pjmCurrentTopicLabel }}</span>
                                                </p>
                                            </div>
                                            <p
                                                v-if="!currentPjmCompletion?.has_full_product_access"
                                                class="mt-3 text-[0.92rem] leading-6 text-[#5b6675]"
                                            >
                                                Pełny dostęp jest opcjonalny. Odblokuje klasyczną naukę, powtórki, statystyki i pytania, które nie mają jeszcze filmu PJM.
                                            </p>
                                        </div>

                                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                                            <div class="border border-[#d9e8f7] bg-[#f3f8fd] px-4 py-3">
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#4b6f91]">
                                                    Przerobione PJM
                                                </p>
                                                <p class="mt-1 text-[1.1rem] font-semibold tracking-tight text-[#16324d] tabular-nums">
                                                    {{ pjmAnsweredQuestionCount }}/{{ pjmProgressQuestionCount }}
                                                </p>
                                            </div>

                                            <div class="border border-[#e5e7eb] bg-[#fafafa] px-4 py-3">
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    Zostało PJM
                                                </p>
                                                <p class="mt-1 text-[1.1rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                    {{ pjmUnansweredQuestionCount }}
                                                </p>
                                            </div>

                                            <div class="border border-[#efe0e0] bg-[#fff7f7] px-4 py-3">
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#8a4b4b]">
                                                    Do poprawki
                                                </p>
                                                <p class="mt-1 text-[1.1rem] font-semibold tracking-tight text-[#6f3131] tabular-nums">
                                                    {{ pjmIncorrectQuestionCount }}
                                                </p>
                                            </div>

                                            <div class="border border-[#f0dfae] bg-[#fff8df] px-4 py-3">
                                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#7a6314]">
                                                    Pokrycie kategorii
                                                </p>
                                                <p class="mt-1 text-[1.1rem] font-semibold tracking-tight text-[#6b5208] tabular-nums">
                                                    {{ pjmCoveragePercent.toFixed(0) }}%
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="completionRewardMessage || syncError"
                                    class="mt-3 space-y-2 border-t border-[#e5e7eb] pt-2.5 text-center"
                                >
                                    <p
                                        v-if="completionRewardMessage"
                                        class="text-sm leading-6 text-[#4b5563]"
                                    >
                                        {{ completionRewardMessage }}
                                    </p>

                                    <div
                                        v-if="syncError"
                                        class="mx-auto flex max-w-3xl items-start gap-3 border border-[#e4c7c1] bg-[#fff8f6] px-4 py-3 text-left shadow-[0_10px_22px_rgba(127,29,29,0.04)]"
                                    >
                                        <span
                                            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f5ddd8] text-[0.95rem] font-semibold text-[#8f3f32]"
                                            aria-hidden="true"
                                        >
                                            !
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-[#7a2f25]">
                                                Problem z synchronizacją sesji
                                            </p>
                                            <p class="mt-1 text-sm leading-6 text-[#7a2f25]/90">
                                                {{ syncError }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <nav
                        :class="completionQuickNavClass"
                        aria-label="Szybka nawigacja po zakończeniu sesji"
                    >
                        <button
                            v-if="isPublicDemoMode && publicDemoPrimaryUsesRegisterDrawer"
                            type="button"
                            :class="completionQuickNavPrimaryButtonClass"
                            @click="openPublicDemoRegisterDrawer"
                        >
                            {{ publicDemoPrimaryLabel }}
                        </button>

                        <Link
                            v-else-if="isPublicDemoMode"
                            :href="publicDemoPrimaryHref"
                            :class="completionQuickNavPrimaryButtonClass"
                        >
                            {{ publicDemoPrimaryLabel }}
                        </Link>

                        <Link
                            v-if="isPublicDemoMode"
                            href="/"
                            :class="completionQuickNavButtonClass"
                        >
                            Wróć na stronę główną
                        </Link>

                        <button
                            v-if="!isPublicDemoMode && !isPjmMode && !isReviewTrainerMode && hasIncorrectAnswers"
                            type="button"
                            :class="completionQuickNavPrimaryButtonClass"
                            :disabled="!canUseCompletionActions || reviewIncorrectQuestionCount <= 0"
                            @click="startFollowUpSession('incorrect')"
                        >
                            <span aria-hidden="true" class="flex h-5 w-5 items-center justify-center rounded-full border border-white/40">•</span>
                            Popraw błędne pytania
                        </button>

                        <button
                            v-if="isPjmMode && pjmIncorrectQuestionCount > 0"
                            type="button"
                            :class="completionQuickNavPrimaryButtonClass"
                            :disabled="!canUseCompletionActions"
                            @click="startPjmFollowUpSession('incorrect')"
                        >
                            Popraw błędne PJM
                        </button>

                        <button
                            v-if="!isPublicDemoMode && !isPjmMode && !isReviewTrainerMode"
                            type="button"
                            :class="completionQuickNavButtonClass"
                            :disabled="!canUseCompletionActions || restartTopicQuestionCount <= 0"
                            @click="startFollowUpSession('all')"
                        >
                            <span aria-hidden="true" class="text-[1.05rem]">↻</span>
                            Powtórz ten dział od początku
                        </button>

                        <button
                            v-if="!isPublicDemoMode && !isPjmMode && !isReviewTrainerMode && nextSessionTopic"
                            type="button"
                            :class="hasIncorrectAnswers ? completionQuickNavButtonClass : completionQuickNavPrimaryButtonClass"
                            :disabled="!canUseCompletionActions || nextTopicQuestionCount <= 0"
                            :title="`Przejdź do następnego działu: ${nextSessionTopic.label}`"
                            @click="startNextTopicSession()"
                        >
                            <span aria-hidden="true" class="text-[1.05rem]">→</span>
                            Przejdź do następnego działu
                        </button>

                        <button
                            v-if="isPjmMode"
                            type="button"
                            :class="completionQuickNavButtonClass"
                            :disabled="!canUseCompletionActions"
                            :title="pjmPrimaryActionTitle"
                            @click="startPjmPrimarySession()"
                        >
                            {{ pjmPrimaryActionLabel }}
                        </button>

                        <a
                            v-if="isPjmMode && !currentPjmCompletion?.has_full_product_access"
                            :href="currentPjmCompletion?.pricing_url ?? '/cennik'"
                            :class="completionQuickNavDarkButtonClass"
                        >
                            Zobacz pełny dostęp
                        </a>

                        <Link
                            v-else-if="isPjmMode"
                            :href="currentPjmCompletion?.session_index_url ?? route('session.index')"
                            :class="completionQuickNavDarkButtonClass"
                        >
                            Przejdź do pełnej nauki
                        </Link>
                    </nav>
                        </template>

                <section
                    v-if="showTopicCompletionOverview && !showCompletionProgressPanel && !isPhoneViewport"
                    data-testid="topic-completion-overview"
                    :class="useFlatPhoneCompletionSummary
                        ? 'border-t border-[#e5e7eb] bg-white pt-4'
                        : 'overflow-hidden border border-[#e5e7eb] bg-white shadow-[0_22px_52px_rgba(15,23,42,0.04),0_10px_22px_rgba(15,23,42,0.02)]'"
                >
                    <div :class="useFlatPhoneCompletionSummary ? 'space-y-4' : 'space-y-5 px-5 py-5 sm:px-7 sm:py-6 lg:px-8'">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-[#0057a3]">
                                    Rekordy działów
                                </p>
                                <h3 class="mt-1 text-[1.04rem] font-semibold tracking-tight text-[#161414] sm:text-[1.14rem]">
                                    Czasy w tej kategorii
                                </h3>
                                <p class="mt-1 text-[0.88rem] leading-6 text-[#5b6675]">
                                    {{ topicCompletionScopeLabel }} · najlepszy czas zapisujemy po wyniku 100%.
                                </p>
                            </div>

                            <div
                                v-if="props.session.license_category_code"
                                class="inline-flex w-fit items-center border border-[#e5e7eb] bg-[#f8f8f8] px-2.5 py-1 text-[0.64rem] font-semibold uppercase tracking-[0.14em] text-[#5f6368]"
                            >
                                {{ props.session.license_category_code }}
                            </div>
                        </div>

                        <div class="space-y-2 md:hidden">
                            <article
                                v-for="item in topicCompletionRows"
                                :key="`topic-record-mobile-${item.topic_id}`"
                                class="border border-[#e5e7eb] bg-white px-3 py-3"
                                :class="item.is_current_topic ? 'border-[#d8b241] bg-[#fffdf6]' : ''"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[0.62rem] font-semibold uppercase tracking-[0.14em] text-[#6b7280]">
                                            {{ item.bucket_label }}
                                        </p>
                                        <h4 class="mt-1 text-[0.94rem] font-semibold leading-6 text-[#161414]">
                                            {{ item.label }}
                                        </h4>
                                    </div>

                                    <p class="shrink-0 text-right text-[0.95rem] font-semibold tabular-nums text-[#161414]">
                                        {{ topicCompletionPrimaryTimeLabel(item) }}
                                    </p>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 border px-2 py-1 text-[0.68rem] font-semibold leading-4"
                                        :class="topicCompletionStatusClass(item)"
                                    >
                                        <span
                                            v-if="item.record_state === 'first_record' || item.record_state === 'improved_record'"
                                            aria-hidden="true"
                                        >↑</span>
                                        {{ topicCompletionStatusLabel(item) }}
                                    </span>
                                    <span class="text-[0.72rem] font-medium text-[#6b7280]">
                                        {{ item.questions_count }} pytań
                                    </span>
                                </div>
                            </article>
                        </div>

                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full border-separate border-spacing-0 text-left">
                                <thead>
                                    <tr class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                        <th class="border-b border-[#e5e7eb] px-3 py-2.5">
                                            Dział
                                        </th>
                                        <th class="border-b border-[#e5e7eb] px-3 py-2.5">
                                            Czas
                                        </th>
                                        <th class="border-b border-[#e5e7eb] px-3 py-2.5">
                                            Próby
                                        </th>
                                        <th class="border-b border-[#e5e7eb] px-3 py-2.5">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in topicCompletionRows"
                                        :key="`topic-record-${item.topic_id}`"
                                        class="align-top"
                                        :class="item.is_current_topic ? 'bg-[#fffdf6]' : ''"
                                    >
                                        <td class="border-b border-[#eef1f4] px-3 py-3">
                                            <p class="text-[0.9rem] font-semibold leading-6 text-[#161414]">
                                                {{ item.label }}
                                            </p>
                                            <p class="mt-0.5 text-[0.72rem] font-medium uppercase tracking-[0.12em] text-[#6b7280]">
                                                {{ item.bucket_label }} · {{ item.questions_count }} pytań
                                            </p>
                                        </td>
                                        <td class="border-b border-[#eef1f4] px-3 py-3">
                                            <p class="text-[0.94rem] font-semibold tabular-nums text-[#161414]">
                                                {{ topicCompletionPrimaryTimeLabel(item) }}
                                            </p>
                                            <p class="mt-0.5 text-[0.74rem] leading-5 text-[#6b7280]">
                                                {{ topicCompletionTimeMetaLabel(item) }}
                                            </p>
                                        </td>
                                        <td class="border-b border-[#eef1f4] px-3 py-3">
                                            <p class="text-[0.9rem] font-semibold tabular-nums text-[#161414]">
                                                {{ topicCompletionAttemptsLabel(item) }}
                                            </p>
                                            <p class="mt-0.5 text-[0.74rem] leading-5 text-[#6b7280]">
                                                {{ topicCompletionAttemptsMetaLabel(item) }}
                                            </p>
                                        </td>
                                        <td class="border-b border-[#eef1f4] px-3 py-3">
                                            <span
                                                class="inline-flex items-center gap-1 border px-2.5 py-1 text-[0.72rem] font-semibold leading-5"
                                                :class="topicCompletionStatusClass(item)"
                                            >
                                                <span
                                                    v-if="item.record_state === 'first_record' || item.record_state === 'improved_record'"
                                                    aria-hidden="true"
                                                >↑</span>
                                                {{ topicCompletionStatusLabel(item) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section
                    v-if="showSessionRoadmap && (!showTopicCompletionOverview || isPhoneViewport) && !showCompletionProgressPanel"
                    :class="useFlatPhoneCompletionSummary
                        ? 'bg-white'
                        : 'overflow-hidden border border-[#e5e7eb] bg-white shadow-[0_22px_52px_rgba(15,23,42,0.04),0_10px_22px_rgba(15,23,42,0.02)]'"
                >
                    <div
                        v-if="useFlatPhoneCompletionSummary"
                        class="border-t border-[#e5e7eb] pt-4"
                    >
                        <div class="flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-[#0057a3]">
                                    Ścieżka działów
                                </p>
                                <p class="mt-1 text-[0.92rem] font-medium text-[#5b6675]">
                                    {{ mobileSessionRoadmapPositionLabel }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-[1.15rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                    {{ roadmapOverallProgressPercent }}%
                                </p>
                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.14em] text-[#6b7280]">
                                    całość
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 h-1.5 w-full overflow-hidden bg-[#edf2f7]">
                            <div
                                class="h-full bg-[#0071ce] transition-[width]"
                                :style="{ width: `${roadmapOverallProgressPercent}%` }"
                            />
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-if="mobileSessionRoadmapCurrentItem"
                                class="border-l-2 border-[#d8b241] bg-[#fffdf6] py-2.5 pl-3 pr-2.5"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#8a6a00]">
                                            {{ mobileSessionRoadmapCurrentBadge }}
                                        </p>
                                        <h4 class="mt-1 text-[1rem] font-semibold leading-6 text-[#161414]">
                                            Dział {{ mobileSessionRoadmapCurrentItem.position }}
                                        </h4>
                                        <p class="mt-1 text-[0.9rem] leading-5 text-[#4b5563]">
                                            {{ mobileSessionRoadmapCurrentItem.label }}
                                        </p>
                                    </div>

                                    <p class="shrink-0 text-right text-[0.92rem] font-semibold text-[#161414] tabular-nums">
                                        {{ mobileSessionRoadmapCurrentSummary }}
                                    </p>
                                </div>
                                <p
                                    v-if="mobileSessionRoadmapCurrentTimeLabel"
                                    class="mt-2 text-[0.78rem] font-medium leading-5 text-[#5b6675]"
                                >
                                    {{ mobileSessionRoadmapCurrentTimeLabel }}
                                </p>
                            </div>

                            <div
                                v-if="mobileSessionRoadmapNextItem"
                                class="border-l-2 border-[#0071ce] bg-[#f7fbff] py-2.5 pl-3 pr-2.5"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#0057a3]">
                                            Dalej
                                        </p>
                                        <h4 class="mt-1 text-[1rem] font-semibold leading-6 text-[#161414]">
                                            Dział {{ mobileSessionRoadmapNextItem.position }}
                                        </h4>
                                        <p class="mt-1 text-[0.9rem] leading-5 text-[#4b5563]">
                                            {{ mobileSessionRoadmapNextItem.label }}
                                        </p>
                                    </div>

                                    <p class="shrink-0 text-right text-[0.82rem] font-medium leading-5 text-[#5b6675]">
                                        {{ mobileSessionRoadmapNextItem.remainingQuestions === 0 ? 'Gotowe' : `${mobileSessionRoadmapNextItem.remainingQuestions} zostało` }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else
                                class="border-l-2 border-[#3f9b58] bg-[#f3fbf5] py-2.5 pl-3 pr-2.5"
                            >
                                <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-[#166534]">
                                    Gotowe
                                </p>
                                <p class="mt-1 text-[0.92rem] leading-5 text-[#345138]">
                                    Nie ma już kolejnego działu na tej ścieżce.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="relative px-7 py-7 sm:px-9 sm:py-8 lg:px-10 lg:py-9">
                        <button
                            type="button"
                            class="absolute left-3 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 items-center justify-center border border-[#e5e7eb] bg-white text-[1.1rem] text-[#161414] shadow-[0_12px_26px_rgba(15,23,42,0.08)] transition md:flex"
                            :class="canScrollSessionRoadmapBackward ? 'opacity-100 hover:border-[#d1d5db] hover:bg-[#fbfbfb]' : 'pointer-events-none opacity-35'"
                            @click="scrollSessionRoadmap('prev')"
                        >
                            <span aria-hidden="true">←</span>
                            <span class="sr-only">Przewiń działy w lewo</span>
                        </button>

                        <button
                            type="button"
                            class="absolute right-3 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 items-center justify-center border border-[#e5e7eb] bg-white text-[1.1rem] text-[#161414] shadow-[0_12px_26px_rgba(15,23,42,0.08)] transition md:flex"
                            :class="canScrollSessionRoadmapForward ? 'opacity-100 hover:border-[#d1d5db] hover:bg-[#fbfbfb]' : 'pointer-events-none opacity-35'"
                            @click="scrollSessionRoadmap('next')"
                        >
                            <span aria-hidden="true">→</span>
                            <span class="sr-only">Przewiń działy w prawo</span>
                        </button>

                        <div class="pointer-events-none absolute inset-y-0 left-7 hidden w-14 bg-gradient-to-r from-white to-transparent md:block lg:left-10" />
                        <div class="pointer-events-none absolute inset-y-0 right-7 hidden w-14 bg-gradient-to-l from-white to-transparent md:block lg:right-10" />

                        <div
                            ref="sessionRoadmapScroller"
                            class="overflow-x-auto scroll-smooth px-1 pb-3 pr-14 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
                            :class="isSessionRoadmapDragging ? 'cursor-grabbing snap-none select-none' : 'cursor-grab snap-x snap-mandatory'"
                            @scroll.passive="syncSessionRoadmapScrollState"
                            @pointerdown="startSessionRoadmapDrag"
                            @pointermove="moveSessionRoadmapDrag"
                            @pointerup="endSessionRoadmapDrag"
                            @pointercancel="endSessionRoadmapDrag"
                            @lostpointercapture="endSessionRoadmapDrag()"
                        >
                            <div class="flex min-w-max gap-10 pr-16 xl:gap-12 2xl:gap-14">
                                <article
                                    v-for="(topic, index) in sessionRoadmap"
                                    :key="`roadmap-${topic.id}`"
                                    data-session-roadmap-card
                                    :data-session-roadmap-topic-id="topic.id"
                                    class="relative w-[20rem] shrink-0 snap-start 2xl:w-[21.5rem]"
                                >
                                    <div
                                        v-if="index < sessionRoadmap.length - 1"
                                        class="absolute left-[3.75rem] right-[-3.25rem] top-7 h-px bg-[#e5e7eb] xl:right-[-3.75rem]"
                                        aria-hidden="true"
                                    />

                                    <div
                                        class="relative z-10 flex h-14 w-14 items-center justify-center border-2 text-[1.08rem] font-semibold tabular-nums"
                                        :class="topic.status === 'completed'
                                            ? 'border-[#3f9b58] bg-[#f2fbf5] text-[#166534]'
                                            : topic.status === 'current'
                                                ? 'border-[#d8b241] bg-[#fff9e8] text-[#8a6a00]'
                                                : topic.status === 'next'
                                                    ? 'border-[#0071ce] bg-[#f5f9ff] text-[#0057a3]'
                                                    : 'border-[#d1d5db] bg-white text-[#6b7280]'"
                                    >
                                        <span v-if="topic.status === 'completed'">✓</span>
                                        <span v-else>{{ topic.position }}</span>
                                    </div>

                                    <div class="mt-5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Dział {{ topic.position }}
                                            </p>
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.12em]"
                                                :class="topic.status === 'completed'
                                                    ? 'bg-[#eaf6ec] text-[#166534]'
                                                    : topic.status === 'current'
                                                        ? 'bg-[#fdf5d8] text-[#8a6a00]'
                                                        : topic.status === 'next'
                                                            ? 'bg-[#eff6ff] text-[#1d4ed8]'
                                                            : 'bg-[#f3f4f6] text-[#6b7280]'"
                                            >
                                                {{ topic.badge }}
                                            </span>
                                        </div>

                                        <h4 class="mt-4 text-[1.12rem] font-semibold leading-7 text-[#161414]">
                                            {{ topic.label }}
                                        </h4>

                                        <p class="mt-3 text-[0.92rem] leading-7 text-[#5b6675]">
                                            {{
                                                topic.status === 'completed'
                                                    ? 'Ten dział masz już domknięty.'
                                                : topic.status === 'current'
                                                    ? `Opanowane ${topic.masteredQuestions} z ${topic.totalQuestions} pytań.`
                                                        : topic.status === 'next'
                                                            ? 'To będzie następny krok po bieżącym dziale.'
                                                            : `Czeka jeszcze ${topic.remainingQuestions} pytań.`
                                            }}
                                        </p>

                                        <div class="mt-5 h-2 w-full overflow-hidden bg-[#edf2f7]">
                                            <div
                                                class="h-full transition-[width]"
                                                :class="topic.status === 'completed'
                                                    ? 'bg-[#3f9b58]'
                                                    : topic.status === 'current'
                                                        ? 'bg-[#d8b241]'
                                                        : topic.status === 'next'
                                                            ? 'bg-[#0071ce]'
                                                            : 'bg-[#cbd5e1]'"
                                                :style="{ width: `${topic.progressPercent}%` }"
                                            />
                                        </div>

                                        <div class="mt-4 flex items-end justify-between gap-4">
                                            <div>
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                    Postęp
                                                </p>
                                                <p class="mt-1.5 text-[1.12rem] font-semibold tracking-tight text-[#161414] tabular-nums">
                                                    {{ topic.masteredQuestions }} / {{ topic.totalQuestions }}
                                                </p>
                                            </div>

                                            <p class="text-right text-[0.84rem] font-medium leading-6 text-[#5b6675]">
                                                {{
                                                    topic.remainingQuestions === 0
                                                        ? 'Gotowe'
                                                        : `${topic.remainingQuestions} zostało`
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div
                            ref="sessionRoadmapLegendRef"
                            class="absolute bottom-4 right-4 z-30 flex items-end justify-end"
                        >
                            <Teleport to="body">
                                <Transition
                                    enter-active-class="ease-out duration-300"
                                    enter-from-class="opacity-0"
                                    enter-to-class="opacity-100"
                                    leave-active-class="ease-in duration-200"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div v-if="isSessionRoadmapLegendOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6 sm:px-0">
                                        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="isSessionRoadmapLegendOpen = false"></div>
                                        
                                        <div 
                                            class="relative w-full max-w-md transform text-left shadow-xl transition-all"
                                            :class="isExamLikeShell 
                                                ? 'rounded-none border border-[#e5e7eb] bg-white' 
                                                : 'rounded-xl bg-[#fdfdfc] ring-1 ring-black/5'"
                                        >
                                            <div class="px-6 py-6" :class="isExamLikeShell ? '' : 'bg-[#fcfbf8] rounded-xl'">
                                                <h3 :class="isExamLikeShell ? 'text-lg font-semibold text-[#161414]' : 'text-[1.18rem] font-semibold tracking-tight text-[#1f1a16]'">
                                                    Legenda mapy sesji
                                                </h3>
                                                
                                                <div class="mt-5 space-y-3" :class="isExamLikeShell ? 'text-[0.95rem] leading-6 text-[#4b5563]' : 'text-[0.95rem] leading-6 text-[#6d6559]'">
                                                    <p>
                                                        <span class="font-semibold" :class="isExamLikeShell ? 'text-[#166534]' : 'text-[#345138]'">Zaliczony</span>:
                                                        ten dział masz już za sobą.
                                                    </p>
                                                    <p>
                                                        <span class="font-semibold" :class="isExamLikeShell ? 'text-[#8a6a00]' : 'text-[#6a5e4d]'">Tu jesteś teraz</span>:
                                                        to dział, który kończysz teraz.
                                                    </p>
                                                    <p>
                                                        <span class="font-semibold" :class="isExamLikeShell ? 'text-[#0057a3]' : 'text-[#837b6d]'">Dalej</span>:
                                                        to następny dział na Twojej ścieżce.
                                                    </p>
                                                    <p>
                                                        <span class="font-semibold" :class="isExamLikeShell ? 'text-[#6b7280]' : 'text-[#9c9588]'">Przed Tobą</span>:
                                                        do tych działów dojdziesz później.
                                                    </p>
                                                </div>

                                                <div class="mt-6 space-y-3 border-t pt-5" :class="isExamLikeShell ? 'border-[#eef1f4] text-[0.9rem] leading-6 text-[#4b5563]' : 'border-[#ddd6c8]/60 text-[0.9rem] leading-6 text-[#6d6559]'">
                                                    <p>
                                                        <strong class="font-semibold" :class="isExamLikeShell ? 'text-[#111827]' : 'text-[#2a2621]'">Kiedy uznajemy dział za zaliczony?</strong><br>
                                                        Wtedy, gdy opanujesz w nim wszystkie pytania. Każde z nich musisz rozwiązać poprawnie.
                                                    </p>
                                                    <p>
                                                        <strong class="font-semibold" :class="isExamLikeShell ? 'text-[#111827]' : 'text-[#2a2621]'">Co blokuje zaliczenie działu?</strong><br>
                                                        Wystarczy jedno pominięte lub błędnie rozwiązane pytanie. Żeby zaliczyć dział, musisz po prostu wrócić do tych braków i poprawić błędy.
                                                    </p>
                                                </div>

                                                <div class="mt-8 flex justify-end">
                                                    <button
                                                        type="button"
                                                        :class="isExamLikeShell 
                                                            ? 'rounded-none border border-[#d2b35b] bg-[#efc54f] px-5 py-2 text-[0.85rem] font-semibold text-[#463309] transition hover:border-[#c8a949] hover:bg-[#e8bd4c]' 
                                                            : 'rounded border border-[#ddd6c8] bg-white px-5 py-2 text-[0.85rem] font-medium text-[#5f584d] transition hover:border-[#cfc6b7] hover:bg-[#f7f4ee] hover:text-[#201d1a]'"
                                                        @click="isSessionRoadmapLegendOpen = false"
                                                    >
                                                        Rozumiem
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </Teleport>

                            <button
                                type="button"
                                class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280] transition hover:text-[#161414]"
                                @click="isSessionRoadmapLegendOpen = !isSessionRoadmapLegendOpen"
                            >
                                Legenda
                            </button>
                        </div>
                    </div>
                </section>

            <section
                v-if="shouldShowCompletionQuestionReview"
                ref="completionResultsRef"
                data-testid="completion-question-review"
                :class="completionReviewSectionClass"
            >
                <div :class="completionReviewHeaderClass">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-[0.95rem] font-bold uppercase tracking-[0.04em] text-[#161414]">
                                    {{ completionResultsHeading }}
                                </h3>
                                <span
                                    v-if="hasIncorrectAnswers"
                                    class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#e53935] px-2 text-[0.72rem] font-semibold text-white"
                                >
                                    {{ displayIncorrectAnswersCount }}
                                </span>
                            </div>
                            <p class="mt-2 text-[0.84rem] font-medium leading-5 text-[#64748b]">
                                Rozwiń pytanie, aby zobaczyć wyjaśnienie i szczegóły.
                            </p>
                        </div>
                    </div>
                </div>

                <div :class="completionReviewBodyClass">
                <div :class="completionReviewListClass">
                    <component
                        :is="useNativeMobileSessionResult ? 'details' : 'article'"
                        v-for="result in completionReviewResults"
                        :key="result.id"
                        :class="useNativeMobileSessionResult ? 'group border-t border-[#e4e7ec]' : 'pl-0'"
                    >
                        <summary
                            v-if="useNativeMobileSessionResult"
                            class="flex min-h-14 cursor-pointer list-none items-start gap-3 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#0b5cff] [&::-webkit-details-marker]:hidden"
                        >
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-[#f04438]" aria-hidden="true" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.64rem] font-semibold text-[#667085]">
                                    Pytanie {{ result.sequence_number }} · {{ formatResponseTime(result.response_time_ms) }}
                                </span>
                                <span
                                    class="mt-1.5 block text-[0.82rem] font-medium leading-5 text-[#101828] [&_strong]:font-semibold"
                                    v-html="renderSessionInlineFormattedHtml(result.prompt ?? 'Pytanie niedostępne.')"
                                />
                            </span>
                            <svg aria-hidden="true" class="mt-1 h-4 w-4 shrink-0 text-[#667085] transition-transform group-open:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none">
                                <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </summary>
                        <div
                            :class="completionReviewArticleClass"
                        >
                            <div class="overflow-hidden bg-white lg:order-2">
                                <div :class="completionReviewMediaFrameClass">
                                    <div class="w-full">
                                        <template v-if="result.media.length > 0">
                                            <p
                                                v-if="result.media[0]?.kind === 'video'"
                                                class="mb-2 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]"
                                            >
                                                Kadr z 0.2 s przed końcem filmu
                                            </p>

                                            <QuestionVideoFrameWithAnnotations
                                                v-if="hasResultVideoFrameAnnotations(result) && result.media[0]?.url"
                                                :src="result.media[0].url"
                                                :poster="result.media[0].poster_url ?? null"
                                                :frame-time-seconds="resultVideoFrameTimeSeconds(result)"
                                                :natural-width="result.media[0]?.width ?? null"
                                                :natural-height="result.media[0]?.height ?? null"
                                                :annotations="resultVideoFrameAnnotations(result)"
                                                :presentation-class="'relative w-full'"
                                                :video-class="completionReviewMediaAssetClass"
                                            />

                                            <video
                                                v-else-if="result.media[0]?.kind === 'video' && result.media[0]?.url"
                                                :src="result.media[0].url ?? undefined"
                                                :poster="result.media[0].poster_url ?? undefined"
                                                preload="metadata"
                                                muted
                                                playsinline
                                                disablepictureinpicture
                                                controlslist="nodownload noplaybackrate noremoteplayback nofullscreen"
                                                class="pointer-events-none"
                                                :class="completionReviewMediaAssetClass"
                                                @loadedmetadata="prepareResultVideoPreview"
                                                @seeked="freezeResultVideoPreview"
                                            />

                                            <QuestionImageWithAnnotations
                                                v-else-if="resultMediaPreviewUrl(result.media[0])"
                                                :src="resultMediaPreviewUrl(result.media[0])"
                                                alt="Podgląd pytania"
                                                loading="lazy"
                                                :natural-width="result.media[0]?.width ?? null"
                                                :natural-height="result.media[0]?.height ?? null"
                                                :annotations="hasResultExplanationAnnotations(result) ? resultImageAnnotations(result) : []"
                                                :presentation-class="'w-full'"
                                                :image-class="completionReviewMediaAssetClass"
                                            />
                                        </template>

                                        <QuestionResultMediaFallback v-else />
                                    </div>
                                </div>
                            </div>

                            <div :class="[completionReviewContentClass, 'lg:order-1']">
                                <div class="max-w-4xl" :class="useNativeMobileSessionResult ? 'hidden' : ''">
                                    <div class="flex flex-wrap items-center gap-2.5 text-[0.72rem] font-medium">
                                        <p class="font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                            Pytanie {{ result.sequence_number }}
                                        </p>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5"
                                            :class="resultStatusClass(result)"
                                        >
                                            {{ resultStatusLabel(result) }}
                                        </span>
                                        <span class="text-[#6b7280]">
                                            Czas: {{ formatResponseTime(result.response_time_ms) }}
                                        </span>
                                    </div>
                                    <h3 :class="completionReviewPromptClass">
                                        <span
                                            class="[&_strong]:font-semibold [&_strong]:text-inherit"
                                            v-html="renderSessionInlineFormattedHtml(result.prompt ?? 'Pytanie niedostępne.')"
                                        />
                                    </h3>
                                </div>

                                <div :class="completionReviewAnswerGridClass">
                                    <div
                                        :class="[completionReviewAnswerCardBaseClass, resultUserAnswerCardClass(result)]"
                                    >
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em]">
                                            Twoja odpowiedź
                                        </p>
                                        <p class="mt-2 text-base font-semibold text-neutral-900">
                                            {{ resultUserAnswerLabel(result) }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6">
                                            {{ resultUserAnswerText(result) }}
                                        </p>
                                    </div>

                                    <div
                                        :class="completionReviewNeutralAnswerCardClass"
                                    >
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em]">
                                            Poprawna odpowiedź
                                        </p>
                                        <p class="mt-2 text-base font-semibold text-neutral-900">
                                            {{ result.correct_answer ? `Odpowiedź ${result.correct_answer}` : 'Brak danych o poprawnej odpowiedzi' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6">
                                            {{ result.correct_answer_text ?? 'Nie udało się odczytać treści poprawnej odpowiedzi.' }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="result.is_correct === false"
                                    class="space-y-3"
                                >
                                    <div
                                        v-if="canShowInlineEditControls"
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                            @click="openQuestionEditor(result)"
                                        >
                                            Edytuj pytanie
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                            @click="openExplanationEditor(result)"
                                        >
                                            Edytuj wyjaśnienie
                                        </button>
                                        <a
                                            :href="`/admin/questions/${result.id}/edit`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center border border-[#d1d5db] bg-white px-3 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[#374151] transition hover:border-[#9ca3af] hover:text-[#161414]"
                                        >
                                            Edytuj grafikę
                                        </a>
                                    </div>
                                    <QuestionExplanationRuntimeBlock
                                        :explanation-html="resultExplanationHtml(result)"
                                        :fallback-text="result.explanation_asset?.body ?? null"
                                        :asset="result.explanation_asset"
                                        :sign-references="result.explanation_sign_references ?? []"
                                        :show-image="visualExplanationsMode !== 'off'"
                                        :show-sign-references="visualExplanationsMode !== 'off'"
                                        :palette="inlineFormattingPalette"
                                        :enable-bold-formatting="enableInlineBold"
                                        :enable-color-formatting="enableInlineColors"
                                    />
                                </div>
                            </div>
                        </div>
                    </component>
                </div>

                <div
                    v-if="hasIncorrectAnswers && correctAnsweredResults.length > 0"
                    class="mt-8 border-t border-[#e5e7eb] pt-5"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]">
                                Dobrze rozwiązane
                            </p>
                            <h4 class="mt-2 text-xl font-semibold tracking-tight text-[#161414]">
                                Dobre odpowiedzi
                            </h4>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center px-1 py-1 text-sm font-medium text-[#4b5563] transition hover:text-[#161414]"
                            @click="void toggleCorrectCompletionResults()"
                        >
                            {{ completionCorrectResultsToggleLabel }}
                        </button>
                    </div>

                    <div
                        v-if="showCorrectCompletionResults"
                        class="mt-5 space-y-4"
                    >
                        <component
                            :is="useNativeMobileSessionResult ? 'details' : 'article'"
                            v-for="result in correctAnsweredResults"
                            :key="`correct-${result.id}`"
                            :class="useNativeMobileSessionResult ? 'group border-t border-[#e4e7ec]' : 'pl-0'"
                        >
                            <summary
                                v-if="useNativeMobileSessionResult"
                                class="flex min-h-14 cursor-pointer list-none items-start gap-3 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#0b5cff] [&::-webkit-details-marker]:hidden"
                            >
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-[#12b76a]" aria-hidden="true" />
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[0.64rem] font-semibold text-[#667085]">
                                        Pytanie {{ result.sequence_number }} · {{ formatResponseTime(result.response_time_ms) }}
                                    </span>
                                    <span
                                        class="mt-1.5 block text-[0.82rem] font-medium leading-5 text-[#101828] [&_strong]:font-semibold"
                                        v-html="renderSessionInlineFormattedHtml(result.prompt ?? 'Pytanie niedostępne.')"
                                    />
                                </span>
                                <svg aria-hidden="true" class="mt-1 h-4 w-4 shrink-0 text-[#667085] transition-transform group-open:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </summary>
                            <div :class="completionReviewArticleClass">
                                <div class="overflow-hidden bg-white lg:order-2">
                                    <div :class="completionReviewMediaFrameClass">
                                        <div class="w-full">
                                            <template v-if="result.media.length > 0">
                                                <p
                                                    v-if="result.media[0]?.kind === 'video'"
                                                    class="mb-2 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]"
                                                >
                                                    Kadr z 0.2 s przed końcem filmu
                                                </p>

                                                <QuestionVideoFrameWithAnnotations
                                                    v-if="hasResultVideoFrameAnnotations(result) && result.media[0]?.url"
                                                    :src="result.media[0].url"
                                                    :poster="result.media[0].poster_url ?? null"
                                                    :frame-time-seconds="resultVideoFrameTimeSeconds(result)"
                                                    :natural-width="result.media[0]?.width ?? null"
                                                    :natural-height="result.media[0]?.height ?? null"
                                                    :annotations="resultVideoFrameAnnotations(result)"
                                                    :presentation-class="'relative w-full'"
                                                    :video-class="completionReviewMediaAssetClass"
                                                />

                                                <video
                                                    v-else-if="result.media[0]?.kind === 'video' && result.media[0]?.url"
                                                    :src="result.media[0].url ?? undefined"
                                                    :poster="result.media[0].poster_url ?? undefined"
                                                    preload="metadata"
                                                    muted
                                                    playsinline
                                                    disablepictureinpicture
                                                    controlslist="nodownload noplaybackrate noremoteplayback nofullscreen"
                                                    class="pointer-events-none"
                                                    :class="completionReviewMediaAssetClass"
                                                    @loadedmetadata="prepareResultVideoPreview"
                                                    @seeked="freezeResultVideoPreview"
                                                />

                                                <QuestionImageWithAnnotations
                                                    v-else-if="resultMediaPreviewUrl(result.media[0])"
                                                    :src="resultMediaPreviewUrl(result.media[0])"
                                                    alt="Podgląd pytania"
                                                    loading="lazy"
                                                    :natural-width="result.media[0]?.width ?? null"
                                                    :natural-height="result.media[0]?.height ?? null"
                                                    :annotations="hasResultExplanationAnnotations(result) ? resultImageAnnotations(result) : []"
                                                    :presentation-class="'w-full'"
                                                    :image-class="completionReviewMediaAssetClass"
                                                />
                                            </template>

                                            <img
                                                v-else-if="resultReferenceImageUrl(result)"
                                                :src="resultReferenceImageUrl(result) ?? undefined"
                                                :alt="resultReferenceImageAlt(result)"
                                                loading="lazy"
                                                :class="completionReviewMediaAssetClass"
                                            />

                                            <QuestionResultMediaFallback v-else />
                                        </div>
                                    </div>
                                </div>

                                <div :class="[completionReviewContentClass, 'lg:order-1']">
                                    <div class="max-w-4xl" :class="useNativeMobileSessionResult ? 'hidden' : ''">
                                        <div class="flex flex-wrap items-center gap-2.5 text-[0.72rem] font-medium">
                                            <p class="font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                                                Pytanie {{ result.sequence_number }}
                                            </p>
                                            <span class="inline-flex items-center rounded-full border border-[#e5e7eb] bg-[#f7f7f7] px-2 py-0.5 text-[#5f6368]">
                                                Dobrze
                                            </span>
                                            <span class="text-[#6b7280]">
                                                Czas: {{ formatResponseTime(result.response_time_ms) }}
                                            </span>
                                        </div>
                                        <h3 :class="completionReviewPromptClass">
                                            <span
                                                class="[&_strong]:font-semibold [&_strong]:text-inherit"
                                                v-html="renderSessionInlineFormattedHtml(result.prompt ?? 'Pytanie niedostępne.')"
                                            />
                                        </h3>
                                    </div>

                                    <div :class="completionReviewAnswerGridClass">
                                        <div :class="completionReviewNeutralAnswerCardClass">
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em]">
                                                Twoja odpowiedź
                                            </p>
                                            <p class="mt-2 text-base font-semibold text-neutral-900">
                                                {{ resultUserAnswerLabel(result) }}
                                            </p>
                                            <p class="mt-1 text-sm leading-6">
                                                {{ resultUserAnswerText(result) }}
                                            </p>
                                        </div>

                                        <div :class="completionReviewNeutralAnswerCardClass">
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em]">
                                                Poprawna odpowiedź
                                            </p>
                                            <p class="mt-2 text-base font-semibold text-neutral-900">
                                                {{ result.correct_answer ? `Odpowiedź ${result.correct_answer}` : 'Brak danych o poprawnej odpowiedzi' }}
                                            </p>
                                            <p class="mt-1 text-sm leading-6">
                                                {{ result.correct_answer_text ?? 'Nie udało się odczytać treści poprawnej odpowiedzi.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </component>
                    </div>
                </div>
                </div>
            </section>
                    </div>
                </div>
            </section>

            <Transition name="public-demo-completion-prompt">
                <section
                    v-if="showPublicDemoCompletionPrompt"
                    class="fixed inset-0 z-[85] flex items-center justify-center overflow-y-auto bg-[#0f172a]/28 px-4 py-6 backdrop-blur-[3px] sm:px-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="public-demo-completion-prompt-title"
                >
                    <div class="relative max-h-[calc(100svh-3rem)] w-full max-w-[68rem] overflow-y-auto border border-[#dbe3ec] bg-white px-5 py-6 text-left shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:px-8 sm:py-7">
                        <button
                            type="button"
                            class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center border border-[#d1d5db] bg-white text-lg font-semibold leading-none text-[#334155] transition hover:border-[#94a3b8] hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0646a8] focus-visible:ring-offset-2"
                            aria-label="Zamknij komunikat"
                            @click="dismissPublicDemoCompletionPrompt"
                        >
                            ×
                        </button>

                        <div class="px-8 text-center">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#0646a8]">
                                Demo zakończone
                            </p>
                            <h2
                                id="public-demo-completion-prompt-title"
                                class="mx-auto mt-2 max-w-[42rem] text-balance text-2xl font-semibold leading-[1.2] tracking-tight text-[#111827] sm:text-[1.85rem]"
                            >
                                Tak wygląda nauka w trybie zapoznawczym
                            </h2>
                        </div>

                        <div class="mt-6 grid gap-5 text-[0.93rem] leading-6 text-[#111827] sm:grid-cols-2 sm:gap-8 sm:text-[0.96rem]">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <img
                                    src="/images/study/demo-rabbit.png"
                                    alt=""
                                    aria-hidden="true"
                                    class="mt-1 h-20 w-20 shrink-0 object-contain sm:h-24 sm:w-24"
                                >
                                <ul class="min-w-0 list-disc space-y-0.5 pl-4 xl:whitespace-nowrap">
                                    <li>Najpierw przechodzisz pytania szybko.</li>
                                    <li>Poznajesz bazę pytań.</li>
                                    <li>Uczysz się schematów.</li>
                                    <li>Wyrabiasz tempo.</li>
                                </ul>
                            </div>
                            <div class="flex items-start gap-3 sm:gap-4">
                                <img
                                    src="/images/study/demo-turtle.png"
                                    alt=""
                                    aria-hidden="true"
                                    class="mt-1 h-20 w-20 shrink-0 object-contain sm:h-24 sm:w-24"
                                >
                                <ul class="min-w-0 list-disc space-y-0.5 pl-4 xl:whitespace-nowrap">
                                    <li>W pełnej wersji możesz przejść całą bazę pytań.</li>
                                    <li>Później wrócisz do błędów i wyjaśnień.</li>
                                    <li>Robisz spokojne powtórki.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <button
                                v-if="!publicDemoPaymentRequired && publicDemoPrimaryUsesRegisterDrawer"
                                type="button"
                                class="inline-flex min-h-[3.1rem] items-center justify-center rounded-none border border-[#0646a8] bg-[#0646a8] px-5 py-3 text-center text-sm font-semibold text-white shadow-none transition hover:border-[#053b8d] hover:bg-[#053b8d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0646a8] focus-visible:ring-offset-2"
                                @click="openPublicDemoRegisterDrawer"
                            >
                                {{ publicDemoCompletionPrimaryLabel }}
                            </button>
                            <a
                                v-else
                                :href="publicDemoCompletionPrimaryHref"
                                class="inline-flex min-h-[3.1rem] items-center justify-center rounded-none border border-[#0646a8] bg-[#0646a8] px-5 py-3 text-center text-sm font-semibold text-white shadow-none transition hover:border-[#053b8d] hover:bg-[#053b8d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0646a8] focus-visible:ring-offset-2"
                            >
                                {{ publicDemoCompletionPrimaryLabel }}
                            </a>
                            <button
                                type="button"
                                class="inline-flex min-h-[3.1rem] items-center justify-center rounded-none border border-[#cbd5e1] bg-white px-5 py-3 text-center text-sm font-semibold text-[#111827] transition hover:border-[#94a3b8] hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0646a8] focus-visible:ring-offset-2"
                                @click="dismissPublicDemoCompletionPrompt"
                            >
                                Sprawdź wynik
                            </button>
                        </div>
                    </div>
                </section>
            </Transition>

            <Transition name="public-demo-gate">
                <section
                    v-if="isPublicDemoGate"
                    class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-[#0f172a]/28 px-3 py-4 backdrop-blur-[3px] sm:px-6 sm:py-6"
                    aria-label="Podgląd modułu testów"
                    aria-modal="true"
                    role="dialog"
                >
                    <div
                        lang="pl"
                        class="relative max-h-[calc(100svh-2rem)] w-full max-w-[58rem] overflow-y-auto rounded-none border border-[#cbd6e4] bg-white px-5 py-5 text-left shadow-none sm:max-h-[calc(100svh-3rem)] sm:px-7 sm:py-5 lg:max-h-none lg:overflow-visible"
                    >
                        <button
                            type="button"
                            aria-label="Zamknij"
                            class="absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-none border border-[#cbd6e4] bg-white text-[#475569] shadow-none transition hover:border-[#94a3b8] hover:bg-[#f8fafc] hover:text-[#17233b] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#94a3b8] sm:right-5 sm:top-5"
                            @click="dismissPublicDemoGate"
                        >
                            <X :size="21" :stroke-width="2" aria-hidden="true" />
                        </button>

                        <header class="pr-11">
                            <div class="flex items-center gap-3">
                                <Settings :size="30" :stroke-width="2.1" class="shrink-0 text-[#17233b]" aria-hidden="true" />
                                <h2 class="text-2xl font-semibold tracking-[-0.02em] text-[#17233b] sm:text-[1.7rem]">
                                    Dostosuj naukę do siebie
                                </h2>
                            </div>
                            <p class="mt-1.5 text-sm leading-6 text-[#536176] sm:text-[0.95rem]">
                                Ustawienia możesz zmienić w dowolnym momencie podczas nauki.
                            </p>
                        </header>

                        <section class="mt-3 bg-white px-4 py-3.5">
                            <h3 class="text-base font-semibold text-[#1f2937]">Aktualne ustawienia</h3>
                            <div class="mt-3 grid gap-x-8 gap-y-3 text-[0.9rem] sm:grid-cols-2 sm:text-[0.92rem]">
                                <div class="space-y-3 sm:pr-7">
                                    <div class="grid grid-cols-[1.5rem_minmax(7.5rem,1fr)_auto] items-center gap-2.5">
                                        <MessageSquareText :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Po odpowiedzi:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicFeedbackModeLabel }}</strong>
                                    </div>
                                    <div class="grid grid-cols-[1.5rem_minmax(7.5rem,1fr)_auto] items-center gap-2.5">
                                        <ArrowRight :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Przejście dalej:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicAdvanceModeLabel }}</strong>
                                    </div>
                                    <div class="grid grid-cols-[1.5rem_minmax(7.5rem,1fr)_auto] items-center gap-2.5">
                                        <Gauge :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Prędkość odtwarzania:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicMediaModeLabel }}</strong>
                                    </div>
                                </div>
                                <div class="space-y-3 sm:pl-1">
                                    <div class="grid grid-cols-[1.5rem_minmax(6.5rem,1fr)_auto] items-center gap-2.5">
                                        <Volume2 :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Audio pytania:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicQuestionAudioModeLabel }}</strong>
                                    </div>
                                    <div class="grid grid-cols-[1.5rem_minmax(6.5rem,1fr)_auto] items-center gap-2.5">
                                        <Lightbulb :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Wskazówki:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicInlineFormattingLabel }}</strong>
                                    </div>
                                    <div class="grid grid-cols-[1.5rem_minmax(6.5rem,1fr)_auto] items-center gap-2.5">
                                        <ArrowUpRight :size="19" :stroke-width="2" class="text-[#334155]" aria-hidden="true" />
                                        <span class="text-[#536176]">Strzałki pomocnicze:</span>
                                        <strong class="font-semibold text-[#17233b]">{{ classicAnnotationsLabel }}</strong>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="mt-2.5 grid gap-2.5 sm:grid-cols-2">
                            <article class="flex gap-3 bg-white px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none bg-[#f3f6fa] text-[#17233b]">
                                    <MessageSquareText :size="22" :stroke-width="2" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-[#17233b]">Po odpowiedzi</h3>
                                    <p class="mt-1.5 text-[0.85rem] leading-[1.5] text-[#445269] sm:text-sm">Wybierz, co ma się dziać po udzieleniu odpowiedzi. Szybko, z wyjaśnieniem albo wynik na końcu.</p>
                                </div>
                            </article>
                            <article class="flex gap-3 bg-white px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none bg-[#f3f6fa] text-[#17233b]">
                                    <Volume2 :size="22" :stroke-width="2" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-[#17233b]">Audio pytania</h3>
                                    <p class="mt-1.5 text-[0.85rem] leading-[1.5] text-[#445269] sm:text-sm">Czytaj pytania sam albo słuchaj lektora. Czytanie własne = szybciej, lektor = gdy jesteś zmęczony lub pytania są długie.</p>
                                </div>
                            </article>
                            <article class="flex gap-3 bg-white px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none bg-[#f3f6fa] text-[#17233b]">
                                    <Lightbulb :size="22" :stroke-width="2" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-[#17233b]">Wskazówki w pytaniu</h3>
                                    <p class="mt-1.5 text-[0.85rem] leading-[1.5] text-[#445269] sm:text-sm">Pogrubienia, kolory i strzałki pomagają zwrócić uwagę na to, co ważne.</p>
                                </div>
                            </article>
                            <article class="flex gap-3 bg-white px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none bg-[#f3f6fa] text-[#17233b]">
                                    <SquarePlay :size="22" :stroke-width="2" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-[#17233b]">Filmy w pytaniach</h3>
                                    <p class="mt-1.5 text-[0.85rem] leading-[1.5] text-[#445269] sm:text-sm">Zdecyduj, czy filmy mają ruszać automatycznie i jak szybko je odtwarzać. Możesz też od razu oglądać końcówkę filmu (ostatnie 2 s).</p>
                                </div>
                            </article>
                        </section>

                        <section class="mt-2.5 bg-white px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Keyboard :size="21" :stroke-width="2" class="text-[#17233b]" aria-hidden="true" />
                                <h3 class="text-base font-semibold text-[#17233b]">Skróty klawiaturowe</h3>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-[0.82rem] text-[#445269] sm:text-sm">
                                <span class="inline-flex items-center gap-1.5"><kbd class="rounded-none border border-[#cbd6e4] bg-[#f8fafc] px-2.5 py-1.5 font-semibold text-[#17233b]">A lub ←</kbd><span>— zaznacz odpowiedź TAK</span></span>
                                <span class="inline-flex items-center gap-1.5"><kbd class="rounded-none border border-[#cbd6e4] bg-[#f8fafc] px-2.5 py-1.5 font-semibold text-[#17233b]">S lub ↓</kbd><span>— pokaż wyjaśnienie</span></span>
                                <span class="inline-flex items-center gap-1.5"><kbd class="rounded-none border border-[#cbd6e4] bg-[#f8fafc] px-2.5 py-1.5 font-semibold text-[#17233b]">D lub →</kbd><span>— zaznacz odpowiedź NIE</span></span>
                            </div>
                            <p class="mt-2.5 text-[0.82rem] leading-5 text-[#536176] sm:text-sm">Skróty przyspieszają naukę i pozwalają przechodzić przez sesję bez używania myszy.</p>
                        </section>

                        <aside class="mt-2.5 flex gap-3 bg-white px-4 py-3 text-[0.82rem] leading-5 text-[#445269] sm:text-sm">
                            <Info :size="19" :stroke-width="2.1" class="mt-0.5 shrink-0 text-[#17233b]" aria-hidden="true" />
                            <p>
                                Pamiętaj, że czytając pytania sam, czytasz je szybciej i jesteś w stanie przerobić większą ilość pytań podczas sesji.<br>
                                Tryb lektora sprawdza się super, kiedy jesteś zmęczony albo na pytaniach specjalistycznych, kiedy są bardzo długie.
                            </p>
                        </aside>

                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-[#445269]">
                                <input
                                    v-model="skipPublicDemoGateNextTime"
                                    type="checkbox"
                                    class="h-5 w-5 rounded-none border-[#cbd6e4] text-[#0646a8] focus:ring-[#0646a8]"
                                >
                                <span>Nie pokazuj tego okna ponownie</span>
                            </label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[3rem] items-center justify-center rounded-none border border-[#cbd6e4] bg-white px-6 py-3 text-sm font-semibold text-[#17233b] shadow-none transition hover:border-[#94a3b8] hover:bg-[#f8fafc] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#94a3b8] focus-visible:ring-offset-2"
                                    @click="customizePublicDemoSettings"
                                >
                                    Dostosuj ustawienia
                                </button>
                                <Link
                                    :href="publicDemoGateDemoHref"
                                    class="inline-flex min-h-[3rem] items-center justify-center rounded-none border border-[#0646a8] bg-[#0646a8] px-6 py-3 text-sm font-semibold text-white transition hover:border-[#053b8d] hover:bg-[#053b8d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0646a8] focus-visible:ring-offset-2"
                                    @click="rememberPublicDemoGatePreference"
                                >
                                    Rozpocznij naukę
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </Transition>
        </div>

        <AuthTopNavigation
            v-if="isPublicDemoMode && publicDemoPrimaryUsesRegisterDrawer && publicDemoAuthDrawerOpen"
        />

        <LoginDrawer
            v-if="isPublicDemoMode && publicDemoPrimaryUsesRegisterDrawer"
            standalone
            :open="publicDemoLoginDrawerOpen"
            :retain-visual="publicDemoRetainsAuthDrawerVisual('login')"
            :hide-visual="publicDemoHidesAuthDrawerVisual('login')"
            @close="closePublicDemoAuthDrawers"
            @open-register="openPublicDemoRegisterDrawer"
        />

        <RegisterDrawer
            v-if="isPublicDemoMode && publicDemoPrimaryUsesRegisterDrawer"
            standalone
            :open="publicDemoRegisterDrawerOpen"
            :categories="publicDemoRegistrationCategories"
            :retain-visual="publicDemoRetainsAuthDrawerVisual('register')"
            :hide-visual="publicDemoHidesAuthDrawerVisual('register')"
            @close="closePublicDemoAuthDrawers"
            @open-login="openPublicDemoLoginDrawer"
        />
    </component>
</template>

<style scoped>
@import url('https://fonts.bunny.net/css?family=inter:400,500,600&display=swap');

.question-copy,
.hint-copy,
.hint-label {
    font-family: 'Inter', 'Figtree', system-ui, sans-serif;
}

.question-copy {
    letter-spacing: -0.01em;
}

.hint-copy {
    letter-spacing: -0.01em;
}

.hint-timing-slider {
    -webkit-appearance: none;
    appearance: none;
    height: 0.35rem;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(108, 100, 88, 0.72) 0%, rgba(198, 191, 180, 0.74) 100%);
    outline: none;
}

.hint-timing-slider::-webkit-slider-runnable-track {
    height: 0.35rem;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(108, 100, 88, 0.72) 0%, rgba(198, 191, 180, 0.74) 100%);
}

.hint-timing-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    margin-top: -0.35rem;
    height: 1.05rem;
    width: 1.05rem;
    border: 1px solid rgba(108, 100, 88, 0.18);
    border-radius: 999px;
    background: #fdfcf9;
    box-shadow: 0 4px 12px rgba(94, 87, 77, 0.11);
    cursor: pointer;
}

.hint-timing-slider::-moz-range-track {
    height: 0.35rem;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(108, 100, 88, 0.72) 0%, rgba(198, 191, 180, 0.74) 100%);
}

.hint-timing-slider::-moz-range-thumb {
    height: 1.05rem;
    width: 1.05rem;
    border: 1px solid rgba(108, 100, 88, 0.18);
    border-radius: 999px;
    background: #fdfcf9;
    box-shadow: 0 4px 12px rgba(94, 87, 77, 0.11);
    cursor: pointer;
}

.topics-handle-trigger {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: translateY(-50%) rotate(180deg);
}

.learning-handle-trigger {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

.topics-handle-enter-active,
.topics-handle-leave-active,
.learning-handle-enter-active,
.learning-handle-leave-active {
    transition: opacity 180ms ease;
}

.topics-handle-enter-from,
.topics-handle-leave-to,
.learning-handle-enter-from,
.learning-handle-leave-to {
    opacity: 0;
}

.learning-backdrop-enter-active,
.learning-backdrop-leave-active {
    transition: opacity 220ms ease;
}

.learning-backdrop-enter-from,
.learning-backdrop-leave-to {
    opacity: 0;
}

.topics-panel-enter-active,
.topics-panel-leave-active,
.learning-panel-enter-active,
.learning-panel-leave-active {
    transition:
        transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
        opacity 180ms ease;
    will-change: transform, opacity;
}

.topics-panel-enter-from,
.topics-panel-leave-to {
    opacity: 0;
    transform: translateX(-22px);
}

.learning-panel-enter-from,
.learning-panel-leave-to {
    opacity: 0;
    transform: translateX(22px);
}

.side-panel-scroll {
    scrollbar-gutter: stable;
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.42) transparent;
}

.side-panel-scroll-left {
    direction: rtl;
}

.side-panel-scroll-inner {
    direction: ltr;
}

.side-panel-scroll::-webkit-scrollbar {
    width: 8px;
}

.side-panel-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.side-panel-scroll::-webkit-scrollbar-thumb {
    border: 2px solid transparent;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.34);
    background-clip: padding-box;
}

.side-panel-scroll:hover::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.5);
    background-clip: padding-box;
}

.focus-copy-enter-active,
.focus-copy-leave-active {
    transition:
        opacity 260ms ease,
        transform 320ms cubic-bezier(0.19, 1, 0.22, 1),
        filter 260ms ease;
    will-change: opacity, transform, filter;
}

.focus-copy-enter-from,
.focus-copy-leave-to {
    opacity: 0;
    transform: translateY(4px);
    filter: blur(0.6px);
}

.public-demo-gate-enter-active,
.public-demo-gate-leave-active {
    transition:
        opacity 220ms ease,
        backdrop-filter 220ms ease;
}

.public-demo-gate-enter-from,
.public-demo-gate-leave-to {
    opacity: 0;
    backdrop-filter: blur(0);
}

.public-demo-completion-prompt-enter-active,
.public-demo-completion-prompt-leave-active {
    transition:
        opacity 180ms ease,
        backdrop-filter 180ms ease;
}

.public-demo-completion-prompt-enter-active > div,
.public-demo-completion-prompt-leave-active > div {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.public-demo-completion-prompt-enter-from,
.public-demo-completion-prompt-leave-to {
    opacity: 0;
    backdrop-filter: blur(0);
}

.public-demo-completion-prompt-enter-from > div,
.public-demo-completion-prompt-leave-to > div {
    opacity: 0;
    transform: translateY(0.5rem) scale(0.98);
}

@media (max-width: 639px) {
    .topics-handle-trigger,
    .learning-handle-trigger {
        top: 5.8rem !important;
        padding: 0.38rem 0.22rem !important;
        font-size: 0.5rem !important;
        line-height: 1 !important;
        letter-spacing: 0.08em !important;
        writing-mode: vertical-rl !important;
        text-orientation: mixed !important;
    }

    .topics-handle-trigger {
        left: 0 !important;
        transform: rotate(180deg) !important;
        border-right-width: 0 !important;
        border-radius: 0.375rem 0 0 0.375rem !important;
    }

    .learning-handle-trigger {
        right: 0 !important;
        transform: none !important;
        border-right-width: 0 !important;
        border-radius: 0.375rem 0 0 0.375rem !important;
    }
}
</style>
