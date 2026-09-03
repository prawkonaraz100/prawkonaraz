<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\ReviewMemoryProgress;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserQuestionProgress;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewMemoryVerifiedSignalService;
use App\Support\ReviewPlannerService;
use App\Support\ReviewTrainerDailyPlanService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('users can browse the review queue page and filter by category', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $categoryA = LicenseCategory::factory()->create([
        'code' => 'A',
        'name' => 'Kategoria A',
        'sort_order' => 2,
    ]);

    $dueQuestionB = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-100',
            'prompt' => 'Pytanie due dla kategorii B.',
        ]);

    $dueQuestionA = Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => 'A-200',
            'prompt' => 'Pytanie due dla kategorii A.',
        ]);

    $futureQuestion = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-300',
            'prompt' => 'Pytanie na przyszlosc.',
        ]);

    QuestionMedia::factory()
        ->for($dueQuestionB)
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/review.webp',
            'mime_type' => 'image/webp',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestionB, 'question')
        ->dueToday()
        ->create([
            'total_attempts' => 4,
            'incorrect_count' => 3,
            'last_quality' => 1,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestionA, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 1,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($futureQuestion, 'question')
        ->scheduledInFuture()
        ->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($user)
        ->get(route('review-queue.index', ['category' => $categoryB->getKey()]))
        ->assertOk();

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->contains(fn (string $query): bool => str_contains($query, 'question_media')))->toBeFalse();

    $response
        ->assertInertia(fn (Assert $page) => $page
            ->component('ReviewQueue/Index')
            ->where('filters.category', $categoryB->getKey())
            ->where('stats.ready_for_review_count', 2)
            ->where('plan.planner_version', ReviewPlannerService::VERSION)
            ->where('plan.daily_plan_policy_version', ReviewTrainerDailyPlanService::VERSION)
            ->where('plan.review_day', today()->toDateString())
            ->where('plan.daily_target_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
            ->where('plan.minimum_session_question_count', ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
            ->where('plan.completed_today_count', 0)
            ->where('plan.daily_remaining_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
            ->where('plan.memory_signal_version', ReviewMemorySignalService::VERSION)
            ->where('plan.due_count', 1)
            ->where('plan.recommended_question_count', 1)
            ->where('plan.coach.version', ReviewPlannerService::COACH_VERSION)
            ->where('plan.coach.tone', 'recovery')
            ->where('plan.segment_counts.risky', 1)
            ->where('plan.preview_count', 1)
            ->where('plan.has_more', false)
            ->missing('plan.preview_question_ids')
            ->where('telemetry.started_sessions_count', 0)
            ->where('telemetry.completed_sessions_count', 0)
            ->has('categories', 2)
            ->where('categories.0.code', 'B')
            ->where('categories.0.due_count', 1)
            ->has('questions', 0)
            ->where('telemetry.completed_full_sessions_count', 0)
            ->where('telemetry.completed_partial_sessions_count', 0)
            ->where('telemetry.completed_unclassified_sessions_count', 0)
            ->where('telemetry.full_average_score_percent', null)
            ->where('telemetry.full_total_answered_count', 0)
            ->where('telemetry.today_answered_count', 0)
            ->where('telemetry.today_correct_count', 0)
            ->where('telemetry.today_unknown_count', 0)
            ->where('telemetry.today_choice_incorrect_count', 0)
            ->where('telemetry.today_needs_recovery_count', 0)
        );
});

test('review queue api returns due questions for the authenticated user only', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-500',
            'prompt' => 'Pytanie w API review queue.',
        ]);

    $otherQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-600',
            'prompt' => 'Pytanie innego uzytkownika.',
        ]);

    QuestionMedia::factory()
        ->for($dueQuestion)
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/review.webp',
            'mime_type' => 'image/webp',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create([
            'total_attempts' => 4,
            'incorrect_count' => 2,
        ]);

    UserQuestionProgress::factory()
        ->for($otherUser)
        ->for($otherQuestion, 'question')
        ->dueToday()
        ->create();
    UserQuestionProgress::factory()
        ->for($user)
        ->for($otherQuestion, 'question')
        ->scheduledInFuture()
        ->create(['total_attempts' => 0]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue'))
        ->assertOk()
        ->assertJsonPath('meta.ready_for_review_count', 1)
        ->assertJsonPath('meta.plan.planner_version', ReviewPlannerService::VERSION)
        ->assertJsonPath('meta.plan.daily_plan_policy_version', ReviewTrainerDailyPlanService::VERSION)
        ->assertJsonPath('meta.plan.review_day', today()->toDateString())
        ->assertJsonPath('meta.plan.daily_target_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->assertJsonPath('meta.plan.minimum_session_question_count', ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->assertJsonPath('meta.plan.completed_today_count', 0)
        ->assertJsonPath('meta.plan.daily_remaining_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->assertJsonPath('meta.plan.memory_signal_version', ReviewMemorySignalService::VERSION)
        ->assertJsonPath('meta.plan.coach.version', ReviewPlannerService::COACH_VERSION)
        ->assertJsonPath('meta.plan.coach.tone', 'recovery')
        ->assertJsonPath('meta.plan.due_count', 1)
        ->assertJsonPath('meta.plan.recommended_question_count', 1)
        ->assertJsonPath('meta.plan.preview_question_ids.0', $dueQuestion->getKey())
        ->assertJsonPath('meta.telemetry.started_sessions_count', 0)
        ->assertJsonPath('meta.telemetry.completed_sessions_count', 0)
        ->assertJsonPath('meta.telemetry.completed_full_sessions_count', 0)
        ->assertJsonPath('meta.telemetry.completed_partial_sessions_count', 0)
        ->assertJsonPath('meta.telemetry.completed_unclassified_sessions_count', 0)
        ->assertJsonPath('meta.telemetry.full_average_score_percent', null)
        ->assertJsonPath('meta.telemetry.full_total_answered_count', 0)
        ->assertJsonPath('meta.telemetry.today_answered_count', 0)
        ->assertJsonPath('meta.telemetry.today_correct_count', 0)
        ->assertJsonPath('meta.telemetry.today_unknown_count', 0)
        ->assertJsonPath('meta.telemetry.today_choice_incorrect_count', 0)
        ->assertJsonPath('meta.telemetry.today_needs_recovery_count', 0)
        ->assertJsonPath('data.questions.0.external_id', 'B-500')
        ->assertJsonPath('data.questions.0.memory_signal.version', ReviewMemorySignalService::VERSION)
        ->assertJsonPath('data.questions.0.total_attempts', 4)
        ->assertJsonPath('data.questions.0.incorrect_count', 2)
        ->assertJsonPath('data.questions.0.media.0.url', 'https://media.example.test/questions/b/review.webp')
        ->assertJsonMissingPath('data.questions.1');
});

test('review queue api can return a plan first payload without preview questions', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-PLAN-FIRST-001',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue', ['include_questions' => 0]))
        ->assertOk()
        ->assertJsonCount(0, 'data.questions')
        ->assertJsonPath('meta.ready_for_review_count', 1)
        ->assertJsonPath('meta.plan.due_count', 1)
        ->assertJsonPath('meta.plan.preview_count', 1)
        ->assertJsonPath('meta.plan.recommended_question_count', 1)
        ->assertJsonMissingPath('meta.plan.preview_question_ids');

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue', ['include_questions' => 'false']))
        ->assertOk()
        ->assertJsonCount(0, 'data.questions')
        ->assertJsonMissingPath('meta.plan.preview_question_ids');
});

