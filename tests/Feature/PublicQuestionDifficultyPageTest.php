<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Inertia\Testing\AssertableInertia as Assert;

test('public hardest questions hub renders aggregated ranking', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $otherCategory = LicenseCategory::factory()->categoryC()->create([
        'name' => 'Kategoria C',
        'sort_order' => 2,
    ]);

    Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create([
            'external_id' => 'C-001',
            'prompt' => 'Czy pojazd ciezarowy moze zawrocic w tej sytuacji?',
        ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-900',
            'prompt' => 'Czy przed przejsciem dla pieszych musisz zachowac szczegolna ostroznosc?',
            'difficulty' => 4,
            'points' => 3,
        ]);

    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();

    $sessionOne = StudySession::factory()
        ->for($userOne)
        ->for($category, 'licenseCategory')
        ->create();
    $sessionOneFollowUp = StudySession::factory()
        ->for($userOne)
        ->for($category, 'licenseCategory')
        ->create();
    $sessionTwo = StudySession::factory()
        ->for($userTwo)
        ->for($category, 'licenseCategory')
        ->create();

    StudySessionAnswer::factory()->for($sessionOne)->for($question)->create([
        'is_correct' => false,
        'response_time_ms' => 17000,
        'answered_at' => now()->subDays(2),
    ]);
    StudySessionAnswer::factory()->for($sessionOneFollowUp)->for($question)->create([
        'is_correct' => false,
        'response_time_ms' => 14000,
        'answered_at' => now()->subDays(1),
    ]);
    StudySessionAnswer::factory()->for($sessionTwo)->for($question)->create([
        'is_correct' => true,
        'response_time_ms' => 12000,
        'answered_at' => now()->subDay(),
    ]);

    UserQuestionProgress::factory()->for($userOne)->for($question, 'question')->create([
        'total_attempts' => 2,
        'correct_count' => 0,
        'incorrect_count' => 2,
        'repetitions' => 0,
        'correct_streak' => 0,
        'last_quality' => 1,
        'next_review_at' => today(),
    ]);

    UserQuestionProgress::factory()->for($userTwo)->for($question, 'question')->create([
        'total_attempts' => 3,
        'correct_count' => 3,
        'incorrect_count' => 0,
        'repetitions' => 3,
        'correct_streak' => 3,
        'last_quality' => 5,
        'next_review_at' => today()->addDays(3),
    ]);

    $this->get(route('public.hardest-questions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HardestQuestions/Index')
            ->where('page.title', 'Najtrudniejsze pytania na prawo jazdy')
            ->where('summary.questions_analyzed', 1)
            ->where('summary.answers_count', 3)
            ->where('summary.users_count', 2)
            ->where('summary.window_label', 'Ostatnie 365 dni')
            ->where('top_questions.0.external_id', 'B-900')
            ->where('top_questions.0.category.code', 'B')
            ->where('featured_question.external_id', 'B-900')
            ->has('categories', 2)
        );
});

test('public hardest questions category page resolves category by slug', function () {
    $category = LicenseCategory::factory()->categoryC()->create([
        'name' => 'Kategoria C',
        'sort_order' => 1,
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'C-101',
            'prompt' => 'Czy kierowca pojazdu ciezarowego powinien utrzymac bezpieczny odstep?',
        ]);

    $user = User::factory()->create();
    $session = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create();

    StudySessionAnswer::factory()->for($session)->for($question)->create([
        'is_correct' => false,
        'response_time_ms' => 21000,
        'answered_at' => now()->subHours(4),
    ]);

    UserQuestionProgress::factory()->for($user)->for($question, 'question')->create([
        'total_attempts' => 1,
        'correct_count' => 0,
        'incorrect_count' => 1,
        'repetitions' => 0,
        'correct_streak' => 0,
        'last_quality' => 1,
        'next_review_at' => today(),
    ]);

    $this->get(route('public.hardest-questions.categories.show', ['categorySlug' => $category->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HardestQuestions/Index')
            ->where('selected_category.code', 'C')
            ->where('selected_category.slug', 'c')
            ->where('summary.questions_analyzed', 1)
            ->where('summary.window_label', 'Ostatnie 365 dni')
            ->where('top_questions.0.external_id', 'C-101')
        );
});
