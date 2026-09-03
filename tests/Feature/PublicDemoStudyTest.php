<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\ReviewMemoryProgress;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\UserQuestionProgress;
use App\Support\PublicQuestionCatalogService;
use App\Support\QuestionAudioExportManifestBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function publicDemoStudyQuestions(int $count = 3): Collection
{
    $category = LicenseCategory::factory()
        ->categoryB()
        ->create([
            'name' => 'Kategoria B',
            'sort_order' => 1,
        ]);

    return Question::factory()
        ->count($count)
        ->for($category, 'licenseCategory')
        ->sequence(fn (Sequence $sequence): array => [
            'external_id' => 'DEMO-'.str_pad((string) ($sequence->index + 1), 3, '0', STR_PAD_LEFT),
            'prompt' => 'Pytanie demo '.($sequence->index + 1).'? Czy to jest bezpieczne zachowanie?',
            'explanation' => 'Wyjasnienie demo '.($sequence->index + 1).' pokazuje, dlaczego odpowiedz B jest poprawna.',
            'option_a' => 'Odpowiedz A '.($sequence->index + 1),
            'option_b' => 'Odpowiedz B '.($sequence->index + 1),
            'option_c' => 'Odpowiedz C '.($sequence->index + 1),
            'correct_answer' => 'b',
            'is_active' => true,
            'delivery_issue' => null,
            'requires_primary_media' => false,
            'source' => 'gov',
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ])
        ->create();
}

/**
 * @return array<string, mixed>
 */
function publicDemoAttachGeneratedQuestionAudio(Question $question): array
{
    $item = app(QuestionAudioExportManifestBuilder::class)->build([
        'external_ids' => [(string) $question->external_id],
    ])['items'][0];

    Storage::disk('media_local')->put($item['target_storage_path'], 'demo-audio');

    QuestionAudioAsset::query()->create([
        'question_id' => $item['question_id'],
        'external_id' => $item['external_id'],
        'asset_key' => $item['asset_key'],
        'content_scope' => $item['content_scope'],
        'audio_type' => $item['audio_type'],
        'locale' => $item['locale'],
        'source_text' => $item['source_text'],
        'source_text_hash' => $item['source_text_hash'],
        'storage_disk' => 'media_local',
        'storage_path' => $item['target_storage_path'],
        'duration_seconds' => 3.2,
        'encoding_format' => 'audio/mpeg',
        'bytes' => 10,
        'voice_provider' => $item['voice_provider'],
        'voice_id' => $item['voice_id'],
        'model_id' => $item['model_id'],
        'generation_version' => $item['generation_version'],
        'status' => QuestionAudioAsset::STATUS_GENERATED,
        'generated_at' => now(),
    ]);

    return $item;
}

test('public tests landing points to isolated player demo', function () {
    config(['public_demo.player_demo.question_limit' => 20]);

    $questions = publicDemoStudyQuestions(20);

    $this->get(route('public.tests'))
        ->assertOk()
        ->assertSessionMissing('public_demo.player_demo')
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'learn')
            ->where('session.ui_shell', 'exam_like')
            ->where('session.total_questions_count', 20)
            ->where('progress.answered', 0)
            ->where('progress.total', 20)
            ->where('currentQuestionNumber', 1)
            ->where('currentQuestion.id', $questions->first()->getKey())
            ->where('publicDemo.enabled', true)
            ->where('publicDemo.mode', 'frozen_packet')
            ->where('publicDemo.gate.enabled', true)
            ->where('publicDemo.gate.primary_label', 'Sprawdź 20 pytań próbnych')
            ->where('publicDemo.gate.secondary_label', 'Zobacz pełny dostęp')
            ->where('publicDemo.routes.demo', route('public.tests.demo.show', ['fresh' => 1], absolute: false))
            ->where('publicDemo.routes.current', route('public.tests.demo.show', absolute: false))
        );
});

test('guest can open the 20 question demo without authentication', function () {
    config(['public_demo.player_demo.question_limit' => 3]);

    $questions = publicDemoStudyQuestions(3);

    $this->get(route('public.tests.demo.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'learn')
            ->where('session.ui_shell', 'exam_like')
            ->where('session.total_questions_count', 3)
            ->where('progress.answered', 0)
            ->where('progress.total', 3)
            ->where('currentQuestionNumber', 1)
            ->where('currentQuestion.id', $questions->first()->getKey())
            ->where('currentQuestion.correct_answer', 'B')
            ->where('publicDemo.enabled', true)
            ->where('publicDemo.mode', 'frozen_packet')
            ->where('publicDemo.viewer.authenticated', false)
        );
});

test('guest demo exposes generated question audio when it is available', function (): void {
    Storage::fake('media_local');
    config(['public_demo.player_demo.question_limit' => 1]);

    $question = publicDemoStudyQuestions(1)->firstOrFail();
    $item = publicDemoAttachGeneratedQuestionAudio($question);

    $this->get(route('public.tests.demo.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.audio.question.url', fn (mixed $url): bool => is_string($url)
                && str_ends_with($url, '/'.$item['target_storage_path']))
            ->where('currentQuestion.audio.question.transcript', 'Pytanie demo 1? Czy to jest bezpieczne zachowanie?')
            ->where('currentQuestion.audio.question.encoding_format', 'audio/mpeg')
        );
});

test('guest demo exposes public explanation links for visible questions', function (): void {
    config(['public_demo.player_demo.question_limit' => 2]);

    $questions = publicDemoStudyQuestions(2);
    $catalog = app(PublicQuestionCatalogService::class);
    $firstPublicExplanationUrl = $catalog->findCanonicalUrlByExternalId((string) $questions->first()->external_id);
    $secondPublicExplanationUrl = $catalog->findCanonicalUrlByExternalId((string) $questions->get(1)->external_id);

    $this->get(route('public.tests.demo.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentQuestion.public_explanation_url', $firstPublicExplanationUrl)
            ->where('questionPool.0.public_explanation_url', $firstPublicExplanationUrl)
            ->where('questionPool.1.public_explanation_url', $secondPublicExplanationUrl)
        );

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $questions->first()->getKey(),
        'selected_answer' => 'a',
    ])
        ->assertOk()
        ->assertJsonPath('answer.is_correct', false)
        ->assertJsonPath('nextQuestion.public_explanation_url', $secondPublicExplanationUrl);
});

