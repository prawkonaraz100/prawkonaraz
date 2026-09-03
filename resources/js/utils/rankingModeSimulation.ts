export const RANKING_API_VERSION = '1.0' as const;
export const RANKING_HEARTBEAT_SECONDS = 10;
export const RANKING_DISCONNECT_TIMEOUT_SECONDS = 30;
export const RANKING_RECONNECT_GRACE_SECONDS = 25;
export const RANKING_MATCH_DURATION_SECONDS = 90;
export const RANKING_TOTAL_QUESTIONS = 40;
export const RANKING_MAX_CONCURRENT_PLAYERS = 100;

export type RankedScenarioKey =
    | 'happy_path'
    | 'disconnect_reconnect'
    | 'disconnect_abandoned'
    | 'walkover'
    | 'server_full';

export type RankedSimulationPhase =
    | 'idle'
    | 'queueing'
    | 'matched'
    | 'in_progress'
    | 'opponent_disconnected'
    | 'server_full'
    | 'finished'
    | 'abandoned';

export interface RankedScenarioOption {
    value: RankedScenarioKey;
    label: string;
    summary: string;
}

export interface RankedRealtimeEvent {
    event: string;
    id: string;
    api_version: typeof RANKING_API_VERSION;
    timestamp: string;
    data?: Record<string, unknown>;
}

export interface RankedSimulationQuestion {
    question_id: number;
    question_number: number;
    text: string;
    revealed_at: string;
}

export interface RankedSimulationPlayer {
    userId: number;
    username: string;
    elo: number;
    eloDelta: number;
    correctAnswers: number;
    totalAnswered: number;
    points: number;
    currentQuestion: number;
    sumResponseTimeMs: number;
}

export interface RankedSimulationOpponent extends RankedSimulationPlayer {
    status: 'waiting' | 'ready' | 'disconnected' | 'reconnected';
    lastAnswerCorrect: boolean | null;
    lastResponseTimeMs: number | null;
}

export interface RankedSimulationBreakdownItem {
    question_id: number;
    question_number: number;
    player1_correct: boolean;
    player1_response_time_ms: number;
    player2_correct: boolean;
    player2_response_time_ms: number;
    result: string;
}

export interface RankedSimulationResult {
    status: 'finished' | 'abandoned';
    reason: string;
    headline: string;
    winnerUsername: string | null;
    loserUsername: string | null;
    eloApplied: boolean;
    answerBreakdown: RankedSimulationBreakdownItem[];
}

export interface RankedSimulationError {
    errorCode: string;
    message: string;
}

export interface RankedSimulationState {
    apiVersion: typeof RANKING_API_VERSION;
    scenario: RankedScenarioKey;
    phase: RankedSimulationPhase;
    transportState: string;
    categoryCode: string;
    categoryLabel: string;
    matchId: string | null;
    countdownSeconds: number | null;
    queuePosition: number | null;
    currentCapacity: number | null;
    estimatedWaitMinutes: number | null;
    heartbeatSeconds: number;
    disconnectTimeoutSeconds: number;
    reconnectGraceSeconds: number;
    matchDurationSeconds: number;
    matchRemainingSeconds: number;
    revealedQuestionCount: number;
    player: RankedSimulationPlayer;
    opponent: RankedSimulationOpponent;
    questions: RankedSimulationQuestion[];
    lastHeartbeatAt: string | null;
    reconnectDeadline: string | null;
    reconnectCountdownSeconds: number | null;
    lastError: RankedSimulationError | null;
    result: RankedSimulationResult | null;
    eventLog: RankedRealtimeEvent[];
    processedEventIds: string[];
}

export interface RankedSimulationStep {
    delayMs: number;
    event?: RankedRealtimeEvent;
    apply: (state: RankedSimulationState) => RankedSimulationState;
}

export interface RankedSimulationScript {
    initialState: RankedSimulationState;
    steps: RankedSimulationStep[];
}

interface RankedSimulationOptions {
    categoryCode: string;
    categoryLabel: string;
    playerName: string;
    playerUserId?: number;
    baseTimestamp?: string;
}

interface ScenarioContext {
    baseTimestamp: string;
    matchId: string;
    player: {
        userId: number;
        username: string;
        elo: number;
    };
    opponent: {
        userId: number;
        username: string;
        elo: number;
    };
    questions: RankedSimulationQuestion[];
}

