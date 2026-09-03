import { RankedRealtimeWebSocketClient } from '@/utils/rankedRealtimeWebSocketClient';
import { describe, expect, it, vi } from 'vitest';

class FakeWebSocket {
    listeners = new Map<string, EventListenerOrEventListenerObject[]>();
    close = vi.fn();

    addEventListener(type: string, listener: EventListenerOrEventListenerObject): void {
        const currentListeners = this.listeners.get(type) ?? [];

        currentListeners.push(listener);
        this.listeners.set(type, currentListeners);
    }

    dispatch(type: string, data?: unknown): void {
        const event = type === 'message'
            ? ({
                data: JSON.stringify(data),
            } as MessageEvent<string>)
            : (new Event(type));

        for (const listener of this.listeners.get(type) ?? []) {
            if (typeof listener === 'function') {
                listener(event);
                continue;
            }

            listener.handleEvent(event);
        }
    }
}

describe('RankedRealtimeWebSocketClient', () => {
    it('forwards parsed stream, error, heartbeat, overview, queue and match messages to handlers', () => {
        const socket = new FakeWebSocket();
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
        const client = new RankedRealtimeWebSocketClient(
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
            () => socket,
        );

        client.connect('ws://localhost/ranked');

        socket.dispatch('message', {
            event: 'stream.ready',
            data: {
                api_version: '1.0',
            },
        });
        socket.dispatch('message', {
            event: 'error',
            id: 'evt-error',
            data: {
                error_code: 'SERVER_FULL',
                message: 'Server full (100/100). Waiting for space...',
            },
        });
        socket.dispatch('message', {
            event: 'heartbeat',
            id: 'evt-heartbeat',
            data: {
                match_id: 'match-123',
            },
        });
        socket.dispatch('message', {
            event: 'overview.sync',
            data: {
                state: 'queued',
            },
        });
        socket.dispatch('message', {
            event: 'queue.left',
            id: 'evt-queue-left',
            data: {
                queue_entry_id: 15,
            },
        });
        socket.dispatch('message', {
            event: 'queue.queued',
            id: 'evt-queue-queued',
            data: {
                queue_entry_id: 15,
            },
        });
        socket.dispatch('message', {
            event: 'queue.resumed',
            id: 'evt-queue-resumed',
            data: {
                queue_entry_id: 15,
            },
        });
        socket.dispatch('message', {
            event: 'queue.matched',
            id: 'evt-queue-matched',
            data: {
                match_id: 'match-123',
            },
        });
        socket.dispatch('message', {
            event: 'queue.server_full',
            id: 'evt-queue-full',
            data: {
                position_in_queue: 101,
            },
        });
        socket.dispatch('message', {
            event: 'match.events',
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
            event: 'overview.sync',
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
            event: 'match.events',
            data: {
                events: [
                    { id: 'evt-1' },
                ],
            },
        });
    });

    it('notifies open, error and close handlers', () => {
        const socket = new FakeWebSocket();
        const onOpen = vi.fn();
        const onError = vi.fn();
        const onClose = vi.fn();
        const client = new RankedRealtimeWebSocketClient(
            {
                onOpen,
                onError,
                onClose,
            },
            () => socket,
        );

        client.connect('ws://localhost/ranked');

        socket.dispatch('open');
        socket.dispatch('error');
        socket.dispatch('close');

        expect(onOpen).toHaveBeenCalledTimes(1);
        expect(onError).toHaveBeenCalledTimes(1);
        expect(onClose).toHaveBeenCalledTimes(1);
    });

    it('closes an old socket before reconnecting and on destroy', () => {
        const firstSocket = new FakeWebSocket();
        const secondSocket = new FakeWebSocket();
        const factory = vi.fn()
            .mockReturnValueOnce(firstSocket)
            .mockReturnValueOnce(secondSocket);
        const client = new RankedRealtimeWebSocketClient({}, factory);

        client.connect('ws://localhost/ranked');
        client.connect('ws://localhost/ranked');
        client.destroy();

        expect(firstSocket.close).toHaveBeenCalledTimes(1);
        expect(secondSocket.close).toHaveBeenCalledTimes(1);
        expect(factory).toHaveBeenCalledTimes(2);
    });
});
