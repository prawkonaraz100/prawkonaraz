<script setup lang="ts">
import CourseModules from '@/Pages/QuestionCollections/Partials/CourseModules.vue';
import { topicArtworkForKey } from '@/lib/topicArtwork';
import { Link } from '@inertiajs/vue3';
import { GraduationCap, RotateCcw } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import rocketButtonIcon from '../../../../images/session/rocket-button.png';

type LearningPath = 'pjm' | 'traffic-signs' | 'classic' | 'zen' | 'exam' | 'memory' | 'ranking';
type QuestionScope = 'all' | 'basic' | 'specialist';

interface TopicOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
}

interface TopicGroup {
    label: string;
    options: TopicOption[];
}

interface LearningPathTab {
    value: LearningPath;
    label: string;
    summary: string;
    disabled: boolean;
}

interface StatusOption {
    value: string;
    label: string;
    count: number;
}

interface ScopeOption {
    value: QuestionScope;
    label: string;
    compactLabel: string;
}

interface ActiveLearningSession {
    title: string;
    subtitle: string | null;
    resume_url: string;
    progress: {
        answered: number;
        total: number;
        percent: number;
    };
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

type MapCell =
    | { kind: 'topic'; topic: TopicOption }
    | { kind: 'finish' }
    | { kind: 'empty' };

const props = defineProps<{
    categoryShortName: string;
    learningPathTabs: LearningPathTab[];
    selectedPath: LearningPath;
    isClassicPath: boolean;
    isZenPath: boolean;
    isExamPath: boolean;
    isPjmPath: boolean;
    learningPathTitle: string;
    learningPathLead: string;
    filteredGroupOptions: TopicGroup[];
    selectedTopicId: number | null;
    statusOptions: StatusOption[];
    questionScopeOptions: ScopeOption[];
    selectedStatus: string;
    selectedScope: QuestionScope;
    randomizeOrder: boolean;
    selectedQuestionCount: number;
    canStartLearning: boolean;
    canUseFullProduct: boolean;
    sessionProcessing: boolean;
    globalIncorrectProcessing: boolean;
    hasGlobalIncorrectQuestions: boolean;
    totalIncorrectQuestions: number;
    managedIncorrectListEnabled: boolean;
    incorrectQuestionsUrl: string;
    activeSession: ActiveLearningSession | null;
    courseProgressPercent: number;
    trafficSignLearningHref: string;
    memoryTrainerHref: string;
    rankingModeHref: string;
    pjmHref: string;
    professionalCourses: ProfessionalCourse[];
    initialProfessionalCourseCode: string | null;
    examFacts: Array<{ label: string; value: string }>;
    startButtonLabel: string;
    errors: {
        licenseCategory?: string;
        topic?: string;
        status?: string;
    };
}>();

const emit = defineEmits<{
    selectPath: [path: LearningPath];
    selectTopic: [topicId: number];
    selectStatus: [status: string];
    selectScope: [scope: QuestionScope];
    setRandomOrder: [random: boolean];
    startLearning: [];
    startGlobalIncorrectLearning: [];
}>();

const isTopicLearningPath = computed(() => props.isClassicPath || props.isZenPath);
const isSessionSetupOpen = ref(Boolean(props.errors.licenseCategory || props.errors.topic || props.errors.status));
const isNavigationCollapsed = ref(true);
const selectedProfessionalCourseCode = ref<string | null>(
    props.professionalCourses.some((course) => course.code === props.initialProfessionalCourseCode)
        ? props.initialProfessionalCourseCode
        : null,
);
const hiddenSessionStatusValues = new Set(['correct', 'memorized']);
const visibleSessionStatusOptions = computed(() =>
    props.statusOptions.filter((option) => !hiddenSessionStatusValues.has(option.value)),
);
const sidebarLearningPathTabs = computed(() =>
    props.learningPathTabs.filter((tab) => tab.value !== 'memory'),
);
const mapTopics = computed(() => props.filteredGroupOptions.flatMap((group) => group.options));
const specialistTopicIds = computed(() =>
    new Set(
        props.filteredGroupOptions
            .filter((group) => group.label === 'Pytania specjalistyczne')
            .flatMap((group) => group.options.map((topic) => topic.id)),
    ),
);
const selectedTopic = computed(() =>
    mapTopics.value.find((topic) => topic.id === props.selectedTopicId) ?? null,
);
const selectedProfessionalCourse = computed(() =>
    props.professionalCourses.find((course) => course.code === selectedProfessionalCourseCode.value) ?? null,
);
const firstUnansweredTopicId = computed(() =>
    mapTopics.value.find((topic) => (topic.counts.unanswered ?? 0) > 0)?.id ?? null,
);
const mapRows = computed(() => {
    const remainingTopics = [...mapTopics.value];
    const rows: MapCell[][] = [];
    const fillRow = (cells: MapCell[]): MapCell[] => [
        ...cells,
        ...Array.from({ length: Math.max(7 - cells.length, 0) }, () => ({ kind: 'empty' } as const)),
    ];
    const takeTopics = (count: number): MapCell[] =>
        remainingTopics.splice(0, count).map((topic) => ({ kind: 'topic', topic }));

    if (remainingTopics.length === 0) {
        return rows;
    }

    while (remainingTopics.length > 6) {
        rows.push(fillRow(takeTopics(7)));
    }

    if (remainingTopics.length > 0) {
        rows.push(fillRow([...takeTopics(6), { kind: 'finish' }]));
    } else if (rows.length > 0) {
        rows.push(fillRow([{ kind: 'finish' }]));
    }

    return rows;
});
const completedTopicCount = computed(() =>
    mapTopics.value.filter((topic) => topicState(topic) === 'complete').length,
);
const mapRouteHeight = computed(() => Math.max(132 * mapRows.value.length, 132));
const mapRoutePath = computed(() => {
    const left = 38;
    const right = 1162;
    const rowHeight = 132;
    let y = 66;
    let path = `M ${left} ${y} H ${right}`;

    for (let rowIndex = 0; rowIndex < mapRows.value.length - 1; rowIndex += 1) {
        const nextY = y + rowHeight;

        if (rowIndex % 2 === 0) {
            path += ` Q 1188 ${y} 1188 ${y + 28} Q 1188 ${nextY} ${right} ${nextY} H ${left}`;
        } else {
            path += ` Q 12 ${y} 12 ${y + 28} Q 12 ${nextY} ${left} ${nextY} H ${right}`;
        }

        y = nextY;
    }

    return path;
});
const mapProgressStyle = computed(() => ({
    background: `conic-gradient(#1e73e8 0deg ${props.courseProgressPercent * 3.6}deg, #e5edf0 ${props.courseProgressPercent * 3.6}deg 360deg)`,
}));

function openSessionSetup(topicId: number): void {
    emit('selectTopic', topicId);

    if (hiddenSessionStatusValues.has(props.selectedStatus)) {
        emit('selectStatus', 'all');
    }

    isSessionSetupOpen.value = true;
}

function closeSessionSetup(): void {
    isSessionSetupOpen.value = false;
}

function sessionStatusDescription(status: string): string {
    const descriptions: Record<string, string> = {
        all: 'Wszystkie dostępne pytania',
        unanswered: 'Pytania, których jeszcze nie przerabiałeś',
        incorrect: 'Pytania rozwiązane błędnie',
        bookmarked: 'Pytania zapisane na Twojej liście',
    };

    return descriptions[status] ?? 'Pytania z wybranego działu';
}

function updateProfessionalCourseUrl(courseCode: string | null): void {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);

