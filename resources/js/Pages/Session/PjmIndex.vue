<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import pjmSignLanguageSymbol from '../../../images/session/pjm-sign-language-symbol.png';

interface Category {
    id: number;
    code: string;
    name: string;
    short_name: string;
}

interface Coverage {
    total_questions: number;
    pjm_questions: number;
    missing_questions: number;
    coverage_percent: number;
}

interface GroupOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: {
        all: number;
        unanswered: number;
        incorrect: number;
        correct: number;
        memorized: number;
    };
    answered_count: number;
    progress_percent: number;
}

interface GroupBucket {
    label: string;
    options: GroupOption[];
}

interface PjmProgress {
    total_questions: number;
    answered_questions: number;
    unanswered_questions: number;
    incorrect_questions: number;
    correct_questions: number;
    memorized_questions: number;
    progress_percent: number;
    current_topic_id: number | null;
    review_topic_id: number | null;
    topic_groups: GroupBucket[];
}

const props = defineProps<{
    category: Category | null;
    coverage: Coverage | null;
    progress: PjmProgress | null;
}>();

type PjmQuestionStatus = 'all' | 'unanswered';
type PjmQuestionCountStrategy = 'fixed' | 'topic_remaining';

const topicGroups = computed(() => props.progress?.topic_groups ?? []);
const topicOptions = computed(() => topicGroups.value.flatMap((group) => group.options));
const firstTopic = computed(() => topicOptions.value[0] ?? null);
const currentTopic = computed<GroupOption | null>(() => {
    const currentTopicId = props.progress?.current_topic_id ?? null;

    return topicOptions.value.find((topic) => topic.id === currentTopicId)
        ?? topicOptions.value.find((topic) => topic.counts.unanswered > 0)
        ?? firstTopic.value;
});
const canStart = computed(() => Boolean(props.category && topicOptions.value.length > 0));
const hasUnansweredQuestions = computed(() => (props.progress?.unanswered_questions ?? 0) > 0);
const progressPercent = computed(() => clampPercent(props.progress?.progress_percent ?? 0));
const roundedProgressPercent = computed(() => Math.round(progressPercent.value));
const categoryLabel = computed(() => props.category?.short_name ?? props.category?.code ?? 'PJM');
const answeredQuestions = computed(() => props.progress?.answered_questions ?? 0);
const totalQuestions = computed(() => props.progress?.total_questions ?? props.coverage?.pjm_questions ?? 0);
const unansweredQuestions = computed(() => props.progress?.unanswered_questions ?? 0);
const incorrectQuestions = computed(() => props.progress?.incorrect_questions ?? 0);
const pjmQuestions = computed(() => props.coverage?.pjm_questions ?? totalQuestions.value);
const defaultQuestionCount = computed(() => questionCountForTopic(currentTopic.value));
const selectedStudyMode = ref<PjmQuestionStatus>('unanswered');
const effectiveStudyMode = computed<PjmQuestionStatus>(() => {
    if (selectedStudyMode.value === 'unanswered' && !hasUnansweredQuestions.value) {
        return 'all';
    }

    return selectedStudyMode.value;
});
const startButtonLabel = computed(() =>
    effectiveStudyMode.value === 'unanswered' ? 'Start PJM' : 'Powtórz',
);
const currentTopicModeQuestionCount = computed(() =>
    questionCountForTopic(currentTopic.value, effectiveStudyMode.value),
);
const currentTopicLabel = computed(() => currentTopic.value?.label ?? 'Brak działu');
const primaryActionLabel = computed(() =>
    effectiveStudyMode.value === 'unanswered'
        ? `Start PJM. Aktualny dział: ${currentTopicLabel.value}. Pytania: ${currentTopicModeQuestionCount.value}.`
        : `Powtórz dział PJM. Aktualny dział: ${currentTopicLabel.value}. Pytania: ${currentTopicModeQuestionCount.value}.`,
);

const form = useForm<{
    question_topic_id: number | null;
    question_count: number;
    question_count_strategy: PjmQuestionCountStrategy;
    question_status: PjmQuestionStatus;
    randomize_order: boolean;
}>({
    question_topic_id: null,
    question_count: defaultQuestionCount.value,
    question_count_strategy: 'topic_remaining',
    question_status: 'unanswered',
    randomize_order: false,
});

function clampPercent(value: number): number {
    return Math.min(Math.max(value, 0), 100);
}

function progressWidth(value: number): string {
    return `${clampPercent(value)}%`;
}

function questionCountForTopic(topic: GroupOption | null, questionStatus: PjmQuestionStatus = questionStatusForTopic(topic)): number {
    const availableQuestions = topic
        ? (questionStatus === 'unanswered' ? topic.counts.unanswered : topic.questions_count)
        : (props.coverage?.pjm_questions ?? 1);

    return Math.max(availableQuestions, 1);
}

function questionStatusForTopic(topic: GroupOption | null): PjmQuestionStatus {
    if (!topic || topic.counts.unanswered > 0) {
        return 'unanswered';
    }

    return 'all';
}