const demoPrompts = [
    'Czy kierowca musi mieć przy sobie dokument prawa jazdy?',
    'Czy wolno zatrzymać się na przejściu dla pieszych?',
    'Czy możesz wyprzedzić pojazd na skrzyżowaniu z ruchem okrężnym?',
    'Czy wolno parkować na chodniku bez zachowania miejsca dla pieszych?',
    'Czy na tym odcinku drogi masz obowiązek ustąpić pierwszeństwa autobusowi?',
    'Czy w tej sytuacji możesz zmienić pas ruchu bez użycia kierunkowskazu?',
    'Czy przed przejazdem kolejowym bez zapór trzeba zachować szczególną ostrożność?',
    'Czy znak widoczny przed Tobą oznacza zakaz zawracania?',
];

export const rankedScenarioOptions: RankedScenarioOption[] = [
    {
        value: 'happy_path',
        label: 'Happy path',
        summary: 'Szybkie sparowanie, stabilny mecz i zwykły wynik po 90 sekundach.',
    },
    {
        value: 'disconnect_reconnect',
        label: 'Reconnect',
        summary: 'Przeciwnik wypada z meczu i wraca przed deadlinem reconnectu.',
    },
    {
        value: 'disconnect_abandoned',
        label: 'Disconnect > 25s',
        summary: 'Brak powrotu przeciwnika kończy mecz jako abandoned.',
    },
    {
        value: 'walkover',
        label: 'Walkover',
        summary: 'Trzy timeouty przeciwnika zamykają mecz walkowerem.',
    },
    {
        value: 'server_full',
        label: 'Server full',
        summary: 'Queue pokazuje błąd pojemności, a potem wznawia matchmaking.',
    },
];

const cloneState = <T>(value: T): T => JSON.parse(JSON.stringify(value)) as T;

const isoAt = (baseTimestamp: string, offsetSeconds: number): string =>
    new Date(new Date(baseTimestamp).getTime() + (offsetSeconds * 1000)).toISOString();

const buildQuestions = (categoryCode: string, baseTimestamp: string): RankedSimulationQuestion[] =>
    Array.from({ length: RANKING_TOTAL_QUESTIONS }, (_, index) => ({
        question_id: 1001 + index,
        question_number: index + 1,
        text: `${demoPrompts[index % demoPrompts.length]} (${categoryCode})`,
        revealed_at: isoAt(baseTimestamp, index * 2),
    }));

const createScenarioContext = (scenario: RankedScenarioKey, options: RankedSimulationOptions): ScenarioContext => {
    const baseTimestamp = options.baseTimestamp ?? '2026-04-09T12:00:00Z';

    return {
        baseTimestamp,
        matchId: `match_${options.categoryCode.toLowerCase()}_${scenario}`,
        player: {
            userId: options.playerUserId ?? 123,
            username: options.playerName || 'Ty',
            elo: 1540,
        },
        opponent: {
            userId: 456,
            username: 'player_b',
            elo: 1550,
        },
        questions: buildQuestions(options.categoryCode, baseTimestamp),
    };
};

const eventId = (name: string, suffix: string): string => `evt_${name}_${suffix}`;

const createEvent = (
    event: string,
    timestamp: string,
    suffix: string,
    data?: Record<string, unknown>,
): RankedRealtimeEvent => ({
    event,
    id: eventId(event.replaceAll('.', '_'), suffix),
    api_version: RANKING_API_VERSION,
    timestamp,
    data,
});

const evolveState = (
    state: RankedSimulationState,
    event: RankedRealtimeEvent | undefined,
    mutator: (draft: RankedSimulationState) => void,
): RankedSimulationState => {
    const next = cloneState(state);

    if (event && !next.processedEventIds.includes(event.id)) {
        next.processedEventIds.push(event.id);
        next.eventLog.push(event);

        if (event.event === 'heartbeat') {
            next.lastHeartbeatAt = event.timestamp;
        }

        if (event.event === 'error') {
            next.lastError = {
                errorCode: String(event.data?.error_code ?? 'SERVER_ERROR'),
                message: String(event.data?.message ?? 'Unexpected realtime error.'),
            };
        }
    }

    mutator(next);

    return next;
};

const step = (
    delayMs: number,
    event: RankedRealtimeEvent | undefined,
    mutator: (draft: RankedSimulationState) => void,
): RankedSimulationStep => ({
    delayMs,
    event,
    apply: (state) => evolveState(state, event, mutator),
});

