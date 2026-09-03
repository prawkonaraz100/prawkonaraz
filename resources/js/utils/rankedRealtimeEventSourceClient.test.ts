import { RankedRealtimeEventSourceClient } from '@/utils/rankedRealtimeEventSourceClient';
import { describe, expect, it, vi } from 'vitest';

class FakeEventSource {
    onopen: ((event: Event) => unknown) | null = null;
    onerror: ((event: Event) => unknown) | null = null;
    listeners = new Map<string, EventListenerOrEventListenerObject[]>();
    close = vi.fn();

    addEventListener(type: string, listener: EventListenerOrEventListenerObject): void {
        const currentListeners = this.listeners.get(type) ?? [];

        currentListeners.push(listener);
        this.listeners.set(type, currentListeners);
    }

    dispatch(type: string, data: unknown): void {
        const event = {
            data: JSON.stringify(data),
        } as MessageEvent<string>;

        for (const listener of this.listeners.get(type) ?? []) {
            if (typeof listener === 'function') {
                listener(event);
                continue;
            }

            listener.handleEvent(event);
        }
    }

    dispatchRaw(type: string, event: Event): void {
        for (const listener of this.listeners.get(type) ?? []) {
            if (typeof listener === 'function') {
                listener(event);
                continue;
            }

            listener.handleEvent(event);
        }
    }
}

describe('RankedRealtimeEventSourceClient', () => {
    it('forwards parsed stream, error, heartbeat, overview, queue and match events to handlers', () => {
        const source = new FakeEventSource();
        const onStreamReady = vi.fn();
        const onEventError = vi.fn();
        const onHeartbeat = vi.fn();
        const onOverviewSync = vi.fn();
        const onQueueLeft = vi.fn();
        const onQueueQueued = vi.fn();
        const onQueueResumed = vi.fn();
        const onQueueMatched = vi.fn();
        const onQueueServerFull = vi.fn();
        const onMatchEvents = vi.fn();
        const client = new RankedRealtimeEventSourceClient(
            {
                onStreamReady,
                onEventError,
                onHeartbeat,
                onOverviewSync,
                onQueueLeft,
                onQueueQueued,
                onQueueResumed,
                onQueueMatched,
                onQueueServerFull,
                onMatchEvents,
            },
            () => source,
        );

        client.connect('/stream');

        source.dispatch('stream.ready', {
            event: 'stream.ready',
            data: {
                api_version: '1.0',
            },
        });
        source.dispatch('error', {
            event: 'error',
            id: 'evt-error',
            data: {
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            },
        });
        source.dispatch('heartbeat', {
            event: 'heartbeat',
            id: 'evt-heartbeat',
            data: {
                match_id: 'match-123',
            },
        });
        source.dispatch('overview.sync', {
            data: {
                state: 'queued',
            },
        });
        source.dispatch('queue.left', {
            event: 'queue.left',
            id: 'evt-queue-left',
            data: {
                queue_entry_id: 15,
            },
        });
        source.dispatch('queue.queued', {
            event: 'queue.queued',
            id: 'evt-queue-queued',
            data: {
                queue_entry_id: 15,
            },
        });
        source.dispatch('queue.resumed', {
            event: 'queue.resumed',
            id: 'evt-queue-resumed',
            data: {
                queue_entry_id: 15,
            },
        });
        source.dispatch('queue.matched', {
            event: 'queue.matched',
            id: 'evt-queue-matched',
            data: {
                match_id: 'match-123',
            },
        });
        source.dispatch('queue.server_full', {
            event: 'queue.server_full',
            id: 'evt-queue-full',
            data: {
                position_in_queue: 101,
            },
        });
        source.dispatch('match.events', {
            data: {
                events: [
                    { id: 'evt-1' },
                ],
            },
        });

        expect(onStreamReady).toHaveBeenCalledWith({
            event: 'stream.ready',
            data: {
                api_version: '1.0',
            },
        });
        expect(onEventError).toHaveBeenCalledWith({
            event: 'error',
            id: 'evt-error',
            data: {
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            },
        });
        expect(onHeartbeat).toHaveBeenCalledWith({
            event: 'heartbeat',
            id: 'evt-heartbeat',
            data: {
                match_id: 'match-123',
            },
        });
        expect(onOverviewSync).toHaveBeenCalledWith({
            data: {
                state: 'queued',
            },
        });
        expect(onQueueLeft).toHaveBeenCalledWith({
            event: 'queue.left',
            id: 'evt-queue-left',
            data: {
                queue_entry_id: 15,
            },
        });
        expect(onQueueQueued).toHaveBeenCalledWith({
            event: 'queue.queued',
            id: 'evt-queue-queued',
            data: {
                queue_entry_id: 15,
            },
        });
        expect(onQueueResumed).toHaveBeenCalledWith({
            event: 'queue.resumed',
            id: 'evt-queue-resumed',
            data: {
                queue_entry_id: 15,
            },
        });
        expect(onQueueMatched).toHaveBeenCalledWith({
            event: 'queue.matched',
            id: 'evt-queue-matched',
            data: {
                match_id: 'match-123',
            },
        });
        expect(onQueueServerFull).toHaveBeenCalledWith({
            event: 'queue.server_full',
            id: 'evt-queue-full',
            data: {
                position_in_queue: 101,
            },
        });
        expect(onMatchEvents).toHaveBeenCalledWith({
            data: {
                events: [
                    { id: 'evt-1' },
                ],
            },
        });
    });

    it('ignores malformed custom error events without crashing the stream client', () => {
        const source = new FakeEventSource();
        const onEventError = vi.fn();
        const client = new RankedRealtimeEventSourceClient(
            {
                onEventError,
            },
            () => source,
        );

        client.connect('/stream');
        source.dispatchRaw('error', new Event('error'));

        expect(onEventError).not.toHaveBeenCalled();
    });

    it('notifies open and error handlers', () => {
        const source = new FakeEventSource();
        const onOpen = vi.fn();
        const onError = vi.fn();
        const client = new RankedRealtimeEventSourceClient(
            {
                onOpen,
                onError,
            },
            () => source,
        );

        client.connect('/stream');

        source.onopen?.(new Event('open'));
        source.onerror?.(new Event('error'));

        expect(onOpen).toHaveBeenCalledTimes(1);
        expect(onError).toHaveBeenCalledTimes(1);
    });

    it('closes an old stream before reconnecting and on destroy', () => {
        const firstSource = new FakeEventSource();
        const secondSource = new FakeEventSource();
        const factory = vi.fn()
            .mockReturnValueOnce(firstSource)
            .mockReturnValueOnce(secondSource);
        const client = new RankedRealtimeEventSourceClient({}, factory);

        client.connect('/stream');
        client.connect('/stream');
        client.destroy();

        expect(firstSource.close).toHaveBeenCalledTimes(1);
        expect(secondSource.close).toHaveBeenCalledTimes(1);
        expect(factory).toHaveBeenCalledTimes(2);
    });
});
