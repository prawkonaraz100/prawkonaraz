<?php

namespace App\Http\Controllers;

use App\Http\Requests\RankedMatchAnswerStoreRequest;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Support\RankedMatchEventPayloadBuilder;
use App\Support\RankedMatchPayloadBuilder;
use App\Support\RankedMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiRankedMatchController extends Controller
{
    protected function overviewPayload(
        Request $request,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): array {
        $overview = $rankedMatchService->overview($request->user());

        return [
            'state' => $payloadBuilder->overviewState($overview['active_match'], $overview['queue'], $request->user()),
            'rating' => $payloadBuilder->rating($overview['rating']),
            'queue' => $payloadBuilder->queueEntry($overview['queue']),
            'capacity' => $payloadBuilder->capacity($overview['capacity']),
            'active_match' => $payloadBuilder->match($overview['active_match'], $request->user()),
            'recent_match' => $payloadBuilder->historyEntry(
                $rankedMatchService->recentMatch($request->user()),
                $request->user(),
            ),
        ];
    }

    public function history(
        Request $request,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        return response()->json([
            'data' => [
                'matches' => $rankedMatchService->recentMatches($request->user(), 8)
                    ->map(fn (RankedMatch $match) => $payloadBuilder->historyEntry($match, $request->user()))
                    ->filter()
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function show(
        Request $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $match = $rankedMatchService->matchForUser($request->user(), $rankedMatch->public_id);

        return response()->json([
            'data' => $payloadBuilder->matchDetails($match, $request->user()),
        ]);
    }

    public function events(
        Request $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchEventPayloadBuilder $eventPayloadBuilder,
    ): JsonResponse {
        $match = $rankedMatchService->matchForUser($request->user(), $rankedMatch->public_id);
        $afterEventId = $request->string('after_event_id')->trim()->value() ?: null;
        $events = $eventPayloadBuilder->eventsForUser($match, $request->user(), $afterEventId);

        return response()->json([
            'data' => [
                'match_public_id' => $match->public_id,
                'events' => $events,
                'latest_event_id' => collect($events)->last()['id'] ?? $afterEventId,
            ],
        ]);
    }

    public function answer(
        RankedMatchAnswerStoreRequest $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $question = Question::query()->findOrFail($request->integer('question_id'));
        $answer = $rankedMatchService->recordAnswer(
            $rankedMatch,
            $request->user(),
            $question,
            (string) $request->string('user_answer'),
            $request->filled('response_time_ms') ? $request->integer('response_time_ms') : null,
        );

        $match = $rankedMatchService->matchForUser($request->user(), $rankedMatch->public_id);

        return response()->json([
            'data' => [
                'accepted' => $answer->wasRecentlyCreated,
                'answer' => [
                    'question_id' => $answer->question_id,
                    'question_number' => $answer->question_number,
                    'selected_answer' => strtoupper($answer->selected_answer),
                    'is_correct' => $answer->is_correct,
                    'response_time_ms' => $answer->response_time_ms,
                    'answered_at' => $answer->answered_at?->toIso8601String(),
                ],
                'match' => $payloadBuilder->matchSummary($match, $request->user()),
                'progress' => $payloadBuilder->playerProgress($match, $request->user()),
            ],
        ]);
    }

    public function pong(
        Request $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $match = $rankedMatchService->recordPong($rankedMatch, $request->user());

        return response()->json([
            'data' => [
                'acknowledged' => true,
                'server_time' => now()->toIso8601String(),
                'state' => $payloadBuilder->matchState($match, $request->user()),
                'match' => $payloadBuilder->matchSummary($match, $request->user()),
                'progress' => $payloadBuilder->playerProgress($match, $request->user()),
            ],
        ]);
    }

    public function ready(
        Request $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $match = $rankedMatchService->recordReady($rankedMatch, $request->user());

        return response()->json([
            'data' => [
                'acknowledged' => true,
                'server_time' => now()->toIso8601String(),
                'state' => $payloadBuilder->matchState($match, $request->user()),
                'match' => $payloadBuilder->matchSummary($match, $request->user()),
                'progress' => $payloadBuilder->playerProgress($match, $request->user()),
            ],
        ]);
    }

    public function abandon(
        Request $request,
        RankedMatch $rankedMatch,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $rankedMatchService->abandonMatchByUser($rankedMatch, $request->user());

        return response()->json([
            'data' => [
                'abandoned_match' => true,
                ...$this->overviewPayload($request, $rankedMatchService, $payloadBuilder),
            ],
        ]);
    }
}
