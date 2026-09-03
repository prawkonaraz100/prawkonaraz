<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiStudySessionAnswerStoreRequest;
use App\Models\Question;
use App\Models\StudySession;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\ReviewTrainerDailyPlanService;
use App\Support\StudySessionApiPayloadBuilder;
use App\Support\StudySessionAnswerKind;
use App\Support\StudySessionManager;
use App\Support\StudyTopicCompletionRecordService;
use Illuminate\Http\JsonResponse;

class ApiStudySessionAnswerController extends Controller
{
    public function store(
        ApiStudySessionAnswerStoreRequest $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        ReviewTrainerDailyPlanService $dailyPlanService,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        return $this->storeForSession(
            $request,
            $studySession,
            $studySessionManager,
            $dailyPlanService,
            $payloadBuilder,
            $reviewCompletionSummaryService,
            $topicCompletionRecordService,
        );
    }

    public function storeCurrent(
        ApiStudySessionAnswerStoreRequest $request,
        StudySessionManager $studySessionManager,
        ReviewTrainerDailyPlanService $dailyPlanService,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);

        return $this->storeForSession(
            $request,
            $studySession,
            $studySessionManager,
            $dailyPlanService,
            $payloadBuilder,
            $reviewCompletionSummaryService,
            $topicCompletionRecordService,
        );
    }

    protected function storeForSession(
        ApiStudySessionAnswerStoreRequest $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        ReviewTrainerDailyPlanService $dailyPlanService,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        abort_if(
            $studySessionManager->retireReviewSessionIfOutsideActiveScope($studySession, $request->user()),
            409,
            'Ten trening pamięci jest poza aktualną kategorią. Uruchom nowy plan.',
        );

        if ($studySession->mode === StudySessionManager::MODE_EXAM && $studySession->status === 'in_progress') {
            $studySession = $studySessionManager->syncExamState($studySession);
        }

        $question = Question::query()->findOrFail($request->integer('question_id'));
        $answerKind = (string) $request->string('answer_kind', StudySessionAnswerKind::CHOICE);
        $selectedAnswer = $request->input('user_answer');
        $selectedAnswer = is_string($selectedAnswer) ? $selectedAnswer : null;

        $answer = $studySessionManager->recordAnswer(
            $studySession,
            $question,
            $selectedAnswer,
            $request->filled('response_time_ms') ? $request->integer('response_time_ms') : null,
            $answerKind,
        );

        $freshSession = $studySession->fresh(['answers', 'licenseCategory']) ?? $studySession;
        if ($freshSession->status === 'completed') {
            $topicCompletionRecordService->syncFromCompletedSession($freshSession);
            $freshSession = $freshSession->fresh(['answers', 'licenseCategory']) ?? $freshSession;
        }

        $answeredCount = $studySessionManager->answeredCount($freshSession);
        $revealsOutcomes = $this->shouldReturnAnswerReveal($freshSession);
        $answerPayload = $payloadBuilder->answer(
            $question->loadMissing('media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations'),
            $answer,
            $revealsOutcomes,
        );
        $nextQuestion = $this->shouldReturnNextQuestion($freshSession)
            ? $studySessionManager->currentQuestion($freshSession)
            : null;
        $nextQuestionNumber = $nextQuestion
            ? $studySessionManager->currentQuestionPosition($freshSession)
            : null;

        $data = [
            'accepted' => $answer->wasRecentlyCreated,
            'question_id' => $question->getKey(),
            'answer_kind' => $answer->answer_kind,
            'is_correct' => $answer->is_correct,
            'answered_questions' => $answeredCount,
            'session_status' => $freshSession->status,
            'answer' => $answerPayload,
            'session' => $payloadBuilder->session($freshSession),
            'progress' => [
                'answered' => $answeredCount,
                'remaining' => max($freshSession->total_questions_count - $answeredCount, 0),
                'total' => $freshSession->total_questions_count,
                'current_question_number' => $nextQuestionNumber,
            ],
            'completed' => $freshSession->status === 'completed',
            'next_question' => $nextQuestion
                ? $payloadBuilder->question(
                    $nextQuestion,
                    includeVisualExplanations: false,
                    publicExplanationUrl: $payloadBuilder->publicExplanationUrlFor($nextQuestion),
                )
                : null,
            'next_question_number' => $nextQuestionNumber,
        ];

        if ($revealsOutcomes) {
            $data['correct_answer'] = $answerPayload['correct_answer'] ?? null;
            $data['correct_answer_text'] = $answerPayload['correct_answer_text'] ?? null;
            $data['explanation'] = $answerPayload['explanation'] ?? null;
            $data['explanation_asset'] = $answerPayload['explanation_asset'] ?? null;
            $data['explanation_sign_references'] = $answerPayload['explanation_sign_references'] ?? [];
            $data['explanation_annotations'] = $answerPayload['explanation_annotations'] ?? [];
        }

        if ($freshSession->mode === StudySessionManager::MODE_SR_REVIEW) {
            $reviewCompletion = null;

            if ($freshSession->status === 'completed') {
                $reviewCompletion = $reviewCompletionSummaryService->persistSnapshot($freshSession);
            }

            $completedTodayCount = $dailyPlanService->completedTodayCount(
                $request->user(),
                $freshSession->license_category_id,
            );

            $data['daily_progress'] = [
                'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
                'review_day' => $dailyPlanService->reviewDay(),
                'daily_target_count' => ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT,
                'minimum_session_question_count' => ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT,
                'completed_today_count' => $completedTodayCount,
                'daily_remaining_count' => max(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT - $completedTodayCount, 0),
            ];
            $data['review_completion'] = $reviewCompletion;
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    protected function shouldReturnAnswerReveal(StudySession $studySession): bool
    {
        return in_array($studySession->mode, [
            StudySessionManager::MODE_LEARN,
            StudySessionManager::MODE_PJM,
            StudySessionManager::MODE_SR_REVIEW,
        ], true);
    }

    protected function shouldReturnNextQuestion(StudySession $studySession): bool
    {
        return $studySession->status === 'in_progress'
            && $studySession->mode !== StudySessionManager::MODE_EXAM;
    }
}