test('review queue api exposes first exposure candidates as a separate source', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->sequence(fn ($sequence) => [
            'external_id' => 'B-NEW-'.($sequence->index + 1),
            'difficulty' => 1,
        ])
        ->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue'))
        ->assertOk()
        ->assertJsonPath('meta.ready_for_review_count', 15)
        ->assertJsonPath('meta.categories.0.code', 'B')
        ->assertJsonPath('meta.categories.0.due_count', 15)
        ->assertJsonPath('meta.plan.due_count', 0)
        ->assertJsonPath('meta.plan.candidate_count', 15)
        ->assertJsonPath('meta.plan.new_candidate_count', 15)
        ->assertJsonPath('meta.plan.candidate_source_counts.primary', 0)
        ->assertJsonPath('meta.plan.candidate_source_counts.seen_booster', 0)
        ->assertJsonPath('meta.plan.candidate_source_counts.new_candidate', 15)
        ->assertJsonPath('meta.plan.recommended_question_count', 15)
        ->assertJsonPath('data.questions.0.total_attempts', 0)
        ->assertJsonPath('data.questions.0.memory_signal.source', 'new_candidate')
        ->assertJsonPath('data.questions.0.memory_signal.memory_state', ReviewMemorySignalService::STATE_NEW)
        ->assertJsonPath('data.questions.0.memory_signal.plan_segment', ReviewMemorySignalService::SEGMENT_REINFORCE);
});

