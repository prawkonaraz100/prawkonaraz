<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewPlannerService;
use App\Support\ReviewTrainerAnalyticsService;
use App\Support\ReviewTrainerDailyPlanService;
use App\Support\StudySessionAnswerKind;
use Inertia\Testing\AssertableInertia as Assert;

test('sr review session start records a planner telemetry event', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(16)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 40,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();
    $event = ReviewTrainerEvent::query()->firstOrFail();

    expect($session->mode)->toBe('sr_review')
        ->and($session->total_questions_count)->toBe(16)
        ->and($session->payload['review_plan']['planner_version'])->toBe(ReviewPlannerService::VERSION)
        ->and($session->payload['review_plan']['daily_plan_policy_version'])->toBe(ReviewTrainerDailyPlanService::VERSION)
        ->and($session->payload['review_plan']['review_day'])->toBe(today()->toDateString())
        ->and($session->payload['review_plan']['daily_target_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($session->payload['review_plan']['minimum_session_question_count'])->toBe(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->and($session->payload['review_plan']['completed_today_count'])->toBe(0)
        ->and($session->payload['review_plan']['daily_remaining_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($session->payload['review_plan']['due_count'])->toBe(16)
        ->and($session->payload['review_plan']['candidate_count'])->toBe(16)
        ->and($session->payload['review_plan']['booster_count'])->toBe(0)
        ->and($session->payload['review_plan']['new_candidate_count'])->toBe(0)
        ->and($session->payload['review_plan']['candidate_source_counts'])->toBe([
            'primary' => 16,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($session->payload['review_plan']['recommended_question_count'])->toBe(16)
        ->and($session->payload['review_plan']['selected_question_count'])->toBe(16)
        ->and($event->event_name)->toBe('review.session_started')
        ->and($event->planner_version)->toBe(ReviewPlannerService::VERSION)
        ->and($event->user_id)->toBe($user->getKey())
        ->and($event->license_category_id)->toBe($category->getKey())
        ->and($event->study_session_id)->toBe($session->getKey())
        ->and($event->payload['daily_plan_policy_version'])->toBe(ReviewTrainerDailyPlanService::VERSION)
        ->and($event->payload['review_day'])->toBe(today()->toDateString())
        ->and($event->payload['daily_target_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($event->payload['minimum_session_question_count'])->toBe(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->and($event->payload['completed_today_count'])->toBe(0)
        ->and($event->payload['daily_remaining_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($event->payload['due_count'])->toBe(16)
        ->and($event->payload['candidate_count'])->toBe(16)
        ->and($event->payload['booster_count'])->toBe(0)
        ->and($event->payload['new_candidate_count'])->toBe(0)
        ->and($event->payload['candidate_source_counts'])->toBe([
            'primary' => 16,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($event->payload['recommended_question_count'])->toBe(16)
        ->and($event->payload['selected_question_count'])->toBe(16)
        ->and($event->payload['selected_question_ids'])->toHaveCount(16);
});

test('sr review can start a fifty question first daily block', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(55)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 80,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();
    $event = ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.session_started')
        ->firstOrFail();

    expect($session->total_questions_count)->toBe(50)
        ->and($session->payload['review_plan']['due_count'])->toBe(50)
        ->and($session->payload['review_plan']['recommended_question_count'])->toBe(50)
        ->and($session->payload['review_plan']['selected_question_count'])->toBe(50)
        ->and($event->payload['selected_question_count'])->toBe(50);
});

test('sr review start is idempotent for the same review day and policy', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(5)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    $payload = [
        'license_category_id' => $category->getKey(),
        'mode' => 'sr_review',
        'question_count' => 50,
    ];

    $this->actingAs($user)
        ->post(route('study-sessions.store'), $payload)
        ->assertRedirect();

    $firstSession = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), $payload)
        ->assertRedirect();

    expect(StudySession::query()->where('user_id', $user->getKey())->count())->toBe(1)
        ->and(StudySession::query()->where('user_id', $user->getKey())->where('status', 'in_progress')->count())->toBe(1)
        ->and(StudySession::query()->where('user_id', $user->getKey())->latest('id')->first()?->getKey())->toBe($firstSession->getKey())
        ->and(ReviewTrainerEvent::query()->where('event_name', 'review.session_started')->count())->toBe(1);
});

test('starting a new session records replaced telemetry for an older active sr review', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $oldSession = StudySession::factory()
        ->inProgress()
        ->create([
            'user_id' => $user->getKey(),
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'started_at' => today()->subDay()->setTime(9, 0),
            'total_questions_count' => 1,
            'payload' => [
                'question_ids' => [$question->getKey()],
                'current_index' => 0,
                'answered_count' => 0,
                'review_plan' => [
                    'planner_version' => ReviewPlannerService::VERSION,
                    'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
                    'review_day' => today()->subDay()->toDateString(),
                    'daily_target_count' => ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT,
                    'minimum_session_question_count' => ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT,
                    'completed_today_count' => 0,
                    'daily_remaining_count' => ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT,
                    'due_count' => 1,
                    'recommended_question_count' => 1,
                    'selected_question_count' => 1,
                    'estimated_duration_seconds' => 45,
                ],
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 50,
        ])
        ->assertRedirect();

    $newSession = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();
    $replacedEvent = ReviewTrainerEvent::query()
        ->where('study_session_id', $oldSession->getKey())
        ->where('event_name', 'review.replaced')
        ->firstOrFail();

    expect(StudySession::query()->where('user_id', $user->getKey())->count())->toBe(2)
        ->and($oldSession->refresh()->status)->toBe('completed')
        ->and($newSession->status)->toBe('in_progress')
        ->and($replacedEvent->payload['started_review_day'])->toBe(today()->subDay()->toDateString())
        ->and($replacedEvent->payload['replacement_review_day'])->toBe(today()->toDateString())
        ->and($replacedEvent->payload['replacement_mode'])->toBe('sr_review')
        ->and($replacedEvent->payload['replacement_reason'])->toBe('new_session_started')
        ->and(ReviewTrainerEvent::query()->where('event_name', 'review.session_started')->count())->toBe(1)
        ->and(ReviewTrainerEvent::query()->where('event_name', 'review.completed')->count())->toBe(0);
});

test('regular learn session does not record review trainer telemetry', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    expect(ReviewTrainerEvent::query()->count())->toBe(0);
});

test('sr review auto completion records one completion telemetry event', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertOk();

    $completedEvent = ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.completed')
        ->firstOrFail();
    $summary = app(ReviewTrainerAnalyticsService::class)->summary($user, (int) $category->getKey());

    expect(ReviewTrainerEvent::query()->where('study_session_id', $session->getKey())->count())->toBe(2)
        ->and($completedEvent->payload['daily_plan_policy_version'])->toBe(ReviewTrainerDailyPlanService::VERSION)
        ->and($completedEvent->payload['started_review_day'])->toBe(today()->toDateString())
        ->and($completedEvent->payload['completed_review_day'])->toBe(today()->toDateString())
        ->and($completedEvent->payload['daily_target_count'])->toBe(ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->and($completedEvent->payload['minimum_session_question_count'])->toBe(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->and($completedEvent->payload['completed_today_count_at_start'])->toBe(0)
        ->and($completedEvent->payload['completed_today_count_after_session'])->toBe(1)
        ->and($completedEvent->payload['due_count'])->toBe(1)
        ->and($completedEvent->payload['candidate_count'])->toBe(1)
        ->and($completedEvent->payload['booster_count'])->toBe(0)
        ->and($completedEvent->payload['new_candidate_count'])->toBe(0)
        ->and($completedEvent->payload['candidate_source_counts'])->toBe([
            'primary' => 1,
            'seen_booster' => 0,
            'new_candidate' => 0,
        ])
        ->and($completedEvent->payload['completion_type'])->toBe('auto_full')
        ->and($completedEvent->payload['answered_count'])->toBe(1)
        ->and($completedEvent->payload['correct_answers_count'])->toBe(1)
        ->and($completedEvent->payload['total_questions_count'])->toBe(1)
        ->and($completedEvent->payload['score_percent'])->toBe(100)
        ->and($summary['started_sessions_count'])->toBe(1)
        ->and($summary['completed_sessions_count'])->toBe(1)
        ->and($summary['completed_full_sessions_count'])->toBe(1)
        ->and($summary['completed_partial_sessions_count'])->toBe(0)
        ->and($summary['completed_unclassified_sessions_count'])->toBe(0)
        ->and($summary['completion_rate_percent'])->toBe(100.0)
        ->and($summary['full_completion_rate_percent'])->toBe(100.0)
        ->and($summary['average_score_percent'])->toBe(100.0)
        ->and($summary['total_answered_count'])->toBe(1)
        ->and($summary['full_average_score_percent'])->toBe(100.0)
        ->and($summary['full_total_answered_count'])->toBe(1)
        ->and($summary['today_review_day'])->toBe(today()->toDateString())
        ->and($summary['today_answered_count'])->toBe(1)
        ->and($summary['today_correct_count'])->toBe(1)
        ->and($summary['today_unknown_count'])->toBe(0)
        ->and($summary['today_choice_incorrect_count'])->toBe(0)
        ->and($summary['today_needs_recovery_count'])->toBe(0)
        ->and($summary['last_completed_at'])->not->toBeNull();
});

test('sr review answers update verified memory progress without changing classic progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $classicProgress = UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertOk();

    $answer = StudySessionAnswer::query()
        ->where('study_session_id', $session->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();
    $dailyAnswer = ReviewTrainerDailyAnswer::query()
        ->where('study_session_answer_id', $answer->getKey())
        ->firstOrFail();
    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect($memoryProgress->license_category_id)->toBe($category->getKey())
        ->and($memoryProgress->verified_attempts_count)->toBe(1)
        ->and($memoryProgress->verified_correct_count)->toBe(1)
        ->and($memoryProgress->verified_unknown_count)->toBe(0)
        ->and($memoryProgress->verified_incorrect_count)->toBe(0)
        ->and($memoryProgress->verified_correct_streak)->toBe(1)
        ->and($memoryProgress->last_verified_result)->toBe(ReviewMemoryProgress::RESULT_CORRECT)
        ->and($memoryProgress->verified_memory_state)->toBe(ReviewMemoryProgress::STATE_REVIEW)
        ->and($memoryProgress->source_policy_version)->toBe(ReviewMemoryProgressService::VERSION)
        ->and($memoryProgress->last_study_session_answer_id)->toBe($answer->getKey())
        ->and($memoryProgress->next_verified_review_at?->toDateString())->toBe(today()->addDay()->toDateString())
        ->and($dailyAnswer->user_id)->toBe($user->getKey())
        ->and($dailyAnswer->license_category_id)->toBe($category->getKey())
        ->and($dailyAnswer->question_id)->toBe($question->getKey())
        ->and($dailyAnswer->study_session_id)->toBe($session->getKey())
        ->and($dailyAnswer->review_day?->toDateString())->toBe(today()->toDateString())
        ->and($dailyAnswer->daily_plan_policy_version)->toBe(ReviewTrainerDailyPlanService::VERSION)
        ->and($dailyAnswer->answer_kind)->toBe(StudySessionAnswerKind::CHOICE)
        ->and($dailyAnswer->is_correct)->toBeTrue()
        ->and($classicProgress->refresh()->total_attempts)->toBe(1);
});

test('duplicate sr review answer does not double count verified memory progress or change classic progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $classicProgress = UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $payload = [
        'question_id' => $question->getKey(),
        'selected_answer' => 'a',
        'response_time_ms' => 1000,
    ];

    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $session), $payload)
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $session), $payload)
        ->assertRedirect();

    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect(StudySessionAnswer::query()->where('study_session_id', $session->getKey())->count())->toBe(1)
        ->and(ReviewTrainerDailyAnswer::query()->where('study_session_id', $session->getKey())->count())->toBe(1)
        ->and($classicProgress->refresh()->total_attempts)->toBe(1)
        ->and($memoryProgress->verified_attempts_count)->toBe(1)
        ->and($memoryProgress->verified_correct_count)->toBe(1);
});

test('sr review response time is stored but does not penalize verified memory', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 120000,
        ])
        ->assertOk();

    $answer = StudySessionAnswer::query()
        ->where('study_session_id', $session->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();
    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect($answer->response_time_ms)->toBe(120000)
        ->and($memoryProgress->last_verified_result)->toBe(ReviewMemoryProgress::RESULT_CORRECT)
        ->and($memoryProgress->verified_memory_state)->toBe(ReviewMemoryProgress::STATE_REVIEW)
        ->and($memoryProgress->verified_correct_count)->toBe(1)
        ->and($memoryProgress->verified_incorrect_count)->toBe(0)
        ->and($memoryProgress->verified_unknown_count)->toBe(0)
        ->and($memoryProgress->next_verified_review_at?->toDateString())->toBe(today()->addDay()->toDateString());
});