const breakdownSample = (): RankedSimulationBreakdownItem[] => [
    {
        question_id: 1001,
        question_number: 1,
        player1_correct: true,
        player1_response_time_ms: 1800,
        player2_correct: true,
        player2_response_time_ms: 2100,
        result: 'both_correct',
    },
    {
        question_id: 1002,
        question_number: 2,
        player1_correct: true,
        player1_response_time_ms: 2200,
        player2_correct: false,
        player2_response_time_ms: 3500,
        result: 'player1_correct',
    },
    {
        question_id: 1003,
        question_number: 3,
        player1_correct: false,
        player1_response_time_ms: 3100,
        player2_correct: true,
        player2_response_time_ms: 2400,
        result: 'player2_correct',
    },
];

export const createInitialRankingSimulationState = (
    scenario: RankedScenarioKey,
    options: RankedSimulationOptions,
): RankedSimulationState => {
    const context = createScenarioContext(scenario, options);

    return {
        apiVersion: RANKING_API_VERSION,
        scenario,
        phase: 'idle',
        transportState: 'Tryb gotowy do uruchomienia.',
        categoryCode: options.categoryCode,
        categoryLabel: options.categoryLabel,
        matchId: null,
        countdownSeconds: null,
        queuePosition: null,
        currentCapacity: null,
        estimatedWaitMinutes: null,
        heartbeatSeconds: RANKING_HEARTBEAT_SECONDS,
        disconnectTimeoutSeconds: RANKING_DISCONNECT_TIMEOUT_SECONDS,
        reconnectGraceSeconds: RANKING_RECONNECT_GRACE_SECONDS,
        matchDurationSeconds: RANKING_MATCH_DURATION_SECONDS,
        matchRemainingSeconds: RANKING_MATCH_DURATION_SECONDS,
        revealedQuestionCount: 0,
        player: {
            userId: context.player.userId,
            username: context.player.username,
            elo: context.player.elo,
            eloDelta: 0,
            correctAnswers: 0,
            totalAnswered: 0,
            points: 0,
            currentQuestion: 0,
            sumResponseTimeMs: 0,
        },
        opponent: {
            userId: context.opponent.userId,
            username: context.opponent.username,
            elo: context.opponent.elo,
            eloDelta: 0,
            correctAnswers: 0,
            totalAnswered: 0,
            points: 0,
            currentQuestion: 0,
            sumResponseTimeMs: 0,
            status: 'waiting',
            lastAnswerCorrect: null,
            lastResponseTimeMs: null,
        },
        questions: context.questions,
        lastHeartbeatAt: null,
        reconnectDeadline: null,
        reconnectCountdownSeconds: null,
        lastError: null,
        result: null,
        eventLog: [],
        processedEventIds: [],
    };
};

const startState = (scenario: RankedScenarioKey, options: RankedSimulationOptions): RankedSimulationState => {
    const state = createInitialRankingSimulationState(scenario, options);

    state.phase = 'queueing';
    state.transportState = 'Szukamy przeciwnika w kolejce rankingowej.';

    return state;
};

const buildStartedEvent = (context: ScenarioContext): RankedRealtimeEvent =>
    createEvent(
        'match.started',
        context.baseTimestamp,
        `${context.matchId}_started`,
        {
            match_id: context.matchId,
            status: 'in_progress',
            started_at: context.baseTimestamp,
            duration_seconds: RANKING_MATCH_DURATION_SECONDS,
            total_questions: RANKING_TOTAL_QUESTIONS,
            player1: {
                user_id: context.player.userId,
                username: context.player.username,
                elo: context.player.elo,
            },
            player2: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
                elo: context.opponent.elo,
            },
            questions: context.questions,
        },
    );

const buildFinishedEvent = (
    context: ScenarioContext,
    timestamp: string,
    reason: 'more_correct_answers' | 'faster_time' | 'draw' | 'walkover',
): RankedRealtimeEvent =>
    createEvent(
        'match.finished',
        timestamp,
        `${context.matchId}_finished`,
        {
            match_id: context.matchId,
            status: 'finished',
            finished_at: timestamp,
            winner: {
                user_id: context.player.userId,
                username: context.player.username,
            },
            loser: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            result: 'player_a_won',
            reason,
            scores: {
                player1: {
                    user_id: context.player.userId,
                    username: context.player.username,
                    correct_answers: 31,
                    total_answered: 40,
                    points: 31,
                    sum_response_time_ms: 77100,
                    elo_before: context.player.elo,
                    elo_after: context.player.elo + 12,
                    elo_change: 12,
                },
                player2: {
                    user_id: context.opponent.userId,
                    username: context.opponent.username,
                    correct_answers: 28,
                    total_answered: 40,
                    points: 28,
                    sum_response_time_ms: 80400,
                    elo_before: context.opponent.elo,
                    elo_after: context.opponent.elo - 12,
                    elo_change: -12,
                },
            },
            answer_breakdown: breakdownSample(),
        },
    );

