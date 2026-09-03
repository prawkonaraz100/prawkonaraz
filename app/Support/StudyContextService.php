<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudyContextService
{
    /**
     * Publiczny ekran nauki ma pokazywać wyłącznie oficjalne kategorie,
     * nawet jeśli w bazie pojawią się testowe lub wewnętrzne rekordy.
     *
     * @var list<string>
     */
    private const PUBLIC_CATEGORY_CODES = ['AM', 'A1', 'A2', 'A', 'B1', 'B', 'C1', 'C', 'D1', 'D', 'T'];

    public function visibleCategoriesQuery(): Builder
    {
        return LicenseCategory::query()
            ->where('is_active', true)
            ->whereIn('code', self::PUBLIC_CATEGORY_CODES)
            ->whereHas('questions', fn (Builder $query) => $query->where('is_active', true)->readyForDelivery());
    }

    /**
     * @return Collection<int, LicenseCategory>
     */
    public function activeCategories(?User $user = null): Collection
    {
        $categories = $this->allActiveCategories();

        if (! $user instanceof User || $user->canUseAllStudyCategories()) {
            return $categories;
        }

        $user->loadMissing('profile:user_id,target_category_id');
        $targetCategoryId = $user->profile?->target_category_id;

        if (! $targetCategoryId) {
            return $categories;
        }

        return $categories
            ->where('id', $targetCategoryId)
            ->values();
    }

    public function selectableCategoriesForProfile(User $user): Collection
    {
        if ($user->canUseAllStudyCategories() || $this->canChangeTargetCategory($user)) {
            return $this->allActiveCategories();
        }

        return $this->activeCategories($user);
    }

    public function canChangeTargetCategory(User $user): bool
    {
        if ($user->canUseAllStudyCategories()) {
            return true;
        }

        $user->loadMissing('profile:user_id,target_category_id');

        return $user->profile?->target_category_id === null;
    }

    public function assertUserCanUseCategory(User $user, LicenseCategory $category): void
    {
        if ($user->canUseAllStudyCategories()) {
            return;
        }

        $user->loadMissing('profile:user_id,target_category_id');

        if ((int) $user->profile?->target_category_id === (int) $category->getKey()) {
            return;
        }

        if ($user->profile?->target_category_id === null) {
            app(UserProfileService::class)->update($user, [
                'target_category_id' => $category->getKey(),
                'onboarding_step' => 'target_category_locked',
            ]);

            return;
        }

        throw new HttpException(403, 'Kategoria nauki jest przypisana na stałe do konta.');
    }

    public function canUseCategoryId(User $user, ?int $categoryId): bool
    {
        if ($categoryId === null) {
            return false;
        }

        return $this->activeCategories($user)
            ->contains(fn (LicenseCategory $category): bool => (int) $category->getKey() === $categoryId);
    }

    /**
     * @return Collection<int, LicenseCategory>
     */
    protected function allActiveCategories(): Collection
    {
        $officialOrder = array_flip(self::PUBLIC_CATEGORY_CODES);

        return $this->visibleCategoriesQuery()
            ->get(['id', 'code', 'name', 'sort_order'])
            ->sortBy(fn (LicenseCategory $category) => $officialOrder[$category->code] ?? PHP_INT_MAX)
            ->values();
    }

    public function shortCategoryName(LicenseCategory $category): string
    {
        return (string) $category->code;
    }

    public function preferredCategoryId(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('profile:user_id,target_category_id,visual_explanations_enabled,visual_explanations_mode');

        $targetCategoryId = $user->profile?->target_category_id;
        $activeCategories = $this->allActiveCategories();

        if ($targetCategoryId) {
            $resolvedCategoryId = $activeCategories
                ->firstWhere('id', $targetCategoryId)
                ?->getKey();

            if ($resolvedCategoryId) {
                return $resolvedCategoryId;
            }
        }

        return $activeCategories->first()?->getKey();
    }

    public function visualExplanationsEnabled(?User $user): bool
    {
        return $this->visualExplanationsMode($user) !== UserProfileService::VISUAL_EXPLANATIONS_MODE_OFF;
    }

    public function visualExplanationsMode(?User $user): string
    {
        if (! $user) {
            return UserProfileService::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT;
        }

        $user->loadMissing('profile:user_id,visual_explanations_enabled,visual_explanations_mode');
        $profile = $user->profile;

        if (! $profile) {
            return UserProfileService::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT;
        }

        $mode = $profile->visual_explanations_mode;

        if (in_array($mode, [
            UserProfileService::VISUAL_EXPLANATIONS_MODE_BEFORE_ANSWER,
            UserProfileService::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT,
            UserProfileService::VISUAL_EXPLANATIONS_MODE_OFF,
        ], true)) {
            return $mode;
        }

        return $profile->visual_explanations_enabled
            ? UserProfileService::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT
            : UserProfileService::VISUAL_EXPLANATIONS_MODE_OFF;
    }

    public function requestedOrPreferredCategoryId(Request $request, string $inputKey = 'category'): ?int
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->canUseAllStudyCategories()) {
            $user->loadMissing('profile:user_id,target_category_id');

            if ($user->profile?->target_category_id) {
                return (int) $user->profile->target_category_id;
            }
        }

        return $request->integer($inputKey) ?: $this->preferredCategoryId($request->user());
    }

    public function dueReviewCount(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $activeCategoryIds = $this->activeCategories($user)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($activeCategoryIds === []) {
            return 0;
        }

        $plan = app(ReviewPlannerService::class)->plan(
            $user,
            $this->preferredCategoryId($user),
            $activeCategoryIds,
        );

        return (int) ($plan['recommended_question_count'] ?? 0);
    }
}
