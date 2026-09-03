export type RankedRealtimeStreamEventName =
    | 'stream.ready'
    | 'error'
    | 'heartbeat'
    | 'overview.sync'
    | 'queue.left'
    | 'queue.queued'
    | 'queue.resumed'
    | 'queue.matched'
    | 'queue.server_full'
    | 'match.events';

export interface RankedRealtimeStreamHandlers {
    onOpen?: () => void;
    onError?: () => void;
    onStreamReady?: (payload: unknown) => void;
    onEventError?: (payload: unknown) => void;
    onHeartbeat?: (payload: unknown) => void;
    onOverviewSync?: (payload: unknown) => void;
    onQueueLeft?: (payload: unknown) => void;
    onQueueQueued?: (payload: unknown) => void;
    onQueueResumed?: (payload: unknown) => void;
    onQueueMatched?: (payload: unknown) => void;
    onQueueServerFull?: (payload: unknown) => void;
    onMatchEvents?: (payload: unknown) => void;
}

export const parseRankedRealtimeStreamMessage = (event: Event): unknown | null => {
    const message = event as MessageEvent<string>;

    if (typeof message.data !== 'string' || message.data.length === 0) {
        return null;
    }

    try {
        return JSON.parse(message.data) as unknown;
    } catch {
        return null;
    }
};

export const dispatchRankedRealtimeStreamPayload = (
    eventName: RankedRealtimeStreamEventName,
    payload: unknown,
    handlers: RankedRealtimeStreamHandlers,
): void => {
    if (eventName === 'stream.ready') {
        handlers.onStreamReady?.(payload);
        return;
    }

    if (eventName === 'error') {
        handlers.onEventError?.(payload);
        return;
    }

    if (eventName === 'heartbeat') {
        handlers.onHeartbeat?.(payload);
        return;
    }

    if (eventName === 'overview.sync') {
        handlers.onOverviewSync?.(payload);
        return;
    }

    if (eventName === 'queue.left') {
        handlers.onQueueLeft?.(payload);
        return;
    }

    if (eventName === 'queue.queued') {
        handlers.onQueueQueued?.(payload);
        return;
    }

    if (eventName === 'queue.resumed') {
        handlers.onQueueResumed?.(payload);
        return;
    }

    if (eventName === 'queue.matched') {
        handlers.onQueueMatched?.(payload);
        return;
    }

    if (eventName === 'queue.server_full') {
        handlers.onQueueServerFull?.(payload);
        return;
    }

    handlers.onMatchEvents?.(payload);
};