    if (courseCode) {
        const course = props.professionalCourses.find((item) => item.code === courseCode);

        if (course) {
            url.searchParams.set('kurs', course.slug);
        }
    } else {
        url.searchParams.delete('kurs');
    }

    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
}

function selectLearningPath(path: LearningPath): void {
    closeSessionSetup();
    selectedProfessionalCourseCode.value = null;
    updateProfessionalCourseUrl(null);
    emit('selectPath', path);
}

function selectProfessionalCourse(courseCode: string): void {
    closeSessionSetup();
    selectedProfessionalCourseCode.value = courseCode;
    updateProfessionalCourseUrl(courseCode);
}

function expandNavigation(): void {
    isNavigationCollapsed.value = false;
}

function collapseNavigation(): void {
    isNavigationCollapsed.value = true;
}

function handleNavigationFocusOut(event: FocusEvent): void {
    const nextFocusedElement = event.relatedTarget;

    if (nextFocusedElement instanceof Node && event.currentTarget instanceof HTMLElement && event.currentTarget.contains(nextFocusedElement)) {
        return;
    }

    collapseNavigation();
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeSessionSetup();
    }
}

watch(
    () => [props.errors.licenseCategory, props.errors.topic, props.errors.status],
    ([licenseCategory, topic, status]) => {
        if (licenseCategory || topic || status) {
            isSessionSetupOpen.value = true;
        }
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown));

function answeredCount(topic: TopicOption): number {
    return Math.max(topic.counts.all - topic.counts.unanswered, 0);
}

function progressPercent(topic: TopicOption): number {
    if (topic.questions_count <= 0) {
        return 0;
    }

    return Math.min(Math.round((answeredCount(topic) / topic.questions_count) * 100), 100);
}

function topicState(topic: TopicOption): 'next' | 'attention' | 'complete' | 'progress' | 'pending' {
    if (topic.id === firstUnansweredTopicId.value) {
        return 'next';
    }

    if ((topic.counts.incorrect ?? 0) > 0) {
        return 'attention';
    }

    if (topic.questions_count > 0 && answeredCount(topic) >= topic.questions_count) {
        return 'complete';
    }

    if (answeredCount(topic) > 0) {
        return 'progress';
    }

    return 'pending';
}

function topicStateLabel(topic: TopicOption): string {
    const state = topicState(topic);

    if (state === 'next') {
        return 'Następny dział';
    }

    if (state === 'attention') {
        return `${topic.counts.incorrect ?? 0} do poprawy`;
    }

    if (state === 'complete') {
        return 'Przerobiony';
    }

    if (state === 'progress') {
        return `${progressPercent(topic)}% przerobione`;
    }

    return 'Nie rozpoczęto';
}

function topicNumber(topic: TopicOption): number {
    return mapTopics.value.findIndex((candidate) => candidate.id === topic.id) + 1;
}

function mapTone(topic: TopicOption): string {
    return specialistTopicIds.value.has(topic.id) ? '#ef7d24' : '#1e73e8';
}

function topicIcon(topic: TopicOption): string {
    const source = `${topic.key} ${topic.label}`.toLocaleLowerCase('pl-PL');

    if (source.includes('skrzy') || source.includes('pierwsz')) return 'junction';
    if (source.includes('sygna') || source.includes('świat')) return 'signal';
    if (source.includes('pies')) return 'pedestrian';
    if (source.includes('rower')) return 'bicycle';
    if (source.includes('prędko') || source.includes('hamow')) return 'speed';
    if (source.includes('park')) return 'parking';
    if (source.includes('autostrad') || source.includes('tunel')) return 'road';
    if (source.includes('ratown') || source.includes('pomoc')) return 'help';
    if (source.includes('znak')) return 'sign';

    return 'route';
}

