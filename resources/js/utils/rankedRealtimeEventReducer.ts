import type {
    RankedRealtimeEvent,
    RankedSimulationPhase,
} from '@/utils/rankingModeSimulation';

export interface RankedRealtimeProjection {
    phase: RankedSimulationPhase | 'idle';
    transportState: string;
    matchId: string | null;
    matchedAt: string | null;
    countdownSeconds: number | null;
    startedAt: string | null;
    durationSeconds: number | null;
    finishedAt: string | null;
    reconnectDeadlineAt: string | null;
    latestEventName: string | null;
    latestEventId: string | null;
    processedEventIds: string[];
    outcome: string | null;
    resultLabel: string | null;
    resultCategoryName: string | null;
    resultTotalQuestions: number | null;
    currentUserAnsweredCount: number;
    currentUserCorrectAnswers: number;
    currentUserEloChange: number | null;
    opponentUsername: string | null;
    opponentAnsweredCount: number;
    opponentCorrectAnswers: number;
    queuePosition: number | null;
    currentCapacity: number | null;
    maxConcurrentPlayers: number | null;
    estimatedWaitMinutes: number | null;
    lastErrorCode: string | null;
    lastErrorMessage: string | null;
    resultReason: string | null;
}

export const createInitialRankedRealtimeProjection = (): RankedRealtimeProjection => ({
    phase: 'idle',
    transportState: 'Brak eventów z backendu.',
    matchId: null,
    matchedAt: null,
    countdownSeconds: null,
    startedAt: null,
    durationSeconds: null,
    finishedAt: null,
    reconnectDeadlineAt: null,
    latestEventName: null,
    latestEventId: null,
    processedEventIds: [],
    outcome: null,
    resultLabel: null,
    resultCategoryName: null,
    resultTotalQuestions: null,
    currentUserAnsweredCount: 0,
    currentUserCorrectAnswers: 0,
    currentUserEloChange: null,
    opponentUsername: null,
    opponentAnsweredCount: 0,
    opponentCorrectAnswers: 0,
    queuePosition: null,
    currentCapacity: null,
    maxConcurrentPlayers: null,
    estimatedWaitMinutes: null,
    lastErrorCode: null,
    lastErrorMessage: null,
    resultReason: null,
});

const resolveOpponentUsername = (event: RankedRealtimeEvent, fallback: string | null): string | null => {
    const opponent = event.data?.opponent;

    if (typeof opponent !== 'object' || opponent === null) {
        return fallback;
    }

    return typeof (opponent as { username?: unknown }).username === 'string'
        ? (opponent as { username: string }).username
        : fallback;
};

const resolveOpponentScore = (
    event: RankedRealtimeEvent,
): { totalAnswered?: number; correctAnswers?: number } => {
    const opponentScore = event.data?.opponent_score;

    if (typeof opponentScore !== 'object' || opponentScore === null) {
        return {};
    }

    const payload = opponentScore as {
        total_answered?: unknown;
        correct_answers?: unknown;
    };

    return {
        totalAnswered: typeof payload.total_answered === 'number'
            ? payload.total_answered
            : undefined,
        correctAnswers: typeof payload.correct_answers === 'number'
            ? payload.correct_answers
            : undefined,
    };
};

const resolveOpponentCurrentState = (
    event: RankedRealtimeEvent,
): { totalAnswered?: number; correctAnswers?: number } => {
    const currentState = event.data?.opponent_current_state;

    if (typeof currentState !== 'object' || currentState === null) {
        return {};
    }

    const payload = currentState as {
        total_answered?: unknown;
        current_question?: unknown;
        correct_answers?: unknown;
    };

    const totalAnswered = typeof payload.total_answered === 'number'
        ? payload.total_answered
        : (typeof payload.current_question === 'number'
            ? Math.max(payload.current_question - 1, 0)
            : undefined);

    return {
        totalAnswered,
        correctAnswers: typeof payload.correct_answers === 'number'
            ? payload.correct_answers
            : undefined,
    };
};

