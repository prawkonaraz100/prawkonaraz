<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionProgress;

test('answer recording clamps interval days to prevent database overflow', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->create([
            'easiness_factor' => 2.5,
            'interval_days' => 65000,
            'repetitions' => 8,
            'next_review_at' => today()->addDays(65000),
            'last_quality' => 5,
            'total_attempts' => 8,
            'correct_count' => 8,
            'incorrect_count' => 0,
            'correct_streak' => 8,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertOk();

    $progress = UserQuestionProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect($progress->interval_days)->toBe(32767)
        ->and($progress->repetitions)->toBe(9)
        ->and($progress->next_review_at)->not->toBeNull();
});
