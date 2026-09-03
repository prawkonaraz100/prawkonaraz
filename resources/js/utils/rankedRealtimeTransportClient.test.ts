import {
    RankedRealtimeTransportClient,
    type RankedRealtimeTransportHandlers,
} from '@/utils/rankedRealtimeTransportClient';
import { describe, expect, it, vi } from 'vitest';

class FakeLiveClient {
    connect = vi.fn();
    destroy = vi.fn();

    constructor(
        public readonly handlers: RankedRealtimeTransportHandlers & {
            onClose?: () => void;
        },
    ) {}
}

describe('RankedRealtimeTransportClient', () => {
    it('prefers websocket when it is configured and supported', () => {
        const onTransportChange = vi.fn();
        const webSocketClients: FakeLiveClient[] = [];
        const eventSourceClients: FakeLiveClient[] = [];
        const client = new RankedRealtimeTransportClient(
            {
                onTransportChange,
            },
            {
                webSocketClientFactory: (handlers) => {
                    const fakeClient = new FakeLiveClient(handlers);

                    webSocketClients.push(fakeClient);

                    return fakeClient;
                },
                eventSourceClientFactory: (handlers) => {
                    const fakeClient = new FakeLiveClient(handlers);

                    eventSourceClients.push(fakeClient);

                    return fakeClient;
                },
                isWebSocketSupported: () => true,
                isEventSourceSupported: () => true,
            },
        );

        const started = client.connect({
            preferWebSocket: true,
            websocketUrl: 'ws://localhost/ranked',
            eventSourceUrl: '/api/v1/ranked/stream',
            allowEventSourceFallback: true,
        });

        expect(started).toBe(true);
        expect(webSocketClients).toHaveLength(1);
        expect(eventSourceClients).toHaveLength(0);

        webSocketClients[0]?.handlers.onOpen?.();

        expect(onTransportChange).toHaveBeenCalledWith('websocket');
    });

    it('falls back from websocket to eventsource when the websocket transport fails', () => {
        const onFallbackStart = vi.fn();
        const onTransportChange = vi.fn();
        const webSocketClients: FakeLiveClient[] = [];
        const eventSourceClients: FakeLiveClient[] = [];
        const client = new RankedRealtimeTransportClient(
            {
                onFallbackStart,
                onTransportChange,
            },
            {
                webSocketClientFactory: (handlers) => {
                    const fakeClient = new FakeLiveClient(handlers);

                    webSocketClients.push(fakeClient);

                    return fakeClient;
                },
                eventSourceClientFactory: (handlers) => {
                    const fakeClient = new FakeLiveClient(handlers);

                    eventSourceClients.push(fakeClient);

                    return fakeClient;
                },
                isWebSocketSupported: () => true,
                isEventSourceSupported: () => true,
            },
        );

        client.connect({
            preferWebSocket: true,
            websocketUrl: 'ws://localhost/ranked',
            eventSourceUrl: '/api/v1/ranked/stream',
            allowEventSourceFallback: true,
        });

        webSocketClients[0]?.handlers.onError?.();

        expect(onFallbackStart).toHaveBeenCalledWith('websocket', 'sse');
        expect(webSocketClients[0]?.destroy).toHaveBeenCalledTimes(1);
        expect(eventSourceClients).toHaveLength(1);

        eventSourceClients[0]?.handlers.onOpen?.();

        expect(onTransportChange).toHaveBeenCalledWith('sse');
    });

    it('starts with eventsource when websocket is unavailable', () => {
        const onTransportChange = vi.fn();
        const eventSourceClients: FakeLiveClient[] = [];
        const client = new RankedRealtimeTransportClient(
            {
                onTransportChange,
            },
            {
                webSocketClientFactory: (handlers) => new FakeLiveClient(handlers),
                eventSourceClientFactory: (handlers) => {
                    const fakeClient = new FakeLiveClient(handlers);

                    eventSourceClients.push(fakeClient);

                    return fakeClient;
                },
                isWebSocketSupported: () => false,
                isEventSourceSupported: () => true,
            },
        );

        const started = client.connect({
            preferWebSocket: true,
            websocketUrl: 'ws://localhost/ranked',
            eventSourceUrl: '/api/v1/ranked/stream',
            allowEventSourceFallback: true,
        });

        expect(started).toBe(true);
        expect(eventSourceClients).toHaveLength(1);

        eventSourceClients[0]?.handlers.onOpen?.();

        expect(onTransportChange).toHaveBeenCalledWith('sse');
    });
});
