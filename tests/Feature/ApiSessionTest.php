<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use App\Models\QuestionExplanationAsset;
use App\Models\QuestionMedia;
use App\Models\QuestionTopic;
use App\Models\ReviewMemoryProgress;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\ReviewTrainerDailyPlanService;
use App\Support\StudySessionAnswerKind;
use Illuminate\Support\Str;

test('public api exposes health and active categories', function () {
    $activeCategory = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    LicenseCategory::factory()->create([
        'code' => 'A',
        'name' => 'Kategoria A',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    Question::factory()
        ->count(2)
        ->for($activeCategory, 'licenseCategory')
        ->create();

    $this->getJson(route('api.v1.health'))
        ->assertOk()
        ->assertJsonPath('data.status', 'ok');

    $this->getJson(route('api.v1.categories.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'B')
        ->assertJsonPath('data.0.short_name', 'B')
        ->assertJsonPath('data.0.questions_count', 2);

    $this->getJson(route('api.v1.categories.show', $activeCategory))
        ->assertOk()
        ->assertJsonPath('data.code', 'B')
        ->assertJsonPath('data.short_name', 'B')
        ->assertJsonPath('data.questions_count', 2);
});

test('authenticated users can create and inspect a learn session through api', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'published_at' => now()->subDays(3), 'external_id' => 'B-001'],
            ['difficulty' => 2, 'published_at' => now()->subDays(2), 'external_id' => 'B-002'],
            ['difficulty' => 3, 'published_at' => now()->subDay(), 'external_id' => 'B-003'],
        )
        ->create();

    QuestionMedia::factory()
        ->for($questions->first())
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/b-001.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'metadata' => [
                'asset_group' => 'question-b-001-image',
                'source_role' => 'full',
            ],
        ]);

    QuestionMedia::factory()
        ->for($questions->first())
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/b-001-thumb.webp',
            'mime_type' => 'image/webp',
            'variant' => 'thumb',
            'sort_order' => 1,
            'metadata' => [
                'asset_group' => 'question-b-001-image',
                'source_role' => 'thumb',
            ],
        ]);
    $questions->first()->questionTopic()->associate(QuestionTopic::query()->create([
        'key' => 'vehicle_operation_and_safety',
        'name' => 'Obsluga pojazdu i bezpieczenstwo jazdy',
        'sort_order' => 100,
    ]))->save();

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.session.category_id', $category->getKey())
        ->assertJsonPath('data.session.mode', 'learn')
        ->assertJsonPath('data.session.status', 'in_progress')
        ->assertJsonPath('data.session.total_questions', 3)
        ->assertJsonPath('data.session.answered_questions', 0)
        ->assertJsonPath('data.category.code', 'B')
        ->assertJsonPath('data.questions.0.external_id', 'B-001')
        ->assertJsonPath('data.questions.0.topic.key', 'vehicle_operation_and_safety')
        ->assertJsonPath('data.questions.0.media.0.url', 'https://media.example.test/questions/b/b-001.webp')
        ->assertJsonPath('data.questions.0.media.0.full_url', 'https://media.example.test/questions/b/b-001.webp')
        ->assertJsonPath('data.questions.0.media.0.thumb_url', 'https://media.example.test/questions/b/b-001-thumb.webp')
        ->assertJsonMissingPath('data.questions.0.correct_answer');

    $sessionId = $response->json('data.session.id');

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.show', $sessionId))
        ->assertOk()
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.progress.answered', 0)
        ->assertJsonPath('data.progress.remaining', 3)
        ->assertJsonPath('data.current_question.external_id', 'B-001')
        ->assertJsonPath('data.current_question.question_text', $questions->first()->prompt)
        ->assertJsonPath('data.current_question.topic.key', 'vehicle_operation_and_safety')
        ->assertJsonPath('data.questions.0.is_answered', false)
        ->assertJsonMissingPath('data.current_question.correct_answer');
});

test('exam session api does not reveal correct answers before completion', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ]);

    $response->assertCreated();

    $sessionId = $response->json('data.session.id');

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.show', $sessionId))
        ->assertOk()
        ->assertJsonMissingPath('data.current_question.correct_answer')
        ->assertJsonMissingPath('data.questions.0.correct_answer');
});

