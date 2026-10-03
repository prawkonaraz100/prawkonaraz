<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import type { LearningProgressMessage } from '@/types/learningProgress';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MobileLearningAppBar from '@/Pages/Session/Partials/MobileLearningAppBar.vue';
import { topicArtworkForKey } from '@/lib/topicArtwork';
import mobileDashboardHero from '../../../../images/session/mobile-dashboard-hero.png';
import mobileRankingPodium from '../../../../images/session/mobile-ranking-podium.png';

type LearningPath = 'pjm' | 'traffic-signs' | 'classic' | 'zen' | 'exam' | 'memory' | 'ranking';
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
    hero_image_url?: string | null;
    hero_image_position?: string | null;
}

interface GroupBucket {
    label: string;
    options: GroupOption[];
}

interface StatusOption {
    value: string;
    label: string;
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
    progress_message: LearningProgressMessage;
    course_progress: {
        category_id: number | null;
        answered_questions: number;
        unanswered_questions: number;
        incorrect_questions: number;
        correct_questions: number;
        total_questions: number;
        percent: number;
        completed_topic_ids: number[];
        completed_topics: number;
        total_topics: number;
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
    learningDashboard: LearningDashboard;
    categoryShortName: string;
    canUseFullProduct: boolean;
    showPjmEntryTile: boolean;
    pjmModuleHref: string;
    pjmSymbolSrc: string;
    selectedQuestionCount: number;
    allGroupOptions: GroupBucket[];
    filteredGroupOptions: GroupBucket[];
    sessionQuestionScope: 'all' | 'basic' | 'specialist';
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

const page = usePage<PageProps>();
const firstName = computed(() => page.props.auth.user?.name?.trim().split(/\s+/)[0] || 'Kursancie');

const emit = defineEmits<{
    (event: 'select-path', path: LearningPath): void;
    (event: 'activate-full-learning'): void;
    (event: 'start-learning'): void;
    (event: 'start-quick-learning'): void;
    (event: 'start-topic-learning', topicId: number): void;
    (event: 'choose-topic', topicId: number): void;
    (event: 'choose-scope', scope: 'all' | 'basic' | 'specialist'): void;
    (event: 'choose-status', status: string): void;
    (event: 'start-global-incorrect-learning'): void;
}>();

const isClassicPath = computed(() => props.selectedPath === 'classic');
const questionScopeTabs = [
    { value: 'all', label: 'Wszystkie' },
    { value: 'basic', label: 'Podstawowe' },
    { value: 'specialist', label: 'Specjalistyczne' },
] as const;
const isZenPath = computed(() => props.selectedPath === 'zen');
const isExamPath = computed(() => props.selectedPath === 'exam');
const isPjmPath = computed(() => props.selectedPath === 'pjm');
const isTrafficSignsPath = computed(() => props.selectedPath === 'traffic-signs');
const isRankingPath = computed(() => props.selectedPath === 'ranking');
const activeSession = computed(() => props.learningDashboard.active_session);
const showLearningSetup = ref(false);
const showTopicPathPicker = ref(false);
const topicPickerFromSetup = ref(false);
const selectedClassicPracticeMode = ref<ClassicPracticeMode>(
    props.sessionQuestionStatus === 'incorrect' ? 'incorrect' : 'all',
);
const quickStartTabs = computed(() =>
    props.learningPathTabs.filter((tab) =>
        tab.value !== 'classic'
        && tab.value !== 'exam'
        && tab.value !== 'memory'
        && tab.value !== 'zen',
    ),
);
const canOpenClassic = computed(() => !props.learningPathTabs.find((tab) => tab.value === 'classic')?.disabled);
const canOpenExam = computed(() => !props.learningPathTabs.find((tab) => tab.value === 'exam')?.disabled);
const reviewShortcutCount = computed(() => props.managedIncorrectListEnabled
    ? props.totalIncorrectQuestions
    : props.learningDashboard.review.due_count);
const reviewShortcutHref = computed(() => props.managedIncorrectListEnabled
    ? props.incorrectQuestionsUrl
    : route('review-queue.index'));
const selectedTopic = computed(() =>
    props.filteredGroupOptions
        .flatMap((group) => group.options)
        .find((option) => option.id === props.sessionQuestionTopicId) ?? null,
);
const topicPathGroups = computed(() =>
    props.filteredGroupOptions.filter((group) => group.options.length > 0),
);

const fieldError = computed(() =>
    props.errors.license_category_id
    ?? props.errors.question_topic_id
    ?? props.errors.question_status
    ?? null,
);

const courseProgress = computed(() => props.learningDashboard.course_progress);
const courseProgressPercent = computed(() => courseProgress.value.percent);
const featuredTopicKeys = [
    'warning_signs',
    'prohibition_and_mandatory_signs',
    'informational_direction_and_supplementary_signs',
    'traffic_lights_and_controller_signals',
];
const popularTopics = computed(() => {
    const topics = props.allGroupOptions.flatMap((group) => group.options);
    const featured = featuredTopicKeys
        .map((key) => topics.find((topic) => topic.key === key))
        .filter((topic): topic is GroupOption => Boolean(topic));

    return [...featured, ...topics.filter((topic) => !featured.some((item) => item.id === topic.id))].slice(0, 4);
});
const activeSessionPositionLabel = computed(() => {
    if (!activeSession.value) {
        return '';
    }

    const questionNumber = activeSession.value.progress.current_question_number;

    return questionNumber
        ? `Pytanie ${questionNumber} z ${activeSession.value.progress.total}`
        : `${activeSession.value.progress.answered} z ${activeSession.value.progress.total} pytań`;
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
    showLearningSetup.value = true;
};

const closeLearningSetup = () => {
    showTopicPathPicker.value = false;
    showLearningSetup.value = false;
};

const leaveLearningSetup = () => {
    closeLearningSetup();

    if (new URL(page.url, 'https://prawkonaraz.pl').searchParams.has('widok')) {
        router.visit(route('session.index'), { replace: true });
    }
};

const openTopicPathPicker = () => {
    topicPickerFromSetup.value = true;
    showTopicPathPicker.value = true;
};

const openTopicsShortcut = () => {
    if (canOpenClassic.value) {
        router.visit(`${route('session.index')}?widok=dzialy`);
    } else if (props.showPjmEntryTile) {
        openLearningSetup('pjm');
    } else {
        emit('activate-full-learning');
    }
};

const openExamShortcut = () => {
    if (canOpenExam.value) {
        router.visit(`${route('session.index')}?widok=testy`);
    } else {
        emit('activate-full-learning');
    }
};

const closeTopicPathPicker = () => {
    showTopicPathPicker.value = false;
};

const chooseTopicFromSheet = (topicId: number) => {
    if (topicPickerFromSetup.value) {
        emit('choose-topic', topicId);
        if (isZenPath.value) {
            emit('choose-status', 'all');
        } else if (selectedClassicPracticeMode.value !== 'global-incorrect') {
            emit('choose-status', selectedClassicPracticeMode.value);
        }
        closeTopicPathPicker();
        return;
    }

    if (props.sessionProcessing || !confirmReplacingActiveSession()) {
        return;
    }

    closeLearningSetup();
    emit('start-topic-learning', topicId);
};

const confirmReplacingActiveSession = () =>
    !activeSession.value || window.confirm('Masz rozpoczętą sesję. Nowa seria zakończy ją przed rozpoczęciem kolejnej. Kontynuować?');

const startReviewShortcut = () => {
    if (confirmReplacingActiveSession()) {
        emit('start-global-incorrect-learning');
    }
};

const startFeaturedTopic = (topicId: number) => {
    if (!props.canUseFullProduct) {
        openTopicsShortcut();
        return;
    }

    if (!props.sessionProcessing && confirmReplacingActiveSession()) {
        emit('start-topic-learning', topicId);
    }
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
    if (!confirmReplacingActiveSession()) {
        return;
    }

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

    return Math.min(Math.round((topicAnsweredCount(option) / option.questions_count) * 100), (option.counts.unanswered ?? 0) > 0 ? 99 : 100);
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
        if (topicPickerFromSetup.value) {
            closeTopicPathPicker();
        } else {
            leaveLearningSetup();
        }
        return;
    }

    if (showLearningSetup.value) {
        leaveLearningSetup();
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
    <section class="mobile-learning-dashboard mx-auto min-h-screen max-w-[30rem] bg-[#faf9f7] px-4 pb-5 md:hidden" aria-label="Mobilny panel nauki">
        <div>
            <header class="relative -mx-4 h-[13.7rem] overflow-hidden bg-[#faf9f7]" aria-label="Powitanie">
                <img :src="mobileDashboardHero" alt="" aria-hidden="true" class="absolute bottom-0 right-0 h-auto w-[75%] max-w-none">
                <div class="pointer-events-none absolute inset-0" style="background: linear-gradient(90deg, #faf9f7 0%, rgba(250, 249, 247, .92) 30%, rgba(250, 249, 247, 0) 64%)" />
                <MobileLearningAppBar />
                <div class="absolute bottom-[1.85rem] left-5 z-10 max-w-[58%]">
                    <h1 class="break-words text-[clamp(1.9rem,7.9vw,2.3rem)] font-extrabold leading-[1.03] tracking-[-0.052em] text-[#070d19]">
                        Cześć<br>{{ firstName }}!
                    </h1>
                    <p class="mt-2.5 text-[0.8rem] leading-[1.35rem] text-[#657081]">
                        Gotowy na kolejną serię pytań?<br>Wybierz tryb nauki i działaj!
                    </p>
                </div>
            </header>

            <Link
                v-if="activeSession"
                :href="activeSession.resume_url"
                class="flex min-h-[5.65rem] items-center gap-3 rounded-[1.1rem] bg-gradient-to-br from-[#ffe59a] via-[#ffdf72] to-[#ffcd4a] px-4 py-3 text-[#101827] shadow-[0_10px_28px_rgba(202,148,31,0.10)] transition hover:brightness-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]"
            >
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-[0.95rem] bg-white/75" aria-hidden="true">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-[#ffc532] text-lg text-white">▶</span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[0.65rem] font-bold uppercase tracking-[0.12em] text-[#855414]">Szybki start</span>
                    <span class="mt-0.5 block text-[1.15rem] font-bold leading-6">Kontynuuj naukę</span>
                    <span class="mt-1 block truncate text-xs text-[#665c49]">{{ activeSession.title }} · {{ activeSessionPositionLabel }}</span>
                </span>
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-xl" aria-hidden="true">→</span>
            </Link>
            <button
                v-else
                type="button"
                class="flex min-h-[5.65rem] w-full items-center gap-3 rounded-[1.1rem] bg-gradient-to-br from-[#ffe59a] via-[#ffdf72] to-[#ffcd4a] px-4 py-3 text-left text-[#101827] shadow-[0_10px_28px_rgba(202,148,31,0.10)] transition hover:brightness-[1.02] disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]"
                :disabled="sessionProcessing || (canUseFullProduct && courseProgress.total_questions === 0)"
                @click="emit('start-quick-learning')"
            >
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-[0.95rem] bg-white/75" aria-hidden="true">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-[#ffc532] text-lg text-white">▶</span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[0.65rem] font-bold uppercase tracking-[0.12em] text-[#855414]">Szybki start</span>
                    <span class="mt-0.5 block text-[1.15rem] font-bold leading-6">{{ canUseFullProduct ? 'Rozpocznij naukę' : 'Otwórz naukę' }}</span>
                    <span class="mt-1 block text-xs text-[#665c49]">{{ canUseFullProduct ? 'Losowe pytania z całej bazy' : 'Sprawdź dostępne możliwości' }}</span>
                </span>
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-xl" aria-hidden="true">→</span>
            </button>

            <section class="mt-2.5 grid grid-cols-2 gap-2" aria-label="Główne tryby">
                <button
                    type="button"
                    class="flex min-h-[4.1rem] items-center gap-2 rounded-[1.05rem] bg-white px-3 py-2 text-left text-[#121826] shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_26px_rgba(15,23,42,0.08)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]"
                    @click="openTopicsShortcut"
                >
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-[0.85rem] bg-[#fff5df] text-[#a35a15]" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 32 32" fill="none"><path d="M16 8c-3.6-2.3-7.6-2.6-12-1.5v19c4.4-1.1 8.4-.8 12 1.5 3.6-2.3 7.6-2.6 12-1.5v-19C23.6 5.4 19.6 5.7 16 8Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/><path d="M16 8v19" stroke="currentColor" stroke-width="2.2"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[0.8rem] font-bold leading-4">Wybierz dział</span>
                        <span class="mt-0.5 block text-[0.6rem] leading-3 text-[#687385]">Ucz się według kategorii</span>
                    </span>
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#fff5df] text-base" aria-hidden="true">›</span>
                </button>
                <button
                    type="button"
                    class="flex min-h-[4.1rem] items-center gap-2 rounded-[1.05rem] bg-white px-3 py-2 text-left text-[#121826] shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_26px_rgba(15,23,42,0.08)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]"
                    @click="openExamShortcut"
                >
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-[0.85rem] bg-[#fff5df] text-[#a35a15]" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="11" stroke="currentColor" stroke-width="2.4"/><circle cx="16" cy="16" r="5" stroke="currentColor" stroke-width="2.4"/><path d="m16 16 9-9M22 7h4v4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[0.8rem] font-bold leading-4">Egzamin próbny</span>
                        <span class="mt-0.5 block text-[0.6rem] leading-3 text-[#687385]">Sprawdź swoją wiedzę</span>
                    </span>
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#fff5df] text-base" aria-hidden="true">›</span>
                </button>
            </section>

            <section id="postep" class="mt-2.5 scroll-mt-5 rounded-[1.1rem] bg-white p-2.5 shadow-[0_8px_28px_rgba(15,23,42,0.035)]" aria-labelledby="mobile-progress-title">
                <div class="flex min-h-7 items-center justify-between gap-2 px-0.5">
                    <h2 id="mobile-progress-title" class="text-[0.92rem] font-bold text-[#121826]">Twój postęp</h2>
                    <Link :href="categoryStatsHref" class="inline-flex min-h-7 items-center gap-1 text-[0.62rem] font-medium text-[#647083] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]">
                        Zobacz szczegóły <span aria-hidden="true">→</span>
                    </Link>
                </div>
                <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                    <div class="relative min-w-0 rounded-[0.85rem] bg-[#f7f8fa] p-2.5 pb-4">
                        <div class="flex items-center gap-1.5 max-[390px]:flex-col max-[390px]:items-start max-[390px]:gap-1">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#e9f9f1] text-[#17b781]" aria-hidden="true">◕</span>
                            <span class="min-w-0">
                                <span class="block text-[0.53rem] leading-3 text-[#687385]">Ukończono</span>
                                <strong class="block text-[0.95rem] leading-5 text-[#101827]">{{ courseProgressPercent }}%</strong>
                            </span>
                        </div>
                        <span class="absolute bottom-2 left-2.5 right-2.5 block h-1.5 overflow-hidden rounded-full bg-[#e5e9ed]" aria-hidden="true"><span class="block h-full rounded-full bg-[#21bc89]" :style="{ width: courseProgressPercent + '%' }" /></span>
                    </div>
                    <div class="min-w-0 rounded-[0.85rem] bg-[#f7f8fa] p-2.5">
                        <div class="flex items-center gap-1.5 max-[390px]:flex-col max-[390px]:items-start max-[390px]:gap-1">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#edf4ff] text-[#2c72e8]" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M9 7h11M9 12h11M9 17h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="4" cy="7" r="1.3" fill="currentColor"/><circle cx="4" cy="12" r="1.3" fill="currentColor"/><circle cx="4" cy="17" r="1.3" fill="currentColor"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[0.53rem] leading-3 text-[#687385]">Rozwiązane</span>
                                <strong class="block whitespace-nowrap text-[0.62rem] leading-5 text-[#101827]">{{ totalAnsweredQuestions }} / {{ courseProgress.total_questions }}</strong>
                            </span>
                        </div>
                    </div>
                    <div class="min-w-0 rounded-[0.85rem] bg-[#f7f8fa] p-2.5">
                        <div class="flex items-center gap-1.5 max-[390px]:flex-col max-[390px]:items-start max-[390px]:gap-1">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#fff7df] text-[#e4a700]" aria-hidden="true">★</span>
                            <span class="min-w-0">
                                <span class="block text-[0.53rem] leading-3 text-[#687385]">Zaliczone działy</span>
                                <strong class="block text-[0.9rem] leading-5 text-[#101827]">{{ courseProgress.completed_topics }} / {{ courseProgress.total_topics }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </section>
            <section v-if="popularTopics.length" class="mt-4" aria-labelledby="mobile-popular-title">
                <div class="flex items-center justify-between gap-3 px-1">
                    <h2 id="mobile-popular-title" class="text-[0.92rem] font-bold text-[#121826]">Popularne działy</h2>
                    <button type="button" class="inline-flex min-h-8 items-center gap-1 text-[0.62rem] font-medium text-[#647083] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]" @click="openTopicsShortcut">
                        Zobacz wszystkie <span aria-hidden="true">→</span>
                    </button>
                </div>
                <div class="mt-1.5 grid grid-cols-4 gap-1.5">
                    <button
                        v-for="topic in popularTopics"
                        :key="topic.id"
                        type="button"
                        class="flex min-h-[7rem] min-w-0 flex-col rounded-[1rem] bg-white p-2.5 text-left shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                        @click="startFeaturedTopic(topic.id)"
                    >
                        <img v-if="topicArtworkForKey(topic.key) || topic.hero_image_url" :src="topicArtworkForKey(topic.key) || topic.hero_image_url || ''" alt="" loading="lazy" class="mx-auto h-10 w-10 object-contain">
                        <span v-else class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-[#eef4fc] text-lg font-bold text-[#0b5cff]" aria-hidden="true">{{ topicIconLabel(topic) }}</span>
                        <span class="mt-1.5 line-clamp-2 min-h-[2rem] w-full text-[0.61rem] font-bold leading-4 text-[#121826]">{{ topic.label }}</span>
                        <span class="mt-auto flex w-full items-center justify-between gap-0.5 text-[0.53rem] text-[#687385]">
                            {{ topic.questions_count }} pytań
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#fff4da] text-sm text-[#352313]" aria-hidden="true">›</span>
                        </span>
                    </button>
                </div>
            </section>

            <section v-if="canUseFullProduct && reviewShortcutCount > 0" class="mt-4 flex items-center justify-between gap-3 rounded-[1.1rem] bg-white px-4 py-3" aria-label="Powtórki">
                <span class="min-w-0">
                    <strong class="block text-sm text-[#121826]">Powtórki</strong>
                    <span class="block text-xs text-[#687385]">{{ reviewShortcutCount }} pytań do utrwalenia</span>
                </span>
                <button v-if="managedIncorrectListEnabled" type="button" class="min-h-11 shrink-0 rounded-full bg-[#fff2dd] px-4 text-xs font-semibold text-[#774b11] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]" :disabled="globalIncorrectProcessing || sessionProcessing" @click="startReviewShortcut">Rozpocznij →</button>
                <Link v-else :href="reviewShortcutHref" class="inline-flex min-h-11 shrink-0 items-center rounded-full bg-[#fff2dd] px-4 text-xs font-semibold text-[#774b11] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]">Rozpocznij →</Link>
            </section>

            <details v-if="quickStartTabs.length" class="group mt-3 rounded-xl border border-[#e7ebf0] bg-white p-4">
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-[#071b33] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] [&::-webkit-details-marker]:hidden">
                    Inne tryby nauki
                    <span class="text-lg text-[#64748b] transition-transform group-open:rotate-90" aria-hidden="true">›</span>
                </summary>

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
            </details>

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
                                    @click="leaveLearningSetup"
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
                                                {{ selectedTopic ? `${selectedTopic.questions_count} pytań · Zmień dział` : 'Wybierz materiał do nauki' }}
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
                                        :aria-label="topicPickerFromSetup ? 'Wróć do ustawień nauki' : 'Wróć do ekranu startowego'"
                                        @click="topicPickerFromSetup ? closeTopicPathPicker() : leaveLearningSetup()"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m14.5 5.5-6.5 6.5 6.5 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        Wróć
                                    </button>
                                    <h2 id="mobile-topic-path-title" class="min-w-0 flex-1 text-lg font-semibold leading-tight text-[#050b2e]">
                                        Wybierz dział
                                    </h2>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-1 rounded-lg bg-[#eef2f7] p-1" role="group" aria-label="Rodzaj pytań">
                                    <button
                                        v-for="scope in questionScopeTabs"
                                        :key="scope.value"
                                        type="button"
                                        class="min-h-11 rounded-md px-1 text-[0.7rem] font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                                        :class="sessionQuestionScope === scope.value ? 'bg-[#ffd84d] text-[#071b33]' : 'text-[#475569] hover:bg-white'"
                                        :aria-pressed="sessionQuestionScope === scope.value"
                                        @click="emit('choose-scope', scope.value)"
                                    >
                                        {{ scope.label }}
                                    </button>
                                </div>

                            </header>

                            <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-4">
                                <div v-if="topicPathGroups.length" class="space-y-5">
                                    <section v-for="group in topicPathGroups" :key="group.label" :aria-label="group.label">
                                        <h3 class="text-xs font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                            {{ group.label }}
                                        </h3>

                                        <ol class="mt-3 space-y-2">
                                            <li
                                                v-for="option in group.options"
                                                :key="option.id"
                                                class="relative"
                                            >
                                                <button
                                                    type="button"
                                                    class="flex min-h-[5.5rem] w-full items-center gap-3 rounded-xl border bg-white p-2.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                                                    :class="option.id === sessionQuestionTopicId && topicPickerFromSetup
                                                        ? 'border-[#f0c33a] bg-[#fff9df]'
                                                        : 'border-[#e7ebf0] hover:border-[#a9bed9]'"
                                                    :aria-label="topicPickerFromSetup ? `Wybierz dział ${option.label}` : `Rozpocznij naukę działu ${option.label}`"
                                                    :disabled="sessionProcessing || option.questions_count === 0"
                                                    @click="chooseTopicFromSheet(option.id)"
                                                >
                                                    <img
                                                        v-if="option.hero_image_url"
                                                        :src="option.hero_image_url"
                                                        alt=""
                                                        loading="lazy"
                                                        class="h-16 w-16 shrink-0 rounded-lg object-cover"
                                                        :style="{ objectPosition: option.hero_image_position || 'center' }"
                                                    >
                                                    <span v-else class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-[#eaf2fa] text-xl font-bold text-[#0b5cff]" aria-hidden="true">
                                                        {{ topicIconLabel(option) }}
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm font-semibold leading-5 text-[#050b2e]">
                                                            {{ option.label }}
                                                        </span>
                                                        <span class="mt-0.5 block text-xs leading-4 text-[#64708b]">
                                                            {{ topicAnsweredCount(option) }} / {{ option.questions_count }} pytań
                                                        </span>
                                                        <span class="mt-2.5 block h-1.5 overflow-hidden rounded-full bg-[#e7edf5]" aria-hidden="true">
                                                            <span
                                                                class="block h-full rounded-full bg-[#10b981]"
                                                                :style="{ width: `${topicProgressPercent(option)}%` }"
                                                            />
                                                        </span>
                                                    </span>
                                                    <svg class="h-4 w-4 shrink-0 text-[#64748b]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                            </li>
                                        </ol>
                                    </section>
                                </div>

                                <div v-else class="px-3 py-14 text-center">
                                    <p class="text-sm font-semibold text-[#050b2e]">Brak działów</p>
                                    <p class="mt-1 text-xs leading-5 text-[#64748b]">Dla wybranego rodzaju pytań nie ma dostępnych działów.</p>
                                </div>
                            </div>

                            <footer v-if="!topicPickerFromSetup" class="border-t border-[#e2e8f0] bg-white px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-3">
                                <p class="text-xs text-[#64748b]">Dotknij działu, aby rozpocząć naukę.</p>
                                <button
                                    type="button"
                                    class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-[#0b5cff] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                                    @click="closeTopicPathPicker"
                                >
                                    Zmień ustawienia serii →
                                </button>
                            </footer>
                            </section>
                        </Transition>
                    </form>
                </div>
            </Transition>
            </Teleport>

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
