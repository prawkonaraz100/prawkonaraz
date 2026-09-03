<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DesktopLearningMap from '@/Pages/Session/Partials/DesktopLearningMap.vue';
import MobileLearningDashboard from '@/Pages/Session/Partials/MobileLearningDashboard.vue';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import learningCardClassicIcon from '../../../images/session/learning-card-classic.png';
import learningCardExamIcon from '../../../images/session/learning-card-exam.png';
import learningCardMemoryIcon from '../../../images/session/learning-card-memory.png';
import learningCardZenIcon from '../../../images/session/learning-card-zen.png';
import learningDashboardHero from '../../../images/session/learning-dashboard-hero.png';
import pjmSignLanguageSymbol from '../../../images/session/pjm-sign-language-symbol.png';

interface SessionCategory {
    id: number;
    code: string;
    name: string;
    short_name: string;
    questions_count: number;
}

interface GroupOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
    hero_image_url?: string | null;
    hero_image_alt?: string | null;
    hero_image_position?: string | null;
    hero_image_source?: string | null;
}

interface GroupBucket {
    label: string;
    options: GroupOption[];
}

interface StatusOption {
    value: string;
    label: string;
}

interface AccessState {
    full_product: {
        allowed: boolean;
        reason: string | null;
        activation_url: string;
    };
}

interface LearningOverview {
    due_review_count: number;
    incorrect_list_count: number;
}

interface IncorrectQuestionListState {
    enabled: boolean;
    read_mode: 'legacy' | 'shadow' | 'list';
    index_url: string;
    auto_remove_on_correct: boolean;
}

interface ActiveLearningSession {
    id: number;
    mode: string;
    ui_shell: StudyUiShell | null;
    status: string;
    title: string;
    subtitle: string | null;
    progress: {
        answered: number;
        remaining: number;
        total: number;
        percent: number;
        current_question_number: number | null;
    };
    resume_url: string;
    can_replace: boolean;
    replace_warning: string;
}

interface LearningDashboard {
    schema_version: number;
    active_session: ActiveLearningSession | null;
    course_progress: {
        category_id: number | null;
        answered_questions: number;
        unanswered_questions: number;
        incorrect_questions: number;
        incorrect_list_count: number;
        correct_questions: number;
        total_questions: number;
        percent: number;
    };
    review: {
        due_count: number;
    };
    recent_learning_activity: unknown | null;
    weekly_activity: unknown | null;
    study_time: unknown | null;
    access_ui: {
        can_show_pricing_link: boolean;
        purchase_mode: string;
        full_product_allowed: boolean;
        full_product_reason: string | null;
        pjm_allowed: boolean;
        pjm_reason: string | null;
    };
}

interface RankingPreview {
    position: number | null;
    rating: number | null;
    matches_played: number;
    message: string;
}

interface PjmCoverage {
    total_questions: number;
    pjm_questions: number;
    missing_questions: number;
    coverage_percent: number;
}

interface PjmModule {
    available: boolean;
    reason: string | null;
    preferred: boolean;
    show_entry_tile: boolean;
    starter_mode: boolean;
    href: string;
    coverage: PjmCoverage | null;
}

interface ProfessionalCourseModule {
    id: number;
    code: string;
    name: string;
    description: string | null;
    questions_count: number;
    progress: {
        answered_count: number;
        total_questions: number;
        percent: number;
    };
    start_url: string;
}

interface ProfessionalCourse {
    id: number;
    code: string;
    slug: string;
    name: string;
    description: string | null;
    category_name: string | null;
    progress: {
        answered_count: number;
        total_questions: number;
        percent: number;
    };
    incorrect_questions: {
        count: number;
        url: string;
    };
    modules: ProfessionalCourseModule[];
}

interface FriendInvitationCta {
    visible: boolean;
    can_issue: boolean;
    pending_count: number;
    pending_limit: number;
    profile_url: string;
    reason: string | null;
    reason_label: string | null;
}

interface StudyContextCategory {
    id: number;
    code: string;
    name: string;
    short_name: string;
}

type StudyPreset = 'learn' | 'zen' | 'exam';
type StudyUiShell = 'exam' | 'exam_like' | 'zen';
type QuestionScope = 'all' | 'basic' | 'specialist';
type LearningPath = 'pjm' | 'traffic-signs' | 'classic' | 'zen' | 'exam' | 'memory' | 'ranking';
type RecommendedStepAction = 'activate' | 'pjm' | 'traffic-signs' | 'memory' | 'classic' | 'exam';
type PendingStartAction = 'session' | 'global-incorrect';

interface RecommendedStep {
    action: RecommendedStepAction;
    eyebrow: string;
    title: string;
    description: string;
    metricLabel: string;
    metricValue: string;
    primaryLabel: string;
    href?: string;
    topic?: GroupOption;
    status?: string;
}

const EXAM_TOTAL_QUESTIONS = 32;

const props = defineProps<{
    category: SessionCategory | null;
    filters: {
        question_topic_id: number | null;
        ui_shell: StudyUiShell;
        question_scope: QuestionScope;
        question_status: string;
        randomize_order: boolean;
        question_count: number;
    };
    group_options: GroupBucket[];
    status_options: StatusOption[];
    access: AccessState;
    learning_overview: LearningOverview;
    incorrect_question_list: IncorrectQuestionListState;
    learning_dashboard: LearningDashboard;
    ranking_preview: RankingPreview;
    pjm_module: PjmModule;
    professional_courses: ProfessionalCourse[];
    initial_professional_course_code: string | null;
    friend_invitation_cta: FriendInvitationCta;
}>();

const page = usePage<PageProps>();
const sharedStudyContext = computed(() => (page.props.studyContext ?? {
    targetCategoryId: null,
    categories: [],
}) as {
    targetCategoryId: number | null;
    categories: StudyContextCategory[];
});
const availableCategories = computed(() => sharedStudyContext.value.categories ?? []);
const canUseFullProduct = computed(() => props.access.full_product.allowed);
const fullAccessUrl = computed(() => props.access.full_product.activation_url || route('access.activate'));
const canShowPricingLink = computed(() => props.learning_dashboard.access_ui.can_show_pricing_link);
const activeLearningSession = computed(() => props.learning_dashboard.active_session);
const showPjmEntryTile = computed(() => props.pjm_module.show_entry_tile);
const isPjmStarterMode = computed(() => props.pjm_module.starter_mode);
const friendInvitationCtaTitle = computed(() => {
    if (props.friend_invitation_cta.can_issue) {
        return 'Wolny dostęp dla znajomego';
    }

    if (props.friend_invitation_cta.pending_count > 0) {
        return 'Masz oczekujące zaproszenia';
    }

    return 'Zaproszenie będzie dostępne po zwolnieniu slotu';
});
const friendInvitationCtaDescription = computed(() => {
    if (props.friend_invitation_cta.can_issue) {
        return 'Możesz udostępnić jeden dostęp Premium do końca bieżącego okresu.';
    }

    if (props.friend_invitation_cta.pending_count > 0) {
        return `W panelu konta czeka ${props.friend_invitation_cta.pending_count} z ${props.friend_invitation_cta.pending_limit} linków. Możesz skopiować kod albo unieważnić stary link.`;
    }

    return props.friend_invitation_cta.reason_label ?? 'Szczegóły zaproszeń znajdziesz w ustawieniach konta.';
});
const friendInvitationCtaStorageKey = computed(() =>
    `prawkonaraz.friendInvitationCta.dismissed.${page.props.auth.user?.id ?? 'guest'}.v1`,
);
const friendInvitationCtaDismissed = ref(false);
const friendInvitationCtaVisible = computed(() =>
    props.friend_invitation_cta.visible && !friendInvitationCtaDismissed.value,
);

const loadFriendInvitationCtaPreference = () => {
    try {
        friendInvitationCtaDismissed.value =
            window.localStorage.getItem(friendInvitationCtaStorageKey.value) === '1';
    } catch {
        friendInvitationCtaDismissed.value = false;
    }
};

const dismissFriendInvitationCta = () => {
    friendInvitationCtaDismissed.value = true;

    try {
        window.localStorage.setItem(friendInvitationCtaStorageKey.value, '1');
    } catch {
        // The banner is already hidden for this page load.
    }
};

const sessionForm = useForm<{
    license_category_id: number | null;
    mode: 'learn' | 'quick' | 'exam';
    ui_shell: StudyUiShell;
    question_topic_id: number | null;
    question_scope: QuestionScope;
    question_status: string;
    randomize_order: boolean;
    question_count: number;
}>( {
    license_category_id: props.category?.id ?? null,
    mode: 'learn',
    ui_shell: props.filters.ui_shell,
    question_topic_id: props.filters.question_topic_id,
    question_scope: props.filters.question_scope,
    question_status: props.filters.question_status,
    randomize_order: props.filters.randomize_order,
    question_count: props.filters.question_count,
});

const initialLearningShell = props.filters.ui_shell === 'zen' ? 'zen' : 'exam_like';
const selectedPreset = ref<StudyPreset>(initialLearningShell === 'zen' ? 'zen' : 'learn');
const selectedPath = ref<LearningPath>(
    showPjmEntryTile.value
        ? 'pjm'
        : initialLearningShell === 'zen'
            ? 'zen'
            : 'classic',
);
const isExamPreset = computed(() => selectedPreset.value === 'exam');
const isClassicPath = computed(() => selectedPath.value === 'classic');
const isZenPath = computed(() => selectedPath.value === 'zen');
const isExamPath = computed(() => selectedPath.value === 'exam');
const isPjmPath = computed(() => selectedPath.value === 'pjm');
const isTrafficSignsPath = computed(() => selectedPath.value === 'traffic-signs');
const isMemoryPath = computed(() => selectedPath.value === 'memory');
const isRankingPath = computed(() => selectedPath.value === 'ranking');
watch(
    isPjmStarterMode,
    (starterMode) => {
        if (starterMode) {
            selectedPath.value = 'pjm';
            selectedPreset.value = 'learn';
            return;
        }

        if (selectedPath.value === 'pjm' && !showPjmEntryTile.value) {
            selectedPath.value = 'classic';
        }
    },
    { immediate: true },
);
const examRules = [
    'Pełna kategoria bez wyboru działów.',
    '32 pytania: 20 podstawowych i 12 specjalistycznych.',
    'Łączny czas egzaminu: 25 minut.',
    'Brak losowania kolejności i filtrowania pytań.',
];
const examFacts = [
    { label: 'Zakres', value: 'Pełna kategoria' },
    { label: 'Liczba pytań', value: '32' },
    { label: 'Struktura', value: '20 + 12' },
    { label: 'Czas', value: '25 min' },
];
const learningPathTitle = computed(() => {
    if (isPjmPath.value) {
        return 'Moduł PJM';
    }

    if (isTrafficSignsPath.value) {
        return 'Znaki drogowe';
    }

    if (isZenPath.value) {
        return 'Zen mode';
    }

    if (isExamPath.value) {
        return 'Egzamin';
    }

    if (isMemoryPath.value) {
        return 'Trener pamięci';
    }

    if (isRankingPath.value) {
        return 'Ranking';
    }

    return 'Nauka klasyczna';
});
const learningPathLead = computed(() => {
    if (isPjmPath.value) {
        return 'Darmowa ścieżka z tłumaczeniem PJM dla Twojej kategorii.';
    }

    if (isTrafficSignsPath.value) {
        return 'Pierwszy krok przed pytaniami egzaminacyjnymi: rozpoznawanie znaków bez przeciążania teorii.';
    }

    if (isZenPath.value) {
        return 'Ten sam materiał, spokojniejszy ekran i mniej rozpraszaczy.';
    }

    if (isExamPath.value) {
        return 'Symulacja warunków egzaminu państwowego bez konfiguracji działów.';
    }

    if (isMemoryPath.value) {
        return 'Powrót do pytań, które wymagają utrwalenia zanim dołożysz kolejne działy.';
    }

    if (isRankingPath.value) {
        return 'Szybki pojedynek 1 na 1, gdy chcesz sprawdzić tempo i pewność odpowiedzi.';
    }

    return 'Najlepszy tryb do codziennego przerabiania działów i utrwalania pytań.';
});
const questionScopeOptions: Array<{
    value: QuestionScope;
    label: string;
    compactLabel: string;
}> = [
    { value: 'all', label: 'Wszystkie', compactLabel: 'Wszystkie' },
    { value: 'basic', label: 'Podstawowe', compactLabel: 'Podst.' },
    { value: 'specialist', label: 'Specjalistyczne', compactLabel: 'Specj.' },
];
const mobileClassicStatusOptions = computed(() =>
    props.status_options.filter((option) => ['all', 'incorrect'].includes(option.value)),
);

const filteredGroupOptions = computed(() => {
    if (sessionForm.question_scope === 'basic') {
        return props.group_options.filter((group) => group.label === 'Pytania podstawowe');
    }

    if (sessionForm.question_scope === 'specialist') {
        return props.group_options.filter((group) => group.label === 'Pytania specjalistyczne');
    }

    return props.group_options;
});