const buildAbandonedEvent = (
    context: ScenarioContext,
    timestamp: string,
    reason: 'opponent_timeout_3_strikes' | 'opponent_disconnect_no_return' | 'queue_timeout',
): RankedRealtimeEvent =>
    createEvent(
        'match.abandoned',
        timestamp,
        `${context.matchId}_abandoned_${reason}`,
        {
            match_id: context.matchId,
            status: 'abandoned',
            abandoned_at: timestamp,
            reason,
            winner_by_default: {
                user_id: context.player.userId,
                username: context.player.username,
            },
            loser: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            elo_applied: true,
            elo_changes: {
                player1: {
                    elo_before: context.player.elo,
                    elo_after: context.player.elo + 10,
                    elo_change: 10,
                },
                player2: {
                    elo_before: context.opponent.elo,
                    elo_after: context.opponent.elo - 10,
                    elo_change: -10,
                },
            },
        },
    );

const buildHappyPathSteps = (context: ScenarioContext): RankedSimulationStep[] => {
    const queueMatchedAt = isoAt(context.baseTimestamp, -5);
    const startedEvent = buildStartedEvent(context);
    const heartbeatAt = isoAt(context.baseTimestamp, 10);
    const opponentAnsweredAt = isoAt(context.baseTimestamp, 18);
    const secondOpponentAnsweredAt = isoAt(context.baseTimestamp, 38);
    const secondHeartbeatAt = isoAt(context.baseTimestamp, 40);
    const finishedAt = isoAt(context.baseTimestamp, 90);

    const queueMatchedEvent = createEvent(
        'queue.matched',
        queueMatchedAt,
        `${context.matchId}_matched`,
        {
            user_id: context.player.userId,
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
                elo: context.opponent.elo,
            },
            starting_in_seconds: 5,
        },
    );

    const firstOpponentAnswered = createEvent(
        'match.opponent_answered',
        opponentAnsweredAt,
        `${context.matchId}_q5`,
        {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            current_question: 5,
            opponent_score: {
                correct_answers: 4,
                total_answered: 5,
                points: 4,
            },
            last_answer: {
                question_id: 1005,
                answer_correct: true,
                response_time_ms: 3200,
            },
            answered_at: opponentAnsweredAt,
        },
    );

    const secondOpponentAnswered = createEvent(
        'match.opponent_answered',
        secondOpponentAnsweredAt,
        `${context.matchId}_q17`,
        {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            current_question: 17,
            opponent_score: {
                correct_answers: 13,
                total_answered: 17,
                points: 13,
            },
            last_answer: {
                question_id: 1017,
                answer_correct: false,
                response_time_ms: 4600,
            },
            answered_at: secondOpponentAnsweredAt,
        },
    );

    return [
        step(900, queueMatchedEvent, (draft) => {
            draft.phase = 'matched';
            draft.transportState = 'Przeciwnik znaleziony. Start za chwilę.';
            draft.matchId = context.matchId;
            draft.countdownSeconds = 5;
            draft.opponent.status = 'ready';
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 4;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 3;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 2;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 1;
        }),
        step(650, startedEvent, (draft) => {
            draft.phase = 'in_progress';
            draft.transportState = 'Mecz trwa. Frontend odsłania pytania lokalnie.';
            draft.countdownSeconds = 0;
            draft.revealedQuestionCount = 8;
            draft.matchRemainingSeconds = 90;
            draft.player.currentQuestion = 6;
            draft.player.totalAnswered = 6;
            draft.player.correctAnswers = 5;
            draft.player.points = 5;
            draft.player.sumResponseTimeMs = 12600;
            draft.opponent.status = 'ready';
        }),
        step(700, createEvent('heartbeat', heartbeatAt, `${context.matchId}_hb1`), (draft) => {
            draft.matchRemainingSeconds = 80;
        }),
        step(900, undefined, (draft) => {
            draft.revealedQuestionCount = 16;
            draft.player.currentQuestion = 14;
            draft.player.totalAnswered = 14;
            draft.player.correctAnswers = 12;
            draft.player.points = 12;
            draft.player.sumResponseTimeMs = 29400;
            draft.matchRemainingSeconds = 68;
        }),
        step(950, firstOpponentAnswered, (draft) => {
            draft.opponent.currentQuestion = 5;
            draft.opponent.totalAnswered = 5;
            draft.opponent.correctAnswers = 4;
            draft.opponent.points = 4;
            draft.opponent.lastAnswerCorrect = true;
            draft.opponent.lastResponseTimeMs = 3200;
        }),
        step(900, undefined, (draft) => {
            draft.revealedQuestionCount = 26;
            draft.player.currentQuestion = 24;
            draft.player.totalAnswered = 24;
            draft.player.correctAnswers = 20;
            draft.player.points = 20;
            draft.player.sumResponseTimeMs = 54800;
            draft.matchRemainingSeconds = 46;
        }),
        step(900, secondOpponentAnswered, (draft) => {
            draft.opponent.currentQuestion = 17;
            draft.opponent.totalAnswered = 17;
            draft.opponent.correctAnswers = 13;
            draft.opponent.points = 13;
            draft.opponent.lastAnswerCorrect = false;
            draft.opponent.lastResponseTimeMs = 4600;
        }),
        step(650, createEvent('heartbeat', secondHeartbeatAt, `${context.matchId}_hb2`), (draft) => {
            draft.revealedQuestionCount = 34;
            draft.matchRemainingSeconds = 30;
        }),
        step(1000, buildFinishedEvent(context, finishedAt, 'more_correct_answers'), (draft) => {
            draft.phase = 'finished';
            draft.transportState = 'Mecz zakończony zwykłym wynikiem.';
            draft.matchRemainingSeconds = 0;
            draft.revealedQuestionCount = 40;
            draft.player.currentQuestion = 40;
            draft.player.totalAnswered = 40;
            draft.player.correctAnswers = 31;
            draft.player.points = 31;
            draft.player.sumResponseTimeMs = 77100;
            draft.player.eloDelta = 12;
            draft.opponent.currentQuestion = 40;
            draft.opponent.totalAnswered = 40;
            draft.opponent.correctAnswers = 28;
            draft.opponent.points = 28;
            draft.opponent.sumResponseTimeMs = 80400;
            draft.opponent.eloDelta = -12;
            draft.result = {
                status: 'finished',
                reason: 'more_correct_answers',
                headline: 'Wygrywasz większą liczbą poprawnych odpowiedzi.',
                winnerUsername: context.player.username,
                loserUsername: context.opponent.username,
                eloApplied: true,
                answerBreakdown: breakdownSample(),
            };
        }),
    ];
};

