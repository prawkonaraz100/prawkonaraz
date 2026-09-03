<?php

namespace App\Http\Controllers;

use App\Support\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthApiController extends Controller
{
    public function __invoke(HealthCheckService $healthCheckService): JsonResponse
    {
        $report = $healthCheckService->report();

        return response()->json([
            'data' => $report,
        ], $report['status'] === 'failed' ? 503 : 200);
    }
}