const topicQuestionCounts = computed(() =>
    filteredGroupOptions.value.flatMap((group) => group.options),
);
const firstTopicId = computed(() => topicQuestionCounts.value[0]?.id ?? null);
const selectedTopic = computed(() =>
    topicQuestionCounts.value.find((option) => option.id === sessionForm.question_topic_id) ?? null,
);
const selectedTopicIndex = computed(() =>
    topicQuestionCounts.value.findIndex((option) => option.id === sessionForm.question_topic_id),
);
const topicDisplayLabel = (topic: GroupOption | null) => {
    if (! topic) {
        return 'Wybierz dział';
    }

    const index = topicQuestionCounts.value.findIndex((option) => option.id === topic.id);

    return index >= 0 ? `${index + 1}. ${topic.label}` : topic.label;
};
const nextTopic = computed(() => {
    if (topicQuestionCounts.value.length <= 1) {
        return null;
    }

    if (selectedTopicIndex.value < 0) {
        return topicQuestionCounts.value[0] ?? null;
    }

    return topicQuestionCounts.value[selectedTopicIndex.value + 1] ?? null;
});
const allTopicOptions = computed(() =>
    props.group_options.flatMap((group) => group.options),
);
const managedIncorrectListEnabled = computed(() =>
    props.incorrect_question_list.enabled
    && props.incorrect_question_list.read_mode === 'list',
);
const totalAnsweredQuestions = computed(() =>
    allTopicOptions.value.reduce((sum, option) => sum + answeredCountForOption(option), 0),
);
const totalUnansweredQuestions = computed(() =>
    allTopicOptions.value.reduce((sum, option) => sum + (option.counts.unanswered ?? 0), 0),
);
const totalIncorrectQuestions = computed(() =>
    managedIncorrectListEnabled.value
        ? props.learning_overview.incorrect_list_count
        : allTopicOptions.value.reduce((sum, option) => sum + (option.counts.incorrect ?? 0), 0),
);
const hasGlobalIncorrectQuestions = computed(() => totalIncorrectQuestions.value > 0);
const firstUnansweredTopic = computed(() =>
    allTopicOptions.value.find((option) => (option.counts.unanswered ?? 0) > 0) ?? null,
);
const firstIncorrectTopic = computed(() =>
    allTopicOptions.value.find((option) => (option.counts.incorrect ?? 0) > 0) ?? null,
);
const mobileRecommendedTopic = computed(() =>
    firstUnansweredTopic.value
    ?? firstIncorrectTopic.value
    ?? allTopicOptions.value[0]
    ?? null,
);
const globalIncorrectForm = useForm<{
    license_category_id: number | null;
    mode: 'learn';
    ui_shell: StudyUiShell;
    question_topic_id: number | null;
    question_scope: QuestionScope;
    question_status: 'incorrect' | 'mistake_list';
    randomize_order: boolean;
    question_count: number;
}>({
    license_category_id: props.category?.id ?? null,
    mode: 'learn',
    ui_shell: 'exam_like',
    question_topic_id: null,
    question_scope: 'all',
    question_status: managedIncorrectListEnabled.value ? 'mistake_list' : 'incorrect',
    randomize_order: false,
    question_count: Math.max(totalIncorrectQuestions.value, 1),
});

const selectedQuestionCount = computed(() => {
    if (selectedPreset.value === 'exam') {
        return EXAM_TOTAL_QUESTIONS;
    }

    const selectedOption = selectedTopic.value;

    if (! selectedOption) {
        return 1;
    }

    return selectedOption.counts[sessionForm.question_status] ?? selectedOption.questions_count ?? 1;
});
const canStartLearning = computed(() =>
    Boolean(
        sessionForm.license_category_id
        && (
            selectedPreset.value === 'exam'
            || (sessionForm.question_topic_id && selectedQuestionCount.value > 0)
        ),
    ),
);
const topicDropdownOpen = ref(false);
const replaceActiveSessionDialogOpen = ref(false);
const pendingStartAction = ref<PendingStartAction | null>(null);
const categoryForm = useForm<{
    target_category_id: number | null;
    return_to: string;
}>({
    target_category_id: sharedStudyContext.value.targetCategoryId ?? props.category?.id ?? null,
    return_to: '',
});

const closeDropdowns = () => {
    topicDropdownOpen.value = false;
};

const isInsideTopicDropdown = (target: Node | null) => {
    if (! target) {
        return false;
    }

    const element = target instanceof Element ? target : target.parentElement;

    return Boolean(element?.closest('[data-topic-dropdown-root]'));
};

const handleDocumentPointerDown = (event: PointerEvent) => {
    const target = event.target as Node | null;

    if (isInsideTopicDropdown(target)) {
        return;
    }

    closeDropdowns();
};

const handleDocumentKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeDropdowns();
    }
};