test('learn answers do not create verified memory progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $session), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertRedirect();

    expect(ReviewMemoryProgress::query()->count())->toBe(0)
        ->and(ReviewTrainerDailyAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('question_id', $question->getKey())
            ->first()?->total_attempts)->toBe(1);
});

test('sr review first exposure candidates create verified memory without classic progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 80,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();
    $questionId = (int) $session->payload['question_ids'][0];

    expect($session->total_questions_count)->toBe(15)
        ->and($session->payload['review_plan']['due_count'])->toBe(0)
        ->and($session->payload['review_plan']['candidate_count'])->toBe(15)
        ->and($session->payload['review_plan']['new_candidate_count'])->toBe(15)
        ->and($session->payload['review_plan']['candidate_source_counts'])->toBe([
            'primary' => 0,
            'seen_booster' => 0,
            'new_candidate' => 15,
        ]);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questionId,
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertOk()
        ->assertJsonPath('answer.answer_kind', StudySessionAnswerKind::CHOICE)
        ->assertJsonPath('answer.is_correct', true);

    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $questionId)
        ->firstOrFail();

    expect($memoryProgress->verified_attempts_count)->toBe(1)
        ->and($memoryProgress->verified_correct_count)->toBe(1)
        ->and($memoryProgress->verified_correct_streak)->toBe(1)
        ->and($memoryProgress->verified_memory_state)->toBe(ReviewMemoryProgress::STATE_REVIEW)
        ->and(UserQuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('question_id', $questionId)
            ->exists())->toBeFalse();
});

