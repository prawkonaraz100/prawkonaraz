<script setup lang="ts">
import MobileStreakLeaderboard from '@/Pages/Session/Partials/MobileStreakLeaderboard.vue';
import MobileBackButton from '@/Components/MobileBackButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LicenseCategoryVehicle from '@/Components/LicenseCategoryVehicle.vue';
import QuestionAudioControl from '@/Components/QuestionAudioControl.vue';
import QuestionExplanationRuntimeBlock from '@/Components/QuestionExplanationRuntimeBlock.vue';
import StreakAudioSettings from '@/Components/StreakAudioSettings.vue';
import StreakResultHero from '@/Components/StreakResultHero.vue';
import { formatStreakDuration, type StreakLeader } from '@/utils/streakDuration';
import { streakResultExplanationBody } from '@/utils/streakResultExplanation';
import { useMediaQuery } from '@/composables/useMediaQuery';
import { readStreakAudioPreferences, streakAudioPreferencesKey } from '@/utils/streakAudioPreferences';
import { readStreakVideoPreferences, streakVideoPreferencesKey, effectiveStreakVideoRate, streakVideoEndingTime } from '@/utils/streakVideoPreferences';
import { ArrowLeft, ArrowRight, ListChecks, Settings, Trophy, Zap } from '@lucide/vue';
import { apiClient, isApiClientError } from '@/lib/apiClient';
import StreakAnswerRecoveryNotice from '@/Components/StreakAnswerRecoveryNotice.vue';
import { isUncertainStreakAnswerFailure, postRecoverableStreakAnswer } from '@/lib/recoverableStreakAnswer';
import type { StreakAnswerRequest } from '@/lib/recoverableStreakAnswer';
import type { PageProps } from '@/types';
import { normalizeExplanationPlainText, renderExamPromptHtml } from '@/utils/explanationFormatting';
import type { ExplanationSignReference } from '@/utils/explanationFormatting';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import mobileDashboardHero from '../../../images/session/optimized/mobile-dashboard-hero.webp';
import '../../../css/barlow.css';

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
    audio?: { question: {
        type: string;
        url: string | null;
        duration_seconds: number | null;
        encoding_format: string | null;
        transcript: string | null;
        asset_key?: string | null;
    } | null };
}

interface ChallengeRun {
    id: number;
    status: string;
    score: number;
    duration_seconds?: number | null;
    question: ChallengeQuestion | null;
}

interface Leader {
    position: number;
    name: string;
    score: number;
    duration_seconds?: number | null;
    avatar_url: string | null;
    initials: string;
    is_me: boolean;
}

