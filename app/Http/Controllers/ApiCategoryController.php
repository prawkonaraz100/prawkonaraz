<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\StudyContextService;
use Illuminate\Http\JsonResponse;

class ApiCategoryController extends Controller
{
    public function index(StudyContextService $studyContextService): JsonResponse
    {
        $categories = $studyContextService->visibleCategoriesQuery()
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LicenseCategory $licenseCategory) => $this->transform($licenseCategory))
            ->values();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function show(LicenseCategory $licenseCategory): JsonResponse
    {
        abort_unless($licenseCategory->is_active, 404);

        $licenseCategory->loadCount([
            'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
        ]);

        return response()->json([
            'data' => $this->transform($licenseCategory),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(LicenseCategory $licenseCategory): array
    {
        $studyContextService = app(StudyContextService::class);

        return [
            'id' => $licenseCategory->getKey(),
            'code' => $licenseCategory->code,
            'slug' => $licenseCategory->slug,
            'name' => $licenseCategory->name,
            'short_name' => $studyContextService->shortCategoryName($licenseCategory),
            'description' => $licenseCategory->description,
            'is_active' => $licenseCategory->is_active,
            'questions_count' => $licenseCategory->questions_count,
        ];
    }
}
