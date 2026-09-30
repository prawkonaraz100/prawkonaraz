<script setup lang="ts">
import QuestionExplanationRuntimeBlock from '@/Components/QuestionExplanationRuntimeBlock.vue';
import QuestionImageWithAnnotations from '@/Components/QuestionImageWithAnnotations.vue';
import QuestionResultMediaFallback from '@/Components/QuestionResultMediaFallback.vue';
import QuestionVideoFrameWithAnnotations from '@/Components/QuestionVideoFrameWithAnnotations.vue';
import MobileAppBar from '@/Components/MobileAppBar.vue';
import MobileBottomNavigation from '@/Components/MobileBottomNavigation.vue';
import MobileEmptyState from '@/Components/MobileEmptyState.vue';
import SessionExamLayout from '@/Layouts/SessionExamLayout.vue';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    renderExplanationHtml,
    renderExamPromptHtml,
} from '@/utils/explanationFormatting';
import {
    resolveImageAnnotations,
    resolveVideoFrameAnnotations,
} from '@/utils/explanationAnnotations';
import { resolveResultMediaPreviewUrl } from '@/utils/questionMediaSources';
import { computed } from 'vue';

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

interface ResultItem {
    id: number;
    sequence_number: number;
    prompt: string | null;
    structure_scope: string | null;
    points: number | null;
    explanation: string | null;
    correct_answer: string | null;
    correct_answer_text: string | null;
    selected_answer: string | null;
    selected_answer_text: string | null;
    is_correct: boolean | null;
    response_time_ms: number | null;
    media: QuestionMedia[];
    explanation_asset: ExplanationAsset | null;
    explanation_sign_references?: ExplanationSignReference[];
    explanation_annotations: ExplanationAnnotation[];
}