interface Overview {
    category: { id: number; code: string; name: string };
    question_count: number;
    personal_best: number;
    personal_best_duration_seconds?: number | null;
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
    explanation_sign_references?: ExplanationSignReference[];
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
let openedRunId: number | null = null;
let exitVersion = 0;
const lastResult = ref<AnswerResult | null>(null);
const mobileResultExplanation = computed(() => streakResultExplanationBody(lastResult.value?.explanation));
const submitting = ref(false);
const pendingAnswer = ref<{ runId: number; body: StreakAnswerRequest } | null>(null);
const selectedAnswer = ref<string | null>(null);
const answerFeedback = ref<'correct' | 'incorrect' | null>(null);
const errorMessage = ref<string | null>(null);
const mobileVideoRoot = ref<HTMLElement | null>(null);
const desktopVideoRoot = ref<HTMLElement | null>(null);
const activeRun = computed(() => overview.value.active_run);
const currentQuestion = computed(() => activeRun.value?.question ?? null);
// Resolve before the first client render. Neither branch mounts media during SSR.
const mobileViewport = useMediaQuery('not all and (min-width: 768px)');
const desktopViewport = useMediaQuery('(min-width: 768px)');
const audioSettingsOpen = ref(false);
const audioPreferences = ref(readStreakAudioPreferences(null));
const videoPreferences = ref(readStreakVideoPreferences(null));
const questionAudioControl = ref<InstanceType<typeof QuestionAudioControl> | null>(null);
const currentQuestionAudio = computed(() => currentQuestion.value?.audio?.question ?? null);
const shouldAutoPlayQuestionAudio = computed(() => mobileViewport.value && screen.value === 'play'
    && !audioSettingsOpen.value && audioPreferences.value.showQuestionAudioControl
    && audioPreferences.value.autoPlayQuestionAudio && Boolean(currentQuestionAudio.value?.url));
const audioStorageKey = computed(() => streakAudioPreferencesKey(user.value?.id ?? 'guest'));
const videoStorageKey = computed(() => streakVideoPreferencesKey(user.value?.id ?? 'guest'));
const pendingVideoMetadata = new Set<() => void>();
const stopQuestionAudio = () => questionAudioControl.value?.stop();
const pauseDesktopVideos = () => desktopVideoRoot.value?.querySelectorAll('video').forEach(video => video.pause());
const pauseMobileVideos = () => {
    autoplayAttempt += 1;
    pendingVideoMetadata.forEach(cancel => cancel());
    mobileVideoRoot.value?.querySelectorAll('video').forEach(video => video.pause());
};
const onQuestionAudioPlay = () => {
    // Muted autoplay can run alongside narration, including a pending ending preview.
    mobileVideoRoot.value?.querySelectorAll('video').forEach(video => {
        if (!video.muted) video.pause();
    });
};
const openAudioSettings = () => {
    stopQuestionAudio();
    pauseMobileVideos();
    audioSettingsOpen.value = true;
};
onMounted(() => {
    try { audioPreferences.value = readStreakAudioPreferences(window.localStorage.getItem(audioStorageKey.value)); } catch { /* Storage may be unavailable. */ }
    try { videoPreferences.value = readStreakVideoPreferences(window.localStorage.getItem(videoStorageKey.value)); } catch { /* Storage may be unavailable. */ }
    document.addEventListener('visibilitychange', stopAudioWhenHidden);
    window.addEventListener('pagehide', leaveRun);
});
const stopAudioWhenHidden = () => { if (document.hidden) { stopQuestionAudio(); pauseMobileVideos(); pauseDesktopVideos(); } };
onBeforeUnmount(() => {
    leaveRun();
    window.removeEventListener('pagehide', leaveRun);
    stopQuestionAudio();
    pauseMobileVideos();
    pauseDesktopVideos();
    document.removeEventListener('visibilitychange', stopAudioWhenHidden);
});
watch(audioPreferences, preferences => {
    try { window.localStorage.setItem(audioStorageKey.value, JSON.stringify(preferences)); } catch { /* Still usable without persistence. */ }
    if (!preferences.showQuestionAudioControl || !preferences.autoPlayQuestionAudio) stopQuestionAudio();
}, { deep: true });
watch([screen, () => currentQuestion.value?.id, mobileViewport], () => {
    stopQuestionAudio();
    // Stop the old nodes before v-if removes them, including on viewport changes.
    pauseMobileVideos();
    pauseDesktopVideos();
    if (screen.value !== 'play' || !mobileViewport.value) audioSettingsOpen.value = false;
}, { flush: 'sync' });

const currentQuestionPromptHtml = computed(() => renderExamPromptHtml(currentQuestion.value?.prompt));
const recordDelta = computed(() => lastResult.value ? Math.max(lastResult.value.score - recordBeforeRun.value, 0) : 0);
// The player's own row pinned under the leaderboard when the record is outside the top 20.
const myStreakStanding = computed<StreakLeader | null>(() => {
    const user = page.props.auth?.user;
    if (!overview.value.personal_rank || !user) return null;

    return {
        position: overview.value.personal_rank,
        name: user.name,
        score: overview.value.personal_best,
        duration_seconds: overview.value.personal_best_duration_seconds ?? null,
        avatar_url: user.avatar_url ?? null,
        initials: user.avatar_initials ?? user.name.slice(0, 1).toUpperCase(),
        is_me: true,
    };
});

const streakProgressGoal = computed(() => Math.max(overview.value.personal_best, 1));
const streakProgressPercent = computed(() => Math.min(100, ((activeRun.value?.score ?? 0) / streakProgressGoal.value) * 100));
const mobileVideoKey = (index: number) => `${currentQuestion.value?.id ?? 'inactive'}-${index}`;
// Mobile dark answer tiles: neutral tile, calm green/red tint once the answer is checked (same as study sessions).
const mobileAnswerClass = (key: string) => {
    if (selectedAnswer.value === key && answerFeedback.value === 'correct') {
        return 'rounded-[5px] border-transparent bg-[rgba(129,182,76,0.16)] text-white shadow-[inset_0_0_0_1px_rgba(129,182,76,0.3)]';
    }

    if (selectedAnswer.value === key && answerFeedback.value === 'incorrect') {
        return 'rounded-[5px] border-transparent bg-[rgba(224,40,40,0.14)] text-white shadow-[inset_0_0_0_1px_rgba(255,138,138,0.26)]';
    }

    return 'pk-tile border-transparent';
};

const mobileAnswerLabelClass = (key: string) => {
    if (selectedAnswer.value === key && answerFeedback.value === 'correct') {
        return 'bg-[#5d9948] text-white';
    }

    if (selectedAnswer.value === key && answerFeedback.value === 'incorrect') {
        return 'bg-[#b0423a] text-white';
    }

    return 'bg-[#ffffff1a] text-[#ffffffd9]';
};

const selectedAnswerClass = (key: string) => {
    if (selectedAnswer.value !== key) {
        return null;
    }

    if (answerFeedback.value === 'correct') {
        return 'border-[#93b6a0] bg-[#edf4ef]';
    }

    if (answerFeedback.value === 'incorrect') {
        return 'border-[#d9b7b7] bg-[#fbf1f1]';
    }

    return null;
};
let autoplayAttempt = 0;

const waitForMobileVideoMetadata = (video: HTMLVideoElement): Promise<boolean> => {
    if (Number.isFinite(video.duration) && video.duration > 0) {
        return Promise.resolve(true);
    }
    if (video.error) return Promise.resolve(false);

    return new Promise((resolve) => {
        const complete = (ready: boolean) => {
            video.removeEventListener('loadedmetadata', readyHandler);
            video.removeEventListener('durationchange', readyHandler);
            video.removeEventListener('error', cancel);
            window.clearTimeout(timeout);
            pendingVideoMetadata.delete(cancel);
            resolve(ready);
        };
        const readyHandler = () => complete(Number.isFinite(video.duration) && video.duration > 0);
        const cancel = () => complete(false);
        const timeout = window.setTimeout(cancel, 5000);
        pendingVideoMetadata.add(cancel);
        video.addEventListener('loadedmetadata', readyHandler, { once: true });
        video.addEventListener('durationchange', readyHandler, { once: true });
        video.addEventListener('error', cancel, { once: true });
        if (video.readyState === 0) {
            video.load();
        }
    });
};

const applyMobileVideoPreferences = (video: HTMLVideoElement) => {
    video.defaultPlaybackRate = effectiveStreakVideoRate(videoPreferences.value);
    video.playbackRate = effectiveStreakVideoRate(videoPreferences.value);
    if (videoPreferences.value.autoJumpToVideoEnding && Number.isFinite(video.duration)) {
        video.currentTime = streakVideoEndingTime(video.duration);
    }
};

const playMobileVideo = async (index: number, endingOnly: boolean, automatic = false) => {
    if (screen.value !== 'play' || !mobileViewport.value || audioSettingsOpen.value || submitting.value || document.hidden) return;
    if (automatic && !videoPreferences.value.autoPlayVideos) return;
    if (!automatic) stopQuestionAudio();
    const key = mobileVideoKey(index);
    const video = Array.from(mobileVideoRoot.value?.querySelectorAll<HTMLVideoElement>('video') ?? [])
        .find((element) => element.dataset.videoKey === key);

    if (!video) {
        return;
    }

    const attempt = ++autoplayAttempt;
    const stillCurrent = () => attempt === autoplayAttempt && video.isConnected && video.dataset.videoKey === key
        && key === mobileVideoKey(index) && screen.value === 'play' && mobileViewport.value
        && !audioSettingsOpen.value && !submitting.value && !document.hidden;
    video.muted = automatic;
    video.defaultPlaybackRate = endingOnly ? 1 : effectiveStreakVideoRate(videoPreferences.value);
    video.playbackRate = video.defaultPlaybackRate;
    errorMessage.value = null;

    try {
        if (endingOnly) {
            const ready = await waitForMobileVideoMetadata(video);
            if (!ready) {
                if (stillCurrent() && !automatic) errorMessage.value = 'Nie udało się wczytać filmu. Spróbuj ponownie.';
                return;
            }
            if (!stillCurrent()) {
                return;
            }

            video.currentTime = streakVideoEndingTime(video.duration);
        } else {
            video.currentTime = 0;
        }

        mobileVideoRoot.value?.querySelectorAll('video').forEach((other) => {
            if (other !== video) {
                other.pause();
            }
        });
        if (!stillCurrent()) return;
        await video.play();
    } catch {
        if (stillCurrent() && !automatic) errorMessage.value = 'Nie udało się odtworzyć filmu. Spróbuj ponownie.';
    }
};

watch(videoPreferences, preferences => {
    try { window.localStorage.setItem(videoStorageKey.value, JSON.stringify(preferences)); } catch { /* Still usable without persistence. */ }
    if (!preferences.autoPlayVideos) pauseMobileVideos();
    mobileVideoRoot.value?.querySelectorAll('video').forEach(applyMobileVideoPreferences);
}, { deep: true });
watch([screen, mobileViewport, () => activeRun.value?.id, () => currentQuestion.value?.id, audioSettingsOpen, () => videoPreferences.value.autoPlayVideos], () => {
    pauseMobileVideos();
    if (screen.value === 'play' && mobileViewport.value && !audioSettingsOpen.value && videoPreferences.value.autoPlayVideos) {
        const index = currentQuestion.value?.media.findIndex(media => media.kind === 'video' && media.url) ?? -1;
        if (index >= 0) void playMobileVideo(index, videoPreferences.value.autoJumpToVideoEnding, true);
    }
}, { flush: 'post' });

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

const closeRun = (runId: number) => {
    void apiClient.postOnPageExit(route('streak-challenge.leave', { streakRun: runId })).catch(() => {});
};

const leaveRun = () => {
    exitVersion++;
    if (openedRunId === null) return;
    const runId = openedRunId;
    openedRunId = null;
    pendingAnswer.value = null;
    errorMessage.value = null;
    overview.value = { ...overview.value, active_run: null };
    screen.value = 'landing';
    closeRun(runId);
};

const startRun = async () => {
    if (submitting.value || pendingAnswer.value) {
        return;
    }

    submitting.value = true;
    errorMessage.value = null;
    selectedAnswer.value = null;
    answerFeedback.value = null;
    recordBeforeRun.value = overview.value.personal_best;

    try {
        const startedAtExitVersion = exitVersion;
        const started = await apiClient.post<Overview>(route('streak-challenge.start'), {
            category_id: props.category.id,
        });
        if (!started.active_run) return;
        if (exitVersion !== startedAtExitVersion) {
            closeRun(started.active_run.id);
            return;
        }
        overview.value = started;
        openedRunId = started.active_run.id;
        lastResult.value = null;
        screen.value = 'play';
    } catch (error) {
        errorMessage.value = describeError(error);
    } finally {
        submitting.value = false;
    }
};

const answer = async (option: QuestionOption) => {
    if (submitting.value || pendingAnswer.value || !activeRun.value || !currentQuestion.value) {
        return;
    }

    pendingAnswer.value = {
        runId: activeRun.value.id,
        body: { question_id: currentQuestion.value.id, selected_answer: option.key },
    };
    await submitPendingAnswer();
};

const submitPendingAnswer = async () => {
    if (submitting.value || !pendingAnswer.value) return;
    const request = pendingAnswer.value;
    submitting.value = true;
    selectedAnswer.value = request.body.selected_answer;
    stopQuestionAudio();
    pauseMobileVideos();
    answerFeedback.value = null;
    errorMessage.value = null;

    try {
        const result = await postRecoverableStreakAnswer<AnswerResult>(
            route('streak-challenge.answer', { streakRun: request.runId }),
            request.body,
        );
        if (openedRunId !== request.runId) return;
        pendingAnswer.value = null;

        answerFeedback.value = result.is_correct ? 'correct' : 'incorrect';
        // Keep feedback brief during a successful streak, with more time to notice a mistake.
        const feedbackDuration = result.is_correct ? 200 : 400;
        await new Promise<void>((resolve) => window.setTimeout(resolve, feedbackDuration));

        if (openedRunId !== request.runId) return;
        if (result.finished) {
            openedRunId = null;
            lastResult.value = result;
            overview.value = result.overview ?? {
                ...overview.value,
                active_run: null,
            };
            if (screen.value === 'play') {
                screen.value = 'result';
            }
        } else {
            overview.value = { ...overview.value, active_run: result.run };
        }
    } catch (error) {
        if (openedRunId !== request.runId) return;
        if (isApiClientError(error) && error.status === 409) {
            leaveRun();
            errorMessage.value = 'Ta seria już się skończyła. Rozpocznij nową próbę.';
        } else {
            if (!isUncertainStreakAnswerFailure(error)) pendingAnswer.value = null;
            errorMessage.value = pendingAnswer.value
                ? 'Nie udało się potwierdzić odpowiedzi. Ponów zapis — Twój wybór nie zostanie naliczony drugi raz.'
                : describeError(error);
        }
    } finally {
        selectedAnswer.value = null;
        answerFeedback.value = null;
        submitting.value = false;
    }
};
</script>

<template>
    <Head title="Trening bez błędu" />