test('sr review unknown answer updates verified memory without changing classic progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $classicProgress = UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'answer_kind' => StudySessionAnswerKind::UNKNOWN,
            'response_time_ms' => 1000,
        ])
        ->assertOk()
        ->assertJsonPath('answer.selected_answer', null)
        ->assertJsonPath('answer.answer_kind', StudySessionAnswerKind::UNKNOWN)
        ->assertJsonPath('answer.is_correct', false);

    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();
    $dailyAnswer = ReviewTrainerDailyAnswer::query()->firstOrFail();
    $summary = app(ReviewTrainerAnalyticsService::class)->summary($user, (int) $category->getKey());

    expect($classicProgress->refresh()->total_attempts)->toBe(1)
        ->and($dailyAnswer->answer_kind)->toBe(StudySessionAnswerKind::UNKNOWN)
        ->and($dailyAnswer->is_correct)->toBeFalse()
        ->and($memoryProgress->verified_attempts_count)->toBe(1)
        ->and($memoryProgress->verified_correct_count)->toBe(0)
        ->and($memoryProgress->verified_unknown_count)->toBe(1)
        ->and($memoryProgress->verified_incorrect_count)->toBe(0)
        ->and($memoryProgress->verified_correct_streak)->toBe(0)
        ->and($memoryProgress->last_verified_result)->toBe(ReviewMemoryProgress::RESULT_UNKNOWN)
        ->and($memoryProgress->verified_memory_state)->toBe(ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
        ->and($memoryProgress->next_verified_review_at?->toDateString())->toBe(today()->toDateString())
        ->and($summary['today_answered_count'])->toBe(1)
        ->and($summary['today_correct_count'])->toBe(0)
        ->and($summary['today_unknown_count'])->toBe(1)
        ->and($summary['today_choice_incorrect_count'])->toBe(0)
        ->and($summary['today_needs_recovery_count'])->toBe(1);
});

