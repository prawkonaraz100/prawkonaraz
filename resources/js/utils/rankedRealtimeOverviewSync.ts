export interface RankedOverviewSyncSnapshotLike {
    state?: string | null;
    queue?: unknown | null;
    active_match?: unknown | null;
    recent_match?: unknown | null;
}

export const isPureIdleRankedOverviewState = (
    snapshot: RankedOverviewSyncSnapshotLike | null | undefined,
): boolean => Boolean(
    snapshot
    && snapshot.state === 'idle'
    && !snapshot.queue
    && !snapshot.active_match
    && !snapshot.recent_match,
);

export const shouldAppendRankedOverviewSyncEvent = (
    previousSnapshot: RankedOverviewSyncSnapshotLike | null | undefined,
    nextSnapshot: RankedOverviewSyncSnapshotLike | null | undefined,
): boolean => !(
    isPureIdleRankedOverviewState(previousSnapshot)
    && isPureIdleRankedOverviewState(nextSnapshot)
);
