<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserProfile;

test('learning home api exposes the mobile study dashboard contract', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a', 'external_id' => 'LH-001'],
            ['correct_answer' => 'b', 'external_id' => 'LH-002'],
            ['correct_answer' => 'c', 'external_id' => 'LH-003'],
        )
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $studySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->inProgress()
        ->create([
            'mode' => 'learn',
            'started_at' => now()->subMinutes(8),
            'total_questions_count' => 3,
            'payload' => [
                'question_ids' => $questions->pluck('id')->all(),
                'current_index' => 1,
                'answered_count' => 1,
                'ui_shell' => 'zen',
                'filters' => [
                    'question_topic_id' => $topic->getKey(),
                    'question_scope' => 'all',
                    'question_status' => 'unanswered',
                    'randomize_order' => false,
                    'question_count' => 3,
                    'question_count_strategy' => 'fixed',
                ],
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($studySession, 'studySession')
        ->for($questions->first())
        ->create([
            'selected_answer' => 'a',
            'is_correct' => true,
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.learning-home', [
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'incorrect',
            'randomize_order' => true,
            'question_count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('data.schema_version', 1)
        ->assertJsonPath('data.category.code', 'B')
        ->assertJsonPath('data.category.short_name', 'B')
        ->assertJsonPath('data.category.questions_count', 3)
        ->assertJsonPath('data.filters.question_topic_id', $topic->getKey())
        ->assertJsonPath('data.filters.ui_shell', 'exam_like')
        ->assertJsonPath('data.filters.question_status', 'incorrect')
        ->assertJsonPath('data.filters.randomize_order', true)
        ->assertJsonPath('data.filters.question_count', 2)
        ->assertJsonPath('data.group_options.0.options.0.id', $topic->getKey())
        ->assertJsonPath('data.status_options.0.value', 'all')
        ->assertJsonPath('data.access.full_product.allowed', true)
        ->assertJsonPath('data.learning_dashboard.active_session.id', $studySession->getKey())
        ->assertJsonPath('data.learning_dashboard.active_session.title', 'Zen mode')
        ->assertJsonPath('data.learning_dashboard.active_session.progress.answered', 1)
        ->assertJsonPath('data.learning_dashboard.active_session.progress.remaining', 2)
        ->assertJsonPath('data.learning_dashboard.active_session.progress.current_question_number', 2)
        ->assertJsonPath('data.learning_dashboard.active_session.resume_url', '/nauka/teraz')
        ->assertJsonPath('data.modes.0.key', 'classic')
        ->assertJsonPath('data.modes.0.api_supported', true)
        ->assertJsonPath('data.modes.2.key', 'exam')
        ->assertJsonPath('data.modes.2.question_count', 32)
        ->assertJsonPath('data.actions.start_session.api_url', '/api/v1/sessions')
        ->assertJsonPath('data.actions.resume_session.api_url', '/api/v1/sessions/current')
        ->assertJsonPath('data.actions.answer_current_session.api_url', '/api/v1/sessions/current/answers')
        ->assertJsonPath('data.actions.complete_current_session.api_url', '/api/v1/sessions/current/complete');
});

test('learning home api returns a semantic access error before purchase', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.me.learning-home'))
        ->assertForbidden()
        ->assertJsonPath('error_code', 'LEARNING_ACCESS_REQUIRED')
        ->assertJsonPath('data.access.full_product.allowed', false)
        ->assertJsonPath('data.access.full_product.activation_url', '/aktywuj-dostep')
        ->assertJsonPath('data.access.pjm.allowed', false);
});
