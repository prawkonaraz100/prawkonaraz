<?php

use App\Models\ContentImportRun;
use App\Models\LicenseCategory;
use App\Models\MonitoringSnapshot;
use App\Models\Question;
use App\Models\QuestionDailyStat;
use App\Models\QuestionMonthlyStat;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserIpHistory;

test('monitoring snapshot command stores a cumulative snapshot for a selected day', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create();
    $user = User::factory()->create();
    $targetDate = now()->subDays(5)->startOfDay();

    $session = StudySession::factory()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'created_at' => $targetDate->copy()->addHour(),
        'started_at' => $targetDate->copy()->addHour(),
        'completed_at' => $targetDate->copy()->addHours(2),
        'status' => 'completed',
    ]);

    $answer = StudySessionAnswer::factory()->create([
        'study_session_id' => $session->getKey(),
        'question_id' => $question->getKey(),
        'created_at' => $targetDate->copy()->addHour()->addMinute(),
        'answered_at' => $targetDate->copy()->addHour()->addMinute(),
    ]);

    ReviewTrainerEvent::query()->create([
        'event_id' => 'review-event-test-1',
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'study_session_id' => $session->getKey(),
        'event_name' => 'review.session_started',
        'planner_version' => 'review-planner-v1',
        'payload' => ['source' => 'monitoring-test'],
        'occurred_at' => $targetDate->copy()->addHour(),
        'created_at' => $targetDate->copy()->addHour(),
        'updated_at' => $targetDate->copy()->addHour(),
    ]);

    ReviewMemoryProgress::factory()
        ->for($user, 'user')
        ->for($question, 'question')
        ->for($category, 'licenseCategory')
        ->create([
            'verified_attempts_count' => 1,
            'verified_correct_count' => 1,
            'created_at' => $targetDate->copy()->addHour(),
            'updated_at' => $targetDate->copy()->addHour(),
        ]);

    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'question_id' => $question->getKey(),
        'study_session_id' => $session->getKey(),
        'study_session_answer_id' => $answer->getKey(),
        'review_day' => $targetDate->toDateString(),
        'daily_plan_policy_version' => 'review-daily-plan-v1',
        'answer_kind' => 'choice',
        'is_correct' => true,
        'answered_at' => $targetDate->copy()->addHour()->addMinute(),
        'created_at' => $targetDate->copy()->addHour()->addMinute(),
        'updated_at' => $targetDate->copy()->addHour()->addMinute(),
    ]);

    QuestionDailyStat::query()->create([
        'stats_date' => $targetDate->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $question->question_topic_id,
        'answers_count' => 1,
        'users_count' => 1,
        'correct_answers_count' => 1,
        'incorrect_answers_count' => 0,
        'response_time_count' => 1,
        'response_time_avg_ms' => 12000,
        'response_time_median_ms' => 12000,
        'progress_users_count' => 0,
        'mastered_progress_users_count' => 0,
        'avg_total_attempts' => 0,
    ]);

    QuestionMonthlyStat::query()->create([
        'stats_month' => $targetDate->copy()->startOfMonth()->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $question->question_topic_id,
        'days_covered_count' => 1,
        'answers_count' => 1,
        'user_days_count' => 1,
        'correct_answers_count' => 1,
        'incorrect_answers_count' => 0,
        'response_time_count' => 1,
        'response_time_avg_ms' => 12000,
        'progress_user_days_count' => 0,
        'mastered_progress_user_days_count' => 0,
        'avg_total_attempts' => 0,
    ]);

    UserIpHistory::query()->create([
        'user_id' => $user->getKey(),
        'ip_address' => '8.8.8.8',
        'source' => 'session',
        'path' => '/nauka/teraz',
        'first_seen_at' => $targetDate->copy()->addHour(),
        'last_seen_at' => $targetDate->copy()->addHour(),
    ]);

    ContentImportRun::query()->create([
        'kind' => 'manifest_import',
        'identifier' => 'import-test',
        'status' => 'completed',
        'dry_run' => false,
        'started_at' => $targetDate->copy()->addHour(),
        'completed_at' => $targetDate->copy()->addHours(2),
    ]);

    $this->artisan('ops:capture-monitoring-snapshot', [
        '--date' => $targetDate->toDateString(),
    ])
        ->expectsOutputToContain('Zapisano snapshot monitoringu')
        ->assertSuccessful();

    $snapshot = MonitoringSnapshot::query()->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot?->snapshot_date?->toDateString())->toBe($targetDate->toDateString());
    expect($snapshot?->study_sessions_count)->toBe(1);
    expect($snapshot?->study_session_answers_count)->toBe(1);
    expect($snapshot?->question_daily_stats_count)->toBe(1);
    expect($snapshot?->question_monthly_stats_count)->toBe(1);
    expect($snapshot?->user_ip_histories_count)->toBe(1);
    expect($snapshot?->content_import_runs_count)->toBe(1);
    expect($snapshot?->review_trainer_events_count)->toBe(1);
    expect($snapshot?->review_memory_progress_count)->toBe(1);
    expect($snapshot?->review_trainer_daily_answers_count)->toBe(1);
    expect($snapshot?->database_volume_total_bytes)->toBeNull();
    expect($snapshot?->database_volume_free_bytes)->toBeNull();
    expect($snapshot?->database_latency_ms)->toBeNull();
    expect(MonitoringSnapshot::query()->count())->toBe(1);
});

test('monitoring snapshot command stores current infrastructure telemetry for today', function () {
    $targetDate = now()->startOfDay();

    $this->artisan('ops:capture-monitoring-snapshot', [
        '--date' => $targetDate->toDateString(),
    ])
        ->expectsOutputToContain('Zapisano snapshot monitoringu')
        ->assertSuccessful();

    $snapshot = MonitoringSnapshot::query()->whereDate('snapshot_date', $targetDate->toDateString())->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot?->database_size_bytes)->not->toBeNull();
    expect($snapshot?->database_volume_total_bytes)->not->toBeNull();
    expect($snapshot?->database_volume_free_bytes)->not->toBeNull();
    expect($snapshot?->database_latency_ms)->not->toBeNull();
    expect($snapshot?->database_volume_total_bytes)->toBeGreaterThan(0);
    expect($snapshot?->database_volume_free_bytes)->toBeGreaterThan(0);
    expect($snapshot?->database_latency_ms)->toBeGreaterThanOrEqual(0);
});
