const parseTimestampMs = (timestamp: string | null | undefined): number | null => {
    if (!timestamp) {
        return null;
    }

    const parsed = Date.parse(timestamp);

    return Number.isNaN(parsed) ? null : parsed;
};

export const getRankedElapsedSeconds = (
    timestamp: string | null | undefined,
    nowMs: number,
): number | null => {
    const timestampMs = parseTimestampMs(timestamp);

    if (timestampMs === null) {
        return null;
    }

    return Math.max(Math.floor((nowMs - timestampMs) / 1000), 0);
};

export const getRankedRemainingSeconds = (
    startedAt: string | null | undefined,
    durationSeconds: number | null | undefined,
    nowMs: number,
): number | null => {
    if (typeof durationSeconds !== 'number' || Number.isNaN(durationSeconds)) {
        return null;
    }

    const elapsedSeconds = getRankedElapsedSeconds(startedAt, nowMs);

    if (elapsedSeconds === null) {
        return null;
    }

    return Math.max(durationSeconds - elapsedSeconds, 0);
};

export const getRankedDurationSeconds = (
    startedAt: string | null | undefined,
    endedAt: string | null | undefined,
): number | null => {
    const startedAtMs = parseTimestampMs(startedAt);
    const endedAtMs = parseTimestampMs(endedAt);

    if (startedAtMs === null || endedAtMs === null) {
        return null;
    }

    return Math.max(Math.floor((endedAtMs - startedAtMs) / 1000), 0);
};

export const getRankedCountdownSeconds = (
    anchorTimestamp: string | null | undefined,
    countdownSeconds: number | null | undefined,
    nowMs: number,
): number | null => {
    if (typeof countdownSeconds !== 'number' || Number.isNaN(countdownSeconds)) {
        return null;
    }

    const anchorMs = parseTimestampMs(anchorTimestamp);

    if (anchorMs === null) {
        return null;
    }

    const targetMs = anchorMs + countdownSeconds * 1000;

    return Math.max(Math.ceil((targetMs - nowMs) / 1000), 0);
};

export const getRankedSecondsUntil = (
    timestamp: string | null | undefined,
    nowMs: number,
): number | null => {
    const timestampMs = parseTimestampMs(timestamp);

    if (timestampMs === null) {
        return null;
    }

    return Math.max(Math.ceil((timestampMs - nowMs) / 1000), 0);
};

export const formatRankedClock = (seconds: number | null | undefined): string => {
    if (typeof seconds !== 'number' || Number.isNaN(seconds)) {
        return '--:--';
    }

    const safeSeconds = Math.max(Math.floor(seconds), 0);
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const remainingSeconds = safeSeconds % 60;
    const paddedMinutes = String(minutes).padStart(2, '0');
    const paddedSeconds = String(remainingSeconds).padStart(2, '0');

    if (hours > 0) {
        return `${hours}:${paddedMinutes}:${paddedSeconds}`;
    }

    return `${paddedMinutes}:${paddedSeconds}`;
};