const resolveFinalOpponentSnapshot = (
    event: RankedRealtimeEvent,
): { username?: string; totalAnswered?: number; correctAnswers?: number } => {
    const opponent = event.data?.opponent;

    if (typeof opponent !== 'object' || opponent === null) {
        return {};
    }

    const payload = opponent as {
        username?: unknown;
        total_answered?: unknown;
        correct_answers?: unknown;
    };

    return {
        username: typeof payload.username === 'string'
            ? payload.username
            : undefined,
        totalAnswered: typeof payload.total_answered === 'number'
            ? payload.total_answered
            : undefined,
        correctAnswers: typeof payload.correct_answers === 'number'
            ? payload.correct_answers
            : undefined,
    };
};

const resolveFinalCurrentUserSnapshot = (
    event: RankedRealtimeEvent,
): { totalAnswered?: number; correctAnswers?: number; eloChange?: number | null } => {
    const currentUser = event.data?.current_user;

    if (typeof currentUser !== 'object' || currentUser === null) {
        return {};
    }

    const payload = currentUser as {
        total_answered?: unknown;
        correct_answers?: unknown;
        elo_change?: unknown;
    };

    return {
        totalAnswered: typeof payload.total_answered === 'number'
            ? payload.total_answered
            : undefined,
        correctAnswers: typeof payload.correct_answers === 'number'
            ? payload.correct_answers
            : undefined,
        eloChange: typeof payload.elo_change === 'number'
            ? payload.elo_change
            : (payload.elo_change === null ? null : undefined),
    };
};

const resolveOverviewQueueServerFull = (
    event: RankedRealtimeEvent,
): {
    positionInQueue?: number;
    currentCapacity?: number;
    maxConcurrentPlayers?: number;
    estimatedWaitMinutes?: number;
    errorCode?: string;
    message?: string;
} => {
    const queue = event.data?.queue;

    if (typeof queue !== 'object' || queue === null) {
        return {};
    }

    const payload = (queue as {
        server_full?: {
            position_in_queue?: unknown;
            current_capacity?: unknown;
            max_concurrent_players?: unknown;
            estimated_wait_minutes?: unknown;
            error_code?: unknown;
            message?: unknown;
        } | null;
    }).server_full;

    if (typeof payload !== 'object' || payload === null) {
        return {};
    }

    return {
        positionInQueue: typeof payload.position_in_queue === 'number'
            ? payload.position_in_queue
            : undefined,
        currentCapacity: typeof payload.current_capacity === 'number'
            ? payload.current_capacity
            : undefined,
        maxConcurrentPlayers: typeof payload.max_concurrent_players === 'number'
            ? payload.max_concurrent_players
            : undefined,
        estimatedWaitMinutes: typeof payload.estimated_wait_minutes === 'number'
            ? payload.estimated_wait_minutes
            : undefined,
        errorCode: typeof payload.error_code === 'string'
            ? payload.error_code
            : undefined,
        message: typeof payload.message === 'string'
            ? payload.message
            : undefined,
    };
};

type OverviewMatchSnapshot = {
    public_id?: unknown;
    state?: unknown;
    status?: unknown;
    matched_at?: unknown;
    countdown_started_at?: unknown;
    countdown_seconds?: unknown;
    started_at?: unknown;
    duration_seconds?: unknown;
    finished_at?: unknown;
    abandoned_at?: unknown;
    reason?: unknown;
    outcome?: unknown;
    result_label?: unknown;
    current_user?: {
        total_answered?: unknown;
        correct_answers?: unknown;
        elo_change?: unknown;
    } | null;
    players?: Array<{
        is_current_user?: unknown;
        total_answered?: unknown;
        correct_answers?: unknown;
        elo_change?: unknown;
    }> | null;
    total_questions?: unknown;
    category?: {
        name?: unknown;
    } | null;
    opponent?: {
        username?: unknown;
        total_answered?: unknown;
        correct_answers?: unknown;
    } | null;
    presence?: {
        opponent?: {
            reconnect_deadline_at?: unknown;
        } | null;
    } | null;
};

const resolveOverviewMatchSnapshot = (
    event: RankedRealtimeEvent,
    key: 'active_match' | 'recent_match',
): OverviewMatchSnapshot | null => {
    const payload = event.data?.[key];

    return typeof payload === 'object' && payload !== null
        ? (payload as OverviewMatchSnapshot)
        : null;
};