test('review queue api rejects invalid include questions flag', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue', ['include_questions' => 'maybe']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('include_questions');
});

test('review queue defaults to the users preferred category when no filter is provided', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $preferredCategory = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $otherCategory = LicenseCategory::factory()->create([
        'code' => 'A',
        'name' => 'Kategoria A',
        'sort_order' => 2,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $preferredCategory->getKey(),
        ]);

    $preferredQuestion = Question::factory()
        ->for($preferredCategory, 'licenseCategory')
        ->create([
            'external_id' => 'B-QUEUE-001',
        ]);

    $otherQuestion = Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create([
            'external_id' => 'A-QUEUE-001',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($preferredQuestion, 'question')
        ->dueToday()
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($otherQuestion, 'question')
        ->dueToday()
        ->create();

    $this->actingAs($user)
        ->get(route('review-queue.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.category', $preferredCategory->getKey())
            ->has('questions', 0)
        );
});

test('review queue renders an empty training plan when nothing is due now', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-FUTURE-001',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->scheduledInFuture()
        ->create();

    $this->actingAs($user)
        ->get(route('review-queue.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ReviewQueue/Index')
            ->where('stats.ready_for_review_count', 0)
            ->where('plan.due_count', 0)
            ->where('plan.recommended_question_count', 0)
            ->where('plan.segment_counts.overdue', 0)
            ->where('plan.segment_counts.risky', 0)
            ->where('plan.segment_counts.reinforce', 0)
            ->where('plan.coach.tone', 'empty')
            ->where('plan.preview_count', 0)
            ->where('telemetry.started_sessions_count', 0)
            ->has('categories', 0)
            ->has('questions', 0)
        );
});

test('review queue category counts include verified memory recovery', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-RECOVERY-001',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->scheduledInFuture()
        ->create([
            'correct_count' => 4,
            'incorrect_count' => 0,
            'correct_streak' => 4,
            'last_quality' => 5,
            'repetitions' => 3,
            'total_attempts' => 4,
        ]);

    ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'verified_attempts_count' => 3,
        'verified_correct_count' => 0,
        'verified_unknown_count' => 2,
        'verified_incorrect_count' => 1,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $this->actingAs($user)
        ->get(route('review-queue.index', ['category' => $category->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ReviewQueue/Index')
            ->where('stats.ready_for_review_count', 1)
            ->where('plan.due_count', 1)
            ->where('plan.coach.tone', 'recovery')
            ->where('categories.0.code', 'B')
            ->where('categories.0.due_count', 1)
            ->has('questions', 0)
        );

    $this->actingAs($user)
        ->getJson(route('api.v1.me.review-queue', ['category' => $category->getKey()]))
        ->assertOk()
        ->assertJsonPath('meta.ready_for_review_count', 1)
        ->assertJsonPath('meta.plan.due_count', 1)
        ->assertJsonPath('data.questions.0.external_id', 'B-RECOVERY-001')
        ->assertJsonPath('data.questions.0.next_review_at', today()->toDateString())
        ->assertJsonPath('data.questions.0.memory_signal.source', 'verified_memory')
        ->assertJsonPath('data.questions.0.memory_signal.verified_memory_signal_version', ReviewMemoryVerifiedSignalService::VERSION)
        ->assertJsonPath('data.questions.0.memory_signal.verified_memory_state', ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
        ->assertJsonPath('data.questions.0.memory_signal.memory_state', ReviewMemorySignalService::STATE_LEECH)
        ->assertJsonPath('data.questions.0.memory_signal.plan_segment', ReviewMemorySignalService::SEGMENT_RISKY);
});

test('legacy review queue path redirects to trener pamieci', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $this->actingAs($user)
        ->get('/review-queue?category=3')
        ->assertRedirect(route('review-queue.index', ['category' => 3]));
});