test('exam state api syncs the current exam question phase', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
            'points' => 3,
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
            'points' => 2,
        ]);

    $sessionId = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertCreated()
        ->json('data.session.id');

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.current.exam.state'), [
            'action' => 'start-answer',
        ])
        ->assertOk()
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.session.mode', 'exam')
        ->assertJsonPath('data.session.status', 'in_progress')
        ->assertJsonPath('data.category.code', 'B')
        ->assertJsonPath('data.progress.answered', 0)
        ->assertJsonPath('data.progress.remaining', 32)
        ->assertJsonPath('data.progress.total', 32)
        ->assertJsonPath('data.progress.current_question_number', 1)
        ->assertJsonPath('data.exam_ui.duration_seconds', 1500)
        ->assertJsonPath('data.exam_ui.pass_threshold', 68)
        ->assertJsonPath('data.exam_ui.max_points', 74)
        ->assertJsonPath('data.exam_ui.basic.total', 20)
        ->assertJsonPath('data.exam_ui.specialist.total', 12)
        ->assertJsonPath('data.exam_ui.phase', 'answer')
        ->assertJsonPath('data.exam_ui.current_scope', 'PODSTAWOWY')
        ->assertJsonPath('data.current_question_number', 1)
        ->assertJsonPath('data.current_question.structure_scope', 'PODSTAWOWY')
        ->assertJsonPath('data.current_question.explanation_asset', null)
        ->assertJsonPath('data.current_question.explanation_annotations', [])
        ->assertJsonMissingPath('data.current_question.correct_answer')
        ->assertJsonMissingPath('data.current_question.explanation')
        ->assertJsonPath('data.completed', false)
        ->assertJsonPath('data.redirect', '/nauka/teraz');
});

test('learn session api uses the full matching topic set instead of an exam-style cap', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(5)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertCreated()
        ->assertJsonPath('data.session.total_questions', 5)
        ->assertJsonCount(5, 'data.questions');
});

test('mobile api exposes current session and accepts web session filters', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $topicQuestions = Question::factory()
        ->count(4)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a', 'external_id' => 'B-T-001'],
            ['correct_answer' => 'b', 'external_id' => 'B-T-002'],
            ['correct_answer' => 'c', 'external_id' => 'B-T-003'],
            ['correct_answer' => 'a', 'external_id' => 'B-T-004'],
        )
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.current'))
        ->assertOk()
        ->assertJsonPath('data.session', null)
        ->assertJsonPath('data.current_question', null)
        ->assertJsonPath('data.questions', []);

    $createResponse = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'exam_like',
            'question_topic_id' => $topic->getKey(),
            'question_scope' => 'all',
            'question_status' => 'all',
            'randomize_order' => true,
            'question_count' => 3,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.session.category_id', $category->getKey())
        ->assertJsonPath('data.session.ui_shell', 'exam_like')
        ->assertJsonPath('data.session.filters.question_topic_id', $topic->getKey())
        ->assertJsonPath('data.session.filters.question_scope', 'all')
        ->assertJsonPath('data.session.filters.question_status', 'all')
        ->assertJsonPath('data.session.filters.randomize_order', true)
        ->assertJsonPath('data.session.total_questions', 4)
        ->assertJsonPath('data.category.short_name', 'B')
        ->assertJsonCount(4, 'data.questions');

    $sessionId = $createResponse->json('data.session.id');
    $questionIds = collect($createResponse->json('data.questions'))
        ->pluck('id')
        ->values();

    expect($questionIds->diff($topicQuestions->pluck('id'))->all())->toBe([]);

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 2,
            'replace_active_session' => false,
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ACTIVE_SESSION_EXISTS')
        ->assertJsonPath('data.active_session.id', $sessionId);

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.current'))
        ->assertOk()
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.session.ui_shell', 'exam_like')
        ->assertJsonPath('data.progress.total', 4)
        ->assertJsonPath('data.progress.answered', 0)
        ->assertJsonPath('data.progress.current_question_number', 1)
        ->assertJsonPath('data.current_question.topic.id', $topic->getKey());

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.current.questions.index', [
            'ids' => $questionIds->take(2)->all(),
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data.questions')
        ->assertJsonPath('data.questions.0.topic.id', $topic->getKey())
        ->assertJsonMissingPath('data.questions.0.correct_answer');

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.current.questions.show', $questionIds->first()))
        ->assertOk()
        ->assertJsonPath('data.question.id', $questionIds->first())
        ->assertJsonMissingPath('data.question.correct_answer');

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.current.answers.store'), [
            'question_id' => $questionIds->first(),
            'selected_answer' => 'A',
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true)
        ->assertJsonPath('data.question_id', $questionIds->first())
        ->assertJsonPath('data.answered_questions', 1)
        ->assertJsonPath('data.session_status', 'in_progress');

    $legacyConflictQuestionId = $questionIds->skip(1)->first();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.current.answers.store'), [
            'question_id' => $legacyConflictQuestionId,
            'selected_answer' => 'B',
            'user_answer' => 'C',
            'response_time_ms' => 1100,
        ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true)
        ->assertJsonPath('data.question_id', $legacyConflictQuestionId)
        ->assertJsonPath('data.answered_questions', 2)
        ->assertJsonPath('data.session_status', 'in_progress');

    expect(
        StudySession::query()
            ->findOrFail($sessionId)
            ->answers()
            ->where('question_id', $legacyConflictQuestionId)
            ->value('selected_answer')
    )->toBe('b');

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.current.complete'))
        ->assertOk()
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.session.status', 'completed')
        ->assertJsonPath('data.progress.answered', 2)
        ->assertJsonPath('data.current_question', null)
        ->assertJsonPath('data.questions.0.correct_answer', Str::upper(
            Question::query()->findOrFail($questionIds->first())->correct_answer,
        ));
});

