<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionDailyStat;
use App\Models\QuestionMonthlyStat;

test('question monthly rollup command compacts old daily stats into monthly archive rows', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $oldMonth = now()->subMonths(14)->startOfMonth();
    $recentDay = now()->subMonths(2)->startOfMonth()->addDay();

    QuestionDailyStat::query()->create([
        'stats_date' => $oldMonth->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $question->question_topic_id,
        'answers_count' => 2,
        'users_count' => 1,
        'correct_answers_count' => 1,
        'incorrect_answers_count' => 1,
        'response_time_count' => 2,
        'response_time_avg_ms' => 10000,
        'response_time_median_ms' => 10000,
        'progress_users_count' => 1,
        'mastered_progress_users_count' => 0,
        'avg_total_attempts' => 2.0,
    ]);

    QuestionDailyStat::query()->create([
        'stats_date' => $oldMonth->copy()->addDay()->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $question->question_topic_id,
        'answers_count' => 4,
        'users_count' => 2,
        'correct_answers_count' => 3,
        'incorrect_answers_count' => 1,
        'response_time_count' => 4,
        'response_time_avg_ms' => 14000,
        'response_time_median_ms' => 14000,
        'progress_users_count' => 2,
        'mastered_progress_users_count' => 1,
        'avg_total_attempts' => 4.0,
    ]);

    QuestionDailyStat::query()->create([
        'stats_date' => $recentDay->toDateString(),
        'question_id' => $question->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $question->question_topic_id,
        'answers_count' => 3,
        'users_count' => 1,
        'correct_answers_count' => 2,
        'incorrect_answers_count' => 1,
        'response_time_count' => 3,
        'response_time_avg_ms' => 11000,
        'response_time_median_ms' => 11000,
        'progress_users_count' => 1,
        'mastered_progress_users_count' => 0,
        'avg_total_attempts' => 3.0,
    ]);

    $cutoffDate = now()->subDays(365)->toDateString();

    $this->artisan('ops:rollup-question-monthly-stats', [
        '--before' => $cutoffDate,
    ])
        ->expectsOutputToContain('Zwinięto 1 miesięcy')
        ->assertSuccessful();

    $this->assertDatabaseHas('question_monthly_stats', [
        'stats_month' => $oldMonth->toDateString(),
        'question_id' => $question->getKey(),
        'answers_count' => 6,
        'user_days_count' => 3,
        'correct_answers_count' => 4,
        'incorrect_answers_count' => 2,
        'response_time_count' => 6,
        'progress_user_days_count' => 3,
        'mastered_progress_user_days_count' => 1,
    ]);

    $monthlyRow = QuestionMonthlyStat::query()
        ->whereDate('stats_month', $oldMonth->toDateString())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect((int) $monthlyRow->days_covered_count)->toBe(2);
    expect((int) $monthlyRow->response_time_avg_ms)->toBe(12667);
    expect((float) $monthlyRow->avg_total_attempts)->toBe(3.33);

    expect(
        QuestionDailyStat::query()
            ->whereDate('stats_date', $oldMonth->toDateString())
            ->exists()
    )->toBeFalse();

    expect(
        QuestionDailyStat::query()
            ->whereDate('stats_date', $oldMonth->copy()->addDay()->toDateString())
            ->exists()
    )->toBeFalse();

    expect(
        QuestionDailyStat::query()
            ->whereDate('stats_date', $recentDay->toDateString())
            ->exists()
    )->toBeTrue();
});
