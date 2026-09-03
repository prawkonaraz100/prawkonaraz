export const RANKED_QUEUE_POLL_INTERVAL_MS = 2500;
export const RANKED_OVERVIEW_POLL_INTERVAL_MS = 5000;
export const RANKED_EVENTS_POLL_INTERVAL_MS = 2000;

type RankedIntervalHandle = ReturnType<typeof globalThis.setInterval>;

export interface RankedRealtimePollingSnapshot {
    queueStatus: string | null;
    activeMatchPublicId: string | null;
    activeMatchStatus: string | null;
    heartbeatSeconds: number;
    enableHeartbeat?: boolean;
    enableOverviewPolling?: boolean;
    enableEventPolling?: boolean;
}

export interface RankedRealtimePollingHandlers {
    fetchOverview: () => void | Promise<void>;
    fetchEvents: (matchPublicId: string) => void | Promise<void>;
    sendPong: () => void | Promise<void>;
}

interface RankedRealtimePollingScheduler {
    setInterval: typeof globalThis.setInterval;
    clearInterval: typeof globalThis.clearInterval;
}

const createDefaultScheduler = (): RankedRealtimePollingScheduler => ({
    setInterval: globalThis.setInterval.bind(globalThis),
    clearInterval: globalThis.clearInterval.bind(globalThis),
});

export class RankedRealtimePollingTransport {
    private heartbeatIntervalId: RankedIntervalHandle | null = null;
    private overviewIntervalId: RankedIntervalHandle | null = null;
    private eventIntervalId: RankedIntervalHandle | null = null;
    private queueIntervalId: RankedIntervalHandle | null = null;

    constructor(
        private readonly handlers: RankedRealtimePollingHandlers,
        private readonly scheduler: RankedRealtimePollingScheduler = createDefaultScheduler(),
    ) {}

    sync(snapshot: RankedRealtimePollingSnapshot): void {
        this.destroy();
        const enableHeartbeat = snapshot.enableHeartbeat ?? true;
        const enableOverviewPolling = snapshot.enableOverviewPolling ?? true;
        const enableEventPolling = snapshot.enableEventPolling ?? true;

        if (
            enableOverviewPolling
            && !snapshot.activeMatchPublicId
            && ['queued', 'matched', 'server_full'].includes(snapshot.queueStatus ?? '')
        ) {
            this.queueIntervalId = this.scheduler.setInterval(() => {
                void this.handlers.fetchOverview();
            }, RANKED_QUEUE_POLL_INTERVAL_MS);

            return;
        }

        if (!snapshot.activeMatchPublicId || !['matched', 'in_progress'].includes(snapshot.activeMatchStatus ?? '')) {
            return;
        }

        if (enableHeartbeat) {
            void this.handlers.sendPong();
        }

        if (enableHeartbeat) {
            this.heartbeatIntervalId = this.scheduler.setInterval(() => {
                void this.handlers.sendPong();
            }, snapshot.heartbeatSeconds * 1000);
        }

        if (enableOverviewPolling) {
            this.overviewIntervalId = this.scheduler.setInterval(() => {
                void this.handlers.fetchOverview();
            }, RANKED_OVERVIEW_POLL_INTERVAL_MS);
        }

        if (enableEventPolling) {
            this.eventIntervalId = this.scheduler.setInterval(() => {
                void this.handlers.fetchEvents(snapshot.activeMatchPublicId as string);
            }, RANKED_EVENTS_POLL_INTERVAL_MS);
        }
    }

    destroy(): void {
        if (this.heartbeatIntervalId !== null) {
            this.scheduler.clearInterval(this.heartbeatIntervalId);
            this.heartbeatIntervalId = null;
        }

        if (this.overviewIntervalId !== null) {
            this.scheduler.clearInterval(this.overviewIntervalId);
            this.overviewIntervalId = null;
        }

        if (this.eventIntervalId !== null) {
            this.scheduler.clearInterval(this.eventIntervalId);
            this.eventIntervalId = null;
        }

        if (this.queueIntervalId !== null) {
            this.scheduler.clearInterval(this.queueIntervalId);
            this.queueIntervalId = null;
        }
    }
}