interface QuestionMedia {
    kind: string;
    url: string | null;
    full_url?: string | null;
    thumb_url?: string | null;
    poster_url: string | null;
    mime_type: string | null;
    width?: number | null;
    height?: number | null;
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

interface ExamSectionResult {
    answered: number;
    total: number;
    correct: number;
    incorrect: number;
    unanswered: number;
    earned_points: number;
    available_points: number;
}

interface ExamResultSummary {
    passed: boolean;
    earned_points: number;
    available_points: number;
    pass_threshold: number;
    official_max_points: number;
    correct_answers_count: number;
    incorrect_answers_count: number;
    unanswered_count: number;
    basic: ExamSectionResult;
    specialist: ExamSectionResult;
}

const props = defineProps<{
    session: SessionSummary;
    progress: ProgressSummary;
    results: ResultItem[];
    completionTiming: CompletionTiming;
    examResult: ExamResultSummary;
}>();
const page = usePage<PageProps>();

const passed = computed(() => props.examResult.passed);
const visualExplanationsEnabled = computed(
    () => page.props.studyContext?.visualExplanationsEnabled ?? true,
);
const statusLabel = computed(() => passed.value ? 'Egzamin zdany' : 'Egzamin niezdany');

const sectionCards = computed(() => [
    {
        key: 'basic',
        label: 'Pytania podstawowe',
        ...props.examResult.basic,
    },
    {
        key: 'specialist',
        label: 'Pytania specjalistyczne',
        ...props.examResult.specialist,
    },
]);

const incorrectOrUnansweredResults = computed(() =>
    props.results.filter((result) => result.is_correct !== true),
);
const answeredAnswersCount = computed(() =>
    props.examResult.correct_answers_count + props.examResult.incorrect_answers_count,
);
const resultProgressWidth = computed(() => {
    if (props.examResult.official_max_points <= 0) {
        return '0%';
    }

    return `${Math.min((props.examResult.earned_points / props.examResult.official_max_points) * 100, 100)}%`;
});

const formatDuration = (seconds: number | null) => {
    if (seconds === null) {
        return 'Brak danych';
    }

    const safeSeconds = Math.max(seconds, 0);
    const minutes = Math.floor(safeSeconds / 60);
    const remainingSeconds = safeSeconds % 60;

    if (minutes <= 0) {
        return `${remainingSeconds}s`;
    }

    return `${minutes} min ${String(remainingSeconds).padStart(2, '0')} s`;
};

const sectionProgressWidth = (section: ExamSectionResult) => {
    if (section.available_points <= 0) {
        return '0%';
    }

    return `${Math.min((section.earned_points / section.available_points) * 100, 100)}%`;
};

const formatQuestionCount = (count: number) => {
    if (count === 1) {
        return '1 pytanie';
    }

    const lastDigit = count % 10;
    const lastTwoDigits = count % 100;

    if (lastDigit >= 2 && lastDigit <= 4 && (lastTwoDigits < 12 || lastTwoDigits > 14)) {
        return `${count} pytania`;
    }

    return `${count} pytań`;
};

const answerDisplay = (answer: string | null, answerText: string | null) => {
    if (!answer) {
        return 'Brak odpowiedzi';
    }

    return answerText ? `${answer} — ${answerText}` : answer;
};

const questionStatusLabel = (result: ResultItem) => {
    if (result.is_correct === true) {
        return 'Poprawna';
    }

    return result.selected_answer === null ? 'Brak odpowiedzi' : 'Błędna';
};

const scopeLabel = (scope: string | null) =>
    scope === 'SPECJALISTYCZNY' ? 'Specjalistyczne' : 'Podstawowe';

const explanationHtml = (result: ResultItem) =>
    renderExplanationHtml(result.explanation);
const resultImageAnnotations = (result: ResultItem) =>
    resolveImageAnnotations(result.explanation_annotations);
const resultVideoFrameAnnotations = (result: ResultItem) =>
    resolveVideoFrameAnnotations(result.explanation_annotations);
const hasResultExplanationAnnotations = (result: ResultItem) =>
    visualExplanationsEnabled.value
    && result.media[0]?.kind === 'image'
    && resultImageAnnotations(result).length > 0;
const hasResultVideoFrameAnnotations = (result: ResultItem) =>
    visualExplanationsEnabled.value
    && result.media[0]?.kind === 'video'
    && resultVideoFrameAnnotations(result).length > 0;
const resultVideoFrameTimeSeconds = (result: ResultItem) =>
    resultVideoFrameAnnotations(result).find((annotation) => annotation.frame_time_seconds !== null)?.frame_time_seconds ?? 0;

const resultMediaPreviewUrl = (media: QuestionMedia) =>
    resolveResultMediaPreviewUrl(media);

const prepareResultVideoPreview = (event: Event) => {
    const video = event.target as HTMLVideoElement | null;

    if (!video || Number.isNaN(video.duration) || video.duration <= 0.25) {
        return;
    }

    try {
        video.currentTime = Math.max(video.duration - 0.2, 0);
    } catch {
        // Browser may briefly reject seek before metadata settles.
    }
};

const freezeResultVideoPreview = (event: Event) => {
    const video = event.target as HTMLVideoElement | null;

    video?.pause();
};
</script>

<template>
    <Head title="Wynik egzaminu" />

    <SessionExamLayout>
        <div class="-mx-3 -my-3 min-h-[100svh] bg-white text-[#101828] sm:-mx-4 sm:-my-4 md:hidden">
            <MobileAppBar
                title="Wynik egzaminu"
                :subtitle="`Kategoria ${session.license_category_code ?? '—'}`"
                :back-href="route('session.index')"
                back-label="Wróć do panelu nauki"
            >
                <template #action>
                    <span
                        class="inline-flex h-9 shrink-0 items-center rounded-[0.5rem] border border-[#d0d5dd] px-3 text-[0.72rem] font-semibold text-[#475467]"
                        :aria-label="`Udzielono ${answeredAnswersCount} odpowiedzi z ${progress.total} pytań`"
                    >
                        {{ answeredAnswersCount }}/{{ progress.total }} odpowiedzi
                    </span>
                </template>
            </MobileAppBar>

