<?php

namespace App\Support;

use App\Models\ReviewMemoryProgress;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\UserQuestionProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReviewTrainerCompletionSummaryService
{
    public const SNAPSHOT_PAYLOAD_KEY = 'review_completion_snapshot';

    public const SNAPSHOT_VERSION = 'review-completion-summary-v1';

    public function __construct(
        protected ReviewMemorySignalService $memorySignalService,
        protected ReviewMemoryVerifiedSignalService $verifiedSignalService,
        protected ReviewMemoryLegacySignalMapper $legacySignalMapper,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function summary(StudySession $studySession): ?array
    {
        if (! $this->isEligibleSession($studySession)) {
            return null;
        }

        $storedSnapshot = $this->storedSnapshot($studySession);

        if ($storedSnapshot !== null) {
            return $storedSnapshot;
        }

        return $this->buildSummary($studySession);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function persistSnapshot(StudySession $studySession): ?array
    {
        if (! $this->isEligibleSession($studySession)) {
            return null;
        }

        $storedSnapshot = $this->storedSnapshot($studySession);

        if ($storedSnapshot !== null) {
            return $storedSnapshot;
        }

        $snapshot = $this->buildSummary($studySession);

        if ($snapshot === null) {
            return null;
        }

        $snapshot = [
            ...$snapshot,
            'snapshot_version' => self::SNAPSHOT_VERSION,
            'snapshot_created_at' => now()->toIso8601String(),
        ];

        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $payload[self::SNAPSHOT_PAYLOAD_KEY] = $snapshot;

        $studySession->forceFill([
            'payload' => $payload,
        ])->save();
        $studySession->setAttribute('payload', $payload);

        return $snapshot;
    }

    protected function isEligibleSession(StudySession $studySession): bool
    {
        return $studySession->mode === StudySessionManager::MODE_SR_REVIEW
            && $studySession->status === 'completed';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function storedSnapshot(StudySession $studySession): ?array
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $snapshot = $payload[self::SNAPSHOT_PAYLOAD_KEY] ?? null;

        if (! is_array($snapshot) || ! isset($snapshot['version'])) {
            return null;
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildSummary(StudySession $studySession): ?array
    {
        $questionIds = $studySession->questionIds()
            ->unique()
            ->values();

        if ($questionIds->isEmpty()) {
            return null;
        }

        $answers = $this->answers($studySession);
        $signals = $this->signals($studySession, $questionIds);
        $memoryStateCounts = $signals['memory_state_counts'];
        $segmentCounts = $signals['segment_counts'];
        $memoryRecoveryCount = $memoryStateCounts[ReviewMemorySignalService::STATE_LEECH]
            + $memoryStateCounts[ReviewMemorySignalService::STATE_RELEARNING];
        $unknownAnswersCount = $answers
            ->filter(fn (StudySessionAnswer $answer): bool => $this->answerKind($answer) === StudySessionAnswerKind::UNKNOWN)
            ->count();
        $choiceIncorrectAnswersCount = $answers
            ->filter(fn (StudySessionAnswer $answer): bool => $answer->is_correct === false
                && $this->answerKind($answer) === StudySessionAnswerKind::CHOICE)
            ->count();
        $needsRecoveryAnswersCount = $answers
            ->filter(fn (StudySessionAnswer $answer): bool => $answer->is_correct === false)
            ->pluck('question_id')
            ->unique()
            ->count();
        $recoveryCount = max($memoryRecoveryCount, $needsRecoveryAnswersCount);
        $learningCount = $memoryStateCounts[ReviewMemorySignalService::STATE_NEW]
            + $memoryStateCounts[ReviewMemorySignalService::STATE_LEARNING];
        $stableCount = $memoryStateCounts[ReviewMemorySignalService::STATE_REVIEW]
            + $memoryStateCounts[ReviewMemorySignalService::STATE_MASTERED];
        $nextReviewLabel = $this->nextReviewLabel($studySession, $questionIds);

        return [
            'version' => ReviewMemorySignalService::VERSION,
            'memory_signal_version' => ReviewMemorySignalService::VERSION,
            'verified_memory_signal_version' => ReviewMemoryVerifiedSignalService::VERSION,
            'total_questions_count' => (int) $studySession->total_questions_count,
            'answered_count' => $answers->count(),
            'correct_answers_count' => $answers->where('is_correct', true)->count(),
            'incorrect_answers_count' => $answers->where('is_correct', false)->count(),
            'unknown_answers_count' => $unknownAnswersCount,
            'choice_incorrect_answers_count' => $choiceIncorrectAnswersCount,
            'needs_recovery_answers_count' => $needsRecoveryAnswersCount,
            'recovery_count' => $recoveryCount,
            'learning_count' => $learningCount,
            'stable_count' => $stableCount,
            'segment_counts' => $segmentCounts,
            'memory_state_counts' => $memoryStateCounts,
            'coach_message' => $this->coachMessage(
                $recoveryCount,
                $learningCount,
                $stableCount,
                $unknownAnswersCount,
                $choiceIncorrectAnswersCount,
            ),
            'next_review_label' => $nextReviewLabel,
            'next_step' => $this->nextStep($recoveryCount, $learningCount, $stableCount, $nextReviewLabel),
        ];
    }

    /**
     * @return Collection<int, StudySessionAnswer>
     */
    protected function answers(StudySession $studySession): Collection
    {
        return $studySession->relationLoaded('answers')
            ? $studySession->answers
            : $studySession->answers()
                ->get(['study_session_id', 'question_id', 'answer_kind', 'is_correct']);
    }

    protected function answerKind(StudySessionAnswer $answer): string
    {
        return $answer->answer_kind ?: StudySessionAnswerKind::CHOICE;
    }

    /**
     * @param  Collection<int, int>  $questionIds
     * @return array{
     *     segment_counts: array{overdue: int, risky: int, reinforce: int},
     *     memory_state_counts: array{new: int, learning: int, review: int, relearning: int, mastered: int, leech: int}
     * }
     */
    protected function signals(StudySession $studySession, Collection $questionIds): array
    {
        $segmentCounts = [
            ReviewMemorySignalService::SEGMENT_OVERDUE => 0,
            ReviewMemorySignalService::SEGMENT_RISKY => 0,
            ReviewMemorySignalService::SEGMENT_REINFORCE => 0,
        ];
        $memoryStateCounts = [
            ReviewMemorySignalService::STATE_NEW => 0,
            ReviewMemorySignalService::STATE_LEARNING => 0,
            ReviewMemorySignalService::STATE_REVIEW => 0,
            ReviewMemorySignalService::STATE_RELEARNING => 0,
            ReviewMemorySignalService::STATE_MASTERED => 0,
            ReviewMemorySignalService::STATE_LEECH => 0,
        ];
        $today = today();
        $verifiedProgressByQuestionId = ReviewMemoryProgress::query()
            ->where('user_id', $studySession->user_id)
            ->whereIn('question_id', $questionIds->all())
            ->get()
            ->keyBy('question_id');

        $verifiedProgressByQuestionId
            ->each(function (ReviewMemoryProgress $progress) use (&$memoryStateCounts, &$segmentCounts, $today): void {
                $signal = $this->verifiedSignalService->signal($progress, $today);

                $this->incrementSignalCounts(
                    $segmentCounts,
                    $memoryStateCounts,
                    $this->legacySignalMapper->segment($signal),
                    $this->legacySignalMapper->memoryState($signal),
                );
            });

        $fallbackQuestionIds = $questionIds
            ->reject(fn (int $questionId): bool => $verifiedProgressByQuestionId->has($questionId))
            ->values();

        if ($fallbackQuestionIds->isEmpty()) {
            return [
                'segment_counts' => $segmentCounts,
                'memory_state_counts' => $memoryStateCounts,
            ];
        }

        UserQuestionProgress::query()
            ->select([
                'user_question_progress.*',
                'questions.difficulty as question_difficulty',
            ])
            ->join('questions', 'questions.id', '=', 'user_question_progress.question_id')
            ->where('user_question_progress.user_id', $studySession->user_id)
            ->whereIn('user_question_progress.question_id', $fallbackQuestionIds->all())
            ->get()
            ->each(function (UserQuestionProgress $progress) use (&$memoryStateCounts, &$segmentCounts, $today): void {
                $signal = $this->memorySignalService->signal($progress, $today);
                $segment = (string) $signal['plan_segment'];
                $memoryState = (string) $signal['memory_state'];

                $this->incrementSignalCounts($segmentCounts, $memoryStateCounts, $segment, $memoryState);
            });

        return [
            'segment_counts' => $segmentCounts,
            'memory_state_counts' => $memoryStateCounts,
        ];
    }

    /**
     * @param  array<string, int>  $segmentCounts
     * @param  array<string, int>  $memoryStateCounts
     */
    protected function incrementSignalCounts(
        array &$segmentCounts,
        array &$memoryStateCounts,
        string $segment,
        string $memoryState,
    ): void {
        if (array_key_exists($segment, $segmentCounts)) {
            $segmentCounts[$segment]++;
        }

        if (array_key_exists($memoryState, $memoryStateCounts)) {
            $memoryStateCounts[$memoryState]++;
        }
    }

    protected function coachMessage(
        int $recoveryCount,
        int $learningCount,
        int $stableCount,
        int $unknownAnswersCount,
        int $choiceIncorrectAnswersCount,
    ): string {
        if ($unknownAnswersCount > 0) {
            return 'Pytania oznaczone jako „Nie wiem” wrócą szybciej. To dobry sygnał dla trenera: tu pamięć wymaga spokojnego odzyskania, nie zgadywania.';
        }

        if ($choiceIncorrectAnswersCount > 0) {
            return 'Część odpowiedzi wymaga poprawki, więc trener potraktuje je jako materiał do odzyskania w kolejnych powtórkach.';
        }

        if ($recoveryCount > 0) {
            return 'Część pytań wróci szybciej, bo pamięć jest tam jeszcze chwiejna. To jest dokładnie materiał na kolejny trening.';
        }

        if ($learningCount > $stableCount) {
            return 'Dobra sesja. Większość pytań jest jeszcze w budowie, więc system będzie je spokojnie utrwalał.';
        }

        return 'Ten zestaw wygląda stabilnie. Kolejne powtórki pojawią się wtedy, gdy będą naprawdę potrzebne.';
    }

    /**
     * @param  Collection<int, int>  $questionIds
     */
    protected function nextReviewLabel(StudySession $studySession, Collection $questionIds): string
    {
        $verifiedProgress = ReviewMemoryProgress::query()
            ->where('user_id', $studySession->user_id)
            ->whereIn('question_id', $questionIds->all())
            ->get(['question_id', 'next_verified_review_at']);
        $verifiedQuestionIds = $verifiedProgress
            ->pluck('question_id')
            ->map(fn (mixed $questionId): int => (int) $questionId);
        $nextReviewDates = $verifiedProgress
            ->pluck('next_verified_review_at')
            ->filter();
        $fallbackQuestionIds = $questionIds
            ->diff($verifiedQuestionIds)
            ->values();

        if ($fallbackQuestionIds->isNotEmpty()) {
            $fallbackNextReviewAt = UserQuestionProgress::query()
                ->where('user_id', $studySession->user_id)
                ->whereIn('question_id', $fallbackQuestionIds->all())
                ->whereNotNull('next_review_at')
                ->min('next_review_at');

            if ($fallbackNextReviewAt) {
                $nextReviewDates->push($fallbackNextReviewAt);
            }
        }

        $nextReviewAt = $nextReviewDates
            ->sortBy(fn (mixed $date): int => Carbon::parse((string) $date)->getTimestamp())
            ->first();

        if (! $nextReviewAt) {
            return 'Kolejny plan pojawi się automatycznie.';
        }

        return $this->nextReviewDateLabel($nextReviewAt);
    }

    protected function nextReviewDateLabel(mixed $nextReviewAt): string
    {
        $reviewDate = Carbon::parse((string) $nextReviewAt)->startOfDay();
        $daysUntilReview = (int) today()->startOfDay()->diffInDays($reviewDate, false);

        if ($daysUntilReview <= 0) {
            return 'Możesz wrócić od razu';
        }

        if ($daysUntilReview === 1) {
            return 'Wróć jutro';
        }

        return "Wróć za {$daysUntilReview} dni";
    }

    /**
     * @return array{tone: string, headline: string, message: string, primary_action_label: string, time_label: string}
     */
    protected function nextStep(int $recoveryCount, int $learningCount, int $stableCount, string $nextReviewLabel): array
    {
        if ($recoveryCount > 0) {
            return [
                'tone' => 'recovery',
                'headline' => 'Następny krok: szybki powrót',
                'message' => 'Trener zapamiętał chwiejne pytania i pokaże je wtedy, gdy powtórka ma największy sens.',
                'primary_action_label' => 'Otwórz trenera pamięci',
                'time_label' => $nextReviewLabel,
            ];
        }

        if ($learningCount > $stableCount) {
            return [
                'tone' => 'learning',
                'headline' => 'Następny krok: utrwalenie',
                'message' => 'Materiał jest jeszcze świeży, więc kolejna sesja ma go spokojnie wzmocnić zamiast zasypywać Cię pytaniami.',
                'primary_action_label' => 'Otwórz trenera pamięci',
                'time_label' => $nextReviewLabel,
            ];
        }

        return [
            'tone' => 'stable',
            'headline' => 'Następny krok: poczekaj na sygnał',
            'message' => 'Ten zestaw wygląda stabilnie. Trener wróci z nim wtedy, gdy pamięć będzie potrzebowała przypomnienia.',
            'primary_action_label' => 'Otwórz trenera pamięci',
            'time_label' => $nextReviewLabel,
        ];
    }
}
