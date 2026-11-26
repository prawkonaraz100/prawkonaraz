<script setup lang="ts">
import MobileStreakLeaderboard from './MobileStreakLeaderboard.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import type { LearningProgressMessage } from '@/types/learningProgress';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { StreakLeader } from '@/utils/streakDuration';
import '../../../../css/barlow.css';
import MobileTopicCarousel from '@/Pages/Session/Partials/MobileTopicCarousel.vue';
import SessionReplacementDialog from '@/Components/SessionReplacementDialog.vue';
import LicenseCategoryVehicle from '@/Components/LicenseCategoryVehicle.vue';
import { topicArtworkForKey } from '@/lib/topicArtwork';
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

interface ProfessionalCourseTile {
    id: number;
    code: string;
    name: string;
    progress: { percent: number };
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
    last_topic_id?: number | null;
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
    loadArtwork: boolean;
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
    examFacts: ExamFact[];
    trafficSignLearningHref: string;
    categoryStatsHref: string;
    rankingModeHref: string;
    rankingPreview: RankingPreview;
    professionalCourses?: ProfessionalCourseTile[];
    errors: {
        license_category_id?: string;
        question_topic_id?: string;
        question_status?: string;
    };
}>();

const page = usePage<PageProps>();
const firstName = computed(() => page.props.auth.user?.name?.trim().split(/\s+/)[0] || 'Kursancie');
const avatarUrl = computed(() => page.props.auth.user?.avatar_url ?? null);
const avatarInitials = computed(() => {
    const user = page.props.auth.user;
    if (user?.avatar_initials) return user.avatar_initials;

    return (user?.name?.trim() || 'Kursant')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toLocaleUpperCase('pl-PL'))
        .join('') || 'K';
});

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
    (event: 'select-professional-course', code: string): void;
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
const pendingSessionStart = ref<(() => void) | null>(null);
const sessionStartBusy = computed(() => props.sessionProcessing || props.globalIncorrectProcessing);
const requestSessionStart = (start: () => void) => {
    if (sessionStartBusy.value || pendingSessionStart.value) return;
    if (activeSession.value) pendingSessionStart.value = start;
    else start();
};
const cancelSessionReplacement = () => { pendingSessionStart.value = null; };
const confirmSessionReplacement = () => {
    if (sessionStartBusy.value) return;
    const start = pendingSessionStart.value;
    // Clear synchronously so repeated clicks cannot start the action twice.
    pendingSessionStart.value = null;
    start?.();
};
watch(() => activeSession.value?.id, cancelSessionReplacement);
const showLearningSetup = ref(false);
const showTopicPathPicker = ref(false);
const topicPickerFromSetup = ref(false);
const selectedClassicPracticeMode = ref<ClassicPracticeMode>(
    props.sessionQuestionStatus === 'incorrect' ? 'incorrect' : 'all',
);
const canOpenClassic = computed(() => !props.learningPathTabs.find((tab) => tab.value === 'classic')?.disabled);
const canOpenExam = computed(() => !props.learningPathTabs.find((tab) => tab.value === 'exam')?.disabled);
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
const carouselTopics = computed(() => props.allGroupOptions.flatMap((group) => group.options));
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

interface ModeTile {
    key: string;
    title: string;
    href: string;
    locked: boolean;
    statLabel: string;
    statValue: string | number | null;
    iconPaths: string[];
    iconColor: string;
    iconBackground: string;
}

const streakBest = ref<number | null>(null);
const streakLeaders = ref<StreakLeader[]>([]);
let streakOverviewRequested = false;
const streakOverviewLoading = ref(false);
const topStreakLeaders = computed(() => streakLeaders.value.slice(0, 3));
const myStreakLeader = ref<StreakLeader | null>(null);
const trainingHref = computed(() => `${route('session.index')}?widok=trening`);
const professionalCourseTiles = computed(() => props.professionalCourses ?? []);
const professionalCourseAccent = (index: number) => ['#ffc94a', '#7ab8ff', '#ff9f6b', '#81b64c'][index % 4];