    <AuthenticatedLayout :hide-mobile-navigation="screen === 'play'" class="max-md:bg-[#302e2b]">
        <section class="mx-auto min-h-[100svh] w-full max-w-[34rem] bg-[#faf9f7] px-4 pb-5 pt-5 text-[#0a1020] md:min-h-0 md:max-w-[76rem] md:bg-transparent md:px-6 md:py-8 md:text-[#071b45]" :class="screen === 'play' ? 'streak-dark max-md:max-w-none max-md:bg-[#302e2b] max-md:px-0 max-md:pt-0 max-md:text-[#ffffffd9]' : 'streak-dark max-md:bg-[#302e2b] max-md:pt-[max(env(safe-area-inset-top),1rem)] max-md:text-[#ffffffd9]'" aria-label="Trening bez błędu">
            <header class="flex items-center gap-3 md:hidden" :class="screen === 'play' ? 'max-md:hidden' : ''">
                <MobileBackButton :href="route('session.index')" label="Wróć do strony głównej" />
                <div class="min-w-0">
                    <h1 class="text-[1.75rem] font-bold leading-[1.1] tracking-[-0.015em]">Seria bez błędu</h1>
                    <p class="mt-1 text-sm text-[#ffffffb8]">Testy na prawo jazdy – kategoria {{ category.code }}</p>
                </div>
            </header>