            <main class="pb-5">
                <section
                    class="border-b border-[#d1d8df] bg-white px-4 py-6"
                    aria-labelledby="mobile-exam-status-heading"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                            :class="passed ? 'bg-[#dcfae6] text-[#067647]' : 'bg-[#fee4e2] text-[#b42318]'"
                            aria-hidden="true"
                        >
                            <svg v-if="passed" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <svg v-else class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 id="mobile-exam-status-heading" class="text-[1.55rem] font-semibold leading-8 text-[#101828]">
                                {{ statusLabel }}
                            </h2>
                            <p class="mt-1 text-[0.76rem] leading-5 text-[#667085]">
                                {{ passed
                                    ? 'Osiągnięto wymagany próg punktowy.'
                                    : 'Przejrzyj błędy przed kolejnym podejściem.' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div>
                            <p class="text-[2.7rem] font-semibold leading-none text-[#101828]">
                                {{ examResult.earned_points }}<span class="text-[1.15rem] text-[#667085]"> / {{ examResult.official_max_points }} pkt</span>
                            </p>
                            <p class="mt-2 text-[0.7rem] text-[#667085]">
                                Próg zaliczenia: {{ examResult.pass_threshold }} pkt
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#e5e9ee]" aria-hidden="true">
                        <div
                            class="h-full rounded-full transition-[width] duration-300 motion-reduce:transition-none"
                            :class="passed ? 'bg-[#12b76a]' : 'bg-[#f04438]'"
                            :style="{ width: resultProgressWidth }"
                        />
                    </div>

                    <div class="mt-5 grid gap-2">
                        <a
                            v-if="incorrectOrUnansweredResults.length > 0"
                            href="#mobile-exam-review"
                            class="inline-flex min-h-12 w-full items-center justify-center rounded-[4px] border border-[#d1d8df] bg-white px-4 text-[0.84rem] font-semibold text-[#223547] transition hover:bg-[#f7f8fa] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#334155] focus-visible:ring-offset-2"
                        >
                            Przejrzyj {{ formatQuestionCount(incorrectOrUnansweredResults.length) }}
                        </a>
                        <Link
                            :href="route('session.index')"
                            class="inline-flex min-h-12 w-full items-center justify-center rounded-[4px] border border-[#d2b35b] bg-[#efc54f] px-4 text-[0.84rem] font-semibold text-[#463309] transition hover:bg-[#e8bd4c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#334155]"
                        >
                            Wróć do nauki
                        </Link>
                    </div>
                </section>

                <section class="px-4 py-6" aria-labelledby="mobile-exam-summary-heading">
                    <h2 id="mobile-exam-summary-heading" class="text-[1rem] font-semibold leading-5">Odpowiedzi</h2>
                    <div class="mt-4 grid grid-cols-3 divide-x divide-[#e4e7ec] border-y border-[#e4e7ec] py-4">
                        <div class="pr-3">
                            <p class="text-[1.15rem] font-semibold text-[#067647]">{{ examResult.correct_answers_count }}</p>
                            <p class="mt-1 text-[0.66rem] text-[#667085]">poprawnych</p>
                        </div>
                        <div class="px-3">
                            <p class="text-[1.15rem] font-semibold text-[#b42318]">{{ examResult.incorrect_answers_count }}</p>
                            <p class="mt-1 text-[0.66rem] text-[#667085]">błędnych</p>
                        </div>
                        <div class="pl-3">
                            <p class="text-[1.15rem] font-semibold text-[#b54708]">{{ examResult.unanswered_count }}</p>
                            <p class="mt-1 text-[0.66rem] text-[#667085]">bez odpowiedzi</p>
                        </div>
                    </div>

                    <p class="mt-4 border-t border-[#e4e7ec] pt-3 text-[0.76rem] text-[#667085]">
                        Czas egzaminu: <strong class="text-[#344054]">{{ formatDuration(completionTiming.duration_seconds) }}</strong>
                    </p>
                </section>

                <details class="group border-t border-[#e4e7ec] px-4 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-[0.9rem] font-semibold [&::-webkit-details-marker]:hidden">
                        Wynik w częściach egzaminu
                        <span class="text-[#667085] transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="mt-3 border-b border-[#e4e7ec]">
                        <article
                            v-for="section in sectionCards"
                            :key="section.key"
                            class="border-t border-[#e4e7ec] py-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-[0.82rem] font-semibold leading-5">{{ section.label }}</h3>
                                    <p class="mt-0.5 text-[0.66rem] text-[#667085]">{{ section.correct }} z {{ section.total }} poprawnych</p>
                                </div>
                                <p class="shrink-0 text-[0.82rem] font-semibold">{{ section.earned_points }} / {{ section.available_points }} pkt</p>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#eaecf0]" aria-hidden="true">
                                <div
                                    class="h-full rounded-full bg-[#556476]"
                                    :style="{ width: sectionProgressWidth(section) }"
                                />
                            </div>
                            <p class="mt-2 text-[0.66rem] text-[#667085]">
                                {{ section.incorrect }} błędnych · {{ section.unanswered }} bez odpowiedzi
                            </p>
                        </article>
                    </div>
                </details>

                <section id="mobile-exam-review" class="scroll-mt-16 border-t border-[#e4e7ec] px-4 py-6" aria-labelledby="mobile-exam-review-heading">
                    <h2 id="mobile-exam-review-heading" class="text-[1rem] font-semibold leading-5">Pytania do przejrzenia</h2>
                    <p class="mt-1 text-[0.72rem] leading-4 text-[#667085]">Błędy i pytania bez odpowiedzi z tego egzaminu.</p>

                    <div v-if="incorrectOrUnansweredResults.length" class="mt-4 border-b border-[#e4e7ec]">
                        <details
                            v-for="result in incorrectOrUnansweredResults"
                            :key="result.id"
                            class="group border-t border-[#e4e7ec]"
                        >
                            <summary class="flex min-h-14 cursor-pointer list-none items-start gap-3 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#334155] [&::-webkit-details-marker]:hidden">
                                <span
                                    class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="result.selected_answer === null ? 'bg-[#f79009]' : 'bg-[#f04438]'"
                                    aria-hidden="true"
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[0.64rem] font-semibold text-[#667085]">
                                        Pytanie {{ result.sequence_number }} · {{ scopeLabel(result.structure_scope) }}
                                    </span>
                                    <span
                                        class="mobile-exam-question mt-1.5 block text-[0.82rem] font-medium leading-5 text-[#101828] [&_strong]:font-semibold"
                                        v-html="renderExamPromptHtml(result.prompt)"
                                    />
                                </span>
                                <svg aria-hidden="true" class="mt-1 h-4 w-4 shrink-0 text-[#667085] transition-transform group-open:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </summary>

                            <div class="pb-5 pl-5">
                                <div v-if="result.media.length > 0" class="mb-4 overflow-hidden rounded-[0.5rem] bg-[#f2f4f7] p-2">
                                    <QuestionVideoFrameWithAnnotations
                                        v-if="hasResultVideoFrameAnnotations(result) && result.media[0]?.url"
                                        :src="result.media[0].url"
                                        :poster="result.media[0].poster_url ?? null"
                                        :frame-time-seconds="resultVideoFrameTimeSeconds(result)"
                                        :natural-width="result.media[0]?.width ?? null"
                                        :natural-height="result.media[0]?.height ?? null"
                                        :annotations="resultVideoFrameAnnotations(result)"
                                        :presentation-class="'relative w-full'"
                                        :video-class="'max-h-52 w-full object-contain'"
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
                                        class="pointer-events-none max-h-52 w-full object-contain"
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
                                        :image-class="'max-h-52 w-full object-contain'"
                                    />
                                    <QuestionResultMediaFallback v-else />
                                </div>

                                <dl class="border-b border-[#e4e7ec]">
                                    <div class="border-t border-[#e4e7ec] py-3">
                                        <dt class="text-[0.66rem] font-semibold text-[#667085]">Twoja odpowiedź</dt>
                                        <dd class="mt-1 text-[0.78rem] leading-5 text-[#344054]">{{ answerDisplay(result.selected_answer, result.selected_answer_text) }}</dd>
                                    </div>
                                    <div class="border-t border-[#e4e7ec] py-3">
                                        <dt class="text-[0.66rem] font-semibold text-[#667085]">Poprawna odpowiedź</dt>
                                        <dd class="mt-1 text-[0.78rem] leading-5 text-[#067647]">{{ answerDisplay(result.correct_answer, result.correct_answer_text) }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4">
                                    <QuestionExplanationRuntimeBlock
                                        :explanation-html="explanationHtml(result)"
                                        :fallback-text="result.explanation_asset?.body ?? null"
                                        :asset="result.explanation_asset"
                                        :sign-references="result.explanation_sign_references ?? []"
                                        :show-image="visualExplanationsEnabled"
                                        :show-sign-references="visualExplanationsEnabled"
                                    />
                                </div>
                            </div>
                        </details>
                    </div>
                    <MobileEmptyState
                        v-else
                        class="mt-4"
                        icon="check"
                        tone="success"
                        title="Wszystko poprawnie"
                        description="Nie ma pytań wymagających ponownego przejrzenia."
                    />
                </section>
            </main>

            <div aria-hidden="true" class="h-[calc(4.75rem+env(safe-area-inset-bottom))]" />
            <MobileBottomNavigation />
        </div>

        <div class="mx-auto hidden max-w-6xl space-y-5 py-5 md:block">
            <section class="border border-[#d1d8df] bg-white px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p class="text-xs text-[#5f6f82]">Wynik egzaminu · kat. {{ session.license_category_code ?? '—' }}</p>
                        <h1 class="mt-1 text-2xl font-semibold text-[#16202c]">{{ statusLabel }}</h1>
                        <p class="mt-2 text-sm text-[#5f6f82]">
                            {{ passed ? 'Próg zaliczenia osiągnięty.' : 'Próg zaliczenia nie został osiągnięty.' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a v-if="incorrectOrUnansweredResults.length" href="#przeglad-pytan" class="inline-flex min-h-10 items-center border border-[#d1d8df] px-4 text-sm font-medium text-[#223547] hover:bg-[#f7f8fa]">
                            Przejrzyj pytania ({{ incorrectOrUnansweredResults.length }})
                        </a>
                        <Link :href="route('session.index')" class="inline-flex min-h-10 items-center border border-[#d2b35b] bg-[#efc54f] px-4 text-sm font-semibold text-[#463309] hover:bg-[#e8bd4c]">
                            Wróć do nauki
                        </Link>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-end gap-x-8 gap-y-3 border-t border-[#e4e7ec] pt-5">
                    <div>
                        <p class="text-xs text-[#5f6f82]">Zdobyte punkty</p>
                        <p class="text-4xl font-semibold tabular-nums" :class="passed ? 'text-[#285c28]' : 'text-[#8f3838]'">
                            {{ examResult.earned_points }} <span class="text-xl text-[#5f6f82]">/ {{ examResult.official_max_points }}</span>
                        </p>
                    </div>
                    <p class="pb-1 text-sm text-[#5f6f82]">Do zaliczenia: <strong class="text-[#16202c]">{{ examResult.pass_threshold }} pkt</strong></p>
                    <p class="pb-1 text-sm text-[#5f6f82]">Czas: <strong class="text-[#16202c]">{{ formatDuration(completionTiming.duration_seconds) }}</strong></p>
                </div>
                <div class="mt-4 h-1.5 bg-[#e5e9ee]" aria-hidden="true">
                    <div class="h-full" :class="passed ? 'bg-[#4d8d62]' : 'bg-[#b56b6b]'" :style="{ width: resultProgressWidth }" />
                </div>
                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-[#5f6f82]">
                    <p>Poprawne: <strong class="text-[#16202c]">{{ examResult.correct_answers_count }}</strong></p>
                    <p>Błędne: <strong class="text-[#16202c]">{{ examResult.incorrect_answers_count }}</strong></p>
                    <p>Bez odpowiedzi: <strong class="text-[#16202c]">{{ examResult.unanswered_count }}</strong></p>
                </div>
            </section>

            <details class="group border border-[#d1d8df] bg-white">
                <summary class="flex cursor-pointer list-none items-center justify-between px-6 py-4 text-sm font-semibold text-[#223547] [&::-webkit-details-marker]:hidden">
                    Wynik w częściach egzaminu
                    <span class="text-[#5f6f82] transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                </summary>
                <div class="grid gap-4 border-t border-[#e4e7ec] px-6 py-4 md:grid-cols-2">
                    <div v-for="section in sectionCards" :key="section.key">
                        <p class="text-sm font-semibold text-[#16202c]">{{ section.label }}</p>
                        <p class="mt-1 text-sm text-[#5f6f82]">{{ section.earned_points }} / {{ section.available_points }} pkt · {{ section.correct }} z {{ section.total }} poprawnych</p>
                    </div>
                </div>
            </details>

                    <section id="przeglad-pytan" class="scroll-mt-5 border border-[#d1d8df] bg-white">
                        <div class="border-b border-[#e4e7ec] px-6 py-4">
                            <h2 class="text-base font-semibold text-[#16202c]">
                                {{ incorrectOrUnansweredResults.length ? `Do przejrzenia: ${incorrectOrUnansweredResults.length}` : 'Pytania do przejrzenia' }}
                            </h2>
                        </div>

                        <div
                            v-if="incorrectOrUnansweredResults.length === 0"
                            class="px-4 py-6 text-sm text-[#466246] sm:px-5"
                        >
                            Wszystkie odpowiedzi w tym egzaminie zostały zaznaczone poprawnie.
                        </div>

                        <div
                            v-else
                            class="divide-y divide-[#e6e6e6]"
                        >
                            <details
                                v-for="result in incorrectOrUnansweredResults"
                                :key="result.id"
                                class="group"
                            >
                                <summary class="flex cursor-pointer list-none items-start gap-4 px-6 py-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#334155] [&::-webkit-details-marker]:hidden">
                                    <span class="w-7 shrink-0 text-sm font-semibold tabular-nums text-[#5f6f82]">{{ result.sequence_number }}.</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium leading-5 text-[#16202c]" v-html="renderExamPromptHtml(result.prompt)" />
                                        <span class="mt-1 block text-xs text-[#5f6f82]">{{ questionStatusLabel(result) }} · {{ scopeLabel(result.structure_scope) }}</span>
                                    </span>
                                    <span class="shrink-0 text-[#5f6f82] transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                                </summary>
                                <div class="grid gap-5 border-t border-[#e4e7ec] px-6 py-5" :class="result.media.length ? 'lg:grid-cols-[minmax(14rem,0.72fr)_minmax(0,1fr)]' : ''">
                                    <div v-if="result.media.length" class="bg-white sm:border sm:border-[#e3e3e3] sm:bg-[#fafafa] sm:px-3 sm:py-3">
                                        <div class="flex min-h-[10.5rem] items-center justify-center bg-white py-2 sm:px-3 sm:py-3 md:min-h-[16rem]">
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
                                                        :video-class="'max-h-[11.5rem] w-full object-contain md:max-h-[16rem]'"
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
                                                        class="pointer-events-none max-h-[11.5rem] w-full object-contain md:max-h-[16rem]"
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
                                                        :image-class="'max-h-[11.5rem] w-full object-contain md:max-h-[16rem]'"
                                                    />
                                                </template>

                                                <QuestionResultMediaFallback v-else />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <dl class="grid gap-3 md:grid-cols-2">
                                            <div class="border border-[#e1e1e1] bg-white px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#7a7a7a]">
                                                    Twoja odpowiedź
                                                </dt>
                                                <dd class="mt-2 text-sm leading-6 text-[#1d2a37]">
                                                    {{ answerDisplay(result.selected_answer, result.selected_answer_text) }}
                                                </dd>
                                            </div>
                                            <div class="border border-[#e1e1e1] bg-white px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#7a7a7a]">
                                                    Poprawna odpowiedź
                                                </dt>
                                                <dd class="mt-2 text-sm leading-6 text-[#1d2a37]">
                                                    {{ answerDisplay(result.correct_answer, result.correct_answer_text) }}
                                                </dd>
                                            </div>
                                        </dl>

                                        <QuestionExplanationRuntimeBlock
                                            :explanation-html="explanationHtml(result)"
                                            :fallback-text="result.explanation_asset?.body ?? null"
                                            :asset="result.explanation_asset"
                                            :sign-references="result.explanation_sign_references ?? []"
                                            :show-image="visualExplanationsEnabled"
                                            :show-sign-references="visualExplanationsEnabled"
                                        />
                                    </div>
                                </div>
                            </details>
                        </div>
                    </section>
        </div>
    </SessionExamLayout>
</template>

<style scoped>
.mobile-exam-question {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
</style>