test('users can answer and complete a session through api', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'published_at' => now()->subDays(3), 'external_id' => 'B-010', 'correct_answer' => 'a'],
            ['difficulty' => 2, 'published_at' => now()->subDays(2), 'external_id' => 'B-011', 'correct_answer' => 'b'],
            ['difficulty' => 3, 'published_at' => now()->subDay(), 'external_id' => 'B-012', 'correct_answer' => 'c'],
        )
        ->create();

    $createResponse = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertCreated();

    $sessionId = $createResponse->json('data.session.id');
    $orderedQuestions = $questions->sortBy('difficulty')->values();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.answers.store', $sessionId), [
            'question_id' => $orderedQuestions[0]->getKey(),
            'user_answer' => 'A',
            'response_time_ms' => 1400,
        ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true)
        ->assertJsonPath('data.question_id', $orderedQuestions[0]->getKey())
        ->assertJsonPath('data.answer_kind', StudySessionAnswerKind::CHOICE)
        ->assertJsonPath('data.is_correct', true)
        ->assertJsonPath('data.answered_questions', 1)
        ->assertJsonPath('data.session_status', 'in_progress')
        ->assertJsonPath('data.answer.question_id', $orderedQuestions[0]->getKey())
        ->assertJsonPath('data.answer.selected_answer', 'A')
        ->assertJsonPath('data.answer.correct_answer', 'A')
        ->assertJsonPath('data.answer.correct_answer_text', $orderedQuestions[0]->option_a)
        ->assertJsonPath('data.answer.explanation', $orderedQuestions[0]->explanation)
        ->assertJsonPath('data.correct_answer', 'A')
        ->assertJsonPath('data.correct_answer_text', $orderedQuestions[0]->option_a)
        ->assertJsonPath('data.explanation', $orderedQuestions[0]->explanation)
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.session.status', 'in_progress')
        ->assertJsonPath('data.progress.answered', 1)
        ->assertJsonPath('data.progress.remaining', 2)
        ->assertJsonPath('data.progress.current_question_number', 2)
        ->assertJsonPath('data.completed', false)
        ->assertJsonPath('data.next_question.id', $orderedQuestions[1]->getKey())
        ->assertJsonMissingPath('data.next_question.correct_answer')
        ->assertJsonMissingPath('data.next_question.explanation')
        ->assertJsonPath('data.next_question.explanation_asset', null)
        ->assertJsonPath('data.next_question_number', 2);

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.answers.store', $sessionId), [
            'question_id' => $orderedQuestions[0]->getKey(),
            'user_answer' => 'A',
            'response_time_ms' => 1400,
        ])
        ->assertOk()
        ->assertJsonPath('data.accepted', false)
        ->assertJsonPath('data.answered_questions', 1)
        ->assertJsonPath('data.answer.correct_answer', 'A')
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.progress.answered', 1);

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.complete', $sessionId))
        ->assertOk()
        ->assertJsonPath('data.session.id', $sessionId)
        ->assertJsonPath('data.session.status', 'completed')
        ->assertJsonPath('data.session.answered_questions', 1)
        ->assertJsonPath('data.current_question', null)
        ->assertJsonPath('data.questions.0.correct_answer', 'A')
        ->assertJsonPath('data.questions.0.selected_answer', 'A')
        ->assertJsonPath('data.questions.0.answer_kind', StudySessionAnswerKind::CHOICE)
        ->assertJsonPath('data.questions.0.is_correct', true);

    $session = StudySession::query()->findOrFail($sessionId);

    expect($session->status)->toBe('completed');
    expect((float) $session->score_percent)->toBe(33.33);
});

