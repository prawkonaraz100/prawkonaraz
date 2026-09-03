<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionSignLanguageAsset;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserQuestionProgress;
use App\Support\StudySessionManager;
use Inertia\Testing\AssertableInertia as Assert;

function createPjmStudySessionUser(LicenseCategory $category): User
{
    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    return $user;
}

function createPjmStudySessionCategory(): LicenseCategory
{
    return LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
}

function createPjmStudySessionTopic(string $key, int $sortOrder): QuestionTopic
{
    return QuestionTopic::query()->create([
        'key' => $key,
        'name' => str($key)->replace('_', ' ')->title()->toString(),
        'sort_order' => $sortOrder,
        'is_active' => true,
    ]);
}

it('shows PJM progress grouped by user topic on the PJM landing page', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);
    $speedLimits = createPjmStudySessionTopic('speed_limits', 210);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $warningSigns->getKey(),
        'display_name' => 'Ostrzegawcze w PJM',
    ]);

    $unansweredPjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90501',
        'question_topic_id' => $warningSigns->getKey(),
    ]);
    $correctPjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90502',
        'question_topic_id' => $warningSigns->getKey(),
    ]);
    $incorrectPjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90503',
        'question_topic_id' => $speedLimits->getKey(),
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90504',
        'question_topic_id' => $warningSigns->getKey(),
    ]);
    $pjmQuestionWithoutTopic = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90505',
        'question_topic_id' => null,
    ]);

    foreach ([$unansweredPjmQuestion, $correctPjmQuestion, $incorrectPjmQuestion, $pjmQuestionWithoutTopic] as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    UserQuestionProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $correctPjmQuestion->getKey(),
        'total_attempts' => 1,
        'correct_count' => 1,
        'incorrect_count' => 0,
        'correct_streak' => 1,
        'last_answered_at' => now(),
        'first_answered_at' => now(),
    ]);
    UserQuestionProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $incorrectPjmQuestion->getKey(),
        'total_attempts' => 1,
        'correct_count' => 0,
        'incorrect_count' => 1,
        'correct_streak' => 0,
        'last_answered_at' => now(),
        'first_answered_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('session.pjm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/PjmIndex')
            ->where('progress.total_questions', 3)
            ->where('progress.answered_questions', 2)
            ->where('progress.unanswered_questions', 1)
            ->where('progress.incorrect_questions', 1)
            ->where('progress.correct_questions', 1)
            ->where('progress.current_topic_id', $warningSigns->getKey())
            ->where('progress.topic_groups.0.label', 'Pytania podstawowe')
            ->where('progress.topic_groups.0.options.0.id', $warningSigns->getKey())
            ->where('progress.topic_groups.0.options.0.label', 'Ostrzegawcze w PJM')
            ->where('progress.topic_groups.0.options.0.questions_count', 2)
            ->where('progress.topic_groups.0.options.0.answered_count', 1)
            ->where('progress.topic_groups.0.options.0.counts.unanswered', 1)
            ->where('progress.topic_groups.1.label', 'Pytania specjalistyczne')
            ->where('progress.topic_groups.1.options.0.id', $speedLimits->getKey())
            ->where('progress.topic_groups.1.options.0.counts.incorrect', 1)
        );
});

it('starts a PJM session from the first topic with unanswered PJM questions when no topic is selected', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);
    $speedLimits = createPjmStudySessionTopic('speed_limits', 210);

    $answeredQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90601',
        'question_topic_id' => $warningSigns->getKey(),
        'correct_answer' => 'a',
    ]);
    $nextTopicQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90602',
        'question_topic_id' => $speedLimits->getKey(),
        'correct_answer' => 'b',
    ]);

    foreach ([$answeredQuestion, $nextTopicQuestion] as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    UserQuestionProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $answeredQuestion->getKey(),
        'total_attempts' => 1,
        'correct_count' => 1,
        'incorrect_count' => 0,
        'correct_streak' => 1,
        'last_answered_at' => now(),
        'first_answered_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('session.pjm.store'), [
            'question_count' => 12,
            'question_status' => 'unanswered',
        ])
        ->assertRedirect(route('study-sessions.current'));

    $studySession = StudySession::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($studySession->mode)->toBe(StudySessionManager::MODE_PJM)
        ->and($studySession->payload['filters']['question_topic_id'])->toBe($speedLimits->getKey())
        ->and($studySession->payload['question_ids'])->toBe([$nextTopicQuestion->getKey()])
        ->and($studySession->payload['question_ids'])->not->toContain($answeredQuestion->getKey());
});

it('starts a PJM session only from the selected topic when a topic is selected', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);
    $speedLimits = createPjmStudySessionTopic('speed_limits', 210);

    $warningQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90701',
        'question_topic_id' => $warningSigns->getKey(),
    ]);
    $speedQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90702',
        'question_topic_id' => $speedLimits->getKey(),
    ]);

    foreach ([$warningQuestion, $speedQuestion] as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    $this->actingAs($user)
        ->post(route('session.pjm.store'), [
            'question_topic_id' => $speedLimits->getKey(),
            'question_count' => 12,
            'question_status' => 'unanswered',
        ])
        ->assertRedirect(route('study-sessions.current'));

    $studySession = StudySession::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($studySession->payload['filters']['question_topic_id'])->toBe($speedLimits->getKey())
        ->and($studySession->payload['question_ids'])->toBe([$speedQuestion->getKey()])
        ->and($studySession->payload['question_ids'])->not->toContain($warningQuestion->getKey());
});

