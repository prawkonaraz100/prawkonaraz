<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;

test('category analytics api returns aggregated stats for the authenticated user', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();

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

    $q1 = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-101',
            'difficulty' => 1,
            'points' => 1,
        ]);

    $q2 = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-102',
            'difficulty' => 4,
            'points' => 3,
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-103',
            'difficulty' => 5,
            'points' => 3,
        ]);

    $otherCategoryQuestion = Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => 'A-201',
            'difficulty' => 2,
            'points' => 1,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($q1, 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 2,
            'incorrect_count' => 0,
            'correct_streak' => 2,
            'last_quality' => 5,
            'next_review_at' => today()->addDay(),
            'last_answered_at' => now()->subDay(),
            'first_answered_at' => now()->subDays(3),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($q2, 'question')
        ->create([
            'total_attempts' => 3,
            'correct_count' => 1,
            'incorrect_count' => 2,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
            'last_answered_at' => now(),
            'first_answered_at' => now()->subDays(4),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($otherCategoryQuestion, 'question')
        ->create([
            'total_attempts' => 5,
            'correct_count' => 4,
            'incorrect_count' => 1,
            'correct_streak' => 2,
            'last_quality' => 4,
            'next_review_at' => today()->addDays(2),
        ]);

    UserQuestionProgress::factory()
        ->for($otherUser)
        ->for($q2, 'question')
        ->create([
            'total_attempts' => 10,
            'correct_count' => 9,
            'incorrect_count' => 1,
            'correct_streak' => 4,
            'last_quality' => 5,
            'next_review_at' => today()->addDays(4),
        ]);

    $sessionOne = StudySession::factory()
        ->for($user)
        ->for($categoryB, 'licenseCategory')
        ->create([
            'status' => 'completed',
            'mode' => 'learn',
        ]);

    $sessionTwo = StudySession::factory()
        ->for($user)
        ->for($categoryB, 'licenseCategory')
        ->create([
            'status' => 'completed',
            'mode' => 'exam',
        ]);

    $otherUsersSession = StudySession::factory()
        ->for($otherUser)
        ->for($categoryB, 'licenseCategory')
        ->create([
            'status' => 'completed',
        ]);

    StudySessionAnswer::factory()
        ->for($sessionOne, 'studySession')
        ->for($q1, 'question')
        ->create([
            'is_correct' => true,
            'response_time_ms' => 5000,
            'created_at' => now()->subDay(),
            'answered_at' => now()->subDay(),
        ]);

    StudySessionAnswer::factory()
        ->for($sessionTwo, 'studySession')
        ->for($q1, 'question')
        ->create([
            'is_correct' => true,
            'response_time_ms' => 4000,
            'created_at' => now(),
            'answered_at' => now(),
        ]);

    StudySessionAnswer::factory()
        ->for($sessionOne, 'studySession')
        ->for($q2, 'question')
        ->create([
            'is_correct' => false,
            'response_time_ms' => 15000,
            'created_at' => now(),
            'answered_at' => now(),
        ]);

    StudySessionAnswer::factory()
        ->for($sessionTwo, 'studySession')
        ->for($q2, 'question')
        ->create([
            'is_correct' => false,
            'response_time_ms' => 12000,
            'created_at' => now()->subDays(2),
            'answered_at' => now()->subDays(2),
        ]);

    StudySessionAnswer::factory()
        ->for($otherUsersSession, 'studySession')
        ->for($q2, 'question')
        ->create([
            'is_correct' => true,
            'response_time_ms' => 1000,
        ]);

    $response = $this->actingAs($user)
        ->getJson(route('api.v1.me.analytics.categories.show', $categoryB));

    $response->assertOk()
        ->assertJsonPath('data.category.id', $categoryB->getKey())
        ->assertJsonPath('data.summary.questions_total', 3)
        ->assertJsonPath('data.summary.tracked_questions_count', 2)
        ->assertJsonPath('data.summary.coverage_pct', 66.7)
        ->assertJsonPath('data.summary.completed_sessions_count', 2)
        ->assertJsonPath('data.summary.answered_count', 4)
        ->assertJsonPath('data.summary.correct_answers_count', 2)
        ->assertJsonPath('data.summary.accuracy_pct', 50)
        ->assertJsonPath('data.summary.avg_response_time_ms', 9000)
        ->assertJsonPath('data.summary.ready_for_review_count', 1)
        ->assertJsonPath('data.summary.hard_questions_count', 1)
        ->assertJsonPath('data.weak_spots.0.external_id', 'B-102')
        ->assertJsonPath('data.breakdowns.question_types.0.key', 'boolean')
        ->assertJsonPath('data.breakdowns.question_types.1.key', 'single_choice')
        ->assertJsonPath('data.breakdowns.points.0.key', 1)
        ->assertJsonPath('data.breakdowns.points.1.key', 3)
        ->assertJsonPath('data.recent_activity.4.answered_count', 1)
        ->assertJsonPath('data.recent_activity.5.answered_count', 1)
        ->assertJsonPath('data.recent_activity.6.answered_count', 2);
});

test('category analytics api returns empty user stats when category has no personal progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();

    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($otherUser)
        ->for($question, 'question')
        ->create([
            'total_attempts' => 3,
            'correct_count' => 1,
            'incorrect_count' => 2,
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.analytics.categories.show', $category))
        ->assertOk()
        ->assertJsonPath('data.summary.questions_total', 1)
        ->assertJsonPath('data.summary.tracked_questions_count', 0)
        ->assertJsonPath('data.summary.coverage_pct', 0)
        ->assertJsonPath('data.summary.answered_count', 0)
        ->assertJsonPath('data.summary.ready_for_review_count', 0)
        ->assertJsonPath('data.summary.hard_questions_count', 0)
        ->assertJsonCount(0, 'data.weak_spots');
});

test('category analytics api excludes memory trainer activity from classic summary', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $memorySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'sr_review',
            'status' => 'completed',
            'correct_answers_count' => 1,
            'total_questions_count' => 1,
            'score_percent' => 100,
        ]);

    StudySessionAnswer::factory()
        ->for($memorySession, 'studySession')
        ->for($question, 'question')
        ->create([
            'is_correct' => true,
            'response_time_ms' => 1000,
            'created_at' => now(),
            'answered_at' => now(),
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.analytics.categories.show', $category))
        ->assertOk()
        ->assertJsonPath('data.summary.questions_total', 1)
        ->assertJsonPath('data.summary.tracked_questions_count', 0)
        ->assertJsonPath('data.summary.completed_sessions_count', 0)
        ->assertJsonPath('data.summary.answered_count', 0)
        ->assertJsonPath('data.summary.correct_answers_count', 0)
        ->assertJsonPath('data.summary.accuracy_pct', 0)
        ->assertJsonPath('data.summary.readiness_score', 0)
        ->assertJsonPath('data.summary.last_answered_at', null)
        ->assertJsonPath('data.recent_activity.6.answered_count', 0);
});

test('category analytics api returns 404 for inactive categories', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.analytics.categories.show', $category))
        ->assertNotFound();
});
