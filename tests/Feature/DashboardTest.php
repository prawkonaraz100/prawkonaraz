<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;

test('dashboard route redirects users without access to activation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('access.activate'));
});

test('dashboard route redirects users with active access to nauka', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('session.index'));
});

test('dashboard api uses question progress for readiness score and exposes hard question counts', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $solidQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $hardQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 5,
        ]);

    $studySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'score_percent' => 33,
            'created_at' => now(),
        ]);

    StudySessionAnswer::factory()
        ->for($studySession)
        ->for($solidQuestion, 'question')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($solidQuestion, 'question')
        ->create([
            'total_attempts' => 5,
            'correct_count' => 4,
            'incorrect_count' => 1,
            'correct_streak' => 2,
            'last_quality' => 4,
            'next_review_at' => today()->addDay(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($hardQuestion, 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 0,
            'incorrect_count' => 2,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.dashboard'))
        ->assertOk()
        ->assertJsonPath('data.sessions_today', 1)
        ->assertJsonPath('data.answered_today', 1)
        ->assertJsonPath('data.classic_sessions_today', 1)
        ->assertJsonPath('data.classic_answered_today', 1)
        ->assertJsonPath('data.memory_trainer_sessions_today', 0)
        ->assertJsonPath('data.memory_trainer_answered_today', 0)
        ->assertJsonPath('data.ready_for_review_count', 1)
        ->assertJsonPath('data.hard_questions_count', 1)
        ->assertJsonPath('data.readiness_score', 40.5)
        ->assertJsonPath('data.study_streak', 1)
        ->assertJsonPath('meta.categories.0.code', 'B')
        ->assertJsonPath('meta.categories.0.hard_questions_count', 1);
});

test('dashboard api exposes memory trainer activity separately from classic activity', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
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
            'created_at' => now(),
        ]);

    StudySessionAnswer::factory()
        ->for($memorySession)
        ->for($question, 'question')
        ->create([
            'created_at' => now(),
            'answered_at' => now(),
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.dashboard'))
        ->assertOk()
        ->assertJsonPath('data.sessions_today', 1)
        ->assertJsonPath('data.answered_today', 1)
        ->assertJsonPath('data.classic_sessions_today', 0)
        ->assertJsonPath('data.classic_answered_today', 0)
        ->assertJsonPath('data.memory_trainer_sessions_today', 1)
        ->assertJsonPath('data.memory_trainer_answered_today', 1)
        ->assertJsonPath('data.ready_for_review_count', 0)
        ->assertJsonPath('data.readiness_score', 0);
});

test('legacy moje postepy path redirects authenticated users to nauka', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $this->actingAs($user)
        ->get('/moje-postepy')
        ->assertRedirect(route('session.index'));
});