const buildDisconnectReconnectSteps = (context: ScenarioContext): RankedSimulationStep[] => {
    const base = buildHappyPathSteps(context).slice(0, 8);
    const disconnectedAt = isoAt(context.baseTimestamp, 25);
    const reconnectDeadline = isoAt(context.baseTimestamp, 50);
    const reconnectedAt = isoAt(context.baseTimestamp, 32);
    const finishedAt = isoAt(context.baseTimestamp, 90);

    const disconnectedEvent = createEvent(
        'match.opponent_disconnected',
        disconnectedAt,
        `${context.matchId}_disconnect`,
        {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            disconnected_at: disconnectedAt,
            reconnect_deadline: reconnectDeadline,
            timeout_seconds: RANKING_RECONNECT_GRACE_SECONDS,
        },
    );

    const reconnectedEvent = createEvent(
        'match.opponent_reconnected',
        reconnectedAt,
        `${context.matchId}_reconnect`,
        {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            reconnected_at: reconnectedAt,
            opponent_current_state: {
                current_question: 5,
                correct_answers: 4,
                points: 4,
            },
        },
    );

    return [
        ...base,
        step(1000, disconnectedEvent, (draft) => {
            draft.phase = 'opponent_disconnected';
            draft.transportState = 'Przeciwnik utracił połączenie. Czekamy na powrót.';
            draft.opponent.status = 'disconnected';
            draft.reconnectDeadline = reconnectDeadline;
            draft.reconnectCountdownSeconds = RANKING_RECONNECT_GRACE_SECONDS;
            draft.matchRemainingSeconds = 58;
        }),
        step(800, undefined, (draft) => {
            draft.reconnectCountdownSeconds = 14;
        }),
        step(900, reconnectedEvent, (draft) => {
            draft.phase = 'in_progress';
            draft.transportState = 'Przeciwnik wrócił. Mecz trwa dalej.';
            draft.opponent.status = 'reconnected';
            draft.reconnectDeadline = null;
            draft.reconnectCountdownSeconds = null;
            draft.matchRemainingSeconds = 46;
            draft.revealedQuestionCount = 28;
            draft.player.currentQuestion = 23;
            draft.player.totalAnswered = 23;
            draft.player.correctAnswers = 18;
            draft.player.points = 18;
            draft.player.sumResponseTimeMs = 50200;
            draft.opponent.currentQuestion = 18;
            draft.opponent.totalAnswered = 18;
            draft.opponent.correctAnswers = 14;
            draft.opponent.points = 14;
        }),
        step(1000, buildFinishedEvent(context, finishedAt, 'faster_time'), (draft) => {
            draft.phase = 'finished';
            draft.transportState = 'Mecz zamknięty po reconnectcie przeciwnika.';
            draft.matchRemainingSeconds = 0;
            draft.revealedQuestionCount = 40;
            draft.player.currentQuestion = 40;
            draft.player.totalAnswered = 40;
            draft.player.correctAnswers = 30;
            draft.player.points = 30;
            draft.player.sumResponseTimeMs = 73500;
            draft.player.eloDelta = 8;
            draft.opponent.currentQuestion = 40;
            draft.opponent.totalAnswered = 40;
            draft.opponent.correctAnswers = 30;
            draft.opponent.points = 30;
            draft.opponent.sumResponseTimeMs = 79200;
            draft.opponent.eloDelta = -8;
            draft.result = {
                status: 'finished',
                reason: 'faster_time',
                headline: 'Wygrywasz czasem reakcji po remisie punktowym.',
                winnerUsername: context.player.username,
                loserUsername: context.opponent.username,
                eloApplied: true,
                answerBreakdown: breakdownSample(),
            };
        }),
    ];
};

