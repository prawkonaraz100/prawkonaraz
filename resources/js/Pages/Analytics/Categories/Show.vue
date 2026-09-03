<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppBar from '@/Components/MobileAppBar.vue';
import MobileEmptyState from '@/Components/MobileEmptyState.vue';
import { renderInlineFormattedHtml } from '@/utils/explanationFormatting';
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface CategoryNavigationItem {
    id: number;
    code: string;
    name: string;
    questions_count: number;
}

interface CategorySummary {
    questions_total: number;
    tracked_questions_count: number;
    coverage_pct: number;
    completed_sessions_count: number;
    answered_count: number;
    correct_answers_count: number;
    accuracy_pct: number;
    avg_response_time_ms: number | null;
    readiness_score: number;
    ready_for_review_count: number;
    hard_questions_count: number;
    last_answered_at: string | null;
}

interface BreakdownItem {
    key: string | number;
    label: string;
    tracked_questions_count: number;
    accuracy_pct: number;
    readiness_score: number;
    ready_for_review_count: number;
}

interface WeakSpot {
    question_id: number;
    external_id: string | null;
    prompt: string | null;
    question_type: string | null;
    difficulty: number | null;
    points: number | null;
    accuracy_pct: number;
    mastery_score: number;
    total_attempts: number;
    incorrect_count: number;
    correct_streak: number;
    last_quality: number | null;
    next_review_at: string | null;
}

interface RecentActivityItem {
    date: string;
    answered_count: number;
    correct_count: number;
    accuracy_pct: number;
}

type BreakdownKey = 'question_types' | 'points' | 'difficulty';

const props = defineProps<{
    category: {
        id: number;
        code: string;
        name: string;
        description: string | null;
    };
    categories: CategoryNavigationItem[];
    filters: {
        category: number;
    };
    summary: CategorySummary;
    breakdowns: {
        question_types: BreakdownItem[];
        points: BreakdownItem[];
        difficulty: BreakdownItem[];
    };
    weak_spots: WeakSpot[];
    recent_activity: RecentActivityItem[];
}>();

const breakdownSections = computed(() => [
    {
        key: 'question_types' as BreakdownKey,
        title: 'Typy pytań',
        shortTitle: 'Typ',
        items: props.breakdowns.question_types,
    },
    {
        key: 'points' as BreakdownKey,
        title: 'Punkty',
        shortTitle: 'Punkty',
        items: props.breakdowns.points,
    },
    {
        key: 'difficulty' as BreakdownKey,
        title: 'Trudność',
        shortTitle: 'Trudność',
        items: props.breakdowns.difficulty,
    },
]);

const activeBreakdownKey = ref<BreakdownKey>('question_types');
const activeBreakdownSection = computed(() =>
    breakdownSections.value.find((section) => section.key === activeBreakdownKey.value)
    ?? breakdownSections.value[0],
);
const hasTrackedProgress = computed(() => props.summary.tracked_questions_count > 0);
const hasRecentActivity = computed(() =>
    props.recent_activity.some((item) => item.answered_count > 0),
);
const readinessBarWidth = computed(() =>
    hasTrackedProgress.value ? `${Math.max(props.summary.readiness_score, 3)}%` : '0%',
);

const maxAnsweredCount = computed(() =>
    Math.max(...props.recent_activity.map((item) => item.answered_count), 1),
);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pl-PL', {
              dateStyle: 'medium',
          }).format(new Date(value))
        : 'Brak aktywnosci';

const formatDateTime = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pl-PL', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Brak aktywnosci';

const activityBarWidth = (answeredCount: number) =>
    `${Math.max((answeredCount / maxAnsweredCount.value) * 100, answeredCount > 0 ? 12 : 0)}%`;

const activityBarHeight = (answeredCount: number) =>
    `${Math.max((answeredCount / maxAnsweredCount.value) * 100, answeredCount > 0 ? 12 : 4)}%`;

const formatWeekday = (value: string) =>
    new Intl.DateTimeFormat('pl-PL', { weekday: 'short' })
        .format(new Date(value))
        .replace('.', '');

const formatResponseTime = (value: number | null) =>
    value !== null ? `${Math.round(value / 1000)} s` : 'Brak danych';

const formatErrorsCount = (count: number) => {
    if (count === 1) {
        return '1 błąd';
    }

    const lastDigit = count % 10;
    const lastTwoDigits = count % 100;

    if (lastDigit >= 2 && lastDigit <= 4 && (lastTwoDigits < 12 || lastTwoDigits > 14)) {
        return `${count} błędy`;
    }

    return `${count} błędów`;
};

