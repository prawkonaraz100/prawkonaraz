<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudySessionApiPayloadBuilder
{
    public function __construct(
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected StudySessionManager $studySessionManager,
        protected QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        protected QuestionExplanationSignReferencePayloadBuilder $questionExplanationSignReferencePayloadBuilder,
        protected QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        protected ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        protected PublicQuestionExplanationLinkResolver $publicQuestionExplanationLinkResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function session(StudySession $studySession): array
    {
        return [
            'id' => $studySession->getKey(),
            'category_id' => $studySession->license_category_id,
            'mode' => $studySession->mode,
            'ui_shell' => data_get($studySession->payload, 'ui_shell'),
            'status' => $studySession->status,
            'started_at' => $studySession->started_at?->toIso8601String(),
            'completed_at' => $studySession->completed_at?->toIso8601String(),
            'correct_answers_count' => $studySession->correct_answers_count,
            'total_questions' => $studySession->total_questions_count,
            'answered_questions' => $studySession->relationLoaded('answers')
                ? $studySession->answers->count()
                : $studySession->answers()->count(),
            'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
            'filters' => data_get($studySession->payload, 'filters', []),
            'review_plan' => $this->reviewPlan($studySession),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function reviewPlan(StudySession $studySession): ?array
    {
        if ($studySession->mode !== StudySessionManager::MODE_SR_REVIEW) {
            return null;
        }

        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $reviewPlan = is_array($payload['review_plan'] ?? null) ? $payload['review_plan'] : null;

        if ($reviewPlan === null) {
            return null;
        }

        $candidateSourceCounts = is_array($reviewPlan['candidate_source_counts'] ?? null)
            ? $reviewPlan['candidate_source_counts']
            : [];

        return [
            'planner_version' => (string) ($reviewPlan['planner_version'] ?? ReviewPlannerService::VERSION),
            'daily_plan_policy_version' => (string) ($reviewPlan['daily_plan_policy_version'] ?? ReviewTrainerDailyPlanService::VERSION),
            'review_day' => (string) ($reviewPlan['review_day'] ?? today()->toDateString()),
            'daily_target_count' => (int) ($reviewPlan['daily_target_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
            'minimum_session_question_count' => (int) ($reviewPlan['minimum_session_question_count'] ?? ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT),
            'completed_today_count' => (int) ($reviewPlan['completed_today_count'] ?? 0),
            'daily_remaining_count' => (int) ($reviewPlan['daily_remaining_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
            'due_count' => (int) ($reviewPlan['due_count'] ?? 0),
            'candidate_count' => (int) ($reviewPlan['candidate_count'] ?? $reviewPlan['due_count'] ?? 0),
            'booster_count' => (int) ($reviewPlan['booster_count'] ?? 0),
            'new_candidate_count' => (int) ($reviewPlan['new_candidate_count'] ?? 0),
            'candidate_source_counts' => [
                'primary' => (int) ($candidateSourceCounts['primary'] ?? $reviewPlan['due_count'] ?? 0),
                'seen_booster' => (int) ($candidateSourceCounts['seen_booster'] ?? $reviewPlan['booster_count'] ?? 0),
                'new_candidate' => (int) ($candidateSourceCounts['new_candidate'] ?? $reviewPlan['new_candidate_count'] ?? 0),
            ],
            'recommended_question_count' => (int) ($reviewPlan['recommended_question_count'] ?? 0),
            'selected_question_count' => (int) ($reviewPlan['selected_question_count'] ?? $studySession->total_questions_count),
            'estimated_duration_seconds' => (int) ($reviewPlan['estimated_duration_seconds'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionDetails(StudySession $studySession): array
    {
        $studySession->loadMissing('answers', 'licenseCategory');

        $orderedQuestions = $this->studySessionManager->orderedQuestions($studySession);
        $currentQuestion = $studySession->status === 'completed'
            ? null
            : $this->studySessionManager->currentQuestion($studySession);
        $answerMap = $studySession->answers->keyBy('question_id');
        $revealsOutcomes = $studySession->status === 'completed';
        $includeVisualExplanations = $studySession->mode !== StudySessionManager::MODE_SR_REVIEW
            || $studySession->status === 'completed';

        if ($includeVisualExplanations) {
            $this->questionExplanationSignReferencePayloadBuilder->preloadForQuestions($orderedQuestions);
        }

        $publicExplanationUrls = $this->publicExplanationUrlsFor($orderedQuestions);

        return [
            'session' => $this->session($studySession),
            'category' => $this->category($studySession->licenseCategory),
            'progress' => [
                'answered' => $studySession->answers->count(),
                'remaining' => max($studySession->total_questions_count - $studySession->answers->count(), 0),
                'total' => $studySession->total_questions_count,
                'current_question_number' => $currentQuestion
                    ? $this->studySessionManager->currentQuestionPosition($studySession)
                    : null,
            ],
            'current_question' => $currentQuestion
                ? $this->question(
                    $currentQuestion,
                    includeVisualExplanations: $includeVisualExplanations,
                    publicExplanationUrl: $publicExplanationUrls->get((int) $currentQuestion->getKey()),
                )
                : null,
            'review_completion' => $this->reviewCompletion($studySession),
            'questions' => $orderedQuestions
                ->map(function (Question $question) use ($answerMap, $includeVisualExplanations, $publicExplanationUrls, $revealsOutcomes): array {
                    return $this->question(
                        $question,
                        $answerMap->get($question->getKey()),
                        $revealsOutcomes,
                        $includeVisualExplanations,
                        $publicExplanationUrls->get((int) $question->getKey()),
                    );
                })
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function reviewCompletion(StudySession $studySession): ?array
    {
        if ($studySession->mode !== StudySessionManager::MODE_SR_REVIEW) {
            return null;
        }

        return $this->reviewCompletionSummaryService->summary($studySession);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function category(?LicenseCategory $licenseCategory): ?array
    {
        if (! $licenseCategory) {
            return null;
        }

        return [
            'id' => $licenseCategory->getKey(),
            'code' => $licenseCategory->code,
            'name' => $licenseCategory->name,
            'short_name' => (string) $licenseCategory->code,
            'description' => $licenseCategory->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function question(
        Question $question,
        ?StudySessionAnswer $answer = null,
        bool $revealsOutcomes = false,
        bool $includeVisualExplanations = true,
        ?string $publicExplanationUrl = null,
    ): array {
        $payload = [
            'id' => $question->getKey(),
            'category_id' => $question->license_category_id,
            'external_id' => $question->external_id,
            'public_explanation_url' => $publicExplanationUrl,
            'question_text' => $question->prompt,
            'question_type' => $question->question_type,
            'structure_scope' => strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')),
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'answers' => collect([
                'A' => $question->option_a,
                'B' => $question->option_b,
                'C' => $question->option_c,
            ])->filter()->all(),
            'media' => $this->questionMediaPayloadBuilder->forQuestion($question->media),
            'explanation_asset' => $includeVisualExplanations
                ? $this->questionExplanationAssetPayloadBuilder->forAsset(
                    app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($question)
                )
                : null,
            'explanation_sign_references' => $includeVisualExplanations
                ? $this->questionExplanationSignReferencePayloadBuilder->forQuestion($question)
                : [],
            'explanation_annotations' => $includeVisualExplanations
                ? $this->questionExplanationAnnotationPayloadBuilder->forRuntime($question->explanationAnnotations)
                : [],
            'topic' => $question->questionTopic ? [
                'id' => $question->questionTopic->getKey(),
                'key' => $question->questionTopic->key,
                'name' => $question->questionTopic->name,
            ] : null,
            'selected_answer' => $answer?->selected_answer ? Str::upper($answer->selected_answer) : null,
            'answer_kind' => $answer?->answer_kind,
            'is_answered' => $answer !== null,
            'response_time_ms' => $answer?->response_time_ms,
        ];

        if ($revealsOutcomes) {
            $payload['correct_answer'] = Str::upper($question->correct_answer);
            $payload['is_correct'] = $answer?->is_correct;
            $payload['explanation'] = $question->explanation;
        }

        return $payload;
    }

    /**
     * @param  iterable<mixed>  $questions
     * @return Collection<int, string>
     */
    public function publicExplanationUrlsFor(iterable $questions): Collection
    {
        return $this->publicQuestionExplanationLinkResolver->urlsForQuestions($questions);
    }

    public function publicExplanationUrlFor(Question $question): ?string
    {
        return $this->publicQuestionExplanationLinkResolver->urlForQuestion($question);
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(Question $question, StudySessionAnswer $answer, bool $revealsOutcomes = false): array
    {
        $payload = [
            'question_id' => $answer->question_id,
            'selected_answer' => $answer->selected_answer ? Str::upper($answer->selected_answer) : null,
            'answer_kind' => $answer->answer_kind,
            'is_correct' => $answer->is_correct,
            'response_time_ms' => $answer->response_time_ms,
        ];

        if (! $revealsOutcomes) {
            return $payload;
        }

        $question->loadMissing('referenceExplanationAsset', 'explanationAnnotations');
        $this->questionExplanationSignReferencePayloadBuilder->preloadForQuestions([$question]);

        $correctAnswer = Str::upper((string) $question->correct_answer);

        return [
            ...$payload,
            'correct_answer' => $correctAnswer,
            'correct_answer_text' => $this->optionText($question, $correctAnswer),
            'explanation' => $question->explanation,
            'explanation_asset' => $this->questionExplanationAssetPayloadBuilder->forAsset(
                app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($question)
            ),
            'explanation_sign_references' => $this->questionExplanationSignReferencePayloadBuilder->forQuestion($question),
            'explanation_annotations' => $this->questionExplanationAnnotationPayloadBuilder
                ->forRuntime($question->explanationAnnotations),
        ];
    }

    protected function optionText(Question $question, ?string $answer): ?string
    {
        if (! $answer) {
            return null;
        }

        return match (Str::lower($answer)) {
            'a' => $question->option_a,
            'b' => $question->option_b,
            'c' => $question->option_c,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function examState(StudySession $studySession): array
    {
        $studySession->loadMissing('answers', 'licenseCategory');

        $questionIds = $this->studySessionManager->questionIds($studySession);
        $answeredCount = $this->studySessionManager->answeredCount($studySession);
        $currentQuestion = $studySession->status === 'in_progress'
            ? $this->studySessionManager->currentQuestion($studySession)
            : null;
        $sectionSummary = $this->sectionSummary(
            Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'metadata']),
            $studySession->answers->keyBy('question_id'),
        );
        $examStartedAt = $this->examTimestamp(
            data_get($studySession->payload, 'exam_started_at'),
            $studySession->started_at,
        );
        $examDeadlineAt = $this->examTimestamp(
            data_get($studySession->payload, 'exam_deadline_at'),
            $examStartedAt?->copy()->addSeconds(StudySessionManager::EXAM_DURATION_SECONDS),
        );
        $currentExamState = $this->studySessionManager->currentExamState($studySession, $currentQuestion);
        $currentQuestionNumber = $studySession->status === 'in_progress'
            ? $this->studySessionManager->currentQuestionPosition($studySession)
            : null;

        return [
            'session' => $this->session($studySession),
            'category' => $this->category($studySession->licenseCategory),
            'progress' => [
                'answered' => $answeredCount,
                'remaining' => max($studySession->total_questions_count - $answeredCount, 0),
                'total' => $studySession->total_questions_count,
                'current_question_number' => $currentQuestionNumber,
            ],
            'exam_ui' => [
                'duration_seconds' => StudySessionManager::EXAM_DURATION_SECONDS,
                'remaining_seconds' => $examDeadlineAt
                    ? max(now()->diffInSeconds($examDeadlineAt, false), 0)
                    : StudySessionManager::EXAM_DURATION_SECONDS,
                'started_at' => $examStartedAt?->toIso8601String(),
                'deadline_at' => $examDeadlineAt?->toIso8601String(),
                'pass_threshold' => 68,
                'max_points' => 74,
                'current_scope' => $currentQuestion
                    ? strtoupper((string) ($currentQuestion->metadata['structure_scope'] ?? 'PODSTAWOWY'))
                    : null,
                'current_points' => $currentQuestion?->points,
                'basic' => $sectionSummary['basic'],
                'specialist' => $sectionSummary['specialist'],
                ...$currentExamState,
            ],
            'current_question_number' => $currentQuestionNumber,
            'current_question' => $currentQuestion
                ? $this->question(
                    $currentQuestion,
                    includeVisualExplanations: false,
                )
                : null,
            'completed' => $studySession->status === 'completed',
            'redirect' => $studySession->status === 'completed'
                ? route('study-sessions.show', $studySession, absolute: false)
                : route('study-sessions.current', absolute: false),
        ];
    }

    protected function examTimestamp(mixed $value, ?Carbon $fallback = null): ?Carbon
    {
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return $fallback?->copy();
            }
        }

        return $fallback?->copy();
    }

    /**
     * @param  Collection<int, Question>  $orderedQuestions
     * @param  Collection<int|string, mixed>  $answerMap
     * @return array{basic: array{answered:int,total:int}, specialist: array{answered:int,total:int}}
     */
    protected function sectionSummary(Collection $orderedQuestions, Collection $answerMap): array
    {
        $summary = [
            'basic' => ['answered' => 0, 'total' => 0],
            'specialist' => ['answered' => 0, 'total' => 0],
        ];

        foreach ($orderedQuestions as $question) {
            $scope = strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY'));
            $bucket = $scope === 'SPECJALISTYCZNY' ? 'specialist' : 'basic';

            $summary[$bucket]['total']++;

            if ($answerMap->has($question->getKey())) {
                $summary[$bucket]['answered']++;
            }
        }

        return $summary;
    }
}