const buildDisconnectAbandonedSteps = (context: ScenarioContext): RankedSimulationStep[] => {
    const base = buildHappyPathSteps(context).slice(0, 8);
    const disconnectedAt = isoAt(context.baseTimestamp, 25);
    const reconnectDeadline = isoAt(context.baseTimestamp, 50);
    const abandonedAt = isoAt(context.baseTimestamp, 56);

    const disconnectedEvent = createEvent(
        'match.opponent_disconnected',
        disconnectedAt,
        `${context.matchId}_disconnect_noreturn`,
        {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            disconnected_at: disconnectedAt,
            reconnect_deadline: reconnectDeadline,
            timeout_seconds: RANKING_RECONNECT_GRACE_SECONDS,
        },
    );

    return [
        ...base,
        step(1000, disconnectedEvent, (draft) => {
            draft.phase = 'opponent_disconnected';
            draft.transportState = 'Przeciwnik zniknął z meczu. Deadline reconnectu biegnie.';
            draft.opponent.status = 'disconnected';
            draft.reconnectDeadline = reconnectDeadline;
            draft.reconnectCountdownSeconds = RANKING_RECONNECT_GRACE_SECONDS;
            draft.matchRemainingSeconds = 58;
        }),
        step(850, undefined, (draft) => {
            draft.reconnectCountdownSeconds = 8;
        }),
        step(1000, buildAbandonedEvent(context, abandonedAt, 'opponent_disconnect_no_return'), (draft) => {
            draft.phase = 'abandoned';
            draft.transportState = 'Mecz zakończony jako abandoned z powodu braku powrotu przeciwnika.';
            draft.matchRemainingSeconds = 0;
            draft.reconnectDeadline = null;
            draft.reconnectCountdownSeconds = null;
            draft.opponent.status = 'disconnected';
            draft.player.eloDelta = 10;
            draft.opponent.eloDelta = -10;
            draft.result = {
                status: 'abandoned',
                reason: 'opponent_disconnect_no_return',
                headline: 'Wygrana walkowerem po disconnectcie bez powrotu.',
                winnerUsername: context.player.username,
                loserUsername: context.opponent.username,
                eloApplied: true,
                answerBreakdown: breakdownSample(),
            };
        }),
    ];
};

