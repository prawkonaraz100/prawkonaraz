<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Models\StreakRun;
use App\Support\StreakChallengeService;
use App\Support\StudyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StreakChallengeController extends Controller
{
    public function state(
        Request $request,
        StudyContextService $studyContextService,
        StreakChallengeService $streakChallengeService,
    ): JsonResponse {
        return response()->json($streakChallengeService->overview(
            $request->user(),
            $this->category($request, $studyContextService),
        ));
    }

    public function start(
        Request $request,
        StudyContextService $studyContextService,
        StreakChallengeService $streakChallengeService,
    ): JsonResponse {
        return response()->json($streakChallengeService->start(
            $request->user(),
            $this->category($request, $studyContextService),
        ));
    }

    public function answer(
        Request $request,
        StreakRun $streakRun,
        StreakChallengeService $streakChallengeService,
    ): JsonResponse {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_answer' => ['required', 'string', Rule::in(['a', 'b', 'c'])],
        ]);

        return response()->json($streakChallengeService->answer(
            $request->user(),
            $streakRun,
            (int) $validated['question_id'],
            (string) $validated['selected_answer'],
        ));
    }

    protected function category(Request $request, StudyContextService $studyContextService): LicenseCategory
    {
        $categoryId = $studyContextService->requestedOrPreferredCategoryId($request, 'category_id');
        $category = $studyContextService->visibleCategoriesQuery()->findOrFail($categoryId);
        $studyContextService->assertUserCanUseCategory($request->user(), $category);

        return $category;
    }
}
