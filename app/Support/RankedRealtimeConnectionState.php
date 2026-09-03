<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class RankedRealtimeConnectionState
{
    public ?string $previousOverviewHash = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $previousQueueSnapshot = null;

    public ?string $previousQueueQueuedHash = null;

    public ?string $previousQueueServerFullHash = null;

    public ?string $previousQueueMatchedId = null;

    public ?string $streamMatchPublicId = null;

    public ?string $streamQueueMatchPublicId = null;

    public ?string $streamHeartbeatMatchPublicId = null;

    public ?string $afterEventId = null;

    public ?Carbon $lastHeartbeatAt = null;
}