test('demo answer advances in session without writing real learning progress', function () {
    config(['public_demo.player_demo.question_limit' => 3]);

    $questions = publicDemoStudyQuestions(3);

    $this->get(route('public.tests.demo.show'))->assertOk();

    expect(StudySession::query()->count())->toBe(0)
        ->and(StudySessionAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()->count())->toBe(0)
        ->and(ReviewMemoryProgress::query()->count())->toBe(0);

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $questions->first()->getKey(),
        'selected_answer' => 'b',
    ])
        ->assertOk()
        ->assertJsonPath('answer.is_correct', true)
        ->assertJsonPath('answer.correct_answer', 'B')
        ->assertJsonPath('session.correct_answers_count', 1)
        ->assertJsonPath('progress.answered', 1)
        ->assertJsonPath('nextQuestion.id', $questions->get(1)->getKey())
        ->assertJsonPath('nextQuestion.correct_answer', 'B')
        ->assertJsonPath('nextQuestion.explanation', 'Wyjasnienie demo 2 pokazuje, dlaczego odpowiedz B jest poprawna.')
        ->assertJsonPath('nextQuestionNumber', 2)
        ->assertJsonPath('completed', false);

    expect(StudySession::query()->count())->toBe(0)
        ->and(StudySessionAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()->count())->toBe(0)
        ->and(ReviewMemoryProgress::query()->count())->toBe(0);
});

test('frozen demo can complete from one final answer sync without writing learning progress', function () {
    config(['public_demo.player_demo.question_limit' => 3]);

    $questions = publicDemoStudyQuestions(3);

    $this->get(route('public.tests.demo.show'))->assertOk();

    $this->postJson(route('public.tests.demo.complete'), [
        'answers' => [
            [
                'question_id' => $questions->first()->getKey(),
                'selected_answer' => 'b',
                'answer_kind' => 'choice',
                'response_time_ms' => 1200,
            ],
            [
                'question_id' => $questions->get(1)->getKey(),
                'selected_answer' => 'a',
                'answer_kind' => 'choice',
                'response_time_ms' => 1400,
            ],
            [
                'question_id' => $questions->get(2)->getKey(),
                'selected_answer' => 'b',
                'answer_kind' => 'choice',
                'response_time_ms' => 900,
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('session.status', 'completed')
        ->assertJsonPath('session.correct_answers_count', 2)
        ->assertJsonPath('session.total_questions_count', 3)
        ->assertJsonPath('session.score_percent', 66.7);

    $this->get(route('public.tests.demo.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.status', 'completed')
            ->where('progress.answered', 3)
            ->where('results.0.correct_answer', 'B')
            ->where('results.1.is_correct', false)
            ->where('results.2.is_correct', true)
        );

    expect(StudySession::query()->count())->toBe(0)
        ->and(StudySessionAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()->count())->toBe(0)
        ->and(ReviewMemoryProgress::query()->count())->toBe(0);
});

test('demo rejects stale answers after moving to the next question', function () {
    config(['public_demo.player_demo.question_limit' => 3]);

    $questions = publicDemoStudyQuestions(3);
    $firstQuestion = $questions->first();

    $this->get(route('public.tests.demo.show'))->assertOk();

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $firstQuestion->getKey(),
        'selected_answer' => 'b',
    ])->assertOk();

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $firstQuestion->getKey(),
        'selected_answer' => 'b',
    ])->assertStatus(409);
});

test('demo completes after configured question count and can be restarted', function () {
    config(['public_demo.player_demo.question_limit' => 2]);

    $questions = publicDemoStudyQuestions(2);

    $this->get(route('public.tests.demo.show'))->assertOk();

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $questions->first()->getKey(),
        'selected_answer' => 'b',
    ])
        ->assertOk()
        ->assertJsonPath('completed', false);

    $this->postJson(route('public.tests.demo.answers.store'), [
        'question_id' => $questions->get(1)->getKey(),
        'selected_answer' => 'b',
    ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonPath('session.status', 'completed')
        ->assertJsonPath('session.correct_answers_count', 2)
        ->assertJsonPath('nextQuestion', null);

    $this->post(route('public.tests.demo.restart'))
        ->assertRedirect(route('public.tests.demo.show', ['fresh' => 1]));

    $this->get(route('public.tests.demo.show', ['fresh' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.status', 'in_progress')
            ->where('progress.answered', 0)
            ->where('currentQuestion.id', $questions->first()->getKey())
        );
});
