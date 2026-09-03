<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewMemoryVerifiedSignalService;
use App\Support\ReviewPlannerService;
use App\Support\ReviewTrainerDailyPlanService;
use App\Support\StudySessionManager;
use Tests\TestCase;

uses(TestCase::class);

test('review planner orders due questions by memory risk without changing progress state', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $overdueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 1]);
    $riskQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 5]);
    $stableQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 2]);
    $futureQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 5]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($overdueQuestion, 'question')
        ->create([
            'next_review_at' => today()->subDay(),
            'incorrect_count' => 0,
            'correct_streak' => 3,
            'last_quality' => 5,
            'total_attempts' => 3,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($riskQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 4,
            'correct_streak' => 0,
            'last_quality' => 1,
            'total_attempts' => 5,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($stableQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 0,
            'correct_streak' => 2,
            'last_quality' => 4,
            'total_attempts' => 2,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($futureQuestion, 'question')
        ->scheduledInFuture()
        ->create();

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['planner_version'])->toBe(ReviewPlannerService::VERSION)
        ->and($plan['daily_plan_policy_version'])->toBe(ReviewTrainerDailyPlanService::VERSION)
        ->and($plan['review_day'])->toBe(today()->toDateString())
        ->and($plan['daily_target_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($plan['minimum_session_question_count'])->toBe(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->and($plan['completed_today_count'])->toBe(0)
        ->and($plan['daily_remaining_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($plan['memory_signal_version'])->toBe(ReviewMemorySignalService::VERSION)
        ->and($plan['due_count'])->toBe(3)
        ->and($plan['candidate_count'])->toBe(3)
        ->and($plan['booster_count'])->toBe(0)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 3,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($plan['recommended_question_count'])->toBe(3)
        ->and($plan['estimated_duration_seconds'])->toBe(135)
        ->and($plan['coach']['version'])->toBe(ReviewPlannerService::COACH_VERSION)
        ->and($plan['coach']['tone'])->toBe('recovery')
        ->and($plan['coach']['primary_action_label'])->toBe('Zacznij od odzyskania')
        ->and($plan['segment_counts'])->toBe([
            ReviewMemorySignalService::SEGMENT_OVERDUE => 1,
            ReviewMemorySignalService::SEGMENT_RISKY => 1,
            ReviewMemorySignalService::SEGMENT_REINFORCE => 1,
        ])
        ->and($plan['memory_state_counts'][ReviewMemorySignalService::STATE_LEECH])->toBe(1)
        ->and($plan['memory_state_counts'][ReviewMemorySignalService::STATE_LEARNING])->toBe(2)
        ->and($plan['ordered_question_ids'])->toBe([
            $overdueQuestion->getKey(),
            $riskQuestion->getKey(),
            $stableQuestion->getKey(),
        ]);
});

test('review planner recommends the first daily block for larger queues', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(60)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());
    $questionIds = app(ReviewPlannerService::class)->recommendedQuestionIds($user, (int) $category->getKey(), 80);

    expect($plan['due_count'])->toBe(50)
        ->and($plan['candidate_count'])->toBe(50)
        ->and($plan['booster_count'])->toBe(0)
        ->and($plan['verification_candidate_count'])->toBe(60)
        ->and($plan['selected_verification_candidate_count'])->toBe(50)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 50,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($plan['recommended_question_count'])->toBe(50)
        ->and($plan['coach']['supporting_label'])->toBe('50 pytań / około 38 min')
        ->and($plan['preview_count'])->toBe(50)
        ->and($plan['has_more'])->toBeFalse()
        ->and($questionIds)->toHaveCount(50);
});

test('review planner fills the first block with previously seen booster candidates', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    Question::factory()
        ->count(55)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->scheduledInFuture()
            ->create([
                'total_attempts' => 2,
                'correct_count' => 2,
                'incorrect_count' => 0,
                'correct_streak' => 2,
                'last_quality' => 4,
            ]));

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());
    $questionIds = app(ReviewPlannerService::class)->recommendedQuestionIds($user, (int) $category->getKey(), 80);

    expect($plan['due_count'])->toBe(1)
        ->and($plan['candidate_count'])->toBe(50)
        ->and($plan['booster_count'])->toBe(49)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 1,
            'seen_booster' => 49,
            'new_candidate' => 0,
        ])
        ->and($plan['recommended_question_count'])->toBe(50)
        ->and($plan['ordered_question_ids'][0])->toBe($dueQuestion->getKey())
        ->and($questionIds)->toHaveCount(50);
});