test('sr review incorrect choice telemetry is separate from unknown', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'b',
            'response_time_ms' => 1000,
        ])
        ->assertOk()
        ->assertJsonPath('answer.answer_kind', StudySessionAnswerKind::CHOICE)
        ->assertJsonPath('answer.is_correct', false);

    $dailyAnswer = ReviewTrainerDailyAnswer::query()->firstOrFail();
    $summary = app(ReviewTrainerAnalyticsService::class)->summary($user, (int) $category->getKey());

    expect($dailyAnswer->answer_kind)->toBe(StudySessionAnswerKind::CHOICE)
        ->and($dailyAnswer->is_correct)->toBeFalse()
        ->and($summary['today_answered_count'])->toBe(1)
        ->and($summary['today_correct_count'])->toBe(0)
        ->and($summary['today_unknown_count'])->toBe(0)
        ->and($summary['today_choice_incorrect_count'])->toBe(1)
        ->and($summary['today_needs_recovery_count'])->toBe(1);
});

test('review analytics scopes daily telemetry by category and allowed categories', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = LicenseCategory::factory()->create(['code' => 'B']);
    $categoryC = LicenseCategory::factory()->create(['code' => 'C']);
    $questionB = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create(['correct_answer' => 'a']);
    $questionC = Question::factory()
        ->for($categoryC, 'licenseCategory')
        ->create(['correct_answer' => 'a']);
    $sessionB = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $categoryB->getKey(),
        'mode' => 'sr_review',
    ]);
    $sessionC = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $categoryC->getKey(),
        'mode' => 'sr_review',
    ]);
    $answerB = StudySessionAnswer::factory()
        ->for($sessionB)
        ->for($questionB, 'question')
        ->create([
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'selected_answer' => 'a',
            'is_correct' => true,
            'answered_at' => now(),
        ]);
    $answerC = StudySessionAnswer::factory()
        ->for($sessionC)
        ->for($questionC, 'question')
        ->unknown()
        ->create([
            'answered_at' => now(),
        ]);

    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $categoryB->getKey(),
        'question_id' => $questionB->getKey(),
        'study_session_id' => $sessionB->getKey(),
        'study_session_answer_id' => $answerB->getKey(),
        'review_day' => today(),
        'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
        'answer_kind' => StudySessionAnswerKind::CHOICE,
        'is_correct' => true,
        'answered_at' => now(),
    ]);
    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $categoryC->getKey(),
        'question_id' => $questionC->getKey(),
        'study_session_id' => $sessionC->getKey(),
        'study_session_answer_id' => $answerC->getKey(),
        'review_day' => today(),
        'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
        'answer_kind' => StudySessionAnswerKind::UNKNOWN,
        'is_correct' => false,
        'answered_at' => now(),
    ]);

    $service = app(ReviewTrainerAnalyticsService::class);
    $selectedCategorySummary = $service->summary($user, (int) $categoryB->getKey(), [
        (int) $categoryB->getKey(),
        (int) $categoryC->getKey(),
    ]);
    $allowedCategorySummary = $service->summary($user, null, [(int) $categoryB->getKey()]);
    $allAllowedSummary = $service->summary($user, null, [
        (int) $categoryB->getKey(),
        (int) $categoryC->getKey(),
    ]);

    expect($selectedCategorySummary['today_answered_count'])->toBe(1)
        ->and($selectedCategorySummary['today_correct_count'])->toBe(1)
        ->and($selectedCategorySummary['today_unknown_count'])->toBe(0)
        ->and($selectedCategorySummary['today_choice_incorrect_count'])->toBe(0)
        ->and($selectedCategorySummary['today_needs_recovery_count'])->toBe(0)
        ->and($allowedCategorySummary['today_answered_count'])->toBe(1)
        ->and($allowedCategorySummary['today_correct_count'])->toBe(1)
        ->and($allowedCategorySummary['today_unknown_count'])->toBe(0)
        ->and($allowedCategorySummary['today_choice_incorrect_count'])->toBe(0)
        ->and($allowedCategorySummary['today_needs_recovery_count'])->toBe(0)
        ->and($allAllowedSummary['today_answered_count'])->toBe(2)
        ->and($allAllowedSummary['today_correct_count'])->toBe(1)
        ->and($allAllowedSummary['today_unknown_count'])->toBe(1)
        ->and($allAllowedSummary['today_choice_incorrect_count'])->toBe(0)
        ->and($allAllowedSummary['today_needs_recovery_count'])->toBe(1);
});

