<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\PublicQuestionDifficultyService;
use App\Support\StudyContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuestionDifficultyController extends Controller
{
    public function index(
        Request $request,
        PublicQuestionDifficultyService $publicQuestionDifficultyService,
    ): Response {
        $ranking = $this->validatedRanking($request);

        return Inertia::render('Public/HardestQuestions/Index', $publicQuestionDifficultyService->build(
            selectedCategory: null,
            ranking: $ranking,
        ));
    }

    public function showCategory(
        string $categorySlug,
        Request $request,
        PublicQuestionDifficultyService $publicQuestionDifficultyService,
        StudyContextService $studyContextService,
    ): Response {
        $category = $studyContextService->visibleCategoriesQuery()
            ->where('slug', $categorySlug)
            ->first();

        abort_unless($category instanceof LicenseCategory, 404);

        $ranking = $this->validatedRanking($request);

        return Inertia::render('Public/HardestQuestions/Index', $publicQuestionDifficultyService->build(
            selectedCategory: $category,
            ranking: $ranking,
        ));
    }

    protected function validatedRanking(Request $request): string
    {
        $validated = $request->validate([
            'ranking' => ['nullable', 'in:overall,first_try,repeat_fail,mastery_lag'],
        ]);

        return (string) ($validated['ranking'] ?? 'overall');
    }
}