test('review planner adds a limited first exposure pack when the seen plan is short', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $newQuestions = Question::factory()
        ->count(30)
        ->for($category, 'licenseCategory')
        ->create();

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());
    $questionIds = app(ReviewPlannerService::class)->recommendedQuestionIds($user, (int) $category->getKey(), 80);

    expect($plan['planner_version'])->toBe(ReviewPlannerService::VERSION)
        ->and($plan['due_count'])->toBe(1)
        ->and($plan['candidate_count'])->toBe(16)
        ->and($plan['booster_count'])->toBe(0)
        ->and($plan['new_candidate_count'])->toBe(15)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 1,
            'seen_booster' => 0,
            'new_candidate' => 15,
        ])
        ->and($plan['recommended_question_count'])->toBe(16)
        ->and($plan['ordered_question_ids'][0])->toBe($dueQuestion->getKey())
        ->and(array_slice($plan['ordered_question_ids'], 1))->toHaveCount(15)
        ->and(array_diff(array_slice($plan['ordered_question_ids'], 1), $newQuestions->pluck('id')->all()))->toBe([])
        ->and($questionIds)->toHaveCount(16);
});

test('review planner does not add first exposure candidates when seen candidates fill the first block', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(50)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    Question::factory()
        ->count(30)
        ->for($category, 'licenseCategory')
        ->create();

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(50)
        ->and($plan['candidate_count'])->toBe(50)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 50,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($plan['recommended_question_count'])->toBe(50);
});

test('review planner estimates duration from memory trainer answers only', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $firstQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();
    $secondQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    foreach ([$firstQuestion, $secondQuestion] as $question) {
        UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]);
    }

    $learnSession = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_LEARN,
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $learnSession->getKey(),
        'question_id' => $firstQuestion->getKey(),
        'response_time_ms' => 60000,
    ]);

    $fallbackPlan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($fallbackPlan['recommended_question_count'])->toBe(2)
        ->and($fallbackPlan['estimated_duration_seconds'])->toBe(90)
        ->and($fallbackPlan['estimated_duration_label'])->toBe('2 min');

    $memorySession = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
        'created_at' => today()->subDay(),
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $memorySession->getKey(),
        'question_id' => $secondQuestion->getKey(),
        'response_time_ms' => 10000,
        'answered_at' => today()->subDay(),
        'created_at' => today()->subDay(),
    ]);

    $memoryPlan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($memoryPlan['recommended_question_count'])->toBe(2)
        ->and($memoryPlan['estimated_duration_seconds'])->toBe(60)
        ->and($memoryPlan['estimated_duration_label'])->toBe('1 min');
});

test('review planner does not use unseen future progress as booster candidates', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->scheduledInFuture()
        ->create(['total_attempts' => 0]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(0)
        ->and($plan['candidate_count'])->toBe(0)
        ->and($plan['booster_count'])->toBe(0)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 0,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($plan['recommended_question_count'])->toBe(0)
        ->and($plan['ordered_question_ids'])->toBe([]);
});

test('review planner counts only todays memory trainer answers in the selected category', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $otherCategory = LicenseCategory::factory()->create(['code' => 'A']);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create();

    $memorySessionToday = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
    ]);
    $memorySessionYesterday = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
    ]);
    $learnSessionToday = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_LEARN,
    ]);
    $otherCategoryMemorySessionToday = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $otherCategory->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
    ]);

    $ledgeredAnswer = StudySessionAnswer::factory()->create([
        'study_session_id' => $memorySessionToday->getKey(),
        'answered_at' => today()->setTime(10, 0),
    ]);
    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'question_id' => $ledgeredAnswer->question_id,
        'study_session_id' => $memorySessionToday->getKey(),
        'study_session_answer_id' => $ledgeredAnswer->getKey(),
        'review_day' => today(),
        'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
        'answer_kind' => 'choice',
        'is_correct' => true,
        'answered_at' => $ledgeredAnswer->answered_at,
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $memorySessionToday->getKey(),
        'answered_at' => today()->setTime(10, 30),
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $memorySessionYesterday->getKey(),
        'answered_at' => today()->subDay()->setTime(10, 0),
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $learnSessionToday->getKey(),
        'answered_at' => today()->setTime(11, 0),
    ]);
    StudySessionAnswer::factory()->create([
        'study_session_id' => $otherCategoryMemorySessionToday->getKey(),
        'answered_at' => today()->setTime(12, 0),
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['completed_today_count'])->toBe(2)
        ->and($plan['daily_remaining_count'])->toBe(78);
});

