<?php

namespace App\Support;

use App\Models\UserQuestionProgress;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ReviewMemorySignalService
{
    public const VERSION = 'memory-signals-v1';

    public const STATE_NEW = 'new';

    public const STATE_LEARNING = 'learning';

    public const STATE_REVIEW = 'review';

    public const STATE_RELEARNING = 'relearning';

    public const STATE_MASTERED = 'mastered';

    public const STATE_LEECH = 'leech';

    public const SEGMENT_OVERDUE = 'overdue';

    public const SEGMENT_RISKY = 'risky';

    public const SEGMENT_REINFORCE = 'reinforce';

    /**
     * @return array<string, int|string>
     */
    public function signal(UserQuestionProgress $progress, ?Carbon $today = null): array
    {
        $today ??= today();

        $totalAttempts = (int) ($progress->total_attempts ?? 0);
        $correctCount = (int) ($progress->correct_count ?? 0);
        $incorrectCount = (int) ($progress->incorrect_count ?? 0);
        $repetitions = (int) ($progress->repetitions ?? 0);
        $correctStreak = (int) ($progress->correct_streak ?? 0);
        $lastQuality = $progress->last_quality !== null ? (int) $progress->last_quality : null;
        $difficulty = max(1, min(5, (int) ($progress->getAttribute('question_difficulty') ?? 1)));
        $overdueDays = $this->overdueDays($progress->next_review_at, $today);
        $leechScore = $this->leechScore($totalAttempts, $incorrectCount, $correctStreak, $lastQuality, $difficulty);
        $stabilityScore = $this->stabilityScore(
            $progress,
            $totalAttempts,
            $incorrectCount,
            $repetitions,
            $correctStreak,
            $lastQuality,
            $overdueDays,
        );
        $memoryState = $this->memoryState(
            $progress,
            $totalAttempts,
            $correctCount,
            $incorrectCount,
            $repetitions,
            $correctStreak,
            $lastQuality,
            $leechScore,
        );

        return [
            'version' => self::VERSION,
            'memory_state' => $memoryState,
            'plan_segment' => $this->planSegment($memoryState, $overdueDays),
            'leech_score' => $leechScore,
            'stability_score' => $stabilityScore,
            'difficulty_score' => $difficulty * 20,
            'overdue_days' => $overdueDays,
        ];
    }

    protected function memoryState(
        UserQuestionProgress $progress,
        int $totalAttempts,
        int $correctCount,
        int $incorrectCount,
        int $repetitions,
        int $correctStreak,
        ?int $lastQuality,
        int $leechScore,
    ): string {
        if ($totalAttempts <= 0) {
            return self::STATE_NEW;
        }

        if (QuestionProgressManager::isMasteredSnapshot(
            $totalAttempts,
            $repetitions,
            $correctStreak,
            $lastQuality,
            $progress->next_review_at,
        )) {
            return self::STATE_MASTERED;
        }

        if ($totalAttempts >= 4 && $incorrectCount >= 3 && $leechScore >= 65) {
            return self::STATE_LEECH;
        }

        if (($lastQuality !== null && $lastQuality < 3) || ($incorrectCount > 0 && $correctStreak === 0)) {
            return self::STATE_RELEARNING;
        }

        if ($repetitions < 2 || $correctStreak < 2 || $correctCount <= 1) {
            return self::STATE_LEARNING;
        }

        return self::STATE_REVIEW;
    }

    protected function planSegment(string $memoryState, int $overdueDays): string
    {
        if ($overdueDays > 0) {
            return self::SEGMENT_OVERDUE;
        }

        if (in_array($memoryState, [self::STATE_LEECH, self::STATE_RELEARNING], true)) {
            return self::SEGMENT_RISKY;
        }

        return self::SEGMENT_REINFORCE;
    }

    protected function leechScore(
        int $totalAttempts,
        int $incorrectCount,
        int $correctStreak,
        ?int $lastQuality,
        int $difficulty,
    ): int {
        if ($totalAttempts <= 0 || $incorrectCount <= 0) {
            return 0;
        }

        $incorrectRatio = $incorrectCount / max($totalAttempts, 1);
        $score = ($incorrectCount * 14)
            + (int) round($incorrectRatio * 35)
            + (max(0, 3 - $correctStreak) * 7)
            + ($lastQuality !== null ? max(0, 3 - $lastQuality) * 8 : 0)
            + (max(0, $difficulty - 3) * 4);

        return max(0, min(100, $score));
    }

    protected function stabilityScore(
        UserQuestionProgress $progress,
        int $totalAttempts,
        int $incorrectCount,
        int $repetitions,
        int $correctStreak,
        ?int $lastQuality,
        int $overdueDays,
    ): int {
        if ($totalAttempts <= 0) {
            return 0;
        }

        $easinessFactor = (float) ($progress->easiness_factor ?? 2.5);
        $qualityAnchor = $lastQuality ?? 3;
        $score = 45
            + ($correctStreak * 11)
            + ($repetitions * 7)
            + (($qualityAnchor - 3) * 8)
            + (int) round(($easinessFactor - 2.5) * 12)
            - ($incorrectCount * 6)
            - (min($overdueDays, 14) * 3);

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
}