test('sr review completion payload exposes unknown answer as an answered result', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = StudySession::query()
        ->where('user_id', $user->getKey())
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'answer_kind' => StudySessionAnswerKind::UNKNOWN,
            'response_time_ms' => 1000,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $session->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'sr_review')
            ->where('session.status', 'completed')
            ->where('progress.answered', 1)
            ->where('currentQuestion', null)
            ->has('results', 1)
            ->where('results.0.id', $question->getKey())
            ->where('results.0.selected_answer', null)
            ->where('results.0.answer_kind', StudySessionAnswerKind::UNKNOWN)
            ->where('results.0.is_correct', false)
            ->where('results.0.response_time_ms', 1000)
            ->where('reviewCompletion.answered_count', 1)
            ->where('reviewCompletion.incorrect_answers_count', 1)
            ->where('reviewCompletion.unknown_answers_count', 1)
            ->where('reviewCompletion.choice_incorrect_answers_count', 0)
            ->where('reviewCompletion.needs_recovery_answers_count', 1)
            ->where('reviewCompletion.recovery_count', 1)
            ->where('reviewCompletion.next_step.tone', 'recovery')
        );
});

test('unknown answer is rejected outside sr review', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'answer_kind' => StudySessionAnswerKind::UNKNOWN,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('answer_kind');

    expect(StudySessionAnswer::query()->count())->toBe(0)
        ->and(ReviewMemoryProgress::query()->count())->toBe(0)
        ->and(ReviewTrainerDailyAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()->count())->toBe(0);
});