test('api accepts sr review mode and normalizes legacy review alias', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 4,
        ]);

    $secondDueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 5,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 2,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($secondDueQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 4,
            'next_review_at' => today()->subDay(),
        ]);

    $aliasResponse = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'review',
            'question_count' => 10,
        ]);

    $aliasResponse->assertCreated()
        ->assertJsonPath('data.session.mode', 'sr_review')
        ->assertJsonPath('data.session.review_plan.daily_plan_policy_version', ReviewTrainerDailyPlanService::VERSION)
        ->assertJsonPath('data.session.review_plan.review_day', today()->toDateString())
        ->assertJsonPath('data.session.review_plan.daily_target_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->assertJsonPath('data.session.review_plan.minimum_session_question_count', ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->assertJsonPath('data.session.review_plan.completed_today_count', 0)
        ->assertJsonPath('data.session.review_plan.daily_remaining_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->assertJsonPath('data.session.review_plan.candidate_count', 2)
        ->assertJsonPath('data.session.review_plan.booster_count', 0)
        ->assertJsonPath('data.session.review_plan.new_candidate_count', 0)
        ->assertJsonPath('data.session.review_plan.candidate_source_counts.primary', 2)
        ->assertJsonPath('data.session.review_plan.candidate_source_counts.seen_booster', 0)
        ->assertJsonPath('data.session.review_plan.candidate_source_counts.new_candidate', 0)
        ->assertJsonPath('data.session.review_plan.selected_question_count', 2)
        ->assertJsonCount(2, 'data.questions');
    $aliasResponse->assertJsonMissingPath('data.questions.0.correct_answer')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonPath('data.questions.0.explanation_asset', null)
        ->assertJsonPath('data.questions.0.explanation_annotations', []);

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.show', $aliasResponse->json('data.session.id')))
        ->assertOk()
        ->assertJsonMissingPath('data.current_question.correct_answer')
        ->assertJsonMissingPath('data.current_question.explanation')
        ->assertJsonPath('data.current_question.explanation_asset', null)
        ->assertJsonPath('data.current_question.explanation_annotations', [])
        ->assertJsonMissingPath('data.questions.0.correct_answer')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonPath('data.questions.0.explanation_asset', null)
        ->assertJsonPath('data.questions.0.explanation_annotations', []);

    $canonicalResponse = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 10,
        ]);

    $canonicalResponse->assertCreated()
        ->assertJsonPath('data.session.mode', 'sr_review')
        ->assertJsonPath('data.session.review_plan.review_day', today()->toDateString())
        ->assertJsonMissingPath('data.questions.0.correct_answer')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonPath('data.questions.0.explanation_asset', null)
        ->assertJsonPath('data.questions.0.explanation_annotations', [])
        ->assertJsonCount(2, 'data.questions');

    $canonicalSessionId = $canonicalResponse->json('data.session.id');
    $canonicalQuestionIds = collect($canonicalResponse->json('data.questions'))
        ->pluck('id')
        ->all();
    $lastQuestion = Question::query()->findOrFail($canonicalQuestionIds[1]);

    QuestionExplanationAsset::factory()
        ->for($lastQuestion, 'question')
        ->create([
            'file_path' => 'question-explanations/api/review-reveal.webp',
            'title' => 'API reveal asset',
            'body' => 'API powinno zwrócić ten blok dopiero po odpowiedzi.',
            'alt_text' => 'API reveal',
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($lastQuestion, 'question')
        ->label('API reveal annotation')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.answers.store', $canonicalSessionId), [
            'question_id' => $lastQuestion->getKey(),
            'user_answer' => $lastQuestion->correct_answer,
        ])
        ->assertOk()
        ->assertJsonPath('data.answer.correct_answer', Str::upper($lastQuestion->correct_answer))
        ->assertJsonPath('data.answer.correct_answer_text', match ($lastQuestion->correct_answer) {
            'a' => $lastQuestion->option_a,
            'b' => $lastQuestion->option_b,
            default => $lastQuestion->option_c,
        })
        ->assertJsonPath('data.correct_answer', Str::upper($lastQuestion->correct_answer))
        ->assertJsonPath('data.correct_answer_text', match ($lastQuestion->correct_answer) {
            'a' => $lastQuestion->option_a,
            'b' => $lastQuestion->option_b,
            default => $lastQuestion->option_c,
        })
        ->assertJsonPath('data.explanation', $lastQuestion->explanation)
        ->assertJsonPath('data.answer.explanation', $lastQuestion->explanation)
        ->assertJsonPath('data.explanation_asset.title', 'API reveal asset')
        ->assertJsonPath('data.answer.explanation_asset.title', 'API reveal asset')
        ->assertJsonPath('data.explanation_asset.image_url', 'https://media.example.test/question-explanations/api/review-reveal.webp')
        ->assertJsonPath('data.explanation_annotations.0.annotation_type', 'label')
        ->assertJsonPath('data.answer.explanation_annotations.0.annotation_type', 'label')
        ->assertJsonPath('data.explanation_annotations.0.label', 'API reveal annotation')
        ->assertJsonPath('data.next_question.id', $canonicalQuestionIds[0])
        ->assertJsonMissingPath('data.next_question.correct_answer')
        ->assertJsonMissingPath('data.next_question.explanation')
        ->assertJsonPath('data.next_question.explanation_asset', null)
        ->assertJsonPath('data.next_question.explanation_annotations', [])
        ->assertJsonPath('data.next_question_number', 1);
});