const loadStreakOverview = async () => {
    if (!props.canUseFullProduct || desktopViewport?.matches || streakOverviewRequested) return;

    streakOverviewRequested = true;
    streakOverviewLoading.value = true;
    try {
        const overview = await apiClient.get<{
            personal_best?: number;
            personal_best_duration_seconds?: number | null;
            personal_rank?: number | null;
            leaderboard?: StreakLeader[];
        }>(
            route('streak-challenge.state'),
            { timeoutMs: 8000 },
        );
        streakBest.value = typeof overview.personal_best === 'number' ? overview.personal_best : null;
        streakLeaders.value = Array.isArray(overview.leaderboard) ? overview.leaderboard : [];
        const user = page.props.auth.user;
        myStreakLeader.value = null;
        if (user && overview.personal_rank && streakBest.value !== null) {
            myStreakLeader.value = streakLeaders.value.find((leader) => leader.is_me) ?? {
                position: overview.personal_rank,
                name: user.name,
                score: streakBest.value,
                duration_seconds: overview.personal_best_duration_seconds ?? null,
                avatar_url: avatarUrl.value,
                initials: avatarInitials.value,
                is_me: true,
            };
        }
    } catch {
        // The home screen stays usable without the optional streak summary.
        streakOverviewRequested = false;
    } finally {
        streakOverviewLoading.value = false;
    }
};

const modeTiles = computed<ModeTile[]>(() => {
    const locked = !props.canUseFullProduct;
    const tiles: ModeTile[] = [
        {
            key: 'streak',
            title: 'Seria bez błędu',
            href: trainingHref.value,
            locked,
            statLabel: streakBest.value !== null || streakOverviewLoading.value ? 'Rekord' : 'Pobij swój rekord',
            statValue: streakOverviewLoading.value ? '–' : streakBest.value,
            iconPaths: ['M13 2L3 14h9l-1 8 10-12h-9l1-8z'],
            iconColor: '#ffc94a',
            iconBackground: '#3a2f1c',
        },
        {
            key: 'ranking',
            title: 'Ranking 1 na 1',
            href: props.rankingModeHref,
            locked,
            statLabel: props.rankingPreview.rating !== null ? 'Punkty' : 'Zagraj z innymi',
            statValue: props.rankingPreview.rating,
            iconPaths: ['M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2', 'M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z', 'M23 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
            iconColor: '#7ab8ff',
            iconBackground: '#1c2c3d',
        },
    ];

    return tiles;
});

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

    requestSessionStart(() => {
        closeLearningSetup();
        emit('start-topic-learning', topicId);
    });
};

