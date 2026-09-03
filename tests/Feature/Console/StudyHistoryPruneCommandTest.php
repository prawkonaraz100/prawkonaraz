<?php

use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewTrainerDailyPlanService;
use App\Support\StudySessionManager;

test('study history prune command removes old completed sessions and abandoned in progress sessions', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $oldCompletedSession = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $question->license_category_id,
        'status' => 'completed',
        'created_at' => now()->subDays(500),
        'started_at' => now()->subDays(500),
        'completed_at' => now()->subDays(500),
    ]);

    StudySessionAnswer::factory()->create([
        'study_session_id' => $oldCompletedSession->getKey(),
        'question_id' => $question->getKey(),
    ]);

    $oldInProgressSession = StudySession::factory()->inProgress()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $question->license_category_id,
        'created_at' => now()->subDays(20),
        'started_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
    ]);

    $recentCompletedSession = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $question->license_category_id,
        'status' => 'completed',
        'created_at' => now()->subDays(30),
        'started_at' => now()->subDays(30),
        'completed_at' => now()->subDays(30),
    ]);

    $this->artisan('ops:prune-study-history', [
        '--days' => 365,
        '--abandoned-days' => 7,
    ])
        ->expectsOutputToContain('Usunieto 1 zakonczonych sesji starszych niz 365 dni oraz 1 porzuconych sesji in_progress starszych niz 7 dni.')
        ->assertSuccessful();

    expect(StudySession::query()->whereKey($oldCompletedSession->getKey())->exists())->toBeFalse();
    expect(StudySession::query()->whereKey($oldInProgressSession->getKey())->exists())->toBeFalse();
    expect(StudySession::query()->whereKey($recentCompletedSession->getKey())->exists())->toBeTrue();
    expect(StudySessionAnswer::query()->where('study_session_id', $oldCompletedSession->getKey())->exists())->toBeFalse();
});

test('study history prune keeps verified memory state after raw review answers are removed', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $oldReviewSession = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $question->license_category_id,
        'mode' => StudySessionManager::MODE_SR_REVIEW,
        'status' => 'completed',
        'created_at' => now()->subDays(500),
        'started_at' => now()->subDays(500),
        'completed_at' => now()->subDays(500),
    ]);

    $answer = StudySessionAnswer::factory()->create([
        'study_session_id' => $oldReviewSession->getKey(),
        'question_id' => $question->getKey(),
        'answered_at' => now()->subDays(500),
    ]);

    $memoryProgress = ReviewMemoryProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $question->getKey(),
        'license_category_id' => $question->license_category_id,
        'verified_attempts_count' => 1,
        'verified_correct_count' => 1,
        'verified_correct_streak' => 1,
        'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
        'last_verified_at' => $answer->answered_at,
        'last_study_session_answer_id' => $answer->getKey(),
        'next_verified_review_at' => now()->subDays(499)->toDateString(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $question->license_category_id,
        'question_id' => $question->getKey(),
        'study_session_id' => $oldReviewSession->getKey(),
        'study_session_answer_id' => $answer->getKey(),
        'review_day' => $answer->answered_at?->toDateString(),
        'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
        'answer_kind' => 'choice',
        'is_correct' => true,
        'answered_at' => $answer->answered_at,
    ]);

    $this->artisan('ops:prune-study-history', [
        '--days' => 365,
        '--abandoned-days' => 7,
    ])->assertSuccessful();

    expect(StudySession::query()->whereKey($oldReviewSession->getKey())->exists())->toBeFalse()
        ->and(StudySessionAnswer::query()->whereKey($answer->getKey())->exists())->toBeFalse()
        ->and(ReviewTrainerDailyAnswer::query()->where('study_session_answer_id', $answer->getKey())->exists())->toBeFalse()
        ->and(ReviewMemoryProgress::query()->whereKey($memoryProgress->getKey())->exists())->toBeTrue()
        ->and($memoryProgress->refresh()->last_study_session_answer_id)->toBeNull()
        ->and($memoryProgress->verified_memory_state)->toBe(ReviewMemoryProgress::STATE_REVIEW);
});
