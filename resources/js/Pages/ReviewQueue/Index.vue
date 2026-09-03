<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileEmptyState from '@/Components/MobileEmptyState.vue';
import type { PageProps } from '@/types';
import { computed, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

interface Category {
    id: number;
    code: string;
    name: string;
    short_name: string;
    due_count: number;
}

interface ReviewQuestion {
    question_id: number;
    difficulty: number;
    next_review_at: string | null;
    incorrect_count: number;
    correct_streak: number;
    memory_signal: {
        version: string;
        source?: 'classic_progress' | 'verified_memory' | 'new_candidate';
        verified_memory_signal_version?: string;
        source_policy_version?: string;
        verified_memory_state?: string;
        memory_state: string;
        plan_segment: 'overdue' | 'risky' | 'reinforce';
        leech_score: number;
        stability_score: number;
        difficulty_score: number;
        overdue_days: number;
        recovery_score?: number;
    };
}

interface Stats {
    ready_for_review_count: number;
}

interface ReviewPlan {
    planner_version: string;
    daily_plan_policy_version: string;
    review_day: string;
    daily_target_count: number;
    minimum_session_question_count: number;
    completed_today_count: number;
    daily_remaining_count: number;
    memory_signal_version: string;
    verified_memory_signal_version: string;
    due_count: number;
    candidate_count: number;
    booster_count: number;
    actionable_count?: number;
    verification_candidate_count?: number;
    selected_verification_candidate_count?: number;
    new_candidate_count: number;
    candidate_source_counts: {
        primary: number;
        seen_booster: number;
        new_candidate: number;
    };
    recommended_question_count: number;
    estimated_duration_seconds: number;
    estimated_duration_label: string;
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
    coach: {
        version: string;
        tone: 'empty' | 'recovery' | 'overdue' | 'steady';
        headline: string;
        message: string;
        primary_action_label: string;
        supporting_label: string;
    };
    preview_limit: number;
    preview_count: number;
    has_more: boolean;
}

interface ReviewTelemetry {
    started_sessions_count: number;
    completed_sessions_count: number;
    completed_full_sessions_count: number;
    completed_partial_sessions_count: number;
    completed_unclassified_sessions_count: number;
    completion_rate_percent: number | null;
    full_completion_rate_percent: number | null;
    average_score_percent: number | null;
    average_duration_seconds: number | null;
    average_duration_label: string | null;
    total_answered_count: number;
    full_average_score_percent: number | null;
    full_average_duration_seconds: number | null;
    full_average_duration_label: string | null;
    full_total_answered_count: number;
    last_completed_at: string | null;
    recent_window_limit: number;
    today_review_day: string;
    today_answered_count: number;
    today_correct_count: number;
    today_unknown_count: number;
    today_choice_incorrect_count: number;
    today_needs_recovery_count: number;
}

const props = defineProps<{
    categories: Category[];
    filters: {
        category: number | null;
    };
    stats: Stats;
    plan: ReviewPlan;
    telemetry: ReviewTelemetry;
    questions: ReviewQuestion[];
}>();

const page = usePage<PageProps>();
const selectedCategory = computed(() => props.filters.category ?? null);
const defaultCategoryId = computed(() => props.filters.category ?? page.props.studyContext.targetCategoryId);
const activeCategory = computed(
    () => props.categories.find((category) => category.id === defaultCategoryId.value) ?? null,
);
const recommendedQuestionCount = computed(() => props.plan.recommended_question_count);
const selectedDueCount = computed(() => props.plan.candidate_count ?? props.plan.due_count);
const estimatedDurationLabel = computed(() => props.plan.estimated_duration_label);
const planCoach = computed(() => props.plan.coach);
const urgentPlanCount = computed(() => Math.max(props.plan.actionable_count ?? props.plan.due_count ?? 0, 0));
const boosterPlanCount = computed(() => Math.max(props.plan.booster_count ?? 0, 0));
const waitingAfterSessionCount = computed(() =>
    Math.max(selectedDueCount.value - recommendedQuestionCount.value, 0),
);
const dailyTargetCount = computed(() => Math.max(props.plan.daily_target_count ?? 0, 0));
const firstBlockTargetCount = computed(() => Math.max(props.plan.minimum_session_question_count ?? 0, 0));
const completedTodayCount = computed(() => Math.max(props.plan.completed_today_count ?? 0, 0));
const newCandidatePlanCount = computed(() =>
    Math.max(props.plan.candidate_source_counts?.new_candidate ?? props.plan.new_candidate_count ?? 0, 0),
);
const projectedTodayCount = computed(() =>
    Math.min(completedTodayCount.value + recommendedQuestionCount.value, dailyTargetCount.value),
);
const dailyProgressRatio = computed(() => {
    if (dailyTargetCount.value <= 0) {
        return 0;
    }

    return Math.min(projectedTodayCount.value / dailyTargetCount.value, 1);
});
const dailyProgressPercent = computed(() => {
    return Math.round(dailyProgressRatio.value * 100);
});
const dailyProgressLabel = computed(() => `${completedTodayCount.value} / ${dailyTargetCount.value}`);
const dailyProjectionLabel = computed(() => `${projectedTodayCount.value} / ${dailyTargetCount.value} po sesji`);
const dailyRemainingAfterPlanLabel = computed(() => {
    const remaining = Math.max(dailyTargetCount.value - projectedTodayCount.value, 0);

    return remaining > 0 ? `${remaining} zostanie` : 'Plan domknięty';
});
const dailyProgressAriaLabel = computed(() =>
    `Plan dzienny trenera pamięci: ${completedTodayCount.value} z ${dailyTargetCount.value} pytań wykonane, ${projectedTodayCount.value} z ${dailyTargetCount.value} po tej sesji.`,
);
const memoryRecoveryCount = computed(() =>
    props.plan.memory_state_counts.leech + props.plan.memory_state_counts.relearning,
);
const memoryLearningCount = computed(() =>
    props.plan.memory_state_counts.new + props.plan.memory_state_counts.learning,
);
const memoryStableCount = computed(() =>
    props.plan.memory_state_counts.review + props.plan.memory_state_counts.mastered,
);
const memoryMapItems = computed(() => [
    {
        label: 'Do odzyskania',
        value: memoryRecoveryCount.value,
        description: 'Wracają szybciej, bo pamięć jest tam jeszcze chwiejna.',
    },
    {
        label: 'W nauce',
        value: memoryLearningCount.value,
        description: 'Budują rytm i potrzebują spokojnego utrwalenia.',
    },
    {
        label: 'Stabilne',
        value: memoryStableCount.value,
        description: 'Są w normalnym cyklu powtórek albo już trzymają się dobrze.',
    },
]);
const sessionCompositionItems = computed(() => [
    {
        label: 'Ta sesja',
        value: recommendedQuestionCount.value,
        description: estimatedDurationLabel.value,
    },
    {
        label: 'Pilne',
        value: urgentPlanCount.value,
        description: 'Recovery i terminy na dziś.',
    },
    {
        label: 'Wzmocnienie',
        value: boosterPlanCount.value,
        description: 'Pytania już widziane.',
    },
    {
        label: 'Nowe',
        value: newCandidatePlanCount.value,
        description: 'Limitowana pierwsza ekspozycja.',
    },
    {
        label: 'Po sesji',
        value: waitingAfterSessionCount.value,
        description: waitingAfterSessionCount.value > 0 ? 'Zostanie na później.' : 'Plan czysty.',
    },
]);
const usesShortSafePlan = computed(() =>
    recommendedQuestionCount.value > 0
    && firstBlockTargetCount.value > 0
    && recommendedQuestionCount.value < firstBlockTargetCount.value
    && newCandidatePlanCount.value === 0,
);
const shortSafePlanLabel = computed(() =>
    `Bezpieczny zestaw: ${recommendedQuestionCount.value} / ${firstBlockTargetCount.value}. Nowe pytania nie są dokładane bez wcześniejszej ekspozycji.`,
);
const usesDiscoveryPlan = computed(() => newCandidatePlanCount.value > 0);
const planGuardLabel = computed(() => {
    if (usesDiscoveryPlan.value) {
        return `Nowe pytania: ${newCandidatePlanCount.value}. Pierwsza odpowiedź tylko otwiera cykl pamięci; potwierdzenie przyjdzie w kolejnej powtórce.`;
    }

    return shortSafePlanLabel.value;
});
const memoryRingDegrees = computed(() => {
    return `${Math.round(dailyProgressRatio.value * 360)}deg`;
});
const missionTitle = computed(() =>
    recommendedQuestionCount.value > 0 ? 'Trening pamięci na teraz' : 'Plan pamięci domknięty',
);
const scopeLabel = computed(() => {
    if (activeCategory.value) {
        return `Kategoria ${activeCategory.value.short_name ?? activeCategory.value.code}`;
    }

    return 'Wybierz kategorię';
});
const todayCorrectLabel = computed(() => String(props.telemetry.today_correct_count));
const todayUnknownLabel = computed(() => String(props.telemetry.today_unknown_count));
const todayRecoveryLabel = computed(() => String(props.telemetry.today_needs_recovery_count));

const reviewForm = useForm<{
    license_category_id: number | null;
    mode: string;
    question_count: number;
}>({
    license_category_id: defaultCategoryId.value,
    mode: 'sr_review',
    question_count: recommendedQuestionCount.value,
});
const hasRecommendedReview = computed(() => recommendedQuestionCount.value > 0);
const heroTitle = computed(() => planCoach.value.headline);
const heroDescription = computed(() => planCoach.value.message);
const planSummaryLabel = computed(() => planCoach.value.supporting_label);

watch(defaultCategoryId, (categoryId) => {
    reviewForm.license_category_id = categoryId ?? null;
});

watch(recommendedQuestionCount, (questionCount) => {
    reviewForm.question_count = questionCount;
});

const startReview = () => {
    if (!reviewForm.license_category_id || recommendedQuestionCount.value <= 0) {
        return;
    }

    reviewForm.post(route('study-sessions.store'));
};

const changeCategory = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    const params = value ? { category: Number(value) } : {};

    router.get(route('review-queue.index'), params, {
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Trener pamięci" />

    <AuthenticatedLayout>
        <div class="-mx-4 -my-10 bg-[#f5f6f8] md:hidden">
            <section class="min-h-[100svh] pb-[calc(5rem+env(safe-area-inset-bottom))] pt-[max(env(safe-area-inset-top),0.75rem)] text-[#101828]">
                <header class="border-b border-[#e2e8f0] bg-white px-4 pb-3 pt-2">
                    <div class="flex min-h-11 items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-[#64748b]">Trening</p>
                            <h1 class="mt-0.5 text-[1.7rem] font-semibold leading-tight text-[#101828]">Trener pamięci</h1>
                        </div>
                        <span class="shrink-0 rounded-md bg-[#eef4ff] px-2.5 py-1.5 text-xs font-semibold text-[#023ea4]">
                            {{ scopeLabel }}
                        </span>
                    </div>
                </header>

                <div class="space-y-5 py-5">
                    <section class="border-y border-[#e2e8f0] bg-white px-4 py-5" aria-label="Dzisiejszy trening pamięci">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#64748b]">Dzisiaj</p>
                        <h2 class="mt-2 text-xl font-semibold leading-7 text-[#101828]">{{ heroTitle }}</h2>
                        <p class="mt-2 text-sm leading-6 text-[#475467]">{{ heroDescription }}</p>

                        <div class="mt-5 grid grid-cols-2 divide-x divide-[#e2e8f0] border-y border-[#e2e8f0] py-3">
                            <div class="pr-3">
                                <p class="text-xs font-medium text-[#667085]">Wykonane</p>
                                <p class="mt-1 text-lg font-semibold tabular-nums text-[#101828]">{{ dailyProgressLabel }}</p>
                            </div>
                            <div class="pl-3">
                                <p class="text-xs font-medium text-[#667085]">Po tej sesji</p>
                                <p class="mt-1 text-lg font-semibold tabular-nums text-[#101828]">{{ dailyProjectionLabel }}</p>
                            </div>
                        </div>

                        <form v-if="hasRecommendedReview" class="mt-5" @submit.prevent="startReview">
                            <button
                                type="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#0b5cff] px-4 text-sm font-semibold text-white shadow-[0_8px_18px_rgba(11,92,255,0.18)] transition hover:bg-[#023ea4] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="reviewForm.processing || !reviewForm.license_category_id || recommendedQuestionCount === 0"
                            >
                                <span>{{ reviewForm.processing ? 'Uruchamianie...' : 'Rozpocznij trening' }}</span>
                                <span aria-hidden="true">→</span>
                            </button>
                        </form>

                        <p v-if="hasRecommendedReview" class="mt-3 text-center text-xs leading-5 text-[#667085]">
                            {{ `${recommendedQuestionCount} pytań · ${estimatedDurationLabel}` }}
                        </p>

                        <MobileEmptyState
                            v-else
                            class="mt-5"
                            title="Powtórki na teraz zakończone"
                            :description="planSummaryLabel"
                            icon="check"
                            tone="success"
                        >
                            <Link
                                :href="route('session.index')"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#cbd5e1] bg-white px-3 text-sm font-semibold text-[#334155] transition hover:bg-[#f8fafc]"
                            >
                                Wróć do nauki
                            </Link>
                        </MobileEmptyState>
                    </section>

                    <section class="px-4" aria-labelledby="memory-state-title">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h2 id="memory-state-title" class="text-base font-semibold text-[#101828]">Stan pamięci</h2>
                            <span class="text-xs font-medium text-[#667085]">{{ dailyRemainingAfterPlanLabel }}</span>
                        </div>
                        <div class="overflow-hidden rounded-lg border border-[#dbe3ec] bg-white divide-y divide-[#e7edf4]">
                            <div
                                v-for="item in memoryMapItems"
                                :key="item.label"
                                class="flex min-h-16 items-center justify-between gap-4 px-4 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#101828]">{{ item.label }}</p>
                                    <p class="mt-0.5 text-xs leading-4 text-[#667085]">{{ item.description }}</p>
                                </div>
                                <p class="shrink-0 text-xl font-semibold tabular-nums text-[#101828]">{{ item.value }}</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="categories.length > 0" class="px-4" aria-labelledby="memory-category-title">
                        <h2 id="memory-category-title" class="mb-2 text-base font-semibold text-[#101828]">Kategoria treningu</h2>
                        <label class="relative block">
                            <span class="sr-only">Wybierz kategorię treningu</span>
                            <select
                                class="h-12 w-full appearance-none rounded-lg border border-[#dbe3ec] bg-white px-3 pr-10 text-sm font-semibold text-[#101828] shadow-[0_4px_12px_rgba(15,23,42,0.035)] focus:border-[#0b5cff] focus:outline-none focus:ring-2 focus:ring-[#0b5cff]/20"
                                :value="selectedCategory ?? ''"
                                @change="changeCategory"
                            >
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    Kategoria {{ category.code }} · {{ category.due_count }} do powtórki
                                </option>
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#667085]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                    </section>
                </div>
            </section>
        </div>

        <div class="mx-auto hidden max-w-7xl space-y-8 px-4 sm:px-6 md:block lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                        Trener pamięci
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-950">
                        {{ missionTitle }}
                    </h2>
                </div>
            </div>

            <section class="border-y border-slate-200 bg-white py-8">
                <div class="grid items-center gap-10 lg:grid-cols-[0.92fr_1.08fr]">
                    <div class="mx-auto w-full max-w-[22rem]">
                        <div
                            class="grid aspect-square place-items-center rounded-full p-3"
                            :style="{
                                background: `conic-gradient(#0d47a1 0deg, #0d47a1 ${memoryRingDegrees}, #e2e8f0 ${memoryRingDegrees}, #e2e8f0 360deg)`,
                            }"
                            role="img"
                            :aria-label="dailyProgressAriaLabel"
                        >
                            <div class="grid h-full w-full place-items-center rounded-full border border-slate-200 bg-white p-8 text-center">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#0d47a1]">
                                        plan dzienny
                                    </p>
                                    <p class="mt-3 text-7xl font-semibold leading-none text-slate-950">
                                        {{ dailyProgressPercent }}%
                                    </p>
                                    <p class="mt-2 text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        {{ dailyProgressLabel }} pytań
                                    </p>
                                    <p class="mt-4 text-sm font-semibold text-slate-950">
                                        {{ dailyProjectionLabel }}
                                    </p>
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                        {{ dailyRemainingAfterPlanLabel }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ scopeLabel }}
                        </p>
                        <h3 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                            {{ heroTitle }}
                        </h3>
                        <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
                            {{ heroDescription }}
                        </p>

                        <form class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center" @submit.prevent="startReview">
                            <button
                                v-if="hasRecommendedReview"
                                type="submit"
                                class="inline-flex h-12 w-full items-center justify-center rounded-md bg-[#0d47a1] px-7 text-base font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                :disabled="reviewForm.processing || !reviewForm.license_category_id || recommendedQuestionCount === 0"
                            >
                                {{ reviewForm.processing ? 'Startowanie...' : planCoach.primary_action_label }}
                            </button>
                            <a
                                v-else
                                :href="route('session.index')"
                                class="inline-flex h-12 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-7 text-base font-semibold text-slate-950 transition hover:border-[#0d47a1] hover:text-[#0d47a1] sm:w-auto"
                            >
                                Wróć do nauki
                            </a>
                            <p class="text-sm font-medium text-slate-500">
                                {{ planSummaryLabel }}
                            </p>
                        </form>

                        <div v-if="categories.length > 0" class="mt-7 max-w-sm">
                            <label for="review_scope" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                zakres
                            </label>
                            <select
                                id="review_scope"
                                class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-950 shadow-sm focus:border-[#0d47a1] focus:ring-[#0d47a1]"
                                :value="selectedCategory ?? ''"
                                @change="changeCategory"
                            >
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    Kategoria {{ category.code }} - {{ category.due_count }} w planie
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="grid gap-6 py-7 lg:grid-cols-[0.78fr_1.22fr] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            mapa pamięci
                        </p>
                        <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
                            Co system widzi w tym zestawie
                        </h3>
                    </div>

                    <div class="grid divide-y divide-slate-200 border-y border-slate-200 md:grid-cols-3 md:divide-x md:divide-y-0 md:border-y-0">
                        <article
                            v-for="item in memoryMapItems"
                            :key="item.label"
                            class="py-5 md:px-5 md:first:pl-0 md:last:pr-0"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ item.label }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">
                                {{ item.value }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ item.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="grid gap-6 py-7 lg:grid-cols-[0.78fr_1.22fr] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                            skład sesji
                        </p>
                        <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">
                            Co wejdzie do treningu
                        </h3>
                    </div>

                    <div class="grid divide-y divide-slate-200 border-y border-slate-200 md:grid-cols-5 md:divide-x md:divide-y-0 md:border-y-0">
                        <article
                            v-for="item in sessionCompositionItems"
                            :key="item.label"
                            class="py-5 md:px-5 md:first:pl-0 md:last:pr-0"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ item.label }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">
                                {{ item.value }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ item.description }}
                            </p>
                        </article>
                    </div>

                    <p
                        v-if="usesShortSafePlan || usesDiscoveryPlan"
                        class="border-t border-slate-200 pt-4 text-sm font-semibold text-slate-600 lg:col-start-2"
                    >
                        {{ planGuardLabel }}
                    </p>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="grid divide-y divide-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
                    <div class="py-5 sm:px-5 sm:first:pl-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            dzisiaj
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ dailyProgressLabel }}
                        </p>
                    </div>
                    <div class="py-5 sm:px-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            odzyskane
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ todayCorrectLabel }}
                        </p>
                    </div>
                    <div class="py-5 sm:px-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            nie wiem
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ todayUnknownLabel }}
                        </p>
                    </div>
                    <div class="py-5 sm:px-5 sm:last:pr-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            do odzyskania
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ todayRecoveryLabel }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
