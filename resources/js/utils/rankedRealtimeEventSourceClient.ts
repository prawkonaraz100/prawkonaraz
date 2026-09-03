import {
    dispatchRankedRealtimeStreamPayload,
    parseRankedRealtimeStreamMessage,
    type RankedRealtimeStreamEventName,
    type RankedRealtimeStreamHandlers,
} from '@/utils/rankedRealtimeStreamProtocol';

export interface RankedRealtimeEventSourceHandlers extends RankedRealtimeStreamHandlers {}

interface RankedRealtimeEventSourceLike {
    addEventListener(type: string, listener: EventListenerOrEventListenerObject): void;
    close(): void;
    onopen: ((this: EventSource, event: Event) => unknown) | null;
    onerror: ((this: EventSource, event: Event) => unknown) | null;
}

type RankedRealtimeEventSourceFactory = (url: string) => RankedRealtimeEventSourceLike;

const defaultEventSourceFactory: RankedRealtimeEventSourceFactory = (url) =>
    new EventSource(url, {
        withCredentials: true,
    });

export class RankedRealtimeEventSourceClient {
    private eventSource: RankedRealtimeEventSourceLike | null = null;

    constructor(
        private readonly handlers: RankedRealtimeEventSourceHandlers,
        private readonly factory: RankedRealtimeEventSourceFactory = defaultEventSourceFactory,
    ) {}

    connect(url: string): void {
        this.destroy();

        const eventSource = this.factory(url);
        this.eventSource = eventSource;

        eventSource.onopen = () => {
            this.handlers.onOpen?.();
        };

        eventSource.onerror = () => {
            this.handlers.onError?.();
        };

        for (const eventName of [
            'stream.ready',
            'error',
            'heartbeat',
            'overview.sync',
            'queue.left',
            'queue.queued',
            'queue.resumed',
            'queue.matched',
            'queue.server_full',
            'match.events',
        ] as RankedRealtimeStreamEventName[]) {
            eventSource.addEventListener(eventName, (event) => {
                const payload = parseRankedRealtimeStreamMessage(event);

                if (payload === null) {
                    return;
                }

                dispatchRankedRealtimeStreamPayload(
                    eventName,
                    payload,
                    this.handlers,
                );
            });
        }
    }

    destroy(): void {
        if (!this.eventSource) {
            return;
        }

        this.eventSource.close();
        this.eventSource = null;
    }
}
