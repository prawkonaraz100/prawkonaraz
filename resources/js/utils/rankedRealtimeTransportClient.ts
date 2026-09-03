import {
    RankedRealtimeEventSourceClient,
    type RankedRealtimeEventSourceHandlers,
} from '@/utils/rankedRealtimeEventSourceClient';
import {
    RankedRealtimeWebSocketClient,
    type RankedRealtimeWebSocketHandlers,
} from '@/utils/rankedRealtimeWebSocketClient';
import type { RankedRealtimeStreamHandlers } from '@/utils/rankedRealtimeStreamProtocol';

export type RankedRealtimeTransportKind = 'websocket' | 'sse';

export interface RankedRealtimeTransportHandlers extends RankedRealtimeStreamHandlers {
    onFallbackStart?: (from: RankedRealtimeTransportKind, to: RankedRealtimeTransportKind) => void;
    onTransportChange?: (transport: RankedRealtimeTransportKind) => void;
}

export interface RankedRealtimeTransportConnectOptions {
    preferWebSocket?: boolean;
    websocketUrl?: string | null;
    eventSourceUrl?: string | null;
    allowEventSourceFallback?: boolean;
}

type RankedRealtimeEventSourceClientLike = Pick<RankedRealtimeEventSourceClient, 'connect' | 'destroy'>;
type RankedRealtimeWebSocketClientLike = Pick<RankedRealtimeWebSocketClient, 'connect' | 'destroy'>;

interface RankedRealtimeTransportClientDependencies {
    eventSourceClientFactory?: (
        handlers: RankedRealtimeEventSourceHandlers,
    ) => RankedRealtimeEventSourceClientLike;
    webSocketClientFactory?: (
        handlers: RankedRealtimeWebSocketHandlers,
    ) => RankedRealtimeWebSocketClientLike;
    isEventSourceSupported?: () => boolean;
    isWebSocketSupported?: () => boolean;
}

const defaultIsEventSourceSupported = (): boolean =>
    typeof globalThis.EventSource !== 'undefined';

const defaultIsWebSocketSupported = (): boolean =>
    typeof globalThis.WebSocket !== 'undefined';

export class RankedRealtimeTransportClient {
    private eventSourceClient: RankedRealtimeEventSourceClientLike | null = null;
    private webSocketClient: RankedRealtimeWebSocketClientLike | null = null;
    private fallbackStarted = false;

    private readonly eventSourceClientFactory: (
        handlers: RankedRealtimeEventSourceHandlers,
    ) => RankedRealtimeEventSourceClientLike;

    private readonly webSocketClientFactory: (
        handlers: RankedRealtimeWebSocketHandlers,
    ) => RankedRealtimeWebSocketClientLike;

    private readonly isEventSourceSupported: () => boolean;
    private readonly isWebSocketSupported: () => boolean;

    constructor(
        private readonly handlers: RankedRealtimeTransportHandlers,
        dependencies: RankedRealtimeTransportClientDependencies = {},
    ) {
        this.eventSourceClientFactory = dependencies.eventSourceClientFactory
            ?? ((streamHandlers) => new RankedRealtimeEventSourceClient(streamHandlers));
        this.webSocketClientFactory = dependencies.webSocketClientFactory
            ?? ((streamHandlers) => new RankedRealtimeWebSocketClient(streamHandlers));
        this.isEventSourceSupported = dependencies.isEventSourceSupported ?? defaultIsEventSourceSupported;
        this.isWebSocketSupported = dependencies.isWebSocketSupported ?? defaultIsWebSocketSupported;
    }

    connect(options: RankedRealtimeTransportConnectOptions): boolean {
        this.destroy();

        const preferWebSocket = options.preferWebSocket ?? true;
        const canUseWebSocket = Boolean(options.websocketUrl) && this.isWebSocketSupported();
        const canUseEventSource = Boolean(options.eventSourceUrl) && this.isEventSourceSupported();

        if (preferWebSocket && canUseWebSocket) {
            this.startWebSocket(options);
            return true;
        }

        if (!preferWebSocket && canUseEventSource) {
            this.startEventSource(options.eventSourceUrl as string);
            return true;
        }

        if (canUseEventSource) {
            this.startEventSource(options.eventSourceUrl as string);
            return true;
        }

        if (canUseWebSocket) {
            this.startWebSocket(options);
            return true;
        }

        return false;
    }

    destroy(): void {
        this.fallbackStarted = false;
        this.eventSourceClient?.destroy();
        this.eventSourceClient = null;
        this.webSocketClient?.destroy();
        this.webSocketClient = null;
    }

    private startEventSource(url: string): void {
        this.eventSourceClient = this.eventSourceClientFactory({
            ...this.handlers,
            onOpen: () => {
                this.handlers.onTransportChange?.('sse');
                this.handlers.onOpen?.();
            },
            onError: () => {
                this.handlers.onError?.();
            },
        });

        this.eventSourceClient.connect(url);
    }

    private startWebSocket(options: RankedRealtimeTransportConnectOptions): void {
        this.webSocketClient = this.webSocketClientFactory({
            ...this.handlers,
            onOpen: () => {
                this.handlers.onTransportChange?.('websocket');
                this.handlers.onOpen?.();
            },
            onError: () => {
                if (this.startFallbackIfPossible(options)) {
                    return;
                }

                this.handlers.onError?.();
            },
            onClose: () => {
                if (this.startFallbackIfPossible(options)) {
                    return;
                }

                this.handlers.onError?.();
            },
        });

        this.webSocketClient.connect(options.websocketUrl as string);
    }

    private startFallbackIfPossible(options: RankedRealtimeTransportConnectOptions): boolean {
        if (
            this.fallbackStarted
            || !options.allowEventSourceFallback
            || !options.eventSourceUrl
            || !this.isEventSourceSupported()
        ) {
            return false;
        }

        this.fallbackStarted = true;
        this.webSocketClient?.destroy();
        this.webSocketClient = null;
        this.handlers.onFallbackStart?.('websocket', 'sse');
        this.startEventSource(options.eventSourceUrl);

        return true;
    }
}
