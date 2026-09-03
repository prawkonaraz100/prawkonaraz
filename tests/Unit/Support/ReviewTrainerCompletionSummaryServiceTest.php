<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewMemoryVerifiedSignalService;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\StudySessionAnswerKind;
use App\Support\StudySessionManager;
use Tests\TestCase;

uses(TestCase::class);

test('review trainer completion summary maps memory signals into user facing groups', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create();
    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 5, 'correct_answer' => 'a'],
            ['difficulty' => 2, 'correct_answer' => 'b'],
            ['difficulty' => 3, 'correct_answer' => 'c'],
        )
        ->create();

    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => StudySessionManager::MODE_SR_REVIEW,
            'status' => 'completed',
            'total_questions_count' => 3,
            'correct_answers_count' => 2,
            'payload' => [
                'question_ids' => $questions->pluck('id')->all(),
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($session)
        ->for($questions[0], 'question')
        ->create(['is_correct' => false]);
    StudySessionAnswer::factory()
        ->for($session)
        ->for($questions[1], 'question')
        ->create(['is_correct' => true]);
    StudySessionAnswer::factory()
        ->for($session)
        ->for($questions[2], 'question')
        ->create(['is_correct' => true]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions[0], 'question')
        ->create([
            'total_attempts' => 6,
            'correct_count' => 2,
            'incorrect_count' => 4,
            'correct_streak' => 0,
            'last_quality' => 1,
            'repetitions' => 0,
            'next_review_at' => today(),
            'easiness_factor' => 1.8,
        ]);
    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions[1], 'question')
        ->create([
            'total_attempts' => 1,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'correct_streak' => 1,
            'last_quality' => 4,
            'repetitions' => 1,
            'next_review_at' => today()->addDay(),
        ]);
    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions[2], 'question')
        ->create([
            'total_attempts' => 5,
            'correct_count' => 5,
            'incorrect_count' => 0,
            'correct_streak' => 5,
            'last_quality' => 4,
            'repetitions' => 3,
            'next_review_at' => today()->addDays(7),
            'easiness_factor' => 2.7,
        ]);

    $summary = app(ReviewTrainerCompletionSummaryService::class)->summary($session);

    expect($summary)->not->toBeNull()
        ->and($summary['version'])->toBe(ReviewMemorySignalService::VERSION)
        ->and($summary['memory_signal_version'])->toBe(ReviewMemorySignalService::VERSION)
        ->and($summary['verified_memory_signal_version'])->toBe(ReviewMemoryVerifiedSignalService::VERSION)
        ->and($summary['answered_count'])->toBe(3)
        ->and($summary['incorrect_answers_count'])->toBe(1)
        ->and($summary['recovery_count'])->toBe(1)
        ->and($summary['learning_count'])->toBe(1)
        ->and($summary['stable_count'])->toBe(1)
        ->and($summary['next_review_label'])->toBe('Możesz wrócić od razu')
        ->and($summary['next_step']['tone'])->toBe('recovery')
        ->and($summary['next_step']['primary_action_label'])->toBe('Otwórz trenera pamięci')
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_LEECH])->toBe(1)
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_LEARNING])->toBe(1)
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_MASTERED])->toBe(1);
});

test('review trainer completion summary uses stored snapshot after session completion', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
            'difficulty' => 2,
        ]);

    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => StudySessionManager::MODE_SR_REVIEW,
            'status' => 'completed',
            'total_questions_count' => 1,
            'correct_answers_count' => 1,
            'payload' => [
                'question_ids' => [$question->getKey()],
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($session)
        ->for($question, 'question')
        ->create([
            'selected_answer' => 'a',
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'is_correct' => true,
        ]);

    $progress = UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->create([
            'total_attempts' => 1,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'correct_streak' => 1,
            'last_quality' => 4,
            'repetitions' => 1,
            'next_review_at' => today()->addDay(),
        ]);

    $service = app(ReviewTrainerCompletionSummaryService::class);
    $snapshot = $service->persistSnapshot($session);

    expect($snapshot)->not->toBeNull()
        ->and($snapshot['snapshot_version'])->toBe(ReviewTrainerCompletionSummaryService::SNAPSHOT_VERSION)
        ->and($snapshot['learning_count'])->toBe(1)
        ->and($snapshot['stable_count'])->toBe(0)
        ->and($snapshot['next_review_label'])->toBe('Wróć jutro');

    $progress->forceFill([
        'total_attempts' => 5,
        'correct_count' => 5,
        'incorrect_count' => 0,
        'correct_streak' => 5,
        'last_quality' => 4,
        'repetitions' => 3,
        'next_review_at' => today()->addDays(7),
        'easiness_factor' => 2.7,
    ])->save();

    $dynamicSession = $session->fresh();
    $dynamicPayload = $dynamicSession->payload;
    unset($dynamicPayload[ReviewTrainerCompletionSummaryService::SNAPSHOT_PAYLOAD_KEY]);
    $dynamicSession->setAttribute('payload', $dynamicPayload);

    expect($service->summary($dynamicSession)['stable_count'])->toBe(1);

    $frozenSummary = $service->summary($session->refresh());

    expect($frozenSummary['learning_count'])->toBe(1)
        ->and($frozenSummary['stable_count'])->toBe(0)
        ->and($frozenSummary['next_review_label'])->toBe('Wróć jutro')
        ->and($session->payload[ReviewTrainerCompletionSummaryService::SNAPSHOT_PAYLOAD_KEY]['learning_count'])->toBe(1);
});

