<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MobileLearningAppBar from '@/Pages/Session/Partials/MobileLearningAppBar.vue';
import mobileLearningHeroRoadCar from '../../../../images/session/mobile-learning-hero-road-car.webp';
import mobileRankingPodium from '../../../../images/session/mobile-ranking-podium.png';

type LearningPath = 'pjm' | 'traffic-signs' | 'classic' | 'zen' | 'exam' | 'memory' | 'ranking';
type RecommendedStepAction = 'activate' | 'pjm' | 'traffic-signs' | 'memory' | 'classic' | 'exam';
type ClassicPracticeMode = 'all' | 'incorrect' | 'global-incorrect';

interface LearningPathTab {
    value: LearningPath;
    label: string;
    summary: string;
    disabled: boolean;
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

interface StatusOption {
    value: string;
    label: string;
}

interface RecommendedStep {
    action: RecommendedStepAction;
    eyebrow: string;
    title: string;
    description: string;
    metricLabel: string;
    metricValue: string;
    primaryLabel: string;
    href?: string;
}

interface ExamFact {
    label: string;
    value: string;
}

interface RankingPreview {
    position: number | null;
    rating: number | null;
    matches_played: number;
    message: string;
}

interface ActiveLearningSession {
    id: number;
    mode: string;
    ui_shell: string | null;
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

const props = defineProps<{
    learningPathTabs: LearningPathTab[];
    selectedPath: LearningPath;
    recommendedStep: RecommendedStep;
    isRecommendedStepLink: boolean;
    recommendedHeroStyle: Record<string, string>;
    learningDashboard: LearningDashboard;
    categoryShortName: string;
    recommendedTopic: GroupOption | null;
    canUseFullProduct: boolean;
    showPjmEntryTile: boolean;
    pjmModuleHref: string;
    pjmSymbolSrc: string;
    selectedQuestionCount: number;
    filteredGroupOptions: GroupBucket[];
    mobileClassicStatusOptions: StatusOption[];
    sessionQuestionTopicId: number | null;
    sessionQuestionStatus: string;
    sessionProcessing: boolean;
    globalIncorrectProcessing: boolean;
    canStartLearning: boolean;
    startButtonLabel: string;
    totalAnsweredQuestions: number;
    totalUnansweredQuestions: number;
    totalIncorrectQuestions: number;
    hasGlobalIncorrectQuestions: boolean;
    managedIncorrectListEnabled: boolean;
    incorrectQuestionsUrl: string;
    examFacts: ExamFact[];
    trafficSignLearningHref: string;
    categoryStatsHref: string;
    rankingModeHref: string;
    rankingPreview: RankingPreview;
    errors: {
        license_category_id?: string;
        question_topic_id?: string;
        question_status?: string;
    };
}>();

const mobileAppBarCategory = computed(() => ({
    id: props.learningDashboard.course_progress.category_id ?? 0,
    code: props.categoryShortName,
    name: `Kategoria ${props.categoryShortName}`,
    short_name: props.categoryShortName,
    questions_count: props.learningDashboard.course_progress.total_questions,
}));

const emit = defineEmits<{
    (event: 'select-path', path: LearningPath): void;
    (event: 'start-recommended-learning'): void;
    (event: 'run-recommended-step'): void;
    (event: 'activate-full-learning'): void;
    (event: 'start-learning'): void;
    (event: 'choose-topic', topicId: number): void;
    (event: 'choose-status', status: string): void;
    (event: 'start-global-incorrect-learning'): void;
}>();

const isClassicPath = computed(() => props.selectedPath === 'classic');
const isZenPath = computed(() => props.selectedPath === 'zen');
const isExamPath = computed(() => props.selectedPath === 'exam');
const isPjmPath = computed(() => props.selectedPath === 'pjm');
const isTrafficSignsPath = computed(() => props.selectedPath === 'traffic-signs');
const isRankingPath = computed(() => props.selectedPath === 'ranking');
const activeSession = computed(() => props.learningDashboard.active_session);
const showLearningSetup = ref(false);
const showTopicPathPicker = ref(false);
const topicSearchQuery = ref('');
const pendingTopicId = ref<number | null>(null);
const selectedClassicPracticeMode = ref<ClassicPracticeMode>(
    props.sessionQuestionStatus === 'incorrect' ? 'incorrect' : 'all',
);
const quickStartTabs = computed(() =>
    props.learningPathTabs.filter((tab) =>
        tab.value !== 'classic'
        && tab.value !== 'memory'
        && tab.value !== 'zen',
    ),
);
const recommendedTopicQuestionCount = computed(() => {
    const topic = props.recommendedTopic;

    if (!topic) {
        return 0;
    }

    if ((topic.counts.unanswered ?? 0) > 0) {
        return topic.counts.unanswered;
    }

    if ((topic.counts.incorrect ?? 0) > 0) {
        return topic.counts.incorrect;
    }

    return topic.questions_count;
});
const recommendedTopicSummary = computed(() => {
    if (!props.recommendedTopic) {
        return 'Wybierz dział i rozpocznij pierwszą serię.';
    }

    const questionCount = recommendedTopicQuestionCount.value;
    const unit = questionCount === 1 ? 'pytanie' : 'pytań';

    return `${props.recommendedTopic.label} · ${questionCount} ${unit}`;
});
const selectedTopic = computed(() =>
    props.filteredGroupOptions
        .flatMap((group) => group.options)
        .find((option) => option.id === props.sessionQuestionTopicId) ?? null,
);
const pendingTopic = computed(() =>
    props.filteredGroupOptions
        .flatMap((group) => group.options)
        .find((option) => option.id === pendingTopicId.value) ?? null,
);

const normalizedTopicSearchQuery = computed(() =>
    topicSearchQuery.value
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('pl-PL'),
);

const topicPathGroups = computed(() => {
    const query = normalizedTopicSearchQuery.value;

    return props.filteredGroupOptions
        .map((group) => ({
            ...group,
            options: group.options.filter((option) => {
                if (!query) {
                    return true;
                }

                return `${option.label} ${option.key}`
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLocaleLowerCase('pl-PL')
                    .includes(query);
            }),
        }))
        .filter((group) => group.options.length > 0);
});

const fieldError = computed(() =>
    props.errors.license_category_id
    ?? props.errors.question_topic_id
    ?? props.errors.question_status
    ?? null,
);

const courseProgress = computed(() => props.learningDashboard.course_progress);
const courseProgressPercent = computed(() => courseProgress.value.percent);
const courseCorrectCount = computed(() => courseProgress.value.correct_questions);
const courseIncorrectCount = computed(() => courseProgress.value.incorrect_questions);
const courseUnansweredCount = computed(() => courseProgress.value.unanswered_questions);
const courseProgressWidth = computed(() => `${Math.max(courseProgressPercent.value, 3)}%`);
const homePrimaryTitle = computed(() => {
    if (courseProgressPercent.value === 0) {
        return 'Zacznij naukę';
    }

    return courseUnansweredCount.value > 0 ? 'Wróć do nauki' : 'Utrwal wiedzę';
});
const homePrimaryActionLabel = computed(() =>
    courseProgressPercent.value === 0 ? 'Rozpocznij' : 'Kontynuuj',
);
const activeSessionPositionLabel = computed(() => {
    if (!activeSession.value) {
        return '';
    }

    const questionNumber = activeSession.value.progress.current_question_number;

    return questionNumber
        ? `Pytanie ${questionNumber} z ${activeSession.value.progress.total}`
        : `${activeSession.value.progress.answered} z ${activeSession.value.progress.total} pytań`;
});
const activeSessionProgressWidth = computed(() => {
    const percent = activeSession.value?.progress.percent ?? 0;

    return `${percent > 0 ? Math.max(percent, 4) : 0}%`;
});
const rankingPositionLabel = computed(() =>
    props.rankingPreview.position !== null ? String(props.rankingPreview.position) : '—',
);
const rankingRatingLabel = computed(() =>
    props.rankingPreview.rating !== null ? String(props.rankingPreview.rating) : '—',
);
const rankingMessage = computed(() =>
    props.rankingPreview.message || 'Zobacz swoją pozycję wśród najlepszych.',
);

const openLearningSetup = (path: LearningPath = 'classic') => {
    if (path === 'classic' || path === 'zen') {
        selectStudyPresentation(path);
    } else {
        emit('select-path', path);
    }

    showTopicPathPicker.value = false;
    topicSearchQuery.value = '';
    showLearningSetup.value = true;
};

const closeLearningSetup = () => {
    showTopicPathPicker.value = false;
    topicSearchQuery.value = '';
    showLearningSetup.value = false;
};

const openTopicPathPicker = () => {
    pendingTopicId.value = props.sessionQuestionTopicId;
    showTopicPathPicker.value = true;
    topicSearchQuery.value = '';
};

const closeTopicPathPicker = () => {
    showTopicPathPicker.value = false;
    topicSearchQuery.value = '';
};

const chooseTopicFromSheet = (topicId: number) => {
    pendingTopicId.value = topicId;
};

const confirmTopicFromSheet = () => {
    if (pendingTopicId.value !== null) {
        emit('choose-topic', pendingTopicId.value);
    }

    if (isZenPath.value) {
        emit('choose-status', 'all');
    } else if (selectedClassicPracticeMode.value !== 'global-incorrect') {
        emit('choose-status', selectedClassicPracticeMode.value);
    }

    closeTopicPathPicker();
};

const selectStudyPresentation = (path: 'classic' | 'zen') => {
    emit('select-path', path);

    if (path === 'classic' && selectedClassicPracticeMode.value !== 'global-incorrect') {
        emit('choose-status', selectedClassicPracticeMode.value);
    }
};

const chooseClassicPracticeMode = (mode: ClassicPracticeMode) => {
    selectedClassicPracticeMode.value = mode;

    if (mode !== 'global-incorrect') {
        emit('choose-status', mode);
    }
};

const startFromSetup = () => {
    closeLearningSetup();

    if (isClassicPath.value && selectedClassicPracticeMode.value === 'global-incorrect') {
        emit('start-global-incorrect-learning');
        return;
    }

    emit('start-learning');
};

const quickStartAccentClass = (path: LearningPath) => {
    if (path === 'exam') {
        return 'bg-[#f59e0b]';
    }

    if (path === 'traffic-signs') {
        return 'bg-[#2563eb]';
    }

    if (path === 'ranking') {
        return 'bg-[#6d5bd0]';
    }

    if (path === 'zen') {
        return 'bg-[#111827]';
    }

    if (path === 'pjm') {
        return 'bg-[#0ea5e9]';
    }

    return 'bg-[#023ea4]';
};

const topicIconLabel = (option: GroupOption) =>
    option.label
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();

const topicShortLabel = (option: GroupOption) =>
    option.label
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .join(' ');

const topicAnsweredCount = (option: GroupOption) =>
    Math.max((option.counts.all ?? option.questions_count) - (option.counts.unanswered ?? option.questions_count), 0);

const topicProgressPercent = (option: GroupOption) => {
    if (option.questions_count <= 0) {
        return 0;
    }

    return Math.min(Math.round((topicAnsweredCount(option) / option.questions_count) * 100), 100);
};

const topicPathStateLabel = (option: GroupOption) => {
    const selectedTopicId = showTopicPathPicker.value
        ? pendingTopicId.value
        : props.sessionQuestionTopicId;

    if (option.id === selectedTopicId) {
        return 'Wybrany';
    }

    const progress = topicProgressPercent(option);

    if (progress === 0) {
        return 'Nowy';
    }

    return progress === 100 ? 'Przerobiony' : `${progress}% przerobione`;
};

const topicPathStateClass = (option: GroupOption) => {
    const selectedTopicId = showTopicPathPicker.value
        ? pendingTopicId.value
        : props.sessionQuestionTopicId;

    if (option.id === selectedTopicId) {
        return 'bg-[#eaf1ff] text-[#023ea4]';
    }

    return topicProgressPercent(option) === 0
        ? 'bg-[#f1f5f9] text-[#475569]'
        : 'bg-[#e8f8ee] text-[#15803d]';
};

const topicIconKind = (option: GroupOption) => {
    const value = `${option.key} ${option.label}`.toLocaleLowerCase('pl-PL');

    if (/znak|sign/.test(value)) {
        return 'sign';
    }

    if (/skrzyż|skrzyz|rondo|intersection/.test(value)) {
        return 'intersection';
    }

    if (/pies|rower|pedestrian|cycl/.test(value)) {
        return 'pedestrian';
    }

    if (/pierwsz|wypad|pomoc|accident/.test(value)) {
        return 'safety';
    }

    return 'road';
};

const countForStatus = (status: ClassicPracticeMode) => {
    if (status === 'global-incorrect') {
        return props.totalIncorrectQuestions;
    }

    if (!selectedTopic.value) {
        return props.selectedQuestionCount;
    }

    return status === 'all'
        ? selectedTopic.value.questions_count
        : selectedTopic.value.counts[status] ?? 0;
};

const classicModeOptions = computed(() => [
    {
        value: 'all' as ClassicPracticeMode,
        title: 'Wszystkie pytania',
        description: 'Pełny trening wybranego działu.',
        count: countForStatus('all'),
        icon: 'K',
        iconClass: 'bg-[#eaf1ff] text-[#023ea4]',
    },
    {
        value: 'incorrect' as ClassicPracticeMode,
        title: 'Tylko błędy',
        description: 'Błędne odpowiedzi z tego działu.',
        count: countForStatus('incorrect'),
        icon: '×',
        iconClass: 'bg-[#ffe7ea] text-[#dc2626]',
    },
    {
        value: 'global-incorrect' as ClassicPracticeMode,
        title: 'Błędy z całego kursu',
        description: 'Błędne odpowiedzi z całego kursu.',
        count: countForStatus('global-incorrect'),
        icon: 'B',
        iconClass: 'bg-[#e8f8ee] text-[#16a34a]',
    },
]);

const selectedModeSummary = computed(() =>
    classicModeOptions.value.find((option) => option.value === selectedClassicPracticeMode.value)
        ?? classicModeOptions.value[0],
);

const selectedStartTitle = computed(() =>
    selectedClassicPracticeMode.value === 'global-incorrect'
        ? 'Błędy z całego kursu'
        : selectedTopic.value ? topicShortLabel(selectedTopic.value) : 'Wybierz dział',
);

const selectedStartDetails = computed(() =>
    `${selectedModeSummary.value.count} pytań`,
);
const setupScreenTitle = computed(() => {
    if (isExamPath.value) {
        return 'Egzamin próbny';
    }

    if (isRankingPath.value) {
        return 'Ranking';
    }

    if (isTrafficSignsPath.value) {
        return 'Znaki drogowe';
    }

    if (isPjmPath.value) {
        return 'Moduł PJM';
    }

    return 'Nowa seria';
});
const setupScreenSubtitle = computed(() =>
    isExamPath.value
        ? 'Sprawdź warunki i rozpocznij egzamin'
        : `Nauka kategorii ${props.categoryShortName}`,
);
const setupFooterTitle = computed(() => {
    if (isExamPath.value) {
        return 'Egzamin próbny';
    }

    if (isZenPath.value) {
        return selectedTopic.value ? topicShortLabel(selectedTopic.value) : 'Wybierz dział';
    }

    return selectedStartTitle.value;
});
const setupFooterDetails = computed(() => {
    if (isExamPath.value) {
        return '32 pytania · 25 min';
    }

    if (isZenPath.value) {
        return selectedTopic.value
            ? `${selectedTopic.value.questions_count} pytań`
            : `${props.selectedQuestionCount} pytań`;
    }

    return selectedStartDetails.value;
});
const setupFooterIcon = computed(() => {
    if (isExamPath.value) {
        return 'E';
    }

    if (isZenPath.value) {
        return selectedTopic.value ? topicIconLabel(selectedTopic.value) : 'Z';
    }

    return selectedClassicPracticeMode.value === 'global-incorrect'
        ? 'B'
        : (selectedTopic.value ? topicIconLabel(selectedTopic.value) : 'D');
});

const setupStartDisabled = computed(() => {
    if (isClassicPath.value && selectedClassicPracticeMode.value === 'global-incorrect') {
        return props.globalIncorrectProcessing || !props.hasGlobalIncorrectQuestions;
    }

    return props.sessionProcessing || (props.canUseFullProduct && !props.canStartLearning);
});

const setupStartLabel = computed(() => {
    if (
        props.sessionProcessing
        || (isClassicPath.value && selectedClassicPracticeMode.value === 'global-incorrect' && props.globalIncorrectProcessing)
    ) {
        return 'Uruchamianie...';
    }

    return isExamPath.value ? 'Rozpocznij egzamin' : 'Rozpocznij serię';
});

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key !== 'Escape') {
        return;
    }

