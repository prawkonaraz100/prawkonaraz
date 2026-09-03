<?php

namespace App\Support;

use App\Models\RankedMatch;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class RankedRealtimeEventStreamPublisher
{
    public function __construct(
        protected RankedMatchService $rankedMatchService,
        protected RankedMatchPayloadBuilder $payloadBuilder,
        protected RankedMatchEventPayloadBuilder $eventPayloadBuilder,
    ) {}

    /**
     * @return array{data: array{server_time: string, api_version: string}}
     */
    public function streamReadyPayload(): array
    {
        return [
            'data' => [
                'server_time' => now()->toIso8601String(),
                'api_version' => '1.0',
            ],
        ];
    }

    /**
     * @return list<array{event: string, payload: array<string, mixed>}>
     */
    public function publish(User $user, RankedRealtimeConnectionState $state): array
    {
        $messages = [];

        $overview = $this->rankedMatchService->overview($user);
        $recentMatch = $this->rankedMatchService->recentMatch($user);
        $overviewPayload = $this->overviewPayload($overview, $recentMatch, $user);
        $overviewHash = md5(json_encode($overviewPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($overviewHash !== $state->previousOverviewHash) {
            $messages[] = [
                'event' => 'overview.sync',
                'payload' => [
                    'data' => $overviewPayload,
                ],
            ];

            $state->previousOverviewHash = $overviewHash;
        }

        $queueLeftEvent = $this->queueLeftEvent($state->previousQueueSnapshot, $overview['queue'], $user);
        $queueResumedEvent = $this->queueResumedEvent($state->previousQueueSnapshot, $overview['queue'], $user);

        if ($queueLeftEvent) {
            $messages[] = [
                'event' => 'queue.left',
                'payload' => $queueLeftEvent,
            ];
        }

        if ($queueResumedEvent) {
            $messages[] = [
                'event' => 'queue.resumed',
                'payload' => $queueResumedEvent,
            ];
        }

        $queueServerFullEvent = $this->queueServerFullEvent($overview['queue'], $user);
        $queueQueuedEvent = $this->queueQueuedEvent($overview['queue'], $user);
        $queueQueuedHash = $queueQueuedEvent
            ? md5(json_encode($queueQueuedEvent['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            : null;
        $queueServerFullHash = $queueServerFullEvent
            ? md5(json_encode($queueServerFullEvent['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            : null;

        if ($queueQueuedEvent && ! $queueResumedEvent && $queueQueuedHash !== $state->previousQueueQueuedHash) {
            $messages[] = [
                'event' => 'queue.queued',
                'payload' => $queueQueuedEvent,
            ];
            $state->previousQueueQueuedHash = $queueQueuedHash;
        }

        if ($queueResumedEvent) {
            $state->previousQueueQueuedHash = $queueQueuedHash;
        }

        if (! $queueQueuedEvent) {
            $state->previousQueueQueuedHash = null;
        }

        if ($queueServerFullEvent && $queueServerFullHash !== $state->previousQueueServerFullHash) {
            $messages[] = [
                'event' => 'error',
                'payload' => $this->queueServerFullErrorEvent($queueServerFullEvent, $user),
            ];
            $messages[] = [
                'event' => 'queue.server_full',
                'payload' => $queueServerFullEvent,
            ];
            $state->previousQueueServerFullHash = $queueServerFullHash;
        }

        if (! $queueServerFullEvent) {
            $state->previousQueueServerFullHash = null;
        }

        $state->previousQueueSnapshot = $this->queueSnapshot($overview['queue']);

        $activeMatch = $overview['active_match'] instanceof RankedMatch
            ? $overview['active_match']
            : null;

        if (! $activeMatch instanceof RankedMatch) {
            $state->streamQueueMatchPublicId = null;
            $state->previousQueueMatchedId = null;
        } else {
            if ($state->streamQueueMatchPublicId !== $activeMatch->public_id) {
                $state->streamQueueMatchPublicId = $activeMatch->public_id;
                $state->previousQueueMatchedId = null;
            }

            if ($state->previousQueueMatchedId === null) {
                $queueMatchedEvent = $this->eventPayloadBuilder->firstEventForUser(
                    $activeMatch,
                    $user,
                    'queue.matched',
                );

                if ($queueMatchedEvent) {
                    $messages[] = [
                        'event' => 'queue.matched',
                        'payload' => $queueMatchedEvent,
                    ];
                    $state->previousQueueMatchedId = $queueMatchedEvent['id'] ?? null;
                }
            }

            if ($state->streamHeartbeatMatchPublicId !== $activeMatch->public_id) {
                $state->streamHeartbeatMatchPublicId = $activeMatch->public_id;
                $state->lastHeartbeatAt = null;
            }

            $heartbeatEvent = $this->heartbeatEvent($activeMatch, $user, $state->lastHeartbeatAt);

            if ($heartbeatEvent) {
                $messages[] = [
                    'event' => 'heartbeat',
                    'payload' => $heartbeatEvent,
                ];
                $state->lastHeartbeatAt = Carbon::parse($heartbeatEvent['timestamp']);
            }
        }

        $relevantMatch = $overview['active_match'] ?? $recentMatch;

        if (! $relevantMatch instanceof RankedMatch) {
            $state->streamMatchPublicId = null;
            $state->streamHeartbeatMatchPublicId = null;
            $state->afterEventId = null;
            $state->lastHeartbeatAt = null;
        } else {
            if ($state->streamMatchPublicId !== $relevantMatch->public_id) {
                $state->streamMatchPublicId = $relevantMatch->public_id;
                $state->afterEventId = null;
            }

            $events = $this->eventPayloadBuilder->eventsForUser($relevantMatch, $user, $state->afterEventId);

            if ($events !== []) {
                $state->afterEventId = collect($events)->last()['id'] ?? $state->afterEventId;

                $messages[] = [
                    'event' => 'match.events',
                    'payload' => [
                        'data' => [
                            'match_public_id' => $relevantMatch->public_id,
                            'events' => $events,
                            'latest_event_id' => $state->afterEventId,
                        ],
                    ],
                ];
            }
        }

        return $messages;
    }

    /**
     * @param  array{rating: RankedPlayerRating, queue: RankedQueueEntry|null, active_match: RankedMatch|null, capacity: array<string, mixed>}  $overview
     * @return array<string, mixed>
     */
    public function overviewPayload(array $overview, ?RankedMatch $recentMatch, User $user): array
    {
        return [
            'state' => $this->payloadBuilder->overviewState($overview['active_match'], $overview['queue'], $user),
            'rating' => $this->payloadBuilder->rating($overview['rating']),
            'queue' => $this->payloadBuilder->queueEntry($overview['queue']),
            'capacity' => $this->payloadBuilder->capacity($overview['capacity']),
            'active_match' => $this->payloadBuilder->match($overview['active_match'], $user),
            'recent_match' => $this->payloadBuilder->historyEntry(
                $recentMatch ?? $this->rankedMatchService->recentMatch($user),
                $user,
            ),
        ];
    }

    public function queueSnapshot(?RankedQueueEntry $queueEntry): ?array
    {
        if (! $queueEntry || ! in_array($queueEntry->status, ['queued', 'server_full'], true)) {
            return null;
        }

        $queueEntry->loadMissing('licenseCategory');

        return [
            'queue_entry_id' => $queueEntry->getKey(),
            'status' => $queueEntry->status,
            'joined_at' => $queueEntry->joined_at?->toIso8601String(),
            'category' => $queueEntry->licenseCategory
                ? [
                    'id' => $queueEntry->licenseCategory->getKey(),
                    'code' => $queueEntry->licenseCategory->code,
                    'name' => $queueEntry->licenseCategory->name,
                    'short_name' => $queueEntry->licenseCategory->short_name,
                ]
                : null,
        ];
    }

    public function queueLeftEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, User $user): ?array
    {
        if (! $previousQueueSnapshot || $currentQueueEntry) {
            return null;
        }

        $timestamp = now()->toIso8601String();

        return [
            'event' => 'queue.left',
            'id' => sprintf(
                'evt_queue_left_user_%d_queue_%d_%s',
                $user->getKey(),
                (int) ($previousQueueSnapshot['queue_entry_id'] ?? 0),
                str_replace([':', '.'], '', $timestamp),
            ),
            'api_version' => '1.0',
            'timestamp' => $timestamp,
            'data' => [
                'user_id' => $user->getKey(),
                'queue_entry_id' => $previousQueueSnapshot['queue_entry_id'] ?? null,
                'previous_status' => $previousQueueSnapshot['status'] ?? null,
                'joined_at' => $previousQueueSnapshot['joined_at'] ?? null,
                'left_at' => $timestamp,
                'category' => $previousQueueSnapshot['category'] ?? null,
            ],
        ];
    }

    public function queueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, User $user): ?array
    {
        if (
            ! $previousQueueSnapshot
            || ! $currentQueueEntry
            || ($previousQueueSnapshot['status'] ?? null) !== 'server_full'
            || $currentQueueEntry->status !== 'queued'
            || (int) ($previousQueueSnapshot['queue_entry_id'] ?? 0) !== $currentQueueEntry->getKey()
        ) {
            return null;
        }

        $currentQueueEntry->loadMissing('licenseCategory');
        $timestamp = data_get($currentQueueEntry->payload, 'resumed_at')
            ?? $currentQueueEntry->updated_at?->toIso8601String()
            ?? now()->toIso8601String();

        return [
            'event' => 'queue.resumed',
            'id' => sprintf(
                'evt_queue_resumed_user_%d_queue_%d_%s',
                $user->getKey(),
                $currentQueueEntry->getKey(),
                str_replace([':', '.'], '', (string) $timestamp),
            ),
            'api_version' => '1.0',
            'timestamp' => (string) $timestamp,
            'data' => [
                'user_id' => $user->getKey(),
                'queue_entry_id' => $currentQueueEntry->getKey(),
                'previous_status' => $previousQueueSnapshot['status'] ?? 'server_full',
                'status' => $currentQueueEntry->status,
                'joined_at' => $currentQueueEntry->joined_at?->toIso8601String(),
                'resumed_at' => data_get($currentQueueEntry->payload, 'resumed_at'),
                'category' => $currentQueueEntry->licenseCategory
                    ? [
                        'id' => $currentQueueEntry->licenseCategory->getKey(),
                        'code' => $currentQueueEntry->licenseCategory->code,
                        'name' => $currentQueueEntry->licenseCategory->name,
                        'short_name' => $currentQueueEntry->licenseCategory->short_name,
                    ]
                    : null,
            ],
        ];
    }

    public function heartbeatEvent(RankedMatch $match, User $user, ?Carbon $lastHeartbeatAt): ?array
    {
        if (! in_array($match->status, ['matched', 'in_progress'], true)) {
            return null;
        }

        $now = now();

        if (
            $lastHeartbeatAt instanceof Carbon
            && $now->diffInSeconds($lastHeartbeatAt) < RankedMatchService::HEARTBEAT_SECONDS
        ) {
            return null;
        }

        return [
            'event' => 'heartbeat',
            'id' => sprintf(
                'evt_heartbeat_match_%s_%s',
                $match->public_id,
                $now->format('YmdHis'),
            ),
            'api_version' => $match->api_version,
            'timestamp' => $now->toIso8601String(),
            'data' => [
                'user_id' => $user->getKey(),
                'match_id' => $match->public_id,
                'heartbeat_seconds' => RankedMatchService::HEARTBEAT_SECONDS,
                'server_time' => $now->toIso8601String(),
                'expects_pong' => true,
                'transport' => 'live',
            ],
        ];
    }

    public function queueServerFullEvent(?RankedQueueEntry $queueEntry, User $user): ?array
    {
        if (! $queueEntry || $queueEntry->status !== 'server_full') {
            return null;
        }

        $currentCapacity = (int) data_get($queueEntry->payload, 'current_capacity', RankedMatchService::MAX_CONCURRENT_PLAYERS);
        $maxConcurrentPlayers = (int) data_get($queueEntry->payload, 'max_concurrent_players', RankedMatchService::MAX_CONCURRENT_PLAYERS);
        $positionInQueue = data_get($queueEntry->payload, 'position_in_queue');
        $estimatedWaitMinutes = data_get($queueEntry->payload, 'estimated_wait_minutes');
        $timestamp = data_get($queueEntry->payload, 'recorded_at')
            ?? $queueEntry->updated_at?->toIso8601String()
            ?? $queueEntry->created_at?->toIso8601String()
            ?? now()->toIso8601String();

        return [
            'event' => 'queue.server_full',
            'id' => sprintf(
                'evt_queue_server_full_user_%d_queue_%d_%d_%d_%s',
                $user->getKey(),
                $queueEntry->getKey(),
                $currentCapacity,
                $maxConcurrentPlayers,
                (string) ($positionInQueue ?? 'waiting'),
            ),
            'api_version' => '1.0',
            'timestamp' => $timestamp,
            'data' => [
                'user_id' => $user->getKey(),
                'queue_entry_id' => $queueEntry->getKey(),
                'position_in_queue' => $positionInQueue,
                'current_capacity' => $currentCapacity,
                'max_concurrent_players' => $maxConcurrentPlayers,
                'estimated_wait_minutes' => $estimatedWaitMinutes,
                'message' => data_get($queueEntry->payload, 'message'),
                'error_code' => data_get($queueEntry->payload, 'error_code', 'SERVER_FULL'),
            ],
        ];
    }

    public function queueQueuedEvent(?RankedQueueEntry $queueEntry, User $user): ?array
    {
        if (! $queueEntry || $queueEntry->status !== 'queued') {
            return null;
        }

        $queueEntry->loadMissing('licenseCategory');

        $timestamp = $queueEntry->joined_at?->toIso8601String()
            ?? $queueEntry->created_at?->toIso8601String()
            ?? now()->toIso8601String();

        return [
            'event' => 'queue.queued',
            'id' => sprintf(
                'evt_queue_queued_user_%d_queue_%d_%s',
                $user->getKey(),
                $queueEntry->getKey(),
                str_replace([':', '.'], '', $timestamp),
            ),
            'api_version' => '1.0',
            'timestamp' => $timestamp,
            'data' => [
                'user_id' => $user->getKey(),
                'queue_entry_id' => $queueEntry->getKey(),
                'status' => $queueEntry->status,
                'joined_at' => $queueEntry->joined_at?->toIso8601String(),
                'category' => $queueEntry->licenseCategory
                    ? [
                        'id' => $queueEntry->licenseCategory->getKey(),
                        'code' => $queueEntry->licenseCategory->code,
                        'name' => $queueEntry->licenseCategory->name,
                        'short_name' => $queueEntry->licenseCategory->short_name,
                    ]
                    : null,
            ],
        ];
    }

    public function queueServerFullErrorEvent(array $queueServerFullEvent, User $user): array
    {
        return [
            'event' => 'error',
            'id' => sprintf(
                'evt_error_server_full_user_%d_%s',
                $user->getKey(),
                str_replace([':', '.'], '', (string) ($queueServerFullEvent['timestamp'] ?? now()->toIso8601String())),
            ),
            'api_version' => '1.0',
            'timestamp' => $queueServerFullEvent['timestamp'] ?? now()->toIso8601String(),
            'data' => [
                'error_code' => data_get($queueServerFullEvent, 'data.error_code', 'SERVER_FULL'),
                'message' => data_get($queueServerFullEvent, 'data.message', 'Server full (100/100). Waiting for space...'),
                'details' => [
                    'channel' => 'queue',
                    'user_id' => $user->getKey(),
                    'queue_entry_id' => data_get($queueServerFullEvent, 'data.queue_entry_id'),
                    'position_in_queue' => data_get($queueServerFullEvent, 'data.position_in_queue'),
                    'current_capacity' => data_get($queueServerFullEvent, 'data.current_capacity'),
                    'max_concurrent_players' => data_get($queueServerFullEvent, 'data.max_concurrent_players'),
                    'estimated_wait_minutes' => data_get($queueServerFullEvent, 'data.estimated_wait_minutes'),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function websocketPayload(string $eventName, array $payload): array
    {
        return [
            'event' => $eventName,
            ...$payload,
        ];
    }
}
