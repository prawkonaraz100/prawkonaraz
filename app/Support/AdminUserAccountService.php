<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Illuminate\Support\Facades\DB;

class AdminUserAccountService
{
    /**
     * @return array<string, int>
     */
    public function changeTargetCategory(User $user, int $targetCategoryId, ?User $actor = null): array
    {
        $targetCategory = LicenseCategory::query()
            ->whereKey($targetCategoryId)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $targetCategory, $actor): array {
            $profile = app(UserProfileService::class)->profileFor($user);
            $oldCategoryId = $profile->target_category_id ? (int) $profile->target_category_id : null;
            $newCategoryId = (int) $targetCategory->getKey();

            if ($oldCategoryId === $newCategoryId) {
                return [];
            }

            $oldCategory = $oldCategoryId
                ? LicenseCategory::query()->find($oldCategoryId)
                : null;
            $resetCounts = $this->resetLearningData($user);

            $profile->forceFill([
                'target_category_id' => $newCategoryId,
                'study_streak' => 0,
                'last_study_date' => null,
                'onboarding_step' => 'admin_category_changed',
            ])->save();

            $user->unsetRelation('profile');

            app(AuditLogService::class)->record(
                'user.target_category_changed',
                'user',
                (string) $user->getKey(),
                $actor,
                [
                    'old_target_category_id' => $oldCategoryId,
                    'old_target_category_code' => $oldCategory?->code,
                    'new_target_category_id' => $newCategoryId,
                    'new_target_category_code' => $targetCategory->code,
                    'reset_counts' => $resetCounts,
                ],
            );

            return $resetCounts;
        });
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function recordAdminSettingsChanges(User $user, array $before, ?User $actor = null): void
    {
        $after = $this->adminSettingsSnapshot($user);
        $changes = [];

        foreach ($after as $key => $value) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue === $value) {
                continue;
            }

            $changes[$key] = [
                'old' => $oldValue,
                'new' => $value,
            ];
        }

        if ($changes === []) {
            return;
        }

        app(AuditLogService::class)->record(
            'user.admin_settings_changed',
            'user',
            (string) $user->getKey(),
            $actor,
            ['changes' => $changes],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function adminSettingsSnapshot(User $user): array
    {
        return [
            'role' => (string) $user->role,
            'is_admin' => (bool) $user->is_admin,
            'is_test_account' => (bool) $user->is_test_account,
            'moderator_quota' => $user->moderatorQuotaLimit(),
            'requires_password_change' => (bool) $user->requires_password_change,
            'is_temporary_account' => (bool) $user->is_temporary_account,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function resetLearningData(User $user): array
    {
        $studySessionIds = StudySession::query()
            ->where('user_id', $user->getKey())
            ->pluck('id');

        $counts = [
            'study_sessions' => $studySessionIds->count(),
            'study_session_answers' => $studySessionIds->isEmpty()
                ? 0
                : StudySessionAnswer::query()->whereIn('study_session_id', $studySessionIds)->count(),
            'question_progress' => UserQuestionProgress::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'review_memory_progress' => ReviewMemoryProgress::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'review_trainer_daily_answers' => ReviewTrainerDailyAnswer::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'ranked_queue_entries' => RankedQueueEntry::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'ranked_player_ratings' => RankedPlayerRating::query()
                ->where('user_id', $user->getKey())
                ->count(),
        ];

        if (! $studySessionIds->isEmpty()) {
            StudySessionAnswer::query()
                ->whereIn('study_session_id', $studySessionIds)
                ->delete();

            StudySession::query()
                ->whereIn('id', $studySessionIds)
                ->delete();
        }

        UserQuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->delete();

        ReviewMemoryProgress::query()
            ->where('user_id', $user->getKey())
            ->delete();

        ReviewTrainerDailyAnswer::query()
            ->where('user_id', $user->getKey())
            ->delete();

        RankedQueueEntry::query()
            ->where('user_id', $user->getKey())
            ->delete();

        RankedPlayerRating::query()
            ->where('user_id', $user->getKey())
            ->delete();

        return $counts;
    }
}