it('starts the entire selected PJM topic when topic remaining strategy is requested', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);
    $speedLimits = createPjmStudySessionTopic('speed_limits', 210);

    $topicQuestions = collect(range(1, 45))
        ->map(fn (int $index) => Question::factory()->for($category, 'licenseCategory')->create([
            'external_id' => sprintf('908%02d', $index),
            'question_topic_id' => $warningSigns->getKey(),
            'difficulty' => ($index % 3) + 1,
        ]));
    $otherTopicQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90899',
        'question_topic_id' => $speedLimits->getKey(),
    ]);

    foreach ($topicQuestions->concat([$otherTopicQuestion]) as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    $this->actingAs($user)
        ->post(route('session.pjm.store'), [
            'question_topic_id' => $warningSigns->getKey(),
            'question_count_strategy' => StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING,
            'question_status' => 'unanswered',
        ])
        ->assertRedirect(route('study-sessions.current'));

    $studySession = StudySession::query()->where('user_id', $user->getKey())->firstOrFail();
    $sessionQuestionIds = collect($studySession->payload['question_ids'])->sort()->values()->all();
    $expectedQuestionIds = $topicQuestions->pluck('id')->sort()->values()->all();

    expect($studySession->mode)->toBe(StudySessionManager::MODE_PJM)
        ->and($studySession->total_questions_count)->toBe(45)
        ->and($studySession->payload['filters']['question_topic_id'])->toBe($warningSigns->getKey())
        ->and($studySession->payload['filters']['question_count_strategy'])->toBe(StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING)
        ->and($studySession->payload['filters']['question_count'])->toBe(45)
        ->and($studySession->payload['question_ids'])->toHaveCount(45)
        ->and($sessionQuestionIds)->toBe($expectedQuestionIds)
        ->and($studySession->payload['question_ids'])->not->toContain($otherTopicQuestion->getKey());
});

it('can repeat an entire PJM topic including already answered questions', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);

    $answeredQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90851',
        'question_topic_id' => $warningSigns->getKey(),
    ]);
    $unansweredQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90852',
        'question_topic_id' => $warningSigns->getKey(),
    ]);

    foreach ([$answeredQuestion, $unansweredQuestion] as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    UserQuestionProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $answeredQuestion->getKey(),
        'total_attempts' => 1,
        'correct_count' => 1,
        'incorrect_count' => 0,
        'correct_streak' => 1,
        'last_answered_at' => now(),
        'first_answered_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('session.pjm.store'), [
            'question_topic_id' => $warningSigns->getKey(),
            'question_count_strategy' => StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING,
            'question_status' => 'all',
        ])
        ->assertRedirect(route('study-sessions.current'));

    $studySession = StudySession::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($studySession->payload['filters']['question_status'])->toBe('all')
        ->and($studySession->payload['filters']['question_count_strategy'])->toBe(StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING)
        ->and($studySession->payload['question_ids'])->toHaveCount(2)
        ->and($studySession->payload['question_ids'])->toContain($answeredQuestion->getKey())
        ->and($studySession->payload['question_ids'])->toContain($unansweredQuestion->getKey());
});

it('rejects topic remaining PJM sessions when no topic can be resolved', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '90901',
        'question_topic_id' => null,
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $this->actingAs($user)
        ->from(route('session.pjm'))
        ->post(route('session.pjm.store'), [
            'question_count_strategy' => StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING,
            'question_status' => 'all',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('question_topic_id');
});

it('starts a free PJM session only with unanswered questions that have PJM assets', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);

    $unansweredPjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '91001',
        'correct_answer' => 'a',
        'difficulty' => 1,
    ]);
    $answeredPjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '91002',
        'correct_answer' => 'b',
        'difficulty' => 2,
    ]);
    $questionWithoutPjm = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '91003',
        'correct_answer' => 'c',
        'difficulty' => 3,
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $unansweredPjmQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);
    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $unansweredPjmQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_ANSWER_A,
    ]);
    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $answeredPjmQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    UserQuestionProgress::factory()->create([
        'user_id' => $user->getKey(),
        'question_id' => $answeredPjmQuestion->getKey(),
        'total_attempts' => 1,
        'correct_count' => 1,
        'correct_streak' => 1,
        'last_answered_at' => now(),
        'first_answered_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('session.pjm.store'))
        ->assertRedirect(route('study-sessions.current'));

    $studySession = StudySession::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($studySession->mode)->toBe(StudySessionManager::MODE_PJM)
        ->and($studySession->license_category_id)->toBe($category->getKey())
        ->and($studySession->payload['filters']['question_status'])->toBe('unanswered')
        ->and($studySession->payload['question_ids'])->toBe([$unansweredPjmQuestion->getKey()])
        ->and($studySession->payload['question_ids'])->not->toContain($answeredPjmQuestion->getKey())
        ->and($studySession->payload['question_ids'])->not->toContain($questionWithoutPjm->getKey());
});

