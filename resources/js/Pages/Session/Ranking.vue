<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SessionExpiredNotice from '@/Components/SessionExpiredNotice.vue';
import { useSessionExpiry } from '@/composables/useSessionExpiry';
import rankingHeroDriverImage from '../../../images/home/hero-main.webp';
import mobileMemoryCoachImage from '../../../images/review/mobile-memory-coach.png';
import {
    apiClient,
    extractApiClientErrorCode,
    extractApiClientErrorMessage,
    isApiClientError,
} from '@/lib/apiClient';
import {
    mergeRankedRealtimeEvents,
    reduceRankedRealtimeEvents,
    sortRankedRealtimeEvents,
} from '@/utils/rankedRealtimeEventReducer';
import { shouldAppendRankedOverviewSyncEvent } from '@/utils/rankedRealtimeOverviewSync';
import {
    findNextUnansweredRankedQuestionId,
    getRankedQuestionResponseTimeMs,
    resolveActiveRankedQuestionId,
} from '@/utils/rankedMatchQuestionFlow';
import {
    formatRankedClock,
    getRankedCountdownSeconds,
    getRankedDurationSeconds,
    getRankedElapsedSeconds,
    getRankedRemainingSeconds,
    getRankedSecondsUntil,
} from '@/utils/rankedRealtimeClock';
import {
    RANKING_MAX_CONCURRENT_PLAYERS,
    createInitialRankingSimulationState,
    createRankingSimulationScript,
    rankedScenarioOptions,
    type RankedRealtimeEvent,
    type RankedScenarioKey,
} from '@/utils/rankingModeSimulation';
import { RankedRealtimePollingTransport } from '@/utils/rankedRealtimePollingTransport';
import {
    RankedRealtimeTransportClient,
    type RankedRealtimeTransportKind,
} from '@/utils/rankedRealtimeTransportClient';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { PageProps } from '@/types';

type RankedScreen = 'lobby' | 'waiting' | 'match' | 'result';

interface CategoryOption {
    id: number;
    code: string;
    name: string;
    short_name: string;
}

interface SpecSummary {
    api_version: string;
    match_channel_pattern: string;
    queue_channel_pattern: string;
    heartbeat_seconds: number;
    disconnect_timeout_seconds: number;
    reconnect_grace_seconds: number;
    matchmaking_timeout_seconds: number;
    match_duration_seconds: number;
    total_questions: number;
    max_concurrent_players: number;
    implementation_status: string;
}

interface RealtimeTransportSummary {
    preferred: 'websocket' | 'sse' | 'polling';
    websocket: {
        enabled: boolean;
        url: string | null;
        fallback: 'sse' | 'polling';
    };
    sse: {
        enabled: boolean;
        url: string | null;
        retry_ms: number;
    };
    polling: {
        enabled: boolean;
        overview_interval_ms: number;
        events_interval_ms: number;
    };
}

interface LeaderboardEntry {
    position: number;
    user_id: number;
    username: string;
    rating: number;
    peak_rating: number;
    matches_played: number;
    wins: number;
    losses: number;
    draws: number;
    current_streak: number;
    best_streak: number;
    win_rate: number | null;
}

interface RankedApiRating {
    user_id: number;
    rating: number;
    peak_rating: number;
    matches_played: number;
    wins: number;
    losses: number;
    draws: number;
    current_streak: number;
    best_streak: number;
    updated_at: string | null;
}

interface RankedApiCapacity {
    current_players: number;
    max_players: number;
    available_slots: number;
    waiting_users: number;
    is_full: boolean;
}

interface RankedApiQueueServerFull {
    error_code: string | null;
    message: string | null;
    current_capacity: number | null;
    max_concurrent_players: number | null;
    position_in_queue: number | null;
    estimated_wait_minutes: number | null;
    recorded_at: string | null;
    resumed_at: string | null;
}

interface RankedApiQueueEntry {
    id: number;
    status: string;
    joined_at: string | null;
    matched_at: string | null;
    left_at: string | null;
    category: CategoryOption | null;
    match_public_id: string | null;
    server_full: RankedApiQueueServerFull | null;
}

interface RankedApiMatchPlayer {
    user_id: number;
    username: string;
    slot: string;
    status: string;
    ready_at: string | null;
    is_ready: boolean;
    presence_state: string;
    elo_before: number;
    elo_after: number | null;
    elo_change: number | null;
    correct_answers: number;
    total_answered: number;
    points: number;
    sum_response_time_ms: number;
    is_current_user: boolean;
}

interface RankedApiPresencePlayer {
    user_id: number;
    is_current_user: boolean;
    presence_state: string;
    is_connected: boolean;
    last_seen_at: string | null;
    disconnected_at: string | null;
    reconnect_deadline_at: string | null;
    last_seen_seconds_ago: number;
    seconds_until_disconnect: number;
    seconds_until_forfeit: number | null;
}

interface RankedApiMatch {
    id: number;
    public_id: string;
    status: string;
    state: string;
    api_version: string;
    duration_seconds: number;
    total_questions: number;
    reason: string | null;
    matched_at: string | null;
    countdown_started_at: string | null;
    countdown_seconds: number | null;
    starts_at: string | null;
    started_at: string | null;
    finished_at: string | null;
    abandoned_at: string | null;
    category: CategoryOption;
    channels: {
        queue: string;
        match: string;
    };
    players: RankedApiMatchPlayer[];
    opponent: RankedApiMatchPlayer | null;
    ready: {
        current_user_ready: boolean;
        current_user_ready_at: string | null;
        opponent_ready: boolean;
        opponent_ready_at: string | null;
    };
    presence: {
        heartbeat_seconds: number;
        disconnect_timeout_seconds: number;
        reconnect_grace_seconds: number;
        current_user: RankedApiPresencePlayer | null;
        opponent: RankedApiPresencePlayer | null;
    };
}

interface RankedApiMatchQuestion {
    id: number;
    question_number: number;
    question_text: string;
    answers: Record<string, string>;
    selected_answer: string | null;
    is_answered: boolean;
    is_correct: boolean | null;
    correct_answer?: string;
    response_time_ms: number | null;
}

interface RankedApiMatchDetails {
    match: RankedApiMatch;
    progress: {
        answered: number;
        remaining: number;
        correct_answers: number;
        points: number;
        total: number;
        completion_percent: number;
    };
    questions: RankedApiMatchQuestion[];
    answer_breakdown: Array<{
        question_id: number;
        question_number: number;
        player1_correct: boolean | null;
        player1_response_time_ms: number | null;
        player2_correct: boolean | null;
        player2_response_time_ms: number | null;
    }>;
}

interface RankedApiRecentMatch extends RankedApiMatch {
    played_at: string | null;
    outcome: string;
    result_label: string;
    current_user: {
        correct_answers: number;
        total_answered: number;
        points: number;
        elo_after: number | null;
        elo_change: number | null;
    };
}

interface BackendRecentMatchCard {
    publicId: string;
    resultLabel: string;
    outcome: string;
    categoryName: string;
    playedAt: string | null;
    totalQuestions: number;
    currentUserCorrectAnswers: number;
    currentUserEloChange: number | null;
    reason: string | null;
}

interface RankedResultParticipantCard {
    username: string;
    roleLabel: string;
    points: number;
    correctAnswers: number;
    totalAnswered: number;
    totalQuestions: number;
    responseTimeMs: number;
    eloChange: number | null;
    outcome: string;
    outcomeLabel: string;
    outcomeClass: string;
    surfaceClass: string;
    avatarFrameClass: string;
    avatarImageClass: string;
    initials: string;
}

interface RankedOverviewResponse {
    data: {
        state: string;
        rating: RankedApiRating;
        queue: RankedApiQueueEntry | null;
        capacity: RankedApiCapacity;
        active_match: RankedApiMatch | null;
        recent_match: RankedApiRecentMatch | null;
    };
}

interface RankedJoinResponse extends RankedOverviewResponse {
    data: RankedOverviewResponse['data'] & {
        joined: boolean;
    };
}

interface RankedLeaveResponse extends RankedOverviewResponse {
    data: RankedOverviewResponse['data'] & {
        left_queue: boolean;
    };
}

interface RankedAbandonResponse extends RankedOverviewResponse {
    data: RankedOverviewResponse['data'] & {
        abandoned_match: boolean;
    };
}

interface RankedMatchDetailsResponse {
    data: RankedApiMatchDetails;
}

interface RankedMatchAnswerResponse {
    data: {
        accepted: boolean;
        answer: {
            question_id: number;
            question_number: number;
            selected_answer: string;
            is_correct: boolean;
            response_time_ms: number | null;
            answered_at: string | null;
        };
        match: RankedApiMatch;
        progress: RankedApiMatchDetails['progress'];
    };
}

interface RankedMatchPongResponse {
    data: {
        acknowledged: boolean;
        server_time: string;
        state: string;
        match: RankedApiMatch;
        progress: RankedApiMatchDetails['progress'];
    };
}

interface RankedMatchReadyResponse extends RankedMatchPongResponse {}

interface RankedMatchEventsResponse {
    data: {
        match_public_id: string;
        events: RankedRealtimeEvent[];
        latest_event_id: string | null;
    };
}

interface RankedHistoryResponse {
    data: {
        matches: RankedApiRecentMatch[];
    };
}

interface RankedRealtimeStreamOverviewMessage {
    data: RankedOverviewResponse['data'];
}

interface RankedRealtimeStreamMatchEventsMessage {
    data: RankedMatchEventsResponse['data'];
}

interface RankedRealtimeStreamErrorPayload {
    event: 'error';
    id: string;
    api_version: string;
    timestamp: string;
    data?: {
        error_code?: string | null;
        message?: string | null;
        details?: Record<string, unknown> | null;
    };
}

const sanitizeBackendSyntheticEventToken = (value: string | number | null | undefined) =>
    String(value ?? 'none').replace(/[^a-zA-Z0-9_-]/g, '-');

const resolveOverviewSyncEventTimestamp = (data: RankedOverviewResponse['data']) =>
    data.active_match?.started_at
    ?? data.active_match?.matched_at
    ?? data.active_match?.finished_at
    ?? data.active_match?.abandoned_at
    ?? data.queue?.server_full?.recorded_at
    ?? data.queue?.matched_at
    ?? data.queue?.joined_at
    ?? data.recent_match?.played_at
    ?? null;

const buildOverviewSyncEvent = (
    data: RankedOverviewResponse['data'],
): RankedRealtimeEvent | null => {
    const resolvedTimestamp = resolveOverviewSyncEventTimestamp(data);
    const isPureIdleSnapshot = data.state === 'idle'
        && !data.queue
        && !data.active_match
        && !data.recent_match;
    const timestamp = resolvedTimestamp ?? (isPureIdleSnapshot ? new Date().toISOString() : null);
    const timestampToken = resolvedTimestamp ?? (isPureIdleSnapshot ? 'synthetic-idle' : null);

    if (!timestamp || !timestampToken) {
        return null;
    }

    return {
        event: 'overview.sync',
        id: [
            'evt_overview_sync',
            sanitizeBackendSyntheticEventToken(data.state),
            sanitizeBackendSyntheticEventToken(data.queue?.status ?? 'no-queue'),
            sanitizeBackendSyntheticEventToken(data.active_match?.public_id ?? 'no-active-match'),
            sanitizeBackendSyntheticEventToken(data.active_match?.state ?? data.active_match?.status ?? 'no-active-state'),
            sanitizeBackendSyntheticEventToken(data.recent_match?.public_id ?? 'no-recent-match'),
            sanitizeBackendSyntheticEventToken(timestampToken),
        ].join('_'),
        api_version: '1.0',
        timestamp,
        data,
    };
};

interface BackendClockCard {
    eyebrow: string;
    primary: string;
    secondary: string;
    progressPercent: number | null;
    tone: string;
}

const props = defineProps<{
    screen: RankedScreen;
    navigationLock: 'lobby' | null;
    selectedCategory: CategoryOption | null;
    leaderboard: LeaderboardEntry[];
    specSummary: SpecSummary;
    realtimeTransport: RealtimeTransportSummary;
}>();

const page = usePage<PageProps>();

const availableCategories = computed<CategoryOption[]>(() => {
    const categories = (page.props.studyContext?.categories ?? []) as CategoryOption[];
    const rankedCategories = categories.filter((category) => category.code === 'B');

    return rankedCategories.length > 0 ? rankedCategories : categories;
});

const playerName = computed(() => {
    const fullName = String(page.props.auth?.user?.name ?? 'Ty');

    return fullName.split(' ')[0] || fullName;
});

const selectedCategoryId = ref<number | null>(
    props.selectedCategory?.id ?? availableCategories.value[0]?.id ?? null,
);
const selectedScenario = ref<RankedScenarioKey>('happy_path');
const isRunning = ref(false);
const scheduledTimeouts: number[] = [];

const selectedCategory = computed(() =>
    availableCategories.value.find((category) => category.id === selectedCategoryId.value)
    ?? availableCategories.value[0]
    ?? props.selectedCategory
    ?? null,
);

