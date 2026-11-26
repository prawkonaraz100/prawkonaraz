<?php

namespace App\Http\Controllers;

use App\Models\RankedQueueEntry;
use App\Support\RankedRealtimeEventStreamPublisher;
use App\Support\RankedRealtimeStreamLoop;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiRankedStreamController extends Controller
{
    public function __invoke(
        Request $request,
        RankedRealtimeEventStreamPublisher $publisher,
        RankedRealtimeStreamLoop $loop,
    ): StreamedResponse {
        $user = $request->user();
        $maxTicks = max(1, min($request->integer('max_ticks', 25), 60));
        $sleepMs = max(0, min($request->integer('sleep_ms', 1000), 5000));
        $lightweightLifetime = $request->has('max_ticks')
            ? null
            : (float) config('ranked.sse_lightweight_lifetime_seconds', 55);

        return response()->stream(function () use (
            $lightweightLifetime,
            $loop,
            $maxTicks,
            $publisher,
            $sleepMs,
            $user,
        ): void {
            ignore_user_abort(true);

            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $emit = function (string $eventName, array $payload): void {
                echo "event: {$eventName}\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                @ob_flush();
                @flush();
            };

            $keepalive = function (): void {
                echo ": keepalive\n\n";
                @ob_flush();
                @flush();
            };

            echo "retry: 2000\n\n";
            @ob_flush();
            @flush();

            $emit('stream.ready', $publisher->streamReadyPayload());

            // Tryb lekki, gdy działa wspólny zegar (etap 1e); inaczej dawne liczenie stanu co sleep_ms.
            $loop->run($user, $emit, $keepalive, $maxTicks, $sleepMs, $lightweightLifetime);
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