const resolveOverviewCurrentUserSnapshot = (
    match: OverviewMatchSnapshot | null,
): { totalAnswered?: number; correctAnswers?: number; eloChange?: number | null } => {
    if (!match) {
        return {};
    }

    if (typeof match.current_user === 'object' && match.current_user !== null) {
        return {
            totalAnswered: typeof match.current_user.total_answered === 'number'
                ? match.current_user.total_answered
                : undefined,
            correctAnswers: typeof match.current_user.correct_answers === 'number'
                ? match.current_user.correct_answers
                : undefined,
            eloChange: typeof match.current_user.elo_change === 'number'
                ? match.current_user.elo_change
                : (match.current_user.elo_change === null ? null : undefined),
        };
    }

    const currentPlayer = match.players?.find((player) => player?.is_current_user === true);

    if (!currentPlayer) {
        return {};
    }

    return {
        totalAnswered: typeof currentPlayer.total_answered === 'number'
            ? currentPlayer.total_answered
            : undefined,
        correctAnswers: typeof currentPlayer.correct_answers === 'number'
            ? currentPlayer.correct_answers
            : undefined,
        eloChange: typeof currentPlayer.elo_change === 'number'
            ? currentPlayer.elo_change
            : (currentPlayer.elo_change === null ? null : undefined),
    };
};

const clearTerminalSummary = (projection: RankedRealtimeProjection): void => {
    projection.outcome = null;
    projection.resultLabel = null;
    projection.resultCategoryName = null;
    projection.resultTotalQuestions = null;
    projection.currentUserAnsweredCount = 0;
    projection.currentUserCorrectAnswers = 0;
    projection.currentUserEloChange = null;
    projection.resultReason = null;
};

const clearMatchRuntimeState = (projection: RankedRealtimeProjection): void => {
    projection.matchId = null;
    projection.matchedAt = null;
    projection.countdownSeconds = null;
    projection.startedAt = null;
    projection.durationSeconds = null;
    projection.finishedAt = null;
    projection.reconnectDeadlineAt = null;
    projection.opponentUsername = null;
    projection.opponentAnsweredCount = 0;
    projection.opponentCorrectAnswers = 0;
};

export const mergeRankedRealtimeEvents = (
    currentEvents: RankedRealtimeEvent[],
    incomingEvents: RankedRealtimeEvent[],
): RankedRealtimeEvent[] => {
    const seen = new Set(currentEvents.map((event) => event.id));
    const merged = [...currentEvents];

    for (const event of incomingEvents) {
        if (seen.has(event.id)) {
            continue;
        }

        seen.add(event.id);
        merged.push(event);
    }

    return merged;
};

export const sortRankedRealtimeEvents = (
    events: RankedRealtimeEvent[],
): RankedRealtimeEvent[] => [...events].sort((left, right) => {
    const leftTimestamp = Date.parse(left.timestamp);
    const rightTimestamp = Date.parse(right.timestamp);

    if (!Number.isNaN(leftTimestamp) && !Number.isNaN(rightTimestamp) && leftTimestamp !== rightTimestamp) {
        return leftTimestamp - rightTimestamp;
    }

    return left.id.localeCompare(right.id);
});