const buildWalkoverSteps = (context: ScenarioContext): RankedSimulationStep[] => {
    const queueMatchedEvent = createEvent(
        'queue.matched',
        isoAt(context.baseTimestamp, -5),
        `${context.matchId}_matched_walkover`,
        {
            user_id: context.player.userId,
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
                elo: context.opponent.elo,
            },
            starting_in_seconds: 5,
        },
    );

    return [
        step(800, queueMatchedEvent, (draft) => {
            draft.phase = 'matched';
            draft.transportState = 'Szybkie sparowanie. Start meczu za chwilę.';
            draft.matchId = context.matchId;
            draft.countdownSeconds = 3;
            draft.opponent.status = 'ready';
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 2;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 1;
        }),
        step(650, buildStartedEvent(context), (draft) => {
            draft.phase = 'in_progress';
            draft.transportState = 'Mecz wystartował, ale przeciwnik ma problemy z odpowiedziami.';
            draft.countdownSeconds = 0;
            draft.revealedQuestionCount = 10;
            draft.matchRemainingSeconds = 84;
            draft.player.currentQuestion = 7;
            draft.player.totalAnswered = 7;
            draft.player.correctAnswers = 6;
            draft.player.points = 6;
            draft.player.sumResponseTimeMs = 14600;
        }),
        step(900, createEvent('heartbeat', isoAt(context.baseTimestamp, 10), `${context.matchId}_hb_walkover`), (draft) => {
            draft.matchRemainingSeconds = 72;
            draft.revealedQuestionCount = 18;
        }),
        step(900, undefined, (draft) => {
            draft.opponent.currentQuestion = 3;
            draft.opponent.totalAnswered = 3;
            draft.opponent.correctAnswers = 0;
            draft.opponent.points = 0;
            draft.opponent.lastAnswerCorrect = false;
            draft.opponent.lastResponseTimeMs = 15000;
            draft.player.currentQuestion = 15;
            draft.player.totalAnswered = 15;
            draft.player.correctAnswers = 12;
            draft.player.points = 12;
            draft.player.sumResponseTimeMs = 31800;
            draft.matchRemainingSeconds = 60;
        }),
        step(950, buildAbandonedEvent(context, isoAt(context.baseTimestamp, 24), 'opponent_timeout_3_strikes'), (draft) => {
            draft.phase = 'abandoned';
            draft.transportState = 'Mecz zamknięty walkowerem po trzech timeoutach przeciwnika.';
            draft.matchRemainingSeconds = 0;
            draft.player.eloDelta = 10;
            draft.opponent.eloDelta = -10;
            draft.result = {
                status: 'abandoned',
                reason: 'opponent_timeout_3_strikes',
                headline: 'Walkover: przeciwnik przekroczył limit timeoutów.',
                winnerUsername: context.player.username,
                loserUsername: context.opponent.username,
                eloApplied: true,
                answerBreakdown: breakdownSample(),
            };
        }),
    ];
};

