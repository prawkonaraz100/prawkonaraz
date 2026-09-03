<?php

namespace App\Http\Controllers;

use App\Models\RankedQueueEntry;
use App\Support\RankedRealtimeConnectionState;
use App\Support\RankedRealtimeEventStreamPublisher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiRankedStreamController extends Controller
{
    public function __invoke(
        Request $request,
        RankedRealtimeEventStreamPublisher $publisher,
    ): StreamedResponse {
        $user = $request->user();
        $maxTicks = max(1, min($request->integer('max_ticks', 25), 60));
        $sleepMs = max(0, min($request->integer('sleep_ms', 1000), 5000));

        return response()->stream(function () use (
            $maxTicks,
            $publisher,
            $sleepMs,
            $user,
        ): void {
            ignore_user_abort(true);

            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $previousOverviewHash = null;
            $previousQueueSnapshot = null;
            $previousQueueQueuedHash = null;
            $previousQueueServerFullHash = null;
            $previousQueueMatchedId = null;
            $streamMatchPublicId = null;
            $streamQueueMatchPublicId = null;
            $streamHeartbeatMatchPublicId = null;
            $afterEventId = null;
            $lastHeartbeatAt = null;

            $emit = function (string $eventName, array $payload): void {
                echo "event: {$eventName}\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                @ob_flush();
                @flush();
            };

            echo "retry: 2000\n\n";
            @ob_flush();
            @flush();

            $state = new RankedRealtimeConnectionState;

            $emit('stream.ready', $publisher->streamReadyPayload());

            for ($tick = 0; $tick < $maxTicks; $tick++) {
                if (connection_aborted()) {
                    break;
                }

                foreach ($publisher->publish($user, $state) as $message) {
                    $emit($message['event'], $message['payload']);
                }

                if ($tick < $maxTicks - 1) {
                    echo ": keepalive\n\n";
                    @ob_flush();
                    @flush();

                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function queueSnapshot(?RankedQueueEntry $queueEntry): ?array
    {
        return app(RankedRealtimeEventStreamPublisher::class)->queueSnapshot($queueEntry);
    }

    protected function queueLeftEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
    {
        return app(RankedRealtimeEventStreamPublisher::class)->queueLeftEvent($previousQueueSnapshot, $currentQueueEntry, $user);
    }

    protected function queueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
    {
        return app(RankedRealtimeEventStreamPublisher::class)->queueResumedEvent($previousQueueSnapshot, $currentQueueEntry, $user);
    }
}
