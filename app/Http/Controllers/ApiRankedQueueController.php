<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinRankedQueueRequest;
use App\Models\LicenseCategory;
use App\Support\RankedMatchPayloadBuilder;
use App\Support\RankedMatchService;
use App\Support\StudyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiRankedQueueController extends Controller
{
    public function overview(
        Request $request,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $overview = $rankedMatchService->overview($request->user());

        return response()->json([
            'data' => [
                'state' => $payloadBuilder->overviewState($overview['active_match'], $overview['queue'], $request->user()),
                'rating' => $payloadBuilder->rating($overview['rating']),
                'queue' => $payloadBuilder->queueEntry($overview['queue']),
                'capacity' => $payloadBuilder->capacity($overview['capacity']),
                'active_match' => $payloadBuilder->match($overview['active_match'], $request->user()),
                'recent_match' => $payloadBuilder->historyEntry(
                    $rankedMatchService->recentMatch($request->user()),
                    $request->user(),
                ),
            ],
        ]);
    }

    public function join(
        JoinRankedQueueRequest $request,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
        StudyContextService $studyContextService,
    ): JsonResponse {
        $category = LicenseCategory::query()->findOrFail($request->integer('category_id'));
        $studyContextService->assertUserCanUseCategory($request->user(), $category);
        $result = $rankedMatchService->joinQueue($request->user(), $category);

        return response()->json([
            'data' => [
                'joined' => $result['joined'],
                'state' => $payloadBuilder->overviewState($result['active_match'], $result['queue'], $request->user()),
                'rating' => $payloadBuilder->rating($result['rating']),
                'queue' => $payloadBuilder->queueEntry($result['queue']),
                'capacity' => $payloadBuilder->capacity($result['capacity']),
                'active_match' => $payloadBuilder->match($result['active_match'], $request->user()),
                'recent_match' => $payloadBuilder->historyEntry(
                    $rankedMatchService->recentMatch($request->user()),
                    $request->user(),
                ),
            ],
        ]);
    }

    public function leave(
        Request $request,
        RankedMatchService $rankedMatchService,
        RankedMatchPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $result = $rankedMatchService->leaveQueue($request->user());

        return response()->json([
            'data' => [
                'left_queue' => $result['left_queue'],
                'state' => $payloadBuilder->overviewState($result['active_match'], $result['queue'], $request->user()),
                'rating' => $payloadBuilder->rating($result['rating']),
                'queue' => $payloadBuilder->queueEntry($result['queue']),
                'capacity' => $payloadBuilder->capacity($result['capacity']),
                'active_match' => $payloadBuilder->match($result['active_match'], $request->user()),
                'recent_match' => $payloadBuilder->historyEntry(
                    $rankedMatchService->recentMatch($request->user()),
                    $request->user(),
                ),
            ],
        ]);
    }
}