const buildServerFullSteps = (context: ScenarioContext): RankedSimulationStep[] => {
    const errorAt = isoAt(context.baseTimestamp, -8);
    const fullAt = isoAt(context.baseTimestamp, -7);
    const queueMatchedAt = isoAt(context.baseTimestamp, -1);

    const errorEvent = createEvent(
        'error',
        errorAt,
        `${context.player.userId}_server_full`,
        {
            error_code: 'SERVER_FULL',
            message: 'Server full (100/100). Waiting for space...',
            details: {
                user_id: context.player.userId,
                suggestion: 'Stay in queue until a slot becomes available.',
            },
        },
    );

    const fullEvent = createEvent(
        'queue.server_full',
        fullAt,
        `${context.player.userId}_queue_full`,
        {
            user_id: context.player.userId,
            position_in_queue: 105,
            current_capacity: RANKING_MAX_CONCURRENT_PLAYERS,
            message: 'Server full (100/100). Waiting for space...',
            estimated_wait_minutes: 3,
        },
    );

    const queueMatchedEvent = createEvent(
        'queue.matched',
        queueMatchedAt,
        `${context.matchId}_matched_after_full`,
        {
            user_id: context.player.userId,
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
                elo: context.opponent.elo,
            },
            starting_in_seconds: 5,
        },
    );

    return [
        step(700, errorEvent, (draft) => {
            draft.phase = 'server_full';
            draft.transportState = 'Serwer jest pełny. Użytkownik czeka poza aktywnym meczem.';
        }),
        step(700, fullEvent, (draft) => {
            draft.phase = 'server_full';
            draft.queuePosition = 105;
            draft.currentCapacity = RANKING_MAX_CONCURRENT_PLAYERS;
            draft.estimatedWaitMinutes = 3;
        }),
        step(900, undefined, (draft) => {
            draft.transportState = 'Czekamy, aż zwolni się slot w limicie concurrent users.';
            draft.queuePosition = 102;
        }),
        step(1000, queueMatchedEvent, (draft) => {
            draft.phase = 'matched';
            draft.transportState = 'Slot się zwolnił. Przeciwnik znaleziony.';
            draft.lastError = null;
            draft.matchId = context.matchId;
            draft.countdownSeconds = 4;
            draft.queuePosition = null;
            draft.currentCapacity = null;
            draft.estimatedWaitMinutes = null;
            draft.opponent.status = 'ready';
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 3;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 2;
        }),
        step(500, undefined, (draft) => {
            draft.countdownSeconds = 1;
        }),
        step(650, buildStartedEvent(context), (draft) => {
            draft.phase = 'in_progress';
            draft.transportState = 'Mecz wystartował po wyjściu z kolejki server full.';
            draft.countdownSeconds = 0;
            draft.revealedQuestionCount = 8;
            draft.matchRemainingSeconds = 88;
            draft.player.currentQuestion = 5;
            draft.player.totalAnswered = 5;
            draft.player.correctAnswers = 5;
            draft.player.points = 5;
            draft.player.sumResponseTimeMs = 10800;
        }),
        step(850, createEvent('match.opponent_answered', isoAt(context.baseTimestamp, 14), `${context.matchId}_q4_after_full`, {
            match_id: context.matchId,
            opponent: {
                user_id: context.opponent.userId,
                username: context.opponent.username,
            },
            current_question: 4,
            opponent_score: {
                correct_answers: 3,
                total_answered: 4,
                points: 3,
            },
            last_answer: {
                question_id: 1004,
                answer_correct: false,
                response_time_ms: 4100,
            },
            answered_at: isoAt(context.baseTimestamp, 14),
        }), (draft) => {
            draft.opponent.currentQuestion = 4;
            draft.opponent.totalAnswered = 4;
            draft.opponent.correctAnswers = 3;
            draft.opponent.points = 3;
            draft.opponent.lastAnswerCorrect = false;
            draft.opponent.lastResponseTimeMs = 4100;
            draft.matchRemainingSeconds = 66;
        }),
        step(950, buildFinishedEvent(context, isoAt(context.baseTimestamp, 90), 'more_correct_answers'), (draft) => {
            draft.phase = 'finished';
            draft.transportState = 'Mecz zakończony po wyjściu z kolejki server full.';
            draft.matchRemainingSeconds = 0;
            draft.revealedQuestionCount = 40;
            draft.player.currentQuestion = 40;
            draft.player.totalAnswered = 40;
            draft.player.correctAnswers = 33;
            draft.player.points = 33;
            draft.player.sumResponseTimeMs = 74900;
            draft.player.eloDelta = 11;
            draft.opponent.currentQuestion = 40;
            draft.opponent.totalAnswered = 40;
            draft.opponent.correctAnswers = 29;
            draft.opponent.points = 29;
            draft.opponent.sumResponseTimeMs = 81200;
            draft.opponent.eloDelta = -11;
            draft.result = {
                status: 'finished',
                reason: 'more_correct_answers',
                headline: 'Po oczekiwaniu w kolejce kończysz mecz zwykłym zwycięstwem.',
                winnerUsername: context.player.username,
                loserUsername: context.opponent.username,
                eloApplied: true,
                answerBreakdown: breakdownSample(),
            };
        }),
    ];
};

const scenarioStepsByKey: Record<RankedScenarioKey, (context: ScenarioContext) => RankedSimulationStep[]> = {
    happy_path: buildHappyPathSteps,
    disconnect_reconnect: buildDisconnectReconnectSteps,
    disconnect_abandoned: buildDisconnectAbandonedSteps,
    walkover: buildWalkoverSteps,
    server_full: buildServerFullSteps,
};

export const createRankingSimulationScript = (
    scenario: RankedScenarioKey,
    options: RankedSimulationOptions,
): RankedSimulationScript => {
    const context = createScenarioContext(scenario, options);

    return {
        initialState: startState(scenario, options),
        steps: scenarioStepsByKey[scenario](context),
    };
};
