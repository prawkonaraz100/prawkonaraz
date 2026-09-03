<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\CategoryAnalyticsService;
use App\Support\StudyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryAnalyticsController extends Controller
{
    public function __invoke(
        Request $request,
        LicenseCategory $licenseCategory,
        CategoryAnalyticsService $categoryAnalyticsService,
        StudyContextService $studyContextService,
    ): JsonResponse {
        abort_unless($licenseCategory->is_active, 404);
        $studyContextService->assertUserCanUseCategory($request->user(), $licenseCategory);

        return response()->json([
            'data' => $categoryAnalyticsService->build($request->user(), $licenseCategory),
        ]);
    }
}
