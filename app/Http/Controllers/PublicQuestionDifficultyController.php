<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\PublicQuestionDifficultyService;
use App\Support\StudyContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicQuestionDifficultyController extends Controller
{
    public function index(
        Request $request,
        PublicQuestionDifficultyService $publicQuestionDifficultyService,
    ): View {
        $ranking = $this->validatedRanking($request);

        return $this->renderDifficultyPage($publicQuestionDifficultyService->build(
            selectedCategory: null,
            ranking: $ranking,
        ));
    }

    public function showCategory(
        string $categorySlug,
        Request $request,
        PublicQuestionDifficultyService $publicQuestionDifficultyService,
        StudyContextService $studyContextService,
    ): View {
        $category = $studyContextService->visibleCategoriesQuery()
            ->where('slug', $categorySlug)
            ->first();

        abort_unless($category instanceof LicenseCategory, 404);

        $ranking = $this->validatedRanking($request);

        return $this->renderDifficultyPage($publicQuestionDifficultyService->build(
            selectedCategory: $category,
            ranking: $ranking,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function renderDifficultyPage(array $payload): View
    {
        $page = $payload['page'];

        return view('hardest-questions.index', [
            ...$payload,
            'meta' => [
                'title' => $page['title'],
                'description' => $page['description'],
                'canonical' => url($page['canonical_path']),
            ],
        ]);
    }

    protected function validatedRanking(Request $request): string
    {
        $validated = $request->validate([
            'ranking' => ['nullable', 'in:overall,first_try,repeat_fail,mastery_lag'],
        ]);

        return (string) ($validated['ranking'] ?? 'overall');
    }
}