test('manual sr review completion telemetry is idempotent', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create()
        ->each(fn (Question $question) => UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->dueToday()
            ->create(['total_attempts' => 1]));

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 2,
        ])
        ->assertRedirect();

    $session = StudySession::query()->where('user_id', $user->getKey())->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->post(route('study-sessions.complete', $session))
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('study-sessions.complete', $session))
        ->assertRedirect();

    $completedEvent = ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.completed')
        ->firstOrFail();
    $summary = app(ReviewTrainerAnalyticsService::class)->summary($user, (int) $category->getKey());

    expect(ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.completed')
        ->count())->toBe(1)
        ->and($completedEvent->payload['completion_type'])->toBe('manual_partial')
        ->and($completedEvent->payload['answered_count'])->toBe(0)
        ->and($completedEvent->payload['total_questions_count'])->toBe(2)
        ->and($summary['completed_full_sessions_count'])->toBe(0)
        ->and($summary['completed_partial_sessions_count'])->toBe(1)
        ->and($summary['completed_unclassified_sessions_count'])->toBe(0)
        ->and($summary['full_completion_rate_percent'])->toBe(0.0)
        ->and($summary['full_average_score_percent'])->toBeNull()
        ->and($summary['full_total_answered_count'])->toBe(0)
        ->and($summary['today_answered_count'])->toBe(0)
        ->and($summary['today_correct_count'])->toBe(0)
        ->and($summary['today_unknown_count'])->toBe(0)
        ->and($summary['today_choice_incorrect_count'])->toBe(0)
        ->and($summary['today_needs_recovery_count'])->toBe(0);
});

test('review analytics keeps legacy completion telemetry unclassified', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);

    ReviewTrainerEvent::query()->create([
        'event_id' => 'legacy-start-'.$user->getKey(),
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'event_name' => 'review.session_started',
        'planner_version' => ReviewPlannerService::VERSION,
        'payload' => [],
        'occurred_at' => now()->subMinute(),
    ]);

    ReviewTrainerEvent::query()->create([
        'event_id' => 'legacy-complete-'.$user->getKey(),
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'event_name' => 'review.completed',
        'planner_version' => ReviewPlannerService::VERSION,
        'payload' => [
            'answered_count' => 2,
            'score_percent' => 50,
        ],
        'occurred_at' => now(),
    ]);

    $summary = app(ReviewTrainerAnalyticsService::class)->summary($user, (int) $category->getKey());

    expect($summary['started_sessions_count'])->toBe(1)
        ->and($summary['completed_sessions_count'])->toBe(1)
        ->and($summary['completed_full_sessions_count'])->toBe(0)
        ->and($summary['completed_partial_sessions_count'])->toBe(0)
        ->and($summary['completed_unclassified_sessions_count'])->toBe(1)
        ->and($summary['completion_rate_percent'])->toBe(100.0)
        ->and($summary['full_completion_rate_percent'])->toBe(0.0)
        ->and($summary['average_score_percent'])->toBe(50.0)
        ->and($summary['total_answered_count'])->toBe(2)
        ->and($summary['full_average_score_percent'])->toBeNull()
        ->and($summary['full_total_answered_count'])->toBe(0)
        ->and($summary['today_answered_count'])->toBe(0)
        ->and($summary['today_correct_count'])->toBe(0)
        ->and($summary['today_unknown_count'])->toBe(0)
        ->and($summary['today_choice_incorrect_count'])->toBe(0)
        ->and($summary['today_needs_recovery_count'])->toBe(0);
});
