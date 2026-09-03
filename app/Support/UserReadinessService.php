<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserQuestionProgress;

class UserReadinessService
{
    public function score(User $user, ?int $categoryId = null): float
    {
        $progressEntries = UserQuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('total_attempts', '>', 0)
            ->whereHas('question', function ($questionQuery) use ($categoryId): void {
                $questionQuery
                    ->where('is_active', true)
                    ->readyForDelivery()
                    ->when($categoryId, fn ($query) => $query->where('license_category_id', $categoryId));
            })
            ->get();

        if ($progressEntries->isEmpty()) {
            return round((float) ($user->studySessions()
                ->regularCategory()
                ->where('status', 'completed')
                ->where('mode', '!=', StudySessionManager::MODE_SR_REVIEW)
                ->when($categoryId, fn ($query) => $query->where('license_category_id', $categoryId))
                ->avg('score_percent') ?? 0), 1);
        }

        return round((float) $progressEntries->avg(
            fn (UserQuestionProgress $progress) => $this->mastery($progress),
        ), 1);
    }

    public function mastery(UserQuestionProgress $progress): float
    {
        $attempts = max($progress->total_attempts, 1);
        $accuracy = $progress->correct_count / $attempts;
        $streakScore = (min($progress->correct_streak, 3) / 3) * 15;
        $freshnessScore = $progress->next_review_at?->isFuture() ? 15 : 0;
        $confidenceMultiplier = 0.4 + ((min($progress->total_attempts, 5) / 5) * 0.6);
        $baseScore = ($accuracy * 70) + $streakScore + $freshnessScore;

        return round($baseScore * $confidenceMultiplier, 2);
    }
}