const currentScreen = computed<RankedScreen>(() => props.screen);
const navigationLock = computed(() => props.navigationLock);
const leaderboardEntries = computed<LeaderboardEntry[]>(() => props.leaderboard ?? []);
const leaderboardUpdatedLabel = computed(() =>
    new Intl.DateTimeFormat('pl-PL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);
const leaderboardUpdatedMobileLabel = computed(() =>
    new Intl.DateTimeFormat('pl-PL', {
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date()),
);
const leaderboardAvatarLabel = (username: string) => {
    const parts = username
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
};

const leaderboardEloTrend = (entry: LeaderboardEntry) => {
    return entry.wins >= entry.losses ? 'up' : 'down';
};

const leaderboardPositionClass = (position: number) => {
    if (position === 1) {
        return 'text-[#e0aa46]';
    }

    if (position === 2) {
        return 'text-[#d9d9d9]';
    }

    if (position === 3) {
        return 'text-[#a97949]';
    }

    return 'text-[#e2e2e2]';
};

const mobileLeaderboardEntries = computed<LeaderboardEntry[]>(() =>
    leaderboardEntries.value.slice(0, 4),
);

const mobileFeaturedLeaderboardEntry = computed<LeaderboardEntry | null>(() =>
    mobileLeaderboardEntries.value[0] ?? null,
);

const mobileSecondaryLeaderboardEntries = computed<LeaderboardEntry[]>(() =>
    mobileLeaderboardEntries.value.slice(1),
);

const leaderboardLevelLabel = (entry: LeaderboardEntry) => {
    if (entry.rating >= 1650) {
        return 'Poziom Mistrz';
    }

    if (entry.rating >= 1450) {
        return 'Poziom Ekspert';
    }

    return 'Poziom Gracz';
};

const leaderboardMomentumLabel = (entry: LeaderboardEntry) => {
    const momentum = Math.max(entry.current_streak, entry.wins - entry.losses, 0);

    return momentum > 0 ? `+${momentum}` : '0';
};

const formatRankedResultTime = (milliseconds: number | null | undefined) => {
    if (milliseconds === null || milliseconds === undefined) {
        return '—';
    }

    const safeMilliseconds = Math.max(milliseconds, 0);
    const totalSeconds = Math.floor(safeMilliseconds / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const tenths = Math.floor((safeMilliseconds % 1000) / 100);

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${tenths}`;
};

const formatRankedResultSignedValue = (value: number | null | undefined) => {
    if (value === null || value === undefined) {
        return '—';
    }

    return `${value > 0 ? '+' : ''}${value}`;
};

const simulationOptions = () => ({
    categoryCode: selectedCategory.value?.code ?? 'B',
    categoryLabel: selectedCategory.value?.short_name ?? selectedCategory.value?.name ?? 'B',
    playerName: playerName.value,
    playerUserId: Number(page.props.auth?.user?.id ?? 123),
});

const simulationState = ref(
    createInitialRankingSimulationState(selectedScenario.value, simulationOptions()),
);
const selectedEventId = ref<string | null>(null);
const rankedOverview = ref<RankedOverviewResponse['data'] | null>(null);
const backendBusy = ref(false);
const backendLoaded = ref(false);
const backendError = ref<string | null>(null);

const mobilePlayerRatingLabel = computed(() =>
    rankedOverview.value ? String(rankedOverview.value.rating.rating) : '—',
);
const backendMatchDetails = ref<RankedApiMatchDetails | null>(null);
const backendMatchBusy = ref(false);
const backendMatchError = ref<string | null>(null);
const {
    handleSessionExpiryError,
    sessionExpired,
} = useSessionExpiry(() => {
    clearTimers();
    clearBackendUiClock();
    destroyBackendRealtimeStream();
    backendRealtimeTransport.destroy();
    backendBusy.value = false;
    backendMatchBusy.value = false;
    backendError.value = 'Sesja wygasła. Zatrzymaliśmy połączenie z rankingiem.';
    backendMatchError.value = backendError.value;
});
const backendHistory = ref<RankedApiRecentMatch[]>([]);
const backendRecentMatch = computed(() =>
    rankedOverview.value?.recent_match
    ?? backendHistory.value[0]
    ?? null,
);
const backendQueueEvents = ref<RankedRealtimeEvent[]>([]);
const backendMatchEvents = ref<RankedRealtimeEvent[]>([]);
const selectedBackendEventId = ref<string | null>(null);
const selectedBackendQuestionId = ref<number | null>(null);
const backendReadyBusy = ref(false);
const backendReadySentForMatchId = ref<string | null>(null);
const backendQueueTimeoutSyncRequestedForJoinedAt = ref<string | null>(null);
const waitingScreenExitPending = ref(false);
const queueLeavePending = ref(false);
const backendPongBusy = ref(false);
const backendNowMs = ref(Date.now());
const backendStreamState = ref<'unsupported' | 'connecting' | 'open' | 'error'>('unsupported');
const backendLiveTransportKind = ref<RankedRealtimeTransportKind | 'polling' | 'none'>('none');
let backendClockIntervalId: number | null = null;
let backendStreamClient: RankedRealtimeTransportClient | null = null;
let backendQuestionTimers: Record<number, number> = {};

const clearTimers = () => {
    while (scheduledTimeouts.length > 0) {
        const timeoutId = scheduledTimeouts.pop();

        if (timeoutId !== undefined) {
            window.clearTimeout(timeoutId);
        }
    }
};

const startBackendUiClock = () => {
    backendNowMs.value = Date.now();

    if (backendClockIntervalId !== null) {
        return;
    }

    backendClockIntervalId = window.setInterval(() => {
        backendNowMs.value = Date.now();
    }, 1000);
};

const clearBackendUiClock = () => {
    if (backendClockIntervalId !== null) {
        window.clearInterval(backendClockIntervalId);
        backendClockIntervalId = null;
    }
};

const resetSimulation = () => {
    clearTimers();
    isRunning.value = false;
    simulationState.value = createInitialRankingSimulationState(
        selectedScenario.value,
        simulationOptions(),
    );
    selectedEventId.value = null;
};

const runScenario = () => {
    if (!selectedCategory.value) {
        return;
    }

    clearTimers();

    const script = createRankingSimulationScript(selectedScenario.value, simulationOptions());

    simulationState.value = script.initialState;
    selectedEventId.value = script.initialState.eventLog.at(-1)?.id ?? null;
    isRunning.value = true;

    let elapsed = 0;

    script.steps.forEach((step, index) => {
        elapsed += step.delayMs;

        const timeoutId = window.setTimeout(() => {
            simulationState.value = step.apply(simulationState.value);

            const lastEvent = simulationState.value.eventLog.at(-1);

            if (lastEvent) {
                selectedEventId.value = lastEvent.id;
            }

            if (index === script.steps.length - 1) {
                isRunning.value = false;
            }
        }, elapsed);

        scheduledTimeouts.push(timeoutId);
    });
};

const normalizeBackendError = (error: unknown) => {
    if (handleSessionExpiryError(error)) {
        return 'Sesja wygasła. Zaloguj się ponownie, aby wrócić do rankingu.';
    }

    if (isApiClientError(error)) {
        const apiCode = extractApiClientErrorCode(error);
        const apiMessage = extractApiClientErrorMessage(error);

        if (apiCode && apiMessage) {
            return `${apiCode}: ${apiMessage}`;
        }

        return apiMessage ?? `API request failed with status ${error.status}`;
    }

    return error instanceof Error ? error.message : 'Nie udało się połączyć z backendem rankingu.';
};

const formatBackendRealtimeError = (payload: unknown) => {
    const event = payload as RankedRealtimeStreamErrorPayload;
    const errorCode = typeof event?.data?.error_code === 'string' ? event.data.error_code : null;
    const message = typeof event?.data?.message === 'string' ? event.data.message : null;

    if (errorCode && message) {
        return `${errorCode}: ${message}`;
    }

    return message ?? errorCode ?? 'Realtime error z backendu rankingu.';
};

const resolveBackendRealtimeErrorChannel = (payload: unknown): 'queue' | 'match' | null => {
    const event = payload as RankedRealtimeStreamErrorPayload;
    const details = event?.data?.details;

    if (typeof details !== 'object' || details === null) {
        return null;
    }

    return typeof details.channel === 'string' && ['queue', 'match'].includes(details.channel)
        ? (details.channel as 'queue' | 'match')
        : null;
};

const fetchRankedHistory = async () => {
    const response = await apiClient.get<RankedHistoryResponse>(route('api.v1.ranked.history'));

    backendHistory.value = response.data.matches;
};

const buildRankedStreamUrl = () =>
    props.realtimeTransport.sse.enabled
        ? (props.realtimeTransport.sse.url ?? route('api.v1.ranked.stream'))
        : null;

const buildRankedWebSocketUrl = () =>
    props.realtimeTransport.websocket.enabled
        ? props.realtimeTransport.websocket.url
        : null;

const buildMatchEventsUrl = (matchPublicId: string, afterEventId?: string) => {
    const baseUrl = route('api.v1.ranked.matches.events', {
        rankedMatch: matchPublicId,
    });

    if (!afterEventId) {
        return baseUrl;
    }

    const separator = baseUrl.includes('?') ? '&' : '?';

    return `${baseUrl}${separator}after_event_id=${encodeURIComponent(afterEventId)}`;
};

const fetchActiveMatchDetails = async (
    publicId?: string,
    options: {
        silent?: boolean;
    } = {},
) => {
    const matchPublicId = publicId ?? rankedOverview.value?.active_match?.public_id;

    if (!matchPublicId) {
        backendMatchDetails.value = null;

        return;
    }

    if (!options.silent) {
        backendMatchBusy.value = true;
    }

    backendMatchError.value = null;

    try {
        const response = await apiClient.get<RankedMatchDetailsResponse>(
            route('api.v1.ranked.matches.show', matchPublicId),
        );

        backendMatchDetails.value = response.data;
        await fetchActiveMatchEvents(matchPublicId);

        if (response.data.match.status === 'matched') {
            void sendBackendReady({
                silent: true,
                matchPublicId,
            });
        }
    } catch (error) {
        backendMatchError.value = normalizeBackendError(error);
    } finally {
        if (!options.silent) {
            backendMatchBusy.value = false;
        }
    }
};

const fetchActiveMatchEvents = async (
    publicId?: string,
    options: {
        incremental?: boolean;
    } = {},
) => {
    const matchPublicId = publicId
        ?? backendMatchDetails.value?.match.public_id
        ?? rankedOverview.value?.active_match?.public_id
        ?? backendRecentMatch.value?.public_id;

    if (!matchPublicId) {
        backendMatchEvents.value = [];
        selectedBackendEventId.value = backendQueueEvents.value.at(-1)?.id ?? null;

        return;
    }

    try {
        const response = await apiClient.get<RankedMatchEventsResponse>(
            buildMatchEventsUrl(
                matchPublicId,
                options.incremental ? backendMatchEvents.value.at(-1)?.id : undefined,
            ),
        );

        backendMatchEvents.value = options.incremental
            ? mergeRankedRealtimeEvents(backendMatchEvents.value, response.data.events)
            : response.data.events;
        selectedBackendEventId.value = response.data.latest_event_id
            ?? backendQueueEvents.value.at(-1)?.id
            ?? backendMatchEvents.value.at(-1)?.id
            ?? null;
    } catch (error) {
        backendMatchError.value = normalizeBackendError(error);
    }
};

const syncOverviewSnapshot = async (
    data: RankedOverviewResponse['data'],
    options: {
        silentMatchDetails?: boolean;
        skipHistory?: boolean;
    } = {},
) => {
    const previousOverview = rankedOverview.value;

    rankedOverview.value = data;
    backendLoaded.value = true;

    const overviewEvent = shouldAppendRankedOverviewSyncEvent(previousOverview, data)
        ? buildOverviewSyncEvent(data)
        : null;

    if (overviewEvent) {
        backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [overviewEvent]);

        if (
            selectedBackendEventId.value === null
            || ['queued', 'server_full', 'idle'].includes(data.state)
        ) {
            selectedBackendEventId.value = overviewEvent.id;
        }
    }

    if (data.state !== 'server_full' && data.queue?.status !== 'server_full') {
        backendError.value = null;
    }

    const latestSummaryMatch = data.active_match ?? data.recent_match ?? null;

    if (latestSummaryMatch) {
        applyBackendMatchSummary(latestSummaryMatch);
    }

    if (!options.skipHistory) {
        try {
            await fetchRankedHistory();
        } catch (error) {
            backendError.value = normalizeBackendError(error);
        }
    }

    const prefersCompletedMatchDetails = ['match', 'result'].includes(currentScreen.value);
    const preferredMatchPublicId = data.active_match?.public_id
        ?? (prefersCompletedMatchDetails
            ? (data.recent_match?.public_id ?? backendMatchDetails.value?.match.public_id ?? null)
            : null);
    const currentDetailsPublicId = backendMatchDetails.value?.match.public_id ?? null;
    const preferredMatchStatus = data.active_match?.status
        ?? (prefersCompletedMatchDetails ? data.recent_match?.status ?? null : null);
    const currentDetailsStatus = backendMatchDetails.value?.match.status ?? null;

    if (preferredMatchPublicId) {
        if (
            currentDetailsPublicId !== preferredMatchPublicId
            || currentDetailsStatus !== preferredMatchStatus
            || !backendMatchDetails.value
        ) {
            await fetchActiveMatchDetails(preferredMatchPublicId, {
                silent: options.silentMatchDetails ?? Boolean(data.active_match),
            });
        }

        return;
    }

    if (
        !['match', 'result'].includes(currentScreen.value)
        || !backendMatchDetails.value
        || !['finished', 'abandoned'].includes(backendMatchDetails.value.match.status)
    ) {
        backendMatchDetails.value = null;
    }
};

const fetchRankedOverview = async (
    options: {
        silent?: boolean;
        skipHistory?: boolean;
    } = {},
) => {
    if (!options.silent) {
        backendBusy.value = true;
    }

    backendError.value = null;

    try {
        const response = await apiClient.get<RankedOverviewResponse>(route('api.v1.ranked.overview'));

        await syncOverviewSnapshot(response.data, {
            silentMatchDetails: options.silent,
            skipHistory: options.skipHistory,
        });
    } catch (error) {
        backendError.value = normalizeBackendError(error);
    } finally {
        if (!options.silent) {
            backendBusy.value = false;
        }
    }
};

const joinRealQueue = async () => {
    if (!selectedCategory.value || sessionExpired.value) {
        return;
    }

    backendBusy.value = true;
    backendError.value = null;

    try {
        const response = await apiClient.post<RankedJoinResponse>(
            route('api.v1.ranked.queue.join'),
            {
                category_id: selectedCategory.value.id,
            },
        );

        await syncOverviewSnapshot(response.data);
        navigateToRankingScreen('waiting');
    } catch (error) {
        backendError.value = normalizeBackendError(error);
    } finally {
        backendBusy.value = false;
    }
};

const leaveRealQueue = async () => {
    if (sessionExpired.value) {
        return;
    }

    const wasWaitingScreen = currentScreen.value === 'waiting';
    const previousOverview = rankedOverview.value
        ? { ...rankedOverview.value }
        : null;

    queueLeavePending.value = true;
    waitingScreenExitPending.value = wasWaitingScreen;
    backendBusy.value = true;
    backendError.value = null;

    if (rankedOverview.value) {
        rankedOverview.value = {
            ...rankedOverview.value,
            state: rankedOverview.value.active_match ? rankedOverview.value.state : 'idle',
            queue: null,
        };
    }

    if (wasWaitingScreen) {
        navigateToRankingScreen('lobby', {
            replace: true,
        });
    }

    try {
        const response = await apiClient.post<RankedLeaveResponse>(
            route('api.v1.ranked.queue.leave'),
        );

        await syncOverviewSnapshot(response.data);

        if (!wasWaitingScreen) {
            navigateToRankingScreen('lobby');
        }
    } catch (error) {
        if (previousOverview) {
            rankedOverview.value = previousOverview;
        }

        if (wasWaitingScreen) {
            navigateToRankingScreen('waiting', {
                replace: true,
            });
        }

        waitingScreenExitPending.value = false;
        backendError.value = normalizeBackendError(error);
    } finally {
        queueLeavePending.value = false;
        backendBusy.value = false;
    }
};

const abandonActiveMatch = async () => {
    if (sessionExpired.value) {
        return;
    }

    const matchPublicId = backendActiveMatch.value?.public_id
        ?? backendMatchDetails.value?.match.public_id
        ?? null;

    if (!matchPublicId) {
        return;
    }

    backendBusy.value = true;
    backendError.value = null;

    try {
        const response = await apiClient.post<RankedAbandonResponse>(
            route('api.v1.ranked.matches.abandon', matchPublicId),
        );

        await syncOverviewSnapshot(response.data);
        navigateToRankingScreen('result', {
            replace: true,
        });
    } catch (error) {
        backendError.value = normalizeBackendError(error);
    } finally {
        backendBusy.value = false;
    }
};

const applyBackendMatchSummary = (match: RankedApiMatch) => {
    if (rankedOverview.value?.active_match?.public_id === match.public_id) {
        rankedOverview.value = {
            ...rankedOverview.value,
            state: match.state,
            active_match: match,
        };
    }

    if (backendMatchDetails.value?.match.public_id === match.public_id) {
        backendMatchDetails.value = {
            ...backendMatchDetails.value,
            match,
        };
    }
};

const applyBackendAnswerResponse = (payload: RankedMatchAnswerResponse['data']) => {
    applyBackendMatchSummary(payload.match);

    if (backendMatchDetails.value?.match.public_id !== payload.match.public_id) {
        return;
    }

    backendMatchDetails.value = {
        ...backendMatchDetails.value,
        match: payload.match,
        progress: payload.progress,
        questions: backendMatchDetails.value.questions.map((question) => {
            if (question.id !== payload.answer.question_id) {
                return question;
            }

            const isCompleted = ['finished', 'abandoned'].includes(payload.match.status);

            return {
                ...question,
                selected_answer: payload.answer.selected_answer,
                is_answered: true,
                response_time_ms: payload.answer.response_time_ms,
                is_correct: isCompleted ? payload.answer.is_correct : question.is_correct,
            };
        }),
    };
};

const resetBackendQuestionState = () => {
    selectedBackendQuestionId.value = null;
    backendQuestionTimers = {};
};

const ensureBackendQuestionTimer = (questionId: number | null) => {
    if (questionId === null) {
        return;
    }

    const question = backendQuestions.value.find((item) => item.id === questionId);
    const matchState = backendMatchDetails.value?.match.state
        ?? rankedOverview.value?.active_match?.state
        ?? null;

    if (!question || question.is_answered || ['finished', 'abandoned', 'opponent_disconnected'].includes(matchState ?? '')) {
        return;
    }

    if (typeof backendQuestionTimers[questionId] === 'number') {
        return;
    }

    backendQuestionTimers = {
        ...backendQuestionTimers,
        [questionId]: Date.now(),
    };
};

const selectBackendQuestion = (questionId: number | null) => {
    selectedBackendQuestionId.value = questionId;
    ensureBackendQuestionTimer(questionId);
};

const submitBackendAnswer = async (questionId: number, userAnswer: string) => {
    if (sessionExpired.value) {
        return;
    }

    const matchPublicId = rankedOverview.value?.active_match?.public_id;

    if (!matchPublicId) {
        return;
    }

    backendMatchBusy.value = true;
    backendMatchError.value = null;

    try {
        const nextQuestionId = findNextUnansweredRankedQuestionId(backendQuestions.value, questionId);

        const response = await apiClient.post<RankedMatchAnswerResponse>(
            route('api.v1.ranked.matches.answers.store', matchPublicId),
            {
                question_id: questionId,
                user_answer: userAnswer,
                response_time_ms: getRankedQuestionResponseTimeMs(
                    backendQuestionTimers[questionId],
                    Date.now(),
                ),
            },
        );

        applyBackendAnswerResponse(response.data);
        delete backendQuestionTimers[questionId];
        await fetchRankedOverview({
            silent: true,
            skipHistory: true,
        });
        await fetchActiveMatchDetails(matchPublicId, {
            silent: true,
        });

        if (nextQuestionId !== null && nextQuestionId !== questionId) {
            selectBackendQuestion(nextQuestionId);
        }
    } catch (error) {
        backendMatchError.value = normalizeBackendError(error);
    } finally {
        backendMatchBusy.value = false;
    }
};

const sendBackendReady = async (
    options: {
        silent?: boolean;
        matchPublicId?: string | null;
    } = {},
) => {
    const matchPublicId = options.matchPublicId
        ?? rankedOverview.value?.active_match?.public_id
        ?? backendMatchDetails.value?.match.public_id
        ?? null;

    if (
        sessionExpired.value
        ||
        !matchPublicId
        || backendReadyBusy.value
        || backendReadySentForMatchId.value === matchPublicId
    ) {
        return;
    }

    backendReadyBusy.value = true;

    if (!options.silent) {
        backendMatchError.value = null;
    }

    try {
        const response = await apiClient.post<RankedMatchReadyResponse>(
            route('api.v1.ranked.matches.ready', matchPublicId),
        );

        applyBackendMatchSummary(response.data.match);

        if (backendMatchDetails.value?.match.public_id === response.data.match.public_id) {
            backendMatchDetails.value = {
                ...backendMatchDetails.value,
                match: response.data.match,
                progress: response.data.progress,
            };
        }

        backendReadySentForMatchId.value = matchPublicId;
    } catch (error) {
        const message = normalizeBackendError(error);

        if (!options.silent) {
            backendMatchError.value = message;
        }
    } finally {
        backendReadyBusy.value = false;
    }
};

const sendBackendPong = async (
    options: {
        silent?: boolean;
    } = {},
) => {
    const matchPublicId = rankedOverview.value?.active_match?.public_id;

    if (sessionExpired.value || !matchPublicId || backendPongBusy.value) {
        return;
    }

    backendPongBusy.value = true;

    if (!options.silent) {
        backendMatchBusy.value = true;
    }

    backendMatchError.value = null;

    try {
        const response = await apiClient.post<RankedMatchPongResponse>(
            route('api.v1.ranked.matches.pong', matchPublicId),
        );

        applyBackendMatchSummary(response.data.match);

        if (!['matched', 'in_progress'].includes(response.data.match.status)) {
            await fetchRankedOverview({
                silent: true,
            });
        }
    } catch (error) {
        backendMatchError.value = normalizeBackendError(error);
    } finally {
        backendPongBusy.value = false;

        if (!options.silent) {
            backendMatchBusy.value = false;
        }
    }
};

const pollBackendOverviewSilently = () => fetchRankedOverview({
    silent: true,
    skipHistory: true,
});

const handleBackendStreamOverviewSync = (payload: unknown) => {
    const message = payload as RankedRealtimeStreamOverviewMessage;

    if (!message?.data) {
        return;
    }

    void syncOverviewSnapshot(message.data, {
        silentMatchDetails: true,
        skipHistory: true,
    });
};

const handleBackendStreamHeartbeat = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);

    void sendBackendPong({
        silent: true,
    });
};

const handleBackendStreamQueueQueued = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);
    backendError.value = null;
    selectedBackendEventId.value = event.id;
};

const handleBackendStreamQueueResumed = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);
    backendError.value = null;
    selectedBackendEventId.value = event.id;
};

const handleBackendStreamQueueLeft = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);
    backendError.value = null;
    selectedBackendEventId.value = event.id;
};

const handleBackendStreamQueueServerFull = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);
    selectedBackendEventId.value = event.id;
};

const handleBackendStreamErrorEvent = (payload: unknown) => {
    const event = payload as RankedRealtimeStreamErrorPayload;

    if (!event?.id) {
        return;
    }

    const channel = resolveBackendRealtimeErrorChannel(payload);
    const formattedError = formatBackendRealtimeError(payload);

    if (channel === 'match') {
        backendMatchEvents.value = mergeRankedRealtimeEvents(backendMatchEvents.value, [
            event as RankedRealtimeEvent,
        ]);
        backendMatchError.value = formattedError;
    } else {
        backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [
            event as RankedRealtimeEvent,
        ]);
        backendError.value = formattedError;
    }

    selectedBackendEventId.value = event.id;
};

const handleBackendStreamQueueMatched = (payload: unknown) => {
    const event = payload as RankedRealtimeEvent;

    if (!event?.id) {
        return;
    }

    backendQueueEvents.value = mergeRankedRealtimeEvents(backendQueueEvents.value, [event]);
    backendError.value = null;
    selectedBackendEventId.value = event.id;
};

const handleBackendStreamMatchEvents = (payload: unknown) => {
    const message = payload as RankedRealtimeStreamMatchEventsMessage;

    if (!message?.data?.match_public_id) {
        return;
    }

    backendMatchEvents.value = mergeRankedRealtimeEvents(
        backendMatchEvents.value,
        message.data.events,
    );
    selectedBackendEventId.value = message.data.latest_event_id
        ?? backendQueueEvents.value.at(-1)?.id
        ?? backendMatchEvents.value.at(-1)?.id
        ?? null;
};

const connectBackendRealtimeStream = () => {
    backendStreamState.value = 'connecting';
    backendLiveTransportKind.value = 'none';
    backendStreamClient = new RankedRealtimeTransportClient({
        onOpen: () => {
            backendStreamState.value = 'open';
            syncBackendRealtimeTransport();
        },
        onError: () => {
            backendLiveTransportKind.value = 'polling';
            backendStreamState.value = backendStreamState.value === 'open' ? 'connecting' : 'error';
            syncBackendRealtimeTransport();
        },
        onFallbackStart: () => {
            backendStreamState.value = 'connecting';
            backendLiveTransportKind.value = 'polling';
            syncBackendRealtimeTransport();
        },
        onTransportChange: (transport) => {
            backendLiveTransportKind.value = transport;
        },
        onEventError: handleBackendStreamErrorEvent,
        onHeartbeat: handleBackendStreamHeartbeat,
        onOverviewSync: handleBackendStreamOverviewSync,
        onQueueLeft: handleBackendStreamQueueLeft,
        onQueueQueued: handleBackendStreamQueueQueued,
        onQueueResumed: handleBackendStreamQueueResumed,
        onQueueMatched: handleBackendStreamQueueMatched,
        onQueueServerFull: handleBackendStreamQueueServerFull,
        onMatchEvents: handleBackendStreamMatchEvents,
    });

    const started = backendStreamClient.connect({
        preferWebSocket: props.realtimeTransport.preferred === 'websocket',
        websocketUrl: buildRankedWebSocketUrl(),
        eventSourceUrl: buildRankedStreamUrl(),
        allowEventSourceFallback: props.realtimeTransport.sse.enabled,
    });

    if (!started) {
        backendStreamState.value = 'unsupported';
        backendLiveTransportKind.value = 'polling';
        syncBackendRealtimeTransport();

        return;
    }

    syncBackendRealtimeTransport();
};

const destroyBackendRealtimeStream = () => {
    backendStreamClient?.destroy();
    backendStreamClient = null;
    backendLiveTransportKind.value = 'none';
};

const backendRealtimeTransport = new RankedRealtimePollingTransport({
    fetchOverview: () => pollBackendOverviewSilently(),
    fetchEvents: (matchPublicId) => fetchActiveMatchEvents(matchPublicId, {
        incremental: true,
    }),
    sendPong: () => sendBackendPong({
        silent: true,
    }),
});

const syncBackendRealtimeTransport = () => {
    if (sessionExpired.value) {
        backendRealtimeTransport.destroy();
        return;
    }

    const streamOwnsOverviewAndEvents = backendStreamState.value === 'open';

    backendRealtimeTransport.sync({
        queueStatus: rankedOverview.value?.queue?.status ?? null,
        activeMatchPublicId: rankedOverview.value?.active_match?.public_id ?? null,
        activeMatchStatus: rankedOverview.value?.active_match?.status ?? null,
        heartbeatSeconds: props.specSummary.heartbeat_seconds,
        enableHeartbeat: !streamOwnsOverviewAndEvents,
        enableOverviewPolling: !streamOwnsOverviewAndEvents,
        enableEventPolling: !streamOwnsOverviewAndEvents,
    });
};

watch([selectedScenario, selectedCategoryId], () => {
    resetSimulation();
});

watch(
    () => simulationState.value.eventLog.length,
    (eventCount) => {
        if (eventCount === 0) {
            selectedEventId.value = null;

            return;
        }

        selectedEventId.value = simulationState.value.eventLog[eventCount - 1]?.id ?? null;
    },
);

onUnmounted(() => {
    clearTimers();
    clearBackendUiClock();
    destroyBackendRealtimeStream();
    backendRealtimeTransport.destroy();
});

onMounted(() => {
    startBackendUiClock();
    connectBackendRealtimeStream();
    void fetchRankedOverview();
});

const preferredRankingScreen = computed<RankedScreen>(() => {
    const activeMatch = rankedOverview.value?.active_match ?? null;
    const hasTerminalMatchDetails = Boolean(
        backendMatchDetails.value
        && ['finished', 'abandoned'].includes(backendMatchDetails.value.match.status),
    );
    const hasRecentTerminalMatch = Boolean(
        backendRecentMatch.value
        && ['finished', 'abandoned'].includes(backendRecentMatch.value.status),
    );

    if (activeMatch) {
        const matchState = activeMatch.state ?? activeMatch.status;

        if (matchState === 'matched') {
            const hasLoadedCurrentMatchDetails = backendMatchDetails.value?.match.public_id === activeMatch.public_id;

            return hasLoadedCurrentMatchDetails ? 'waiting' : 'waiting';
        }

        return 'match';
    }

    if (currentScreen.value === 'match' && hasTerminalMatchDetails) {
        return 'result';
    }

    if (currentScreen.value === 'match' && backendMatchDetails.value) {
        return 'match';
    }

    if (rankedOverview.value?.queue) {
        return 'waiting';
    }

    if (currentScreen.value === 'result' && (hasTerminalMatchDetails || hasRecentTerminalMatch || backendLastResultCard.value)) {
        return 'result';
    }

    return 'lobby';
});

watch(
    () => [
        currentScreen.value,
        backendLoaded.value ? 'loaded' : 'loading',
        preferredRankingScreen.value,
    ].join(':'),
    () => {
        if (!backendLoaded.value || pendingScreenNavigation.value) {
            return;
        }

        if (
            navigationLock.value === 'lobby'
            && currentScreen.value === 'lobby'
            && preferredRankingScreen.value !== 'lobby'
        ) {
            return;
        }

        if (preferredRankingScreen.value === currentScreen.value) {
            return;
        }

        navigateToRankingScreen(preferredRankingScreen.value, {
            replace: true,
        });
    },
);

watch(
    () => [
        rankedOverview.value?.queue?.status ?? 'no-queue',
        rankedOverview.value?.active_match?.public_id ?? 'none',
        rankedOverview.value?.active_match?.status ?? 'idle',
    ].join(':'),
    () => {
        syncBackendRealtimeTransport();
    },
);

const selectedScenarioMeta = computed(() =>
    rankedScenarioOptions.find((option) => option.value === selectedScenario.value)
    ?? rankedScenarioOptions[0],
);

const phaseLabel = computed(() => {
    const labels: Record<string, string> = {
        idle: 'Gotowy',
        queueing: 'Queue',
        matched: 'Matched',
        in_progress: 'Live',
        opponent_disconnected: 'Reconnect window',
        server_full: 'Server full',
        finished: 'Finished',
        abandoned: 'Abandoned',
    };

    return labels[simulationState.value.phase] ?? simulationState.value.phase;
});

const phaseChipClass = computed(() => {
    const classes: Record<string, string> = {
        idle: 'bg-[#efe8dd] text-[#6f4a23]',
        queueing: 'bg-[#fff1df] text-[#9a530b]',
        matched: 'bg-[#e8f3ff] text-[#155fa0]',
        in_progress: 'bg-[#ecfdf3] text-[#0f7b43]',
        opponent_disconnected: 'bg-[#fff4da] text-[#986300]',
        server_full: 'bg-[#fff0eb] text-[#b43b16]',
        finished: 'bg-[#ecfdf3] text-[#0f7b43]',
        abandoned: 'bg-[#fdecec] text-[#a52323]',
    };

    return classes[simulationState.value.phase] ?? 'bg-[#f3f4f6] text-[#374151]';
});

const visibleQuestionChips = computed(() =>
    simulationState.value.questions.slice(0, 10),
);

const revealedQuestionPreview = computed(() =>
    simulationState.value.questions
        .slice(0, simulationState.value.revealedQuestionCount)
        .slice(-4),
);

const selectedEvent = computed<RankedRealtimeEvent | null>(() =>
    simulationState.value.eventLog.find((event) => event.id === selectedEventId.value)
    ?? simulationState.value.eventLog.at(-1)
    ?? null,
);

const selectedEventJson = computed(() => {
    if (selectedEvent.value) {
        return JSON.stringify(selectedEvent.value, null, 2);
    }

    return JSON.stringify({
        event: 'queue.matched',
        id: 'evt_queue_matched_user_123_match_b_happy_path',
        api_version: props.specSummary.api_version,
        timestamp: '2026-04-09T11:58:45Z',
        data: {
            user_id: page.props.auth?.user?.id ?? 123,
            match_id: 'match_b_happy_path',
            opponent: {
                user_id: 456,
                username: 'player_b',
                elo: 1550,
            },
            starting_in_seconds: 5,
        },
    }, null, 2);
});

const resultReasonLabel = computed(() => {
    const reason = simulationState.value.result?.reason;
    const labels: Record<string, string> = {
        more_correct_answers: 'więcej poprawnych odpowiedzi',
        faster_time: 'szybszy łączny czas reakcji',
        draw: 'remis',
        walkover: 'walkower',
        player_left_match: 'opuszczenie meczu',
        opponent_disconnect_no_return: 'brak powrotu po disconnectcie',
        opponent_timeout_3_strikes: 'trzy timeouty przeciwnika',
        queue_timeout: 'timeout kolejki',
    };

    return reason ? labels[reason] ?? reason : null;
});

const matchProgressPercent = computed(() => {
    const answered = Math.max(
        simulationState.value.player.totalAnswered,
        simulationState.value.opponent.totalAnswered,
    );

    return Math.min((answered / props.specSummary.total_questions) * 100, 100);
});

const channelsSummary = computed(() => [
    {
        label: 'Queue channel',
        value: props.specSummary.queue_channel_pattern,
        detail: 'matchmaking i server full',
    },
    {
        label: 'Match channel',
        value: props.specSummary.match_channel_pattern,
        detail: 'heartbeat i eventy meczu',
    },
]);

const backendStateClass = (state: string) => {
    const classes: Record<string, string> = {
        idle: 'bg-[#f3f4f6] text-[#4b5563]',
        queueing: 'bg-[#fff4da] text-[#986300]',
        queued: 'bg-[#fff4da] text-[#986300]',
        server_full: 'bg-[#fff0eb] text-[#b43b16]',
        matched: 'bg-[#e8f3ff] text-[#155fa0]',
        in_progress: 'bg-[#ecfdf3] text-[#0f7b43]',
        opponent_disconnected: 'bg-[#fff4da] text-[#986300]',
        finished: 'bg-[#ecfdf3] text-[#0f7b43]',
        abandoned: 'bg-[#fdecec] text-[#a52323]',
    };

    return classes[state] ?? 'bg-[#f3f4f6] text-[#4b5563]';
};

const backendOutcomeLabel = (outcome: string) => {
    const labels: Record<string, string> = {
        win: 'Wygrana',
        loss: 'Przegrana',
        draw: 'Remis',
        abandoned: 'Przerwany',
    };

    return labels[outcome] ?? outcome;
};

const backendOutcomeClass = (outcome: string) => {
    const classes: Record<string, string> = {
        win: 'bg-[#ecfdf3] text-[#0f7b43]',
        loss: 'bg-[#fdecec] text-[#a52323]',
        draw: 'bg-[#eef2ff] text-[#3749a4]',
        abandoned: 'bg-[#fff4da] text-[#986300]',
    };

    return classes[outcome] ?? 'bg-[#f3f4f6] text-[#4b5563]';
};

const backendReasonLabel = (reason: string | null | undefined) => {
    const labels: Record<string, string> = {
        more_correct_answers: 'więcej poprawnych odpowiedzi',
        faster_time: 'szybszy łączny czas reakcji',
        draw: 'remis',
        walkover: 'walkower',
        player_left_match: 'opuszczenie meczu',
        opponent_disconnect_no_return: 'brak powrotu przeciwnika',
        opponent_timeout_3_strikes: 'timeout przeciwnika',
        queue_timeout: 'timeout kolejki',
        both_players_inactive: 'obie strony zniknęły z meczu',
    };

    if (!reason) {
        return '—';
    }

    return labels[reason] ?? reason;
};

const backendStateHumanLabel = (state: string | null | undefined) => {
    const labels: Record<string, string> = {
        idle: 'gotowy',
        queued: 'w kolejce',
        server_full: 'brak slotu',
        matched: 'sparowano',
        in_progress: 'mecz trwa',
        opponent_disconnected: 'czekamy na powrót',
        finished: 'zakończony',
        abandoned: 'walkower',
    };

    return state ? labels[state] ?? state : '—';
};

const backendStateLabel = computed(() => rankedOverview.value?.state ?? 'idle');
const backendStateDisplayLabel = computed(() => backendStateHumanLabel(backendStateLabel.value));

const backendStateChipClass = computed(() => {
    return backendStateClass(backendStateLabel.value);
});

const backendCapacity = computed(() => rankedOverview.value?.capacity ?? null);

const startSummaryCards = computed(() => {
    if (!rankedOverview.value) {
        return [];
    }

    return [
        {
            label: 'Twoje ELO',
            value: String(rankedOverview.value.rating.rating),
            detail: `peak ${rankedOverview.value.rating.peak_rating}`,
        },
    ];
});
const startPrimaryCard = computed(() => startSummaryCards.value[0] ?? null);
const startSocialPlatforms = [
    { key: 'instagram', label: 'Instagram' },
    { key: 'tiktok', label: 'TikTok' },
    { key: 'youtube', label: 'YouTube' },
    { key: 'facebook', label: 'Facebook' },
] as const;

const backendActiveMatch = computed(() => rankedOverview.value?.active_match ?? null);

const backendOpponent = computed(() => backendActiveMatch.value?.opponent ?? null);

const backendResultMatch = computed<RankedApiMatch | RankedApiRecentMatch | null>(() => {
    const detailedMatch = backendMatchDetails.value?.match ?? null;

    if (detailedMatch && ['finished', 'abandoned'].includes(detailedMatch.status)) {
        return detailedMatch;
    }

    if (backendRecentMatch.value && ['finished', 'abandoned'].includes(backendRecentMatch.value.status)) {
        return backendRecentMatch.value;
    }

    return null;
});

const backendResultCurrentPlayer = computed(() =>
    backendResultMatch.value?.players.find((player) => player.is_current_user) ?? null,
);

const backendResultOpponentPlayer = computed(() =>
    backendResultMatch.value?.players.find((player) => !player.is_current_user)
    ?? backendResultMatch.value?.opponent
    ?? null,
);

const backendLastResultCard = computed<BackendRecentMatchCard | null>(() => {
    if (backendRecentMatch.value) {
        return {
            publicId: backendRecentMatch.value.public_id,
            resultLabel: backendRecentMatch.value.result_label,
            outcome: backendRecentMatch.value.outcome,
            categoryName: backendRecentMatch.value.category.name,
            playedAt: backendRecentMatch.value.played_at,
            totalQuestions: backendRecentMatch.value.total_questions,
            currentUserCorrectAnswers: backendRecentMatch.value.current_user.correct_answers,
            currentUserEloChange: backendRecentMatch.value.current_user.elo_change,
            reason: backendRecentMatch.value.reason,
        };
    }

    if (
        !['finished', 'abandoned'].includes(backendRealtimeProjection.value.phase)
        || !backendRealtimeProjection.value.matchId
    ) {
        return null;
    }

    const categoryName = backendRealtimeProjection.value.resultCategoryName
        ?? backendMatchDetails.value?.match.category.name
        ?? selectedCategory.value?.name
        ?? props.selectedCategory?.name
        ?? 'Tryb rankingowy 1v1';
    const outcome = backendRealtimeProjection.value.outcome
        ?? (backendRealtimeProjection.value.phase === 'abandoned' ? 'abandoned' : 'draw');
    const resultLabel = backendRealtimeProjection.value.resultLabel
        ?? backendOutcomeLabel(outcome);

    return {
        publicId: backendRealtimeProjection.value.matchId,
        resultLabel,
        outcome,
        categoryName,
        playedAt: backendRealtimeProjection.value.finishedAt,
        totalQuestions: backendRealtimeProjection.value.resultTotalQuestions ?? props.specSummary.total_questions,
        currentUserCorrectAnswers: backendRealtimeProjection.value.currentUserCorrectAnswers,
        currentUserEloChange: backendRealtimeProjection.value.currentUserEloChange,
        reason: backendRealtimeProjection.value.resultReason,
    };
});

const resolveResultOpponentOutcome = (outcome: string | null | undefined) => {
    if (outcome === 'win') {
        return 'loss';
    }

    if (outcome === 'loss' || outcome === 'abandoned') {
        return 'win';
    }

    return 'draw';
};

const backendResultParticipants = computed<{
    current: RankedResultParticipantCard;
    opponent: RankedResultParticipantCard;
} | null>(() => {
    const currentPlayer = backendResultCurrentPlayer.value;
    const opponentPlayer = backendResultOpponentPlayer.value;
    const resultCard = backendLastResultCard.value;
    const totalQuestions = resultCard?.totalQuestions
        ?? backendResultMatch.value?.total_questions
        ?? props.specSummary.total_questions;

    if (!currentPlayer || !opponentPlayer) {
        return null;
    }

    const currentOutcome = resultCard?.outcome ?? 'draw';
    const opponentOutcome = resolveResultOpponentOutcome(currentOutcome);

    return {
        current: {
            username: currentPlayer.username || playerName.value,
            roleLabel: 'Ty',
            points: currentPlayer.points ?? currentPlayer.correct_answers ?? 0,
            correctAnswers: currentPlayer.correct_answers ?? 0,
            totalAnswered: currentPlayer.total_answered ?? 0,
            totalQuestions,
            responseTimeMs: currentPlayer.sum_response_time_ms ?? 0,
            eloChange: currentPlayer.elo_change ?? resultCard?.currentUserEloChange ?? null,
            outcome: currentOutcome,
            outcomeLabel: backendOutcomeLabel(currentOutcome),
            outcomeClass: backendOutcomeClass(currentOutcome),
            surfaceClass: currentOutcome === 'win'
                ? 'bg-[#fff8ef]'
                : currentOutcome === 'loss'
                    ? 'bg-[#fbfcfd]'
                    : 'bg-white',
            avatarFrameClass: currentOutcome === 'win'
                ? 'border-[#f58220]/35 shadow-[0_18px_34px_rgba(245,130,32,0.14)]'
                : 'border-[#d9dde5] shadow-[0_16px_28px_rgba(15,23,42,0.08)]',
            avatarImageClass: 'object-cover object-[56%_32%]',
            initials: leaderboardAvatarLabel(currentPlayer.username || playerName.value),
        },
        opponent: {
            username: opponentPlayer.username || backendRealtimeProjection.value.opponentUsername || 'Przeciwnik',
            roleLabel: 'Przeciwnik',
            points: opponentPlayer.points ?? opponentPlayer.correct_answers ?? 0,
            correctAnswers: opponentPlayer.correct_answers ?? 0,
            totalAnswered: opponentPlayer.total_answered ?? 0,
            totalQuestions,
            responseTimeMs: opponentPlayer.sum_response_time_ms ?? 0,
            eloChange: opponentPlayer.elo_change ?? null,
            outcome: opponentOutcome,
            outcomeLabel: backendOutcomeLabel(opponentOutcome),
            outcomeClass: backendOutcomeClass(opponentOutcome),
            surfaceClass: opponentOutcome === 'win'
                ? 'bg-[#fff8ef]'
                : opponentOutcome === 'loss'
                    ? 'bg-[#fbfcfd]'
                    : 'bg-white',
            avatarFrameClass: opponentOutcome === 'win'
                ? 'border-[#f58220]/35 shadow-[0_18px_34px_rgba(245,130,32,0.14)]'
                : 'border-[#d9dde5] shadow-[0_16px_28px_rgba(15,23,42,0.08)]',
            avatarImageClass: 'object-cover object-[40%_30%] -scale-x-100 saturate-75 opacity-95',
            initials: leaderboardAvatarLabel(opponentPlayer.username || backendRealtimeProjection.value.opponentUsername || 'Przeciwnik'),
        },
    };
});

const backendQuestions = computed(() => backendMatchDetails.value?.questions ?? []);

const backendResolvedQuestionId = computed(() =>
    resolveActiveRankedQuestionId(backendQuestions.value, selectedBackendQuestionId.value),
);

const backendSelectedQuestion = computed(() =>
    backendQuestions.value.find((question) => question.id === backendResolvedQuestionId.value)
    ?? null,
);

const backendQuestionProgress = computed(() => {
    const total = backendQuestions.value.length;
    const answered = backendQuestions.value.filter((question) => question.is_answered).length;

    return {
        answered,
        total,
        remaining: Math.max(total - answered, 0),
    };
});

const backendQueueTimeoutRemainingSeconds = computed(() => {
    const queue = rankedOverview.value?.queue;

    if (!queue || queue.status !== 'queued') {
        return null;
    }

    return getRankedRemainingSeconds(
        queue.joined_at,
        props.specSummary.matchmaking_timeout_seconds,
        backendNowMs.value,
    ) ?? props.specSummary.matchmaking_timeout_seconds;
});

watch(
    () => backendMatchDetails.value?.match.public_id ?? null,
    (matchPublicId, previousMatchPublicId) => {
        if (matchPublicId !== previousMatchPublicId) {
            resetBackendQuestionState();
            backendReadySentForMatchId.value = null;
        }
    },
);

watch(
    () => backendActiveMatch.value?.public_id ?? null,
    (matchPublicId, previousMatchPublicId) => {
        if (matchPublicId !== previousMatchPublicId) {
            backendReadySentForMatchId.value = null;
        }
    },
);

watch(
    () => rankedOverview.value?.queue?.joined_at ?? null,
    (joinedAt, previousJoinedAt) => {
        if (joinedAt !== previousJoinedAt) {
            backendQueueTimeoutSyncRequestedForJoinedAt.value = null;
        }
    },
);

watch(
    () => backendQuestions.value,
    (questions) => {
        const resolvedQuestionId = resolveActiveRankedQuestionId(
            questions,
            selectedBackendQuestionId.value,
        );

        if (resolvedQuestionId !== selectedBackendQuestionId.value) {
            selectedBackendQuestionId.value = resolvedQuestionId;
        }

        ensureBackendQuestionTimer(resolvedQuestionId);
    },
    {
        immediate: true,
    },
);

watch(
    () => ({
        queueStatus: rankedOverview.value?.queue?.status ?? null,
        joinedAt: rankedOverview.value?.queue?.joined_at ?? null,
        remainingSeconds: backendQueueTimeoutRemainingSeconds.value,
    }),
    ({ queueStatus, joinedAt, remainingSeconds }) => {
        if (
            queueStatus !== 'queued'
            || !joinedAt
            || remainingSeconds === null
            || remainingSeconds > 0
        ) {
            return;
        }

        if (backendQueueTimeoutSyncRequestedForJoinedAt.value === joinedAt) {
            return;
        }

        backendQueueTimeoutSyncRequestedForJoinedAt.value = joinedAt;

        void fetchRankedOverview({
            silent: true,
            skipHistory: true,
        });
    },
    {
        immediate: true,
    },
);

watch(currentScreen, (screen) => {
    if (screen !== 'waiting') {
        waitingScreenExitPending.value = false;
    }
});

watch(
    () => [
        currentScreen.value,
        backendActiveMatch.value?.public_id ?? 'none',
        backendActiveMatch.value?.status ?? 'idle',
        backendMatchDetails.value?.match.public_id ?? 'none',
        backendMatchDetails.value?.match.status ?? 'idle',
    ].join(':'),
    () => {
        const activeMatch = backendActiveMatch.value;

        if (
            !activeMatch
            || activeMatch.status !== 'matched'
            || !backendMatchDetails.value
            || backendMatchDetails.value.match.public_id !== activeMatch.public_id
            || !['waiting', 'match'].includes(currentScreen.value)
        ) {
            return;
        }

        void sendBackendReady({
            silent: true,
            matchPublicId: activeMatch.public_id,
        });
    },
    {
        immediate: true,
    },
);

const backendServerFullDetails = computed(() =>
    rankedOverview.value?.queue?.status === 'server_full'
        ? rankedOverview.value.queue.server_full
        : null,
);

const backendQueueClock = computed<BackendClockCard | null>(() => {
    const queue = rankedOverview.value?.queue;

    if (!queue) {
        return null;
    }

    if (queue.status === 'queued') {
        const remainingSeconds = backendQueueTimeoutRemainingSeconds.value
            ?? props.specSummary.matchmaking_timeout_seconds;
        const elapsedSeconds = getRankedElapsedSeconds(queue.joined_at, backendNowMs.value) ?? 0;
        const progressPercent = Math.min(
            (elapsedSeconds / Math.max(props.specSummary.matchmaking_timeout_seconds, 1)) * 100,
            100,
        );

        return {
            eyebrow: 'Pozostały czas',
            primary: String(remainingSeconds),
            secondary: remainingSeconds > 0
                ? 'Czekamy na przeciwnika. Jeśli czas minie, opuścisz kolejkę automatycznie.'
                : 'Kończymy to wyszukiwanie i odświeżamy stan kolejki.',
            progressPercent,
            tone: 'text-[#986300]',
        };
    }

    if (queue.status === 'server_full') {
        const sinceRecordedSeconds = getRankedElapsedSeconds(
            queue.server_full?.recorded_at ?? queue.joined_at,
            backendNowMs.value,
        ) ?? 0;
        const currentCapacity = queue.server_full?.current_capacity ?? backendCapacity.value?.current_players ?? 0;
        const maxPlayers = queue.server_full?.max_concurrent_players ?? backendCapacity.value?.max_players ?? props.specSummary.max_concurrent_players;

        return {
            eyebrow: 'Brak wolnego slotu',
            primary: `${currentCapacity}/${maxPlayers}`,
            secondary: queue.server_full?.message
                ?? `System jest pełny od ${formatRankedClock(sinceRecordedSeconds)}. Twoje miejsce w kolejce pozostaje zachowane.`,
            progressPercent: null,
            tone: 'text-[#b43b16]',
        };
    }

    if (queue.status === 'matched') {
        const matchedSeconds = getRankedElapsedSeconds(queue.matched_at, backendNowMs.value) ?? 0;

        return {
            eyebrow: 'Znaleziono przeciwnika',
            primary: formatRankedClock(matchedSeconds),
            secondary: rankedOverview.value?.active_match
                ? 'Przeciwnik jest gotowy. Za chwilę przejdziesz do meczu.'
                : 'Mecz został sparowany. Ekran dociąga jeszcze pełne szczegóły pojedynku.',
            progressPercent: null,
            tone: 'text-[#155fa0]',
        };
    }

    return {
        eyebrow: 'Status kolejki',
        primary: backendStateHumanLabel(queue.status),
        secondary: 'Wpis do kolejki jest zapisany i czeka na następną aktualizację.',
        progressPercent: null,
        tone: 'text-[#4b5563]',
    };
});

const backendServerFullBanner = computed(() => {
    if (!backendServerFullDetails.value) {
        return null;
    }

    return {
        title: backendServerFullDetails.value.error_code ?? 'SERVER_FULL',
        body: backendServerFullDetails.value.message
            ?? 'Serwer chwilowo nie ma wolnego slotu dla kolejnego użytkownika rankingu.',
        meta: [
            backendServerFullDetails.value.position_in_queue !== null
                ? `pozycja ${backendServerFullDetails.value.position_in_queue}`
                : null,
            backendServerFullDetails.value.estimated_wait_minutes !== null
                ? `ETA ${backendServerFullDetails.value.estimated_wait_minutes} min`
                : null,
            backendCapacity.value
                ? `${backendCapacity.value.current_players}/${backendCapacity.value.max_players} aktywnych`
                : null,
        ].filter((value): value is string => Boolean(value)).join(' • '),
    };
});

const backendCombinedEvents = computed(() =>
    sortRankedRealtimeEvents(
        mergeRankedRealtimeEvents(backendQueueEvents.value, backendMatchEvents.value),
    ),
);

const backendRealtimeProjection = computed(() =>
    reduceRankedRealtimeEvents(backendCombinedEvents.value),
);

const selectedBackendEvent = computed<RankedRealtimeEvent | null>(() =>
    backendCombinedEvents.value.find((event) => event.id === selectedBackendEventId.value)
    ?? backendCombinedEvents.value.at(-1)
    ?? null,
);

const selectedBackendEventJson = computed(() => {
    if (selectedBackendEvent.value) {
        return JSON.stringify(selectedBackendEvent.value, null, 2);
    }

    return JSON.stringify({
        event: 'queue.matched',
        id: 'evt_queue_matched_backend_preview',
        api_version: props.specSummary.api_version,
        timestamp: '2026-04-21T20:00:00Z',
        data: {
            match_id: 'backend_preview',
        },
    }, null, 2);
});

const backendPresence = computed(() => {
    if (
        backendMatchDetails.value
        && backendActiveMatch.value
        && backendMatchDetails.value.match.public_id === backendActiveMatch.value.public_id
    ) {
        return backendMatchDetails.value.match.presence;
    }

    return backendActiveMatch.value?.presence
        ?? backendMatchDetails.value?.match.presence
        ?? null;
});

const backendCurrentPresence = computed(() => backendPresence.value?.current_user ?? null);
const backendOpponentPresence = computed(() => backendPresence.value?.opponent ?? null);
const backendCurrentLastSeenSeconds = computed(() =>
    getRankedElapsedSeconds(backendCurrentPresence.value?.last_seen_at, backendNowMs.value)
    ?? backendCurrentPresence.value?.last_seen_seconds_ago
    ?? null,
);
const backendOpponentLastSeenSeconds = computed(() =>
    getRankedElapsedSeconds(backendOpponentPresence.value?.last_seen_at, backendNowMs.value)
    ?? backendOpponentPresence.value?.last_seen_seconds_ago
    ?? null,
);
const backendCurrentDisconnectCountdownSeconds = computed(() => {
    if (!backendCurrentPresence.value) {
        return null;
    }

    if (backendCurrentPresence.value.presence_state === 'disconnected') {
        return 0;
    }

    return getRankedRemainingSeconds(
        backendCurrentPresence.value.last_seen_at,
        props.specSummary.disconnect_timeout_seconds,
        backendNowMs.value,
    ) ?? backendCurrentPresence.value.seconds_until_disconnect;
});
const backendOpponentDisconnectCountdownSeconds = computed(() => {
    if (!backendOpponentPresence.value) {
        return null;
    }

    if (backendOpponentPresence.value.presence_state === 'disconnected') {
        return 0;
    }

    return getRankedRemainingSeconds(
        backendOpponentPresence.value.last_seen_at,
        props.specSummary.disconnect_timeout_seconds,
        backendNowMs.value,
    ) ?? backendOpponentPresence.value.seconds_until_disconnect;
});
const backendReconnectCountdownSeconds = computed(() =>
    getRankedSecondsUntil(
        backendOpponentPresence.value?.reconnect_deadline_at ?? backendRealtimeProjection.value.reconnectDeadlineAt,
        backendNowMs.value,
    ) ?? backendOpponentPresence.value?.seconds_until_forfeit ?? null,
);

const backendPresenceLabel = (presenceState: string | null | undefined) => {
    const labels: Record<string, string> = {
        online: 'online',
        disconnected: 'offline',
    };

    return presenceState ? labels[presenceState] ?? presenceState : '—';
};

const backendPresenceChipClass = (presenceState: string | null | undefined) => {
    const classes: Record<string, string> = {
        online: 'bg-[#ecfdf3] text-[#0f7b43]',
        disconnected: 'bg-[#fff4da] text-[#986300]',
    };

    return presenceState ? classes[presenceState] ?? 'bg-[#f3f4f6] text-[#4b5563]' : 'bg-[#f3f4f6] text-[#4b5563]';
};

const backendPresenceBanner = computed(() => {
    const opponentPresence = backendOpponentPresence.value;

    if (!opponentPresence || opponentPresence.presence_state !== 'disconnected') {
        return null;
    }

    return {
        title: 'Przeciwnik wypadł z heartbeatów',
        body: `Reconnect window trwa jeszcze około ${backendReconnectCountdownSeconds.value ?? 0} sekund. Jeśli nie wróci, backend zamknie mecz walkowerem.`,
    };
});

const backendMatchInteractionLocked = computed(() => {
    const matchState = backendMatchDetails.value?.match.state ?? backendActiveMatch.value?.state ?? null;

    return ['matched', 'finished', 'abandoned', 'opponent_disconnected'].includes(matchState ?? '');
});

const backendTimedMatch = computed(() =>
    backendActiveMatch.value
    ?? backendMatchDetails.value?.match
    ?? backendResultMatch.value
    ?? null,
);

const backendMatchReady = computed(() => {
    if (
        backendMatchDetails.value
        && backendActiveMatch.value
        && backendMatchDetails.value.match.public_id === backendActiveMatch.value.public_id
    ) {
        return backendMatchDetails.value.match.ready;
    }

    return backendActiveMatch.value?.ready
        ?? backendTimedMatch.value?.ready
        ?? null;
});

const backendMatchCountdown = computed(() => {
    const match = backendTimedMatch.value;

    if (!match || match.status !== 'matched') {
        return null;
    }

    const projectionCountdownSeconds = typeof backendRealtimeProjection.value.countdownSeconds === 'number'
        && backendRealtimeProjection.value.countdownSeconds > 0
        ? backendRealtimeProjection.value.countdownSeconds
        : null;
    const countdownSeconds = projectionCountdownSeconds
        ?? (
            typeof match.countdown_seconds === 'number'
            && match.countdown_seconds > 0
            && match.countdown_started_at
                ? match.countdown_seconds
                : null
        );
    const countdownAnchor = match.countdown_started_at
        ?? (
            projectionCountdownSeconds
                ? backendRealtimeProjection.value.matchedAt
                : null
        );

    if (!countdownAnchor || !countdownSeconds) {
        return null;
    }

    const remainingSeconds = getRankedCountdownSeconds(
        countdownAnchor,
        countdownSeconds,
        backendNowMs.value,
    ) ?? countdownSeconds;

    return {
        totalSeconds: countdownSeconds,
        remainingSeconds,
        progressPercent: Math.min(
            ((countdownSeconds - remainingSeconds) / countdownSeconds) * 100,
            100,
        ),
    };
});

const backendMatchClock = computed<BackendClockCard | null>(() => {
    const match = backendTimedMatch.value;

    if (!match) {
        return null;
    }

    if (match.status === 'matched') {
        if (backendMatchCountdown.value) {
            const { remainingSeconds, progressPercent } = backendMatchCountdown.value;

            return {
                eyebrow: 'Start meczu',
                primary: formatRankedClock(remainingSeconds),
                secondary: 'Obie strony są gotowe. Startujemy równocześnie po wspólnym countdownie.',
                progressPercent,
                tone: 'text-[#155fa0]',
            };
        }

        const matchedSeconds = getRankedElapsedSeconds(
            match.matched_at ?? backendRealtimeProjection.value.matchedAt,
            backendNowMs.value,
        ) ?? 0;

        return {
            eyebrow: 'Ładowanie meczu',
            primary: formatRankedClock(matchedSeconds),
            secondary: backendMatchReady.value?.current_user_ready
                ? 'Twój ekran jest gotowy. Czekamy, aż przeciwnik załaduje pytania i potwierdzi gotowość.'
                : 'Ładujemy pytania i potwierdzamy gotowość Twojego ekranu przed startem meczu.',
            progressPercent: null,
            tone: 'text-[#155fa0]',
        };
    }

    if (match.started_at && ['in_progress', 'opponent_disconnected'].includes(match.state)) {
        const remainingSeconds = getRankedRemainingSeconds(
            match.started_at,
            match.duration_seconds,
            backendNowMs.value,
        ) ?? 0;
        const elapsedSeconds = getRankedElapsedSeconds(match.started_at, backendNowMs.value) ?? 0;
        const progressPercent = match.duration_seconds > 0
            ? Math.min((elapsedSeconds / match.duration_seconds) * 100, 100)
            : null;

        return {
            eyebrow: match.state === 'opponent_disconnected' ? 'Okno powrotu przeciwnika' : 'Pozostały czas',
            primary: formatRankedClock(remainingSeconds),
            secondary: match.state === 'opponent_disconnected'
                ? `Czekamy na powrót przeciwnika. Mecz trwa ${formatRankedClock(elapsedSeconds)} z ${formatRankedClock(match.duration_seconds)}.`
                : `Mecz trwa ${formatRankedClock(elapsedSeconds)} z ${formatRankedClock(match.duration_seconds)}.`,
            progressPercent,
            tone: match.state === 'opponent_disconnected' ? 'text-[#986300]' : 'text-[#0f7b43]',
        };
    }

    if (['finished', 'abandoned'].includes(match.status)) {
        const resolvedAt = match.finished_at ?? match.abandoned_at;
        const finalMatchDurationSeconds = getRankedDurationSeconds(
            match.started_at ?? match.matched_at,
            resolvedAt,
        );

        return {
            eyebrow: match.status === 'finished' ? 'Mecz zakończony' : 'Mecz przerwany',
            primary: formatRankedClock(finalMatchDurationSeconds ?? match.duration_seconds ?? 0),
            secondary: 'To jest finalny czas zakończonego meczu.',
            progressPercent: 100,
            tone: match.status === 'finished' ? 'text-[#0f7b43]' : 'text-[#a52323]',
        };
    }

    return null;
});

const backendMatchProgressPercent = computed(() =>
    backendMatchClock.value?.progressPercent ?? 0,
);

const shouldShowLiveShortcut = computed(() => Boolean(
    rankedOverview.value?.queue || backendActiveMatch.value,
));

const showLiveStatusPanel = computed(() => Boolean(
    backendError.value
    || rankedOverview.value?.queue
    || backendActiveMatch.value
));

const shouldShowBackendMainPanel = computed(() =>
    currentScreen.value !== 'lobby' && showLiveStatusPanel.value,
);

const pendingScreenNavigation = ref<RankedScreen | null>(null);

const buildRankingScreenRouteParams = (options: {
    stay?: 'lobby' | null;
} = {}) => {
    const params: Record<string, string> = {};

    if (options.stay) {
        params.stay = options.stay;
    }

    return params;
};

const rankingScreenRouteName = (screen: RankedScreen) => {
    if (screen === 'waiting') {
        return 'session.ranking.waiting';
    }

    if (screen === 'match') {
        return 'session.ranking.match';
    }

    if (screen === 'result') {
        return 'session.ranking.result';
    }

    return 'session.ranking';
};

const navigateToRankingScreen = (
    screen: RankedScreen,
    options: {
        replace?: boolean;
        preserveState?: boolean;
        stay?: 'lobby' | null;
    } = {},
) => {
    if (currentScreen.value === screen || pendingScreenNavigation.value === screen) {
        return;
    }

    pendingScreenNavigation.value = screen;
    router.get(route(rankingScreenRouteName(screen), buildRankingScreenRouteParams({
        stay: options.stay ?? null,
    })), {}, {
        preserveState: options.preserveState ?? true,
        preserveScroll: true,
        replace: options.replace ?? false,
        onFinish: () => {
            pendingScreenNavigation.value = null;
        },
    });
};

const primaryRankingActionLabel = computed(() => {
    if (queueLeavePending.value) {
        return 'Wracamy do startu...';
    }

    if (backendBusy.value) {
        return rankedOverview.value?.queue
            ? 'Anulowanie...'
            : 'Uruchamianie rankingu...';
    }

    if (rankedOverview.value?.queue?.status === 'server_full') {
        return 'Anuluj oczekiwanie';
    }

    if (rankedOverview.value?.queue?.status === 'queued') {
        return 'Anuluj szukanie';
    }

    if (rankedOverview.value?.queue?.status === 'matched') {
        return 'Przeciwnik znaleziony';
    }

    if (backendActiveMatch.value) {
        if (backendActiveMatch.value.status === 'matched') {
            return backendMatchCountdown.value
                ? 'Start za moment'
                : 'Ładowanie meczu...';
        }

        if (['finished', 'abandoned'].includes(backendActiveMatch.value.status)) {
            return 'Pokaż wynik';
        }

        return 'Wróć do meczu';
    }

    return 'Start ranking';
});

const primaryRankingActionShowsSpinner = computed(() =>
    queueLeavePending.value
    || backendBusy.value
    || rankedOverview.value?.queue?.status === 'queued'
    || rankedOverview.value?.queue?.status === 'server_full'
    || rankedOverview.value?.queue?.status === 'matched'
    || backendActiveMatch.value?.status === 'matched',
);

const inlineLobbyError = computed(() => backendError.value);

const waitingScreenTitle = computed(() => {
    if (waitingScreenExitPending.value) {
        return 'Wracamy do startu';
    }

    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendMatchCountdown.value
            ? 'Start za moment'
            : 'Ładowanie meczu';
    }

    if (rankedOverview.value?.queue?.status === 'server_full') {
        return 'Czekasz na wolny slot';
    }

    if (rankedOverview.value?.queue?.status === 'queued') {
        return 'Szukamy przeciwnika';
    }

    return selectedCategory.value
        ? 'Szukamy przeciwnika'
        : 'Łączymy z rankingiem';
});

const waitingScreenClock = computed(() => {
    if (rankedOverview.value?.queue?.status === 'queued' || rankedOverview.value?.queue?.status === 'server_full') {
        return backendQueueClock.value;
    }

    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendMatchClock.value ?? backendQueueClock.value;
    }

    return backendQueueClock.value ?? backendMatchClock.value ?? null;
});

const waitingScreenTimer = computed(() =>
    {
        if (waitingScreenExitPending.value) {
            return '...';
        }

        if (rankedOverview.value?.queue?.status === 'queued' && backendQueueTimeoutRemainingSeconds.value !== null) {
            return String(backendQueueTimeoutRemainingSeconds.value);
        }

        if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
            if (backendMatchCountdown.value) {
                return String(backendMatchCountdown.value.remainingSeconds);
            }

            return '...';
        }

        if (rankedOverview.value?.queue?.status === 'matched') {
            return '...';
        }

        if (!rankedOverview.value?.queue && !backendActiveMatch.value) {
            return '0';
        }

        return waitingScreenClock.value?.primary ?? '00:00';
    },
);

const waitingScreenSubtitle = computed(() => {
    if (waitingScreenExitPending.value) {
        return 'Opuszczamy kolejkę i wracamy do ekranu startowego.';
    }

    if (backendError.value) {
        return backendError.value;
    }

    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendMatchClock.value?.secondary
            ?? (
                backendMatchReady.value?.current_user_ready
                    ? `Twój ekran jest gotowy. Czekamy na ${backendOpponent.value?.username ?? 'drugiego gracza'}.`
                    : `Ładujemy planszę pytań dla meczu z ${backendOpponent.value?.username ?? 'przeciwnikiem'}.`
            );
    }

    if (rankedOverview.value?.queue?.status === 'server_full') {
        return backendServerFullBanner.value?.body
            ?? 'System chwilowo nie ma wolnego slotu. Zachowujemy Twoje miejsce.';
    }

    if (rankedOverview.value?.queue?.status === 'queued') {
        return waitingScreenClock.value?.secondary
            ?? 'Dobieramy przeciwnika.';
    }

    return selectedCategory.value
        ? 'Za chwilę pokażemy status wyszukiwania przeciwnika.'
        : 'Łączymy z matchmakingiem.';
});

const waitingScreenMeta = computed(() => {
    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendOpponent.value?.username
            ? `Grasz z ${backendOpponent.value.username}`
            : 'Przygotowujemy pojedynek 1 na 1';
    }

    return 'Dobieramy przeciwnika automatycznie';
});

const waitingScreenBadge = computed(() => {
    if (waitingScreenExitPending.value) {
        return 'Kończymy wyszukiwanie';
    }

    if (rankedOverview.value?.queue?.status === 'server_full') {
        return 'Brak wolnego slotu';
    }

    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendMatchCountdown.value ? 'Start za moment' : 'Ładowanie meczu';
    }

    return 'Matchmaking';
});

const waitingScreenTimerLabel = computed(() => {
    if (waitingScreenExitPending.value) {
        return 'Chwila';
    }

    if (backendActiveMatch.value?.status === 'matched' || backendActiveMatch.value?.state === 'matched') {
        return backendMatchCountdown.value ? 'Start za' : 'Przygotowanie';
    }

    if (rankedOverview.value?.queue?.status === 'matched') {
        return 'Przygotowanie';
    }

    if (rankedOverview.value?.queue?.status === 'queued') {
        return 'Pozostało';
    }

    return 'Status';
});

const waitingScreenCanExit = computed(() =>
    Boolean(rankedOverview.value?.queue && !backendActiveMatch.value && !waitingScreenExitPending.value),
);

const handlePrimaryRankingAction = async () => {
    if (rankedOverview.value?.queue && !backendActiveMatch.value) {
        await leaveRealQueue();

        return;
    }

    if (shouldShowLiveShortcut.value) {
        navigateToRankingScreen(
            backendActiveMatch.value?.status === 'matched'
                ? 'waiting'
                : (backendActiveMatch.value ? 'match' : 'waiting'),
        );

        return;
    }

    await joinRealQueue();
};

const backendProjectionPhaseLabel = computed(() => {
    const labels: Record<string, string> = {
        idle: 'Gotowy',
        queueing: 'Kolejka',
        server_full: 'Brak slotu',
        matched: 'Sparowano',
        in_progress: 'Mecz trwa',
        opponent_disconnected: 'Okno powrotu',
        finished: 'Zakończony',
        abandoned: 'Walkower',
    };

    return labels[backendRealtimeProjection.value.phase] ?? backendRealtimeProjection.value.phase;
});

const backendTransportLabel = computed(() => {
    if (backendStreamState.value === 'open') {
        return backendLiveTransportKind.value === 'websocket'
            ? 'Live WebSocket'
            : 'Live SSE';
    }

    if (backendStreamState.value === 'connecting') {
        return props.realtimeTransport.preferred === 'websocket'
            ? 'Łączenie live'
            : 'Przywracanie live';
    }

    if (backendStreamState.value === 'unsupported') {
        return 'Odświeżanie cykliczne';
    }

    return 'Fallback odświeżania';
});

const backendTransportChipClass = computed(() => {
    const classes: Record<typeof backendStreamState.value, string> = {
        unsupported: 'bg-[#f3f4f6] text-[#4b5563]',
        connecting: 'bg-[#fff4da] text-[#986300]',
        open: 'bg-[#ecfdf3] text-[#0f7b43]',
        error: 'bg-[#fdecec] text-[#a52323]',
    };

    return classes[backendStreamState.value];
});
</script>

<template>
    <Head title="Tryb rankingowy 1v1" />

    <SessionExpiredNotice v-if="sessionExpired" />

    <AuthenticatedLayout>
        <template v-if="currentScreen === 'waiting'">
            <section class="flex min-h-[100svh] flex-col bg-[#f7f8fa] px-4 pb-[calc(1.5rem+env(safe-area-inset-bottom))] pt-[calc(1.25rem+env(safe-area-inset-top))] text-[#17191d] md:hidden">
                <header>
                    <p class="text-[0.78rem] font-semibold text-[#667085]">Ranking 1 na 1</p>
                    <h1 class="mt-1 text-[1.72rem] font-bold leading-8">{{ waitingScreenBadge }}</h1>
                </header>

                <main class="flex flex-1 flex-col items-center justify-center pb-12 text-center">
                    <div class="grid h-14 w-14 place-items-center rounded-full border border-[#b9d6fb] bg-[#eaf3ff]">
                        <span class="h-3 w-3 rounded-full bg-[#0b63ce] animate-pulse" aria-hidden="true" />
                    </div>
                    <p class="mt-7 text-[0.78rem] font-semibold text-[#667085]">{{ waitingScreenTimerLabel }}</p>
                    <p class="mt-2 text-[3.2rem] font-bold leading-none tabular-nums text-[#17191d]">{{ waitingScreenTimer }}</p>
                    <h2 class="mt-6 text-[1.28rem] font-bold leading-7 text-[#17191d]">{{ waitingScreenTitle }}</h2>
                    <p class="mt-3 max-w-[20rem] text-[0.92rem] leading-6 text-[#667085]">{{ waitingScreenSubtitle }}</p>
                    <p class="mt-4 text-[0.8rem] font-medium text-[#98a2b3]">{{ waitingScreenMeta }}</p>
                </main>

                <div class="border-t border-[#dfe3e8] pt-4">
                    <button
                        v-if="waitingScreenCanExit"
                        type="button"
                        class="flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#17191d] px-4 text-[0.96rem] font-semibold text-white transition hover:bg-[#344054] disabled:cursor-not-allowed disabled:opacity-55"
                        :disabled="backendBusy"
                        @click="leaveRealQueue"
                    >
                        Anuluj wyszukiwanie
                    </button>
                    <Link
                        v-else
                        :href="route('session.ranking', buildRankingScreenRouteParams({ stay: 'lobby' }))"
                        class="flex min-h-12 items-center justify-center rounded-[8px] bg-[#17191d] px-4 text-[0.96rem] font-semibold text-white"
                    >
                        Wróć do rankingu
                    </Link>
                    <button
                        v-if="!waitingScreenExitPending"
                        type="button"
                        class="mt-2 flex min-h-10 w-full items-center justify-center text-[0.86rem] font-semibold text-[#0b63ce]"
                        :disabled="backendBusy"
                        @click="fetchRankedOverview()"
                    >
                        Odśwież stan
                    </button>
                </div>
            </section>

            <div class="mx-auto hidden min-h-[72vh] w-full max-w-[92rem] items-center justify-center px-4 py-8 sm:px-6 md:flex lg:px-8">
                <section class="w-full border border-[#d8d8dd] bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#ececec] px-6 py-4 sm:px-8">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]">
                            {{ waitingScreenBadge }}
                        </p>
                        <p class="text-[0.92rem] text-[#71717a]">
                            {{ waitingScreenMeta }}
                        </p>
                    </div>

                    <div class="px-6 py-10 text-center sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                        <h1 class="mx-auto max-w-4xl text-[2.35rem] font-semibold tracking-[-0.06em] text-[#020309] sm:text-[3.4rem]">
                            {{ waitingScreenTitle }}
                        </h1>

                        <div class="mt-6 flex items-center justify-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#17161b] animate-pulse"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#17161b]/65 animate-pulse [animation-delay:160ms]"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#17161b]/35 animate-pulse [animation-delay:320ms]"></span>
                        </div>

                        <div class="mx-auto mt-9 max-w-3xl">
                            <p class="text-[0.76rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]">
                                {{ waitingScreenTimerLabel }}
                            </p>
                            <p class="mt-4 text-[4.4rem] font-semibold tracking-[-0.08em] text-[#020309] sm:text-[6rem]">
                                {{ waitingScreenTimer }}
                            </p>
                            <p class="mx-auto mt-4 max-w-2xl text-[0.98rem] leading-7 text-[#71717a] sm:text-[1.02rem]">
                                {{ waitingScreenSubtitle }}
                            </p>
                        </div>

                        <div
                            v-if="rankedOverview?.queue?.status === 'server_full' && backendServerFullBanner?.meta"
                            class="mx-auto mt-8 max-w-2xl border border-[#f0d4c8] bg-[#fcf5f1] px-5 py-4 text-[0.9rem] leading-7 text-[#7f2e1a]"
                        >
                            {{ backendServerFullBanner.meta }}
                        </div>

                        <div
                            v-if="!waitingScreenExitPending"
                            class="mt-10 flex flex-wrap items-center justify-center gap-3"
                        >
                            <button
                                type="button"
                                class="inline-flex min-h-[3.1rem] items-center justify-center border border-[#d8d8dd] bg-white px-6 text-[0.92rem] font-semibold text-[#17161b] transition hover:border-[#bfc3c9] hover:bg-[#fbfbfc] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="backendBusy"
                                @click="fetchRankedOverview()"
                            >
                                Odśwież
                            </button>
                            <button
                                v-if="waitingScreenCanExit"
                                type="button"
                                class="inline-flex min-h-[3.1rem] items-center justify-center bg-[#f58220] px-6 text-[0.92rem] font-semibold text-white transition hover:bg-[#d86f17] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="backendBusy"
                                @click="leaveRealQueue"
                            >
                                Anuluj
                            </button>
                            <Link
                                v-else
                                :href="route('session.ranking', buildRankingScreenRouteParams({ stay: 'lobby' }))"
                                class="inline-flex min-h-[3.1rem] items-center justify-center bg-[#f58220] px-6 text-[0.92rem] font-semibold text-white transition hover:bg-[#d86f17]"
                            >
                                Wróć do startu
                            </Link>
                        </div>

                        <p
                            v-else
                            class="mt-10 text-[0.92rem] leading-7 text-[#71717a]"
                        >
                            Wracamy do lobby.
                        </p>
                    </div>
                </section>
            </div>
        </template>

        <template v-else-if="currentScreen === 'match'">
            <section class="min-h-[100svh] bg-[#f7f8fa] text-[#17191d] md:hidden">
                <header class="border-b border-[#dfe3e8] bg-white px-4 pb-4 pt-[calc(1rem+env(safe-area-inset-top))]">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[0.76rem] font-semibold text-[#667085]">Mecz rankingowy</p>
                            <p class="mt-1 truncate text-[1rem] font-semibold text-[#17191d]">{{ backendOpponent?.username ?? 'Łączenie z przeciwnikiem' }}</p>
                        </div>
                        <button
                            type="button"
                            class="min-h-10 shrink-0 px-2 text-[0.82rem] font-semibold text-[#b42318] disabled:cursor-not-allowed disabled:opacity-55"
                            :disabled="backendBusy"
                            @click="abandonActiveMatch"
                        >
                            Poddaj
                        </button>
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-4">
                        <p class="text-[0.82rem] font-semibold tabular-nums text-[#475467]">
                            {{ backendQuestionProgress.answered }}/{{ backendQuestionProgress.total }}
                        </p>
                        <p v-if="backendMatchClock" class="text-[1.15rem] font-bold tabular-nums" :class="backendMatchClock.tone">
                            {{ backendMatchClock.primary }}
                        </p>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#e8edf3]">
                        <div
                            class="h-full rounded-full bg-[#0b63ce] transition-[width] duration-300"
                            :style="{ width: `${backendQuestionProgress.total > 0 ? (backendQuestionProgress.answered / backendQuestionProgress.total) * 100 : 0}%` }"
                        />
                    </div>
                </header>

                <main class="mx-auto w-full max-w-xl px-4 pb-[calc(1.5rem+env(safe-area-inset-bottom))] pt-6">
                    <div v-if="backendPresenceBanner" class="mb-4 border-y border-[#f7dca8] bg-[#fff9e9] px-4 py-3 text-[0.84rem] leading-5 text-[#7f6327]">
                        <p class="font-semibold text-[#9b6a00]">{{ backendPresenceBanner.title }}</p>
                        <p class="mt-1">{{ backendPresenceBanner.body }}</p>
                    </div>
                    <p v-if="backendMatchError" class="mb-4 border-y border-[#f5c2c7] bg-[#fff6f7] px-4 py-3 text-[0.84rem] leading-5 text-[#b42318]">
                        {{ backendMatchError }}
                    </p>

                    <section v-if="backendSelectedQuestion" :key="backendSelectedQuestion.id">
                        <p class="text-[0.78rem] font-semibold text-[#667085]">Pytanie {{ backendSelectedQuestion.question_number }}</p>
                        <h1 class="mt-2 text-[1.25rem] font-bold leading-7 text-[#17191d]">{{ backendSelectedQuestion.question_text }}</h1>

                        <div class="mt-6 grid gap-3">
                            <button
                                v-for="(label, key) in backendSelectedQuestion.answers"
                                :key="key"
                                type="button"
                                class="flex min-h-[4.25rem] items-center gap-3 rounded-[8px] border px-4 py-3 text-left text-[0.92rem] font-medium leading-5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-default"
                                :class="backendSelectedQuestion.selected_answer === key ? 'border-[#0b63ce] bg-[#eaf3ff] text-[#074b9f]' : 'border-[#dfe3e8] bg-white text-[#17191d]'"
                                :disabled="backendMatchBusy || backendSelectedQuestion.is_answered || backendMatchInteractionLocked"
                                @click="submitBackendAnswer(backendSelectedQuestion.id, key)"
                            >
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border border-current/20 text-[0.74rem] font-semibold">{{ key }}</span>
                                <span class="min-w-0 flex-1">{{ label }}</span>
                            </button>
                        </div>

                        <p v-if="backendSelectedQuestion.is_answered" class="mt-5 border-y border-[#dfe3e8] bg-white px-4 py-3 text-[0.84rem] leading-5 text-[#475467]">
                            Twoja odpowiedź: {{ backendSelectedQuestion.selected_answer }}
                        </p>
                        <p v-else-if="backendMatchInteractionLocked" class="mt-5 border-y border-[#f7dca8] bg-[#fff9e9] px-4 py-3 text-[0.84rem] leading-5 text-[#7f6327]">
                            Odpowiedzi są chwilowo zablokowane.
                        </p>
                    </section>

                    <div v-else class="flex min-h-[45svh] flex-col items-center justify-center text-center">
                        <span class="h-3 w-3 rounded-full bg-[#0b63ce] animate-pulse" aria-hidden="true" />
                        <p class="mt-4 text-[0.92rem] text-[#667085]">Ładujemy pytanie meczu.</p>
                    </div>
                </main>
            </section>

            <div class="hidden space-y-6 md:block">
                <section class="overflow-hidden rounded-[1.6rem] bg-[#11161f] px-5 py-6 text-white shadow-[0_28px_70px_rgba(15,23,42,0.24)] sm:px-7 sm:py-7">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div>
                            <p class="text-[0.76rem] font-semibold uppercase tracking-[0.18em] text-white/48">
                                Mecz rankingowy
                            </p>
                            <h1 class="mt-2 text-[1.7rem] font-semibold tracking-[-0.05em] text-white sm:text-[2.2rem]">
                                {{ backendMatchDetails?.match.public_id ?? backendActiveMatch?.public_id ?? 'Trwa ładowanie meczu' }}
                            </h1>
                            <p class="mt-2 text-[0.95rem] leading-7 text-white/68">
                                {{ backendOpponent?.username ? `Przeciwnik: ${backendOpponent.username}` : 'Przygotowujemy planszę pytań.' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div
                                v-if="backendMatchClock"
                                class="rounded-[1rem] border border-white/10 bg-white/8 px-4 py-3 text-right"
                            >
                                <p class="text-[0.72rem] uppercase tracking-[0.14em] text-white/48">
                                    {{ backendMatchClock.eyebrow }}
                                </p>
                                <p class="mt-1 text-[1.35rem] font-semibold tracking-[-0.04em]" :class="backendMatchClock.tone">
                                    {{ backendMatchClock.primary }}
                                </p>
                            </div>
                            <div
                                v-if="backendMatchDetails"
                                class="rounded-[1rem] border border-white/10 bg-white/8 px-4 py-3"
                            >
                                <p class="text-[0.72rem] uppercase tracking-[0.14em] text-white/48">
                                    Postęp
                                </p>
                                <p class="mt-1 text-[1.35rem] font-semibold tracking-[-0.04em] text-white">
                                    {{ backendQuestionProgress.answered }}/{{ backendQuestionProgress.total }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex min-h-[2.7rem] items-center justify-center rounded-[0.95rem] border border-white/12 bg-white/8 px-4 text-[0.84rem] font-semibold text-white transition hover:bg-white/12"
                                :disabled="backendBusy"
                                @click="abandonActiveMatch"
                            >
                                {{ backendBusy ? 'Poddajemy mecz...' : 'Poddaj mecz' }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="backendMatchClock?.progressPercent !== null"
                        class="mt-5 h-2.5 overflow-hidden rounded-full bg-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-[linear-gradient(90deg,#f58220,#f2b766)] transition-[width] duration-700"
                            :style="{ width: `${backendMatchProgressPercent}%` }"
                        ></div>
                    </div>
                </section>

                <div
                    v-if="backendPresenceBanner"
                    class="rounded-[1rem] border border-[#f7dca8] bg-[#fff9e9] px-4 py-4 text-[0.9rem] leading-7 text-[#7f6327]"
                >
                    <p class="font-semibold text-[#9b6a00]">
                        {{ backendPresenceBanner.title }}
                    </p>
                    <p class="mt-1">
                        {{ backendPresenceBanner.body }}
                    </p>
                </div>

                <div
                    v-if="backendMatchError"
                    class="rounded-[1rem] border border-[#ffd0c3] bg-[#fff4f1] px-4 py-4 text-[0.9rem] leading-7 text-[#7f3a2b]"
                >
                    {{ backendMatchError }}
                </div>

                <section
                    v-if="backendMatchDetails"
                    class="grid gap-6 xl:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)]"
                >
                    <div class="space-y-4">
                        <article class="rounded-[1.25rem] border border-[#e9e1d4] bg-white p-5 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.15em] text-[#8c7a67]">
                                        Plansza pytań
                                    </p>
                                    <p class="mt-2 text-[0.95rem] font-semibold text-[#17161b]">
                                        {{ backendQuestionProgress.answered }}/{{ backendQuestionProgress.total }} odpowiedzi
                                    </p>
                                </div>
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[0.74rem] font-semibold"
                                    :class="backendStateClass(backendMatchDetails.match.state)"
                                >
                                    {{ backendStateHumanLabel(backendMatchDetails.match.state) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-8">
                                <div
                                    v-for="question in backendQuestions"
                                    :key="question.id"
                                    class="inline-flex min-h-[2.8rem] items-center justify-center rounded-[0.85rem] border text-[0.82rem] font-semibold transition"
                                    :class="
                                        question.id === backendResolvedQuestionId
                                            ? 'border-[#17161b] bg-[#17161b] text-white shadow-[0_10px_22px_rgba(23,22,27,0.14)]'
                                            : question.is_answered
                                                ? 'border-[#cae9d7] bg-[#ecfdf3] text-[#0f7b43]'
                                                : 'border-[#ddd1c4] bg-[#faf8f4] text-[#2f2921]'
                                    "
                                >
                                    {{ question.question_number }}
                                </div>
                            </div>
                        </article>

                    </div>

                    <article class="rounded-[1.25rem] border border-[#e9e1d4] bg-white p-5 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                        <div v-if="backendSelectedQuestion">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#8c7a67]">
                                        Pytanie {{ backendSelectedQuestion.question_number }}
                                    </p>
                                    <p class="mt-2 text-[1rem] leading-7 text-[#201d1a]">
                                        {{ backendSelectedQuestion.question_text }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-[0.74rem] font-semibold"
                                    :class="backendSelectedQuestion.is_answered ? 'bg-[#ecfdf3] text-[#0f7b43]' : 'bg-[#f3f4f6] text-[#4b5563]'"
                                >
                                    {{ backendSelectedQuestion.is_answered ? 'odpowiedziane' : 'otwarte' }}
                                </span>
                            </div>

                            <div class="mt-5 grid gap-2.5 sm:grid-cols-2">
                                <button
                                    v-for="(label, key) in backendSelectedQuestion.answers"
                                    :key="key"
                                    type="button"
                                    class="inline-flex min-h-[3.25rem] items-center justify-between rounded-[0.95rem] border px-4 py-3 text-left text-[0.88rem] font-medium transition"
                                    :class="
                                        backendSelectedQuestion.selected_answer === key
                                            ? 'border-[#17161b] bg-[#17161b] text-white'
                                            : 'border-[#ddd1c4] bg-[#faf8f4] text-[#2f2921] hover:border-[#c7b5a0] hover:bg-white'
                                    "
                                    :disabled="backendMatchBusy || backendSelectedQuestion.is_answered || backendMatchInteractionLocked"
                                    @click="submitBackendAnswer(backendSelectedQuestion.id, key)"
                                >
                                    <span class="mr-3 inline-flex min-h-[2rem] min-w-[2rem] items-center justify-center rounded-full border border-current/20 text-[0.76rem] font-semibold">
                                        {{ key }}
                                    </span>
                                    <span class="flex-1 leading-6">{{ label }}</span>
                                </button>
                            </div>

                            <p class="mt-5 rounded-[0.95rem] border border-[#ece3d7] bg-[#faf8f4] px-4 py-3 text-[0.84rem] leading-7 text-[#6f665c]">
                                Odpowiadasz pytanie po pytaniu. Po jednym kliknięciu system sam przejdzie do następnego.
                            </p>

                            <p
                                v-if="backendSelectedQuestion.is_answered"
                                class="mt-4 text-[0.84rem] leading-7 text-[#6f665c]"
                            >
                                Twoja odpowiedź: {{ backendSelectedQuestion.selected_answer }}
                                <span v-if="['finished', 'abandoned'].includes(backendMatchDetails.match.status) && backendSelectedQuestion.correct_answer">
                                    / poprawna: {{ backendSelectedQuestion.correct_answer }}
                                </span>
                            </p>

                            <p
                                v-else-if="backendMatchInteractionLocked"
                                class="mt-4 text-[0.84rem] leading-7 text-[#6f665c]"
                            >
                                Odpowiedzi są chwilowo zablokowane, bo mecz czeka na reconnect albo został zamknięty.
                            </p>
                        </div>

                        <div
                            v-else
                            class="rounded-[1rem] border border-dashed border-[#ddd1c4] bg-[#fcfbf8] px-4 py-6 text-[0.9rem] leading-7 text-[#6f665c]"
                        >
                            Ładujemy bieżące pytanie.
                        </div>
                    </article>
                </section>

                <section
                    v-else
                    class="rounded-[1.25rem] border border-[#e9e1d4] bg-white p-8 text-center shadow-[0_18px_42px_rgba(15,23,42,0.05)]"
                >
                    <p class="text-[0.92rem] leading-7 text-[#6f665c]">
                        Trwa ładowanie pytań meczu.
                    </p>
                    <div class="mt-5 flex items-center justify-center gap-3">
                        <span class="h-3 w-3 rounded-full bg-[#17161b] animate-pulse"></span>
                        <span class="h-3 w-3 rounded-full bg-[#17161b]/70 animate-pulse [animation-delay:160ms]"></span>
                        <span class="h-3 w-3 rounded-full bg-[#17161b]/45 animate-pulse [animation-delay:320ms]"></span>
                    </div>
                </section>
            </div>
        </template>

        <template v-else-if="currentScreen === 'result'">
            <section class="min-h-screen bg-[#f7f8fa] pb-[calc(5.5rem+env(safe-area-inset-bottom))] text-[#17191d] md:hidden">
                <header class="border-b border-[#dfe3e8] bg-white px-4 pb-5 pt-[calc(1.25rem+env(safe-area-inset-top))]">
                    <p class="text-[0.78rem] font-semibold text-[#667085]">Ranking 1 na 1</p>
                    <h1 class="mt-1 text-[1.72rem] font-bold leading-8">{{ backendLastResultCard?.resultLabel ?? 'Mecz zakończony' }}</h1>
                </header>

                <section v-if="backendResultParticipants" class="px-4 py-5">
                    <div class="border-y border-[#dfe3e8] bg-white">
                        <article class="flex items-center justify-between gap-4 px-4 py-4">
                            <div class="min-w-0">
                                <p class="truncate text-[1rem] font-semibold text-[#17191d]">{{ backendResultParticipants.current.username }}</p>
                                <p class="mt-1 text-[0.8rem] font-medium" :class="backendResultParticipants.current.outcomeClass">{{ backendResultParticipants.current.outcomeLabel }}</p>
                            </div>
                            <p class="text-[2rem] font-bold tabular-nums text-[#17191d]">{{ backendResultParticipants.current.points }}</p>
                        </article>
                        <article class="flex items-center justify-between gap-4 border-t border-[#e3e7eb] px-4 py-4">
                            <div class="min-w-0">
                                <p class="truncate text-[1rem] font-semibold text-[#17191d]">{{ backendResultParticipants.opponent.username }}</p>
                                <p class="mt-1 text-[0.8rem] font-medium" :class="backendResultParticipants.opponent.outcomeClass">{{ backendResultParticipants.opponent.outcomeLabel }}</p>
                            </div>
                            <p class="text-[2rem] font-bold tabular-nums text-[#17191d]">{{ backendResultParticipants.opponent.points }}</p>
                        </article>
                    </div>

                    <section class="mt-5" aria-labelledby="ranking-result-details-heading">
                        <h2 id="ranking-result-details-heading" class="text-[0.82rem] font-semibold text-[#667085]">Twój wynik</h2>
                        <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                            <div class="flex min-h-14 items-center justify-between px-4 py-3">
                                <span class="text-[0.92rem] font-medium">Poprawne odpowiedzi</span>
                                <span class="text-[0.92rem] font-semibold tabular-nums">{{ backendResultParticipants.current.correctAnswers }}/{{ backendResultParticipants.current.totalQuestions }}</span>
                            </div>
                            <div class="flex min-h-14 items-center justify-between px-4 py-3">
                                <span class="text-[0.92rem] font-medium">Zmiana ELO</span>
                                <span class="text-[0.92rem] font-semibold tabular-nums" :class="(backendResultParticipants.current.eloChange ?? 0) >= 0 ? 'text-[#157347]' : 'text-[#b42318]'">{{ formatRankedResultSignedValue(backendResultParticipants.current.eloChange) }}</span>
                            </div>
                        </div>
                    </section>

                    <Link
                        :href="route('session.ranking', buildRankingScreenRouteParams())"
                        class="mt-5 flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#0b63ce] px-4 text-[0.96rem] font-semibold text-white"
                    >
                        Wróć do rankingu
                    </Link>
                </section>

                <div v-else class="flex min-h-[60svh] flex-col items-center justify-center px-4 text-center">
                    <span class="h-3 w-3 rounded-full bg-[#0b63ce] animate-pulse" aria-hidden="true" />
                    <p class="mt-4 text-[0.92rem] text-[#667085]">Ładujemy wynik meczu.</p>
                </div>
            </section>

            <div class="mx-auto hidden min-h-[56vh] w-full max-w-[60rem] items-center justify-center px-4 py-4 sm:px-6 md:flex lg:px-8">
                <section class="w-full border border-[#d8d8dd] bg-white">
                    <template v-if="backendLastResultCard && backendResultParticipants">
                        <div class="px-5 py-6 sm:px-6 sm:py-7">
                            <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] md:divide-x md:divide-[#eceef2] md:gap-0">
                                <article class="flex w-full justify-center px-3 text-center md:px-8">
                                    <div class="flex w-full max-w-[16rem] flex-col items-center">
                                    <div
                                        class="relative h-24 w-24 shrink-0 overflow-hidden rounded-full border bg-[#f3f4f6]"
                                        :class="backendResultParticipants.current.avatarFrameClass"
                                    >
                                        <img
                                            :src="rankingHeroDriverImage"
                                            :alt="backendResultParticipants.current.username"
                                            class="h-full w-full"
                                            :class="backendResultParticipants.current.avatarImageClass"
                                        >
                                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#020309]/12 via-transparent to-transparent"></div>
                                    </div>

                                    <h2 class="mt-3 text-[1.65rem] font-semibold tracking-[-0.05em] text-[#17161b] sm:text-[1.9rem]">
                                        {{ backendResultParticipants.current.username }}
                                    </h2>
                                    <span
                                        class="mt-2 inline-flex px-3 py-1 text-[0.72rem] font-semibold"
                                        :class="backendResultParticipants.current.outcomeClass"
                                    >
                                        {{ backendResultParticipants.current.outcomeLabel }}
                                    </span>
                                    <div class="mt-5 w-full border-t border-[#eceef2]">
                                        <div class="py-3">
                                            <p class="text-[1.45rem] font-semibold tracking-[-0.05em] text-[#17161b]">
                                                {{ backendResultParticipants.current.correctAnswers }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                poprawne odpowiedzi
                                            </p>
                                        </div>
                                        <div class="border-t border-[#eceef2] py-3">
                                            <p class="text-[1.05rem] font-semibold tracking-[-0.04em] text-[#17161b]">
                                                {{ formatRankedResultTime(backendResultParticipants.current.responseTimeMs) }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                czas odpowiedzi
                                            </p>
                                        </div>
                                        <div class="border-t border-[#eceef2] py-3">
                                            <p class="text-[1.05rem] font-semibold tracking-[-0.04em] text-[#17161b]">
                                                {{ formatRankedResultSignedValue(backendResultParticipants.current.eloChange) }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                zmiana ELO
                                            </p>
                                        </div>
                                    </div>
                                    </div>
                                </article>

                                <article class="flex w-full justify-center border-t border-[#eceef2] px-3 pt-6 text-center md:border-t-0 md:px-8 md:pt-0">
                                    <div class="flex w-full max-w-[16rem] flex-col items-center">
                                    <div
                                        class="relative h-24 w-24 shrink-0 overflow-hidden rounded-full border bg-[#f3f4f6]"
                                        :class="backendResultParticipants.opponent.avatarFrameClass"
                                    >
                                        <img
                                            :src="rankingHeroDriverImage"
                                            :alt="backendResultParticipants.opponent.username"
                                            class="h-full w-full"
                                            :class="backendResultParticipants.opponent.avatarImageClass"
                                        >
                                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#020309]/12 via-transparent to-transparent"></div>
                                    </div>

                                    <h2 class="mt-3 text-[1.65rem] font-semibold tracking-[-0.05em] text-[#17161b] sm:text-[1.9rem]">
                                        {{ backendResultParticipants.opponent.username }}
                                    </h2>
                                    <span
                                        class="mt-2 inline-flex px-3 py-1 text-[0.72rem] font-semibold"
                                        :class="backendResultParticipants.opponent.outcomeClass"
                                    >
                                        {{ backendResultParticipants.opponent.outcomeLabel }}
                                    </span>

                                    <div class="mt-5 w-full border-t border-[#eceef2]">
                                        <div class="py-3">
                                            <p class="text-[1.45rem] font-semibold tracking-[-0.05em] text-[#17161b]">
                                                {{ backendResultParticipants.opponent.correctAnswers }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                poprawne odpowiedzi
                                            </p>
                                        </div>
                                        <div class="border-t border-[#eceef2] py-3">
                                            <p class="text-[1.05rem] font-semibold tracking-[-0.04em] text-[#17161b]">
                                                {{ formatRankedResultTime(backendResultParticipants.opponent.responseTimeMs) }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                czas odpowiedzi
                                            </p>
                                        </div>
                                        <div class="border-t border-[#eceef2] py-3">
                                            <p class="text-[1.05rem] font-semibold tracking-[-0.04em] text-[#17161b]">
                                                {{ formatRankedResultSignedValue(backendResultParticipants.opponent.eloChange) }}
                                            </p>
                                            <p class="mt-1 text-[0.78rem] uppercase tracking-[0.16em] text-[#8b9098]">
                                                zmiana ELO
                                            </p>
                                        </div>
                                    </div>
                                    </div>
                                </article>
                            </div>

                            <div class="mt-6 flex justify-center border-t border-[#eceef2] pt-5">
                                <Link
                                    :href="route('session.ranking', buildRankingScreenRouteParams())"
                                    class="inline-flex min-h-[3.1rem] items-center justify-center bg-[#f58220] px-6 text-[0.92rem] font-semibold text-white transition hover:bg-[#d86f17]"
                                >
                                    Wróć do rankingu
                                </Link>
                            </div>
                        </div>
                    </template>

                    <div
                        v-else
                        class="px-6 py-12 text-center sm:px-8"
                    >
                        <p class="text-[0.95rem] leading-7 text-[#6b7280]">
                            Trwa ładowanie końcowego podsumowania meczu.
                        </p>
                        <div class="mt-5 flex items-center justify-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-[#17161b] animate-pulse"></span>
                            <span class="h-3 w-3 rounded-full bg-[#17161b]/70 animate-pulse [animation-delay:160ms]"></span>
                            <span class="h-3 w-3 rounded-full bg-[#17161b]/45 animate-pulse [animation-delay:320ms]"></span>
                        </div>
                    </div>
                </section>
            </div>
        </template>

        <template v-else>
        <section class="min-h-screen bg-[#f7f8fa] pb-[calc(5.5rem+env(safe-area-inset-bottom))] text-[#17191d] md:hidden">
            <header class="px-4 pb-5 pt-[calc(1.25rem+env(safe-area-inset-top))]">
                <p class="text-[0.78rem] font-semibold text-[#667085]">Pojedynek 1 na 1</p>
                <h1 class="mt-1 text-[1.72rem] font-bold leading-8 text-[#17191d]">Ranking</h1>
            </header>

            <section class="border-y border-[#dfe3e8] bg-white px-4 py-5" aria-labelledby="ranking-play-heading">
                <p class="text-[0.76rem] font-semibold text-[#667085]">Następny mecz</p>
                <h2 id="ranking-play-heading" class="mt-1 text-[1.25rem] font-bold leading-6 text-[#17191d]">
                    {{ selectedCategory?.short_name ?? selectedCategory?.code ?? 'B' }} · {{ specSummary.total_questions }} pytań
                </h2>
                <p class="mt-2 text-[0.88rem] leading-5 text-[#667085]">
                    System dobierze przeciwnika o zbliżonym poziomie.
                </p>

                <div class="mt-5 divide-x divide-[#e3e7eb] border-y border-[#e3e7eb]">
                    <div class="grid grid-cols-2">
                        <div class="px-0 py-3">
                            <p class="text-[0.72rem] font-semibold text-[#667085]">Twoje ELO</p>
                            <p class="mt-1 text-[1.35rem] font-bold tabular-nums text-[#17191d]">{{ mobilePlayerRatingLabel }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[0.72rem] font-semibold text-[#667085]">Czas meczu</p>
                            <p class="mt-1 text-[1.35rem] font-bold tabular-nums text-[#17191d]">{{ formatRankedClock(specSummary.match_duration_seconds) }}</p>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-5 flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#0b63ce] px-4 text-[0.96rem] font-semibold text-white transition hover:bg-[#084fa8] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                    :disabled="backendBusy || !selectedCategory"
                    @click="handlePrimaryRankingAction"
                >
                    {{ primaryRankingActionLabel }}
                </button>
                <p v-if="inlineLobbyError" class="mt-3 text-[0.82rem] font-semibold leading-5 text-[#b42318]">{{ inlineLobbyError }}</p>
            </section>

            <section class="mt-5 px-4" aria-labelledby="ranking-account-heading">
                <h2 id="ranking-account-heading" class="text-[0.82rem] font-semibold text-[#667085]">Twoje konto</h2>
                <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <div class="flex min-h-14 items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Mecze</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#17191d]">{{ rankedOverview?.rating.matches_played ?? 0 }}</span>
                    </div>
                    <div class="flex min-h-14 items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Zwycięstwa</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#157347]">{{ rankedOverview?.rating.wins ?? 0 }}</span>
                    </div>
                    <div class="flex min-h-14 items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Najlepsze ELO</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#17191d]">{{ rankedOverview?.rating.peak_rating ?? '—' }}</span>
                    </div>
                </div>
            </section>

            <section class="mt-5 px-4" aria-labelledby="ranking-table-heading">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="ranking-table-heading" class="text-[0.82rem] font-semibold text-[#667085]">Najlepsi gracze</h2>
                    <span class="text-[0.76rem] text-[#98a2b3]">{{ leaderboardUpdatedMobileLabel }}</span>
                </div>
                <div v-if="leaderboardEntries.length > 0" class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <article v-for="entry in leaderboardEntries.slice(0, 5)" :key="entry.user_id" class="flex min-h-[4.25rem] items-center gap-3 px-4 py-2">
                        <span class="w-5 text-[0.86rem] font-semibold tabular-nums text-[#667085]">{{ entry.position }}</span>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#eef2f6] text-[0.74rem] font-semibold text-[#475467]">
                            {{ leaderboardAvatarLabel(entry.username) }}
                        </span>
                        <p class="min-w-0 flex-1 truncate text-[0.9rem] font-semibold text-[#17191d]">{{ entry.username }}</p>
                        <span class="text-[0.88rem] font-semibold tabular-nums text-[#17191d]">{{ entry.rating }}</span>
                    </article>
                </div>
                <p v-else class="mt-2 rounded-[8px] border border-dashed border-[#cbd5e1] bg-white px-4 py-5 text-[0.86rem] leading-5 text-[#667085]">
                    Ranking pojawi się tutaj po pierwszych rozegranych meczach.
                </p>
            </section>
        </section>

        <section class="hidden pb-3 md:hidden">
            <div class="mx-auto w-full max-w-[26rem] rounded-[1.75rem] bg-white px-4 pb-4 pt-4 text-[#050b2e] shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                <div class="flex items-center justify-between">
                    <Link
                        href="/nauka"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-[0.9rem] border border-[#e4ebf6] bg-white text-[#0b5cff] shadow-[0_8px_18px_rgba(15,23,42,0.08)] transition hover:border-[#0b5cff] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                        aria-label="Wróć do panelu nauki"
                    >
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </Link>
                </div>

                <header class="mt-8">
                    <h1 class="max-w-[19rem] text-[2rem] font-bold leading-[1.08] tracking-[-0.06em] text-[#050b2e]">
                        Ranking najlepszych graczy prawa jazdy
                    </h1>
                    <p class="mt-4 text-[1rem] font-semibold leading-none text-[#64708b]">
                        Aktualizacja:
                        <span class="text-[#0b5cff]">{{ leaderboardUpdatedMobileLabel }}</span>
                    </p>
                </header>

                <div class="mt-5 space-y-3">
                    <article
                        v-if="mobileFeaturedLeaderboardEntry"
                        class="relative min-h-[10.15rem] overflow-hidden rounded-[1.1rem] border border-[#b7cffd] bg-[linear-gradient(105deg,#edf6ff_0%,#f8fbff_50%,#eaf3ff_100%)] px-3.5 py-4 shadow-[0_12px_26px_rgba(11,92,255,0.12)]"
                    >
                        <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 opacity-45">
                            <div class="absolute right-8 top-3 h-[12rem] w-14 rotate-[26deg] rounded-full border-l-2 border-dashed border-white"></div>
                            <div class="absolute right-1 top-0 h-[12rem] w-20 rotate-[26deg] rounded-full border-l-2 border-white/80"></div>
                        </div>

                        <div class="relative grid h-full grid-cols-[4.85rem_minmax(0,1fr)_4.15rem] items-center gap-2.5">
                            <div class="flex flex-col items-center">
                                <img
                                    :src="mobileMemoryCoachImage"
                                    alt=""
                                    class="h-[4.15rem] w-[4.15rem] object-contain"
                                    loading="eager"
                                    decoding="async"
                                >
                                <span class="-mt-0.5 inline-flex h-8 min-w-14 items-center justify-center rounded-t-[0.75rem] bg-[#0b5cff] px-3 text-[1.2rem] font-bold leading-none text-white shadow-[0_8px_16px_rgba(11,92,255,0.24)]">
                                    {{ mobileFeaturedLeaderboardEntry.position }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="inline-flex h-[3.25rem] w-[3.25rem] shrink-0 items-center justify-center overflow-hidden rounded-full border-[0.28rem] border-[#6ea2ff] bg-white shadow-[0_8px_18px_rgba(11,92,255,0.18)]">
                                        <img
                                            :src="rankingHeroDriverImage"
                                            :alt="mobileFeaturedLeaderboardEntry.username"
                                            class="h-full w-full object-cover object-[55%_38%]"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="truncate text-[0.96rem] font-bold tracking-[-0.045em] text-[#050b2e]">
                                            {{ mobileFeaturedLeaderboardEntry.username }}
                                        </h2>
                                        <span class="mt-1 inline-flex max-w-full items-center gap-1 rounded-full bg-white/78 px-2 py-1 text-[0.65rem] font-bold leading-none text-[#0b5cff] shadow-[0_5px_14px_rgba(11,92,255,0.1)]">
                                            <span class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full bg-[#0b5cff] text-[0.5rem] text-white">★</span>
                                            {{ leaderboardLevelLabel(mobileFeaturedLeaderboardEntry) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="relative text-right">
                                <p class="text-[0.74rem] font-bold uppercase tracking-[0.08em] text-[#64708b]">
                                    ELO
                                </p>
                                <p class="mt-1 text-[1.82rem] font-bold leading-none tracking-[-0.06em] text-[#0b5cff]">
                                    {{ mobileFeaturedLeaderboardEntry.rating }}
                                </p>
                                <p class="mt-2 text-[0.84rem] font-bold text-[#16a34a]">
                                    ↑ {{ leaderboardMomentumLabel(mobileFeaturedLeaderboardEntry) }}
                                </p>
                            </div>
                        </div>
                    </article>

                    <div
                        v-if="mobileSecondaryLeaderboardEntries.length > 0"
                        class="space-y-2.5"
                    >
                        <article
                            v-for="entry in mobileSecondaryLeaderboardEntries"
                            :key="entry.user_id"
                            class="grid min-h-[5.7rem] grid-cols-[3.25rem_4.25rem_minmax(0,1fr)_4.2rem] items-center gap-2.5 rounded-[1rem] border border-[#eef2f8] bg-white px-3 py-3 shadow-[0_8px_22px_rgba(15,23,42,0.045)]"
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full text-[1.85rem] font-bold leading-none tracking-[-0.06em]"
                                :class="entry.position === 2 ? 'bg-[#f6f8fb] text-[#64708b]' : entry.position === 3 ? 'bg-[#fff0e6] text-[#bd5b14]' : 'bg-transparent text-[#64708b]'"
                            >
                                {{ entry.position }}
                            </span>

                            <span class="inline-flex h-[3.85rem] w-[3.85rem] items-center justify-center overflow-hidden rounded-full bg-[#f8fafc]">
                                <img
                                    :src="rankingHeroDriverImage"
                                    :alt="entry.username"
                                    class="h-full w-full object-cover"
                                    :class="entry.position % 2 === 0 ? 'object-[58%_36%]' : 'object-[42%_34%]'"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </span>

                            <div class="min-w-0">
                                <h2 class="truncate text-[1rem] font-bold tracking-[-0.04em] text-[#050b2e]">
                                    {{ entry.username }}
                                </h2>
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#f4f8ff] px-2 py-1 text-[0.72rem] font-bold leading-none text-[#0b5cff]">
                                    <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-[#0b5cff] text-[0.52rem] text-white">★</span>
                                    {{ leaderboardLevelLabel(entry) }}
                                </span>
                            </div>

                            <div class="border-l border-[#eef2f8] pl-2 text-right">
                                <p class="text-[0.68rem] font-bold uppercase tracking-[0.08em] text-[#64708b]">
                                    ELO
                                </p>
                                <p class="mt-0.5 text-[1.6rem] font-bold leading-none tracking-[-0.055em] text-[#050b2e]">
                                    {{ entry.rating }}
                                </p>
                                <p class="mt-1 text-[0.78rem] font-bold text-[#16a34a]">
                                    ↑ {{ leaderboardMomentumLabel(entry) }}
                                </p>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="leaderboardEntries.length === 0"
                        class="rounded-[1rem] border border-dashed border-[#cbd5e1] bg-[#f8fbff] px-4 py-5 text-center text-[0.92rem] leading-6 text-[#64708b]"
                    >
                        Ranking pojawi się tutaj po pierwszych rozegranych meczach.
                    </div>
                </div>

                <article class="mt-4 grid min-h-[6.4rem] grid-cols-[5.3rem_minmax(0,1fr)_5.2rem] items-center gap-3 overflow-hidden rounded-[1rem] border border-[#dce9ff] bg-[linear-gradient(105deg,#edf6ff_0%,#f9fcff_54%,#eaf3ff_100%)] px-3 py-3 shadow-[0_10px_24px_rgba(11,92,255,0.08)]">
                    <img
                        :src="mobileMemoryCoachImage"
                        alt=""
                        class="h-[4.5rem] w-[4.5rem] object-contain"
                        loading="lazy"
                        decoding="async"
                    >
                    <div>
                        <p class="text-[0.86rem] font-bold uppercase tracking-[0.08em] text-[#0b5cff]">
                            Twoje ELO
                        </p>
                        <p class="mt-1 text-[2.5rem] font-bold leading-none tracking-[-0.07em] text-[#050b2e]">
                            {{ mobilePlayerRatingLabel }}
                        </p>
                    </div>
                    <div class="relative flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-[1.25rem] bg-[#0b5cff] text-white shadow-[0_12px_26px_rgba(11,92,255,0.24)]">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3.5 18 6v4.5c0 4-2.5 7.4-6 9-3.5-1.6-6-5-6-9V6l6-2.5Z" fill="currentColor" opacity="0.28" />
                            <path d="m9 12 2 2 4-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </article>

                <button
                    type="button"
                    class="mt-4 inline-flex min-h-[3.55rem] w-full items-center justify-center gap-3 rounded-[1rem] bg-[#0b5cff] px-4 text-[1.08rem] font-bold text-white shadow-[0_14px_28px_rgba(11,92,255,0.25)] transition hover:bg-[#023ea4] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="backendBusy || !selectedCategory"
                    @click="handlePrimaryRankingAction"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M8 21h8M12 17v4M7 4h10v3.5a5 5 0 0 1-10 0V4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M7 6H4.5A1.5 1.5 0 0 0 3 7.5v.75A3.75 3.75 0 0 0 6.75 12H8M17 6h2.5A1.5 1.5 0 0 1 21 7.5v.75A3.75 3.75 0 0 1 17.25 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ backendBusy ? 'Uruchamiamy...' : 'Popraw ranking' }}</span>
                    <span class="text-2xl font-light leading-none" aria-hidden="true">›</span>
                </button>
            </div>
        </section>

        <section class="hidden px-4 pb-6 pt-3 sm:px-6 sm:pt-4 md:block lg:px-8 lg:pt-5">
            <div class="mx-auto w-full space-y-8">
                <section class="relative overflow-visible">
                    <div class="px-2 py-4 sm:px-4 sm:py-6 xl:px-6 xl:py-8">
                        <div class="mx-auto w-full">
                            <div class="mb-5">
                                <Link
                                    href="/nauka"
                                    class="group inline-flex items-center gap-2 text-[0.82rem] font-medium text-[#6b7280] transition hover:text-[#020309]"
                                >
                                    <span aria-hidden="true">←</span>
                                    <span>Wróć do panelu nauki</span>
                                </Link>
                            </div>

                            <div class="text-center">
                                <h1 class="text-[2rem] font-semibold tracking-[-0.04em] text-[#020309] sm:text-[2.45rem] xl:text-[2.85rem]">
                                    Ranking najlepszych graczy prawa jazdy
                                </h1>
                            </div>

                            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18.5rem] lg:items-start xl:gap-8 xl:grid-cols-[minmax(0,1fr)_21rem]">
                                <div class="min-w-0">
                                    <div class="mb-3 flex justify-end text-[0.82rem] text-[#6b7280]">
                                        <p>
                                            Aktualizacja: {{ leaderboardUpdatedLabel }}
                                        </p>
                                    </div>

                                    <article class="overflow-hidden border border-[#d8d8dd] bg-white">
                                    <div
                                        v-if="leaderboardEntries.length > 0"
                                        class="divide-y divide-[#ececec]"
                                    >
                                        <div class="hidden grid-cols-[8.9rem_minmax(0,1fr)_11.5rem] gap-8 px-6 py-4 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280] sm:grid">
                                            <span class="pl-5">Top</span>
                                            <span>Gracz</span>
                                            <span>ELO</span>
                                        </div>
                                        <article
                                            v-for="entry in leaderboardEntries"
                                            :key="entry.user_id"
                                            class="grid grid-cols-[4.9rem_minmax(0,1fr)_auto] items-stretch gap-5 px-3 py-4 transition sm:grid-cols-[8.9rem_minmax(0,1fr)_11.5rem] sm:gap-8 sm:px-6 sm:py-5"
                                        >
                                            <span
                                                class="flex h-full items-center justify-start pl-2 text-[2rem] font-light leading-none tracking-[-0.06em] sm:pl-5 sm:text-[3rem]"
                                                :class="leaderboardPositionClass(entry.position)"
                                            >
                                                {{ entry.position }}
                                            </span>

                                            <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                                                <span
                                                    class="inline-flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#f7f8fa] text-[0.95rem] font-semibold text-[#4b5563] sm:h-[4rem] sm:w-[4rem] sm:text-[0.98rem]"
                                                    :class="entry.position <= 3 ? 'ring-1 ring-[#f0c9a0]' : ''"
                                                >
                                                    {{ leaderboardAvatarLabel(entry.username) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate text-[1rem] font-semibold tracking-[-0.02em] text-[#020309] sm:text-[1.12rem]">
                                                        {{ entry.username }}
                                                    </p>
                                                    <div class="mt-1.5 text-[0.8rem] leading-6 text-[#71717a]">
                                                        <span>Firma: przykladowa nazwa firmy</span>
                                                    </div>
                                                    <div class="mt-1.5 flex items-center gap-1.5 text-[#8b9098]">
                                                            <span
                                                                v-for="platform in startSocialPlatforms"
                                                                :key="`leaderboard-${entry.user_id}-${platform.key}`"
                                                                :title="platform.label"
                                                                class="inline-flex h-5 w-5 items-center justify-center transition hover:text-[#17161b]"
                                                            >
                                                                <span class="sr-only">{{ platform.label }}</span>
                                                                <svg
                                                                    v-if="platform.key === 'instagram'"
                                                                    viewBox="0 0 24 24"
                                                                    class="h-3.5 w-3.5"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    stroke-width="1.8"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    aria-hidden="true"
                                                                >
                                                                    <rect x="3.5" y="3.5" width="17" height="17" rx="4.5"></rect>
                                                                    <circle cx="12" cy="12" r="4"></circle>
                                                                    <circle cx="17.25" cy="6.75" r="0.8" fill="currentColor" stroke="none"></circle>
                                                                </svg>
                                                                <svg
                                                                    v-else-if="platform.key === 'tiktok'"
                                                                    viewBox="0 0 24 24"
                                                                    class="h-3.5 w-3.5"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    stroke-width="1.8"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    aria-hidden="true"
                                                                >
                                                                    <path d="M14 4v8.6a3.6 3.6 0 1 1-2.9-3.5"></path>
                                                                    <path d="M14 4c1 1.9 2.4 3.1 4.5 3.4"></path>
                                                                </svg>
                                                                <svg
                                                                    v-else-if="platform.key === 'youtube'"
                                                                    viewBox="0 0 24 24"
                                                                    class="h-3.5 w-3.5"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    stroke-width="1.8"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    aria-hidden="true"
                                                                >
                                                                    <path d="M22 12s0-3-0.4-4.4a2.8 2.8 0 0 0-2-2C18.1 5.2 12 5.2 12 5.2s-6.1 0-7.6 0.4a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 0.4 4.4a2.8 2.8 0 0 0 2 2c1.5 0.4 7.6 0.4 7.6 0.4s6.1 0 7.6-0.4a2.8 2.8 0 0 0 2-2C22 15 22 12 22 12Z"></path>
                                                                    <path d="m10 9 5 3-5 3Z" fill="currentColor" stroke="none"></path>
                                                                </svg>
                                                                <svg
                                                                    v-else
                                                                    viewBox="0 0 24 24"
                                                                    class="h-3.5 w-3.5"
                                                                    fill="currentColor"
                                                                    aria-hidden="true"
                                                                >
                                                                    <path d="M13.4 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.3-1.5 1.6-1.5h1.7V3.6c-.3 0-.9-.1-1.8-.1-1.8 0-3 .9-3 3.2v1.8H9.3V13H12v8h1.4Z"></path>
                                                                </svg>
                                                            </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-end gap-4 sm:justify-start">
                                                <svg
                                                    v-if="leaderboardEloTrend(entry) === 'up'"
                                                    viewBox="0 0 24 24"
                                                    class="h-6 w-6 shrink-0 text-[#88c441] sm:h-7 sm:w-7"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.3"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M12 19V6"></path>
                                                    <path d="m6.5 11.5 5.5-5.5 5.5 5.5"></path>
                                                </svg>
                                                <svg
                                                    v-else
                                                    viewBox="0 0 24 24"
                                                    class="h-6 w-6 shrink-0 text-[#e53935] sm:h-7 sm:w-7"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.3"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M12 5v13"></path>
                                                    <path d="m6.5 12.5 5.5 5.5 5.5-5.5"></path>
                                                </svg>
                                                <p class="text-[1.28rem] font-semibold tracking-[-0.04em] text-[#020309] sm:text-[1.55rem]">
                                                    {{ entry.rating }}
                                                </p>
                                            </div>
                                        </article>
                                    </div>

                                    <div
                                        v-else
                                        class="py-2 text-[0.9rem] leading-7 text-[#5f6368]"
                                    >
                                        Ranking pojawi się tutaj po pierwszych rozegranych meczach.
                                    </div>
                                    </article>
                                </div>

                                <aside class="pt-1 lg:pl-1 xl:pl-2">
                                    <div class="border border-[#d8d8dd] bg-white">
                                        <div class="px-5 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-[#d1d5db] bg-[#f7f8fa]">
                                                    <img
                                                        :src="rankingHeroDriverImage"
                                                        alt="Gracz gotowy do startu rankingu"
                                                        class="h-full w-full object-cover object-[60%_32%]"
                                                    >
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]">
                                                        Profil rankingowy
                                                    </p>
                                                    <h2 class="mt-2 text-[1.85rem] font-semibold tracking-[-0.05em] text-[#17161b]">
                                                        {{ playerName }}
                                                    </h2>

                                                    <div class="mt-4 border-t border-[#ececec] pt-3">
                                                        <div class="flex flex-wrap items-center gap-2 text-[#8b9098]">
                                                            <span
                                                                v-for="platform in startSocialPlatforms"
                                                                :key="platform.key"
                                                                :title="platform.label"
                                                                class="inline-flex h-7 w-7 items-center justify-center transition hover:text-[#17161b]"
                                                            >
                                                            <svg
                                                                v-if="platform.key === 'instagram'"
                                                                viewBox="0 0 24 24"
                                                                class="h-3.5 w-3.5"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="1.8"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                aria-hidden="true"
                                                            >
                                                                <rect x="3.5" y="3.5" width="17" height="17" rx="4.5"></rect>
                                                                <circle cx="12" cy="12" r="4"></circle>
                                                                <circle cx="17.25" cy="6.75" r="0.8" fill="currentColor" stroke="none"></circle>
                                                            </svg>
                                                            <svg
                                                                v-else-if="platform.key === 'tiktok'"
                                                                viewBox="0 0 24 24"
                                                                class="h-3.5 w-3.5"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="1.8"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                aria-hidden="true"
                                                            >
                                                                <path d="M14 4v8.6a3.6 3.6 0 1 1-2.9-3.5"></path>
                                                                <path d="M14 4c1 1.9 2.4 3.1 4.5 3.4"></path>
                                                            </svg>
                                                            <svg
                                                                v-else-if="platform.key === 'youtube'"
                                                                viewBox="0 0 24 24"
                                                                class="h-3.5 w-3.5"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="1.8"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                aria-hidden="true"
                                                            >
                                                                <path d="M22 12s0-3-0.4-4.4a2.8 2.8 0 0 0-2-2C18.1 5.2 12 5.2 12 5.2s-6.1 0-7.6 0.4a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 0.4 4.4a2.8 2.8 0 0 0 2 2c1.5 0.4 7.6 0.4 7.6 0.4s6.1 0 7.6-0.4a2.8 2.8 0 0 0 2-2C22 15 22 12 22 12Z"></path>
                                                                <path d="m10 9 5 3-5 3Z" fill="currentColor" stroke="none"></path>
                                                            </svg>
                                                            <svg
                                                                v-else
                                                                viewBox="0 0 24 24"
                                                                class="h-3.5 w-3.5"
                                                                fill="currentColor"
                                                                aria-hidden="true"
                                                            >
                                                                <path d="M13.4 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.3-1.5 1.6-1.5h1.7V3.6c-.3 0-.9-.1-1.8-.1-1.8 0-3 .9-3 3.2v1.8H9.3V13H12v8h1.4Z"></path>
                                                            </svg>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="startPrimaryCard"
                                            class="border-y border-[#ececec] px-5 py-5"
                                        >
                                            <dl class="flex items-center justify-between gap-4">
                                                <div class="min-w-0">
                                                    <dt class="text-[0.8rem] font-semibold uppercase tracking-[0.18em] text-[#6b7280]">
                                                        {{ startPrimaryCard.label }}
                                                    </dt>
                                                </div>
                                                <dd class="shrink-0 text-[1.8rem] font-semibold tracking-[-0.05em] text-[#17161b] sm:text-[1.95rem]">
                                                    {{ startPrimaryCard.value }}
                                                </dd>
                                            </dl>
                                        </div>

                                        <div class="px-5 py-5">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[3.25rem] w-full items-center justify-center bg-[#f58220] px-6 text-[0.98rem] font-semibold text-white transition hover:bg-[#d86f17] disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="backendBusy || !selectedCategory"
                                                @click="handlePrimaryRankingAction"
                                            >
                                                <span
                                                    v-if="primaryRankingActionShowsSpinner"
                                                    class="mr-3 inline-flex h-5 w-5 animate-spin rounded-full border-2 border-white/25 border-t-white"
                                                    aria-hidden="true"
                                                ></span>
                                                <span>{{ primaryRankingActionLabel }}</span>
                                            </button>

                                            <p
                                                v-if="inlineLobbyError"
                                                class="mt-3 text-[0.82rem] leading-6 text-[#a52323]"
                                            >
                                                {{ inlineLobbyError }}
                                            </p>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </section>

            <section
                v-if="shouldShowBackendMainPanel"
                class="grid gap-6"
            >
                <div class="space-y-6">
                    <article
                        v-if="shouldShowBackendMainPanel"
                        id="ranking-live-flow"
                        class="rounded-[1.25rem] border border-[#e9e1d4] bg-white p-5 shadow-[0_18px_42px_rgba(15,23,42,0.05)]"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#8c7a67]">
                                    Status gry
                                </p>
                                <h2 class="mt-2 text-[1.3rem] font-semibold tracking-[-0.03em] text-[#17161b]">
                                    Ranking na żywo
                                </h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[0.78rem] font-semibold"
                                    :class="backendTransportChipClass"
                                >
                                    {{ backendTransportLabel }}
                                </span>
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[0.78rem] font-semibold"
                                    :class="backendStateChipClass"
                                >
                                    {{ backendStateDisplayLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[2.85rem] items-center justify-center rounded-[0.9rem] border border-[#ddd1c4] bg-white px-5 text-[0.92rem] font-semibold text-[#2f2921] transition hover:border-[#c7b5a0] hover:bg-[#faf8f4] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="backendBusy"
                                @click="fetchRankedOverview()"
                            >
                                Odśwież status
                            </button>
                            <button
                                v-if="rankedOverview?.queue"
                                type="button"
                                class="inline-flex min-h-[2.85rem] items-center justify-center rounded-[0.9rem] border border-[#ddd1c4] bg-white px-5 text-[0.92rem] font-semibold text-[#2f2921] transition hover:border-[#c7b5a0] hover:bg-[#faf8f4] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="backendBusy"
                                @click="leaveRealQueue"
                            >
                                Opuść kolejkę
                            </button>
                        </div>

                        <div
                            v-if="backendError"
                            class="mt-4 rounded-[1rem] border border-[#ffd0c3] bg-[#fff4f1] px-4 py-3 text-[0.84rem] leading-6 text-[#7f3a2b]"
                        >
                            {{ backendError }}
                        </div>

                        <div
                            v-if="backendLoaded && rankedOverview"
                            class="mt-5 space-y-4"
                        >
                            <div
                                v-if="rankedOverview.queue || rankedOverview.active_match"
                                class="grid gap-4 xl:grid-cols-3"
                            >
                                <div
                                    v-if="rankedOverview.queue"
                                    class="rounded-[1rem] border border-[#efe7db] bg-[#fcfbf8] p-4"
                                >
                                    <p class="text-[0.76rem] font-semibold uppercase tracking-[0.15em] text-[#8c7a67]">
                                        Stan kolejki
                                    </p>
                                    <div class="mt-3 space-y-2 text-[0.84rem] leading-6 text-[#2f2921]">
                                        <div
                                            v-if="backendServerFullBanner"
                                            class="rounded-[0.9rem] border border-[#ffd7cd] bg-[#fff4f1] px-3 py-3"
                                        >
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#b43b16]">
                                                        {{ backendServerFullBanner.title }}
                                                    </p>
                                                    <p class="mt-1 text-[0.84rem] font-semibold text-[#7f2e1a]">
                                                        {{ backendServerFullBanner.body }}
                                                    </p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-[#ffe3db] px-2.5 py-1 text-[0.72rem] font-semibold text-[#b43b16]">
                                                    outside capacity
                                                </span>
                                            </div>
                                            <p
                                                v-if="backendServerFullBanner.meta"
                                                class="mt-2 text-[0.78rem] leading-6 text-[#8a4b3c]"
                                            >
                                                {{ backendServerFullBanner.meta }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="backendQueueClock"
                                            class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3"
                                        >
                                            <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">
                                                {{ backendQueueClock.eyebrow }}
                                            </p>
                                            <p
                                                class="mt-1 text-[1rem] font-semibold"
                                                :class="backendQueueClock.tone"
                                            >
                                                {{ backendQueueClock.primary }}
                                            </p>
                                            <p class="mt-1 text-[0.8rem] leading-6 text-[#6f665c]">
                                                {{ backendQueueClock.secondary }}
                                            </p>
                                        </div>
                                        <p><span class="font-semibold">Status:</span> {{ backendStateHumanLabel(rankedOverview.queue.status) }}</p>
                                        <p v-if="rankedOverview.queue.category">
                                            <span class="font-semibold">Kategoria:</span>
                                            {{ rankedOverview.queue.category.name }}
                                        </p>
                                        <p v-if="rankedOverview.queue.joined_at">
                                            <span class="font-semibold">Dołączenie:</span>
                                            {{ rankedOverview.queue.joined_at }}
                                        </p>
                                        <p v-if="rankedOverview.queue.match_public_id">
                                            <span class="font-semibold">Match ID:</span>
                                            {{ rankedOverview.queue.match_public_id }}
                                        </p>
                                        <p v-if="rankedOverview.queue.server_full?.position_in_queue != null">
                                            <span class="font-semibold">Pozycja poza limitem:</span>
                                            {{ rankedOverview.queue.server_full?.position_in_queue }}
                                        </p>
                                        <p v-if="rankedOverview.queue.server_full?.estimated_wait_minutes != null">
                                            <span class="font-semibold">Szacowany czas:</span>
                                            {{ rankedOverview.queue.server_full?.estimated_wait_minutes }} min
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="rankedOverview.active_match"
                                    class="rounded-[1rem] border border-[#efe7db] bg-[#fcfbf8] p-4"
                                >
                                    <p class="text-[0.76rem] font-semibold uppercase tracking-[0.15em] text-[#8c7a67]">
                                        Bieżący mecz
                                    </p>
                                    <div class="mt-3 space-y-3">
                                        <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                            <p class="text-[0.82rem] font-semibold text-[#17161b]">
                                                {{ rankedOverview.active_match.public_id }}
                                            </p>
                                            <p class="mt-1 text-[0.8rem] text-[#6f665c]">
                                                {{ rankedOverview.active_match.category.name }} • {{ rankedOverview.active_match.total_questions }} pytań
                                            </p>
                                        </div>

                                        <div
                                            v-if="backendMatchClock"
                                            class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3"
                                        >
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">
                                                        {{ backendMatchClock.eyebrow }}
                                                    </p>
                                                    <p
                                                        class="mt-1 text-[1.05rem] font-semibold"
                                                        :class="backendMatchClock.tone"
                                                    >
                                                        {{ backendMatchClock.primary }}
                                                    </p>
                                                </div>
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-[0.72rem] font-semibold"
                                                    :class="backendStateClass(rankedOverview.active_match.state)"
                                                >
                                                    {{ backendStateHumanLabel(rankedOverview.active_match.state) }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                                {{ backendMatchClock.secondary }}
                                            </p>
                                            <div
                                                v-if="backendMatchClock.progressPercent !== null"
                                                class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#efe7db]"
                                            >
                                                <div
                                                    class="h-full rounded-full bg-[linear-gradient(90deg,#f58220,#f2b766)] transition-[width] duration-700"
                                                    :style="{ width: `${backendMatchClock.progressPercent}%` }"
                                                ></div>
                                            </div>
                                        </div>


                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                                <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Ty</p>
                                                <p class="mt-1 text-[0.9rem] font-semibold text-[#17161b]">
                                                    {{
                                                        rankedOverview.active_match.players.find((player) => player.is_current_user)?.username
                                                        ?? '—'
                                                    }}
                                                </p>
                                            </div>
                                            <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                                <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Przeciwnik</p>
                                                <p class="mt-1 text-[0.9rem] font-semibold text-[#17161b]">
                                                    {{ backendOpponent?.username ?? backendRealtimeProjection.opponentUsername ?? 'oczekiwanie' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            v-if="backendCurrentPresence || backendOpponentPresence"
                                            class="grid gap-3 sm:grid-cols-2"
                                        >
                                            <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Twój heartbeat</p>
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-1 text-[0.72rem] font-semibold"
                                                        :class="backendPresenceChipClass(backendCurrentPresence?.presence_state)"
                                                    >
                                                        {{ backendPresenceLabel(backendCurrentPresence?.presence_state) }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                                    Ostatni kontakt {{ backendCurrentLastSeenSeconds ?? '—' }} s temu.
                                                    Następny timeout za {{ backendCurrentDisconnectCountdownSeconds ?? '—' }} s.
                                                </p>
                                            </div>
                                            <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Heartbeat przeciwnika</p>
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-1 text-[0.72rem] font-semibold"
                                                        :class="backendPresenceChipClass(backendOpponentPresence?.presence_state)"
                                                    >
                                                        {{ backendPresenceLabel(backendOpponentPresence?.presence_state) }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                                    <template v-if="backendOpponentPresence?.presence_state === 'disconnected'">
                                                        Deadline reconnectu za {{ backendReconnectCountdownSeconds ?? '—' }} s.
                                                    </template>
                                                    <template v-else>
                                                        Ostatni kontakt {{ backendOpponentLastSeenSeconds ?? '—' }} s temu.
                                                        Timeout za {{ backendOpponentDisconnectCountdownSeconds ?? '—' }} s.
                                                    </template>
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            v-if="backendPresenceBanner"
                                            class="rounded-[0.9rem] border border-[#f7dca8] bg-[#fff9e9] px-3 py-3"
                                        >
                                            <p class="text-[0.82rem] font-semibold text-[#9b6a00]">
                                                {{ backendPresenceBanner.title }}
                                            </p>
                                            <p class="mt-1 text-[0.8rem] leading-6 text-[#7f6327]">
                                                {{ backendPresenceBanner.body }}
                                            </p>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-3">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[2.6rem] items-center justify-center rounded-[0.85rem] bg-[#17161b] px-4 text-[0.84rem] font-semibold text-white transition hover:bg-[#0f0f13] disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="backendMatchBusy"
                                                @click="fetchActiveMatchDetails()"
                                            >
                                                {{ backendMatchBusy ? 'Ładowanie meczu...' : 'Otwórz pytania meczu' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div
                            v-if="backendMatchError"
                            class="mt-4 rounded-[1rem] border border-[#ffd0c3] bg-[#fff4f1] px-4 py-3 text-[0.84rem] leading-6 text-[#7f3a2b]"
                        >
                            {{ backendMatchError }}
                        </div>

                        <div
                            v-if="backendMatchDetails"
                            class="mt-5 rounded-[1rem] border border-[#efe7db] bg-[#fcfbf8] p-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-[0.76rem] font-semibold uppercase tracking-[0.15em] text-[#8c7a67]">
                                        Pytania i postęp meczu
                                    </p>
                                    <h3 class="mt-2 text-[1.05rem] font-semibold tracking-[-0.03em] text-[#17161b]">
                                        {{ backendMatchDetails.match.public_id }}
                                    </h3>
                                </div>
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[0.76rem] font-semibold"
                                    :class="backendStateClass(backendMatchDetails.match.state)"
                                >
                                    {{ backendStateHumanLabel(backendMatchDetails.match.state) }}
                                </span>
                            </div>

                            <div
                                v-if="backendPresenceBanner"
                                class="mt-4 rounded-[0.9rem] border border-[#f7dca8] bg-[#fff9e9] px-4 py-3"
                            >
                                <p class="text-[0.82rem] font-semibold text-[#9b6a00]">
                                    {{ backendPresenceBanner.title }}
                                </p>
                                <p class="mt-1 text-[0.8rem] leading-6 text-[#7f6327]">
                                    {{ backendPresenceBanner.body }}
                                </p>
                            </div>

                            <div
                                v-if="backendMatchClock"
                                class="mt-4 rounded-[0.9rem] border border-[#e6ddd1] bg-white px-4 py-4"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">
                                            {{ backendMatchClock.eyebrow }}
                                        </p>
                                        <p
                                            class="mt-1 text-[1.08rem] font-semibold"
                                            :class="backendMatchClock.tone"
                                        >
                                            {{ backendMatchClock.primary }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[0.74rem] font-semibold"
                                        :class="backendStateClass(backendMatchDetails.match.state)"
                                    >
                                        {{ backendStateHumanLabel(backendMatchDetails.match.state) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                    {{ backendMatchClock.secondary }}
                                </p>
                                <div
                                    v-if="backendMatchClock.progressPercent !== null"
                                    class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#efe7db]"
                                >
                                    <div
                                        class="h-full rounded-full bg-[linear-gradient(90deg,#17161b,#4b5563)] transition-[width] duration-700"
                                        :style="{ width: `${backendMatchClock.progressPercent}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Odpowiedzi</p>
                                    <p class="mt-1 text-[0.95rem] font-semibold text-[#17161b]">
                                        {{ backendMatchDetails.progress.answered }}/{{ backendMatchDetails.progress.total }}
                                    </p>
                                </div>
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Poprawne</p>
                                    <p class="mt-1 text-[0.95rem] font-semibold text-[#17161b]">
                                        {{ backendMatchDetails.progress.correct_answers }}
                                    </p>
                                </div>
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Punkty</p>
                                    <p class="mt-1 text-[0.95rem] font-semibold text-[#17161b]">
                                        {{ backendMatchDetails.progress.points }}
                                    </p>
                                </div>
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Postęp</p>
                                    <p class="mt-1 text-[0.95rem] font-semibold text-[#17161b]">
                                        {{ backendMatchDetails.progress.completion_percent }}%
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="backendCurrentPresence || backendOpponentPresence"
                                class="mt-4 grid gap-3 sm:grid-cols-2"
                            >
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Twój status połączenia</p>
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-[0.72rem] font-semibold"
                                            :class="backendPresenceChipClass(backendCurrentPresence?.presence_state)"
                                        >
                                            {{ backendPresenceLabel(backendCurrentPresence?.presence_state) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                        Timeout za {{ backendCurrentDisconnectCountdownSeconds ?? '—' }} s.
                                    </p>
                                </div>
                                <div class="rounded-[0.9rem] border border-[#e6ddd1] bg-white px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Status przeciwnika</p>
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-[0.72rem] font-semibold"
                                            :class="backendPresenceChipClass(backendOpponentPresence?.presence_state)"
                                        >
                                            {{ backendPresenceLabel(backendOpponentPresence?.presence_state) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-[0.82rem] leading-6 text-[#6f665c]">
                                        <template v-if="backendOpponentPresence?.presence_state === 'disconnected'">
                                            Walkower za {{ backendReconnectCountdownSeconds ?? '—' }} s.
                                        </template>
                                        <template v-else>
                                            Ostatni heartbeat {{ backendOpponentLastSeenSeconds ?? '—' }} s temu.
                                            Timeout za {{ backendOpponentDisconnectCountdownSeconds ?? '—' }} s.
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-[0.9rem] border border-[#e6ddd1] bg-white px-4 py-3">
                                <div>
                                    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-[#8c7a67]">Powód zakończenia</p>
                                    <p class="mt-1 text-[0.9rem] font-semibold text-[#17161b]">
                                        {{ backendReasonLabel(backendMatchDetails.match.reason) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(17rem,0.72fr)_minmax(0,1.28fr)]">
                                <div class="space-y-3">
                                    <div class="rounded-[0.95rem] border border-[#e6ddd1] bg-white px-4 py-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#8c7a67]">
                                                    Plansza pytań
                                                </p>
                                                <p class="mt-1 text-[0.88rem] leading-6 text-[#6f665c]">
                                                    Odpowiedziane {{ backendQuestionProgress.answered }}/{{ backendQuestionProgress.total }}.
                                                    Zostało {{ backendQuestionProgress.remaining }}.
                                                </p>
                                            </div>
                                            <span class="rounded-full bg-[#f6f3ee] px-3 py-1 text-[0.74rem] font-semibold text-[#5b534a]">
                                                {{ backendQuestionProgress.total }} pytań
                                            </span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-8 xl:grid-cols-5">
                                            <div
                                                v-for="question in backendQuestions"
                                                :key="question.id"
                                                class="inline-flex min-h-[2.7rem] items-center justify-center rounded-[0.8rem] border text-[0.8rem] font-semibold transition"
                                                :class="
                                                    question.id === backendResolvedQuestionId
                                                        ? 'border-[#17161b] bg-[#17161b] text-white shadow-[0_10px_22px_rgba(23,22,27,0.14)]'
                                                        : question.is_answered
                                                            ? 'border-[#cae9d7] bg-[#ecfdf3] text-[#0f7b43]'
                                                            : 'border-[#ddd1c4] bg-[#faf8f4] text-[#2f2921]'
                                                "
                                            >
                                                {{ question.question_number }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-[0.95rem] border border-[#e6ddd1] bg-white px-4 py-4">
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#8c7a67]">
                                            Flow pytań
                                        </p>
                                        <p class="mt-3 text-[0.82rem] leading-6 text-[#6f665c]">
                                            <template v-if="backendSelectedQuestion">
                                                Aktywne pytanie {{ backendSelectedQuestion.question_number }}.
                                                Po odpowiedzi widok automatycznie przejdzie dalej.
                                            </template>
                                            <template v-else>
                                                Ładujemy bieżące pytanie dla gracza.
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <article
                                    v-if="backendSelectedQuestion"
                                    class="rounded-[0.95rem] border border-[#e6ddd1] bg-white px-4 py-4"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#8c7a67]">
                                                Pytanie {{ backendSelectedQuestion.question_number }}
                                            </p>
                                            <p class="mt-1 text-[0.9rem] leading-6 text-[#201d1a]">
                                                {{ backendSelectedQuestion.question_text }}
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full px-3 py-1 text-[0.74rem] font-semibold"
                                            :class="backendSelectedQuestion.is_answered ? 'bg-[#ecfdf3] text-[#0f7b43]' : 'bg-[#f3f4f6] text-[#4b5563]'"
                                        >
                                            {{ backendSelectedQuestion.is_answered ? 'odpowiedziane' : 'otwarte' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                        <button
                                            v-for="(label, key) in backendSelectedQuestion.answers"
                                            :key="key"
                                            type="button"
                                            class="inline-flex min-h-[3rem] items-center justify-between rounded-[0.85rem] border px-3.5 py-3 text-left text-[0.84rem] font-medium transition"
                                            :class="
                                                backendSelectedQuestion.selected_answer === key
                                                    ? 'border-[#17161b] bg-[#17161b] text-white'
                                                    : 'border-[#ddd1c4] bg-[#faf8f4] text-[#2f2921] hover:border-[#c7b5a0] hover:bg-white'
                                            "
                                            :disabled="backendMatchBusy || backendSelectedQuestion.is_answered || backendMatchInteractionLocked"
                                            @click="submitBackendAnswer(backendSelectedQuestion.id, key)"
                                        >
                                            <span class="mr-3 inline-flex min-h-[1.9rem] min-w-[1.9rem] items-center justify-center rounded-full border border-current/20 text-[0.76rem] font-semibold">
                                                {{ key }}
                                            </span>
                                            <span class="flex-1 leading-6">{{ label }}</span>
                                        </button>
                                    </div>

                                    <p
                                        v-if="backendSelectedQuestion.is_answered"
                                        class="mt-3 text-[0.82rem] leading-6 text-[#6f665c]"
                                    >
                                        Twoja odpowiedź: {{ backendSelectedQuestion.selected_answer }}
                                        <span v-if="['finished', 'abandoned'].includes(backendMatchDetails.match.status) && backendSelectedQuestion.correct_answer">
                                            / poprawna: {{ backendSelectedQuestion.correct_answer }}
                                        </span>
                                    </p>

                                    <p
                                        v-else-if="backendMatchInteractionLocked"
                                        class="mt-3 text-[0.82rem] leading-6 text-[#6f665c]"
                                    >
                                        Interakcja z odpowiedziami jest chwilowo zablokowana, bo mecz jeszcze nie wystartował albo został już zamknięty.
                                    </p>
                                </article>
                            </div>
                        </div>

                    </article>
                </div>
            </section>
            </div>
        </section>
        </template>
    </AuthenticatedLayout>
</template>
