<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertUserProductProfileRequest;
use App\Models\LicenseCategory;
use App\Support\StudyContextService;
use App\Support\UserAvatarService;
use App\Support\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeProfileController extends Controller
{
    public function show(
        Request $request,
        UserProfileService $userProfileService,
        StudyContextService $studyContextService,
    ): JsonResponse {
        $profile = $userProfileService->profileFor($request->user())->load('targetCategory');

        return response()->json([
            'data' => $this->transformProfile($request->user(), $profile),
            'meta' => [
                'categories' => $this->categories($studyContextService),
            ],
        ]);
    }

    public function update(
        UpsertUserProductProfileRequest $request,
        UserProfileService $userProfileService,
    ): JsonResponse {
        $profile = $userProfileService->update($request->user(), $request->validated());

        return response()->json([
            'data' => $this->transformProfile($request->user(), $profile),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformProfile($user, $profile): array
    {
        return [
            'display_name' => $profile->display_name,
            'email' => $user->email,
            'avatar_url' => app(UserAvatarService::class)->url($user),
            'has_uploaded_avatar' => filled($user->avatar_path),
            'target_category_id' => $profile->target_category_id,
            'target_category' => $profile->targetCategory ? [
                'id' => $profile->targetCategory->getKey(),
                'code' => $profile->targetCategory->code,
                'name' => $profile->targetCategory->name,
                'short_name' => app(StudyContextService::class)->shortCategoryName($profile->targetCategory),
            ] : null,
            'exam_date' => $profile->exam_date?->toDateString(),
            'study_streak' => $profile->study_streak,
            'last_study_date' => $profile->last_study_date?->toDateString(),
            'tier' => $profile->tier,
            'onboarding_step' => $profile->onboarding_step,
            'visual_explanations_enabled' => $profile->visual_explanations_enabled,
            'auto_remove_incorrect_questions_on_correct' => $profile->auto_remove_incorrect_questions_on_correct,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function categories(StudyContextService $studyContextService): array
    {
        return $studyContextService->activeCategories()
            ->map(fn (LicenseCategory $category) => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'short_name' => $studyContextService->shortCategoryName($category),
            ])
            ->values()
            ->all();
    }
}
