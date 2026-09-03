import {
    RANKED_EVENTS_POLL_INTERVAL_MS,
    RANKED_OVERVIEW_POLL_INTERVAL_MS,
    RANKED_QUEUE_POLL_INTERVAL_MS,
    RankedRealtimePollingTransport,
} from '@/utils/rankedRealtimePollingTransport';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('RankedRealtimePollingTransport', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('polls overview while the user is still queued', async () => {
        const fetchOverview = vi.fn();
        const transport = new RankedRealtimePollingTransport({
            fetchOverview,
            fetchEvents: vi.fn(),
            sendPong: vi.fn(),
        });

        transport.sync({
            queueStatus: 'queued',
            activeMatchPublicId: null,
            activeMatchStatus: null,
            heartbeatSeconds: 10,
        });

        await vi.advanceTimersByTimeAsync(RANKED_QUEUE_POLL_INTERVAL_MS);

        expect(fetchOverview).toHaveBeenCalledTimes(1);
        transport.destroy();
    });

    it('keeps polling overview while the user waits outside capacity in server_full', async () => {
        const fetchOverview = vi.fn();
        const transport = new RankedRealtimePollingTransport({
            fetchOverview,
            fetchEvents: vi.fn(),
            sendPong: vi.fn(),
        });

        transport.sync({
            queueStatus: 'server_full',
            activeMatchPublicId: null,
            activeMatchStatus: null,
            heartbeatSeconds: 10,
        });

        await vi.advanceTimersByTimeAsync(RANKED_QUEUE_POLL_INTERVAL_MS * 2);

        expect(fetchOverview).toHaveBeenCalledTimes(2);
        transport.destroy();
    });

    it('sends pong immediately and schedules overview plus event polling for active matches', async () => {
        const fetchOverview = vi.fn();
        const fetchEvents = vi.fn();
        const sendPong = vi.fn();
        const transport = new RankedRealtimePollingTransport({
            fetchOverview,
            fetchEvents,
            sendPong,
        });

        transport.sync({
            queueStatus: 'matched',
            activeMatchPublicId: 'match-123',
            activeMatchStatus: 'matched',
            heartbeatSeconds: 10,
        });

        expect(sendPong).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(RANKED_EVENTS_POLL_INTERVAL_MS);
        expect(fetchEvents).toHaveBeenCalledWith('match-123');

        await vi.advanceTimersByTimeAsync(
            RANKED_OVERVIEW_POLL_INTERVAL_MS - RANKED_EVENTS_POLL_INTERVAL_MS,
        );
        expect(fetchOverview).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(5000);
        expect(sendPong).toHaveBeenCalledTimes(2);

        transport.destroy();
    });

    it('replaces queue polling with active-match polling when snapshot changes', async () => {
        const fetchOverview = vi.fn();
        const fetchEvents = vi.fn();
        const sendPong = vi.fn();
        const transport = new RankedRealtimePollingTransport({
            fetchOverview,
            fetchEvents,
            sendPong,
        });

        transport.sync({
            queueStatus: 'queued',
            activeMatchPublicId: null,
            activeMatchStatus: null,
            heartbeatSeconds: 10,
        });

        await vi.advanceTimersByTimeAsync(RANKED_QUEUE_POLL_INTERVAL_MS);
        expect(fetchOverview).toHaveBeenCalledTimes(1);

        transport.sync({
            queueStatus: 'matched',
            activeMatchPublicId: 'match-xyz',
            activeMatchStatus: 'in_progress',
            heartbeatSeconds: 10,
        });

        expect(sendPong).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(RANKED_QUEUE_POLL_INTERVAL_MS);
        expect(fetchOverview).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(
            RANKED_OVERVIEW_POLL_INTERVAL_MS - RANKED_QUEUE_POLL_INTERVAL_MS,
        );
        expect(fetchOverview).toHaveBeenCalledTimes(2);
        expect(fetchEvents).toHaveBeenCalledWith('match-xyz');

        transport.destroy();
    });

    it('can keep only heartbeat scheduling when stream transport owns overview and events', async () => {
        const fetchOverview = vi.fn();
        const fetchEvents = vi.fn();
        const sendPong = vi.fn();
        const transport = new RankedRealtimePollingTransport({
            fetchOverview,
            fetchEvents,
            sendPong,
        });

        transport.sync({
            queueStatus: 'matched',
            activeMatchPublicId: 'match-live',
            activeMatchStatus: 'in_progress',
            heartbeatSeconds: 10,
            enableOverviewPolling: false,
            enableEventPolling: false,
        });

        expect(sendPong).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(RANKED_OVERVIEW_POLL_INTERVAL_MS);

        expect(fetchOverview).not.toHaveBeenCalled();
        expect(fetchEvents).not.toHaveBeenCalled();
        expect(sendPong).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(5000);
        expect(sendPong).toHaveBeenCalledTimes(2);

        transport.destroy();
    });
});