const changeCategory = (event: Event) => {
    const categoryId = Number((event.target as HTMLSelectElement).value);

    if (!Number.isFinite(categoryId) || categoryId === props.filters.category) {
        return;
    }

    router.get(route('analytics.categories.show', categoryId));
};
</script>

<template>
    <Head :title="`Statystyki ${category.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.18em]">
                    Statystyki
                </p>
                <h2 class="mt-4 text-3xl font-bold leading-tight">
                    {{ category.code }} - {{ category.name }}
                </h2>
            </div>
        </template>

        <div class="min-h-[100svh] bg-white text-[#101828] md:hidden">
            <MobileAppBar
                title="Postęp"
                :subtitle="`Kategoria ${category.code}`"
                :back-href="route('session.index')"
                back-label="Wróć do panelu nauki"
            >
                <template #action>
                    <label v-if="categories.length > 1" class="relative shrink-0">
                        <span class="sr-only">Zmień kategorię prawa jazdy</span>
                        <select
                            :value="filters.category"
                            class="h-10 appearance-none rounded-[0.5rem] border border-[#d0d5dd] bg-white py-0 pl-3 pr-8 text-[0.78rem] font-semibold text-[#344054] focus:border-[#0b5cff] focus:ring-[#0b5cff]"
                            @change="changeCategory"
                        >
                            <option
                                v-for="categoryOption in categories"
                                :key="categoryOption.id"
                                :value="categoryOption.id"
                            >
                                Kat. {{ categoryOption.code }}
                            </option>
                        </select>
                        <svg aria-hidden="true" class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#667085]" viewBox="0 0 24 24" fill="none">
                            <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </label>
                    <span
                        v-else
                        class="inline-flex h-10 items-center rounded-[0.5rem] border border-[#d0d5dd] px-3 text-[0.78rem] font-semibold text-[#344054]"
                    >
                        Kat. {{ category.code }}
                    </span>
                </template>
            </MobileAppBar>

            <div class="px-4 pb-5 pt-5">
                <section aria-labelledby="mobile-readiness-heading">
                    <div class="flex items-end justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[0.72rem] font-semibold text-[#667085]">Opanowanie materiału</p>
                            <h2 id="mobile-readiness-heading" class="mt-1 text-[1.35rem] font-semibold leading-7 text-[#101828]">
                                Kategoria {{ category.code }}
                            </h2>
                            <p class="mt-1 text-[0.74rem] leading-4 text-[#667085]">
                                {{ hasTrackedProgress
                                    ? `${summary.tracked_questions_count} z ${summary.questions_total} pytań ma zapisany postęp.`
                                    : 'Postęp pojawi się po pierwszej serii pytań.' }}
                            </p>
                        </div>
                        <p class="shrink-0 text-[2.55rem] font-semibold leading-none text-[#101828]">
                            {{ summary.readiness_score }}<span class="text-[1.15rem] text-[#667085]">%</span>
                        </p>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#e8edf4]" aria-hidden="true">
                        <div
                            class="h-full rounded-full bg-[#0b5cff] transition-[width] duration-300 motion-reduce:transition-none"
                            :style="{ width: readinessBarWidth }"
                        />
                    </div>

                    <div class="mt-5 grid grid-cols-3 divide-x divide-[#e4e7ec] border-y border-[#e4e7ec] py-4">
                        <div class="pr-3">
                            <p class="text-[1.1rem] font-semibold leading-5">{{ summary.coverage_pct }}%</p>
                            <p class="mt-1 text-[0.66rem] leading-3 text-[#667085]">przerobione</p>
                        </div>
                        <div class="px-3">
                            <p class="text-[1.1rem] font-semibold leading-5">{{ summary.accuracy_pct }}%</p>
                            <p class="mt-1 text-[0.66rem] leading-3 text-[#667085]">skuteczności</p>
                        </div>
                        <div class="pl-3">
                            <p class="text-[1.1rem] font-semibold leading-5">{{ summary.ready_for_review_count }}</p>
                            <p class="mt-1 text-[0.66rem] leading-3 text-[#667085]">do powtórki</p>
                        </div>
                    </div>

                    <Link
                        v-if="summary.ready_for_review_count > 0"
                        :href="route('review-queue.index')"
                        class="mt-4 flex min-h-12 items-center gap-3 rounded-[0.5rem] bg-[#f2f7ff] px-3.5 py-3 text-[#0747a6] transition hover:bg-[#e7f0ff] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                    >
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-[#0b5cff]" aria-hidden="true">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none">
                                <path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[0.82rem] font-semibold">Powtórz zaplanowane pytania</span>
                            <span class="mt-0.5 block text-[0.68rem] text-[#475467]">{{ summary.ready_for_review_count }} czeka w trenerze pamięci</span>
                        </span>
                        <svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none">
                            <path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </Link>

                    <Link
                        v-else-if="!hasTrackedProgress"
                        :href="route('session.index')"
                        class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-[0.5rem] bg-[#0b5cff] px-4 text-[0.84rem] font-semibold text-white transition hover:bg-[#064bd4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                    >
                        Rozpocznij naukę
                    </Link>
                </section>

                <section class="mt-7 border-t border-[#e4e7ec] pt-6" aria-labelledby="mobile-activity-heading">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="mobile-activity-heading" class="text-[1rem] font-semibold leading-5">Ostatnie 7 dni</h2>
                            <p class="mt-1 text-[0.72rem] leading-4 text-[#667085]">
                                {{ hasRecentActivity ? 'Liczba odpowiedzi każdego dnia.' : 'Brak odpowiedzi w tym tygodniu.' }}
                            </p>
                        </div>
                        <p class="text-right text-[0.72rem] font-medium leading-4 text-[#475467]">
                            {{ recent_activity.reduce((total, item) => total + item.answered_count, 0) }} odpowiedzi
                        </p>
                    </div>

                    <div
                        v-if="hasRecentActivity"
                        class="mt-5 grid h-32 grid-cols-7 gap-2"
                        aria-label="Aktywność z ostatnich siedmiu dni"
                    >
                        <div
                            v-for="activity in recent_activity"
                            :key="activity.date"
                            class="flex min-w-0 flex-col items-center"
                        >
                            <div class="flex min-h-0 w-full flex-1 items-end justify-center">
                                <div
                                    class="w-full max-w-6 rounded-t-[0.35rem] bg-[#0b5cff] transition-[height] duration-300 motion-reduce:transition-none"
                                    :class="activity.answered_count > 0 ? 'opacity-100' : 'bg-[#e4e7ec]'"
                                    :style="{ height: activityBarHeight(activity.answered_count) }"
                                    :aria-label="`${formatDate(activity.date)}: ${activity.answered_count} odpowiedzi, ${activity.accuracy_pct}% skuteczności`"
                                />
                            </div>
                            <span class="mt-2 text-[0.6rem] font-medium leading-3 text-[#667085]">
                                {{ formatWeekday(activity.date) }}
                            </span>
                            <span class="mt-0.5 text-[0.64rem] font-semibold leading-3 text-[#344054]">
                                {{ activity.answered_count }}
                            </span>
                        </div>
                    </div>
                    <MobileEmptyState
                        v-else
                        class="mt-4"
                        icon="activity"
                        title="Brak aktywności w tym tygodniu"
                        description="Nowe odpowiedzi pojawią się tutaj po kolejnej serii pytań."
                    />
                </section>

                <section class="mt-7 border-t border-[#e4e7ec] pt-6" aria-labelledby="mobile-weak-spots-heading">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="mobile-weak-spots-heading" class="text-[1rem] font-semibold leading-5">Do poprawy</h2>
                            <p class="mt-1 text-[0.72rem] leading-4 text-[#667085]">Pytania z najniższym poziomem opanowania.</p>
                        </div>
                        <span v-if="weak_spots.length" class="text-[0.72rem] font-semibold text-[#475467]">
                            {{ weak_spots.length }} pytań
                        </span>
                    </div>

                    <div v-if="weak_spots.length" class="mt-4 border-b border-[#e4e7ec]">
                        <article
                            v-for="question in weak_spots"
                            :key="question.question_id"
                            class="border-t border-[#e4e7ec] py-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[0.64rem] font-semibold text-[#667085]">
                                    {{ question.external_id ?? `Pytanie ${question.question_id}` }}
                                </span>
                                <span class="text-[0.66rem] font-semibold text-[#b42318]">
                                    {{ formatErrorsCount(question.incorrect_count) }}
                                </span>
                            </div>
                            <h3
                                class="mobile-analytics-question mt-2 text-[0.84rem] font-medium leading-5 text-[#101828] [&_strong]:font-semibold"
                                v-html="renderInlineFormattedHtml(question.prompt ?? 'Brak treści pytania')"
                            />
                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#eaecf0]" aria-hidden="true">
                                    <div
                                        class="h-full rounded-full bg-[#f04438]"
                                        :style="{ width: `${Math.max(question.mastery_score, 3)}%` }"
                                    />
                                </div>
                                <span class="shrink-0 text-[0.68rem] font-semibold text-[#475467]">
                                    {{ question.mastery_score }}% opanowania
                                </span>
                            </div>
                        </article>
                    </div>
                    <MobileEmptyState
                        v-else
                        class="mt-4"
                        :icon="hasTrackedProgress ? 'check' : 'inbox'"
                        :tone="hasTrackedProgress ? 'success' : 'neutral'"
                        :title="hasTrackedProgress ? 'Brak pilnych pytań' : 'Nie ma jeszcze pytań do poprawy'"
                        :description="hasTrackedProgress
                            ? 'W tej chwili nie wykryliśmy materiału wymagającego pilnej powtórki.'
                            : 'Po pierwszych odpowiedziach pokażemy tutaj pytania, do których warto wrócić.'"
                    />
                </section>

                <section class="mt-7 border-t border-[#e4e7ec] pt-6" aria-labelledby="mobile-breakdown-heading">
                    <h2 id="mobile-breakdown-heading" class="text-[1rem] font-semibold leading-5">Przekrój wyników</h2>
                    <p class="mt-1 text-[0.72rem] leading-4 text-[#667085]">Porównaj skuteczność w różnych grupach pytań.</p>

                    <div class="mt-4 grid grid-cols-3 gap-1 rounded-[0.5rem] bg-[#f2f4f7] p-1" role="tablist" aria-label="Rodzaj przekroju wyników">
                        <button
                            v-for="section in breakdownSections"
                            :key="section.key"
                            type="button"
                            role="tab"
                            class="min-h-9 rounded-[0.4rem] px-2 text-[0.68rem] font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                            :class="activeBreakdownKey === section.key ? 'bg-white text-[#101828] shadow-sm' : 'text-[#667085]'"
                            :aria-selected="activeBreakdownKey === section.key"
                            @click="activeBreakdownKey = section.key"
                        >
                            {{ section.shortTitle }}
                        </button>
                    </div>

                    <div v-if="activeBreakdownSection?.items.length" class="mt-4 border-b border-[#e4e7ec]">
                        <div
                            v-for="item in activeBreakdownSection.items"
                            :key="`${activeBreakdownSection.key}-${item.key}`"
                            class="border-t border-[#e4e7ec] py-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[0.82rem] font-semibold leading-5 text-[#101828]">{{ item.label }}</p>
                                    <p class="mt-0.5 text-[0.66rem] leading-4 text-[#667085]">{{ item.tracked_questions_count }} pytań z postępem</p>
                                </div>
                                <p class="shrink-0 text-[0.8rem] font-semibold text-[#101828]">{{ item.accuracy_pct }}%</p>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#eaecf0]" aria-hidden="true">
                                    <div
                                        class="h-full rounded-full bg-[#0b5cff]"
                                        :style="{ width: `${Math.max(item.readiness_score, 3)}%` }"
                                    />
                                </div>
                                <span class="shrink-0 text-[0.66rem] text-[#667085]">{{ item.readiness_score }}% opanowania</span>
                            </div>
                        </div>
                    </div>
                    <MobileEmptyState
                        v-else
                        class="mt-4"
                        icon="chart"
                        title="Za mało danych do porównania"
                        description="Rozwiąż więcej pytań, aby zobaczyć skuteczność w tej grupie."
                    />
                </section>

                <section class="mt-7 border-t border-[#e4e7ec] pt-6" aria-labelledby="mobile-details-heading">
                    <h2 id="mobile-details-heading" class="text-[1rem] font-semibold leading-5">Szczegóły aktywności</h2>
                    <dl class="mt-4 border-b border-[#e4e7ec]">
                        <div class="flex min-h-12 items-center justify-between gap-4 border-t border-[#e4e7ec] py-3">
                            <dt class="text-[0.76rem] text-[#667085]">Ostatnia odpowiedź</dt>
                            <dd class="text-right text-[0.76rem] font-semibold text-[#344054]">{{ formatDateTime(summary.last_answered_at) }}</dd>
                        </div>
                        <div class="flex min-h-12 items-center justify-between gap-4 border-t border-[#e4e7ec] py-3">
                            <dt class="text-[0.76rem] text-[#667085]">Zakończone sesje</dt>
                            <dd class="text-[0.76rem] font-semibold text-[#344054]">{{ summary.completed_sessions_count }}</dd>
                        </div>
                        <div class="flex min-h-12 items-center justify-between gap-4 border-t border-[#e4e7ec] py-3">
                            <dt class="text-[0.76rem] text-[#667085]">Średni czas odpowiedzi</dt>
                            <dd class="text-[0.76rem] font-semibold text-[#344054]">{{ formatResponseTime(summary.avg_response_time_ms) }}</dd>
                        </div>
                        <div class="flex min-h-12 items-center justify-between gap-4 border-t border-[#e4e7ec] py-3">
                            <dt class="text-[0.76rem] text-[#667085]">Wszystkie odpowiedzi</dt>
                            <dd class="text-[0.76rem] font-semibold text-[#344054]">{{ summary.answered_count }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>

        <div class="hidden space-y-8 md:block">
            <section class="border border-black p-6">
                <p class="text-sm text-black/70">
                    {{ category.description ?? 'Ten ekran pokazuje gotowosc i skutecznosc w wybranej kategorii.' }}
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <Link
                        v-for="categoryOption in categories"
                        :key="categoryOption.id"
                        :href="route('analytics.categories.show', categoryOption.id)"
                        class="border border-black px-4 py-2 text-sm font-medium"
                        :class="filters.category === categoryOption.id ? 'bg-black text-white' : 'bg-white text-black'"
                    >
                        {{ categoryOption.code }} ({{ categoryOption.questions_count }})
                    </Link>
                </div>
            </section>

            <section class="border border-black p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="border border-black p-5">
                        <p class="text-sm">Gotowosc</p>
                        <p class="mt-3 text-4xl font-bold">
                            {{ summary.readiness_score }}%
                        </p>
                    </div>
                    <div class="border border-black p-5">
                        <p class="text-sm">Pokrycie</p>
                        <p class="mt-3 text-4xl font-bold">
                            {{ summary.coverage_pct }}%
                        </p>
                        <p class="mt-2 text-sm text-black/70">
                            {{ summary.tracked_questions_count }} z {{ summary.questions_total }} pytan ma progres
                        </p>
                    </div>
                    <div class="border border-black p-5">
                        <p class="text-sm">Skutecznosc</p>
                        <p class="mt-3 text-4xl font-bold">
                            {{ summary.accuracy_pct }}%
                        </p>
                        <p class="mt-2 text-sm text-black/70">
                            {{ summary.correct_answers_count }} poprawnych z {{ summary.answered_count }} odpowiedzi
                        </p>
                    </div>
                    <div class="border border-black p-5">
                        <p class="text-sm">Sredni czas</p>
                        <p class="mt-3 text-4xl font-bold">
                            {{ summary.avg_response_time_ms !== null ? `${Math.round(summary.avg_response_time_ms / 1000)}s` : '-' }}
                        </p>
                        <p class="mt-2 text-sm text-black/70">
                            Ostatnia aktywnosc: {{ formatDateTime(summary.last_answered_at) }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="border border-black p-5">
                        <p class="text-sm">Sesje zakonczone</p>
                        <p class="mt-3 text-3xl font-bold">
                            {{ summary.completed_sessions_count }}
                        </p>
                    </div>
                    <div class="border border-black p-5">
                        <p class="text-sm">Do powtorki</p>
                        <p class="mt-3 text-3xl font-bold">
                            {{ summary.ready_for_review_count }}
                        </p>
                    </div>
                    <div class="border border-black p-5">
                        <p class="text-sm">Odpowiedzi</p>
                        <p class="mt-3 text-3xl font-bold">
                            {{ summary.answered_count }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 xl:grid-cols-[1.05fr_0.95fr]">
                <section class="border border-black p-6">
                    <p class="text-sm font-medium uppercase tracking-[0.18em]">
                        Rozklad wynikow
                    </p>
                    <h3 class="mt-3 text-2xl font-bold">
                        Gdzie idzie dobrze, a gdzie tracisz punkty
                    </h3>

                    <div class="mt-8 space-y-6">
                        <div
                            v-for="section in breakdownSections"
                            :key="section.title"
                            class="border border-black p-5"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <h4 class="text-lg font-semibold">
                                    {{ section.title }}
                                </h4>
                                <span class="text-xs font-medium uppercase tracking-[0.18em]">
                                    {{ section.items.length }} grup
                                </span>
                            </div>

                            <div v-if="section.items.length" class="mt-5 space-y-3">
                                <div
                                    v-for="item in section.items"
                                    :key="`${section.title}-${item.key}`"
                                    class="border border-black p-4"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold">
                                                {{ item.label }}
                                            </p>
                                            <p class="mt-1 text-sm text-black/70">
                                                {{ item.tracked_questions_count }} pytan z progresem
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs font-medium">
                                            <span class="border border-black px-3 py-1">
                                                Accuracy: {{ item.accuracy_pct }}%
                                            </span>
                                            <span class="border border-black px-3 py-1">
                                                Readiness: {{ item.readiness_score }}%
                                            </span>
                                            <span class="border border-black px-3 py-1">
                                                Due: {{ item.ready_for_review_count }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p
                                v-else
                                class="mt-5 border border-black p-4 text-sm text-black/70"
                            >
                                Brak danych dla tej sekcji.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="space-y-6">
                    <section class="border border-black p-6">
                        <p class="text-sm font-medium uppercase tracking-[0.18em]">
                            Slabe obszary
                        </p>
                        <h3 class="mt-3 text-2xl font-bold">
                            Pytania wymagajace najblizszej uwagi
                        </h3>

                        <div v-if="weak_spots.length" class="mt-8 space-y-4">
                            <article
                                v-for="question in weak_spots"
                                :key="question.question_id"
                                class="border border-black p-5"
                            >
                                <div class="flex flex-wrap items-center gap-3 text-xs font-medium uppercase tracking-[0.18em]">
                                    <span>
                                        {{ question.external_id ?? `Q-${question.question_id}` }}
                                    </span>
                                    <span class="text-black/50">
                                        {{ question.points ?? '-' }} pkt
                                    </span>
                                    <span class="text-black/50">
                                        Poziom {{ question.difficulty ?? '-' }}
                                    </span>
                                </div>
                                <h4
                                    class="mt-4 text-lg font-semibold leading-7 [&_strong]:font-bold"
                                    v-html="renderInlineFormattedHtml(question.prompt ?? 'Brak pytania')"
                                />

                                <div class="mt-4 grid gap-3 md:grid-cols-4">
                                    <div class="border border-black p-3">
                                        <p class="text-xs uppercase tracking-[0.18em]">Mastery</p>
                                        <p class="mt-2 text-xl font-bold">
                                            {{ question.mastery_score }}%
                                        </p>
                                    </div>
                                    <div class="border border-black p-3">
                                        <p class="text-xs uppercase tracking-[0.18em]">Accuracy</p>
                                        <p class="mt-2 text-xl font-bold">
                                            {{ question.accuracy_pct }}%
                                        </p>
                                    </div>
                                    <div class="border border-black p-3">
                                        <p class="text-xs uppercase tracking-[0.18em]">Bledy</p>
                                        <p class="mt-2 text-xl font-bold">
                                            {{ question.incorrect_count }}
                                        </p>
                                    </div>
                                    <div class="border border-black p-3">
                                        <p class="text-xs uppercase tracking-[0.18em]">Next review</p>
                                        <p class="mt-2 text-sm font-bold">
                                            {{ formatDate(question.next_review_at) }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p
                            v-else
                            class="mt-8 border border-black p-5 text-sm text-black/70"
                        >
                            Brak zidentyfikowanych weak spots. To dobry znak albo jeszcze za malo danych.
                        </p>
                    </section>

                    <section class="border border-black p-6">
                        <p class="text-sm font-medium uppercase tracking-[0.18em]">
                            Ostatnie 7 dni
                        </p>
                        <h3 class="mt-3 text-2xl font-bold">
                            Aktywnosc w czasie
                        </h3>

                        <div class="mt-8 space-y-4">
                            <div
                                v-for="activity in recent_activity"
                                :key="activity.date"
                                class="border border-black p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">
                                            {{ formatDate(activity.date) }}
                                        </p>
                                        <p class="mt-1 text-sm text-black/70">
                                            {{ activity.correct_count }} poprawnych z {{ activity.answered_count }}
                                        </p>
                                    </div>
                                    <div class="text-sm font-semibold">
                                        {{ activity.accuracy_pct }}%
                                    </div>
                                </div>

                                <div class="mt-4 h-3 overflow-hidden border border-black bg-white">
                                    <div
                                        class="h-full bg-black transition-all"
                                        :style="{ width: activityBarWidth(activity.answered_count) }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.mobile-analytics-question {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
</style>