test('review planner does not return questions already answered today in memory trainer', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $answeredTodayQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();
    $freshDueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    foreach ([$answeredTodayQuestion, $freshDueQuestion] as $question) {
        UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create();
    }

    $memorySessionToday = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
    ]);

    StudySessionAnswer::factory()->create([
        'study_session_id' => $memorySessionToday->getKey(),
        'question_id' => $answeredTodayQuestion->getKey(),
        'answered_at' => today()->setTime(10, 0),
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['completed_today_count'])->toBe(1)
        ->and($plan['due_count'])->toBe(1)
        ->and($plan['ordered_question_ids'])->toBe([$freshDueQuestion->getKey()]);
});

test('review planner prioritizes verified recovery over future classic progress', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    $verifiedRecoveryQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 1]);
    $classicDueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 5]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($verifiedRecoveryQuestion, 'question')
        ->scheduledInFuture()
        ->create([
            'correct_count' => 4,
            'incorrect_count' => 0,
            'correct_streak' => 4,
            'last_quality' => 5,
            'repetitions' => 3,
            'total_attempts' => 4,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($classicDueQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 0,
            'correct_streak' => 2,
            'last_quality' => 4,
            'total_attempts' => 2,
        ]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $verifiedRecoveryQuestion->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 5,
        'verified_correct_count' => 1,
        'verified_unknown_count' => 3,
        'verified_incorrect_count' => 1,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['verified_memory_signal_version'])->toBe(ReviewMemoryVerifiedSignalService::VERSION)
        ->and($plan['due_count'])->toBe(2)
        ->and($plan['recommended_question_count'])->toBe(2)
        ->and($plan['ordered_question_ids'])->toBe([
            $verifiedRecoveryQuestion->getKey(),
            $classicDueQuestion->getKey(),
        ])
        ->and($plan['segment_counts'][ReviewMemorySignalService::SEGMENT_RISKY])->toBe(1)
        ->and($plan['memory_state_counts'][ReviewMemorySignalService::STATE_LEECH])->toBe(1)
        ->and($plan['coach']['tone'])->toBe('recovery');
});

test('review planner includes verified memory progress even without classic progress', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['difficulty' => 2]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 1,
        'verified_correct_count' => 1,
        'verified_correct_streak' => 1,
        'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(1)
        ->and($plan['candidate_count'])->toBe(1)
        ->and($plan['new_candidate_count'])->toBe(0)
        ->and($plan['candidate_source_counts'])->toBe([
            'primary' => 1,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($plan['ordered_question_ids'])->toBe([$question->getKey()])
        ->and($plan['verified_memory_signal_version'])->toBe(ReviewMemoryVerifiedSignalService::VERSION);
});

test('review planner lets verified future memory suppress stale classic due progress', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 5,
            'correct_streak' => 0,
            'last_quality' => 1,
            'total_attempts' => 9,
        ]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 3,
        'verified_correct_count' => 3,
        'verified_correct_streak' => 3,
        'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
        'next_verified_review_at' => today()->addDays(6),
        'verified_memory_state' => ReviewMemoryProgress::STATE_VERIFIED_MEMORY,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(0)
        ->and($plan['candidate_count'])->toBe(0)
        ->and($plan['actionable_count'])->toBe(0)
        ->and($plan['verification_candidate_count'])->toBe(0)
        ->and($plan['selected_verification_candidate_count'])->toBe(0)
        ->and($plan['ordered_question_ids'])->toBe([]);
});

test('review planner caps verified actionable progress to the current session block', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(55)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => ReviewMemoryProgress::factory()->create([
            'user_id' => $user->getKey(),
            'question_id' => $question->getKey(),
            'license_category_id' => $category->getKey(),
            'verified_attempts_count' => 1,
            'verified_correct_count' => 1,
            'verified_correct_streak' => 1,
            'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
            'next_verified_review_at' => today(),
            'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
            'source_policy_version' => ReviewMemoryProgressService::VERSION,
        ]));

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(50)
        ->and($plan['candidate_count'])->toBe(50)
        ->and($plan['actionable_count'])->toBe(50)
        ->and($plan['recommended_question_count'])->toBe(50)
        ->and($plan['ordered_question_ids'])->toHaveCount(50);
});

test('review planner does not repeat verified recovery already answered today', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->scheduledInFuture()
        ->create();

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 2,
        'verified_unknown_count' => 1,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
    ]);

    $session = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => StudySessionManager::MODE_SR_REVIEW,
    ]);

    StudySessionAnswer::factory()->create([
        'study_session_id' => $session->getKey(),
        'question_id' => $question->getKey(),
        'answered_at' => today()->setTime(10, 0),
    ]);

    $plan = app(ReviewPlannerService::class)->plan($user, (int) $category->getKey());

    expect($plan['due_count'])->toBe(0)
        ->and($plan['recommended_question_count'])->toBe(0)
        ->and($plan['ordered_question_ids'])->toBe([]);
});
