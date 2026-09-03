import {
    dispatchRankedRealtimeStreamPayload,
    parseRankedRealtimeStreamMessage,
    type RankedRealtimeStreamEventName,
    type RankedRealtimeStreamHandlers,
} from '@/utils/rankedRealtimeStreamProtocol';

export interface RankedRealtimeWebSocketHandlers extends RankedRealtimeStreamHandlers {
    onClose?: () => void;
}

interface RankedRealtimeWebSocketLike {
    addEventListener(type: string, listener: EventListenerOrEventListenerObject): void;
    close(): void;
}

type RankedRealtimeWebSocketFactory = (url: string) => RankedRealtimeWebSocketLike;

const defaultWebSocketFactory: RankedRealtimeWebSocketFactory = (url) => new WebSocket(url);

const resolveWebSocketEventName = (payload: unknown): RankedRealtimeStreamEventName | null => {
    if (typeof payload !== 'object' || payload === null) {
        return null;
    }

    return typeof (payload as { event?: unknown }).event === 'string'
        ? ((payload as { event: RankedRealtimeStreamEventName }).event)
        : null;
};

export class RankedRealtimeWebSocketClient {
    private socket: RankedRealtimeWebSocketLike | null = null;

    constructor(
        private readonly handlers: RankedRealtimeWebSocketHandlers,
        private readonly factory: RankedRealtimeWebSocketFactory = defaultWebSocketFactory,
    ) {}

    connect(url: string): void {
        this.destroy();

        const socket = this.factory(url);
        this.socket = socket;

        socket.addEventListener('open', () => {
            this.handlers.onOpen?.();
        });

        socket.addEventListener('error', () => {
            this.handlers.onError?.();
        });

        socket.addEventListener('close', () => {
            this.handlers.onClose?.();
        });

        socket.addEventListener('message', (event) => {
            const payload = parseRankedRealtimeStreamMessage(event as Event);

            if (payload === null) {
                return;
            }

            const eventName = resolveWebSocketEventName(payload);

            if (!eventName) {
                return;
            }

            dispatchRankedRealtimeStreamPayload(eventName, payload, this.handlers);
        });
    }

    destroy(): void {
        if (!this.socket) {
            return;
        }

        this.socket.close();
        this.socket = null;
    }
}