function topicArtworkFor(topic: TopicOption): string | null {
    return topicArtworkForKey(topic.key);
}

</script>

<template>
    <section class="hidden bg-[#fbfcfd] md:block" aria-label="Panel nauki">
        <div class="w-full">
            <div
                class="grid min-h-[calc(100vh-6.5rem)] transition-[grid-template-columns] duration-200 ease-out"
                :class="isNavigationCollapsed ? 'md:grid-cols-[4.75rem_minmax(0,1fr)]' : 'md:grid-cols-[14.5rem_minmax(0,1fr)]'"
            >
                <aside
                    class="flex min-h-full flex-col border-r border-[#e5eaed] bg-[#fdfefe] py-4 transition-[padding] duration-200 ease-out"
                    :class="isNavigationCollapsed ? 'px-2' : 'px-3 xl:px-4'"
                    aria-label="Tryby nauki"
                    @mouseenter="expandNavigation"
                    @mouseleave="collapseNavigation"
                    @focusin="expandNavigation"
                    @focusout="handleNavigationFocusOut"
                >
                    <nav class="space-y-1" aria-label="Tryby nauki">
                        <button
                            v-for="tab in sidebarLearningPathTabs"
                            :key="tab.value"
                            type="button"
                            class="group relative flex min-h-12 w-full items-center gap-3 rounded-[10px] text-left transition-[background-color,color,transform] duration-150 hover:bg-[#f1f4f5] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ef3b26] disabled:cursor-not-allowed disabled:opacity-45"
                            :class="selectedPath === tab.value
                                ? `${isNavigationCollapsed ? 'justify-center px-0' : 'px-2.5'} bg-[#eef1f3] text-[#101820]`
                                : `${isNavigationCollapsed ? 'justify-center px-0' : 'px-2.5'} text-[#66737d] hover:text-[#101820]`"
                            :disabled="tab.disabled"
                            :aria-pressed="selectedPath === tab.value"
                            :aria-label="isNavigationCollapsed ? `${tab.label}: ${tab.summary}` : undefined"
                            :title="isNavigationCollapsed ? `${tab.label}: ${tab.summary}` : undefined"
                            @click="selectLearningPath(tab.value)"
                        >
                            <span
                                class="grid h-10 min-w-10 place-items-center text-[#596771] transition-transform duration-150 group-hover:scale-105 group-hover:text-[#101820]"
                                :class="selectedPath === tab.value ? 'text-[#101820]' : ''"
                                aria-hidden="true"
                            >
                                <svg v-if="tab.value === 'classic'" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 5.75A2.75 2.75 0 0 1 6.75 3H10v17H6.75A2.75 2.75 0 0 0 4 22V5.75Zm16 0A2.75 2.75 0 0 0 17.25 3H14v17h3.25A2.75 2.75 0 0 1 20 22V5.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                </svg>
                                <svg v-else-if="tab.value === 'zen'" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M12 3v2.25M12 18.75V21M3 12h2.25M18.75 12H21M5.64 5.64l1.6 1.6m9.52 9.52 1.6 1.6m0-12.72-1.6 1.6m-9.52 9.52-1.6 1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                                <svg v-else-if="tab.value === 'memory'" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                    <path d="M9.1 18.5H8a4 4 0 0 1-1.2-7.82A5.25 5.25 0 0 1 16.9 9a4 4 0 0 1-.9 7.84h-1.1M12 7.5v9M9.75 10.5h4.5M10 20.5h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg v-else-if="tab.value === 'exam'" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                    <rect x="5" y="3.5" width="14" height="17" rx="2.25" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M9 3.5h6v3H9v-3Zm1 10 1.5 1.5 3-3M9 17h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg v-else class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 3.5 20 8v8l-8 4.5L4 16V8l8-4.5ZM8.5 10.5l3.5 2 3.5-2M12 12.5V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span v-if="!isNavigationCollapsed" class="min-w-0">
                                <span class="block truncate text-[0.84rem] font-semibold leading-5">{{ tab.label }}</span>
                                <span class="block truncate text-[0.71rem] leading-4" :class="selectedPath === tab.value ? 'text-[#697681]' : 'text-[#8a969e]'">{{ tab.summary }}</span>
                            </span>
                        </button>
                    </nav>

                    <div v-if="professionalCourses.length > 0" class="mt-6 border-t border-[#e8edef] pt-4">
                        <p
                            v-if="!isNavigationCollapsed"
                            class="px-2.5 text-[0.64rem] font-semibold uppercase tracking-[0.1em] text-[#87929b]"
                        >
                            Kursy zawodowe
                        </p>
                        <div class="mt-2 space-y-1">
                            <button
                                v-for="course in professionalCourses"
                                :key="course.id"
                                type="button"
                                class="group relative flex min-h-12 w-full items-center gap-3 rounded-[10px] text-left transition-[background-color,color,transform] duration-150 hover:bg-[#f1f4f5] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ef3b26]"
                                :class="selectedProfessionalCourse?.code === course.code
                                    ? `${isNavigationCollapsed ? 'justify-center px-0' : 'px-2.5'} bg-[#eef1f3] text-[#101820]`
                                    : `${isNavigationCollapsed ? 'justify-center px-0' : 'px-2.5'} text-[#66737d] hover:text-[#101820]`"
                                :aria-pressed="selectedProfessionalCourse?.code === course.code"
                                :aria-label="isNavigationCollapsed ? `Kurs zawodowy: ${course.name}` : undefined"
                                :title="isNavigationCollapsed ? `Kurs zawodowy: ${course.name}` : undefined"
                                @click="selectProfessionalCourse(course.code)"
                            >
                                <span
                                    class="grid h-10 min-w-10 place-items-center text-[#596771] transition-transform duration-150 group-hover:scale-105 group-hover:text-[#101820]"
                                    :class="selectedProfessionalCourse?.code === course.code ? 'text-[#101820]' : ''"
                                    aria-hidden="true"
                                >
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                        <rect x="4" y="7" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.8" />
                                        <path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m-11 4h16M10 11v2h4v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span v-if="!isNavigationCollapsed" class="min-w-0">
                                    <span class="block truncate text-[0.84rem] font-semibold leading-5">{{ course.name }}</span>
                                    <span class="block truncate text-[0.71rem] leading-4 text-[#8a969e]">{{ course.modules.length }} modułów</span>
                                </span>
                            </button>
                        </div>
                    </div>

                </aside>

                <main class="min-w-0">

            <section
                v-if="activeSession"
                class="flex flex-wrap items-center justify-between gap-4 bg-[#101820] px-6 py-4 text-white xl:px-9"
                aria-label="Aktywna sesja"
            >
                <div class="min-w-0">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-white/62">Bieżąca sesja</p>
                    <p class="mt-1 truncate text-[1rem] font-semibold">{{ activeSession.title }}</p>
                    <p class="mt-1 text-sm text-white/70">
                        {{ activeSession.subtitle }} · {{ activeSession.progress.answered }}/{{ activeSession.progress.total }} pytań
                    </p>
                </div>
                <Link
                    :href="activeSession.resume_url"
                    class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-[5px] bg-white px-5 text-sm font-semibold text-[#101820] transition hover:bg-[#f1f4f6] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#101820]"
                >
                    Wróć do sesji <span aria-hidden="true">→</span>
                </Link>
            </section>

            <template v-if="selectedProfessionalCourse">
                <section class="min-h-[calc(100vh-6.5rem)] bg-[#fbfcfd] px-6 py-9 xl:px-10 xl:py-12" aria-label="Kurs zawodowy">
                    <div class="mx-auto max-w-[88rem]">
                        <header class="border-b border-[#d7dde1] pb-8 2xl:grid 2xl:grid-cols-[minmax(0,1fr)_29rem] 2xl:gap-x-14">
                            <div>
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-[#ef3b26]">Kurs zawodowy</p>
                                <h2 class="mt-3 max-w-4xl text-3xl font-semibold leading-tight text-[#101820] xl:text-[2.55rem]">{{ selectedProfessionalCourse.name }}</h2>
                            <p v-if="selectedProfessionalCourse.description" class="mt-4 max-w-2xl text-base leading-7 text-[#475467]">
                                {{ selectedProfessionalCourse.description }}
                            </p>
                                <p v-if="selectedProfessionalCourse.category_name" class="mt-5 text-sm font-medium text-[#667085]">
                                {{ selectedProfessionalCourse.category_name }}
                            </p>
                            </div>

                            <dl class="mt-8 grid grid-cols-3 border-y border-[#d7dde1] 2xl:mt-0 2xl:self-end">
                                <div class="py-4 pr-4">
                                    <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#78838d]">Przerobione</dt>
                                    <dd class="mt-2 text-xl font-semibold text-[#101820]">
                                        {{ selectedProfessionalCourse.progress.answered_count }}
                                        <span class="text-sm font-medium text-[#667085]">/ {{ selectedProfessionalCourse.progress.total_questions }}</span>
                                    </dd>
                                </div>
                                <div class="border-l border-[#d7dde1] px-4 py-4">
                                    <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#78838d]">Ukończenie</dt>
                                    <dd class="mt-2 text-xl font-semibold text-[#101820]">{{ selectedProfessionalCourse.progress.percent }}%</dd>
                                </div>
                                <div class="border-l border-[#d7dde1] py-4 pl-4">
                                    <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#78838d]">Do poprawy</dt>
                                    <dd class="mt-2 text-xl font-semibold" :class="selectedProfessionalCourse.incorrect_questions.count > 0 ? 'text-[#d92d20]' : 'text-[#101820]'">
                                        {{ selectedProfessionalCourse.incorrect_questions.count }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="col-span-2 mt-7 h-2 overflow-hidden bg-[#e4e9ed]" role="progressbar" :aria-valuenow="selectedProfessionalCourse.progress.percent" aria-valuemin="0" aria-valuemax="100" aria-label="Postęp kursu">
                                <span class="block h-full bg-[#ef3b26] transition-[width] duration-300" :style="{ width: `${selectedProfessionalCourse.progress.percent}%` }" />
                            </div>
                        </header>

                        <CourseModules
                            class="mt-9"
                            :modules="selectedProfessionalCourse.modules"
                            :active-session="activeSession"
                            :review-count="selectedProfessionalCourse.incorrect_questions.count"
                            :review-url="selectedProfessionalCourse.incorrect_questions.url"
                        />
                    </div>
                </section>
            </template>

            <template v-else-if="isTopicLearningPath">
                <section class="min-w-0" aria-label="Mapa działów nauki">
                    <div class="min-h-[calc(100vh-6.5rem)] overflow-hidden bg-[#fbfcfd] px-4 py-5 sm:px-6 xl:px-10 xl:py-6">
                        <header class="flex flex-wrap items-start justify-between gap-4">
                            <div class="border-l-[4px] border-[#1e73e8] pl-3.5">
                                <h1 class="text-[1.05rem] font-semibold leading-5 text-[#172029] xl:text-[1.2rem]">
                                    Ścieżka nauki
                                </h1>
                                <p class="mt-0.5 text-[0.75rem] font-medium text-[#74818b] xl:text-[0.8rem]">
                                    {{ mapTopics.length }} działów · Ucz się krok po kroku
                                </p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[0.66rem] font-semibold text-[#65727d]">
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#1e73e8]" aria-hidden="true" />Pytania podstawowe</span>
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#ef7d24]" aria-hidden="true" />Pytania specjalistyczne</span>
                                </div>
                            </div>

                            <dl class="flex min-w-[17.75rem] divide-x divide-[#e7ecef] overflow-hidden rounded-[9px] border border-[#e7ecef] bg-white shadow-[0_7px_20px_rgba(16,24,32,0.045)]">
                                <div class="flex min-w-[9.9rem] items-center gap-2.5 px-4 py-2.5">
                                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full" :style="mapProgressStyle" aria-hidden="true">
                                        <span class="h-6 w-6 rounded-full bg-white" />
                                    </div>
                                    <div>
                                        <dt class="text-[0.63rem] font-semibold leading-3 text-[#74818b]">Twój postęp</dt>
                                        <dd class="mt-0.5 text-[0.9rem] font-bold leading-4 text-[#172029]">{{ courseProgressPercent }}%</dd>
                                    </div>
                                </div>
                                <div class="min-w-[7.85rem] px-4 py-2.5">
                                    <dt class="text-[0.63rem] font-semibold leading-3 text-[#74818b]">Działy zaliczone</dt>
                                    <dd class="mt-0.5 text-[0.9rem] font-bold leading-4 text-[#172029]">
                                        {{ completedTopicCount }} / {{ mapTopics.length }}
                                    </dd>
                                </div>
                            </dl>
                        </header>

                        <div v-if="mapRows.length > 0" class="relative mt-7 w-full xl:mt-8" :style="{ minHeight: `${mapRouteHeight}px` }">
                            <svg
                                aria-hidden="true"
                                class="pointer-events-none absolute inset-0 z-0 h-full w-full"
                                :viewBox="`0 0 1200 ${mapRouteHeight}`"
                                preserveAspectRatio="none"
                                fill="none"
                            >
                                <path :d="mapRoutePath" stroke="#e8edef" stroke-width="32" stroke-linecap="round" stroke-linejoin="round" />
                                <path :d="mapRoutePath" stroke="#c8d1d6" stroke-width="2" stroke-dasharray="11 10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <div class="relative z-10 grid">
                                <div
                                    v-for="(row, rowIndex) in mapRows"
                                    :key="rowIndex"
                                    class="grid min-h-[8.25rem] grid-cols-7 items-center gap-x-2 xl:gap-x-5"
                                >
                                    <div
                                        v-for="(cell, cellIndex) in row"
                                        :key="`${rowIndex}-${cellIndex}`"
                                        class="relative flex min-w-0 justify-center"
                                    >
                                        <template v-if="cell.kind === 'finish'">
                                            <div class="relative flex h-[6.2rem] w-full max-w-[8.9rem] flex-col items-center justify-center rounded-[10px] border border-[#e3e8eb] bg-white px-2 text-center shadow-[0_8px_22px_rgba(16,24,32,0.05)] xl:h-[6.7rem]" aria-label="Meta trasy nauki">
                                                <svg class="h-8 w-8 text-[#172029]" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                                    <path d="M8 28V5.5m1 1c4-1.9 8.2 1.8 13.3 0v12c-5.1 1.8-9.3-1.9-13.3 0" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M9.5 6.7h3.2v3.1H9.5zm6.4 0h3.2v3.1h-3.2zm-3.2 3.1h3.2v3.1h-3.2zm6.4 3.1h3.2v3.1h-3.2zm-6.4 3.1h3.2v3.1h-3.2z" fill="currentColor" />
                                                </svg>
                                                <span class="mt-1 text-[0.6rem] font-bold leading-none text-[#172029]">META</span>
                                            </div>
                                        </template>

                                        <button
                                            v-else-if="cell.kind === 'topic'"
                                            type="button"
                                            class="group relative flex h-[6.2rem] w-full max-w-[8.9rem] flex-col items-center rounded-[10px] border bg-white px-2.5 pt-3.5 pb-2 text-center shadow-[0_6px_18px_rgba(16,24,32,0.035)] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1e73e8] focus-visible:ring-offset-2 xl:h-[6.7rem]"
                                            :class="selectedTopicId === cell.topic.id
                                                ? 'border-[#172029] ring-1 ring-[#172029] shadow-[0_10px_24px_rgba(16,24,32,0.1)]'
                                                : 'border-[#e6eaed] hover:-translate-y-0.5 hover:border-[#b9c3c9] hover:shadow-[0_10px_22px_rgba(16,24,32,0.08)]'"
                                            :aria-label="`${cell.topic.label}. ${topicStateLabel(cell.topic)}. ${answeredCount(cell.topic)} z ${cell.topic.questions_count} pytań przerobionych.`"
                                            :aria-pressed="selectedTopicId === cell.topic.id"
                                            @click="openSessionSetup(cell.topic.id)"
                                        >
                                            <span
                                                class="absolute -top-3 left-1/2 grid h-6 min-w-6 -translate-x-1/2 place-items-center rounded-full px-1 text-[0.67rem] font-bold text-white shadow-[0_3px_8px_rgba(16,24,32,0.16)]"
                                                :style="{ backgroundColor: mapTone(cell.topic) }"
                                                aria-hidden="true"
                                            >
                                                {{ topicNumber(cell.topic) }}
                                            </span>

                                            <img
                                                v-if="topicArtworkFor(cell.topic)"
                                                :src="topicArtworkFor(cell.topic) ?? ''"
                                                alt=""
                                                class="h-9 w-9 object-contain xl:h-10 xl:w-10"
                                                aria-hidden="true"
                                            />
                                            <span v-else class="grid h-7 w-7 place-items-center" :style="{ color: mapTone(cell.topic) }" aria-hidden="true">
                                                <svg v-if="topicIcon(cell.topic) === 'junction'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M16 4v24M16 16 6 9M16 16l10-7" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/><circle cx="16" cy="16" r="3.2" fill="currentColor" /></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'signal'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><rect x="11" y="3" width="10" height="26" rx="4" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="9" r="2.2" fill="currentColor"/><circle cx="16" cy="16" r="2.2" fill="currentColor" opacity=".48"/><circle cx="16" cy="23" r="2.2" fill="currentColor" /></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'pedestrian'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><circle cx="17" cy="6" r="2.8" fill="currentColor"/><path d="m15 12 3.5 3.2 4.5 1.4M16.5 13 12 18l-4 2M17 16l-1 6 4 6M14 20l-5 7" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'bicycle'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><circle cx="8" cy="23" r="5" stroke="currentColor" stroke-width="2"/><circle cx="24" cy="23" r="5" stroke="currentColor" stroke-width="2"/><path d="m8 23 6-12 5 12m-5-7h7l3-4M13 9h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'speed'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="3"/><path d="M10 21c1.8-5 5.2-7.4 10-8" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/><path d="m17 13 3-1" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'parking'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><rect x="5" y="4" width="22" height="24" rx="3" stroke="currentColor" stroke-width="2.4"/><path d="M12 23V9h5a4 4 0 0 1 0 8h-5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'road'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M7 29 13 3h6l6 26" stroke="currentColor" stroke-width="2.3" stroke-linejoin="round"/><path d="M16 7v4m0 4v4m0 4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'help'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M16 4v24M4 16h24" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="2"/></svg>
                                                <svg v-else-if="topicIcon(cell.topic) === 'sign'" class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M16 3 29 27H3L16 3Z" stroke="currentColor" stroke-width="2.8" stroke-linejoin="round"/><path d="M16 11v7m0 4h.01" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
                                                <svg v-else class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M6 25c6-10 14-10 20-18M9 6h5v5M18 21h5v5" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="25" r="2.5" fill="currentColor"/></svg>
                                            </span>
                                            <span class="mt-1.5 line-clamp-2 text-[0.59rem] font-semibold leading-[0.72rem] text-[#172029] xl:text-[0.64rem] xl:leading-[0.76rem]">
                                                {{ cell.topic.label }}
                                            </span>
                                            <span class="mt-auto flex w-full items-center gap-2 px-1 text-[0.58rem] font-semibold leading-none xl:text-[0.62rem]" :style="{ color: mapTone(cell.topic) }">
                                                <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#e8edf0]">
                                                    <span class="block h-full rounded-full transition-[width] duration-300" :style="{ width: `${progressPercent(cell.topic)}%`, backgroundColor: mapTone(cell.topic) }" />
                                                </span>
                                                <span class="w-7 text-right">{{ progressPercent(cell.topic) }}%</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-else class="py-12 text-center text-sm text-[#65727d]">
                            Dla tego zakresu nie znaleziono działów pytań.
                        </p>
                    </div>
                </section>

                <Teleport to="body">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <section
                            v-if="isSessionSetupOpen"
                            class="fixed inset-0 z-[70] hidden bg-[#101820]/45 p-5 backdrop-blur-[2px] md:grid md:place-items-center xl:p-7"
                            aria-labelledby="learning-settings-title"
                        >
                            <div class="flex max-h-[calc(100vh-2.5rem)] w-full max-w-[74rem] flex-col overflow-hidden rounded-[14px] border border-[#dce3e8] bg-white shadow-[0_24px_80px_rgba(15,23,42,0.3)] xl:max-h-[calc(100vh-3.5rem)]">
                                <header class="flex items-center justify-between gap-5 border-b border-[#e7ebee] px-6 py-4 xl:px-8">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#215fbe] text-xs font-semibold text-white shadow-[0_4px_10px_rgba(33,95,190,0.2)]">
                                            {{ selectedTopic ? topicNumber(selectedTopic) : '—' }}
                                        </span>
                                        <span v-if="selectedTopic && topicArtworkFor(selectedTopic)" class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden">
                                            <img :src="topicArtworkFor(selectedTopic) ?? ''" alt="" class="h-11 w-11 object-contain" />
                                        </span>
                                        <div class="min-w-0">
                                            <h2 id="learning-settings-title" class="truncate text-lg font-semibold leading-tight text-[#101820]">
                                                {{ selectedTopic?.label ?? 'Wybierz dział na mapie' }}
                                            </h2>
                                            <p class="mt-1 text-[0.78rem] font-medium text-[#71808b]">Konfiguracja sesji nauki</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[#d9e0e5] text-[#52606d] transition hover:border-[#101820] hover:text-[#101820] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ef3b26] focus-visible:ring-offset-2"
                                        title="Zamknij konfigurację sesji"
                                        aria-label="Zamknij konfigurację sesji"
                                        @click="closeSessionSetup"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="m3 3 10 10M13 3 3 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </header>

                                <div class="grid min-h-0 overflow-y-auto divide-y divide-[#e7ebee] lg:grid-cols-[1.14fr_0.78fr_0.82fr_1.1fr] lg:divide-x lg:divide-y-0">
                                    <div class="px-6 py-5 xl:px-7">
                                        <div class="flex items-center justify-between gap-4">
                                            <p class="text-sm font-semibold text-[#202a33]">Zestaw pytań</p>
                                            <span class="shrink-0 text-[0.76rem] font-semibold text-[#52616c]">{{ selectedQuestionCount }}</span>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <button
                                                v-for="option in visibleSessionStatusOptions"
                                                :key="option.value"
                                                type="button"
                                                class="flex min-h-[3.55rem] w-full items-center gap-3 rounded-[7px] border px-3 py-2 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2869ca] focus-visible:ring-offset-2"
                                                :class="selectedStatus === option.value
                                                    ? 'border-[#7da5e8] bg-[#f5f9ff] text-[#172a44] shadow-[0_3px_10px_rgba(38,105,202,0.08)]'
                                                    : 'border-[#e1e6ea] bg-white text-[#35414a] hover:border-[#aeb8c0]'"
                                                :aria-pressed="selectedStatus === option.value"
                                                @click="emit('selectStatus', option.value)"
                                            >
                                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border-2" :class="selectedStatus === option.value ? 'border-[#2869ca] bg-[#2869ca] text-white' : 'border-[#aeb9c3] bg-white text-transparent'">
                                                    <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                                        <path d="m3.2 8.1 2.8 2.8 6.6-6.4" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-[0.78rem] font-semibold leading-tight">{{ option.label }}</span>
                                                    <span class="mt-1 block text-[0.68rem] leading-tight text-[#6b7883]">{{ sessionStatusDescription(option.value) }}</span>
                                                </span>
                                                <span class="grid h-7 min-w-7 shrink-0 place-items-center rounded-full bg-[#edf1f5] px-1.5 text-[0.68rem] font-semibold text-[#43515d]">{{ option.count }}</span>
                                            </button>
                                        </div>
                                        <Link
                                            v-if="hasGlobalIncorrectQuestions && managedIncorrectListEnabled"
                                            :href="incorrectQuestionsUrl"
                                            class="mt-4 inline-flex items-center gap-2 text-[0.74rem] font-semibold text-[#2869ca] transition hover:text-[#164a9c]"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 3h10M3 8h10M3 13h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M1 3h.01M1 8h.01M1 13h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                            Zarządzaj listą błędnych pytań
                                        </Link>
                                    </div>

                                    <div class="px-6 py-5 xl:px-7">
                                        <fieldset>
                                            <legend class="text-sm font-semibold text-[#202a33]">Kolejność pytań</legend>
                                            <div class="mt-4 grid grid-cols-2 overflow-hidden rounded-[7px] border border-[#dde4e9]">
                                                <button type="button" class="min-h-11 border-r border-[#dde4e9] px-3 text-center text-[0.78rem] font-semibold transition" :class="!randomizeOrder ? 'border-[#101820] bg-[#101820] text-white' : 'bg-white text-[#5c6a75] hover:bg-[#f7f9fa]'" :aria-pressed="!randomizeOrder" @click="emit('setRandomOrder', false)">Stała</button>
                                                <button type="button" class="min-h-11 px-3 text-center text-[0.78rem] font-semibold transition" :class="randomizeOrder ? 'bg-[#101820] text-white' : 'bg-white text-[#5c6a75] hover:bg-[#f7f9fa]'" :aria-pressed="randomizeOrder" @click="emit('setRandomOrder', true)">Losowa</button>
                                            </div>
                                            <p class="mt-4 flex gap-2 text-[0.7rem] leading-5 text-[#71808b]">
                                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="6.2" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.1v3.5M8 4.8h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                                Pytania będą wyświetlane w ustalonej kolejności.
                                            </p>
                                        </fieldset>
                                    </div>

                                    <div class="px-6 py-5 xl:px-7">
                                        <fieldset>
                                            <legend class="text-sm font-semibold text-[#202a33]">Zakres pytań</legend>
                                            <div class="mt-4 space-y-2">
                                                <button v-for="scope in questionScopeOptions" :key="scope.value" type="button" class="flex min-h-10 w-full items-center justify-between rounded-[7px] border px-3 text-left text-[0.78rem] font-semibold transition" :class="selectedScope === scope.value ? 'border-[#101820] bg-[#101820] text-white' : 'border-[#e1e6ea] bg-white text-[#5c6a75] hover:border-[#aeb8c0]'" :aria-pressed="selectedScope === scope.value" @click="emit('selectScope', scope.value)">
                                                    {{ scope.label }}
                                                    <svg v-if="selectedScope === scope.value" class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3.2 8.1 2.8 2.8 6.6-6.4" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                </button>
                                            </div>
                                            <p class="mt-4 flex gap-2 text-[0.7rem] leading-5 text-[#71808b]">
                                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="6.2" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.1v3.5M8 4.8h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                                Zawiera pytania podstawowe i specjalistyczne.
                                            </p>
                                        </fieldset>
                                    </div>

                                    <div class="flex flex-col justify-center bg-[#fbfcfb] px-6 py-5 xl:px-7">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-11 w-11 place-items-center text-[#16823b]">
                                                <GraduationCap :size="26" :stroke-width="2" aria-hidden="true" />
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-[#1d2a23]">Gotowe do nauki</p>
                                                <p class="mt-1 text-[0.71rem] font-medium text-[#718078]">{{ selectedQuestionCount }} pytań <span class="px-1 text-[#c1c9c3]">•</span> {{ randomizeOrder ? 'losowa kolejność' : 'stała kolejność' }} <span class="px-1 text-[#c1c9c3]">•</span> {{ questionScopeOptions.find((scope) => scope.value === selectedScope)?.label.toLowerCase() }}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="mt-6 inline-flex min-h-[61px] w-full items-center justify-center gap-3 rounded-[7px] bg-[#16823b] px-5 text-[0.92rem] font-semibold text-white shadow-[0_10px_20px_rgba(22,130,59,0.18)] transition hover:bg-[#126b30] disabled:cursor-not-allowed disabled:opacity-55"
                                            :disabled="sessionProcessing || (canUseFullProduct && !canStartLearning)"
                                            @click="emit('startLearning')"
                                        >
                                            <img :src="rocketButtonIcon" alt="" class="h-8 w-8 object-contain" />
                                            {{ sessionProcessing ? 'Uruchamianie...' : startButtonLabel }} <span aria-hidden="true">→</span>
                                        </button>
                                        <div v-if="hasGlobalIncorrectQuestions" class="my-5 flex items-center gap-3 text-center text-[0.7rem] font-semibold text-[#718078] before:h-px before:flex-1 before:bg-[#dce5de] after:h-px after:flex-1 after:bg-[#dce5de]">lub</div>
                                        <button
                                            v-if="hasGlobalIncorrectQuestions"
                                            type="button"
                                            class="grid min-h-12 w-full grid-cols-[2rem_minmax(0,1fr)_2rem] items-center gap-2 rounded-[7px] bg-[#16823b] px-4 text-[0.82rem] font-semibold text-white shadow-[0_10px_20px_rgba(22,130,59,0.18)] transition hover:bg-[#126b30] disabled:cursor-not-allowed disabled:opacity-55"
                                            :disabled="sessionProcessing || globalIncorrectProcessing"
                                            @click="emit('startGlobalIncorrectLearning')"
                                        >
                                            <span class="grid h-8 w-8 place-items-center" aria-hidden="true"><RotateCcw :size="20" :stroke-width="2.2" /></span>
                                            <span class="truncate text-center">{{ globalIncorrectProcessing ? 'Uruchamianie...' : 'Błędy z całego kursu' }}</span>
                                            <span class="text-center text-[0.78rem] text-white">{{ totalIncorrectQuestions }}</span>
                                        </button>
                                    </div>
                                </div>

                                <p v-if="errors.licenseCategory || errors.topic || errors.status" class="border-t border-[#f0c5bf] bg-[#fff8f7] px-6 py-3 text-sm font-medium text-[#a52a1d] xl:px-8">
                                    {{ errors.licenseCategory ?? errors.topic ?? errors.status }}
                                </p>
                            </div>
                        </section>
                    </Transition>
                </Teleport>
            </template>

            <section v-else class="min-h-[calc(100vh-6.5rem)] bg-white px-6 py-7 xl:px-9 xl:py-10">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[#ef3b26]">{{ learningPathTitle }}</p>
                <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <h2 class="max-w-3xl text-2xl font-semibold leading-tight text-[#101820]">{{ learningPathLead }}</h2>
                        <div v-if="isExamPath" class="mt-5 flex flex-wrap gap-3">
                            <span v-for="fact in examFacts" :key="fact.label" class="border border-[#dfe4e8] bg-[#fbfcfd] px-3 py-2 text-sm text-[#64717d]">
                                {{ fact.label }}: <strong class="font-semibold text-[#101820]">{{ fact.value }}</strong>
                            </span>
                        </div>
                    </div>
                    <button
                        v-if="isExamPath"
                        type="button"
                        class="inline-flex min-h-11 items-center gap-3 rounded-[5px] bg-[#ef3b26] px-5 text-sm font-semibold text-white transition hover:bg-[#d93220] disabled:cursor-not-allowed disabled:opacity-55"
                        :disabled="sessionProcessing || (canUseFullProduct && !canStartLearning)"
                        @click="emit('startLearning')"
                    >
                        {{ sessionProcessing ? 'Uruchamianie...' : startButtonLabel }} <span aria-hidden="true">→</span>
                    </button>
                    <Link v-else-if="selectedPath === 'memory'" :href="memoryTrainerHref" class="inline-flex min-h-11 items-center gap-3 rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#29353e]">Otwórz trenera <span aria-hidden="true">→</span></Link>
                    <Link v-else-if="selectedPath === 'traffic-signs'" :href="trafficSignLearningHref" class="inline-flex min-h-11 items-center gap-3 rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#29353e]">Trenuj znaki <span aria-hidden="true">→</span></Link>
                    <Link v-else-if="selectedPath === 'ranking'" :href="rankingModeHref" class="inline-flex min-h-11 items-center gap-3 rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#29353e]">Wejdź do rankingu <span aria-hidden="true">→</span></Link>
                    <Link v-else-if="isPjmPath" :href="pjmHref" class="inline-flex min-h-11 items-center gap-3 rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#29353e]">Otwórz moduł PJM <span aria-hidden="true">→</span></Link>
                </div>
            </section>
                </main>
            </div>
        </div>
    </section>
</template>
