<?php

namespace App\Support;

use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Carbon\CarbonInterface;

class QuestionProgressManager
{
    // Postgres maps unsignedSmallInteger to smallint with non-negative check,
    // so the effective upper bound remains signed smallint max.
    public const MAX_INTERVAL_DAYS = 32767;

    public const MASTERED_MIN_REPETITIONS = 3;

    public const MASTERED_MIN_CORRECT_STREAK = 3;

    public const MASTERED_MIN_LAST_QUALITY = 4;

    public static function isMasteredSnapshot(
        ?int $totalAttempts,
        ?int $repetitions,
        ?int $correctStreak,
        ?int $lastQuality,
        mixed $nextReviewAt,
    ): bool {
        if (($totalAttempts ?? 0) <= 0) {
            return false;
        }

        $nextReviewDate = match (true) {
            $nextReviewAt instanceof CarbonInterface => $nextReviewAt->toDateString(),
            is_string($nextReviewAt) => $nextReviewAt,
            default => null,
        };

        return ($repetitions ?? 0) >= self::MASTERED_MIN_REPETITIONS
            && ($correctStreak ?? 0) >= self::MASTERED_MIN_CORRECT_STREAK
            && ($lastQuality ?? 0) >= self::MASTERED_MIN_LAST_QUALITY
            && $nextReviewDate !== null
            && $nextReviewDate > today()->toDateString();
    }

    public static function progressBucketFromSnapshot(
        ?int $totalAttempts,
        ?int $correctCount,
        ?int $incorrectCount,
        ?int $repetitions,
        ?int $correctStreak,
        ?int $lastQuality,
        mixed $nextReviewAt,
    ): string {
        if (($totalAttempts ?? 0) <= 0) {
            return 'unanswered';
        }

        if (self::isMasteredSnapshot(
            $totalAttempts,
            $repetitions,
            $correctStreak,
            $lastQuality,
            $nextReviewAt,
        )) {
            return 'memorized';
        }

        if (($incorrectCount ?? 0) > 0) {
            return 'incorrect';
        }

        if (($correctCount ?? 0) > 0) {
            return 'correct';
        }

        return 'unanswered';
    }

    public function recordAnswer(
        User $user,
        Question $question,
        bool $isCorrect,
        ?int $responseTimeMs = null,
        ?CarbonInterface $answeredAt = null,
    ): UserQuestionProgress {
        $answeredAt ??= now();

        $progress = UserQuestionProgress::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'question_id' => $question->getKey(),
        ]);

        $quality = $this->qualityScore($isCorrect, $responseTimeMs);
        $easinessFactor = (float) ($progress->easiness_factor ?? 2.50);
        $intervalDays = $this->normalizeIntervalDays((int) ($progress->interval_days ?? 1));
        $repetitions = (int) ($progress->repetitions ?? 0);

        if ($quality < 3) {
            $repetitions = 0;
            $intervalDays = 1;
        } else {
            $repetitions++;
            $easinessFactor = max(1.3, $this->nextEasinessFactor($easinessFactor, $quality));
            $intervalDays = match ($repetitions) {
                1 => 1,
                2 => 3,
                default => max(1, (int) round($intervalDays * $easinessFactor)),
            };
        }

        $intervalDays = $this->normalizeIntervalDays($intervalDays);

        $progress->fill([
            'easiness_factor' => round($easinessFactor, 2),
            'interval_days' => $intervalDays,
            'repetitions' => $repetitions,
            'next_review_at' => $quality < 3
                ? $answeredAt->copy()->toDateString()
                : $answeredAt->copy()->addDays($intervalDays)->toDateString(),
            'last_quality' => $quality,
            'total_attempts' => (int) ($progress->total_attempts ?? 0) + 1,
            'correct_count' => (int) ($progress->correct_count ?? 0) + ($isCorrect ? 1 : 0),
            'incorrect_count' => (int) ($progress->incorrect_count ?? 0) + ($isCorrect ? 0 : 1),
            'correct_streak' => $isCorrect ? (int) ($progress->correct_streak ?? 0) + 1 : 0,
            'last_answered_at' => $answeredAt,
            'first_answered_at' => $progress->first_answered_at ?? $answeredAt,
        ]);

        $progress->save();

        return $progress;
    }

    protected function qualityScore(bool $isCorrect, ?int $responseTimeMs): int
    {
        if (! $isCorrect) {
            return 1;
        }

        if ($responseTimeMs === null) {
            return 4;
        }

        return match (true) {
            $responseTimeMs <= 7000 => 5,
            $responseTimeMs <= 15000 => 4,
            default => 3,
        };
    }

    protected function nextEasinessFactor(float $current, int $quality): float
    {
        $delta = 0.1 - (5 - $quality) * (0.08 + ((5 - $quality) * 0.02));

        return $current + $delta;
    }

    protected function normalizeIntervalDays(int $intervalDays): int
    {
        return max(1, min($intervalDays, self::MAX_INTERVAL_DAYS));
    }
}
