<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $dashboardMetricsService): JsonResponse
    {
        $payload = $dashboardMetricsService->build($request->user());

        return response()->json([
            'data' => $payload['stats'],
            'meta' => [
                'categories' => $payload['categories']->values(),
            ],
        ]);
    }
}