it('allows a free PJM user to open the PJM session payload with sign language assets', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '92001',
        'correct_answer' => 'a',
    ]);
    QuestionMedia::factory()->for($question)->video()->create([
        'path' => 'questions/pjm-session-original-media.mp4',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'path' => 'pjm/sign-language/pjm92001.mp4',
    ]);

    app(StudySessionManager::class)->start(
        $user,
        $category,
        StudySessionManager::MODE_PJM,
        12,
        ['question_status' => 'unanswered'],
    );

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', StudySessionManager::MODE_PJM)
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.media.0.kind', 'video')
            ->where('currentQuestion.sign_language_assets.0.role', QuestionSignLanguageAsset::ROLE_QUESTION)
        );
});

it('records PJM answers in the shared question progress table', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '93001',
        'correct_answer' => 'a',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    app(StudySessionManager::class)->start(
        $user,
        $category,
        StudySessionManager::MODE_PJM,
        12,
        ['question_status' => 'unanswered'],
    );

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1200,
        ])
        ->assertOk()
        ->assertJsonPath('answer.is_correct', true)
        ->assertJsonPath('completed', true);

    $progress = UserQuestionProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $question->getKey())
        ->first();

    expect($progress)->not->toBeNull()
        ->and($progress->total_attempts)->toBe(1)
        ->and($progress->correct_count)->toBe(1);
});

it('shows honest PJM completion coverage and full access context', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $pjmQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '93501',
        'correct_answer' => 'a',
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '93502',
        'correct_answer' => 'b',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $pjmQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $studySession = app(StudySessionManager::class)->start(
        $user,
        $category,
        StudySessionManager::MODE_PJM,
        12,
        ['question_status' => 'unanswered'],
    );

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $pjmQuestion->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1200,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', StudySessionManager::MODE_PJM)
            ->where('pjmCompletion.coverage.total_questions', 2)
            ->where('pjmCompletion.coverage.pjm_questions', 1)
            ->where('pjmCompletion.coverage.missing_questions', 1)
            ->where('pjmCompletion.has_full_product_access', false)
            ->where('pjmCompletion.pricing_url', route('public.pricing', absolute: false))
        );
});

it('shows user-specific PJM completion progress and the next topic to continue', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $warningSigns = createPjmStudySessionTopic('warning_signs', 10);
    $speedLimits = createPjmStudySessionTopic('speed_limits', 210);

    $completedQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '93601',
        'question_topic_id' => $warningSigns->getKey(),
        'correct_answer' => 'a',
    ]);
    $nextTopicQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '93602',
        'question_topic_id' => $speedLimits->getKey(),
        'correct_answer' => 'b',
    ]);

    foreach ([$completedQuestion, $nextTopicQuestion] as $question) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => $question->external_id,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        ]);
    }

    $studySession = app(StudySessionManager::class)->start(
        $user,
        $category,
        StudySessionManager::MODE_PJM,
        12,
        [
            'question_topic_id' => $warningSigns->getKey(),
            'question_status' => 'unanswered',
        ],
    );

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $completedQuestion->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1200,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonPath('pjmCompletion.progress.total_questions', 2)
        ->assertJsonPath('pjmCompletion.progress.answered_questions', 1)
        ->assertJsonPath('pjmCompletion.progress.unanswered_questions', 1)
        ->assertJsonPath('pjmCompletion.progress.current_topic_id', $speedLimits->getKey());

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', StudySessionManager::MODE_PJM)
            ->where('pjmCompletion.progress.total_questions', 2)
            ->where('pjmCompletion.progress.answered_questions', 1)
            ->where('pjmCompletion.progress.unanswered_questions', 1)
            ->where('pjmCompletion.progress.current_topic_id', $speedLimits->getKey())
            ->where('pjmCompletion.progress.topic_groups.0.options.0.id', $warningSigns->getKey())
            ->where('pjmCompletion.progress.topic_groups.0.options.0.answered_count', 1)
            ->where('pjmCompletion.progress.topic_groups.1.options.0.id', $speedLimits->getKey())
            ->where('pjmCompletion.progress.topic_groups.1.options.0.counts.unanswered', 1)
        );
});

it('does not let free PJM access open non-PJM study sessions', function (): void {
    $category = createPjmStudySessionCategory();
    $user = createPjmStudySessionUser($category);
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '94001',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $studySession = StudySession::factory()->for($user)->for($category, 'licenseCategory')->inProgress()->create([
        'mode' => StudySessionManager::MODE_LEARN,
        'total_questions_count' => 1,
        'payload' => [
            'question_ids' => [$question->getKey()],
            'current_index' => 0,
            'answered_count' => 0,
            'ui_shell' => StudySessionManager::UI_SHELL_ZEN,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertRedirect(route('access.activate'));
});
