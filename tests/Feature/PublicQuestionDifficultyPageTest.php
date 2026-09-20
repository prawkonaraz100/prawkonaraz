<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;

test('public hardest questions hub renders raw seo html and aggregated ranking', function () {
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

    $response = $this->get(route('public.hardest-questions.index'));

    $response
        ->assertOk()
        ->assertSee('<title>Najtrudniejsze pytania na prawo jazdy</title>', false)
        ->assertSee(
            '<meta name="description" content="Publiczny ranking pytań, które realnie sprawiają kursantom największą trudność.">',
            false,
        )
        ->assertSee(
            '<link rel="canonical" href="'.route('public.hardest-questions.index').'">',
            false,
        )
        ->assertSee('<h1', false)
        ->assertSeeText('Najtrudniejsze pytania na prawo jazdy')
        ->assertSeeText('Publiczny ranking pytań, które realnie sprawiają kursantom największą trudność.')
        ->assertSeeText('Czy przed przejsciem dla pieszych musisz zachowac szczegolna ostroznosc?')
        ->assertSee('href="/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/b"', false)
        ->assertSee('href="/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/c"', false)
        ->assertSee('href="/najtrudniejsze-pytania-na-prawo-jazdy?ranking=first_try"', false);
});

test('public hardest questions category page renders raw seo html for selected ranking', function () {
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

    $canonical = route('public.hardest-questions.categories.show', ['categorySlug' => $category->slug]);

    $this->get($canonical.'?ranking=first_try')
        ->assertOk()
        ->assertSee('<title>Najtrudniejsze pytania na prawo jazdy kategorii C</title>', false)
        ->assertSee(
            '<meta name="description" content="Publiczny ranking pytań, które sprawiają największą trudność kursantom kategorii C.">',
            false,
        )
        ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
        ->assertSeeText('Najtrudniejsze pytania na prawo jazdy kategorii C')
        ->assertSeeText('Najczęściej mylone na starcie')
        ->assertSeeText('Czy kierowca pojazdu ciezarowego powinien utrzymac bezpieczny odstep?')
        ->assertSee(
            'href="/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/c?ranking=repeat_fail"',
            false,
        );
});

