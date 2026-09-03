import {
    createRankingSimulationScript,
    type RankedSimulationState,
} from './rankingModeSimulation';
import { describe, expect, it } from 'vitest';

const runScript = (scenario: Parameters<typeof createRankingSimulationScript>[0]): RankedSimulationState => {
    const script = createRankingSimulationScript(scenario, {
        categoryCode: 'B',
        categoryLabel: 'B',
        playerName: 'Tester',
        playerUserId: 321,
    });

    return script.steps.reduce(
        (state, step) => step.apply(state),
        script.initialState,
    );
};

describe('rankingModeSimulation', () => {
    it('builds the happy path with queue, match start and finished result', () => {
        const script = createRankingSimulationScript('happy_path', {
            categoryCode: 'B',
            categoryLabel: 'B',
            playerName: 'Tester',
            playerUserId: 321,
        });
        const events = script.steps
            .map((step) => step.event?.event)
            .filter((event): event is string => Boolean(event));
        const finalState = runScript('happy_path');

        expect(events).toContain('queue.matched');
        expect(events).toContain('match.started');
        expect(events).toContain('match.finished');
        expect(finalState.phase).toBe('finished');
        expect(finalState.result?.reason).toBe('more_correct_answers');
        expect(finalState.player.correctAnswers).toBeGreaterThan(finalState.opponent.correctAnswers);
    });

    it('keeps the reconnect scenario inside the reconnect window and returns to live play', () => {
        const script = createRankingSimulationScript('disconnect_reconnect', {
            categoryCode: 'B',
            categoryLabel: 'B',
            playerName: 'Tester',
        });
        const events = script.steps
            .map((step) => step.event?.event)
            .filter((event): event is string => Boolean(event));
        const finalState = runScript('disconnect_reconnect');

        expect(events).toContain('match.opponent_disconnected');
        expect(events).toContain('match.opponent_reconnected');
        expect(finalState.phase).toBe('finished');
        expect(finalState.result?.reason).toBe('faster_time');
        expect(finalState.opponent.status).toBe('reconnected');
    });

    it('starts server full scenario with error and queue.server_full before recovery', () => {
        const script = createRankingSimulationScript('server_full', {
            categoryCode: 'B',
            categoryLabel: 'B',
            playerName: 'Tester',
        });
        const firstEvents = script.steps
            .slice(0, 3)
            .map((step) => step.event?.event)
            .filter((event): event is string => Boolean(event));
        const finalState = runScript('server_full');

        expect(firstEvents[0]).toBe('error');
        expect(firstEvents[1]).toBe('queue.server_full');
        expect(finalState.phase).toBe('finished');
        expect(finalState.lastError).toBeNull();
    });
});