            <header v-if="screen !== 'play'" class="mb-6 hidden items-center justify-between gap-4 md:flex">
                <Link :href="route('session.index')" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-[#53698b] transition hover:text-[#071b45] focus-visible:rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0876f8]">
                    <ArrowLeft :size="18" aria-hidden="true" /> Wróć do nauki
                </Link>
                <span class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#dfe5ec] bg-white px-3 text-sm font-semibold">
                    <LicenseCategoryVehicle :code="category.code" class="h-6 w-10" /> Kategoria {{ category.code }}
                </span>
            </header>

            <div v-if="screen === 'landing'" class="hidden items-start gap-6 md:grid lg:grid-cols-[minmax(0,1.6fr)_minmax(18rem,1fr)]" data-testid="streak-desktop-lobby">
                <div class="min-w-0 space-y-5">
                    <section class="relative overflow-hidden rounded-3xl border border-[#dfe5ec] bg-white p-7 shadow-[0_12px_32px_rgba(7,27,69,0.035)] xl:p-9" aria-labelledby="streak-desktop-landing-title">
                        <img :src="mobileDashboardHero" alt="" class="pointer-events-none absolute -right-8 bottom-0 w-[22rem] opacity-85" aria-hidden="true">
                        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,#fff_0%,#fff_42%,rgba(255,255,255,.92)_60%,rgba(255,255,255,.08)_100%)]" aria-hidden="true" />
                        <div class="relative">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#71809b]">Trening bez błędu</p>
                            <h1 id="streak-desktop-landing-title" class="mt-3 max-w-[27rem] text-[clamp(2rem,3.2vw,2.75rem)] font-bold leading-[1.12] tracking-tight">Ile pytań z rzędu odpowiesz bez błędu?</h1>
                            <p class="mt-4 max-w-[24rem] text-base leading-7 text-[#53698b]">Każda poprawna odpowiedź wydłuża serię. Pierwsza pomyłka ją kończy.</p>
                            <div class="mt-6 flex items-center gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#eef3f8] text-base font-semibold">
                                    <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover">
                                    <span v-else>{{ initials }}</span>
                                </span>
                                <div><p class="text-sm text-[#71809b]">Twój rekord</p><p class="mt-0.5 text-sm text-[#53698b]"><strong class="mr-1 text-2xl font-bold leading-none text-[#071b45]">{{ overview.personal_best }}</strong> poprawnych z rzędu</p></div>
                            </div>
                            <button type="button" :disabled="submitting || overview.question_count === 0" class="group mt-7 inline-flex min-h-14 w-full max-w-[20rem] items-center justify-between gap-4 rounded-xl bg-[linear-gradient(135deg,#ffdb77,#ffc644)] py-2 pl-5 pr-2 text-base font-bold text-[#071b45] transition hover:brightness-[1.025] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#a87412] disabled:cursor-not-allowed disabled:opacity-60" @click="startRun">
                                <span>{{ submitting ? 'Uruchamianie...' : 'Zaczynamy' }}</span>
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#fff7e7]" aria-hidden="true"><ArrowRight :size="20" :stroke-width="2" /></span>
                            </button>
                            <p v-if="overview.question_count === 0" class="mt-3 text-sm text-[#71809b]">W tej kategorii nie ma teraz pytań do treningu.</p>
                        </div>
                    </section>
                    <section class="rounded-2xl border border-[#dfe5ec] bg-white p-5" aria-labelledby="streak-desktop-progress-title">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eaf8f2] text-[#079d6c]" aria-hidden="true"><ListChecks :size="21" /></span><div><h2 id="streak-desktop-progress-title" class="text-sm font-semibold">Postęp nauki</h2><p class="mt-0.5 text-xs text-[#71809b]">{{ courseProgress.answered_questions }} z {{ courseProgress.total_questions }} pytań</p></div></div>
                            <strong class="text-xl font-bold text-[#079d6c]">{{ courseProgress.percent }}%</strong>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#edf1f5]" role="progressbar" aria-label="Ukończono bazę pytań" :aria-valuenow="courseProgress.percent" aria-valuemin="0" aria-valuemax="100"><span class="block h-full rounded-full bg-[#00cf85]" :style="{ width: `${courseProgress.percent}%` }" /></div>
                    </section>
                </div>
                <aside class="min-w-0 rounded-3xl border border-[#dfe5ec] bg-white p-6" aria-labelledby="streak-desktop-ranking-title">
                    <div class="flex items-center gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#fff5df] text-[#b78010]" aria-hidden="true"><Trophy :size="22" /></span><div><h2 id="streak-desktop-ranking-title" class="text-lg font-semibold">Najlepsze serie</h2><p class="mt-0.5 text-xs text-[#71809b]">Ranking kategorii {{ category.code }}</p></div></div>
                    <ol v-if="overview.leaderboard.length" class="mt-5 max-h-[26rem] space-y-1 overflow-y-auto" aria-label="Ranking najlepszych serii">
                        <li v-for="leader in overview.leaderboard" :key="leader.position" class="flex min-h-14 items-center gap-3 rounded-xl px-2.5 py-2" :class="leader.is_me ? 'bg-[#eef4ff]' : leader.position === 1 ? 'bg-[#fff9e9]' : ''">
                            <span class="w-5 shrink-0 text-center text-xs font-semibold text-[#71809b]">{{ leader.position }}</span>
                            <span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#edf2f7] text-xs font-semibold"><img v-if="leader.avatar_url" :src="leader.avatar_url" alt="" class="h-full w-full object-cover"><span v-else>{{ leader.initials }}</span></span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium" :title="leader.name">{{ leader.name }}<span v-if="leader.is_me" class="sr-only"> — Ty</span></span>
                            <span class="flex shrink-0 items-center gap-2"><strong class="text-lg tabular-nums">{{ leader.score }}</strong><span class="text-xs tabular-nums text-[#71809b]" :title="`Czas serii: ${formatStreakDuration(leader.duration_seconds)}`">{{ formatStreakDuration(leader.duration_seconds) }}</span></span>
                        </li>
                    </ol>
                    <p v-else class="mt-5 rounded-xl bg-[#f7f9fc] px-4 py-8 text-center text-sm leading-6 text-[#71809b]">Nie ma jeszcze wyników. Rozpocznij serię i wyznacz pierwszy rekord.</p>
                    <div class="mt-5 border-t border-[#e7ecf2] pt-4 text-sm text-[#53698b]"><p>{{ overview.personal_rank ? `Twoje miejsce: ${overview.personal_rank}` : 'Twój wynik pojawi się po ukończeniu serii.' }}</p><p class="mt-1 text-xs text-[#71809b]">Rozpoczęte serie: {{ overview.attempts_count }} · Liczy się najlepszy wynik.</p></div>
                </aside>
            </div>

            <div v-if="screen === 'landing'" class="mt-4 md:hidden">
                <section class="pk-panel p-3" aria-labelledby="streak-landing-title">
                    <h2 id="streak-landing-title" class="text-[1.0625rem] font-bold leading-tight">
                        Ile pytań z rzędu odpowiesz bez błędu?
                    </h2>
                    <p class="mt-2 text-sm leading-snug text-[#ffffffb8]">Każda poprawna odpowiedź wydłuża serię. Pierwsza pomyłka ją kończy.</p>
                    <dl class="mt-3 grid grid-cols-2 gap-2">
                        <div class="pk-tile p-3">
                            <dt class="text-xs text-[#ffffffb8]">Mój rekord</dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-[1.75rem] font-bold leading-none tabular-nums">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="#ffc94a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" /></svg>
                                {{ overview.personal_best }}
                            </dd>
                        </div>
                        <div class="pk-tile p-3">
                            <dt class="text-xs text-[#ffffffb8]">Miejsce w rankingu</dt>
                            <dd class="mt-1 text-[1.75rem] font-bold leading-none tabular-nums">{{ overview.personal_rank ?? '–' }}</dd>
                        </div>
                    </dl>
                </section>

                <button
                    type="button"
                    class="pk-btn-primary mt-3 flex min-h-12 w-full items-center justify-center gap-2.5 px-4 text-[1.0625rem] font-semibold disabled:opacity-60"
                    :disabled="submitting || overview.question_count === 0"
                    @click="startRun"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5z" /></svg>
                    {{ submitting ? 'Uruchamianie...' : 'Zaczynamy' }}
                </button>
                <p v-if="overview.question_count === 0" class="mt-2.5 text-center text-sm text-[#ffffffb8]">W tej kategorii nie ma teraz pytań do treningu.</p>

                <section class="mt-6" aria-labelledby="streak-mobile-ranking-title">
                    <MobileStreakLeaderboard
                        v-if="overview.leaderboard.length"
                        title-id="streak-mobile-ranking-title"
                        :leaders="overview.leaderboard"
                        :caption="`Kategoria ${category.code}`"
                        :me="myStreakStanding"
                    />
                    <template v-else>
                        <h2 id="streak-mobile-ranking-title" class="text-[1.0625rem] font-bold">Najlepsi w serii</h2>
                        <p class="pk-panel mt-2.5 px-5 py-8 text-center text-sm leading-6 text-[#ffffffb8]">Nie ma jeszcze wyników. Rozpocznij serię i wyznacz pierwszy rekord.</p>
                    </template>
                    <p class="mt-3 text-center text-[0.75rem] leading-5 text-[#ffffffb8]">Przy remisie wygrywa krótszy czas. Rozpoczęte serie: {{ overview.attempts_count }}.</p>
                </section>

                <section class="pk-tile mt-6 p-3" aria-labelledby="streak-mobile-progress-title">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="streak-mobile-progress-title" class="text-[1.0625rem] font-bold">Postęp nauki</h2>
                        <span class="text-sm font-bold text-[#81b64c]">{{ courseProgress.percent }}%</span>
                    </div>
                    <p class="mt-0.5 text-xs text-[#ffffffb8]">{{ courseProgress.answered_questions }} z {{ courseProgress.total_questions }} pytań</p>
                    <div class="mt-2.5 h-2 overflow-hidden rounded-[10px] bg-[#ffffff1a]" role="progressbar" aria-label="Ukończono bazę pytań" :aria-valuenow="courseProgress.percent" aria-valuemin="0" aria-valuemax="100">
                        <span class="block h-full rounded-full bg-[#81b64c]" :style="{ width: courseProgress.percent + '%' }" />
                    </div>
                </section>
            </div>

            <template v-else-if="screen === 'play' && currentQuestion">
                <section v-if="mobileViewport" class="md:hidden" aria-labelledby="streak-question-title-mobile">
                    <!-- One compact bar: current streak, record, settings, exit; progress towards the record underneath. -->
                    <div class="sticky top-0 z-40 border-b border-[#ffffff1a] bg-[#302e2b] px-3 pb-2.5 pt-[max(env(safe-area-inset-top),0.625rem)]">
                        <div class="flex items-center gap-2">
                            <p class="flex min-w-0 flex-1 items-center gap-2" aria-live="polite">
                                <Zap :size="18" class="shrink-0 text-[#ffc94a]" aria-hidden="true" />
                                <span class="text-[1.375rem] font-bold leading-none tabular-nums text-white" :aria-label="`Seria: ${activeRun?.score ?? 0} poprawnych z rzędu`">{{ activeRun?.score ?? 0 }}</span>
                                <span class="truncate text-xs text-[#ffffff80]">z rzędu · rekord {{ overview.personal_best }}</span>
                            </p>
                            <button type="button" class="pk-btn-secondary grid h-10 w-10 shrink-0 place-items-center" aria-label="Ustawienia treningu" aria-haspopup="dialog" :aria-expanded="audioSettingsOpen" @click="openAudioSettings"><Settings :size="18" aria-hidden="true" /></button>
                            <button type="button" class="pk-btn-secondary min-h-10 shrink-0 px-3.5 text-sm font-semibold" @click="leaveRun">Wyjdź</button>
                        </div>
                        <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-[#ffffff1a]" role="progressbar" :aria-valuenow="Math.min(activeRun?.score ?? 0, streakProgressGoal)" aria-valuemin="0" :aria-valuemax="streakProgressGoal" aria-label="Postęp serii względem rekordu">
                            <span class="block h-full rounded-full bg-[#81b64c] transition-all" :style="{ width: `${streakProgressPercent}%` }" />
                        </div>
                    </div>

                    <div v-if="currentQuestion.media.length" ref="mobileVideoRoot" class="space-y-3 px-3 pt-3">
                        <template v-for="(media, index) in currentQuestion.media" :key="`${currentQuestion.id}-${index}`">
                            <img v-if="media.kind === 'image' && media.url" :src="media.url" alt="Ilustracja do pytania" class="block aspect-video w-full rounded-[5px] bg-[#00000033] object-contain">
                            <figure v-else-if="media.kind === 'video' && media.url" class="relative aspect-video w-full overflow-hidden rounded-[5px] bg-black">
                                <video
                                    :src="media.url"
                                    :data-video-key="mobileVideoKey(index)"
                                    :poster="media.poster_url || undefined"
                                    playsinline
                                    preload="metadata"
                                    class="block h-full w-full object-contain"
                                    @loadedmetadata="applyMobileVideoPreferences($event.target as HTMLVideoElement)"
                                />
                                <div class="pointer-events-none absolute inset-x-2 bottom-2 z-20 flex items-center justify-center gap-2">
                                    <button type="button" class="pointer-events-auto inline-flex min-h-9 items-center justify-center gap-1.5 rounded-full border border-white/90 bg-transparent px-3 text-xs font-semibold text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.95)] transition hover:bg-black/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" @click.stop="playMobileVideo(index, false)">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current"><path d="M8 5v14l11-7L8 5Z" /></svg>
                                        Cały film
                                    </button>
                                    <button type="button" class="pointer-events-auto inline-flex min-h-9 items-center justify-center gap-1.5 rounded-full border border-white/90 bg-transparent px-3 text-xs font-semibold text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.95)] transition hover:bg-black/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" @click.stop="playMobileVideo(index, true)">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current"><path d="M3 5v14l8-7L3 5Zm9 0v14l8-7-8-7Z" /></svg>
                                        Końcówka
                                    </button>
                                </div>
                            </figure>
                        </template>
                    </div>

                    <div class="min-h-[32svh] px-4 pb-5 pt-5">
                        <div class="flex min-h-11 items-center gap-1.5">
                            <p class="shrink-0 text-xs font-semibold text-[#ffffff80]">Pytanie {{ (activeRun?.score ?? 0) + 1 }}</p>
                            <QuestionAudioControl
                                v-if="mobileViewport && audioPreferences.showQuestionAudioControl && currentQuestionAudio?.url"
                                ref="questionAudioControl"
                                class="streak-question-audio"
                                :key="currentQuestion.id"
                                :asset="currentQuestionAudio"
                                variant="classic"
                                :autoplay="shouldAutoPlayQuestionAudio"
                                :autoplay-key="currentQuestion.id"
                                @play="onQuestionAudioPlay"
                            />
                        </div>
                        <h1 id="streak-question-title-mobile" class="mt-1 text-[clamp(1.15rem,5vw,1.4rem)] font-medium leading-[1.42] tracking-tight text-[#ffffffd9]" v-html="currentQuestionPromptHtml" />
                    </div>

                    <div :class="currentQuestion.options.length > 2 ? 'h-56' : 'h-36'" aria-hidden="true" />
                    <div class="fixed inset-x-0 bottom-0 z-50 border-t border-[#ffffff1a] bg-[#302e2b] px-3 pt-2.5 pb-[calc(env(safe-area-inset-bottom,0px)+0.625rem)]">
                        <div class="mx-auto max-w-[30rem] space-y-2">
                            <StreakAnswerRecoveryNotice :message="errorMessage" :can-retry="!!pendingAnswer" :busy="submitting" @retry="submitPendingAnswer" />
                            <button
                                v-for="option in currentQuestion.options"
                                :key="option.key"
                                type="button"
                                class="w-full overflow-hidden border font-semibold transition disabled:cursor-wait focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 [-webkit-tap-highlight-color:transparent]"
                                :class="[
                                    currentQuestion.question_type === 'boolean' ? 'min-h-[3.5rem] text-center text-[0.96rem] uppercase tracking-[0.05em]' : 'flex min-h-14 items-center gap-3 px-3 py-3 text-left text-[0.95rem]',
                                    mobileAnswerClass(option.key),
                                ]"
                                :disabled="submitting || !!pendingAnswer"
                                @click="answer(option)"
                            >
                                <template v-if="currentQuestion.question_type === 'boolean'">{{ normalizeExplanationPlainText(option.text) }}</template>
                                <template v-else>
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-[3px] text-xs font-semibold" :class="mobileAnswerLabelClass(option.key)">{{ option.label }}</span>
                                    <span class="min-w-0 font-semibold leading-6">{{ normalizeExplanationPlainText(option.text) }}</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </section>

            <section v-if="desktopViewport" class="hidden md:block" aria-labelledby="streak-question-title-desktop" data-testid="streak-desktop-play">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#71809b]">Seria bez błędu · Kategoria {{ category.code }}</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight">Trening bez błędu</h1>
                    </div>
                    <button type="button" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border border-[#dfe5ec] bg-white px-4 text-sm font-semibold text-[#53698b] transition hover:bg-[#f7f9fc] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0876f8]" @click="leaveRun"><ArrowLeft :size="17" aria-hidden="true" /> Wróć do treningu</button>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3" aria-label="Wynik bieżącej serii">
                    <div class="rounded-xl border border-[#dfe5ec] bg-white px-4 py-3"><p class="text-xs text-[#71809b]">Pytanie</p><strong class="mt-1 block text-xl tabular-nums">{{ (activeRun?.score ?? 0) + 1 }}</strong></div>
                    <div class="rounded-xl border border-[#ccebdd] bg-[#f1fbf6] px-4 py-3"><p class="text-xs text-[#43826a]">Poprawne z rzędu</p><strong class="mt-1 block text-xl tabular-nums text-[#15754f]">{{ activeRun?.score ?? 0 }}</strong></div>
                    <div class="rounded-xl border border-[#f1e3c2] bg-[#fffaf0] px-4 py-3"><p class="text-xs text-[#947035]">Twój rekord</p><strong class="mt-1 block text-xl tabular-nums">{{ overview.personal_best }}</strong></div>
                </div>
                <article class="mt-5 grid min-w-0 gap-6 rounded-3xl border border-[#dfe5ec] bg-white p-6 xl:gap-8 xl:p-8" :class="currentQuestion.media.some(media => media.url) ? 'lg:grid-cols-2' : 'mx-auto max-w-[52rem]'">
                    <div v-if="currentQuestion.media.some(media => media.url)" ref="desktopVideoRoot" class="min-w-0 space-y-3" data-testid="streak-desktop-media">
                        <template v-for="(media, index) in currentQuestion.media" :key="`${currentQuestion.id}-${index}`">
                            <img v-if="media.kind === 'image' && media.url" :src="media.url" alt="Ilustracja do pytania" class="h-[clamp(16rem,38vh,26rem)] w-full rounded-2xl bg-[#f7f9fc] object-contain">
                            <video v-else-if="media.kind === 'video' && media.url" :src="media.url" :poster="media.poster_url || undefined" controls playsinline class="h-[clamp(16rem,38vh,26rem)] w-full rounded-2xl bg-[#121826]" />
                        </template>
                    </div>
                    <div class="min-w-0" data-testid="streak-desktop-answers">
                    <p class="text-xs font-semibold uppercase leading-5 tracking-[0.08em] text-[#71809b]">{{ currentQuestion.topic || `Pytanie ${(activeRun?.score ?? 0) + 1}` }}</p>
                    <h2 id="streak-question-title-desktop" class="mt-3 text-xl font-semibold leading-8" v-html="currentQuestionPromptHtml" />
                    <div class="mt-5 space-y-2.5">
                        <button
                            v-for="option in currentQuestion.options"
                            :key="option.key"
                            type="button"
                            class="flex min-h-16 w-full items-center gap-3 rounded-xl border px-4 py-3 text-left text-sm leading-6 transition disabled:cursor-wait focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0876f8] [-webkit-tap-highlight-color:transparent]"
                            :class="selectedAnswerClass(option.key) ?? 'border-[#e4e7eb] bg-white hover:border-[#9ca3af] hover:bg-[#fafafa]'"
                            :disabled="submitting || !!pendingAnswer"
                            @click="answer(option)"
                        >
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[#dfe5ec] bg-white font-semibold">{{ option.label }}</span>
                            <span>{{ normalizeExplanationPlainText(option.text) }}</span>
                        </button>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-[#71809b]">Odpowiedź zapisuje się od razu. Nie można jej poprawić.</p>
                    </div>
                </article>
            </section>
            </template>

            <section v-else-if="screen === 'result' && lastResult" class="mt-7 rounded-[1.5rem] bg-white px-5 py-7 text-center shadow-[0_12px_30px_rgba(16,24,40,0.05)] max-md:mt-4 max-md:rounded-[5px] max-md:bg-transparent max-md:px-0 max-md:text-[#ffffffd9] max-md:shadow-none md:mx-auto md:mt-0 md:max-w-[48rem] md:border md:border-[#dfe5ec] md:p-8 md:shadow-[0_12px_32px_rgba(7,27,69,0.035)]" aria-labelledby="streak-result-title">
                <StreakResultHero v-if="mobileViewport" :score="lastResult.score" :previous-best="recordBeforeRun" :duration-seconds="lastResult.run.duration_seconds" />
                <template v-else>
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#fff1d4] text-3xl" aria-hidden="true">★</div>
                <h1 id="streak-result-title" class="mt-4 text-2xl font-extrabold">{{ lastResult.is_correct ? 'Wszystkie pytania zaliczone!' : 'Seria zakończona' }}</h1>
                <p class="mt-2 text-sm text-[#6b7280]">Poprawne odpowiedzi z rzędu</p>
                <strong class="mt-1 block text-6xl leading-none">{{ lastResult.score }}</strong>
                <p v-if="recordDelta > 0" class="mt-3 text-sm font-bold text-[#159b6b]">Nowy rekord osobisty!</p>
                <p v-else class="mt-3 text-sm text-[#6b7280]">Twój rekord: {{ overview.personal_best }}</p>
                </template>
                <div v-if="!lastResult.is_correct" class="pk-dark-scope mt-6 rounded-xl bg-[#f8f9fa] p-4 text-left text-sm max-md:rounded-[5px] max-md:bg-[#00000033]">
                    <p v-if="mobileViewport && mobileResultExplanation" class="mb-2 font-semibold text-[#374151] max-md:text-[#ffffffd9]">Zasada do zapamiętania:</p>
                    <QuestionExplanationRuntimeBlock
                        v-if="mobileViewport"
                        :fallback-text="mobileResultExplanation"
                        :sign-references="lastResult.explanation_sign_references ?? []"
                        :show-image="false"
                        empty-message="Do tego pytania nie ma jeszcze wyjaśnienia."
                    />
                    <template v-else>
                        <p>Poprawna odpowiedź: <strong>{{ lastResult.correct_answer }}</strong></p>
                        <p v-if="lastResult.explanation" class="mt-2 leading-6 text-[#4b5563]">{{ normalizeExplanationPlainText(lastResult.explanation) }}</p>
                    </template>
                </div>
                <button type="button" class="mt-6 min-h-12 w-full rounded-xl bg-[#ffc644] px-5 font-bold disabled:opacity-60 max-md:rounded-[10px] max-md:bg-[linear-gradient(#81b64c,#5d9948)] max-md:text-white max-md:shadow-[inset_0_1px_0_rgba(178,224,104,0.4),inset_0_-1px_0_#45753c,0_2px_4px_rgba(0,0,0,0.2)] md:max-w-[22rem] md:bg-[linear-gradient(135deg,#ffdb77,#ffc644)] md:focus-visible:outline md:focus-visible:outline-2 md:focus-visible:outline-offset-4 md:focus-visible:outline-[#a87412]" :disabled="submitting" @click="startRun">
                    {{ submitting ? 'Uruchamianie...' : 'Spróbuj ponownie' }}
                    <span v-if="!submitting" class="hidden md:inline" aria-hidden="true"> →</span>
                </button>
                <button type="button" class="mt-2 min-h-11 w-full text-sm font-semibold text-[#5f6876] max-md:text-[#ffffffb8]" @click="screen = 'landing'">Zobacz ranking</button>
            </section>

            <p v-if="answerFeedback" role="status" class="sr-only">{{ answerFeedback === 'correct' ? 'Poprawna odpowiedź' : 'Błędna odpowiedź' }}</p>
            <StreakAnswerRecoveryNotice class="mt-4" :class="screen === 'play' ? 'max-md:hidden' : ''" :message="errorMessage" :can-retry="!!pendingAnswer" :busy="submitting" @retry="submitPendingAnswer" />
            <StreakAudioSettings
                v-if="mobileViewport && screen === 'play'"
                :open="audioSettingsOpen"
                v-model:show-question-audio-control="audioPreferences.showQuestionAudioControl"
                v-model:auto-play-question-audio="audioPreferences.autoPlayQuestionAudio"
                v-model:auto-play-videos="videoPreferences.autoPlayVideos"
                v-model:video-playback-rate="videoPreferences.videoPlaybackRate"
                v-model:auto-jump-to-video-ending="videoPreferences.autoJumpToVideoEnding"
                @close="audioSettingsOpen = false"
            />
        </section>
    </AuthenticatedLayout>
</template>

<style scoped>
@media (max-width: 767px) {
    .streak-dark { font-family: Barlow, Figtree, ui-sans-serif, system-ui, sans-serif; }
    .streak-dark :is(h1, h2) { font-family: inherit; }
    .streak-question-audio :deep(button) { width: 44px; height: 44px; border-radius: 10px; background: transparent; color: rgb(255 255 255 / 72%); }
    .streak-question-audio :deep(button:hover) { background: rgb(255 255 255 / 10%); }
    .streak-question-audio :deep(svg) { width: 18px; height: 18px; }
    .streak-start-button {
        height: auto;
        min-height: 44px;
        aspect-ratio: 800 / 136;
        padding: 0;
        border-radius: 4% / 23.53%;
        background: transparent;
        color: #080b12;
        font-family: Inter, Arial, sans-serif;
        font-size: clamp(16px, 5vw, 24px);
        font-weight: 900;
        letter-spacing: -0.025em;
    }

    .streak-start-button__label {
        max-width: 64%;
        line-height: 1.15;
        text-align: center;
    }
}
</style>