test('api memory trainer start rejects hidden categories outside public study context', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $hiddenCategory = LicenseCategory::factory()->create([
        'code' => 'INTERNAL',
        'name' => 'Internal test category',
        'is_active' => true,
    ]);
    $question = Question::factory()
        ->for($hiddenCategory, 'licenseCategory')
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $hiddenCategory->getKey(),
            'mode' => 'sr_review',
            'question_count' => 10,
        ])
        ->assertNotFound();

    expect(StudySession::query()->where('user_id', $user->getKey())->count())->toBe(0);
});

test('api accepts unknown answer only for sr review sessions', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create([
            'total_attempts' => 1,
        ]);

    $createResponse = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 1,
        ])
        ->assertCreated();

    $sessionId = $createResponse->json('data.session.id');

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.answers.store', $sessionId), [
            'question_id' => $question->getKey(),
            'answer_kind' => StudySessionAnswerKind::UNKNOWN,
        ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true)
        ->assertJsonPath('data.answer_kind', StudySessionAnswerKind::UNKNOWN)
        ->assertJsonPath('data.is_correct', false)
        ->assertJsonPath('data.session_status', 'completed')
        ->assertJsonPath('data.daily_progress.daily_plan_policy_version', ReviewTrainerDailyPlanService::VERSION)
        ->assertJsonPath('data.daily_progress.review_day', today()->toDateString())
        ->assertJsonPath('data.daily_progress.daily_target_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT)
        ->assertJsonPath('data.daily_progress.minimum_session_question_count', ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT)
        ->assertJsonPath('data.daily_progress.completed_today_count', 1)
        ->assertJsonPath('data.daily_progress.daily_remaining_count', ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT - 1)
        ->assertJsonPath('data.review_completion.unknown_answers_count', 1)
        ->assertJsonPath('data.review_completion.needs_recovery_answers_count', 1)
        ->assertJsonPath('data.review_completion.next_step.tone', 'recovery');

    $this->actingAs($user)
        ->getJson(route('api.v1.sessions.show', $sessionId))
        ->assertOk()
        ->assertJsonPath('data.session.status', 'completed')
        ->assertJsonPath('data.review_completion.unknown_answers_count', 1)
        ->assertJsonPath('data.review_completion.needs_recovery_answers_count', 1)
        ->assertJsonPath('data.review_completion.next_step.tone', 'recovery');

    $memoryProgress = ReviewMemoryProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    expect($memoryProgress->verified_unknown_count)->toBe(1)
        ->and(UserQuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('question_id', $question->getKey())
            ->first()?->total_attempts)->toBe(1)
        ->and(data_get(
            StudySession::query()->findOrFail($sessionId)->payload,
            ReviewTrainerCompletionSummaryService::SNAPSHOT_PAYLOAD_KEY.'.snapshot_version',
        ))
        ->toBe(ReviewTrainerCompletionSummaryService::SNAPSHOT_VERSION);
});

test('api quick mode caps the session size to ten questions', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'quick',
            'question_count' => 20,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.session.mode', 'quick')
        ->assertJsonPath('data.session.total_questions', 10)
        ->assertJsonCount(10, 'data.questions');
});

test('users cannot inspect someone elses session through the api', function () {
    $owner = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create();

    $session = StudySession::factory()
        ->for($owner)
        ->for($category, 'licenseCategory')
        ->create([
            'status' => 'in_progress',
            'payload' => [
                'question_ids' => $questions->pluck('id')->all(),
            ],
            'total_questions_count' => 3,
            'correct_answers_count' => 0,
            'score_percent' => 0,
        ]);

    $this->actingAs($otherUser)
        ->getJson(route('api.v1.sessions.show', $session))
        ->assertForbidden();
});