const startFeaturedTopic = (topicId: number) => {
    if (!props.canUseFullProduct) {
        openTopicsShortcut();
        return;
    }

    requestSessionStart(() => {
        emit('start-topic-learning', topicId);
    });
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
    if (setupStartDisabled.value) return;
    requestSessionStart(() => {
        closeLearningSetup();
        if (isClassicPath.value && selectedClassicPracticeMode.value === 'global-incorrect') emit('start-global-incorrect-learning');
        else emit('start-learning');
    });
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

    // The top-layer confirmation handles Escape, not the underlying setup.
    if (pendingSessionStart.value) return;

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

let previousBodyOverflow: string | null = null;
watch([showLearningSetup, pendingSessionStart], ([isOpen, pending]) => {
    if (!isOpen) {
        closeTopicPathPicker();
    }

    if (isOpen || pending) {
        previousBodyOverflow ??= document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    } else if (previousBodyOverflow !== null) {
        document.body.style.overflow = previousBodyOverflow;
        previousBodyOverflow = null;
    }
});

const desktopViewport = typeof window !== 'undefined' ? window.matchMedia('(min-width: 768px)') : null;
const syncMobileViewport = () => {
    if (desktopViewport?.matches) {
        cancelSessionReplacement();
    } else {
        void loadStreakOverview();
    }
};
onMounted(() => {
    syncMobileViewport();
    document.addEventListener('keydown', closeOnEscape);
    desktopViewport?.addEventListener('change', syncMobileViewport);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    desktopViewport?.removeEventListener('change', syncMobileViewport);
    if (previousBodyOverflow !== null) document.body.style.overflow = previousBodyOverflow;
});
</script>

<template>
    <SessionReplacementDialog
        :show="pendingSessionStart !== null"
        :session-id="activeSession?.id"
        :session-title="activeSession?.title ?? 'Rozpoczęta sesja'"
        :session-progress="activeSessionPositionLabel"
        :busy="sessionStartBusy"
        @cancel="cancelSessionReplacement"
        @confirm="confirmSessionReplacement"
    />
    <section class="mobile-learning-dashboard mobile-home-dark min-h-screen bg-[#302e2b] px-3 pb-8 text-[#ffffffd9] md:hidden" aria-label="Mobilny panel nauki">
        <div class="mx-auto max-w-[30rem]">
            <header class="flex min-h-14 items-center justify-between gap-3 pt-[max(env(safe-area-inset-top),0.5rem)]" aria-label="Powitanie">
                <Link
                    href="/profile"
                    class="flex min-h-11 min-w-0 items-center gap-3 rounded-[5px] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffffffd9]"
                    aria-label="Przejdź do profilu"
                >
                    <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-[3px] bg-[#8a6a5c] text-sm font-bold text-white">
                        <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                        <span v-else>{{ avatarInitials }}</span>
                    </span>
                    <span class="min-w-0 truncate text-[1.0625rem] font-extrabold leading-tight text-white">Cześć, {{ firstName }}!</span>
                </Link>
                <span class="flex shrink-0 items-center gap-1.5" :aria-label="`Kategoria ${categoryShortName}`">
                    <LicenseCategoryVehicle :code="categoryShortName" class="h-6 w-10 shrink-0 brightness-0 invert opacity-[0.72]" />
                    <span class="text-[1.375rem] font-extrabold leading-none text-white">{{ categoryShortName }}</span>
                </span>
            </header>

            <section class="pk-panel mt-2 p-3" aria-labelledby="mobile-learn-title">
                <h2 id="mobile-learn-title" class="text-[1.0625rem] font-bold leading-5 text-[#ffffffd9]">Ucz się</h2>

                <Link
                    v-if="activeSession"
                    :href="activeSession.resume_url"
                    class="pk-btn-primary mt-3 flex min-h-12 items-center justify-center gap-2.5 px-4 py-1.5"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5z" /></svg>
                    <span class="min-w-0 text-center">
                        <span class="block text-[1.0625rem] font-semibold leading-5">Kontynuuj naukę</span>
                        <span class="block truncate text-xs font-medium leading-4 text-[#ffffffd9]">{{ activeSessionPositionLabel }}</span>
                    </span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="pk-btn-primary mt-3 flex min-h-12 w-full items-center justify-center gap-2.5 px-4 disabled:opacity-60"
                    :disabled="sessionProcessing || (canUseFullProduct && courseProgress.total_questions === 0)"
                    @click="emit('start-quick-learning')"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5z" /></svg>
                    <span class="text-[1.0625rem] font-semibold">{{ canUseFullProduct ? 'Ucz się' : 'Otwórz naukę' }}</span>
                    <span class="sr-only">{{ canUseFullProduct ? '— losowe pytania z całej bazy' : '— sprawdź dostępne możliwości' }}</span>
                </button>

                <div class="mt-5 flex flex-col gap-2" aria-label="Tryby nauki">
                    <button type="button" class="pk-btn-secondary flex min-h-12 w-full items-center justify-center gap-2.5 px-4 text-[1.0625rem] font-semibold" @click="openExamShortcut">
                        <svg class="h-[22px] w-[22px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="#7ab8ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
                        Egzamin próbny
                    </button>
                    <button type="button" class="pk-btn-secondary flex min-h-12 w-full items-center justify-center gap-2.5 px-4 text-[1.0625rem] font-semibold" @click="openTopicsShortcut">
                        <svg class="h-[22px] w-[22px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="#ff9f6b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" /></svg>
                        Wybierz dział
                    </button>
                    <component
                        :is="mode.locked ? 'button' : Link"
                        v-for="mode in modeTiles"
                        :key="mode.key"
                        v-bind="mode.locked ? { type: 'button' } : { href: mode.href }"
                        class="pk-btn-secondary flex min-h-12 w-full items-center justify-center gap-2.5 px-4 text-[1.0625rem] font-semibold"
                        :class="mode.locked ? 'opacity-70' : ''"
                        @click="mode.locked ? emit('activate-full-learning') : undefined"
                    >
                        <svg class="h-[22px] w-[22px] shrink-0" viewBox="0 0 24 24" fill="none" :stroke="mode.iconColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path v-for="(d, index) in mode.iconPaths" :key="index" :d="d" />
                        </svg>
                        <span class="min-w-0 truncate">{{ mode.title }}</span>
                        <span v-if="mode.statValue !== null" class="shrink-0 text-sm font-semibold tabular-nums text-[#ffffff80]">{{ mode.statLabel }} {{ mode.statValue }}</span>
                    </component>
                </div>
            </section>

            <section class="pk-tile mt-3 flex items-center gap-3 p-3" aria-labelledby="mobile-progress-title">
                <div class="relative h-[5.5rem] w-[5.5rem] shrink-0">
                    <svg class="h-full w-full" viewBox="0 0 84 84" aria-hidden="true">
                        <circle cx="42" cy="42" r="35" fill="none" stroke="rgb(255 255 255 / 10%)" stroke-width="9" />
                        <circle
                            v-if="courseProgressPercent > 0"
                            cx="42"
                            cy="42"
                            r="35"
                            pathLength="100"
                            fill="none"
                            stroke="#81b64c"
                            stroke-width="9"
                            stroke-linecap="round"
                            stroke-dasharray="100"
                            :stroke-dashoffset="100 - courseProgressPercent"
                            transform="rotate(-90 42 42)"
                        />
                    </svg>
                    <span class="absolute inset-0 grid place-items-center text-[1.375rem] font-extrabold tabular-nums text-white">{{ courseProgressPercent }}%</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="mobile-progress-title" class="text-[1.0625rem] font-bold leading-5 text-[#ffffffd9]">Twój postęp</h2>
                    <p class="mt-1 text-sm leading-snug text-[#ffffffb8]">
                        {{ totalAnsweredQuestions }} z {{ courseProgress.total_questions }} pytań · {{ courseProgress.completed_topics }} z {{ courseProgress.total_topics }} działów
                    </p>
                    <div role="progressbar" class="sr-only" aria-label="Postęp przerabiania pytań" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="courseProgressPercent" />
                    <Link :href="categoryStatsHref" class="pk-btn-secondary mt-2 flex min-h-11 w-full items-center justify-center px-3 text-[0.9375rem] font-semibold">
                        Szczegóły postępu
                    </Link>
                </div>
            </section>

            <section v-if="carouselTopics.length" class="mt-6" aria-labelledby="mobile-popular-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="mobile-popular-title" class="text-[1.0625rem] font-bold text-[#ffffffd9]">Kontynuuj działy</h2>
                    <button type="button" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#ffffffb8] hover:text-[#ffffffd9] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffffffd9]" @click="openTopicsShortcut">
                        Wszystkie {{ carouselTopics.length }}
                    </button>
                </div>
                <MobileTopicCarousel
                    :topics="carouselTopics"
                    :last-topic-id="learningDashboard.last_topic_id ?? null"
                    :processing="sessionProcessing"
                    dark
                    @start-topic="startFeaturedTopic"
                />
            </section>

            <section v-if="professionalCourseTiles.length" class="mt-6" aria-labelledby="mobile-professional-title">
                <h2 id="mobile-professional-title" class="text-[1.0625rem] font-bold text-[#ffffffd9]">Kursy zawodowe</h2>
                <div class="-mx-3 mt-2.5 flex gap-2 overflow-x-auto px-3 pb-2 [scrollbar-width:none]">
                    <button
                        v-for="(course, index) in professionalCourseTiles"
                        :key="course.id"
                        type="button"
                        class="pk-tile flex w-[11rem] shrink-0 flex-col items-start gap-2 p-3 text-left"
                        @click="emit('select-professional-course', course.code)"
                    >
                        <span class="line-clamp-3 min-h-[3.75rem] text-[0.95rem] font-bold leading-tight" :style="{ color: professionalCourseAccent(index) }">{{ course.name }}</span>
                        <span class="mt-auto w-full">
                            <span class="block text-xs text-[#ffffffb8]">Postęp {{ course.progress.percent }}%</span>
                            <span class="mt-1 block h-2 w-full overflow-hidden rounded-[10px] bg-[#ffffff1a]" aria-hidden="true">
                                <span class="block h-full rounded-[10px] bg-[#81b64c]" :style="{ width: `${Math.min(100, Math.max(0, course.progress.percent))}%` }" />
                            </span>
                        </span>
                    </button>
                </div>
            </section>

            <section v-if="streakOverviewLoading && !topStreakLeaders.length" class="mt-6" aria-hidden="true">
                <div class="h-5 w-40 rounded-[3px] bg-[#ffffff1a]" />
                <div class="pk-panel mt-3 flex flex-col gap-1 p-3">
                    <div v-for="row in 3" :key="row" class="flex items-center gap-3 py-1.5">
                        <span class="h-8 w-8 rounded-[3px] bg-[#ffffff1a]" />
                        <span class="h-3.5 flex-1 rounded-[3px] bg-[#ffffff1a]" />
                        <span class="h-3.5 w-6 rounded-[3px] bg-[#ffffff1a]" />
                    </div>
                </div>
            </section>

            <section v-else-if="topStreakLeaders.length" class="mt-6" aria-labelledby="mobile-leaders-title">
                <MobileStreakLeaderboard
                    title-id="mobile-leaders-title"
                    :leaders="topStreakLeaders"
                    :caption="`Kategoria ${categoryShortName}`"
                    :me="myStreakLeader"
                    label="Najlepsze serie"
                />
                <Link :href="trainingHref" class="pk-btn-secondary mt-2.5 flex min-h-11 items-center justify-center text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffffffd9]">
                    Pełny ranking
                </Link>
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
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                            <path d="M8 21h8M12 17v4M7 4h10v3.5a5 5 0 0 1-10 0V4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M7 6H4.5A1.5 1.5 0 0 0 3 7.5v.75A3.75 3.75 0 0 0 6.75 12H8M17 6h2.5A1.5 1.5 0 0 1 21 7.5v.75A3.75 3.75 0 0 1 17.25 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="whitespace-nowrap text-center text-[1.02rem] font-bold leading-none tracking-[-0.02em]">
                                        Zobacz pełny ranking
                                    </span>
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
                                                        v-if="topicArtworkForKey(option.key)"
                                                        :src="topicArtworkForKey(option.key) ?? ''"
                                                        alt=""
                                                        loading="lazy"
                                                        decoding="async"
                                                        class="h-16 w-16 shrink-0 object-contain"
                                                    >
                                                    <img
                                                        v-else-if="option.hero_image_url"
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
                                    Zmień ustawienia serii
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
.mobile-home-dark {
    font-family: Barlow, Figtree, ui-sans-serif, system-ui, sans-serif;
}

.mobile-home-dark :is(h1, h2, h3) {
    font-family: inherit;
    letter-spacing: -0.01em;
}

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

<style>
@media (max-width: 767px) {
    body:has(.mobile-home-dark) {
        background-color: #302e2b;
    }
}
</style>
