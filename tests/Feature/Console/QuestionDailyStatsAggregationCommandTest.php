<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;

test('question daily stats command aggregates a selected day', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();
    $user = User::factory()->create();
    $date = now()->subDay()->startOfDay();

    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'status' => 'completed',
            'started_at' => $date->copy()->addHour(),
            'completed_at' => $date->copy()->addHour()->addMinutes(3),
        ]);

    StudySessionAnswer::factory()
        ->for($session)
        ->for($question)
        ->create([
            'is_correct' => false,
            'response_time_ms' => 13500,
            'answered_at' => $date->copy()->addHour()->addMinute(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 0,
            'incorrect_count' => 2,
            'repetitions' => 0,
            'correct_streak' => 0,
            'last_quality' => 1,
            'last_answered_at' => $date->copy()->addHour()->addMinute(),
            'next_review_at' => $date->copy()->addDay()->toDateString(),
        ]);

    $this->artisan('ops:aggregate-question-daily-stats', [
        '--date' => $date->toDateString(),
    ])->assertExitCode(0);

    $this->assertDatabaseHas('question_daily_stats', [
        'stats_date' => $date->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'answers_count' => 1,
        'users_count' => 1,
        'incorrect_answers_count' => 1,
        'progress_users_count' => 1,
        'mastered_progress_users_count' => 0,
    ]);
});
