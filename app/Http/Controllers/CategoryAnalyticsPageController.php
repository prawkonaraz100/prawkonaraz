<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\CategoryAnalyticsService;
use App\Support\StudyContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryAnalyticsPageController extends Controller
{
    public function __invoke(
        Request $request,
        LicenseCategory $licenseCategory,
        CategoryAnalyticsService $categoryAnalyticsService,
        StudyContextService $studyContextService,
    ): Response {
        abort_unless($licenseCategory->is_active, 404);
        $studyContextService->assertUserCanUseCategory($request->user(), $licenseCategory);

        $categories = $studyContextService->activeCategories($request->user());
        $questionCounts = LicenseCategory::query()
            ->whereKey($categories->pluck('id'))
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
            ])
            ->get()
            ->keyBy('id');

        return Inertia::render('Analytics/Categories/Show', [
            ...$categoryAnalyticsService->build($request->user(), $licenseCategory),
            'categories' => $categories
                ->map(fn (LicenseCategory $category) => [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'name' => $category->name,
                    'questions_count' => (int) ($questionCounts->get($category->getKey())?->questions_count ?? 0),
                ])
                ->values(),
            'filters' => [
                'category' => $licenseCategory->getKey(),
            ],
        ]);
    }
}
