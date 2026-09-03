import {
    isPureIdleRankedOverviewState,
    shouldAppendRankedOverviewSyncEvent,
} from '@/utils/rankedRealtimeOverviewSync';
import { describe, expect, it } from 'vitest';

describe('rankedRealtimeOverviewSync', () => {
    it('recognizes a pure idle overview snapshot', () => {
        expect(isPureIdleRankedOverviewState({
            state: 'idle',
            queue: null,
            active_match: null,
            recent_match: null,
        })).toBe(true);

        expect(isPureIdleRankedOverviewState({
            state: 'queued',
            queue: {
                id: 15,
            },
            active_match: null,
            recent_match: null,
        })).toBe(false);

        expect(isPureIdleRankedOverviewState({
            state: 'idle',
            queue: null,
            active_match: null,
            recent_match: {
                public_id: 'match-1',
            },
        })).toBe(false);
    });

    it('does not append duplicate synthetic idle snapshots back-to-back', () => {
        const previousSnapshot = {
            state: 'idle',
            queue: null,
            active_match: null,
            recent_match: null,
        };
        const nextSnapshot = {
            state: 'idle',
            queue: null,
            active_match: null,
            recent_match: null,
        };

        expect(shouldAppendRankedOverviewSyncEvent(previousSnapshot, nextSnapshot)).toBe(false);
    });

    it('still appends a new idle snapshot after leaving a non-idle state', () => {
        const previousSnapshot = {
            state: 'queued',
            queue: {
                id: 15,
            },
            active_match: null,
            recent_match: null,
        };
        const nextSnapshot = {
            state: 'idle',
            queue: null,
            active_match: null,
            recent_match: null,
        };

        expect(shouldAppendRankedOverviewSyncEvent(previousSnapshot, nextSnapshot)).toBe(true);
    });
});