function selectStudyMode(mode: PjmQuestionStatus): void {
    if (mode === 'unanswered' && !hasUnansweredQuestions.value) {
        return;
    }

    selectedStudyMode.value = mode;
}

function startSession(
    topic: GroupOption | null = currentTopic.value,
    questionStatus: PjmQuestionStatus = effectiveStudyMode.value,
): void {
    const resolvedQuestionStatus = questionStatus === 'unanswered' && (topic?.counts.unanswered ?? 0) <= 0
        ? 'all'
        : questionStatus;

    form.question_topic_id = topic?.id ?? null;
    form.question_count = topic ? 1 : Math.min(questionCountForTopic(topic, resolvedQuestionStatus), 40);
    form.question_count_strategy = topic ? 'topic_remaining' : 'fixed';
    form.question_status = resolvedQuestionStatus;
    form.randomize_order = false;
    form.post(route('session.pjm.store'));
}
</script>

<template>
    <Head title="Nauka PJM" />

    <AuthenticatedLayout>
        <section class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[76rem] space-y-8">
                <header class="grid gap-6 border-b border-slate-200 pb-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <div class="flex min-w-0 items-center gap-4">
                        <img
                            :src="pjmSignLanguageSymbol"
                            alt=""
                            aria-hidden="true"
                            class="h-20 w-20 shrink-0 rounded-md object-contain sm:h-24 sm:w-24"
                        >
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                                {{ categoryLabel }}
                            </p>
                            <h1 class="mt-2 text-4xl font-semibold leading-none tracking-tight text-slate-950 md:text-5xl">
                                Nauka PJM
                            </h1>
                            <p class="mt-3 max-w-[34rem] truncate text-base text-slate-600">
                                {{ currentTopicLabel }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-md bg-[#0d47a1] px-7 text-base font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400 sm:w-auto sm:min-w-[15rem]"
                        :aria-label="primaryActionLabel"
                        :disabled="form.processing || !canStart"
                        @click="startSession()"
                    >
                        <span
                            aria-hidden="true"
                            class="h-0 w-0 shrink-0 border-y-[0.55rem] border-l-[0.85rem] border-y-transparent border-l-current"
                        />
                        <span>{{ form.processing ? 'Start...' : startButtonLabel }}</span>
                    </button>
                </header>

                <section
                    class="grid border-y border-slate-200 sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-slate-200"
                    aria-label="Postęp PJM"
                >
                    <div class="flex min-h-[6rem] items-center gap-4 border-b border-slate-200 py-4 sm:px-4 xl:border-b-0">
                        <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center text-3xl font-semibold text-[#0d47a1]">
                            ✓
                        </span>
                        <div>
                            <p class="text-2xl font-semibold leading-none tracking-tight text-slate-950">
                                {{ answeredQuestions }}/{{ totalQuestions }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Gotowe
                            </p>
                        </div>
                    </div>

                    <div class="flex min-h-[6rem] items-center gap-4 border-b border-slate-200 py-4 sm:px-4 xl:border-b-0">
                        <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center text-3xl font-semibold text-slate-950">
                            →
                        </span>
                        <div>
                            <p class="text-2xl font-semibold leading-none tracking-tight text-slate-950">
                                {{ unansweredQuestions }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Dalej
                            </p>
                        </div>
                    </div>

                    <div class="flex min-h-[6rem] items-center gap-4 border-b border-slate-200 py-4 sm:border-b-0 sm:px-4">
                        <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center text-3xl font-semibold text-slate-950">
                            !
                        </span>
                        <div>
                            <p class="text-2xl font-semibold leading-none tracking-tight text-slate-950">
                                {{ incorrectQuestions }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Błędy
                            </p>
                        </div>
                    </div>

                    <div class="flex min-h-[6rem] items-center gap-4 py-4 sm:px-4">
                        <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center text-xl font-bold text-[#0d47a1]">
                            PJM
                        </span>
                        <div>
                            <p class="text-2xl font-semibold leading-none tracking-tight text-slate-950">
                                {{ pjmQuestions }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Pytania
                            </p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.45fr)] lg:items-stretch">
                    <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                                    Teraz
                                </p>
                                <h2 class="mt-2 truncate text-xl font-semibold text-slate-950">
                                    {{ currentTopicLabel }}
                                </h2>
                            </div>
                            <p class="text-3xl font-semibold tracking-tight text-slate-950">
                                {{ roundedProgressPercent }}%
                            </p>
                        </div>

                        <div
                            class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200"
                            role="progressbar"
                            aria-label="Postęp PJM"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="roundedProgressPercent"
                        >
                            <div
                                class="h-full rounded-full bg-[#0d47a1] transition-[width] duration-300"
                                :style="{ width: progressWidth(progressPercent) }"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 rounded-md border border-slate-200 bg-white p-2">
                        <button
                            type="button"
                            class="flex min-h-[5rem] flex-col items-center justify-center rounded-md px-3 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45"
                            :class="effectiveStudyMode === 'unanswered' ? 'bg-[#0d47a1] text-white' : 'bg-white text-slate-950 hover:bg-slate-50'"
                            :disabled="!hasUnansweredQuestions"
                            aria-label="Ucz się tylko nieprzerobionych pytań PJM"
                            @click="selectStudyMode('unanswered')"
                        >
                            <span
                                aria-hidden="true"
                                class="h-0 w-0 shrink-0 border-y-[0.5rem] border-l-[0.75rem] border-y-transparent border-l-current"
                            />
                            <span class="mt-2 text-sm font-semibold">Nowe</span>
                            <span class="mt-0.5 text-xs opacity-80">{{ currentTopicModeQuestionCount }}</span>
                        </button>

                        <button
                            type="button"
                            class="flex min-h-[5rem] flex-col items-center justify-center rounded-md px-3 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2"
                            :class="effectiveStudyMode === 'all' ? 'bg-[#0d47a1] text-white' : 'bg-white text-slate-950 hover:bg-slate-50'"
                            aria-label="Powtórz cały dział PJM"
                            @click="selectStudyMode('all')"
                        >
                            <span aria-hidden="true" class="text-[1.8rem] leading-none">↻</span>
                            <span class="mt-2 text-sm font-semibold">Cały</span>
                            <span class="mt-0.5 text-xs opacity-80">{{ questionCountForTopic(currentTopic, 'all') }}</span>
                        </button>
                    </div>
                </section>

                <div
                    v-if="!canStart"
                    class="flex items-center gap-4 border-y border-slate-200 py-4 text-slate-700"
                    role="status"
                >
                    <span aria-hidden="true" class="text-[2rem] font-semibold">!</span>
                    <p class="text-[0.95rem] font-semibold">Brak działów PJM</p>
                </div>

                <div class="flex">
                    <Link
                        href="/nauka"
                        class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-950 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2"
                        aria-label="Wróć do wyboru nauki"
                    >
                        <span aria-hidden="true">←</span>
                        <span>Wróć</span>
                    </Link>
                </div>

                <section
                    v-if="topicGroups.length > 0"
                    class="space-y-6 border-t border-slate-200 pt-8"
                    aria-labelledby="pjm-topic-heading"
                >
                    <h2 id="pjm-topic-heading" class="text-2xl font-semibold tracking-tight text-slate-950">
                        Działy
                    </h2>

                    <section
                        v-for="group in topicGroups"
                        :key="group.label"
                        class="space-y-3"
                    >
                        <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            {{ group.label }}
                        </h3>

                        <div class="divide-y divide-slate-200 border-y border-slate-200">
                            <article
                                v-for="topic in group.options"
                                :key="topic.id"
                                class="py-5"
                                :class="currentTopic?.id === topic.id ? 'bg-slate-50 px-4' : ''"
                            >
                                <div class="flex flex-col gap-5 lg:grid lg:grid-cols-[minmax(0,1fr)_7rem_16rem] lg:items-center">
                                    <div class="min-w-0">
                                        <p
                                            v-if="currentTopic?.id === topic.id && hasUnansweredQuestions"
                                            class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]"
                                        >
                                            Teraz
                                        </p>
                                        <h4 class="mt-1 text-lg font-semibold leading-6 text-slate-950">
                                            {{ topic.label }}
                                        </h4>
                                    </div>

                                    <div class="grid w-full max-w-[8rem] grid-cols-2 text-center lg:max-w-none">
                                        <div>
                                            <p class="text-xl font-semibold tracking-tight text-slate-950">{{ topic.counts.unanswered }}</p>
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">Nowe</p>
                                        </div>
                                        <div>
                                            <p class="text-xl font-semibold tracking-tight text-slate-950">{{ topic.counts.incorrect }}</p>
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">Błędy</p>
                                        </div>
                                    </div>

                                    <div>
                                        <div
                                            class="h-2 overflow-hidden rounded-full bg-slate-200"
                                            role="progressbar"
                                            :aria-label="`Postęp działu ${topic.label}`"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            :aria-valuenow="Math.round(clampPercent(topic.progress_percent))"
                                        >
                                            <div
                                                class="h-full rounded-full bg-[#0d47a1] transition-[width] duration-300"
                                                :style="{ width: progressWidth(topic.progress_percent) }"
                                            />
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-950 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45"
                                                :disabled="form.processing || topic.counts.unanswered <= 0"
                                                :aria-label="`Startuj nieprzerobione pytania PJM z działu ${topic.label}`"
                                                @click="startSession(topic, 'unanswered')"
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    class="h-0 w-0 shrink-0 border-y-[0.36rem] border-l-[0.56rem] border-y-transparent border-l-current"
                                                />
                                                <span>Start</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-md bg-[#0d47a1] px-3 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="form.processing"
                                                :aria-label="`Powtórz cały dział PJM: ${topic.label}`"
                                                @click="startSession(topic, 'all')"
                                            >
                                                <span aria-hidden="true" class="text-lg leading-none">↻</span>
                                                <span>Cały</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </section>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
