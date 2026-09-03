<?php

namespace App\Support;

use App\Models\ReviewMemoryProgress;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ReviewMemoryVerifiedSignalService
{
    public const VERSION = 'verified-memory-signals-v1';

    public const SEGMENT_RECOVERY = 'recovery';

    public const SEGMENT_DUE = 'due';

    public const SEGMENT_REINFORCE = 'reinforce';

    /**
     * @return array<string, int|string>
     */
    public function signal(ReviewMemoryProgress $progress, ?Carbon $today = null): array
    {
        $today ??= today();
        $attempts = (int) ($progress->verified_attempts_count ?? 0);
        $correctCount = (int) ($progress->verified_correct_count ?? 0);
        $unknownCount = (int) ($progress->verified_unknown_count ?? 0);
        $incorrectCount = (int) ($progress->verified_incorrect_count ?? 0);
        $correctStreak = (int) ($progress->verified_correct_streak ?? 0);
        $errorCount = $unknownCount + $incorrectCount;
        $overdueDays = $this->overdueDays($progress->next_verified_review_at, $today);
        $memoryState = $this->memoryState($progress, $attempts, $correctStreak, $errorCount);
        $recoveryScore = $this->recoveryScore(
            $progress,
            $attempts,
            $errorCount,
            $correctStreak,
            $overdueDays,
        );
        $leechScore = $this->leechScore($attempts, $unknownCount, $incorrectCount, $correctStreak);
        $stabilityScore = $this->stabilityScore(
            $memoryState,
            $attempts,
            $correctCount,
            $errorCount,
            $unknownCount,
            $correctStreak,
            $overdueDays,
        );

        return [
            'version' => self::VERSION,
            'source_policy_version' => (string) ($progress->source_policy_version ?? ReviewMemoryProgressService::VERSION),
            'verified_memory_state' => $memoryState,
            'plan_segment' => $this->planSegment($memoryState, $progress, $recoveryScore, $overdueDays, $today),
            'recovery_score' => $recoveryScore,
            'leech_score' => $leechScore,
            'stability_score' => $stabilityScore,
            'overdue_days' => $overdueDays,
            'verified_attempts_count' => $attempts,
            'verified_error_count' => $errorCount,
            'verified_correct_streak' => $correctStreak,
        ];
    }

    protected function memoryState(ReviewMemoryProgress $progress, int $attempts, int $correctStreak, int $errorCount): string
    {
        $storedState = (string) ($progress->verified_memory_state ?? '');

        if (in_array($storedState, [
            ReviewMemoryProgress::STATE_NEW,
            ReviewMemoryProgress::STATE_REVIEW,
            ReviewMemoryProgress::STATE_VERIFIED_MEMORY,
            ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        ], true)) {
            return $storedState;
        }

        if ($attempts <= 0) {
            return ReviewMemoryProgress::STATE_NEW;
        }

        if ($errorCount > 0 && $correctStreak === 0) {
            return ReviewMemoryProgress::STATE_NEEDS_RECOVERY;
        }

        return $correctStreak >= 3
            ? ReviewMemoryProgress::STATE_VERIFIED_MEMORY
            : ReviewMemoryProgress::STATE_REVIEW;
    }

    protected function planSegment(
        string $memoryState,
        ReviewMemoryProgress $progress,
        int $recoveryScore,
        int $overdueDays,
        Carbon $today,
    ): string {
        $lastResult = (string) ($progress->last_verified_result ?? '');

        if (
            $memoryState === ReviewMemoryProgress::STATE_NEEDS_RECOVERY
            || in_array($lastResult, [
                ReviewMemoryProgress::RESULT_UNKNOWN,
                ReviewMemoryProgress::RESULT_INCORRECT,
                ReviewMemoryProgress::RESULT_SKIPPED,
            ], true)
            || $recoveryScore >= 65
        ) {
            return self::SEGMENT_RECOVERY;
        }

        if ($overdueDays > 0 || $this->isDueToday($progress->next_verified_review_at, $today)) {
            return self::SEGMENT_DUE;
        }

        return self::SEGMENT_REINFORCE;
    }

    protected function recoveryScore(
        ReviewMemoryProgress $progress,
        int $attempts,
        int $errorCount,
        int $correctStreak,
        int $overdueDays,
    ): int {
        if ($attempts <= 0) {
            return 0;
        }

        $lastResult = (string) ($progress->last_verified_result ?? '');
        $errorRatio = $errorCount / max($attempts, 1);
        $score = (int) round($errorRatio * 35)
            + (max(0, 3 - $correctStreak) * 7)
            + min($overdueDays * 4, 16);

        if ((string) ($progress->verified_memory_state ?? '') === ReviewMemoryProgress::STATE_NEEDS_RECOVERY) {
            $score += 30;
        }

        if (in_array($lastResult, [
            ReviewMemoryProgress::RESULT_UNKNOWN,
            ReviewMemoryProgress::RESULT_INCORRECT,
            ReviewMemoryProgress::RESULT_SKIPPED,
        ], true)) {
            $score += 20;
        }

        return max(0, min(100, $score));
    }

    protected function leechScore(int $attempts, int $unknownCount, int $incorrectCount, int $correctStreak): int
    {
        $errorCount = $unknownCount + $incorrectCount;

        if ($attempts <= 0 || $errorCount <= 0) {
            return 0;
        }

        $errorRatio = $errorCount / max($attempts, 1);
        $score = ($errorCount * 14)
            + (int) round($errorRatio * 38)
            + ($unknownCount * 6)
            + ($incorrectCount * 5)
            + (max(0, 3 - $correctStreak) * 8);

        return max(0, min(100, $score));
    }

    protected function stabilityScore(
        string $memoryState,
        int $attempts,
        int $correctCount,
        int $errorCount,
        int $unknownCount,
        int $correctStreak,
        int $overdueDays,
    ): int {
        if ($attempts <= 0) {
            return 0;
        }

        $correctRatio = $correctCount / max($attempts, 1);
        $score = 35
            + ($correctStreak * 12)
            + (int) round($correctRatio * 35)
            - ($errorCount * 10)
            - ($unknownCount * 4)
            - (min($overdueDays, 14) * 4);

        if ($memoryState === ReviewMemoryProgress::STATE_VERIFIED_MEMORY) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    protected function overdueDays(mixed $nextReviewAt, Carbon $today): int
    {
        if ($nextReviewAt === null || $nextReviewAt === '') {
            return 0;
        }

        $reviewAt = $nextReviewAt instanceof CarbonInterface
            ? Carbon::parse($nextReviewAt->toDateString())
            : Carbon::parse((string) $nextReviewAt);

        return max(0, (int) $reviewAt->startOfDay()->diffInDays($today->copy()->startOfDay(), false));
    }

    protected function isDueToday(mixed $nextReviewAt, Carbon $today): bool
    {
        if ($nextReviewAt === null || $nextReviewAt === '') {
            return false;
        }

        $reviewAt = $nextReviewAt instanceof CarbonInterface
            ? Carbon::parse($nextReviewAt->toDateString())
            : Carbon::parse((string) $nextReviewAt);

        return $reviewAt->isSameDay($today);
    }
}
