<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { apiClient, isApiClientError } from '@/lib/apiClient';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import mobileDashboardHero from '../../../images/session/mobile-dashboard-hero.png';

interface QuestionOption {
    key: 'a' | 'b' | 'c';
    label: string;
    text: string;
}

interface QuestionMedia {
    kind: 'image' | 'video';
    url: string | null;
    poster_url: string | null;
}

interface ChallengeQuestion {
    id: number;
    prompt: string;
    question_type: string;
    topic: string | null;
    options: QuestionOption[];
    media: QuestionMedia[];
}

interface ChallengeRun {
    id: number;
    status: string;
    score: number;
    question: ChallengeQuestion | null;
}

interface Leader {
    position: number;
    name: string;
    score: number;
    avatar_url: string | null;
    initials: string;
    is_me: boolean;
}

interface Overview {
    category: { id: number; code: string; name: string };
    question_count: number;
    personal_best: number;
    personal_rank: number | null;
    attempts_count: number;
    leaderboard: Leader[];
    active_run: ChallengeRun | null;
}

interface AnswerResult {
    is_correct: boolean;
    finished: boolean;
    score: number;
    run: ChallengeRun;
    correct_answer: string | null;
    explanation: string | null;
    overview?: Overview;
}

const props = defineProps<{
    category: {
        id: number;
        code: string;
        name: string;
    };
    courseProgress: {
        percent: number;
        answered_questions: number;
        total_questions: number;
    };
    initialStreak: Overview;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const firstName = computed(() => user.value?.name?.trim().split(/\s+/)[0] || 'Kursancie');
const avatarUrl = computed(() => user.value?.avatar_url ?? null);
const initials = computed(() => user.value?.avatar_initials || firstName.value.charAt(0).toLocaleUpperCase('pl-PL'));
const overview = ref<Overview>(props.initialStreak);
const recordBeforeRun = ref(props.initialStreak.personal_best);
const screen = ref<'landing' | 'play' | 'result'>('landing');
const lastResult = ref<AnswerResult | null>(null);
const submitting = ref(false);
const selectedAnswer = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

const activeRun = computed(() => overview.value.active_run);
const currentQuestion = computed(() => activeRun.value?.question ?? null);
const canResume = computed(() => activeRun.value?.status === 'in_progress' && Boolean(currentQuestion.value));
const recordDelta = computed(() => lastResult.value ? Math.max(lastResult.value.score - recordBeforeRun.value, 0) : 0);

const describeError = (error: unknown): string => {
    if (isApiClientError(error) && typeof error.data === 'object' && error.data !== null) {
        const payload = error.data as { message?: string; errors?: Record<string, string[]> };

        return payload.errors?.selected_answer?.[0]
            || payload.errors?.category_id?.[0]
            || payload.message
            || 'Nie udało się wykonać działania. Spróbuj ponownie.';
    }

    return 'Nie udało się połączyć z serwerem. Sprawdź połączenie i spróbuj ponownie.';
};

const startRun = async () => {
    if (submitting.value) {
        return;
    }

    if (canResume.value && !lastResult.value) {
        screen.value = 'play';
        return;
    }

    submitting.value = true;
    errorMessage.value = null;
    selectedAnswer.value = null;
    recordBeforeRun.value = overview.value.personal_best;

    try {
        overview.value = await apiClient.post<Overview>(route('streak-challenge.start'), {
            category_id: props.category.id,
        });
        lastResult.value = null;
        screen.value = 'play';
    } catch (error) {
        errorMessage.value = describeError(error);
    } finally {
        submitting.value = false;
    }
};

const answer = async (option: QuestionOption) => {
    if (submitting.value || !activeRun.value || !currentQuestion.value) {
        return;
    }

    submitting.value = true;
    selectedAnswer.value = option.key;
    errorMessage.value = null;

    try {
        const result = await apiClient.post<AnswerResult>(
            route('streak-challenge.answer', { streakRun: activeRun.value.id }),
            {
                question_id: currentQuestion.value.id,
                selected_answer: option.key,
            },
        );

        if (result.finished) {
            lastResult.value = result;
            overview.value = result.overview ?? {
                ...overview.value,
                active_run: null,
            };
            screen.value = 'result';
        } else {
            overview.value = { ...overview.value, active_run: result.run };
        }
    } catch (error) {
        errorMessage.value = describeError(error);
    } finally {
        selectedAnswer.value = null;
        submitting.value = false;
    }
};
</script>

<template>
    <Head title="Trening bez błędu" />

    <AuthenticatedLayout>
        <section class="mx-auto min-h-[100svh] w-full max-w-[34rem] bg-[#faf9f7] px-4 pb-5 pt-5 text-[#0a1020]" aria-label="Trening bez błędu">
            <header class="flex items-center justify-between gap-3">
                <span class="inline-flex h-11 items-center gap-2 rounded-[0.9rem] bg-white px-3 text-lg font-semibold shadow-[0_8px_24px_rgba(15,23,42,0.04)]" :aria-label="`Kategoria ${category.code}`">
                    {{ category.code }}
                    <svg class="h-6 w-8" viewBox="0 0 36 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" aria-hidden="true">
                        <path d="M5.2 15.6h2.9l2.9-5.4h12.4l4 5.4h3.1M11 10.2l-2 5.4h18.4" />
                        <circle cx="11" cy="17.1" r="2.6" /><circle cx="25.2" cy="17.1" r="2.6" />
                    </svg>
                </span>
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/><path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                    </span>
                    <span class="max-w-28 truncate text-sm font-bold">{{ firstName }}</span>
                    <Link href="/profile" class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#dce7ee] text-sm font-bold" aria-label="Przejdź do profilu">
                        <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover">
                        <span v-else>{{ initials }}</span>
                    </Link>
                </div>
            </header>

            <div v-if="screen === 'landing'" class="mt-7">
                <div class="flex items-end justify-between gap-3 px-1">
                    <p class="text-lg text-[#0b1120]">Ukończono <strong>{{ courseProgress.percent }}%</strong></p>
                    <p class="text-xs text-[#888fa0]">{{ courseProgress.answered_questions }} z {{ courseProgress.total_questions }} pytań</p>
                </div>
                <div class="mt-2.5 h-3 overflow-hidden rounded-full bg-[#e9eae8]" aria-hidden="true">
                    <span class="block h-full rounded-full bg-[#1bd899]" :style="{ width: courseProgress.percent + '%' }" />
                </div>

                <section class="relative mt-5 overflow-hidden rounded-[1.65rem] bg-white px-5 pb-4 pt-14 shadow-[0_16px_45px_rgba(16,24,40,0.045)]" aria-labelledby="streak-landing-title">
                    <img :src="mobileDashboardHero" alt="" class="pointer-events-none absolute right-0 top-32 w-[74%] opacity-90" aria-hidden="true">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-[19rem]" style="background: linear-gradient(90deg, #fff 4%, rgba(255,255,255,.93) 39%, rgba(255,255,255,0) 77%)" />
                    <div class="relative z-10">
                        <h1 id="streak-landing-title" class="max-w-[19rem] text-[clamp(1.8rem,8vw,2.5rem)] font-extrabold leading-[1.08] tracking-[-0.06em]">
                            Ile pytań z rzędu odpowiesz bez błędu?
                        </h1>
                        <div class="mt-5 flex items-center gap-3">
                            <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-[#dce7ee] text-lg font-bold">
                                <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover">
                                <span v-else>{{ initials }}</span>
                            </span>
                            <span>
                                <span class="block text-sm text-[#9096a3]">Mój rekord</span>
                                <strong class="block text-[2rem] leading-none">{{ overview.personal_best }}</strong>
                            </span>
                        </div>

                        <button
                            type="button"
                            class="mt-6 flex min-h-[3.2rem] w-full items-center justify-center gap-3 rounded-[1rem] bg-gradient-to-r from-[#ffc749] to-[#ffbc34] px-5 text-[1.05rem] font-bold text-[#111827] shadow-[0_9px_20px_rgba(227,166,45,0.15)] transition hover:brightness-[1.03] disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114]"
                            :disabled="submitting || overview.question_count === 0"
                            @click="startRun"
                        >
                            {{ submitting ? 'Uruchamianie...' : canResume ? 'Kontynuuj serię' : 'Zaczynamy' }}
                            <span aria-hidden="true">→</span>
                        </button>

                        <div class="mt-4 grid grid-cols-3 gap-2" aria-label="Najlepsze wyniki">
                            <div
                                v-for="(leader, index) in overview.leaderboard.slice(0, 3)"
                                :key="index"
                                class="flex min-h-[7.5rem] min-w-0 flex-col items-center rounded-[1rem] p-2 text-center"
                                :class="leader.position === 1 ? 'bg-[#fff9e9]' : 'bg-[#f7f8fa]'"
                            >
                                <span class="self-start text-sm" :class="leader.position === 1 ? 'text-[#efb727]' : leader.position === 2 ? 'text-[#a7afbb]' : 'text-[#c98b66]'" aria-hidden="true">♛</span>
                                <span class="relative grid h-10 w-10 place-items-center overflow-hidden rounded-full bg-[#e3eaf0] text-xs font-bold">
                                    <img v-if="leader.avatar_url" :src="leader.avatar_url" alt="" class="h-full w-full object-cover">
                                    <span v-else>{{ leader.initials }}</span>
                                </span>
                                <strong class="mt-1 text-base leading-5">{{ leader.score }}</strong>
                                <span class="max-w-full truncate text-[0.65rem] font-semibold">{{ leader.position }} · {{ leader.name }}</span>
                            </div>
                            <div v-if="!overview.leaderboard.length" class="col-span-3 grid min-h-[7.5rem] place-items-center rounded-[1rem] bg-[#f7f8fa] px-4 text-center text-sm text-[#6b7280]">
                                Bądź pierwszą osobą w rankingu tej kategorii.
                            </div>
                        </div>
                        <details v-if="overview.leaderboard.length" class="mt-3 rounded-xl bg-[#f8f9fa] px-3 py-2">
                            <summary class="cursor-pointer text-center text-xs font-semibold text-[#647083]">Pełny ranking{{ overview.personal_rank ? ` · Twoje miejsce: ${overview.personal_rank}` : '' }}</summary>
                            <ol class="mt-2 max-h-52 space-y-1 overflow-y-auto" aria-label="Ranking najlepszych serii">
                                <li v-for="(leader, index) in overview.leaderboard" :key="index" class="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-xs" :class="leader.is_me ? 'bg-[#fff1c9] font-bold' : 'bg-white'">
                                    <span class="truncate">{{ leader.position }}. {{ leader.name }}</span>
                                    <strong>{{ leader.score }}</strong>
                                </li>
                            </ol>
                        </details>
                        <p class="mt-3 text-center text-[0.68rem] text-[#858d99]">Pierwsza pomyłka kończy serię. Liczy się najlepszy wynik.</p>
                    </div>
                </section>
            </div>

            <section v-else-if="screen === 'play' && currentQuestion" class="mt-6" aria-labelledby="streak-question-title">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#a87412]">Seria bez błędu</p>
                        <h1 class="mt-1 text-2xl font-extrabold">Wynik: {{ activeRun?.score }}</h1>
                    </div>
                    <span class="rounded-full bg-[#fff3d4] px-3 py-1.5 text-xs font-semibold">Rekord: {{ overview.personal_best }}</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#e9eae8]" aria-hidden="true">
                    <span class="block h-full rounded-full bg-[#1bd899] transition-all" :style="{ width: `${Math.min(100, ((activeRun?.score ?? 0) / Math.max(overview.personal_best, 10)) * 100)}%` }" />
                </div>
                <article class="mt-5 rounded-[1.5rem] bg-white p-4 shadow-[0_12px_30px_rgba(16,24,40,0.05)]">
                    <p class="text-xs font-semibold text-[#a87412]">{{ currentQuestion.topic || `Pytanie ${(activeRun?.score ?? 0) + 1}` }}</p>
                    <div v-if="currentQuestion.media.length" class="mt-4 space-y-3">
                        <template v-for="(media, index) in currentQuestion.media" :key="index">
                            <img v-if="media.kind === 'image' && media.url" :src="media.url" alt="Ilustracja do pytania" class="max-h-[30svh] w-full rounded-xl bg-[#f4f5f6] object-contain">
                            <video v-else-if="media.kind === 'video' && media.url" :src="media.url" :poster="media.poster_url || undefined" controls playsinline class="max-h-[30svh] w-full rounded-xl bg-[#121826]" />
                        </template>
                    </div>
                    <h2 id="streak-question-title" class="mt-4 text-[1.12rem] font-bold leading-7">{{ currentQuestion.prompt }}</h2>
                    <div class="mt-5 space-y-2.5">
                        <button
                            v-for="option in currentQuestion.options"
                            :key="option.key"
                            type="button"
                            class="flex min-h-14 w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left text-sm transition disabled:cursor-wait"
                            :class="selectedAnswer === option.key ? 'border-[#eab73b] bg-[#fff5d8]' : 'border-[#e4e7eb] bg-white hover:border-[#eab73b] hover:bg-[#fffaf0]'"
                            :disabled="submitting"
                            @click="answer(option)"
                        >
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[#d6dbe3] font-bold">{{ option.label }}</span>
                            <span>{{ option.text }}</span>
                        </button>
                    </div>
                </article>
                <p class="mt-3 text-center text-xs text-[#6b7280]">Odpowiedź zapisuje się od razu. Nie można jej poprawić.</p>
            </section>

            <section v-else-if="screen === 'result' && lastResult" class="mt-7 rounded-[1.5rem] bg-white px-5 py-7 text-center shadow-[0_12px_30px_rgba(16,24,40,0.05)]" aria-labelledby="streak-result-title">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#fff1d4] text-3xl" aria-hidden="true">★</div>
                <h1 id="streak-result-title" class="mt-4 text-2xl font-extrabold">{{ lastResult.is_correct ? 'Wszystkie pytania zaliczone!' : 'Seria zakończona' }}</h1>
                <p class="mt-2 text-sm text-[#6b7280]">Poprawne odpowiedzi z rzędu</p>
                <strong class="mt-1 block text-6xl leading-none">{{ lastResult.score }}</strong>
                <p v-if="recordDelta > 0" class="mt-3 text-sm font-bold text-[#159b6b]">Nowy rekord osobisty!</p>
                <p v-else class="mt-3 text-sm text-[#6b7280]">Twój rekord: {{ overview.personal_best }}</p>
                <div v-if="!lastResult.is_correct" class="mt-6 rounded-xl bg-[#f8f9fa] p-4 text-left text-sm">
                    <p>Poprawna odpowiedź: <strong>{{ lastResult.correct_answer }}</strong></p>
                    <p v-if="lastResult.explanation" class="mt-2 leading-6 text-[#4b5563]">{{ lastResult.explanation }}</p>
                </div>
                <button type="button" class="mt-6 min-h-12 w-full rounded-xl bg-[#ffc644] px-5 font-bold disabled:opacity-60" :disabled="submitting" @click="startRun">
                    {{ submitting ? 'Uruchamianie...' : 'Spróbuj ponownie →' }}
                </button>
                <button type="button" class="mt-2 min-h-11 w-full text-sm font-semibold text-[#5f6876]" @click="screen = 'landing'">Zobacz ranking</button>
            </section>

            <p v-if="errorMessage" role="alert" class="mt-4 rounded-xl bg-[#fff1ef] px-4 py-3 text-sm text-[#a43228]">{{ errorMessage }}</p>
        </section>
    </AuthenticatedLayout>
</template>
