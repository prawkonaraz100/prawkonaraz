import {
    createInitialRankedRealtimeProjection,
    mergeRankedRealtimeEvents,
    reduceRankedRealtimeEvents,
    sortRankedRealtimeEvents,
} from '@/utils/rankedRealtimeEventReducer';
import type { RankedRealtimeEvent } from '@/utils/rankingModeSimulation';
import { describe, expect, it } from 'vitest';

const event = (
    name: string,
    id: string,
    timestamp: string,
    data: Record<string, unknown> = {},
): RankedRealtimeEvent => ({
    event: name,
    id,
    api_version: '1.0',
    timestamp,
    data,
});

describe('rankedRealtimeEventReducer', () => {
    it('reduces queue, start and opponent answer events into a live projection', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.matched', 'evt-1', '2026-04-21T20:00:00Z', {
                match_id: 'match-1',
                starting_in_seconds: 5,
                opponent: {
                    username: 'Player Two',
                },
            }),
            event('match.started', 'evt-2', '2026-04-21T20:00:05Z', {
                match_id: 'match-1',
                started_at: '2026-04-21T20:00:05Z',
                duration_seconds: 90,
            }),
            event('match.opponent_answered', 'evt-3', '2026-04-21T20:00:12Z', {
                match_id: 'match-1',
                opponent: {
                    username: 'Player Two',
                },
                opponent_score: {
                    total_answered: 4,
                    correct_answers: 3,
                    points: 3,
                },
            }),
        ]);

        expect(projection.phase).toBe('in_progress');
        expect(projection.matchId).toBe('match-1');
        expect(projection.matchedAt).toBe('2026-04-21T20:00:00Z');
        expect(projection.startedAt).toBe('2026-04-21T20:00:05Z');
        expect(projection.durationSeconds).toBe(90);
        expect(projection.opponentUsername).toBe('Player Two');
        expect(projection.opponentAnsweredCount).toBe(4);
        expect(projection.opponentCorrectAnswers).toBe(3);
        expect(projection.processedEventIds).toEqual(['evt-1', 'evt-2', 'evt-3']);
    });

    it('returns to in_progress when disconnect is followed by reconnect', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.started', 'evt-1', '2026-04-21T20:00:00Z', {
                match_id: 'match-2',
                started_at: '2026-04-21T20:00:00Z',
            }),
            event('match.opponent_disconnected', 'evt-2', '2026-04-21T20:00:10Z', {
                match_id: 'match-2',
                reconnect_deadline: '2026-04-21T20:00:35Z',
                opponent: {
                    username: 'Player B',
                },
            }),
            event('match.opponent_reconnected', 'evt-3', '2026-04-21T20:00:18Z', {
                match_id: 'match-2',
                reconnected_at: '2026-04-21T20:00:18Z',
                opponent: {
                    username: 'Player B',
                },
                opponent_current_state: {
                    current_question: 5,
                    correct_answers: 4,
                    points: 4,
                },
            }),
        ]);

        expect(projection.phase).toBe('in_progress');
        expect(projection.reconnectDeadlineAt).toBeNull();
        expect(projection.latestEventName).toBe('match.opponent_reconnected');
        expect(projection.opponentAnsweredCount).toBe(4);
        expect(projection.opponentCorrectAnswers).toBe(4);
    });

    it('keeps matched phase during backend ready countdown and stores the shared timer anchor', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.matched', 'evt-1', '2026-04-21T20:00:00Z', {
                match_id: 'match-3',
                starting_in_seconds: 0,
                opponent: {
                    username: 'Player Ready',
                },
            }),
            event('match.countdown_started', 'evt-2', '2026-04-21T20:00:04Z', {
                match_id: 'match-3',
                countdown_started_at: '2026-04-21T20:00:04Z',
                countdown_seconds: 3,
                starts_at: '2026-04-21T20:00:07Z',
            }),
        ]);

        expect(projection.phase).toBe('matched');
        expect(projection.matchId).toBe('match-3');
        expect(projection.matchedAt).toBe('2026-04-21T20:00:04Z');
        expect(projection.countdownSeconds).toBe(3);
        expect(projection.transportState).toContain('countdown');
    });

    it('uses overview sync events to project queued state before queue matched arrives', () => {
        const projection = reduceRankedRealtimeEvents([
            event('overview.sync', 'evt-1', '2026-04-21T20:00:00Z', {
                state: 'queued',
                queue: {
                    id: 15,
                    status: 'queued',
                    joined_at: '2026-04-21T20:00:00Z',
                },
            }),
        ]);

        expect(projection.phase).toBe('queueing');
        expect(projection.transportState).toContain('kolejce');
        expect(projection.lastErrorCode).toBeNull();
    });

    it('returns to idle from overview sync when queue and active match are both gone', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.queued', 'evt-queued', '2026-04-21T20:00:00Z', {
                queue_entry_id: 15,
                joined_at: '2026-04-21T20:00:00Z',
                status: 'queued',
            }),
            event('overview.sync', 'evt-idle', '2026-04-21T20:00:05Z', {
                state: 'idle',
                queue: null,
                active_match: null,
                recent_match: null,
            }),
        ]);

        expect(projection.phase).toBe('idle');
        expect(projection.transportState).toContain('brak aktywnej kolejki');
        expect(projection.matchId).toBeNull();
        expect(projection.opponentUsername).toBeNull();
        expect(projection.queuePosition).toBeNull();
        expect(projection.currentCapacity).toBeNull();
        expect(projection.lastErrorCode).toBeNull();
        expect(projection.currentUserAnsweredCount).toBe(0);
        expect(projection.currentUserCorrectAnswers).toBe(0);
    });

    it('treats queue.left as a first-class live leave event for queue flow', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.server_full', 'evt-server-full', '2026-04-21T20:00:00Z', {
                position_in_queue: 101,
                current_capacity: 100,
                max_concurrent_players: 100,
                estimated_wait_minutes: 3,
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            }),
            event('queue.left', 'evt-left', '2026-04-21T20:00:05Z', {
                queue_entry_id: 15,
                previous_status: 'server_full',
                left_at: '2026-04-21T20:00:05Z',
            }),
        ]);

        expect(projection.phase).toBe('idle');
        expect(projection.transportState).toContain('opu');
        expect(projection.queuePosition).toBeNull();
        expect(projection.currentCapacity).toBeNull();
        expect(projection.maxConcurrentPlayers).toBeNull();
        expect(projection.estimatedWaitMinutes).toBeNull();
        expect(projection.lastErrorCode).toBeNull();
        expect(projection.lastErrorMessage).toBeNull();
    });

    it('does not erase terminal match summary when queue.left follows a finished snapshot', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.finished', 'evt-finished', '2026-04-21T19:59:50Z', {
                match_id: 'match-prev',
                finished_at: '2026-04-21T19:59:50Z',
                total_questions: 40,
                reason: 'more_correct_answers',
                category: {
                    name: 'Prawo jazdy kat. B',
                },
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 40,
                    correct_answers: 36,
                    elo_change: 8,
                },
                opponent: {
                    username: 'Prev Player',
                    total_answered: 40,
                    correct_answers: 34,
                },
            }),
            event('queue.left', 'evt-left', '2026-04-21T20:00:05Z', {
                queue_entry_id: 15,
                previous_status: 'queued',
                left_at: '2026-04-21T20:00:05Z',
            }),
        ]);

        expect(projection.phase).toBe('finished');
        expect(projection.resultLabel).toBe('Wygrana');
        expect(projection.currentUserCorrectAnswers).toBe(36);
        expect(projection.opponentUsername).toBe('Prev Player');
    });

    it('treats queue.queued as a first-class live queue event', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.finished', 'evt-prev', '2026-04-21T19:59:50Z', {
                match_id: 'match-prev',
                finished_at: '2026-04-21T19:59:50Z',
                total_questions: 40,
                reason: 'more_correct_answers',
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 40,
                    correct_answers: 36,
                    elo_change: 8,
                },
                opponent: {
                    username: 'Prev Player',
                    total_answered: 40,
                    correct_answers: 34,
                },
            }),
            event('queue.queued', 'evt-queued', '2026-04-21T20:00:00Z', {
                queue_entry_id: 15,
                joined_at: '2026-04-21T20:00:00Z',
                status: 'queued',
                category: {
                    code: 'B',
                },
            }),
        ]);

        expect(projection.phase).toBe('queueing');
        expect(projection.transportState).toContain('queue');
        expect(projection.currentUserAnsweredCount).toBe(0);
        expect(projection.currentUserCorrectAnswers).toBe(0);
        expect(projection.currentUserEloChange).toBeNull();
        expect(projection.resultLabel).toBeNull();
        expect(projection.lastErrorCode).toBeNull();
        expect(projection.lastErrorMessage).toBeNull();
        expect(projection.matchId).toBeNull();
        expect(projection.finishedAt).toBeNull();
        expect(projection.opponentUsername).toBeNull();
        expect(projection.opponentAnsweredCount).toBe(0);
    });

    it('keeps queueing projection when overview sync includes recent_match from a previous game', () => {
        const projection = reduceRankedRealtimeEvents([
            event('overview.sync', 'evt-queueing', '2026-04-21T20:00:00Z', {
                state: 'queued',
                queue: {
                    id: 15,
                    status: 'queued',
                    joined_at: '2026-04-21T20:00:00Z',
                },
                active_match: null,
                recent_match: {
                    public_id: 'match-prev',
                    status: 'finished',
                    finished_at: '2026-04-21T19:59:50Z',
                    outcome: 'win',
                    result_label: 'Wygrana',
                    current_user: {
                        total_answered: 40,
                        correct_answers: 37,
                        elo_change: 11,
                    },
                    opponent: {
                        username: 'Prev Player',
                        total_answered: 40,
                        correct_answers: 35,
                    },
                },
            }),
        ]);

        expect(projection.phase).toBe('queueing');
        expect(projection.resultLabel).toBeNull();
        expect(projection.currentUserCorrectAnswers).toBe(0);
        expect(projection.matchId).toBeNull();
        expect(projection.opponentUsername).toBeNull();
    });

    it('clears previous match snapshot when server_full starts a new queue attempt', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.finished', 'evt-finished', '2026-04-21T19:59:50Z', {
                match_id: 'match-prev',
                finished_at: '2026-04-21T19:59:50Z',
                total_questions: 40,
                reason: 'more_correct_answers',
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 40,
                    correct_answers: 36,
                    elo_change: 8,
                },
                opponent: {
                    username: 'Prev Player',
                    total_answered: 40,
                    correct_answers: 34,
                },
            }),
            event('queue.server_full', 'evt-server-full', '2026-04-21T20:00:10Z', {
                position_in_queue: 101,
                current_capacity: 100,
                max_concurrent_players: 100,
                estimated_wait_minutes: 3,
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            }),
        ]);

        expect(projection.phase).toBe('server_full');
        expect(projection.resultLabel).toBeNull();
        expect(projection.currentUserCorrectAnswers).toBe(0);
        expect(projection.matchId).toBeNull();
        expect(projection.opponentUsername).toBeNull();
        expect(projection.queuePosition).toBe(101);
    });

    it('uses overview sync to bootstrap an active in-progress match before detailed events arrive', () => {
        const projection = reduceRankedRealtimeEvents([
            event('overview.sync', 'evt-1', '2026-04-21T20:00:10Z', {
                state: 'in_progress',
                active_match: {
                    public_id: 'match-overview-1',
                    status: 'in_progress',
                    state: 'in_progress',
                    matched_at: '2026-04-21T20:00:00Z',
                    started_at: '2026-04-21T20:00:05Z',
                    duration_seconds: 90,
                    players: [
                        {
                            is_current_user: true,
                            total_answered: 6,
                            correct_answers: 4,
                            elo_change: null,
                        },
                    ],
                    opponent: {
                        username: 'Player Sync',
                        total_answered: 7,
                        correct_answers: 5,
                    },
                },
            }),
        ]);

        expect(projection.phase).toBe('in_progress');
        expect(projection.matchId).toBe('match-overview-1');
        expect(projection.matchedAt).toBe('2026-04-21T20:00:00Z');
        expect(projection.startedAt).toBe('2026-04-21T20:00:05Z');
        expect(projection.durationSeconds).toBe(90);
        expect(projection.currentUserAnsweredCount).toBe(6);
        expect(projection.currentUserCorrectAnswers).toBe(4);
        expect(projection.currentUserEloChange).toBeNull();
        expect(projection.opponentUsername).toBe('Player Sync');
        expect(projection.opponentAnsweredCount).toBe(7);
        expect(projection.opponentCorrectAnswers).toBe(5);
    });

    it('uses overview sync to bootstrap finished results after a reload', () => {
        const projection = reduceRankedRealtimeEvents([
            event('overview.sync', 'evt-1', '2026-04-21T20:01:35Z', {
                state: 'finished',
                recent_match: {
                    public_id: 'match-finished-1',
                    status: 'finished',
                    finished_at: '2026-04-21T20:01:30Z',
                    total_questions: 40,
                    reason: 'more_correct_answers',
                    category: {
                        name: 'Prawo jazdy kat. B',
                    },
                    outcome: 'win',
                    result_label: 'Wygrana',
                    current_user: {
                        total_answered: 40,
                        correct_answers: 38,
                        elo_change: 12,
                    },
                    opponent: {
                        username: 'Player History',
                        total_answered: 40,
                        correct_answers: 36,
                    },
                },
            }),
        ]);

        expect(projection.phase).toBe('finished');
        expect(projection.matchId).toBe('match-finished-1');
        expect(projection.finishedAt).toBe('2026-04-21T20:01:30Z');
        expect(projection.resultReason).toBe('more_correct_answers');
        expect(projection.outcome).toBe('win');
        expect(projection.resultLabel).toBe('Wygrana');
        expect(projection.resultCategoryName).toBe('Prawo jazdy kat. B');
        expect(projection.resultTotalQuestions).toBe(40);
        expect(projection.currentUserAnsweredCount).toBe(40);
        expect(projection.currentUserCorrectAnswers).toBe(38);
        expect(projection.currentUserEloChange).toBe(12);
        expect(projection.opponentUsername).toBe('Player History');
        expect(projection.opponentAnsweredCount).toBe(40);
        expect(projection.opponentCorrectAnswers).toBe(36);
    });

    it('hydrates terminal finished snapshot directly from match finished event payload', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.finished', 'evt-1', '2026-04-21T20:01:35Z', {
                match_id: 'match-finished-live-1',
                finished_at: '2026-04-21T20:01:34Z',
                total_questions: 40,
                reason: 'faster_time',
                category: {
                    name: 'Prawo jazdy kat. B',
                },
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 40,
                    correct_answers: 35,
                    elo_change: 9,
                },
                opponent: {
                    username: 'Player Final',
                    total_answered: 40,
                    correct_answers: 34,
                },
            }),
        ]);

        expect(projection.phase).toBe('finished');
        expect(projection.matchId).toBe('match-finished-live-1');
        expect(projection.finishedAt).toBe('2026-04-21T20:01:34Z');
        expect(projection.resultReason).toBe('faster_time');
        expect(projection.outcome).toBe('win');
        expect(projection.resultLabel).toBe('Wygrana');
        expect(projection.resultCategoryName).toBe('Prawo jazdy kat. B');
        expect(projection.resultTotalQuestions).toBe(40);
        expect(projection.currentUserAnsweredCount).toBe(40);
        expect(projection.currentUserCorrectAnswers).toBe(35);
        expect(projection.currentUserEloChange).toBe(9);
        expect(projection.opponentUsername).toBe('Player Final');
        expect(projection.opponentAnsweredCount).toBe(40);
        expect(projection.opponentCorrectAnswers).toBe(34);
    });

    it('hydrates terminal abandoned snapshot directly from match abandoned event payload', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.abandoned', 'evt-1', '2026-04-21T20:01:35Z', {
                match_id: 'match-abandoned-live-1',
                abandoned_at: '2026-04-21T20:01:32Z',
                total_questions: 40,
                reason: 'opponent_disconnect_no_return',
                category: {
                    name: 'Prawo jazdy kat. B',
                },
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 12,
                    correct_answers: 10,
                    elo_change: 10,
                },
                opponent: {
                    username: 'Player Walkover',
                    total_answered: 12,
                    correct_answers: 8,
                },
            }),
        ]);

        expect(projection.phase).toBe('abandoned');
        expect(projection.matchId).toBe('match-abandoned-live-1');
        expect(projection.finishedAt).toBe('2026-04-21T20:01:32Z');
        expect(projection.resultReason).toBe('opponent_disconnect_no_return');
        expect(projection.outcome).toBe('win');
        expect(projection.resultLabel).toBe('Wygrana');
        expect(projection.resultCategoryName).toBe('Prawo jazdy kat. B');
        expect(projection.resultTotalQuestions).toBe(40);
        expect(projection.currentUserAnsweredCount).toBe(12);
        expect(projection.currentUserCorrectAnswers).toBe(10);
        expect(projection.currentUserEloChange).toBe(10);
        expect(projection.opponentUsername).toBe('Player Walkover');
        expect(projection.opponentAnsweredCount).toBe(12);
        expect(projection.opponentCorrectAnswers).toBe(8);
    });

    it('clears terminal summary when a new queue matched event starts the next ranked flow', () => {
        const projection = reduceRankedRealtimeEvents([
            event('match.finished', 'evt-1', '2026-04-21T20:01:35Z', {
                match_id: 'match-finished-live-1',
                finished_at: '2026-04-21T20:01:34Z',
                reason: 'faster_time',
                outcome: 'win',
                result_label: 'Wygrana',
                current_user: {
                    total_answered: 40,
                    correct_answers: 35,
                    elo_change: 9,
                },
                opponent: {
                    username: 'Player Final',
                    total_answered: 40,
                    correct_answers: 34,
                },
            }),
            event('queue.matched', 'evt-2', '2026-04-21T20:02:00Z', {
                match_id: 'match-new-1',
                starting_in_seconds: 5,
                opponent: {
                    username: 'Player New',
                },
            }),
        ]);

        expect(projection.phase).toBe('matched');
        expect(projection.matchId).toBe('match-new-1');
        expect(projection.outcome).toBeNull();
        expect(projection.resultLabel).toBeNull();
        expect(projection.resultCategoryName).toBeNull();
        expect(projection.resultTotalQuestions).toBeNull();
        expect(projection.resultReason).toBeNull();
        expect(projection.currentUserAnsweredCount).toBe(0);
        expect(projection.currentUserCorrectAnswers).toBe(0);
        expect(projection.currentUserEloChange).toBeNull();
        expect(projection.opponentUsername).toBe('Player New');
    });

    it('projects queue server full state and clears it after queue matched', () => {
        const projection = reduceRankedRealtimeEvents([
            event('error', 'evt-1', '2026-04-21T19:59:55Z', {
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            }),
            event('queue.server_full', 'evt-2', '2026-04-21T20:00:00Z', {
                position_in_queue: 105,
                current_capacity: 100,
                max_concurrent_players: 100,
                estimated_wait_minutes: 3,
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            }),
            event('queue.matched', 'evt-3', '2026-04-21T20:00:06Z', {
                match_id: 'match-3',
                starting_in_seconds: 5,
                opponent: {
                    username: 'Player Queue',
                },
            }),
        ]);

        expect(projection.phase).toBe('matched');
        expect(projection.matchId).toBe('match-3');
        expect(projection.countdownSeconds).toBe(5);
        expect(projection.queuePosition).toBeNull();
        expect(projection.currentCapacity).toBeNull();
        expect(projection.estimatedWaitMinutes).toBeNull();
        expect(projection.lastErrorCode).toBeNull();
        expect(projection.lastErrorMessage).toBeNull();
    });

    it('returns from server_full to queueing when queue.resumed arrives', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.server_full', 'evt-1', '2026-04-21T20:00:00Z', {
                position_in_queue: 105,
                current_capacity: 100,
                max_concurrent_players: 100,
                estimated_wait_minutes: 3,
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            }),
            event('queue.resumed', 'evt-2', '2026-04-21T20:00:04Z', {
                queue_entry_id: 15,
                previous_status: 'server_full',
                status: 'queued',
                resumed_at: '2026-04-21T20:00:04Z',
            }),
        ]);

        expect(projection.phase).toBe('queueing');
        expect(projection.transportState).toContain('wznowienie kolejki');
        expect(projection.queuePosition).toBeNull();
        expect(projection.currentCapacity).toBeNull();
        expect(projection.maxConcurrentPlayers).toBeNull();
        expect(projection.estimatedWaitMinutes).toBeNull();
        expect(projection.lastErrorCode).toBeNull();
        expect(projection.lastErrorMessage).toBeNull();
    });

    it('keeps queue capacity details while client is blocked by server full', () => {
        const projection = reduceRankedRealtimeEvents([
            event('queue.server_full', 'evt-1', '2026-04-21T20:00:00Z', {
                position_in_queue: 104,
                current_capacity: 100,
                max_concurrent_players: 100,
                estimated_wait_minutes: 2,
                error_code: 'SERVER_FULL',
                message: 'Waiting for one of the active slots to free up.',
            }),
            event('heartbeat', 'evt-2', '2026-04-21T20:00:10Z'),
        ]);

        expect(projection.phase).toBe('server_full');
        expect(projection.queuePosition).toBe(104);
        expect(projection.currentCapacity).toBe(100);
        expect(projection.maxConcurrentPlayers).toBe(100);
        expect(projection.estimatedWaitMinutes).toBe(2);
        expect(projection.lastErrorCode).toBe('SERVER_FULL');
        expect(projection.lastErrorMessage).toBe('Waiting for one of the active slots to free up.');
    });

    it('deduplicates merged events and keeps only new incremental payloads', () => {
        const merged = mergeRankedRealtimeEvents(
            [
                event('queue.matched', 'evt-1', '2026-04-21T20:00:00Z'),
                event('match.started', 'evt-2', '2026-04-21T20:00:05Z'),
            ],
            [
                event('match.started', 'evt-2', '2026-04-21T20:00:05Z'),
                event('match.finished', 'evt-3', '2026-04-21T20:01:30Z', {
                    reason: 'more_correct_answers',
                }),
            ],
        );

        expect(merged.map((item) => item.id)).toEqual(['evt-1', 'evt-2', 'evt-3']);
        expect(reduceRankedRealtimeEvents(merged).phase).toBe('finished');
    });

    it('sorts combined events by timestamp for stable raw event logs', () => {
        const sorted = sortRankedRealtimeEvents([
            event('heartbeat', 'evt-3', '2026-04-21T20:00:10Z'),
            event('queue.matched', 'evt-1', '2026-04-21T20:00:00Z'),
            event('match.started', 'evt-2', '2026-04-21T20:00:05Z'),
        ]);

        expect(sorted.map((item) => item.id)).toEqual(['evt-1', 'evt-2', 'evt-3']);
    });

    it('starts from an empty idle projection', () => {
        expect(createInitialRankedRealtimeProjection()).toMatchObject({
            phase: 'idle',
            processedEventIds: [],
            latestEventId: null,
        });
    });
});