test('review trainer completion summary prefers verified memory signals over classic exposure', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
            'difficulty' => 2,
        ]);

    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => StudySessionManager::MODE_SR_REVIEW,
            'status' => 'completed',
            'total_questions_count' => 1,
            'correct_answers_count' => 1,
            'payload' => [
                'question_ids' => [$question->getKey()],
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($session)
        ->for($question, 'question')
        ->create([
            'selected_answer' => 'a',
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'is_correct' => true,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->create([
            'total_attempts' => 5,
            'correct_count' => 5,
            'incorrect_count' => 0,
            'correct_streak' => 5,
            'last_quality' => 4,
            'repetitions' => 3,
            'next_review_at' => today()->addDays(7),
            'easiness_factor' => 2.7,
        ]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 2,
        'verified_correct_count' => 1,
        'verified_unknown_count' => 1,
        'verified_incorrect_count' => 0,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'last_verified_at' => now(),
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
    ]);

    $summary = app(ReviewTrainerCompletionSummaryService::class)->summary($session);

    expect($summary['recovery_count'])->toBe(1)
        ->and($summary['stable_count'])->toBe(0)
        ->and($summary['segment_counts'][ReviewMemorySignalService::SEGMENT_RISKY])->toBe(1)
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_RELEARNING])->toBe(1)
        ->and($summary['next_review_label'])->toBe('Możesz wrócić od razu');
});

test('review trainer completion summary maps verified leech risk consistently with planner', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
            'difficulty' => 4,
        ]);

    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => StudySessionManager::MODE_SR_REVIEW,
            'status' => 'completed',
            'total_questions_count' => 1,
            'correct_answers_count' => 0,
            'payload' => [
                'question_ids' => [$question->getKey()],
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($session)
        ->for($question, 'question')
        ->create([
            'selected_answer' => 'b',
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'is_correct' => false,
        ]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 5,
        'verified_correct_count' => 1,
        'verified_unknown_count' => 3,
        'verified_incorrect_count' => 1,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'last_verified_at' => now(),
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
    ]);

    $summary = app(ReviewTrainerCompletionSummaryService::class)->summary($session);

    expect($summary)->not->toBeNull()
        ->and($summary['recovery_count'])->toBe(1)
        ->and($summary['segment_counts'][ReviewMemorySignalService::SEGMENT_RISKY])->toBe(1)
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_LEECH])->toBe(1)
        ->and($summary['memory_state_counts'][ReviewMemorySignalService::STATE_RELEARNING])->toBe(0);
});

test('review trainer completion summary is limited to completed sr review sessions', function () {
    $learnSession = StudySession::factory()->create([
        'mode' => StudySessionManager::MODE_LEARN,
        'status' => 'completed',
        'payload' => ['question_ids' => [1]],
    ]);
    $inProgressReviewSession = StudySession::factory()->create([
        'mode' => StudySessionManager::MODE_SR_REVIEW,
        'status' => 'in_progress',
        'payload' => ['question_ids' => [1]],
    ]);

    expect(app(ReviewTrainerCompletionSummaryService::class)->summary($learnSession))->toBeNull()
        ->and(app(ReviewTrainerCompletionSummaryService::class)->summary($inProgressReviewSession))->toBeNull();
});