export const reduceRankedRealtimeEvents = (
    events: RankedRealtimeEvent[],
): RankedRealtimeProjection => {
    const projection = createInitialRankedRealtimeProjection();

    for (const event of events) {
        if (projection.processedEventIds.includes(event.id)) {
            continue;
        }

        projection.processedEventIds.push(event.id);
        projection.latestEventName = event.event;
        projection.latestEventId = event.id;

        if (event.event === 'overview.sync') {
            const overviewState = typeof event.data?.state === 'string'
                ? event.data.state
                : null;
            const serverFull = resolveOverviewQueueServerFull(event);
            const activeMatch = resolveOverviewMatchSnapshot(event, 'active_match');
            const recentMatch = resolveOverviewMatchSnapshot(event, 'recent_match');
            const activeCurrentUser = resolveOverviewCurrentUserSnapshot(activeMatch);

            if (overviewState === 'idle' && !activeMatch && !recentMatch) {
                projection.phase = 'idle';
                projection.transportState = 'Overview potwierdza brak aktywnej kolejki i meczu.';
                clearMatchRuntimeState(projection);
                projection.queuePosition = null;
                projection.currentCapacity = null;
                projection.maxConcurrentPlayers = null;
                projection.estimatedWaitMinutes = null;
                projection.lastErrorCode = null;
                projection.lastErrorMessage = null;
                clearTerminalSummary(projection);
            }

            if (overviewState === 'queued') {
                projection.phase = 'queueing';
                projection.transportState = 'Gracz jest w kolejce i czeka na pairing z backendu.';
                clearTerminalSummary(projection);
                clearMatchRuntimeState(projection);
                projection.queuePosition = null;
                projection.currentCapacity = null;
                projection.maxConcurrentPlayers = null;
                projection.estimatedWaitMinutes = null;
                projection.lastErrorCode = null;
                projection.lastErrorMessage = null;
            }

            if (overviewState === 'server_full') {
                projection.phase = 'server_full';
                projection.transportState = 'Overview potwierdza brak wolnych slotów dla kolejnego gracza rankingu.';
                clearTerminalSummary(projection);
                clearMatchRuntimeState(projection);
                projection.queuePosition = serverFull.positionInQueue ?? projection.queuePosition;
                projection.currentCapacity = serverFull.currentCapacity ?? projection.currentCapacity;
                projection.maxConcurrentPlayers = serverFull.maxConcurrentPlayers ?? projection.maxConcurrentPlayers;
                projection.estimatedWaitMinutes = serverFull.estimatedWaitMinutes ?? projection.estimatedWaitMinutes;
                projection.lastErrorCode = serverFull.errorCode ?? projection.lastErrorCode;
                projection.lastErrorMessage = serverFull.message ?? projection.lastErrorMessage;
            }

            if (typeof activeMatch?.public_id === 'string') {
                projection.matchId = activeMatch.public_id;
            }

            if (typeof activeMatch?.matched_at === 'string') {
                projection.matchedAt = activeMatch.matched_at;
            }

            if (typeof activeMatch?.countdown_started_at === 'string') {
                projection.matchedAt = activeMatch.countdown_started_at;
            }

            if (typeof activeMatch?.countdown_seconds === 'number') {
                projection.countdownSeconds = activeMatch.countdown_seconds;
            }

            if (typeof activeMatch?.duration_seconds === 'number') {
                projection.durationSeconds = activeMatch.duration_seconds;
            }

            if (typeof activeMatch?.opponent?.username === 'string') {
                projection.opponentUsername = activeMatch.opponent.username;
            }

            if (typeof activeMatch?.opponent?.total_answered === 'number') {
                projection.opponentAnsweredCount = activeMatch.opponent.total_answered;
            }

            if (typeof activeMatch?.opponent?.correct_answers === 'number') {
                projection.opponentCorrectAnswers = activeMatch.opponent.correct_answers;
            }

            if (
                activeMatch
                && (activeMatch.state === 'matched' || activeMatch.status === 'matched')
            ) {
                projection.phase = 'matched';
                projection.transportState = 'Overview potwierdza pairing jeszcze przed kolejnym eventem queue/match.';
                clearTerminalSummary(projection);
            }

            if (
                activeMatch
                && (activeMatch.state === 'in_progress' || activeMatch.status === 'in_progress')
            ) {
                projection.phase = 'in_progress';
                projection.transportState = 'Overview potwierdza aktywny mecz, zanim dolecą kolejne eventy odpowiedzi.';
                clearTerminalSummary(projection);
                projection.reconnectDeadlineAt = null;
                projection.countdownSeconds = 0;

                if (typeof activeMatch.started_at === 'string') {
                    projection.startedAt = activeMatch.started_at;
                }
            }

            if (
                activeMatch
                && activeMatch.state === 'opponent_disconnected'
            ) {
                projection.phase = 'opponent_disconnected';
                projection.transportState = 'Overview potwierdza reconnect window po disconnectcie przeciwnika.';
                clearTerminalSummary(projection);
                projection.countdownSeconds = 0;

                if (typeof activeMatch.started_at === 'string') {
                    projection.startedAt = activeMatch.started_at;
                }

                const reconnectDeadlineAt = activeMatch.presence?.opponent?.reconnect_deadline_at;

                if (typeof reconnectDeadlineAt === 'string') {
                    projection.reconnectDeadlineAt = reconnectDeadlineAt;
                }
            }

            if (typeof activeCurrentUser.totalAnswered === 'number') {
                projection.currentUserAnsweredCount = activeCurrentUser.totalAnswered;
            }

            if (typeof activeCurrentUser.correctAnswers === 'number') {
                projection.currentUserCorrectAnswers = activeCurrentUser.correctAnswers;
            }

            const completedMatch = activeMatch ?? (
                ['idle', 'finished', 'abandoned'].includes(overviewState ?? '')
                    ? recentMatch
                    : null
            );

            if (
                completedMatch
                && (completedMatch.status === 'finished' || overviewState === 'finished')
            ) {
                projection.phase = 'finished';
                projection.transportState = 'Overview synchronizuje już zakończony mecz po odświeżeniu klienta.';
                projection.reconnectDeadlineAt = null;
                projection.countdownSeconds = 0;

                if (typeof completedMatch.public_id === 'string') {
                    projection.matchId = completedMatch.public_id;
                }

                if (typeof completedMatch.opponent?.username === 'string') {
                    projection.opponentUsername = completedMatch.opponent.username;
                }

                if (typeof completedMatch.opponent?.total_answered === 'number') {
                    projection.opponentAnsweredCount = completedMatch.opponent.total_answered;
                }

                if (typeof completedMatch.opponent?.correct_answers === 'number') {
                    projection.opponentCorrectAnswers = completedMatch.opponent.correct_answers;
                }

                if (typeof completedMatch.finished_at === 'string') {
                    projection.finishedAt = completedMatch.finished_at;
                }

                if (typeof completedMatch.reason === 'string') {
                    projection.resultReason = completedMatch.reason;
                }

                if (typeof completedMatch.outcome === 'string') {
                    projection.outcome = completedMatch.outcome;
                }

                if (typeof completedMatch.result_label === 'string') {
                    projection.resultLabel = completedMatch.result_label;
                }

                if (typeof completedMatch.current_user?.total_answered === 'number') {
                    projection.currentUserAnsweredCount = completedMatch.current_user.total_answered;
                }

                if (typeof completedMatch.current_user?.correct_answers === 'number') {
                    projection.currentUserCorrectAnswers = completedMatch.current_user.correct_answers;
                }

                if (typeof completedMatch.current_user?.elo_change === 'number') {
                    projection.currentUserEloChange = completedMatch.current_user.elo_change;
                }

                if (typeof completedMatch.total_questions === 'number') {
                    projection.resultTotalQuestions = completedMatch.total_questions;
                }

                if (typeof completedMatch.category?.name === 'string') {
                    projection.resultCategoryName = completedMatch.category.name;
                }
            }

            if (
                completedMatch
                && (completedMatch.status === 'abandoned' || overviewState === 'abandoned')
            ) {
                projection.phase = 'abandoned';
                projection.transportState = 'Overview synchronizuje mecz zamknięty jako abandoned po odświeżeniu klienta.';
                projection.reconnectDeadlineAt = null;
                projection.countdownSeconds = 0;

                if (typeof completedMatch.public_id === 'string') {
                    projection.matchId = completedMatch.public_id;
                }

                if (typeof completedMatch.opponent?.username === 'string') {
                    projection.opponentUsername = completedMatch.opponent.username;
                }

                if (typeof completedMatch.opponent?.total_answered === 'number') {
                    projection.opponentAnsweredCount = completedMatch.opponent.total_answered;
                }

                if (typeof completedMatch.opponent?.correct_answers === 'number') {
                    projection.opponentCorrectAnswers = completedMatch.opponent.correct_answers;
                }

                if (typeof completedMatch.abandoned_at === 'string') {
                    projection.finishedAt = completedMatch.abandoned_at;
                }

                if (typeof completedMatch.reason === 'string') {
                    projection.resultReason = completedMatch.reason;
                }

                if (typeof completedMatch.outcome === 'string') {
                    projection.outcome = completedMatch.outcome;
                }

                if (typeof completedMatch.result_label === 'string') {
                    projection.resultLabel = completedMatch.result_label;
                }

                if (typeof completedMatch.current_user?.total_answered === 'number') {
                    projection.currentUserAnsweredCount = completedMatch.current_user.total_answered;
                }

                if (typeof completedMatch.current_user?.correct_answers === 'number') {
                    projection.currentUserCorrectAnswers = completedMatch.current_user.correct_answers;
                }

                if (typeof completedMatch.current_user?.elo_change === 'number') {
                    projection.currentUserEloChange = completedMatch.current_user.elo_change;
                }

                if (typeof completedMatch.total_questions === 'number') {
                    projection.resultTotalQuestions = completedMatch.total_questions;
                }

                if (typeof completedMatch.category?.name === 'string') {
                    projection.resultCategoryName = completedMatch.category.name;
                }
            }

            continue;
        }

        if (event.event === 'error') {
            projection.lastErrorCode = typeof event.data?.error_code === 'string'
                ? event.data.error_code
                : projection.lastErrorCode;
            projection.lastErrorMessage = typeof event.data?.message === 'string'
                ? event.data.message
                : projection.lastErrorMessage;

            if (projection.phase === 'idle' && projection.lastErrorMessage) {
                projection.transportState = projection.lastErrorMessage;
            }

            continue;
        }

        if (event.event === 'heartbeat') {
            if (projection.phase !== 'idle') {
                projection.transportState = 'Połączenie live jest podtrzymywane heartbeatem.';
            }

            continue;
        }

        if (event.event === 'queue.left') {
            projection.transportState = 'Kanał queue potwierdza opuszczenie kolejki rankingowej.';

            if (!['finished', 'abandoned'].includes(projection.phase)) {
                projection.phase = 'idle';
                clearMatchRuntimeState(projection);
            }

            projection.queuePosition = null;
            projection.currentCapacity = null;
            projection.maxConcurrentPlayers = null;
            projection.estimatedWaitMinutes = null;
            projection.lastErrorCode = null;
            projection.lastErrorMessage = null;
            continue;
        }

        if (event.event === 'queue.queued') {
            projection.phase = 'queueing';
            projection.transportState = 'Kanał queue potwierdza aktywne oczekiwanie na pairing.';
            clearTerminalSummary(projection);
            clearMatchRuntimeState(projection);
            projection.queuePosition = null;
            projection.currentCapacity = null;
            projection.maxConcurrentPlayers = null;
            projection.estimatedWaitMinutes = null;
            projection.lastErrorCode = null;
            projection.lastErrorMessage = null;
            continue;
        }

        if (event.event === 'queue.resumed') {
            projection.phase = 'queueing';
            projection.transportState = 'Kanał queue potwierdza wznowienie kolejki po zwolnieniu slotu.';
            clearTerminalSummary(projection);
            clearMatchRuntimeState(projection);
            projection.queuePosition = null;
            projection.currentCapacity = null;
            projection.maxConcurrentPlayers = null;
            projection.estimatedWaitMinutes = null;
            projection.lastErrorCode = null;
            projection.lastErrorMessage = null;
            continue;
        }

        if (event.event === 'queue.server_full') {
            projection.phase = 'server_full';
            projection.transportState = 'Backend zgłosił brak wolnych slotów i trzyma gracza poza limitem concurrent users.';
            clearTerminalSummary(projection);
            clearMatchRuntimeState(projection);
            projection.queuePosition = typeof event.data?.position_in_queue === 'number'
                ? event.data.position_in_queue
                : projection.queuePosition;
            projection.currentCapacity = typeof event.data?.current_capacity === 'number'
                ? event.data.current_capacity
                : projection.currentCapacity;
            projection.maxConcurrentPlayers = typeof event.data?.max_concurrent_players === 'number'
                ? event.data.max_concurrent_players
                : projection.maxConcurrentPlayers;
            projection.estimatedWaitMinutes = typeof event.data?.estimated_wait_minutes === 'number'
                ? event.data.estimated_wait_minutes
                : projection.estimatedWaitMinutes;
            projection.lastErrorCode = typeof event.data?.error_code === 'string'
                ? event.data.error_code
                : projection.lastErrorCode;
            projection.lastErrorMessage = typeof event.data?.message === 'string'
                ? event.data.message
                : projection.lastErrorMessage;
            continue;
        }

        if (event.event === 'queue.matched') {
            projection.phase = 'matched';
            projection.transportState = 'Przeciwnik został znaleziony. Klient może przejść do countdownu.';
            clearTerminalSummary(projection);
            clearMatchRuntimeState(projection);
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.matchedAt = event.timestamp;
            projection.countdownSeconds = Number(event.data?.starting_in_seconds ?? 0);
            projection.opponentUsername = resolveOpponentUsername(event, projection.opponentUsername);
            projection.queuePosition = null;
            projection.currentCapacity = null;
            projection.maxConcurrentPlayers = null;
            projection.estimatedWaitMinutes = null;
            projection.lastErrorCode = null;
            projection.lastErrorMessage = null;
            continue;
        }

        if (event.event === 'match.countdown_started') {
            projection.phase = 'matched';
            projection.transportState = 'Obie strony potwierdziły gotowość. Trwa wspólny countdown przed startem meczu.';
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.matchedAt = String(
                event.data?.countdown_started_at
                ?? event.timestamp
                ?? projection.matchedAt
                ?? '',
            );
            projection.countdownSeconds = Number(
                event.data?.countdown_seconds
                ?? projection.countdownSeconds
                ?? 0,
            );
            projection.reconnectDeadlineAt = null;
            continue;
        }

        if (event.event === 'match.started') {
            projection.phase = 'in_progress';
            projection.transportState = 'Mecz wystartował i reducer pracuje już na prawdziwym event feedzie.';
            clearTerminalSummary(projection);
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.startedAt = String(event.data?.started_at ?? event.timestamp ?? projection.startedAt ?? '');
            projection.durationSeconds = Number(event.data?.duration_seconds ?? projection.durationSeconds ?? 0);
            projection.countdownSeconds = 0;
            projection.queuePosition = null;
            projection.currentCapacity = null;
            projection.maxConcurrentPlayers = null;
            projection.estimatedWaitMinutes = null;
            continue;
        }

        if (event.event === 'match.opponent_answered') {
            const opponentScore = resolveOpponentScore(event);

            projection.phase = projection.phase === 'idle' ? 'in_progress' : projection.phase;
            projection.transportState = 'Spływają eventy odpowiedzi przeciwnika.';
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.opponentUsername = resolveOpponentUsername(event, projection.opponentUsername);
            projection.opponentAnsweredCount = Number(
                opponentScore.totalAnswered
                ?? event.data?.total_answered
                ?? projection.opponentAnsweredCount,
            );
            projection.opponentCorrectAnswers = Number(
                opponentScore.correctAnswers
                ?? event.data?.correct_answers
                ?? projection.opponentCorrectAnswers,
            );
            continue;
        }

        if (event.event === 'match.opponent_disconnected') {
            projection.phase = 'opponent_disconnected';
            projection.transportState = 'Przeciwnik zniknął z heartbeatów. Trwa reconnect window.';
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.opponentUsername = resolveOpponentUsername(event, projection.opponentUsername);
            projection.reconnectDeadlineAt = String(
                event.data?.reconnect_deadline_at
                ?? event.data?.reconnect_deadline
                ?? projection.reconnectDeadlineAt
                ?? '',
            );
            continue;
        }

        if (event.event === 'match.opponent_reconnected') {
            const opponentState = resolveOpponentCurrentState(event);

            projection.phase = 'in_progress';
            projection.transportState = 'Przeciwnik wrócił do meczu.';
            projection.reconnectDeadlineAt = null;
            projection.opponentUsername = resolveOpponentUsername(event, projection.opponentUsername);
            projection.opponentAnsweredCount = Number(
                opponentState.totalAnswered ?? projection.opponentAnsweredCount,
            );
            projection.opponentCorrectAnswers = Number(
                opponentState.correctAnswers ?? projection.opponentCorrectAnswers,
            );
            continue;
        }

        if (event.event === 'match.finished') {
            const opponentSnapshot = resolveFinalOpponentSnapshot(event);
            const currentUserSnapshot = resolveFinalCurrentUserSnapshot(event);

            projection.phase = 'finished';
            projection.transportState = 'Mecz został zakończony normalnym wynikiem.';
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.finishedAt = String(event.data?.finished_at ?? event.timestamp ?? projection.finishedAt ?? '');
            projection.resultReason = String(event.data?.reason ?? '');
            projection.outcome = typeof event.data?.outcome === 'string' ? event.data.outcome : projection.outcome;
            projection.resultLabel = typeof event.data?.result_label === 'string'
                ? event.data.result_label
                : projection.resultLabel;
            projection.resultTotalQuestions = typeof event.data?.total_questions === 'number'
                ? event.data.total_questions
                : projection.resultTotalQuestions;
            projection.resultCategoryName = typeof event.data?.category === 'object'
                && event.data.category !== null
                && typeof (event.data.category as { name?: unknown }).name === 'string'
                ? (event.data.category as { name: string }).name
                : projection.resultCategoryName;
            projection.reconnectDeadlineAt = null;
            projection.currentUserAnsweredCount = Number(
                currentUserSnapshot.totalAnswered ?? projection.currentUserAnsweredCount,
            );
            projection.currentUserCorrectAnswers = Number(
                currentUserSnapshot.correctAnswers ?? projection.currentUserCorrectAnswers,
            );
            if (currentUserSnapshot.eloChange !== undefined) {
                projection.currentUserEloChange = currentUserSnapshot.eloChange;
            }
            projection.opponentUsername = opponentSnapshot.username ?? projection.opponentUsername;
            projection.opponentAnsweredCount = Number(
                opponentSnapshot.totalAnswered ?? projection.opponentAnsweredCount,
            );
            projection.opponentCorrectAnswers = Number(
                opponentSnapshot.correctAnswers ?? projection.opponentCorrectAnswers,
            );
            continue;
        }

        if (event.event === 'match.abandoned') {
            const opponentSnapshot = resolveFinalOpponentSnapshot(event);
            const currentUserSnapshot = resolveFinalCurrentUserSnapshot(event);

            projection.phase = 'abandoned';
            projection.transportState = 'Mecz został zamknięty jako abandoned.';
            projection.matchId = String(event.data?.match_id ?? projection.matchId ?? '');
            projection.finishedAt = String(
                event.data?.abandoned_at
                ?? event.data?.finished_at
                ?? event.timestamp
                ?? projection.finishedAt
                ?? '',
            );
            projection.resultReason = String(event.data?.reason ?? '');
            projection.outcome = typeof event.data?.outcome === 'string' ? event.data.outcome : projection.outcome;
            projection.resultLabel = typeof event.data?.result_label === 'string'
                ? event.data.result_label
                : projection.resultLabel;
            projection.resultTotalQuestions = typeof event.data?.total_questions === 'number'
                ? event.data.total_questions
                : projection.resultTotalQuestions;
            projection.resultCategoryName = typeof event.data?.category === 'object'
                && event.data.category !== null
                && typeof (event.data.category as { name?: unknown }).name === 'string'
                ? (event.data.category as { name: string }).name
                : projection.resultCategoryName;
            projection.reconnectDeadlineAt = null;
            projection.currentUserAnsweredCount = Number(
                currentUserSnapshot.totalAnswered ?? projection.currentUserAnsweredCount,
            );
            projection.currentUserCorrectAnswers = Number(
                currentUserSnapshot.correctAnswers ?? projection.currentUserCorrectAnswers,
            );
            if (currentUserSnapshot.eloChange !== undefined) {
                projection.currentUserEloChange = currentUserSnapshot.eloChange;
            }
            projection.opponentUsername = opponentSnapshot.username ?? projection.opponentUsername;
            projection.opponentAnsweredCount = Number(
                opponentSnapshot.totalAnswered ?? projection.opponentAnsweredCount,
            );
            projection.opponentCorrectAnswers = Number(
                opponentSnapshot.correctAnswers ?? projection.opponentCorrectAnswers,
            );
        }
    }

    return projection;
};
