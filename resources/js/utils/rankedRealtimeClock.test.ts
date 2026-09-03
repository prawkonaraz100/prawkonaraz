import {
    formatRankedClock,
    getRankedCountdownSeconds,
    getRankedDurationSeconds,
    getRankedElapsedSeconds,
    getRankedRemainingSeconds,
    getRankedSecondsUntil,
} from '@/utils/rankedRealtimeClock';
import { describe, expect, it } from 'vitest';

describe('rankedRealtimeClock', () => {
    it('formats compact clocks for minutes and hours', () => {
        expect(formatRankedClock(65)).toBe('01:05');
        expect(formatRankedClock(3723)).toBe('1:02:03');
        expect(formatRankedClock(null)).toBe('--:--');
    });

    it('calculates elapsed and remaining seconds from backend timestamps', () => {
        const nowMs = Date.parse('2026-04-21T20:01:10Z');

        expect(getRankedElapsedSeconds('2026-04-21T20:00:00Z', nowMs)).toBe(70);
        expect(getRankedRemainingSeconds('2026-04-21T20:00:30Z', 90, nowMs)).toBe(50);
        expect(getRankedDurationSeconds('2026-04-21T20:00:30Z', '2026-04-21T20:01:20Z')).toBe(50);
    });

    it('calculates countdowns and absolute deadlines against the local ui clock', () => {
        const nowMs = Date.parse('2026-04-21T20:00:03.200Z');

        expect(getRankedCountdownSeconds('2026-04-21T20:00:00Z', 5, nowMs)).toBe(2);
        expect(getRankedSecondsUntil('2026-04-21T20:00:30Z', nowMs)).toBe(27);
    });
});
