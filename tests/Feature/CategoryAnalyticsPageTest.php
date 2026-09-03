<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Inertia\Testing\AssertableInertia as Assert;

test('users can open a category analytics page', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'description' => 'Przygotowanie do egzaminu kategorii B.',
        'sort_order' => 1,
    ]);
    $otherCategory = LicenseCategory::factory()->create([
        'code' => 'A',
        'name' => 'Kategoria A',
        'sort_order' => 2,
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-301',
            'prompt' => 'Czy musisz zachowac szczegolna ostroznosc przed przejsciem dla pieszych?',
            'difficulty' => 4,
            'points' => 3,
            'question_type' => 'single_choice',
        ]);

    Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->create([
            'total_attempts' => 3,
            'correct_count' => 1,
            'incorrect_count' => 2,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
        ]);

    $this->actingAs($user)
        ->get(route('analytics.categories.show', $category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics/Categories/Show')
            ->where('category.id', $category->getKey())
            ->where('category.code', 'B')
            ->where('filters.category', $category->getKey())
            ->has('categories', 2)
            ->where('summary.questions_total', 1)
            ->where('summary.tracked_questions_count', 1)
            ->where('summary.ready_for_review_count', 1)
            ->where('summary.hard_questions_count', 1)
            ->where('weak_spots.0.external_id', 'B-301')
        );
});

test('inactive categories return 404 on category analytics page', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('analytics.categories.show', $category))
        ->assertNotFound();
});