onMounted(() => {
    loadFriendInvitationCtaPreference();
    document.addEventListener('pointerdown', handleDocumentPointerDown);
    document.addEventListener('keydown', handleDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', handleDocumentPointerDown);
    document.removeEventListener('keydown', handleDocumentKeydown);
});

watch(
    () => sharedStudyContext.value.targetCategoryId ?? props.category?.id ?? null,
    (newCategoryId) => {
        categoryForm.target_category_id = newCategoryId;
    },
    { immediate: true },
);

const defaultVisibleTopicId = () => {
    const preferredTopicId = props.filters.question_topic_id;

    return topicQuestionCounts.value.some((option) => option.id === preferredTopicId)
        ? preferredTopicId
        : firstTopicId.value;
};

watch(
    () => props.category?.id ?? null,
    (categoryId) => {
        sessionForm.license_category_id = categoryId;

        const selectedTopicStillVisible = topicQuestionCounts.value.some(
            (option) => option.id === sessionForm.question_topic_id,
        );

        if (sessionForm.question_topic_id === null || !selectedTopicStillVisible) {
            sessionForm.question_topic_id = defaultVisibleTopicId();
        }
    },
    { immediate: true },
);

watch(
    firstTopicId,
    (topicId) => {
        const selectedTopicStillVisible = topicQuestionCounts.value.some(
            (option) => option.id === sessionForm.question_topic_id,
        );

        if (sessionForm.question_topic_id === null || !selectedTopicStillVisible) {
            sessionForm.question_topic_id = defaultVisibleTopicId() ?? topicId;
        }
    },
    { immediate: true },
);

watch(
    selectedQuestionCount,
    (questionCount) => {
        sessionForm.question_count = questionCount;
    },
    { immediate: true },
);

watch(
    totalIncorrectQuestions,
    (questionCount) => {
        globalIncorrectForm.question_count = Math.max(questionCount, 1);
    },
    { immediate: true },
);

const openReplaceActiveSessionDialog = (action: PendingStartAction) => {
    pendingStartAction.value = action;
    replaceActiveSessionDialogOpen.value = true;
};

const closeReplaceActiveSessionDialog = () => {
    replaceActiveSessionDialogOpen.value = false;
    pendingStartAction.value = null;
};

const submitSessionForm = () => {
    sessionForm.post(route('study-sessions.store'));
};

const submitGlobalIncorrectForm = () => {
    globalIncorrectForm.question_topic_id = null;
    globalIncorrectForm.question_scope = 'all';
    globalIncorrectForm.question_status = managedIncorrectListEnabled.value ? 'mistake_list' : 'incorrect';
    globalIncorrectForm.randomize_order = false;
    globalIncorrectForm.question_count = totalIncorrectQuestions.value;
    globalIncorrectForm.post(route('study-sessions.store'));
};

const confirmReplaceActiveSession = () => {
    const action = pendingStartAction.value;

    closeReplaceActiveSessionDialog();

    if (action === 'global-incorrect') {
        submitGlobalIncorrectForm();
        return;
    }

    if (action === 'session') {
        submitSessionForm();
    }
};

const startLearning = () => {
    if (!canUseFullProduct.value) {
        activateFullLearning();
        return;
    }

    if (!sessionForm.license_category_id) {
        return;
    }

    if (activeLearningSession.value) {
        openReplaceActiveSessionDialog('session');
        return;
    }

    submitSessionForm();
};

const startGlobalIncorrectLearning = () => {
    if (!canUseFullProduct.value) {
        activateFullLearning();
        return;
    }

    if (!globalIncorrectForm.license_category_id || totalIncorrectQuestions.value <= 0) {
        return;
    }

    if (activeLearningSession.value) {
        openReplaceActiveSessionDialog('global-incorrect');
        return;
    }

    submitGlobalIncorrectForm();
};

const activateFullLearning = () => {
    if (!canShowPricingLink.value) {
        return;
    }

    window.location.href = fullAccessUrl.value;
};

const toggleTopicDropdown = () => {
    if (isPjmStarterMode.value) {
        return;
    }

    if (filteredGroupOptions.value.length === 0) {
        return;
    }

    topicDropdownOpen.value = !topicDropdownOpen.value;
};

const chooseTopic = (topicId: number) => {
    if (isPjmStarterMode.value) {
        return;
    }

    sessionForm.question_topic_id = topicId;
    topicDropdownOpen.value = false;
};

const chooseNextTopic = () => {
    if (isPjmStarterMode.value || ! nextTopic.value) {
        return;
    }

    chooseTopic(nextTopic.value.id);
};

const chooseTopicFromSelect = (event: Event) => {
    const topicId = Number((event.target as HTMLSelectElement).value);

    if (Number.isNaN(topicId)) {
        return;
    }

    chooseTopic(topicId);
};

const chooseStatus = (status: string) => {
    if (isPjmStarterMode.value) {
        return;
    }

    sessionForm.question_status = status;
};

const chooseQuestionScope = (scope: QuestionScope) => {
    if (isPjmStarterMode.value) {
        return;
    }

    sessionForm.question_scope = scope;
    topicDropdownOpen.value = false;
};

const currentPathWithoutCategory = () => {
    if (typeof window === 'undefined') {
        return '/nauka';
    }

    const url = new URL(window.location.href);
    url.searchParams.delete('category');

    const search = url.searchParams.toString();

    return `${url.pathname}${search ? `?${search}` : ''}${url.hash}`;
};

const selectCategory = (id: number) => {
    if (isPjmStarterMode.value) {
        return;
    }

    if (categoryForm.processing || categoryForm.target_category_id === id) {
        return;
    }

    categoryForm.target_category_id = id;
    categoryForm.return_to = currentPathWithoutCategory();
    categoryForm.patch(route('profile.product.update'), {
        preserveScroll: true,
        preserveState: false,
    });
};

const answeredCountForOption = (option: GroupOption) =>
    Math.max(option.counts.all - option.counts.unanswered, 0);

const answeredProgressPercentForOption = (option: GroupOption) =>
    option.questions_count > 0
        ? Math.min((answeredCountForOption(option) / option.questions_count) * 100, 100)
        : 0;

const previewTopic = (topicId: number) => {
    chooseTopic(topicId);
    chooseStatus('all');
};

const applyStudyPreset = (preset: StudyPreset) => {
    if (isPjmStarterMode.value) {
        return;
    }

    selectedPreset.value = preset;
    topicDropdownOpen.value = false;

    if (preset === 'learn') {
        sessionForm.mode = 'learn';
        sessionForm.ui_shell = 'exam_like';

        return;
    }

    if (preset === 'zen') {
        sessionForm.mode = 'learn';
        sessionForm.ui_shell = 'zen';
        sessionForm.question_status = 'all';
        sessionForm.randomize_order = false;

        return;
    }

    sessionForm.mode = 'exam';
    sessionForm.ui_shell = 'exam';
    sessionForm.question_scope = 'all';
    sessionForm.question_status = 'all';
    sessionForm.randomize_order = false;
};

const selectLearningPath = (path: LearningPath) => {
    if (isPjmStarterMode.value && path !== 'pjm') {
        return;
    }

    selectedPath.value = path;

    if (path === 'classic') {
        applyStudyPreset('learn');
        return;
    }

    if (path === 'zen') {
        applyStudyPreset('zen');
        return;
    }

    if (path === 'exam') {
        applyStudyPreset('exam');
    }
};

const startMobileRecommendedLearning = () => {
    const topic = mobileRecommendedTopic.value;

    if (!topic) {
        return;
    }

    const status = (topic.counts.unanswered ?? 0) > 0
        ? 'unanswered'
        : (topic.counts.incorrect ?? 0) > 0
            ? 'incorrect'
            : 'all';

    selectLearningPath('classic');
    sessionForm.question_topic_id = topic.id;
    sessionForm.question_scope = 'all';
    sessionForm.question_status = status;
    sessionForm.randomize_order = false;
    sessionForm.question_count = topic.counts[status] ?? topic.questions_count;
    startLearning();
};

const startButtonLabel = computed(() =>
    !canUseFullProduct.value
        ? 'Aktywuj pełną naukę'
        : selectedPreset.value === 'exam'
            ? 'Rozpocznij egzamin'
            : 'Rozpocznij naukę',
);

const rankingModeHref = computed(() => {
    if (!canUseFullProduct.value) {
        return fullAccessUrl.value;
    }

    const categoryId = sessionForm.license_category_id ?? props.category?.id ?? null;

    return categoryId
        ? route('session.ranking', { category: categoryId })
        : route('session.ranking');
});
const trafficSignLearningHref = computed(() => {
    if (!canUseFullProduct.value) {
        return fullAccessUrl.value;
    }

    return route('session.traffic-signs');
});
const memoryTrainerHref = computed(() =>
    canUseFullProduct.value ? '/trener-pamieci' : fullAccessUrl.value,
);
const recommendedHeroStyle = computed(() => ({
    backgroundImage: `url(${learningDashboardHero})`,
}));
const categoryStatsHref = computed(() =>
    props.category ? route('analytics.categories.show', props.category.id) : '#',
);
const desktopHeroTopic = computed(() =>
    selectedTopic.value
    ?? firstUnansweredTopic.value
    ?? allTopicOptions.value[0]
    ?? null,
);
const desktopHeroImageSrc = computed(() =>
    desktopHeroTopic.value?.hero_image_url || learningDashboardHero,
);
const desktopHeroImageStyle = computed(() => ({
    objectPosition: desktopHeroTopic.value?.hero_image_position || 'center',
}));
const desktopHeroAnsweredCount = computed(() =>
    desktopHeroTopic.value ? answeredCountForOption(desktopHeroTopic.value) : 0,
);
const desktopHeroRemainingCount = computed(() =>
    desktopHeroTopic.value ? Math.max(desktopHeroTopic.value.questions_count - desktopHeroAnsweredCount.value, 0) : 0,
);
const desktopHeroProgressPercent = computed(() => {
    const topic = desktopHeroTopic.value;

    if (!topic || topic.questions_count <= 0) {
        return 0;
    }

    return Math.min(Math.round((desktopHeroAnsweredCount.value / topic.questions_count) * 100), 100);
});
const desktopHeroProgressWidth = computed(() =>
    `${desktopHeroProgressPercent.value > 0 ? Math.max(desktopHeroProgressPercent.value, 4) : 0}%`,
);
const desktopHeroSecondaryText = computed(() =>
    desktopHeroRemainingCount.value > 0
        ? `${desktopHeroRemainingCount.value} pytań do końca`
        : 'Dział przerobiony',
);
const courseQuestionTotal = computed(() => totalAnsweredQuestions.value + totalUnansweredQuestions.value);
const courseProgressPercent = computed(() => {
    if (courseQuestionTotal.value <= 0) {
        return 0;
    }

    return Math.min(Math.round((totalAnsweredQuestions.value / courseQuestionTotal.value) * 100), 100);
});
const courseProgressStyle = computed(() => ({
    background: `conic-gradient(#e11d2e 0deg ${courseProgressPercent.value * 3.6}deg, #eef2f7 ${courseProgressPercent.value * 3.6}deg 360deg)`,
}));
const courseCorrectCount = computed(() =>
    Math.max(totalAnsweredQuestions.value - totalIncorrectQuestions.value, 0),
);
const desktopStatusOptions = computed(() =>
    props.status_options.map((option) => {
        const count = selectedTopic.value
            ? (option.value === 'all'
                ? selectedTopic.value.questions_count
                : selectedTopic.value.counts[option.value] ?? 0)
            : 0;

        return {
            ...option,
            count,
        };
    }),
);
const learningPathTabs = computed<Array<{
    value: LearningPath;
    label: string;
    summary: string;
    disabled: boolean;
}>>(() => {
    const locked = isPjmStarterMode.value;
    const tabs: Array<{
        value: LearningPath;
        label: string;
        summary: string;
        disabled: boolean;
    }> = [];

    if (showPjmEntryTile.value) {
        tabs.push({
            value: 'pjm',
            label: 'PJM',
            summary: 'Tłumaczenia',
            disabled: false,
        });
    }

    tabs.push(
        {
            value: 'classic',
            label: 'Nauka klasyczna',
            summary: 'Działy i statusy',
            disabled: locked,
        },
        {
            value: 'zen',
            label: 'Zen mode',
            summary: 'Pełny focus',
            disabled: locked,
        },
        {
            value: 'memory',
            label: 'Trener pamięci',
            summary: `${props.learning_overview.due_review_count} do powtórki`,
            disabled: locked,
        },
        {
            value: 'exam',
            label: 'Egzamin',
            summary: '32 pytania',
            disabled: locked,
        },
    );

    return tabs;
});
const desktopLearningCardIcons: Partial<Record<LearningPath, string>> = {
    classic: learningCardClassicIcon,
    zen: learningCardZenIcon,
    memory: learningCardMemoryIcon,
    exam: learningCardExamIcon,
};

function desktopPathButtonLabel(path: LearningPath): string {
    if (path === 'zen') {
        return 'Start Zen';
    }

    if (path === 'memory') {
        return 'Otwórz trenera';
    }

    if (path === 'exam') {
        return 'Start egzaminu';
    }

    if (path === 'pjm') {
        return 'Otwórz PJM';
    }

    return 'Start nauki';
}

function desktopPathSummary(path: LearningPath, fallback: string): string {
    if (path === 'classic') {
        return 'Działy i statusy';
    }

    if (path === 'zen') {
        return 'Pełny focus';
    }

    if (path === 'memory') {
        return `${props.learning_overview.due_review_count} do powtórki`;
    }

    if (path === 'exam') {
        return '32 pytania';
    }

    return fallback;
}

const desktopLearningCards = computed(() =>
    learningPathTabs.value.map((tab) => ({
        ...tab,
        buttonLabel: desktopPathButtonLabel(tab.value),
        summary: desktopPathSummary(tab.value, tab.summary),
        icon: desktopLearningCardIcons[tab.value] ?? null,
        badge: tab.value === 'memory' && props.learning_overview.due_review_count > 0
            ? String(props.learning_overview.due_review_count)
            : null,
    })),
);
const desktopPrimaryActionValues: LearningPath[] = ['classic', 'zen', 'memory', 'exam'];
const desktopSidebarValues: LearningPath[] = ['classic', 'zen', 'memory', 'exam'];
const desktopQuickActionCards = computed(() =>
    desktopPrimaryActionValues
        .map((value) => desktopLearningCards.value.find((card) => card.value === value) ?? null)
        .filter((card): card is NonNullable<typeof card> => card !== null),
);
const desktopSidebarCards = computed(() =>
    desktopSidebarValues
        .map((value) => desktopLearningCards.value.find((card) => card.value === value) ?? null)
        .filter((card): card is NonNullable<typeof card> => card !== null),
);
const recommendedStep = computed<RecommendedStep>(() => {
    if (isPjmStarterMode.value && showPjmEntryTile.value) {
        return {
            action: 'pjm',
            eyebrow: 'Następny krok',
            title: 'Kontynuuj moduł PJM',
            description: 'To jest aktywna ścieżka dla tego konta. Klasyczne tryby zostają dostępne po aktywacji pełnej nauki.',
            metricLabel: 'Tryb',
            metricValue: 'PJM',
            primaryLabel: 'Otwórz PJM',
            href: props.pjm_module.href,
        };
    }

    if (!canUseFullProduct.value) {
        return {
            action: 'activate',
            eyebrow: 'Następny krok',
            title: 'Aktywuj pełną naukę',
            description: 'Pełny dostęp odblokuje klasyczną naukę, Zen mode, egzaminy, znaki, trenera pamięci i ranking.',
            metricLabel: 'Dostęp',
            metricValue: 'Zablokowany',
            primaryLabel: 'Aktywuj pełną naukę',
        };
    }

    if (totalAnsweredQuestions.value === 0) {
        return {
            action: 'classic',
            eyebrow: 'Rekomendowany start',
            title: 'Zacznij od pierwszego działu',
            description: 'Wybierz dział i przejdź przez pierwszą krótką serię pytań w swojej kategorii.',
            metricLabel: 'Postęp pytań',
            metricValue: '0 przerobionych',
            primaryLabel: 'Wybierz dział',
            topic: firstUnansweredTopic.value ?? undefined,
            status: 'unanswered',
        };
    }

    if (props.learning_overview.due_review_count > 0) {
        return {
            action: 'memory',
            eyebrow: 'Następny krok',
            title: 'Wróć do trenera pamięci',
            description: 'Masz materiał, który warto odzyskać zanim dołożysz kolejne pytania.',
            metricLabel: 'Do powtórki',
            metricValue: String(props.learning_overview.due_review_count),
            primaryLabel: 'Otwórz trenera',
            href: '/trener-pamieci',
        };
    }

    if (firstUnansweredTopic.value) {
        return {
            action: 'classic',
            eyebrow: 'Kontynuuj naukę',
            title: firstUnansweredTopic.value.label,
            description: 'Przerób kolejną partię nieprzerobionych pytań w aktualnej kategorii.',
            metricLabel: 'Zostało',
            metricValue: String(firstUnansweredTopic.value.counts.unanswered ?? 0),
            primaryLabel: 'Kontynuuj dział',
            topic: firstUnansweredTopic.value,
            status: 'unanswered',
        };
    }

    if (firstIncorrectTopic.value) {
        return {
            action: 'classic',
            eyebrow: 'Do poprawy',
            title: 'Powtórz błędy ze wszystkich działów',
            description: 'Najlepiej domknąć błędne odpowiedzi z całej kategorii zanim przejdziesz do egzaminu próbnego.',
            metricLabel: 'Błędne',
            metricValue: String(totalIncorrectQuestions.value),
            primaryLabel: 'Powtórz błędy',
            status: 'incorrect',
        };
    }

    return {
        action: 'exam',
        eyebrow: 'Następny krok',
        title: 'Sprawdź gotowość w egzaminie',
        description: 'Najważniejsze działy są przerobione. Teraz warto sprawdzić wynik w warunkach egzaminacyjnych.',
        metricLabel: 'Tryb',
        metricValue: '32 pytania',
        primaryLabel: 'Rozpocznij egzamin',
    };
});

const isRecommendedStepLink = computed(() =>
    ['pjm', 'memory'].includes(recommendedStep.value.action),
);

const runRecommendedStep = () => {
    const step = recommendedStep.value;

    if (step.action === 'activate') {
        activateFullLearning();
        return;
    }

    if (step.action === 'classic' && step.status === 'incorrect' && !step.topic) {
        selectLearningPath('classic');
        startGlobalIncorrectLearning();
        return;
    }

    if (step.action === 'classic' && step.topic) {
        selectLearningPath('classic');
        sessionForm.question_topic_id = step.topic.id;
        sessionForm.question_status = step.status ?? 'unanswered';
        sessionForm.question_scope = 'all';
        sessionForm.randomize_order = false;
        sessionForm.question_count = step.topic.counts[sessionForm.question_status] ?? step.topic.questions_count;
        startLearning();
        return;
    }

    if (step.action === 'exam') {
        selectLearningPath('exam');
        sessionForm.question_count = EXAM_TOTAL_QUESTIONS;
        startLearning();
    }
};

const startDesktopHeroLearning = () => {
    const topic = desktopHeroTopic.value;

    if (!topic) {
        runRecommendedStep();
        return;
    }

    selectLearningPath(isZenPath.value ? 'zen' : 'classic');
    sessionForm.question_topic_id = topic.id;
    sessionForm.question_scope = 'all';
    sessionForm.question_status = desktopHeroRemainingCount.value > 0 ? 'unanswered' : 'all';
    sessionForm.randomize_order = false;
    sessionForm.question_count = topic.counts[sessionForm.question_status] ?? topic.questions_count;
    startLearning();
};

</script>

<template>
    <Head title="Panel nauki" />

    <AuthenticatedLayout>
        <div class="min-h-[calc(100vh-4rem)] bg-white">
            <section
                v-if="category"
                class="pb-3 pt-0 sm:px-0 sm:pb-0 sm:pt-0"
            >
                <div class="w-full md:space-y-4">
                    <section
                        class="hidden"
                        :style="recommendedHeroStyle"
                    >
                        <div
                            aria-hidden="true"
                            class="pointer-events-none absolute -right-12 top-0 h-full w-[34rem] bg-[radial-gradient(circle_at_55%_35%,rgba(255,255,255,0.18),rgba(255,255,255,0)_58%)]"
                        />

                        <div class="relative grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.34fr)] lg:items-center">
                            <div class="max-w-3xl">
                                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-white/82">
                                {{ recommendedStep.eyebrow }}
                                </p>
                                <h2 class="mt-2 max-w-[42rem] text-[1.8rem] font-semibold leading-tight tracking-[-0.035em] text-white sm:text-[2.25rem]">
                                    {{ recommendedStep.title }}
                                </h2>
                                <p class="mt-3 max-w-[42rem] text-sm leading-6 text-white/86 sm:text-base">
                                    {{ recommendedStep.description }}
                                </p>
                            </div>

                            <div class="border border-white/82 bg-[#064f9e]/28 p-4 backdrop-blur-[1px]">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-white/76">
                                            {{ recommendedStep.metricLabel }}
                                        </p>
                                        <p class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-white">
                                            {{ recommendedStep.metricValue }}
                                        </p>
                                    </div>
                                    <span aria-hidden="true" class="text-3xl font-light leading-none text-white/78">
                                        ›
                                    </span>
                                </div>

                                <Link
                                    v-if="isRecommendedStepLink"
                                    :href="recommendedStep.href ?? '/'"
                                    class="mt-5 inline-flex min-h-[3.15rem] w-full items-center justify-center bg-white px-5 text-sm font-semibold text-[#064f9e] transition hover:bg-[#eef6ff] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                                >
                                    {{ recommendedStep.primaryLabel }}
                                </Link>
                                <button
                                    v-else
                                    type="button"
                                    class="mt-5 inline-flex min-h-[3.15rem] w-full items-center justify-center bg-white px-5 text-sm font-semibold text-[#064f9e] transition hover:bg-[#eef6ff] disabled:cursor-not-allowed disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                                    :disabled="sessionForm.processing"
                                    @click="runRecommendedStep"
                                >
                                    {{ sessionForm.processing ? 'Uruchamianie...' : recommendedStep.primaryLabel }}
                                </button>
                            </div>
                        </div>
                    </section>

                    <MobileLearningDashboard
                        :learning-path-tabs="learningPathTabs"
                        :selected-path="selectedPath"
                        :recommended-step="recommendedStep"
                        :is-recommended-step-link="isRecommendedStepLink"
                        :recommended-hero-style="recommendedHeroStyle"
                        :learning-dashboard="learning_dashboard"
                        :category-short-name="category.short_name"
                        :recommended-topic="mobileRecommendedTopic"
                        :can-use-full-product="canUseFullProduct"
                        :show-pjm-entry-tile="showPjmEntryTile"
                        :pjm-module-href="pjm_module.href"
                        :pjm-symbol-src="pjmSignLanguageSymbol"
                        :selected-question-count="selectedQuestionCount"
                        :filtered-group-options="filteredGroupOptions"
                        :mobile-classic-status-options="mobileClassicStatusOptions"
                        :session-question-topic-id="sessionForm.question_topic_id"
                        :session-question-status="sessionForm.question_status"
                        :session-processing="sessionForm.processing"
                        :global-incorrect-processing="globalIncorrectForm.processing"
                        :can-start-learning="canStartLearning"
                        :start-button-label="startButtonLabel"
                        :total-answered-questions="totalAnsweredQuestions"
                        :total-unanswered-questions="totalUnansweredQuestions"
                        :total-incorrect-questions="totalIncorrectQuestions"
                        :has-global-incorrect-questions="hasGlobalIncorrectQuestions"
                        :managed-incorrect-list-enabled="managedIncorrectListEnabled"
                        :incorrect-questions-url="incorrect_question_list.index_url"
                        :exam-facts="examFacts"
                        :traffic-sign-learning-href="trafficSignLearningHref"
                        :category-stats-href="categoryStatsHref"
                        :ranking-mode-href="rankingModeHref"
                        :ranking-preview="ranking_preview"
                        :errors="{
                            license_category_id: sessionForm.errors.license_category_id,
                            question_topic_id: sessionForm.errors.question_topic_id,
                            question_status: sessionForm.errors.question_status,
                        }"
                        @select-path="selectLearningPath"
                        @start-recommended-learning="startMobileRecommendedLearning"
                        @run-recommended-step="runRecommendedStep"
                        @activate-full-learning="activateFullLearning"
                        @start-learning="startLearning"
                        @choose-topic="chooseTopic"
                        @choose-status="chooseStatus"
                        @start-global-incorrect-learning="startGlobalIncorrectLearning"
                    />

                    <div
                        v-if="replaceActiveSessionDialogOpen && activeLearningSession"
                        class="fixed inset-0 z-50 flex items-end justify-center bg-[#020617]/62 px-4 pb-4 pt-10 backdrop-blur-[1px] md:items-center md:pb-10"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="replace-active-session-title"
                    >
                        <button
                            type="button"
                            class="absolute inset-0"
                            aria-label="Anuluj rozpoczęcie nowej sesji"
                            @click="closeReplaceActiveSessionDialog"
                        />

                        <section class="relative w-full max-w-md rounded-[1rem] bg-white p-4 shadow-[0_24px_80px_rgba(15,23,42,0.28)]">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-[#0b5cff]">
                                Aktywna sesja
                            </p>
                            <h2 id="replace-active-session-title" class="mt-1 text-xl font-semibold leading-tight tracking-[0] text-[#050b2e]">
                                Masz aktywną sesję
                            </h2>
                            <p class="mt-2 text-sm leading-5 text-[#475569]">
                                Rozpoczęcie nowej sesji zakończy obecną. Możesz też wrócić do aktualnej nauki.
                            </p>

                            <div class="mt-3 rounded-[0.8rem] border border-[#e2e8f0] bg-[#f8fbff] px-3 py-2.5">
                                <p class="text-sm font-semibold text-[#050b2e]">
                                    {{ activeLearningSession.title }}
                                </p>
                                <p class="mt-0.5 text-xs text-[#475569]">
                                    {{ activeLearningSession.subtitle }} · {{ activeLearningSession.progress.answered }}/{{ activeLearningSession.progress.total }} pytań
                                </p>
                            </div>

                            <div class="mt-4 grid gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[3rem] w-full items-center justify-center rounded-[0.75rem] bg-[#0b5cff] px-4 text-sm font-semibold text-white transition hover:bg-[#023ea4] disabled:cursor-not-allowed disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                                    :disabled="sessionForm.processing || globalIncorrectForm.processing"
                                    @click="confirmReplaceActiveSession"
                                >
                                    Rozpocznij nową
                                </button>
                                <Link
                                    :href="activeLearningSession.resume_url"
                                    class="inline-flex min-h-[3rem] w-full items-center justify-center rounded-[0.75rem] border border-[#cbd5e1] bg-white px-4 text-sm font-semibold text-[#0f172a] transition hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                                >
                                    Wróć do sesji
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-[0.75rem] px-4 text-sm font-semibold text-[#475569] transition hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                                    @click="closeReplaceActiveSessionDialog"
                                >
                                    Anuluj
                                </button>
                            </div>
                        </section>
                    </div>

                    <section
                        v-if="friendInvitationCtaVisible"
                        class="border-b border-[#e5eaf3] bg-[#fbfcff] px-4 py-3 md:px-6 xl:px-8"
                        aria-label="Panel zaproszenia znajomego"
                    >
                        <div class="mx-auto flex max-w-[90rem] flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="min-w-0 md:flex md:items-center md:gap-4">
                                <p class="shrink-0 text-[0.72rem] font-bold uppercase tracking-[0] text-[#e11d2e]">
                                    Zaproszenie
                                </p>
                                <div class="min-w-0 md:flex md:items-center md:gap-3">
                                    <h2 class="mt-1 text-[0.95rem] font-bold leading-tight tracking-[0] text-[#10172f] md:mt-0">
                                        {{ friendInvitationCtaTitle }}
                                    </h2>
                                    <p class="mt-1 max-w-[48rem] text-[0.84rem] font-medium leading-5 text-[#516071] md:mt-0">
                                        {{ friendInvitationCtaDescription }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <Link
                                    :href="friend_invitation_cta.profile_url"
                                    class="inline-flex min-h-10 items-center justify-center rounded-[6px] border border-[#e11d2e] bg-white px-3.5 text-[0.83rem] font-bold text-[#e11d2e] transition hover:bg-[#fff5f6] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2"
                                >
                                    {{ friend_invitation_cta.can_issue ? 'Przejdź do zaproszeń' : 'Zarządzaj zaproszeniami' }}
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex min-h-10 items-center justify-center rounded-[6px] border border-transparent px-3 text-[0.83rem] font-bold text-[#516071] transition hover:bg-white hover:text-[#10172f] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10172f] focus-visible:ring-offset-2"
                                    @click="dismissFriendInvitationCta"
                                >
                                    Ukryj
                                </button>
                            </div>
                        </div>
                    </section>

                    <DesktopLearningMap
                        :category-short-name="category.short_name"
                        :learning-path-tabs="learningPathTabs"
                        :selected-path="selectedPath"
                        :is-classic-path="isClassicPath"
                        :is-zen-path="isZenPath"
                        :is-exam-path="isExamPath"
                        :is-pjm-path="isPjmPath"
                        :learning-path-title="learningPathTitle"
                        :learning-path-lead="learningPathLead"
                        :filtered-group-options="filteredGroupOptions"
                        :selected-topic-id="sessionForm.question_topic_id"
                        :status-options="desktopStatusOptions"
                        :question-scope-options="questionScopeOptions"
                        :selected-status="sessionForm.question_status"
                        :selected-scope="sessionForm.question_scope"
                        :randomize-order="sessionForm.randomize_order"
                        :selected-question-count="selectedQuestionCount"
                        :can-start-learning="canStartLearning"
                        :can-use-full-product="canUseFullProduct"
                        :session-processing="sessionForm.processing"
                        :global-incorrect-processing="globalIncorrectForm.processing"
                        :has-global-incorrect-questions="hasGlobalIncorrectQuestions"
                        :total-incorrect-questions="totalIncorrectQuestions"
                        :managed-incorrect-list-enabled="managedIncorrectListEnabled"
                        :incorrect-questions-url="incorrect_question_list.index_url"
                        :active-session="activeLearningSession"
                        :course-progress-percent="courseProgressPercent"
                        :traffic-sign-learning-href="trafficSignLearningHref"
                        :memory-trainer-href="memoryTrainerHref"
                        :ranking-mode-href="rankingModeHref"
                        :pjm-href="pjm_module.href"
                        :professional-courses="professional_courses"
                        :initial-professional-course-code="initial_professional_course_code"
                        :exam-facts="examFacts"
                        :start-button-label="startButtonLabel"
                        :errors="{
                            licenseCategory: sessionForm.errors.license_category_id,
                            topic: sessionForm.errors.question_topic_id,
                            status: sessionForm.errors.question_status,
                        }"
                        @select-path="selectLearningPath"
                        @select-topic="chooseTopic"
                        @select-status="chooseStatus"
                        @select-scope="chooseQuestionScope"
                        @set-random-order="sessionForm.randomize_order = $event"
                        @start-learning="startLearning"
                        @start-global-incorrect-learning="startGlobalIncorrectLearning"
                    />

                    <div class="hidden overflow-hidden bg-white md:!hidden md:min-h-[calc(100vh-8.5rem)] md:grid-cols-[13.25rem_minmax(0,1fr)] xl:grid-cols-[14.5rem_minmax(0,1fr)]">
                        <aside class="flex min-h-full flex-col border-r border-[#e4eaf3] bg-white px-3 py-5">
                            <nav class="space-y-1.5" aria-label="Panel nauki">
                                <button
                                    v-for="card in desktopSidebarCards"
                                    :key="card.value"
                                    type="button"
                                    class="group flex min-h-10 w-full items-center gap-2.5 rounded-[5px] px-3 text-left text-[0.86rem] font-bold transition disabled:cursor-not-allowed disabled:opacity-45"
                                    :class="selectedPath === card.value
                                        ? 'bg-[#07152d] text-white shadow-[0_12px_28px_rgba(7,21,45,0.12)]'
                                        : 'text-[#10172f] hover:bg-[#f5f7fb]'"
                                    :disabled="card.disabled"
                                    @click="selectLearningPath(card.value)"
                                >
                                    <span class="grid h-6 w-6 shrink-0 place-items-center" aria-hidden="true">
                                        <img
                                            v-if="card.icon"
                                            :src="card.icon"
                                            alt=""
                                            class="h-5 w-5 object-contain"
                                        >
                                        <span v-else class="text-[0.7rem] font-bold">PJM</span>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate">{{ card.label }}</span>
                                    <span
                                        v-if="card.badge && card.value !== 'memory'"
                                        class="grid h-5 min-w-5 place-items-center rounded-full bg-[#e11d2e] px-1.5 text-[0.68rem] font-bold text-white"
                                    >
                                        {{ card.badge }}
                                    </span>
                                </button>
                            </nav>

                            <div class="mt-auto pt-6">
                                <section class="rounded-[5px] border border-[#e4eaf3] bg-white p-3 shadow-[0_14px_30px_rgba(15,23,42,0.04)]">
                                    <p class="text-[0.84rem] font-bold text-[#10172f]">Ucz się efektywnie</p>
                                    <p class="mt-2 text-[0.76rem] font-medium leading-5 text-[#516071]">
                                        Regularna nauka przynosi najlepsze efekty.
                                    </p>
                                    <Link
                                        href="/jak-to-dziala"
                                        class="mt-3 inline-flex items-center gap-2 text-[0.78rem] font-bold text-[#0b5cff] transition hover:text-[#083fba]"
                                    >
                                        <span>Dowiedz się więcej</span>
                                        <span aria-hidden="true">→</span>
                                    </Link>
                                </section>
                            </div>
                        </aside>

                        <main class="min-w-0 px-5 py-4 xl:px-6">
                            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_17rem]">
                                <div class="min-w-0 space-y-4">
                                    <section
                                        class="grid min-h-[15rem] overflow-hidden rounded-[7px] border border-[#e4eaf3] bg-white shadow-[0_16px_42px_rgba(15,23,42,0.05)] lg:grid-cols-[minmax(0,0.95fr)_minmax(19rem,1.05fr)]"
                                        aria-label="Twoja aktualna sesja"
                                    >
                                        <div class="flex min-w-0 flex-col justify-center p-6 xl:p-7">
                                            <p class="text-[0.78rem] font-bold uppercase tracking-[0.08em] text-[#4b5874]">
                                                Twoja aktualna sesja
                                            </p>
                                            <h2 class="mt-3 text-[1.58rem] font-bold leading-tight tracking-[0] text-[#10172f]">
                                                {{ desktopHeroTopic?.label ?? 'Wybierz dział' }}
                                            </h2>
                                            <div class="mt-4 flex flex-wrap items-center gap-4 text-[0.86rem] font-bold text-[#10172f]">
                                                <span class="inline-flex items-center gap-2">
                                                    <span>{{ desktopHeroTopic?.questions_count ?? 0 }} pytań</span>
                                                </span>
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="grid h-6 w-6 place-items-center rounded-full border border-[#cbd6e5]" aria-hidden="true">◇</span>
                                                    <span>Dział {{ selectedTopicIndex >= 0 ? selectedTopicIndex + 1 : 1 }} z {{ Math.max(topicQuestionCounts.length, 1) }}</span>
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                class="mt-5 inline-flex min-h-[2.9rem] w-fit items-center justify-center gap-3 rounded-[5px] bg-[#e11d2e] px-6 text-[0.88rem] font-bold uppercase tracking-[0] text-white transition hover:bg-[#c91627] disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2"
                                                :disabled="sessionForm.processing"
                                                @click="startDesktopHeroLearning"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M8 5.4v13.2L18.4 12 8 5.4Z" />
                                                </svg>
                                                <span>{{ sessionForm.processing ? 'Uruchamianie...' : 'Kontynuuj naukę' }}</span>
                                            </button>
                                        </div>

                                        <div class="relative min-h-[14.2rem] overflow-hidden bg-[#f7f9fc]">
                                            <img
                                                :src="desktopHeroImageSrc"
                                                alt=""
                                                aria-hidden="true"
                                                class="absolute inset-0 h-full w-full object-cover object-center"
                                                :style="desktopHeroImageStyle"
                                            >
                                            <div aria-hidden="true" class="absolute inset-y-0 left-0 w-24 bg-[linear-gradient(90deg,#fff_0%,rgba(255,255,255,0)_100%)]" />
                                        </div>
                                    </section>

                                    <section aria-label="Szybkie akcje">
                                        <h2 class="text-[0.95rem] font-bold text-[#10172f]">Szybkie akcje</h2>
                                        <div class="mt-2 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                                            <button
                                                v-for="card in desktopQuickActionCards"
                                                :key="card.value"
                                                type="button"
                                                class="group flex min-h-[3.45rem] items-center justify-between rounded-[6px] border bg-white px-4 text-left shadow-[0_12px_28px_rgba(15,23,42,0.03)] transition disabled:cursor-not-allowed disabled:opacity-45"
                                                :class="selectedPath === card.value
                                                    ? 'border-[#07152d] ring-1 ring-[#07152d]'
                                                    : 'border-[#e4eaf3] hover:-translate-y-0.5 hover:border-[#cbd6e5]'"
                                                :disabled="card.disabled"
                                                @click="selectLearningPath(card.value)"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-[0.92rem] font-bold text-[#10172f]">
                                                        {{ card.label }}
                                                    </span>
                                                </span>
                                                <span class="text-2xl font-light leading-none text-[#10172f] transition group-hover:translate-x-0.5" aria-hidden="true">›</span>
                                            </button>
                                        </div>
                                    </section>

                                    <form class="rounded-[8px] border border-[#e4eaf3] bg-white p-4 shadow-[0_16px_42px_rgba(15,23,42,0.045)] xl:p-5" @submit.prevent="startLearning">
                                        <template v-if="isClassicPath || isZenPath">
                                            <section>
                                                <h3 class="text-[0.95rem] font-bold leading-tight text-[#10172f]">1. Wybierz dział</h3>
                                                <div data-topic-dropdown-root class="relative mt-2.5">
                                                    <button
                                                        type="button"
                                                        class="flex min-h-[2.9rem] w-full min-w-0 items-center justify-between gap-3 rounded-[5px] border border-[#b9c7da] bg-white px-4 text-left transition hover:border-[#8fa2bb] disabled:cursor-not-allowed disabled:opacity-60"
                                                        :disabled="filteredGroupOptions.length === 0"
                                                        :aria-expanded="topicDropdownOpen"
                                                        @click="toggleTopicDropdown"
                                                    >
                                                        <span class="flex min-w-0 flex-1 items-center gap-3">
                                                            <span class="truncate text-[0.9rem] font-bold text-[#10172f]">
                                                                {{ topicDisplayLabel(selectedTopic) }}
                                                            </span>
                                                        </span>
                                                        <span class="text-xl font-light text-[#10172f]" aria-hidden="true">⌄</span>
                                                    </button>

                                                    <div
                                                        v-if="topicDropdownOpen && filteredGroupOptions.length > 0"
                                                        class="absolute left-0 right-0 top-full z-40 mt-2 overflow-hidden rounded-[7px] border border-[#dfe7f1] bg-white shadow-[0_24px_54px_rgba(15,23,42,0.14)]"
                                                    >
                                                        <div class="max-h-[23rem] overflow-y-auto py-2">
                                                            <div
                                                                v-for="group in filteredGroupOptions"
                                                                :key="group.label"
                                                                class="border-t border-[#edf1f6] first:border-t-0"
                                                            >
                                                                <p class="px-4 py-2 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-[#667085]">
                                                                    {{ group.label }}
                                                                </p>
                                                                <button
                                                                    v-for="option in group.options"
                                                                    :key="option.id"
                                                                    type="button"
                                                                    class="flex w-full items-center justify-between gap-4 px-4 py-2.5 text-left transition hover:bg-[#f8fafc]"
                                                                    :class="sessionForm.question_topic_id === option.id ? 'bg-[#fff5f6]' : ''"
                                                                    @click="chooseTopic(option.id)"
                                                                >
                                                                    <span class="min-w-0 flex-1 truncate text-[0.84rem] font-bold text-[#10172f]">
                                                                        {{ topicDisplayLabel(option) }}
                                                                    </span>
                                                                    <span class="shrink-0 text-[0.78rem] font-bold text-[#667085]">
                                                                        {{ option.questions_count }}
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            <section class="mt-4 rounded-[7px] border border-[#e0e7f1] bg-white p-4 lg:grid lg:grid-cols-2 xl:p-5">
                                                <div class="min-w-0 pb-4 lg:border-r lg:border-[#d4deeb] lg:pb-0 lg:pr-6">
                                                    <h3 class="text-[0.95rem] font-bold leading-tight text-[#10172f]">2. Wybierz zestaw pytań</h3>
                                                    <div class="mt-3 space-y-1.5">
                                                        <button
                                                            v-for="option in desktopStatusOptions"
                                                            :key="option.value"
                                                            type="button"
                                                            class="flex min-h-8 w-full items-center justify-between gap-3 rounded-[5px] px-1 text-left transition hover:bg-[#f8fafc]"
                                                            @click="chooseStatus(option.value)"
                                                        >
                                                            <span class="flex min-w-0 items-center gap-3">
                                                                <span
                                                                    class="grid h-4 w-4 shrink-0 place-items-center rounded-full border"
                                                                    :class="sessionForm.question_status === option.value ? 'border-[#0b5cff]' : 'border-[#b5c1d3]'"
                                                                    aria-hidden="true"
                                                                >
                                                                    <span v-if="sessionForm.question_status === option.value" class="h-2 w-2 rounded-full bg-[#0b5cff]" />
                                                                </span>
                                                                <span class="truncate text-[0.86rem] font-semibold text-[#10172f]">
                                                                    {{ option.label }}
                                                                </span>
                                                            </span>
                                                            <span class="grid h-7 min-w-10 shrink-0 place-items-center rounded-full bg-[#f1f3f6] px-2.5 text-[0.82rem] font-bold text-[#10172f]">
                                                                {{ option.count }}
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="min-w-0 border-t border-[#e0e7f1] pt-4 lg:border-t-0 lg:pl-6 lg:pt-0">
                                                    <h3 class="text-[0.95rem] font-bold leading-tight text-[#10172f]">3. Ustawienia sesji</h3>
                                                    <div class="mt-3">
                                                        <p class="text-[0.84rem] font-medium text-[#1f2a44]">Kolejność pytań</p>
                                                        <div class="mt-2.5 space-y-2">
                                                            <button type="button" class="flex w-full items-center gap-3 text-left text-[0.86rem] font-semibold text-[#10172f]" @click="sessionForm.randomize_order = false">
                                                                <span class="grid h-4 w-4 place-items-center rounded-full border" :class="!sessionForm.randomize_order ? 'border-[#0b5cff]' : 'border-[#b5c1d3]'">
                                                                    <span v-if="!sessionForm.randomize_order" class="h-2 w-2 rounded-full bg-[#0b5cff]" />
                                                                </span>
                                                                Stała kolejność
                                                            </button>
                                                            <button type="button" class="flex w-full items-center gap-3 text-left text-[0.86rem] font-semibold text-[#10172f]" @click="sessionForm.randomize_order = true">
                                                                <span class="grid h-4 w-4 place-items-center rounded-full border" :class="sessionForm.randomize_order ? 'border-[#0b5cff]' : 'border-[#b5c1d3]'">
                                                                    <span v-if="sessionForm.randomize_order" class="h-2 w-2 rounded-full bg-[#0b5cff]" />
                                                                </span>
                                                                Losowa kolejność
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="mt-4 border-t border-[#d4deeb] pt-4">
                                                        <p class="text-[0.84rem] font-medium text-[#1f2a44]">Zakres pytań w sesji</p>
                                                        <div class="mt-2.5 grid min-h-[2.55rem] grid-cols-3 overflow-hidden rounded-[5px] border border-[#b9c7da]">
                                                            <button
                                                                v-for="scope in questionScopeOptions"
                                                                :key="scope.value"
                                                                type="button"
                                                                class="px-3 text-[0.78rem] font-bold transition"
                                                                :class="sessionForm.question_scope === scope.value ? 'bg-[#07152d] text-white' : 'bg-white text-[#10172f] hover:bg-[#f8fafc]'"
                                                                @click="chooseQuestionScope(scope.value)"
                                                            >
                                                                {{ scope.label }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            <div
                                                class="mt-4 grid gap-3"
                                                :class="hasGlobalIncorrectQuestions && managedIncorrectListEnabled ? 'lg:grid-cols-3' : 'lg:grid-cols-2'"
                                            >
                                                <Link
                                                    v-if="hasGlobalIncorrectQuestions && managedIncorrectListEnabled"
                                                    :href="incorrect_question_list.index_url"
                                                    class="inline-flex min-h-[3.15rem] items-center justify-center gap-3 rounded-[5px] border border-[#ff1e2d] bg-white px-5 text-[0.82rem] font-bold uppercase tracking-[0] text-[#e11d2e] transition hover:bg-[#fff5f6]"
                                                >
                                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M12 3.75 21 20.25H3L12 3.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                                        <path d="M12 9v5M12 17.25h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                    <span>{{ totalIncorrectQuestions }} pytań do poprawy</span>
                                                </Link>
                                                <button
                                                    v-if="hasGlobalIncorrectQuestions && managedIncorrectListEnabled"
                                                    type="button"
                                                    class="inline-flex min-h-[3.15rem] items-center justify-center gap-3 rounded-[5px] border border-[#e11d2e] bg-[#e11d2e] px-5 text-[0.82rem] font-bold uppercase tracking-[0] text-white transition hover:bg-[#c91929] disabled:cursor-not-allowed disabled:opacity-50"
                                                    :disabled="globalIncorrectForm.processing || sessionForm.processing"
                                                    @click="startGlobalIncorrectLearning"
                                                >
                                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M8 5.4v13.2L18.4 12 8 5.4Z" />
                                                    </svg>
                                                    <span>{{ globalIncorrectForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }}</span>
                                                </button>
                                                <button
                                                    v-else-if="hasGlobalIncorrectQuestions"
                                                    type="button"
                                                    class="inline-flex min-h-[3.15rem] items-center justify-center gap-3 rounded-[5px] border border-[#ff1e2d] bg-white px-5 text-[0.82rem] font-bold uppercase tracking-[0] text-[#e11d2e] transition hover:bg-[#fff5f6] disabled:cursor-not-allowed disabled:opacity-50"
                                                    :disabled="globalIncorrectForm.processing || sessionForm.processing"
                                                    @click="startGlobalIncorrectLearning"
                                                >
                                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M12 3.75 21 20.25H3L12 3.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                                        <path d="M12 9v5M12 17.25h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                    <span>{{ globalIncorrectForm.processing ? 'Uruchamianie...' : `${totalIncorrectQuestions} pytań z błędami` }}</span>
                                                </button>

                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-[3.15rem] items-center justify-center gap-3 rounded-[5px] bg-[#07152d] px-5 text-[0.84rem] font-bold uppercase tracking-[0] text-white transition hover:bg-[#12223f] disabled:cursor-not-allowed disabled:opacity-60"
                                                    :class="hasGlobalIncorrectQuestions ? '' : 'lg:col-span-2'"
                                                    :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)"
                                                >
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M8 5.4v13.2L18.4 12 8 5.4Z" />
                                                    </svg>
                                                    <span>{{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}</span>
                                                </button>
                                            </div>
                                        </template>

                                        <template v-else-if="isExamPath">
                                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                                <div>
                                                    <p class="text-[0.78rem] font-bold uppercase tracking-[0.08em] text-[#e11d2e]">Tryb egzaminacyjny</p>
                                                    <h2 class="mt-2 text-xl font-bold text-[#10172f]">Egzamin próbny</h2>
                                                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                                                        <div v-for="fact in examFacts" :key="fact.label" class="rounded-[5px] border border-[#e3e8f0] bg-[#f8fafc] px-4 py-3">
                                                            <p class="text-[0.66rem] font-bold uppercase tracking-[0.1em] text-[#667085]">{{ fact.label }}</p>
                                                            <p class="mt-1 text-lg font-bold text-[#10172f]">{{ fact.value }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[5px] bg-[#07152d] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#12223f] disabled:cursor-not-allowed disabled:opacity-60" :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)">
                                                    {{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}
                                                </button>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                                <div>
                                                    <p class="text-[0.78rem] font-bold uppercase tracking-[0.08em] text-[#e11d2e]">{{ learningPathTitle }}</p>
                                                    <h2 class="mt-2 text-xl font-bold text-[#10172f]">{{ learningPathLead }}</h2>
                                                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-[#516071]">
                                                        Ten moduł działa jako osobna ścieżka, bez dodatkowej konfiguracji działu.
                                                    </p>
                                                </div>
                                                <Link v-if="isTrafficSignsPath" :href="trafficSignLearningHref" class="inline-flex min-h-11 items-center justify-center rounded-[5px] bg-[#07152d] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#12223f]">Trenuj znaki</Link>
                                                <Link v-else-if="isMemoryPath" :href="memoryTrainerHref" class="inline-flex min-h-11 items-center justify-center rounded-[5px] bg-[#07152d] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#12223f]">Otwórz trenera</Link>
                                                <Link v-else-if="isRankingPath" :href="rankingModeHref" class="inline-flex min-h-11 items-center justify-center rounded-[5px] bg-[#07152d] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#12223f]">Wejdź do rankingu</Link>
                                                <Link v-else :href="pjm_module.href" class="inline-flex min-h-11 items-center justify-center rounded-[5px] bg-[#07152d] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#12223f]">Otwórz moduł</Link>
                                            </div>
                                        </template>

                                        <p
                                            v-if="sessionForm.errors.license_category_id || sessionForm.errors.question_topic_id || sessionForm.errors.question_status"
                                            class="mt-4 border-t border-[#edf1f6] pt-4 text-sm font-bold text-[#b42318]"
                                        >
                                            {{
                                                sessionForm.errors.license_category_id
                                                    ?? sessionForm.errors.question_topic_id
                                                    ?? sessionForm.errors.question_status
                                            }}
                                        </p>
                                    </form>
                                </div>

                                <aside class="space-y-4">
                                    <section class="rounded-[7px] border border-[#e4eaf3] bg-white p-4 shadow-[0_16px_42px_rgba(15,23,42,0.045)]">
                                        <p class="text-[0.78rem] font-bold uppercase tracking-[0.08em] text-[#4b5874]">
                                            Twój postęp
                                        </p>
                                        <div class="mx-auto mt-4 grid h-[7.4rem] w-[7.4rem] place-items-center rounded-full" :style="courseProgressStyle" aria-hidden="true">
                                            <div class="grid h-[5.55rem] w-[5.55rem] place-items-center rounded-full bg-white shadow-[inset_0_0_0_1px_rgba(226,232,240,0.95)]">
                                                <span class="text-[1.45rem] font-bold leading-none text-[#10172f]">{{ courseProgressPercent }}%</span>
                                                <span class="-mt-4 text-[0.64rem] font-semibold text-[#516071]">ukończenia</span>
                                            </div>
                                        </div>
                                        <div class="mt-4 space-y-2.5">
                                            <p class="flex items-start gap-2.5 text-[0.82rem] font-semibold text-[#10172f]">
                                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border border-[#20bf55] text-[#20bf55]">✓</span>
                                                <span><strong class="block font-bold">{{ courseCorrectCount }}</strong><span class="text-[#516071]">poprawne odpowiedzi</span></span>
                                            </p>
                                            <p class="flex items-start gap-2.5 text-[0.82rem] font-semibold text-[#10172f]">
                                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border border-[#e11d2e] text-[#e11d2e]">×</span>
                                                <span><strong class="block font-bold">{{ totalIncorrectQuestions }}</strong><span class="text-[#516071]">błędne odpowiedzi</span></span>
                                            </p>
                                            <p class="flex items-start gap-2.5 text-[0.82rem] font-semibold text-[#10172f]">
                                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border border-[#0b5cff] text-[#0b5cff]">◷</span>
                                                <span><strong class="block font-bold">--:--</strong><span class="text-[#516071]">średni czas / pytanie</span></span>
                                            </p>
                                        </div>
                                        <Link :href="categoryStatsHref" class="mt-4 inline-flex min-h-8 w-full items-center justify-between border-t border-[#edf1f6] pt-3 text-[0.8rem] font-bold text-[#0b5cff] transition hover:text-[#083fba]">
                                            <span>Zobacz szczegółowe statystyki</span>
                                            <span aria-hidden="true">→</span>
                                        </Link>
                                    </section>
                                </aside>
                            </div>
                        </main>
                    </div>

                    <div class="hidden">
                        <section
                            class="relative min-h-[17.4rem] overflow-hidden rounded-[12px] border border-[#e5eaf3] bg-[#f7fbff] px-10 py-8 shadow-[0_18px_46px_rgba(15,23,42,0.06)]"
                            aria-label="Kontynuuj naukę"
                        >
                            <img
                                :src="learningDashboardHero"
                                alt=""
                                aria-hidden="true"
                                class="pointer-events-none absolute bottom-0 right-0 h-[102%] w-[82%] object-contain object-right-bottom opacity-95 [mask-image:linear-gradient(90deg,transparent_0%,rgba(0,0,0,0)_7%,#000_24%,#000_100%)] xl:h-[106%] xl:w-[84%]"
                            >
                            <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 w-[46%] bg-[linear-gradient(90deg,rgba(248,251,255,0.98)_0%,rgba(248,251,255,0.92)_58%,rgba(248,251,255,0)_100%)]" />

                            <div class="relative max-w-[31rem]">
                                <p class="text-[0.78rem] font-bold uppercase tracking-[0.06em] text-[#e11d2e]">
                                    Kontynuuj naukę
                                </p>
                                <h1 class="mt-4 text-[2.72rem] font-bold leading-none tracking-[0] text-[#10172f]">
                                    {{ desktopHeroTopic?.label ?? 'Nauka klasyczna' }}
                                </h1>
                                <div class="mt-5 flex items-center gap-2 text-[1rem] font-medium text-[#10172f]">
                                    <span class="font-bold text-[#e11d2e]">{{ desktopHeroProgressPercent }}%</span>
                                    <span>ukończono</span>
                                    <span aria-hidden="true" class="text-[#9aa6b7]">•</span>
                                    <span>{{ desktopHeroSecondaryText }}</span>
                                </div>
                                <div class="mt-4 h-1.5 w-[22rem] overflow-hidden rounded-full bg-[#dbe3ec]">
                                    <div
                                        class="h-full rounded-full bg-[#e11d2e] transition-[width] duration-300"
                                        :style="{ width: desktopHeroProgressWidth }"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="mt-8 inline-flex min-h-[3.4rem] items-center justify-center gap-3 rounded-[6px] bg-[#e11d2e] px-7 text-[1rem] font-bold text-white shadow-[0_16px_28px_rgba(225,29,46,0.22)] transition hover:bg-[#c91627] disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d2e] focus-visible:ring-offset-2"
                                    :disabled="sessionForm.processing"
                                    @click="startDesktopHeroLearning"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M8 5.4v13.2L18.4 12 8 5.4Z" />
                                    </svg>
                                    <span>{{ sessionForm.processing ? 'Uruchamianie...' : 'Kontynuuj naukę' }}</span>
                                </button>
                            </div>
                        </section>

                        <form class="space-y-4" @submit.prevent="startLearning">
                            <section class="grid gap-4 lg:grid-cols-6" aria-label="Tryby nauki">
                                <article
                                    v-for="card in desktopLearningCards"
                                    :key="card.value"
                                    class="group relative flex min-h-[9.9rem] flex-col items-center justify-between rounded-[8px] border bg-white px-4 py-4 text-center shadow-[0_14px_34px_rgba(15,23,42,0.045)] transition"
                                    :class="[
                                        selectedPath === card.value
                                            ? 'border-[#e11d2e] shadow-[0_18px_38px_rgba(225,29,46,0.08)]'
                                            : 'border-[#e3e8f0] hover:-translate-y-0.5 hover:border-[#cbd6e5] hover:shadow-[0_20px_42px_rgba(15,23,42,0.075)]',
                                        card.disabled ? 'cursor-not-allowed opacity-50' : '',
                                    ]"
                                >
                                    <span
                                        v-if="card.badge"
                                        class="absolute right-7 top-6 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#e11d2e] px-1.5 text-[0.62rem] font-bold text-white"
                                    >
                                        {{ card.badge }}
                                    </span>
                                    <button
                                        type="button"
                                        class="flex w-full flex-1 flex-col items-center text-center focus:outline-none"
                                        :disabled="card.disabled"
                                        :aria-pressed="selectedPath === card.value"
                                        @click="selectLearningPath(card.value)"
                                    >
                                        <span class="grid h-11 w-11 place-items-center" aria-hidden="true">
                                            <img
                                                v-if="card.icon"
                                                :src="card.icon"
                                                alt=""
                                                class="h-11 w-11 object-contain"
                                            >
                                            <span v-else class="text-[0.92rem] font-bold text-[#10172f]">PJM</span>
                                        </span>
                                        <span class="mt-3 text-[0.88rem] font-bold text-[#10172f]">
                                            {{ card.label }}
                                        </span>
                                        <span class="mt-1 text-[0.78rem] font-medium text-[#5b6475]">
                                            {{ card.summary }}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-3 inline-flex min-h-8 w-full items-center justify-center rounded-[4px] px-3 text-[0.78rem] font-bold transition disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="selectedPath === card.value
                                            ? 'bg-[#e11d2e] text-white hover:bg-[#c91627]'
                                            : 'bg-[#111a33] text-white hover:bg-[#1e293f]'"
                                        :disabled="card.disabled"
                                        @click="selectLearningPath(card.value)"
                                    >
                                        {{ card.buttonLabel }}
                                    </button>
                                </article>
                            </section>

                            <section class="rounded-[8px] border border-[#e3e8f0] bg-white p-5 shadow-[0_14px_34px_rgba(15,23,42,0.045)]">
                                <template v-if="isClassicPath || isZenPath">
                                    <div class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1.45fr)_minmax(0,1.45fr)]">
                                        <section class="min-w-0 border-r border-[#edf1f6] pr-5">
                                            <h2 class="text-[0.9rem] font-bold text-[#10172f]">1. Wybierz dział</h2>
                                            <div data-topic-dropdown-root class="relative mt-4">
                                                <button
                                                    type="button"
                                                    class="flex min-h-[3.9rem] w-full min-w-0 items-center justify-between gap-3 rounded-[6px] border border-[#dfe7f1] bg-white px-4 text-left transition hover:border-[#c6d3e3] hover:bg-[#fbfdff] disabled:cursor-not-allowed disabled:opacity-60"
                                                    :disabled="filteredGroupOptions.length === 0"
                                                    :aria-expanded="topicDropdownOpen"
                                                    @click="toggleTopicDropdown"
                                                >
                                                    <span class="flex min-w-0 flex-1 items-center gap-3">
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block truncate text-[0.86rem] font-bold text-[#10172f]">
                                                                {{ selectedTopic?.label ?? 'Wybierz dział' }}
                                                            </span>
                                                            <span class="mt-0.5 block text-[0.74rem] font-semibold text-[#5b6475]">
                                                                {{ selectedTopic ? `${selectedTopic.questions_count}/${selectedTopic.questions_count}` : '0/0' }}
                                                            </span>
                                                        </span>
                                                    </span>
                                                    <span class="text-xl font-light text-[#10172f]" aria-hidden="true">›</span>
                                                </button>

                                                <div
                                                    v-if="topicDropdownOpen && filteredGroupOptions.length > 0"
                                                    class="absolute left-0 right-0 top-full z-40 mt-2 overflow-hidden rounded-[8px] border border-[#dfe7f1] bg-white shadow-[0_24px_54px_rgba(15,23,42,0.14)]"
                                                >
                                                    <div class="max-h-[23rem] overflow-y-auto py-2">
                                                        <div
                                                            v-for="group in filteredGroupOptions"
                                                            :key="group.label"
                                                            class="border-t border-[#edf1f6] first:border-t-0"
                                                        >
                                                            <p class="px-4 py-2 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-[#667085]">
                                                                {{ group.label }}
                                                            </p>
                                                            <button
                                                                v-for="option in group.options"
                                                                :key="option.id"
                                                                type="button"
                                                                class="flex w-full items-center justify-between gap-4 px-4 py-2.5 text-left transition hover:bg-[#f8fafc]"
                                                                :class="sessionForm.question_topic_id === option.id ? 'bg-[#fff5f6]' : ''"
                                                                @click="chooseTopic(option.id)"
                                                            >
                                                                <span class="min-w-0 flex-1 truncate text-[0.82rem] font-bold text-[#10172f]">
                                                                    {{ option.label }}
                                                                </span>
                                                                <span class="shrink-0 text-[0.76rem] font-bold text-[#667085]">
                                                                    {{ option.questions_count }}
                                                                </span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_2.45rem] gap-2">
                                                <button
                                                    type="button"
                                                    data-topic-dropdown-root
                                                    class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dfe7f1] bg-white px-4 text-[0.78rem] font-bold text-[#10172f] transition hover:border-[#c6d3e3] hover:bg-[#f8fafc]"
                                                    @click="toggleTopicDropdown"
                                                >
                                                    Wybierz inny dział
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dfe7f1] bg-white text-xl font-light leading-none text-[#10172f] transition hover:border-[#c6d3e3] hover:bg-[#f8fafc] disabled:cursor-not-allowed disabled:opacity-40"
                                                    :disabled="!nextTopic"
                                                    :title="nextTopic ? `Następny dział: ${nextTopic.label}` : 'To ostatni dział w tym zakresie'"
                                                    @click="chooseNextTopic"
                                                >
                                                    <span class="sr-only">Następny dział</span>
                                                    <span aria-hidden="true">›</span>
                                                </button>
                                            </div>
                                        </section>

                                        <section class="min-w-0 border-r border-[#edf1f6] pr-5">
                                            <h2 class="text-[0.9rem] font-bold text-[#10172f]">2. Wybierz zestaw pytań</h2>
                                            <div class="mt-3 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(12rem,0.78fr)]">
                                                <div class="rounded-[6px] border border-[#e3e8f0] bg-white p-3">
                                                    <button
                                                        v-for="option in desktopStatusOptions"
                                                        :key="option.value"
                                                        type="button"
                                                        class="flex w-full items-center justify-between gap-3 rounded-[4px] px-1.5 py-1.5 text-left transition hover:bg-[#f8fafc]"
                                                        @click="chooseStatus(option.value)"
                                                    >
                                                        <span class="flex min-w-0 items-center gap-2">
                                                            <span
                                                                class="grid h-4 w-4 shrink-0 place-items-center rounded-full border"
                                                                :class="sessionForm.question_status === option.value ? 'border-[#e11d2e]' : 'border-[#9aa6b7]'"
                                                                aria-hidden="true"
                                                            >
                                                                <span v-if="sessionForm.question_status === option.value" class="h-2 w-2 rounded-full bg-[#e11d2e]" />
                                                            </span>
                                                            <span class="truncate text-[0.8rem] font-semibold text-[#10172f]">{{ option.label }}</span>
                                                        </span>
                                                        <span class="shrink-0 text-[0.76rem] font-semibold text-[#10172f]">
                                                            {{ option.count }} pytań
                                                        </span>
                                                    </button>
                                                </div>

                                                <div class="rounded-[6px] border border-[#ffb8bf] bg-[#fff7f8] p-4">
                                                    <div class="flex items-start gap-3">
                                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-[#e11d2e]" aria-hidden="true">
                                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                                <path d="M20 12a8 8 0 1 1-2.35-5.66" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
                                                                <path d="M20 4v5h-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </span>
                                                        <div>
                                                            <h3 class="text-[0.84rem] font-bold leading-5 text-[#10172f]">
                                                                {{ managedIncorrectListEnabled ? 'Twoje pytania do poprawy' : 'Powtórz błędne odpowiedzi z całej kategorii' }}
                                                            </h3>
                                                            <p class="mt-3 text-[0.73rem] font-medium leading-5 text-[#5b6475]">
                                                                {{ managedIncorrectListEnabled
                                                                    ? `Na liście czeka ${totalIncorrectQuestions} pytań. Sam decydujesz, kiedy je usunąć.`
                                                                    : `Zbierzemy ${totalIncorrectQuestions} pytań z błędami bez wybierania pojedynczego działu.` }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-if="managedIncorrectListEnabled"
                                                        class="mt-4 grid gap-2 sm:grid-cols-2"
                                                    >
                                                        <Link
                                                            :href="incorrect_question_list.index_url"
                                                            class="inline-flex min-h-10 items-center justify-center rounded-[4px] border border-[#ff9ea8] bg-white px-3 text-[0.78rem] font-bold text-[#e11d2e] transition hover:bg-[#fff0f2]"
                                                        >
                                                            Otwórz listę
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            class="inline-flex min-h-10 items-center justify-center rounded-[4px] border border-[#e11d2e] bg-[#e11d2e] px-3 text-[0.78rem] font-bold text-white transition hover:bg-[#c91929] disabled:cursor-not-allowed disabled:opacity-50"
                                                            :disabled="globalIncorrectForm.processing || sessionForm.processing"
                                                            @click="startGlobalIncorrectLearning"
                                                        >
                                                            {{ globalIncorrectForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }}
                                                        </button>
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        class="mt-4 inline-flex min-h-9 w-full items-center justify-center rounded-[4px] border border-[#ff9ea8] bg-white px-4 text-[0.82rem] font-bold text-[#e11d2e] transition hover:bg-[#fff0f2] disabled:cursor-not-allowed disabled:opacity-50"
                                                        :disabled="globalIncorrectForm.processing || sessionForm.processing || !hasGlobalIncorrectQuestions"
                                                        @click="startGlobalIncorrectLearning"
                                                    >
                                                        {{ globalIncorrectForm.processing ? 'Uruchamianie...' : 'Powtórz błędy' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </section>

                                        <section class="min-w-0">
                                            <h2 class="text-[0.9rem] font-bold text-[#10172f]">3. Ustawienia sesji</h2>
                                            <div class="mt-3 rounded-[6px] border border-[#e3e8f0] bg-white p-3">
                                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,0.72fr)]">
                                                    <p class="text-[0.76rem] font-bold text-[#10172f]">Kolejność pytań w dziale</p>
                                                    <div class="space-y-2">
                                                        <button type="button" class="flex w-full items-center gap-2 text-left text-[0.78rem] font-semibold text-[#10172f]" @click="sessionForm.randomize_order = false">
                                                            <span class="grid h-4 w-4 place-items-center rounded-full border" :class="!sessionForm.randomize_order ? 'border-[#e11d2e]' : 'border-[#9aa6b7]'">
                                                                <span v-if="!sessionForm.randomize_order" class="h-2 w-2 rounded-full bg-[#e11d2e]" />
                                                            </span>
                                                            Stała kolejność
                                                        </button>
                                                        <button type="button" class="flex w-full items-center gap-2 text-left text-[0.78rem] font-semibold text-[#10172f]" @click="sessionForm.randomize_order = true">
                                                            <span class="grid h-4 w-4 place-items-center rounded-full border" :class="sessionForm.randomize_order ? 'border-[#e11d2e]' : 'border-[#9aa6b7]'">
                                                                <span v-if="sessionForm.randomize_order" class="h-2 w-2 rounded-full bg-[#e11d2e]" />
                                                            </span>
                                                            Losowa kolejność
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3 rounded-[6px] border border-[#e3e8f0] bg-white p-3">
                                                <p class="text-[0.76rem] font-bold text-[#10172f]">Zakres pytań w sesji</p>
                                                <div class="mt-3 inline-grid min-h-9 grid-cols-3 overflow-hidden rounded-[4px] border border-[#e3e8f0]">
                                                    <button
                                                        v-for="scope in questionScopeOptions"
                                                        :key="scope.value"
                                                        type="button"
                                                        class="px-4 text-[0.76rem] font-bold transition"
                                                        :class="sessionForm.question_scope === scope.value ? 'bg-[#e11d2e] text-white' : 'bg-white text-[#10172f] hover:bg-[#f8fafc]'"
                                                        @click="chooseQuestionScope(scope.value)"
                                                    >
                                                        {{ scope.label }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-3 flex items-center justify-between gap-4">
                                                <p class="flex min-w-0 items-center gap-2 text-[0.72rem] font-medium leading-5 text-[#5b6475]">
                                                    <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border border-[#2f7df6] text-[0.65rem] font-bold text-[#2f7df6]">i</span>
                                                    <span>Start uruchamia wybrany dział w kategorii {{ category.short_name }}.</span>
                                                </p>
                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-10 w-[13.4rem] shrink-0 items-center justify-center gap-2 rounded-[4px] bg-[#111a33] px-5 text-[0.86rem] font-bold text-white transition hover:bg-[#1e293f] disabled:cursor-not-allowed disabled:opacity-60"
                                                    :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M8 5.4v13.2L18.4 12 8 5.4Z" />
                                                    </svg>
                                                    <span>{{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}</span>
                                                </button>
                                            </div>
                                        </section>
                                    </div>
                                </template>

                                <template v-else-if="isExamPath">
                                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                        <div>
                                            <h2 class="text-lg font-bold text-[#10172f]">Egzamin próbny</h2>
                                            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                                                <div v-for="fact in examFacts" :key="fact.label" class="rounded-[6px] border border-[#e3e8f0] bg-[#f8fafc] px-4 py-3">
                                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.1em] text-[#667085]">{{ fact.label }}</p>
                                                    <p class="mt-1 text-lg font-bold text-[#10172f]">{{ fact.value }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#111a33] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#1e293f] disabled:cursor-not-allowed disabled:opacity-60" :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)">
                                            {{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}
                                        </button>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                        <div>
                                            <p class="text-[0.74rem] font-bold uppercase tracking-[0.12em] text-[#e11d2e]">{{ learningPathTitle }}</p>
                                            <h2 class="mt-2 text-xl font-bold text-[#10172f]">{{ learningPathLead }}</h2>
                                            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-[#5b6475]">
                                                Ten moduł działa jako osobna ścieżka, bez dodatkowej konfiguracji działu.
                                            </p>
                                        </div>
                                        <Link v-if="isTrafficSignsPath" :href="trafficSignLearningHref" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#111a33] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#1e293f]">Trenuj znaki</Link>
                                        <Link v-else-if="isMemoryPath" :href="memoryTrainerHref" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#111a33] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#1e293f]">Otwórz trenera</Link>
                                        <Link v-else-if="isRankingPath" :href="rankingModeHref" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#111a33] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#1e293f]">Wejdź do rankingu</Link>
                                        <Link v-else :href="pjm_module.href" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#111a33] px-7 text-[0.9rem] font-bold text-white transition hover:bg-[#1e293f]">Otwórz moduł</Link>
                                    </div>
                                </template>

                                <p
                                    v-if="sessionForm.errors.license_category_id || sessionForm.errors.question_topic_id || sessionForm.errors.question_status"
                                    class="mt-4 border-t border-[#edf1f6] pt-4 text-sm font-bold text-[#b42318]"
                                >
                                    {{
                                        sessionForm.errors.license_category_id
                                            ?? sessionForm.errors.question_topic_id
                                            ?? sessionForm.errors.question_status
                                    }}
                                </p>
                            </section>
                        </form>

                        <section class="grid gap-4 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                            <article class="rounded-[8px] border border-[#e3e8f0] bg-white p-5 shadow-[0_14px_34px_rgba(15,23,42,0.04)]">
                                <div class="flex items-start justify-between gap-5">
                                    <div>
                                        <p class="text-[0.84rem] font-bold text-[#10172f]">Twoja aktualna sesja</p>
                                        <div class="mt-4">
                                            <div>
                                                <h2 class="text-xl font-bold text-[#10172f]">{{ selectedTopic?.label ?? 'Wybierz dział' }}</h2>
                                                <p class="mt-1 text-[0.84rem] font-semibold text-[#5b6475]">{{ selectedTopic?.questions_count ?? 0 }} pytań</p>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" data-topic-dropdown-root class="inline-flex min-h-10 w-28 items-center justify-center rounded-[4px] border border-[#dfe7f1] bg-white px-5 text-[0.84rem] font-bold text-[#10172f] transition hover:bg-[#f8fafc]" @click="toggleTopicDropdown">
                                        Zmień
                                    </button>
                                </div>
                            </article>

                            <article class="rounded-[8px] border border-[#e3e8f0] bg-white p-5 shadow-[0_14px_34px_rgba(15,23,42,0.04)]">
                                <div class="grid items-center gap-5 lg:grid-cols-[auto_minmax(0,1fr)_auto]">
                                    <div>
                                        <p class="text-[0.84rem] font-bold text-[#10172f]">Twój postęp</p>
                                        <div class="mt-3 grid h-[5.7rem] w-[5.7rem] place-items-center rounded-full" :style="courseProgressStyle" aria-hidden="true">
                                            <div class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-full bg-white shadow-[inset_0_0_0_1px_rgba(226,232,240,0.95)]">
                                                <span class="text-2xl font-bold leading-none text-[#10172f]">{{ courseProgressPercent }}%</span>
                                                <span class="-mt-4 text-[0.6rem] font-semibold text-[#667085]">ogólny postęp</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2.5">
                                        <p class="flex items-center gap-3 text-[0.86rem] font-semibold text-[#10172f]"><span class="grid h-6 w-6 place-items-center rounded-full border border-[#20bf55] text-[#20bf55]">✓</span><span class="font-bold">{{ courseCorrectCount }}</span><span class="text-[#5b6475]">poprawne odpowiedzi</span></p>
                                        <p class="flex items-center gap-3 text-[0.86rem] font-semibold text-[#10172f]"><span class="grid h-6 w-6 place-items-center rounded-full border border-[#e11d2e] text-[#e11d2e]">×</span><span class="font-bold">{{ totalIncorrectQuestions }}</span><span class="text-[#5b6475]">błędne odpowiedzi</span></p>
                                        <p class="flex items-center gap-3 text-[0.86rem] font-semibold text-[#10172f]"><span class="grid h-6 w-6 place-items-center rounded-full border border-[#2f7df6] text-[#2f7df6]">◷</span><span class="font-bold">--</span><span class="text-[#5b6475]">średni czas / pytanie</span></p>
                                    </div>
                                    <Link :href="categoryStatsHref" class="inline-flex min-h-10 w-48 items-center justify-center gap-2 rounded-[4px] border border-[#dfe7f1] bg-white px-5 text-[0.84rem] font-bold text-[#10172f] transition hover:bg-[#f8fafc]">
                                        <span>Zobacz statystyki</span>
                                        <span aria-hidden="true">↗</span>
                                    </Link>
                                </div>
                            </article>
                        </section>

                        <section class="flex items-center justify-between gap-6 rounded-[8px] border border-[#e3e8f0] bg-white px-5 py-4 text-[#10172f] shadow-[0_14px_34px_rgba(15,23,42,0.035)]">
                            <div class="flex items-center gap-4">
                                <span class="grid h-12 w-12 place-items-center rounded-[6px] border border-[#dfe7f1]" aria-hidden="true">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 3.5 19 6v5.2c0 4.2-2.4 7.4-7 9.3-4.6-1.9-7-5.1-7-9.3V6l7-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                        <path d="m8.7 12 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[0.84rem] font-bold">Baza pytań aktualna na dzień 29.04.2026</p>
                                    <p class="mt-1 max-w-2xl text-[0.76rem] font-medium leading-5 text-[#5b6475]">
                                        Zgodna z Rozporządzeniem Ministra Infrastruktury z dnia 24 lutego 2016 r. i Ustawą z dnia 5 stycznia 2011 r. o kierujących pojazdami.
                                    </p>
                                </div>
                            </div>
                            <Link href="/metodologia" class="inline-flex min-h-10 w-48 shrink-0 items-center justify-center rounded-[4px] border border-[#dfe7f1] bg-white px-5 text-[0.84rem] font-bold text-[#10172f] transition hover:bg-[#f8fafc]">
                                Sprawdź aktualizacje
                            </Link>
                        </section>
                    </div>

                    <form
                        class="hidden"
                        @submit.prevent="startLearning"
                    >
                        <nav
                            class="flex gap-0 overflow-x-auto bg-[#064f9e] px-3 pt-3 sm:px-5 lg:grid lg:auto-cols-fr lg:grid-flow-col lg:overflow-visible"
                            aria-label="Tryby nauki"
                        >
                            <button
                                v-for="tab in learningPathTabs"
                                :key="tab.value"
                                type="button"
                                class="min-h-[4.1rem] min-w-[9.6rem] border border-transparent px-4 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e] lg:min-w-0"
                                :class="[
                                    selectedPath === tab.value
                                        ? 'bg-white text-[#0f172a]'
                                        : 'text-white hover:bg-white/10',
                                    tab.disabled
                                        ? 'cursor-not-allowed opacity-45'
                                        : '',
                                ]"
                                :disabled="tab.disabled"
                                :aria-pressed="selectedPath === tab.value"
                                @click="selectLearningPath(tab.value)"
                            >
                                <span class="block text-sm font-semibold leading-5">
                                    {{ tab.label }}
                                </span>
                                <span
                                    class="mt-1 block text-xs leading-4"
                                    :class="selectedPath === tab.value ? 'text-[#64748b]' : 'text-white/75'"
                                >
                                    {{ tab.summary }}
                                </span>
                            </button>
                        </nav>

                        <section class="bg-white p-4 sm:p-5 lg:p-6">
                            <div class="flex flex-col gap-3 border-b border-[#e5eaf1] pb-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#64748b]">
                                        Wybrana ścieżka
                                    </p>
                                    <h2 class="mt-1 text-[1.35rem] font-semibold leading-tight tracking-[-0.02em] text-[#0f172a]">
                                        {{ learningPathTitle }}
                                    </h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#64748b]">
                                        {{ learningPathLead }}
                                    </p>
                                </div>

                                <div
                                    v-if="isClassicPath || isZenPath || isExamPath"
                                    class="shrink-0 border border-[#e5eaf1] bg-[#f8fafc] px-3 py-2 text-left sm:text-right"
                                >
                                    <p class="text-[0.66rem] font-semibold uppercase tracking-[0.14em] text-[#64748b]">
                                        Zestaw
                                    </p>
                                    <p class="mt-1 text-base font-semibold text-[#0f172a]">
                                        {{ selectedQuestionCount }} pytań
                                    </p>
                                </div>
                            </div>

                            <div v-if="isTrafficSignsPath" class="grid gap-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,0.32fr)] lg:items-center">
                                <div>
                                    <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0f172a]">
                                        Zacznij od rozpoznawania znaków
                                    </h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#64748b]">
                                        Ten tryb uczy znaków osobno, bez mieszania ich z pytaniami egzaminacyjnymi. To dobry pierwszy krok przed klasyczną nauką.
                                    </p>
                                </div>

                                <Link
                                    :href="trafficSignLearningHref"
                                    class="inline-flex min-h-[3.15rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                >
                                    Trenuj znaki
                                </Link>
                            </div>

                            <div v-else-if="isPjmPath && showPjmEntryTile" class="grid gap-5 py-5 lg:grid-cols-[9rem_minmax(0,1fr)] lg:items-center">
                                <div class="mx-auto flex aspect-square w-36 items-center justify-center border border-[#dbe3ec] bg-[#f8fbff] p-3 lg:w-full">
                                    <img
                                        :src="pjmSignLanguageSymbol"
                                        alt=""
                                        aria-hidden="true"
                                        class="h-full w-full object-contain"
                                    >
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0f172a]">
                                        Ucz się z tłumaczeniem PJM
                                    </h3>
                                    <p class="mt-2 text-sm leading-6 text-[#64748b]">
                                        Ten tryb jest dostępny dla kont, które wybrały ścieżkę PJM. Pełna nauka klasyczna pozostaje osobnym dostępem.
                                    </p>
                                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                        <Link
                                            :href="pjm_module.href"
                                            class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-5 text-sm font-semibold text-white transition hover:bg-[#012f7d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        >
                                            Otwórz moduł PJM
                                        </Link>
                                        <button
                                            v-if="!canUseFullProduct"
                                            type="button"
                                            class="inline-flex min-h-[3rem] items-center justify-center border border-[#cbd5e1] bg-white px-5 text-sm font-semibold text-[#0f172a] transition hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                            @click="activateFullLearning"
                                        >
                                            Aktywuj pełną naukę
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="isExamPath" class="space-y-5 py-5">
                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div
                                        v-for="fact in examFacts"
                                        :key="fact.label"
                                        class="border border-[#e5eaf1] bg-[#f8fafc] px-4 py-3"
                                    >
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-[#64748b]">
                                            {{ fact.label }}
                                        </p>
                                        <p class="mt-1 text-lg font-semibold text-[#0f172a]">
                                            {{ fact.value }}
                                        </p>
                                    </div>
                                </div>

                                <ul class="grid gap-2 text-sm leading-6 text-[#475569] sm:grid-cols-2">
                                    <li v-for="rule in examRules" :key="rule" class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#023ea4]"></span>
                                        <span>{{ rule }}</span>
                                    </li>
                                </ul>

                                <div class="flex flex-col gap-3 border-t border-[#e5eaf1] pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-sm leading-6 text-[#64748b]">
                                        Start uruchamia pełny zestaw egzaminacyjny dla kategorii {{ category.short_name }}.
                                    </p>
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)"
                                    >
                                        {{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}
                                    </button>
                                </div>
                            </div>

                            <div v-else-if="isMemoryPath" class="grid gap-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,0.32fr)] lg:items-center">
                                <div>
                                    <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0f172a]">
                                        Odzyskaj materiał zanim dołożysz nowy
                                    </h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#64748b]">
                                        Trener pamięci działa jako osobny moduł powtórek. Wracasz do pytań, które realnie wymagają utrwalenia.
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <div class="border border-[#e5eaf1] bg-[#f8fafc] px-4 py-3">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-[#64748b]">
                                            Do powtórki
                                        </p>
                                        <p class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-[#0f172a]">
                                            {{ learning_overview.due_review_count }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="memoryTrainerHref"
                                        class="inline-flex min-h-[3.15rem] w-full items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                    >
                                        Otwórz trenera
                                    </Link>
                                </div>
                            </div>

                            <div v-else-if="isRankingPath" class="grid gap-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,0.32fr)] lg:items-center">
                                <div>
                                    <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0f172a]">
                                        Sprawdź tempo w pojedynku
                                    </h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#64748b]">
                                        Ranking to szybki tryb rywalizacji. Nie miesza się z konfiguracją działów ani z trenerem pamięci.
                                    </p>
                                </div>

                                <Link
                                    :href="rankingModeHref"
                                    class="inline-flex min-h-[3.15rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                >
                                    Wejdź do rankingu
                                </Link>
                            </div>

                            <template v-else>
                                <div class="space-y-5 py-5">
                                    <div
                                        v-if="isClassicPath"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <button
                                            v-for="option in status_options"
                                            :key="option.value"
                                            type="button"
                                            class="min-h-[2.5rem] border px-3 text-sm font-semibold transition"
                                            :class="sessionForm.question_status === option.value
                                                ? 'border-[#023ea4] bg-[#023ea4] text-white'
                                                : 'border-[#dbe3ec] bg-white text-[#334155] hover:border-[#aebbd0] hover:bg-[#f8fbff]'"
                                            @click="chooseStatus(option.value)"
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>

                                    <div
                                        v-if="isClassicPath && hasGlobalIncorrectQuestions"
                                        class="grid gap-4 border border-[#dbe3ec] bg-[#f8fafc] p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                                    >
                                        <div>
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#023ea4]">
                                                {{ managedIncorrectListEnabled ? 'Twoja lista' : 'Błędy ze wszystkich działów' }}
                                            </p>
                                            <h3 class="mt-1 text-lg font-semibold tracking-[-0.02em] text-[#0f172a]">
                                                {{ managedIncorrectListEnabled ? 'Pytania do poprawy' : 'Powtórz błędne odpowiedzi z całej kategorii' }}
                                            </h3>
                                            <p class="mt-2 text-sm leading-6 text-[#64748b]">
                                                {{ managedIncorrectListEnabled
                                                    ? `${totalIncorrectQuestions} pytań pozostanie na liście, dopóki sam ich nie usuniesz.`
                                                    : `Zbierzemy ${totalIncorrectQuestions} pytań z błędami bez wybierania pojedynczego działu.` }}
                                            </p>
                                            <p
                                                v-if="globalIncorrectForm.errors.license_category_id || globalIncorrectForm.errors.question_status"
                                                class="mt-2 text-sm font-medium text-[#b42318]"
                                            >
                                                {{
                                                    globalIncorrectForm.errors.license_category_id
                                                        ?? globalIncorrectForm.errors.question_status
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="managedIncorrectListEnabled"
                                            class="grid gap-2 sm:grid-cols-2"
                                        >
                                            <Link
                                                :href="incorrect_question_list.index_url"
                                                class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-white px-5 text-sm font-semibold text-[#023ea4] transition hover:bg-[#f3f7ff]"
                                            >
                                                Otwórz listę
                                            </Link>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-5 text-sm font-semibold text-white transition hover:bg-[#012f7d] disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="globalIncorrectForm.processing || sessionForm.processing"
                                                @click="startGlobalIncorrectLearning"
                                            >
                                                {{ globalIncorrectForm.processing ? 'Uruchamianie...' : 'Powtórz pytania' }}
                                            </button>
                                        </div>
                                        <button
                                            v-else
                                            type="button"
                                            class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="globalIncorrectForm.processing || sessionForm.processing"
                                            @click="startGlobalIncorrectLearning"
                                        >
                                            {{ globalIncorrectForm.processing ? 'Uruchamianie...' : 'Powtórz błędy' }}
                                        </button>
                                    </div>

                                    <div data-topic-dropdown-root class="relative z-40">
                                        <button
                                            id="question_topic_id"
                                            type="button"
                                            class="flex min-h-[3.25rem] w-full items-center justify-between gap-4 border border-[#dbe3ec] bg-[#f8fafc] px-4 text-left text-[0.97rem] font-semibold text-[#0f172a] transition hover:bg-white"
                                            :class="filteredGroupOptions.length === 0 ? 'cursor-not-allowed opacity-60 hover:bg-[#f8fafc]' : ''"
                                            :aria-expanded="topicDropdownOpen"
                                            :disabled="filteredGroupOptions.length === 0"
                                            @click="toggleTopicDropdown"
                                        >
                                            <span class="truncate">
                                                {{ selectedTopic ? selectedTopic.label : 'Dział pytań' }}
                                            </span>
                                            <span aria-hidden="true" class="text-lg text-[#64748b]">
                                                {{ topicDropdownOpen ? '−' : '+' }}
                                            </span>
                                        </button>

                                        <div
                                            v-if="topicDropdownOpen && filteredGroupOptions.length > 0"
                                            class="absolute left-0 right-0 top-full mt-2 overflow-hidden border border-[#dbe3ec] bg-white shadow-[0_22px_48px_rgba(15,23,42,0.12)]"
                                        >
                                            <div class="max-h-[24rem] overflow-y-auto py-2">
                                                <div
                                                    v-for="group in filteredGroupOptions"
                                                    :key="group.label"
                                                    class="border-t border-[#edf1f6] first:border-t-0"
                                                >
                                                    <p class="px-5 py-2 text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-[#64748b]">
                                                        {{ group.label }}
                                                    </p>
                                                    <button
                                                        v-for="option in group.options"
                                                        :key="option.id"
                                                        type="button"
                                                        class="flex w-full items-center justify-between gap-4 px-5 py-3 text-left text-sm transition hover:bg-[#f8fafc]"
                                                        :class="sessionForm.question_topic_id === option.id ? 'bg-[#eef4ff] text-[#0f172a]' : 'text-[#334155]'"
                                                        @click="chooseTopic(option.id)"
                                                    >
                                                        <span class="min-w-0 truncate font-semibold">
                                                            {{ option.label }}
                                                        </span>
                                                        <span class="shrink-0 text-[#64748b]">
                                                            {{ option.questions_count }}
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <p
                                        v-if="filteredGroupOptions.length === 0"
                                        class="border border-[#e5eaf1] bg-[#f8fafc] px-4 py-3 text-sm leading-6 text-[#64748b]"
                                    >
                                        Dla tej kategorii nie znaleziono działów w wybranym zakresie pytań.
                                    </p>

                                    <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                        <div
                                            v-if="isClassicPath"
                                            class="space-y-2"
                                        >
                                            <div class="grid min-h-[3rem] grid-cols-2 overflow-hidden border border-[#dbe3ec] bg-[#f8fafc]">
                                                <button
                                                    type="button"
                                                    class="px-3 text-sm font-semibold transition"
                                                    :class="!sessionForm.randomize_order ? 'bg-[#0f172a] text-white' : 'bg-white text-[#334155] hover:bg-[#f8fbff]'"
                                                    @click="sessionForm.randomize_order = false"
                                                >
                                                    Stała kolejność
                                                </button>
                                                <button
                                                    type="button"
                                                    class="px-3 text-sm font-semibold transition"
                                                    :class="sessionForm.randomize_order ? 'bg-[#0f172a] text-white' : 'bg-white text-[#334155] hover:bg-[#f8fbff]'"
                                                    @click="sessionForm.randomize_order = true"
                                                >
                                                    Losowa kolejność
                                                </button>
                                            </div>
                                            <p class="text-xs leading-5 text-[#64748b]">
                                                Kolejność pytań w wybranym dziale.
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="grid min-h-[3rem] grid-cols-3 overflow-hidden border border-[#dbe3ec] bg-[#f8fafc]">
                                                <button
                                                    v-for="scope in questionScopeOptions"
                                                    :key="scope.value"
                                                    type="button"
                                                    class="px-2 text-sm font-semibold transition"
                                                    :class="sessionForm.question_scope === scope.value ? 'bg-[#0f172a] text-white' : 'bg-white text-[#334155] hover:bg-[#f8fbff]'"
                                                    :title="scope.label"
                                                    @click="chooseQuestionScope(scope.value)"
                                                >
                                                    <span class="hidden sm:inline">{{ scope.label }}</span>
                                                    <span class="sm:hidden">{{ scope.compactLabel }}</span>
                                                </button>
                                            </div>
                                            <p class="text-xs leading-5 text-[#64748b]">
                                                Zakres pytań w sesji.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 border-t border-[#e5eaf1] pt-5 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm leading-6 text-[#64748b]">
                                            Start uruchamia wybrany dział w kategorii {{ category.short_name }}.
                                        </p>
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[3rem] items-center justify-center border border-[#023ea4] bg-[#023ea4] px-6 text-sm font-semibold text-white transition hover:bg-[#012f7d] disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="sessionForm.processing || (canUseFullProduct && !canStartLearning)"
                                        >
                                            {{ sessionForm.processing ? 'Uruchamianie...' : startButtonLabel }}
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <p
                                v-if="sessionForm.errors.license_category_id || sessionForm.errors.question_topic_id || sessionForm.errors.question_status"
                                class="border-t border-[#e5eaf1] pt-4 text-sm font-medium text-[#b42318]"
                            >
                                {{
                                    sessionForm.errors.license_category_id
                                        ?? sessionForm.errors.question_topic_id
                                        ?? sessionForm.errors.question_status
                                }}
                            </p>
                        </section>
                    </form>

                    <section
                        v-if="filteredGroupOptions.length > 0 && (isClassicPath || isZenPath) && !isPjmStarterMode"
                        id="topic-groups"
                        class="hidden"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#64748b]">
                                    Szybki start z działu
                                </p>
                                <h2 class="mt-1 text-xl font-semibold tracking-[-0.02em] text-[#0f172a]">
                                    Wybierz dział bez wracania do konfiguratora
                                </h2>
                            </div>
                            <p class="text-sm text-[#64748b]">
                                Postęp pokazuje przerobione pytania w dziale.
                            </p>
                        </div>

                        <section
                            v-for="group in filteredGroupOptions"
                            :key="group.label"
                            class="space-y-3"
                        >
                            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-[#64748b]">
                                {{ group.label }}
                            </h3>

                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <article
                                    v-for="option in group.options"
                                    :key="option.id"
                                    class="border border-[#dbe3ec] bg-white p-4 shadow-[0_12px_28px_rgba(15,23,42,0.04)]"
                                    :class="sessionForm.question_topic_id === option.id ? 'border-[#023ea4]' : ''"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <h4 class="min-w-0 text-sm font-semibold leading-6 text-[#0f172a]">
                                            {{ option.label }}
                                        </h4>

                                        <p class="shrink-0 text-sm font-semibold text-[#64748b]">
                                            {{ answeredCountForOption(option) }}/{{ option.questions_count }}
                                        </p>
                                    </div>

                                    <div class="mt-3 h-2 overflow-hidden bg-[#e5eaf1]">
                                        <div
                                            class="h-full bg-[#023ea4] transition-[width] duration-300"
                                            :style="{ width: `${answeredProgressPercentForOption(option)}%` }"
                                        />
                                    </div>

                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-[#334155] underline underline-offset-4 transition hover:text-[#023ea4]"
                                            @click="previewTopic(option.id)"
                                        >
                                            Pokaż wszystkie
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex min-h-[2.4rem] items-center justify-center border border-[#dbe3ec] bg-white px-3 text-sm font-semibold text-[#0f172a] transition hover:border-[#023ea4]"
                                            @click="chooseTopic(option.id)"
                                        >
                                            Wybierz dział
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </section>

                    <section class="hidden">
                        <p class="font-medium text-[#475569]">
                            Baza pytań została przygotowana zgodnie z aktualnym stanem prawnym.
                        </p>
                        <ul class="mt-1.5 space-y-1.5">
                            <li class="flex gap-2">
                                <span class="mt-[0.42rem] h-1 w-1 shrink-0 rounded-full bg-[#94a3b8]"></span>
                                <span>
                                    Rozporządzeniem Ministra Infrastruktury z dnia 24 lutego 2016 r. w sprawie egzaminowania osób ubiegających się o uprawnienia do kierowania pojazdami.
                                </span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-[0.42rem] h-1 w-1 shrink-0 rounded-full bg-[#94a3b8]"></span>
                                <span>
                                    Ustawą z dnia 5 stycznia 2011 r. o kierujących pojazdami.
                                </span>
                            </li>
                        </ul>
                    </section>
                </div>
            </section>

            <section v-else class="relative z-10 border border-[#dbe3ec] bg-white p-5">
                <p class="text-sm text-[#475569]">
                    Wybierz kategorię prawa jazdy w profilu, aby uruchomić sesję.
                </p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