    if (showTopicPathPicker.value) {
        closeTopicPathPicker();
        return;
    }

    if (showLearningSetup.value) {
        closeLearningSetup();
    }
};

watch(
    () => props.sessionQuestionStatus,
    (status) => {
        if (status === 'all' || status === 'incorrect') {
            selectedClassicPracticeMode.value = status;
        }
    },
);

watch(showLearningSetup, (isOpen) => {
    if (!isOpen) {
        closeTopicPathPicker();
    }

    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <section class="mobile-learning-dashboard bg-white px-4 pb-3 pt-1.5 md:hidden" aria-label="Mobilny panel nauki">
        <MobileLearningAppBar
            class="-mx-4 mb-1"
            :category="mobileAppBarCategory"
        />

        <div class="space-y-6">
            <section
                v-if="activeSession"
                class="relative overflow-hidden rounded-lg bg-[#064f9e] px-5 py-5 text-white shadow-[0_14px_30px_rgba(15,23,42,0.14)]"
                aria-label="Aktywna sesja"
            >
                <img
                    :src="mobileLearningHeroRoadCar"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 right-0 h-[96%] w-[56%] object-contain object-right-bottom opacity-80"
                >
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,#064f9e_0%,#0752d5_50%,rgba(7,82,213,0.28)_72%,rgba(7,82,213,0)_100%)]" />

                <div class="relative max-w-[13.5rem]">
                    <div class="min-w-0">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-white/82">
                            Aktywna sesja
                        </p>
                        <h2 class="mt-1 text-[1.6rem] font-semibold leading-tight tracking-[0] text-white">
                            {{ activeSession.title }}
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-white/88">
                            {{ activeSessionPositionLabel }}
                        </p>
                    </div>

                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/22">
                        <div
                            class="h-full rounded-full bg-[#b7ff20]"
                            :style="{ width: activeSessionProgressWidth }"
                        />
                    </div>

                    <Link
                        :href="activeSession.resume_url"
                        class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-white px-4 text-sm font-semibold text-[#064f9e] shadow-[0_10px_20px_rgba(5,11,46,0.2)] transition hover:bg-[#eef6ff] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                    >
                        Wróć do sesji
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>

                <button
                    type="button"
                    class="relative mt-3 inline-flex min-h-9 items-center text-xs font-semibold text-white/90 underline decoration-white/45 underline-offset-4 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                    @click="openLearningSetup('classic')"
                >
                    Ustaw nową serię
                </button>
            </section>

            <section
                v-else
                class="relative min-h-[14.5rem] overflow-hidden rounded-lg bg-[#064f9e] px-5 py-5 text-white shadow-[0_14px_30px_rgba(15,23,42,0.14)]"
                aria-label="Rozpocznij naukę"
            >
                <img
                    :src="mobileLearningHeroRoadCar"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 right-0 h-[96%] w-[56%] object-contain object-right-bottom opacity-80"
                >
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,#064f9e_0%,#0752d5_50%,rgba(7,82,213,0.28)_72%,rgba(7,82,213,0)_100%)]" />

                <div class="relative max-w-[13.5rem]">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-white/82">
                        Następna seria · Kategoria {{ categoryShortName }}
                    </p>
                    <h2 class="mt-1 text-[1.6rem] font-semibold leading-tight tracking-[0] text-white">
                        {{ homePrimaryTitle }}
                    </h2>
                    <p class="mt-2 text-sm leading-5 text-white/88">
                        {{ recommendedTopicSummary }}
                    </p>

                    <button
                        type="button"
                        class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-white px-4 text-sm font-semibold text-[#064f9e] shadow-[0_10px_20px_rgba(5,11,46,0.2)] transition hover:bg-[#eef6ff] disabled:cursor-not-allowed disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                        :disabled="sessionProcessing || !recommendedTopic"
                        @click="emit('start-recommended-learning')"
                    >
                        <span>{{ homePrimaryActionLabel }}</span>
                        <span aria-hidden="true">→</span>
                    </button>

                    <button
                        type="button"
                        class="mt-2 inline-flex min-h-10 w-full items-center justify-center text-xs font-semibold text-white/90 underline decoration-white/45 underline-offset-4 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#064f9e]"
                        @click="openLearningSetup('classic')"
                    >
                        Zmień dział lub tryb
                    </button>
                </div>
            </section>

            <section v-if="quickStartTabs.length" aria-labelledby="mobile-learning-modes-title">
                <div>
                    <h2 id="mobile-learning-modes-title" class="text-base font-semibold leading-tight text-[#050b2e]">
                        Inne tryby
                    </h2>
                    <p class="mt-1 text-xs leading-4 text-[#64748b]">
                        Sprawdź wiedzę w innym formacie.
                    </p>
                </div>

                <div
                    class="mt-3 grid gap-2"
                    :class="quickStartTabs.length === 1 ? 'grid-cols-1' : 'grid-cols-2'"
                >
                    <button
                        v-for="tab in quickStartTabs"
                        :key="tab.value"
                        type="button"
                        class="group min-h-[5.65rem] rounded-lg border bg-white p-2.5 text-left shadow-[0_8px_18px_rgba(15,23,42,0.045)] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                        :class="tab.disabled
                            ? 'cursor-not-allowed border-[#e0e7ef] opacity-45'
                            : 'border-[#e0e7ef] hover:-translate-y-0.5 hover:border-[#9fb6d8] hover:shadow-[0_18px_34px_rgba(15,23,42,0.09)]'"
                        :disabled="tab.disabled"
                        @click="openLearningSetup(tab.value)"
                    >
                        <span class="flex items-start justify-between gap-3">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white shadow-[0_8px_14px_rgba(15,23,42,0.1)]"
                                :class="quickStartAccentClass(tab.value)"
                                aria-hidden="true"
                            >
                                <span class="text-xs font-semibold">
                                    {{ tab.label.slice(0, 1) }}
                                </span>
                            </span>
                            <span class="text-lg font-light leading-none text-[#64748b] transition group-hover:translate-x-0.5">
                                →
                            </span>
                        </span>

                        <span class="mt-2 block text-[0.82rem] font-semibold leading-4 text-[#0f172a]">
                            {{ tab.label }}
                        </span>
                        <span class="mt-0.5 block text-[0.68rem] leading-3.5 text-[#64748b]">
                            {{ tab.summary }}
                        </span>
                    </button>
                </div>
            </section>

            <Teleport to="body">
            <Transition name="mobile-learning-sheet">
                <div
                    v-if="showLearningSetup"
                    class="fixed inset-0 z-[70] md:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="mobile-learning-setup-title"
                >
                    <form
                        class="absolute inset-0 flex min-h-[100svh] max-h-[100svh] flex-col overflow-hidden bg-[#f2f4f7]"
                        @submit.prevent="startFromSetup"
                    >
                        <header class="shrink-0 border-b border-[#e2e8f0] bg-white px-4 pb-3 pt-[max(env(safe-area-inset-top),0.75rem)]">
                            <div class="flex min-h-11 items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-[#023ea4] transition hover:bg-[#f1f5f9] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                    aria-label="Wróć do panelu nauki"
                                    @click="closeLearningSetup"
                                >
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m14.5 5.5-6.5 6.5 6.5 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="min-w-0 flex-1">
                                    <h2 id="mobile-learning-setup-title" class="truncate text-[1.12rem] font-semibold leading-6 text-[#050b2e]">
                                        {{ setupScreenTitle }}
                                    </h2>
                                    <p class="truncate text-[0.72rem] leading-4 text-[#64748b]">
                                        {{ setupScreenSubtitle }}
                                    </p>
                                </div>
                            </div>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-5 pt-4">
                            <div v-if="isClassicPath || isZenPath" class="space-y-6">
                                <section aria-labelledby="mobile-learning-topic-title">
                                    <h3 id="mobile-learning-topic-title" class="mb-2 px-1 text-[0.78rem] font-medium text-[#667085]">
                                        Dział
                                    </h3>

                                    <button
                                        type="button"
                                        class="flex min-h-[4.75rem] w-full items-center gap-3 rounded-lg border border-[#e0e7ef] bg-white px-3 py-2.5 text-left shadow-[0_2px_6px_rgba(15,23,42,0.04)] transition hover:border-[#9fc0ee] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        :aria-label="`Zmień dział: ${selectedTopic?.label ?? 'Wybierz dział'}`"
                                        @click="openTopicPathPicker"
                                    >
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eaf1ff] text-xs font-semibold text-[#023ea4]" aria-hidden="true">
                                            {{ selectedTopic ? topicIconLabel(selectedTopic) : 'D' }}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold leading-5 text-[#050b2e]">
                                                {{ selectedTopic?.label ?? 'Wybierz dział' }}
                                            </span>
                                            <span class="mt-0.5 block text-xs leading-4 text-[#64708b]">
                                                {{ selectedTopic ? `${selectedTopic.questions_count} pytań · Zmień na ścieżce działów` : 'Wybierz materiał do nauki' }}
                                            </span>
                                        </span>
                                        <svg class="h-5 w-5 shrink-0 text-[#667085]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </section>

                                <section aria-labelledby="mobile-learning-mode-title">
                                    <h3 id="mobile-learning-mode-title" class="mb-2 px-1 text-[0.78rem] font-medium text-[#667085]">
                                        Zakres pytań
                                    </h3>

                                    <div v-if="isClassicPath" class="overflow-hidden rounded-lg border border-[#e0e7ef] bg-white shadow-[0_2px_6px_rgba(15,23,42,0.04)] divide-y divide-[#eaecf0]">
                                        <button
                                            v-for="option in classicModeOptions"
                                            :key="option.value"
                                            type="button"
                                            class="flex min-h-[4.2rem] w-full items-center gap-2.5 px-3 py-2.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus-visible:relative focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#023ea4]"
                                            :class="selectedClassicPracticeMode === option.value ? 'bg-[#f8fbff]' : 'bg-white hover:bg-[#f9fafb]'"
                                            :disabled="option.value === 'global-incorrect' && !hasGlobalIncorrectQuestions"
                                            @click="chooseClassicPracticeMode(option.value)"
                                        >
                                            <span
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-base font-semibold"
                                                :class="option.iconClass"
                                                aria-hidden="true"
                                            >
                                                {{ option.icon }}
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold leading-5 text-[#050b2e]">
                                                    {{ option.title }}
                                                </span>
                                                <span class="mt-0.5 block text-[0.7rem] leading-4 text-[#667085]">
                                                    {{ option.description }}
                                                </span>
                                            </span>

                                            <span class="flex shrink-0 items-center gap-2">
                                                <span class="whitespace-nowrap text-xs font-medium text-[#3f4a66]">
                                                    {{ option.count }} pytań
                                                </span>
                                                <span
                                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full border"
                                                    :class="selectedClassicPracticeMode === option.value
                                                        ? 'border-[#0b5cff] bg-[#0b5cff] text-white'
                                                        : 'border-[#cbd5e1] bg-white'"
                                                    aria-hidden="true"
                                                >
                                                    <span v-if="selectedClassicPracticeMode === option.value" class="text-xs font-semibold">✓</span>
                                                </span>
                                            </span>
                                        </button>
                                    </div>

                                    <div v-else class="flex min-h-[4.2rem] items-center gap-3 rounded-lg border border-[#e0e7ef] bg-white px-3 py-2.5 shadow-[0_2px_6px_rgba(15,23,42,0.04)]">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#eaf1ff] text-sm font-semibold text-[#023ea4]" aria-hidden="true">W</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-[#050b2e]">Wszystkie pytania</span>
                                            <span class="mt-0.5 block text-[0.7rem] leading-4 text-[#667085]">Zen korzysta z pełnego wybranego działu.</span>
                                        </span>
                                        <span class="text-xs font-medium text-[#667085]">{{ selectedTopic?.questions_count ?? selectedQuestionCount }} pytań</span>
                                    </div>
                                </section>

                                <section aria-labelledby="mobile-learning-presentation-title">
                                    <h3 id="mobile-learning-presentation-title" class="mb-2 px-1 text-[0.78rem] font-medium text-[#667085]">Widok nauki</h3>
                                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-[#e4e7ec] p-1" role="group" aria-label="Widok nauki">
                                        <button
                                            type="button"
                                            class="min-h-10 rounded-md px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                            :class="isClassicPath ? 'bg-white text-[#101828] shadow-sm' : 'text-[#667085]'"
                                            :aria-pressed="isClassicPath"
                                            @click="selectStudyPresentation('classic')"
                                        >
                                            Standardowy
                                        </button>
                                        <button
                                            type="button"
                                            class="min-h-10 rounded-md px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                            :class="isZenPath ? 'bg-white text-[#101828] shadow-sm' : 'text-[#667085]'"
                                            :aria-pressed="isZenPath"
                                            @click="selectStudyPresentation('zen')"
                                        >
                                            Zen
                                        </button>
                                    </div>
                                </section>
                            </div>

                            <div v-else-if="isTrafficSignsPath" class="mt-8 space-y-4">
                                <p class="text-sm leading-6 text-[#3f4a66]">
                                    Najpierw rozpoznajesz znaki, potem przechodzisz do pytań egzaminacyjnych.
                                </p>
                                <Link
                                    :href="trafficSignLearningHref"
                                    class="inline-flex min-h-[3.5rem] w-full items-center justify-center rounded-[1rem] bg-[#0b5cff] px-5 text-sm font-semibold text-white transition hover:bg-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                >
                                    Trenuj znaki
                                </Link>
                            </div>

                            <div v-else-if="isPjmPath && showPjmEntryTile" class="mt-8 space-y-4">
                                <div class="flex items-center gap-4 rounded-[1.2rem] border border-[#e2e8f0] bg-[#f8fbff] p-4">
                                    <div class="flex aspect-square w-20 shrink-0 items-center justify-center rounded-[1rem] bg-white p-2">
                                        <img
                                            :src="pjmSymbolSrc"
                                            alt=""
                                            aria-hidden="true"
                                            class="h-full w-full object-contain"
                                        >
                                    </div>
                                    <p class="text-sm leading-6 text-[#3f4a66]">
                                        Ścieżka z tłumaczeniem PJM dla Twojej kategorii.
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <Link
                                        :href="pjmModuleHref"
                                        class="inline-flex min-h-[3.5rem] w-full items-center justify-center rounded-[1rem] bg-[#0b5cff] px-5 text-sm font-semibold text-white transition hover:bg-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                    >
                                        Otwórz moduł PJM
                                    </Link>
                                    <button
                                        v-if="!canUseFullProduct"
                                        type="button"
                                        class="inline-flex min-h-[3.25rem] w-full items-center justify-center rounded-[1rem] border border-[#cbd5e1] bg-white px-5 text-sm font-semibold text-[#0f172a] transition hover:bg-[#f8fafc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        @click="emit('activate-full-learning')"
                                    >
                                        Aktywuj pełną naukę
                                    </button>
                                </div>
                            </div>

                            <div v-else-if="isExamPath" class="mt-7 space-y-4">
                                <div class="rounded-[1rem] border border-[#edf2f7] bg-white px-4 py-2 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                                    <div
                                        v-for="(fact, index) in examFacts"
                                        :key="fact.label"
                                        class="flex min-h-[3.35rem] items-center justify-between gap-4"
                                        :class="index === examFacts.length - 1 ? '' : 'border-b border-[#edf2f7]'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center"
                                                :class="[
                                                    index === 0 ? 'text-[#0b5cff]' : '',
                                                    index === 1 ? 'text-[#22c55e]' : '',
                                                    index === 2 ? 'text-[#f59e0b]' : '',
                                                    index === 3 ? 'text-[#7c3aed]' : '',
                                                ]"
                                                aria-hidden="true"
                                            >
                                                <svg v-if="index === 0" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 3v4M12 17v4M3 12h4M17 12h4M7.76 7.76l2.12 2.12M14.12 14.12l2.12 2.12M16.24 7.76l-2.12 2.12M9.88 14.12l-2.12 2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" />
                                                </svg>
                                                <svg v-else-if="index === 1" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                    <path d="M9.25 9.25a3 3 0 1 1 4.94 2.29c-.97.82-1.69 1.32-1.69 2.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.5 17.25h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" />
                                                </svg>
                                                <svg v-else-if="index === 2" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 3v9h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                </svg>
                                                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" />
                                                </svg>
                                            </span>
                                            <span class="text-[0.72rem] font-medium leading-4 text-[#475569]">
                                                {{ fact.label }}
                                            </span>
                                        </div>

                                        <span class="whitespace-nowrap text-[0.76rem] font-semibold leading-4 text-[#050b2e]">
                                            {{ fact.value }}
                                        </span>
                                    </div>
                                </div>

                            </div>

                            <div v-else-if="isRankingPath" class="mt-5 space-y-5">
                                <img
                                    :src="mobileRankingPodium"
                                    alt=""
                                    class="h-auto w-full rounded-[1.1rem] object-cover shadow-[0_12px_28px_rgba(11,92,255,0.08)]"
                                    loading="eager"
                                    decoding="async"
                                >

                                <section aria-labelledby="mobile-ranking-position-title">
                                    <h3 id="mobile-ranking-position-title" class="text-base font-semibold leading-tight tracking-[-0.025em] text-[#050b2e]">
                                        Twoja pozycja
                                    </h3>

                                    <div class="mt-3 flex min-h-[4.25rem] items-center gap-3 rounded-[1rem] border border-[#e2e8f0] bg-white px-3 py-2.5 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0b5cff] text-[1.55rem] font-bold leading-none tracking-[-0.05em] text-white shadow-[0_8px_18px_rgba(11,92,255,0.18)]">
                                            {{ rankingPositionLabel }}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-base font-semibold leading-5 text-[#050b2e]">
                                                Ty
                                            </span>
                                            <span class="mt-0.5 block truncate text-xs leading-4 text-[#64708b]">
                                                {{ rankingMessage }}
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-right">
                                            <span class="block text-[1.72rem] font-bold leading-none tracking-[-0.035em] text-[#0b5cff]">
                                                {{ rankingRatingLabel }}
                                            </span>
                                            <span class="mt-1 block text-xs leading-none text-[#64708b]">
                                                punktów
                                            </span>
                                        </span>
                                    </div>
                                </section>

                                <Link
                                    :href="rankingModeHref"
                                    class="inline-flex min-h-[3.45rem] w-full items-center justify-center gap-2.5 rounded-[1rem] bg-[#0b5cff] px-4 text-white shadow-[0_12px_24px_rgba(11,92,255,0.24)] transition hover:bg-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                >
                                    <span class="flex items-center justify-center" aria-hidden="true">
                                        <svg class="h-5 w-5 text-white/90" viewBox="0 0 24 24" fill="none">
                                            <path d="M8 21h8M12 17v4M7 4h10v3.5a5 5 0 0 1-10 0V4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M7 6H4.5A1.5 1.5 0 0 0 3 7.5v.75A3.75 3.75 0 0 0 6.75 12H8M17 6h2.5A1.5 1.5 0 0 1 21 7.5v.75A3.75 3.75 0 0 1 17.25 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="whitespace-nowrap text-center text-[1.02rem] font-bold leading-none tracking-[-0.02em]">
                                        Zobacz pełny ranking
                                    </span>
                                    <span class="text-2xl font-light leading-none" aria-hidden="true">›</span>
                                </Link>
                            </div>

                            <p
                                v-if="fieldError"
                                class="mt-4 border-t border-[#e5eaf1] pt-4 text-sm font-medium text-[#b42318]"
                            >
                                {{ fieldError }}
                            </p>
                        </div>

                        <div
                            v-if="isClassicPath || isZenPath || isExamPath"
                            class="flex items-center justify-between gap-2 border-t border-[#e2e8f0] bg-white px-3 pb-[max(env(safe-area-inset-bottom),0.65rem)] pt-2.5 shadow-[0_-10px_24px_rgba(15,23,42,0.06)]"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-1.5 rounded-[0.7rem] bg-[#f8fbff] px-1.5 py-1">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#eaf1ff] text-[0.64rem] font-semibold text-[#023ea4]" aria-hidden="true">
                                    {{ setupFooterIcon }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-[0.7rem] font-semibold leading-4 text-[#050b2e]">
                                        {{ setupFooterTitle }}
                                    </span>
                                    <span class="block truncate text-[0.7rem] leading-4 text-[#3f4a66]">
                                        {{ setupFooterDetails }}
                                    </span>
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex h-12 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-[#0b5cff] px-4 text-[0.82rem] font-semibold text-white shadow-[0_7px_14px_rgba(11,92,255,0.18)] transition hover:bg-[#023ea4] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="setupStartDisabled"
                            >
                                <span>{{ setupStartLabel }}</span>
                                <span class="text-lg font-light leading-none" aria-hidden="true">→</span>
                            </button>
                        </div>

                        <Transition name="mobile-learning-push">
                            <section
                                v-if="showTopicPathPicker"
                                class="absolute inset-0 z-20 flex min-h-full flex-col bg-[#f8fafc]"
                                aria-labelledby="mobile-topic-path-title"
                            >
                            <header class="border-b border-[#e2e8f0] bg-white px-4 pb-3 pt-[max(env(safe-area-inset-top),0.75rem)]">
                                <div class="flex min-h-11 items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-11 items-center gap-1 rounded-lg px-1.5 text-sm font-semibold text-[#023ea4] transition hover:bg-[#f1f5f9] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        aria-label="Wróć do ustawień nauki"
                                        @click="closeTopicPathPicker"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m14.5 5.5-6.5 6.5 6.5 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        Wróć
                                    </button>
                                    <h2 id="mobile-topic-path-title" class="min-w-0 flex-1 text-lg font-semibold leading-tight text-[#050b2e]">
                                        Ścieżka działów
                                    </h2>
                                </div>

                                <label class="relative mt-2 block">
                                    <span class="sr-only">Szukaj działu</span>
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#64748b]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="10.8" cy="10.8" r="5.8" stroke="currentColor" stroke-width="2" />
                                        <path d="m15.2 15.2 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <input
                                        v-model="topicSearchQuery"
                                        type="search"
                                        class="h-11 w-full rounded-lg border border-[#cbd9ea] bg-[#f8fafc] py-2 pl-10 pr-3 text-sm text-[#050b2e] placeholder:text-[#7c8798] focus:border-[#0b5cff] focus:outline-none focus:ring-2 focus:ring-[#0b5cff]/20"
                                        placeholder="Szukaj działu"
                                    >
                                </label>
                            </header>

                            <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-4">
                                <div v-if="topicPathGroups.length" class="space-y-6">
                                    <section v-for="group in topicPathGroups" :key="group.label" :aria-label="group.label">
                                        <h3 class="text-xs font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                            {{ group.label }}
                                        </h3>

                                        <ol class="mt-3 space-y-2">
                                            <li
                                                v-for="(option, index) in group.options"
                                                :key="option.id"
                                                class="relative pl-12"
                                            >
                                                <span
                                                    v-if="index < group.options.length - 1"
                                                    class="absolute bottom-[-0.5rem] left-[1.28rem] top-10 w-px bg-[#cddbf0]"
                                                    aria-hidden="true"
                                                />
                                                <span
                                                    class="absolute left-0 top-3 flex h-10 w-10 items-center justify-center rounded-full border"
                                                    :class="option.id === pendingTopicId
                                                        ? 'border-[#0b5cff] bg-[#eaf1ff] text-[#023ea4]'
                                                        : 'border-[#d7e0eb] bg-white text-[#475569]'"
                                                    aria-hidden="true"
                                                >
                                                    <svg v-if="topicIconKind(option) === 'sign'" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 4 21 20H3L12 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                                        <path d="M12 9v4.5M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                    <svg v-else-if="topicIconKind(option) === 'intersection'" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 3v18M4 8h16M7 8v5a2 2 0 0 0 2 2h6a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <svg v-else-if="topicIconKind(option) === 'pedestrian'" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                        <circle cx="12" cy="5.5" r="2" stroke="currentColor" stroke-width="2" />
                                                        <path d="m10 21 1-6-3-3 2-3 2 2 3-1.5M14 14l2 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <svg v-else-if="topicIconKind(option) === 'safety'" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 3.5 19 6v5.8c0 4.2-2.9 7.8-7 8.7-4.1-.9-7-4.5-7-8.7V6l7-2.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                                        <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                        <path d="M8 21V3M16 21V3M12 5v3M12 11v3M12 17v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                </span>

                                                <button
                                                    type="button"
                                                    class="w-full rounded-lg border bg-white px-3 py-3 text-left shadow-[0_5px_12px_rgba(15,23,42,0.04)] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                                    :class="option.id === pendingTopicId
                                                        ? 'border-[#0b5cff] bg-[#f8fbff]'
                                                        : 'border-[#e0e7ef] hover:border-[#a9bed9]'"
                                                    :aria-pressed="option.id === pendingTopicId"
                                                    @click="chooseTopicFromSheet(option.id)"
                                                >
                                                    <span class="flex items-start justify-between gap-3">
                                                        <span class="min-w-0">
                                                            <span class="block text-sm font-semibold leading-5 text-[#050b2e]">
                                                                {{ option.label }}
                                                            </span>
                                                            <span class="mt-0.5 block text-xs leading-4 text-[#64708b]">
                                                                {{ option.questions_count }} pytań
                                                            </span>
                                                        </span>
                                                        <span class="shrink-0 rounded-full px-2 py-1 text-[0.68rem] font-semibold leading-none" :class="topicPathStateClass(option)">
                                                            {{ topicPathStateLabel(option) }}
                                                        </span>
                                                    </span>

                                                    <span class="mt-3 block h-1.5 overflow-hidden rounded-full bg-[#e7edf5]" aria-hidden="true">
                                                        <span
                                                            class="block h-full rounded-full bg-[#0b5cff]"
                                                            :style="{ width: `${topicProgressPercent(option)}%` }"
                                                        />
                                                    </span>
                                                </button>
                                            </li>
                                        </ol>
                                    </section>
                                </div>

                                <div v-else class="px-3 py-14 text-center">
                                    <p class="text-sm font-semibold text-[#050b2e]">Nie znaleziono działu</p>
                                    <p class="mt-1 text-xs leading-5 text-[#64748b]">Spróbuj innej nazwy lub wyczyść wyszukiwanie.</p>
                                </div>
                            </div>

                            <footer class="border-t border-[#e2e8f0] bg-white px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold leading-5 text-[#050b2e]">
                                            {{ pendingTopic?.label ?? 'Wybierz dział' }}
                                        </span>
                                        <span class="block text-xs leading-4 text-[#64708b]">
                                            {{ pendingTopic ? `${pendingTopic.questions_count} pytań` : 'Materiał do nauki' }}
                                        </span>
                                    </span>
                                    <button
                                        type="button"
                                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-[#0b5cff] px-4 text-sm font-semibold text-white shadow-[0_7px_14px_rgba(11,92,255,0.18)] transition hover:bg-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2"
                                        :disabled="pendingTopicId === null"
                                        @click="confirmTopicFromSheet"
                                    >
                                        Wybierz dział
                                    </button>
                                </div>
                            </footer>
                            </section>
                        </Transition>
                    </form>
                </div>
            </Transition>
            </Teleport>

            <section
                v-if="managedIncorrectListEnabled && hasGlobalIncorrectQuestions"
                class="rounded-lg border border-[#f1b8b2] bg-white px-4 py-3 shadow-[0_8px_22px_rgba(15,23,42,0.05)]"
                aria-labelledby="mobile-incorrect-questions-title"
            >
                <div class="flex items-start justify-between gap-4">
                    <span class="min-w-0">
                        <span id="mobile-incorrect-questions-title" class="block text-sm font-semibold text-[#071b33]">Pytania do poprawy</span>
                        <span class="mt-0.5 block text-xs leading-4 text-[#667085]">Zostają na liście, dopóki sam ich nie usuniesz.</span>
                    </span>
                    <strong class="shrink-0 text-lg text-[#c53d32]">{{ totalIncorrectQuestions }}</strong>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <Link
                        :href="incorrectQuestionsUrl"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-[#df8f86] bg-white px-3 text-sm font-semibold text-[#b9382f] transition hover:bg-[#fff7f6] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                    >
                        Przejrzyj listę
                    </Link>
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-[#c53d32] bg-[#c53d32] px-3 text-sm font-semibold text-white transition hover:bg-[#a92f28] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="globalIncorrectProcessing || sessionProcessing"
                        @click="emit('start-global-incorrect-learning')"
                    >
                        {{ globalIncorrectProcessing ? 'Uruchamianie…' : 'Powtórz pytania' }}
                    </button>
                </div>
            </section>

            <section class="border-y border-[#e7edf4] py-5" aria-label="Postęp kursu">
                <Link
                    :href="categoryStatsHref"
                    class="flex items-end justify-between gap-3 rounded-[0.5rem] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                    aria-label="Zobacz szczegółowy postęp kursu"
                >
                    <div>
                        <h2 class="text-base font-semibold leading-tight text-[#050b2e]">
                            Postęp kursu
                        </h2>
                        <p class="mt-1 text-xs leading-4 text-[#64748b]">
                            Wszystkie działy kategorii {{ categoryShortName }}.
                        </p>
                    </div>
                    <span class="flex shrink-0 items-center gap-1.5 text-[#050b2e]">
                        <span class="text-2xl font-semibold leading-none">{{ courseProgressPercent }}%</span>
                        <svg aria-hidden="true" class="h-4 w-4 text-[#64748b]" viewBox="0 0 24 24" fill="none">
                            <path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </Link>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#e8eef6]" aria-hidden="true">
                    <div
                        class="h-full rounded-full bg-[#0b5cff] transition-[width] duration-300"
                        :style="{ width: courseProgressWidth }"
                    />
                </div>

                <div class="mt-4">

                    <div class="border-l border-[#e5eaf1] pl-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#22c55e] text-[#16a34a]" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                                        <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[0.85rem] font-semibold leading-4 text-[#050b2e]">
                                        {{ courseCorrectCount }}
                                    </span>
                                    <span class="block text-[0.64rem] leading-3 text-[#475569]">
                                        poprawnych
                                    </span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#ef4444] text-[#ef4444]" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                                        <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[0.85rem] font-semibold leading-4 text-[#050b2e]">
                                        {{ courseIncorrectCount }}
                                    </span>
                                    <span class="block text-[0.64rem] leading-3 text-[#475569]">
                                        błędnych
                                    </span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#0b5cff] text-[#0b5cff]" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[0.85rem] font-semibold leading-4 text-[#050b2e]">
                                        {{ courseUnansweredCount }}
                                    </span>
                                    <span class="block text-[0.64rem] leading-3 text-[#475569]">
                                        do przerobienia
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </section>
</template>

<style scoped>
.mobile-learning-dashboard {
    animation: mobile-learning-dashboard-in 160ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.mobile-learning-sheet-enter-active,
.mobile-learning-sheet-leave-active {
    transition: opacity 180ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.mobile-learning-sheet-enter-active form,
.mobile-learning-sheet-leave-active form {
    transition: transform 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.mobile-learning-sheet-enter-from,
.mobile-learning-sheet-leave-to {
    opacity: 0;
}

.mobile-learning-sheet-enter-from form,
.mobile-learning-sheet-leave-to form {
    transform: translateX(1.5rem);
}

.mobile-learning-push-enter-active,
.mobile-learning-push-leave-active {
    transition: transform 220ms cubic-bezier(0.2, 0.8, 0.2, 1), opacity 160ms ease;
}

.mobile-learning-push-enter-from,
.mobile-learning-push-leave-to {
    opacity: 0;
    transform: translateX(2rem);
}

@keyframes mobile-learning-dashboard-in {
    from {
        opacity: 0.94;
    }

    to {
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mobile-learning-dashboard,
    .mobile-learning-dashboard * {
        animation: none !important;
        transition: none !important;
    }
}
</style>
