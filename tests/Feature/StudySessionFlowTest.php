<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionExplanationAnnotation;
use App\Models\QuestionExplanationAsset;
use App\Models\QuestionMedia;
use App\Models\QuestionSignLanguageAsset;
use App\Models\QuestionTopic;
use App\Models\SharedQuestionExplanationAsset;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\TrafficSign;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Models\UserTopicCompletionRecord;
use App\Support\QuestionAudioExportManifestBuilder;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\StudySessionAnswerKind;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('users can complete a study session end to end', function () {
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
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(3)],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDays(2)],
            ['difficulty' => 3, 'correct_answer' => 'c', 'published_at' => now()->subDay()],
        )
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->firstOrFail();
    $orderedQuestions = $questions->sortBy('difficulty')->values();

    QuestionExplanationAsset::factory()
        ->for($orderedQuestions->first(), 'question')
        ->create([
            'disk' => 'public',
            'file_path' => 'question-explanations/warning-sign.webp',
            'title' => 'Znak ostrzegawczy',
            'body' => 'To jest materiał referencyjny dla pierwszego pytania.',
            'caption' => 'Zwróć uwagę na kształt i kolor.',
            'alt_text' => 'Trójkątny znak ostrzegawczy',
            'is_active' => true,
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($orderedQuestions->first(), 'question')
        ->label('Ten znak jest kluczowy')
        ->create([
            'annotation_type' => 'label',
            'x_percent' => 45.5,
            'y_percent' => 28.0,
            'tone' => 'warning',
            'position' => 1,
        ]);

    QuestionMedia::factory()
        ->for($orderedQuestions->first())
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/first/video/clip.mp4',
            'poster_path' => 'questions/b/first/video/poster.webp',
            'mime_type' => 'video/mp4',
        ]);
    $orderedQuestions->first()->questionTopic()->associate(QuestionTopic::query()->create([
        'key' => 'vehicle_operation_and_safety',
        'name' => 'Obsluga pojazdu i bezpieczenstwo jazdy',
        'sort_order' => 100,
    ]))->save();

    expect($studySession->status)->toBe('in_progress');
    expect($studySession->questionIds())->toHaveCount(3);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.id', $studySession->getKey())
            ->where('session.license_category_code', 'B')
            ->where('questionIds.0', $orderedQuestions->first()->getKey())
            ->where('progress.total', 3)
            ->where('progress.answered', 0)
            ->where('examUi.duration_seconds', 1500)
            ->where('examUi.basic.total', 3)
            ->where('examUi.specialist.total', 0)
            ->has('questionIds', 3)
            ->where('currentQuestionNumber', 1)
            ->where('currentQuestion.id', $orderedQuestions->first()->getKey())
            ->where('currentQuestion.topic.key', 'vehicle_operation_and_safety')
            ->where('currentQuestion.structure_scope', 'PODSTAWOWY')
            ->where('currentQuestion.media.0.url', 'https://media.example.test/questions/b/first/video/clip.mp4')
            ->where('currentQuestion.media.0.poster_url', 'https://media.example.test/questions/b/first/video/poster.webp')
            ->where('currentQuestion.explanation_asset.title', 'Znak ostrzegawczy')
            ->where('currentQuestion.explanation_asset.image_url', 'https://media.example.test/question-explanations/warning-sign.webp')
            ->where('currentQuestion.explanation_annotations.0.annotation_type', 'label')
            ->where('currentQuestion.explanation_annotations.0.label', 'Ten znak jest kluczowy')
        );

    foreach ($orderedQuestions as $index => $question) {
        $this->actingAs($user)
            ->post(route('study-sessions.answers.store', $studySession), [
                'question_id' => $question->getKey(),
                'selected_answer' => $question->correct_answer,
                'response_time_ms' => 1000 + ($index * 500),
            ])
            ->assertRedirect(
                $index < 2
                    ? route('study-sessions.current')
                    : route('study-sessions.show', $studySession),
            );
    }

    $studySession->refresh();

    expect($studySession->status)->toBe('completed');
    expect($studySession->correct_answers_count)->toBe(3);
    expect((float) $studySession->score_percent)->toBe(100.0);
    expect($studySession->answers()->pluck('answer_kind')->unique()->values()->all())
        ->toBe([StudySessionAnswerKind::CHOICE]);
    expect(UserQuestionProgress::query()->count())->toBe(3);
    $firstQuestionProgress = UserQuestionProgress::query()
        ->where('user_id', $user->getKey())
        ->where('question_id', $orderedQuestions->first()->getKey())
        ->first();

    expect($firstQuestionProgress)->not->toBeNull();
    expect($firstQuestionProgress->total_attempts)->toBe(1);
    expect($firstQuestionProgress->correct_count)->toBe(1);
    expect($firstQuestionProgress->repetitions)->toBe(1);
    expect($firstQuestionProgress->next_review_at?->toDateString())->toBe(today()->addDay()->toDateString());

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.status', 'completed')
            ->where('session.correct_answers_count', 3)
            ->where('progress.answered', 3)
            ->where('currentQuestion', null)
            ->has('results', 3)
            ->where('results.0.response_time_ms', 1000)
            ->where('results.0.explanation_asset.title', 'Znak ostrzegawczy')
            ->where('results.0.explanation_asset.image_url', 'https://media.example.test/question-explanations/warning-sign.webp')
            ->where('results.0.explanation_annotations.0.annotation_type', 'label')
            ->where('results.0.explanation_annotations.0.label', 'Ten znak jest kluczowy')
        );
});

test('learn session exposes automatic sign references from the explanation text', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
        'study.explanation_sign_references_enabled' => true,
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
            'explanation' => 'B-20 nakazuje zatrzymanie, a P-12 wskazuje miejsce zatrzymania.',
        ]);

    TrafficSign::factory()->published()->create([
        'code' => 'B-20',
        'name' => 'Stop',
        'image_path' => 'https://media.example.test/traffic-signs/b-20-stop.webp',
        'image_alt' => 'Znak B-20 Stop',
    ]);
    TrafficSign::factory()->published()->create([
        'code' => 'P-12',
        'name' => 'Linia bezwzglednego zatrzymania',
        'image_path' => 'https://media.example.test/traffic-signs/p-12.webp',
        'image_alt' => 'Znak P-12',
    ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.explanation_sign_references.0.code', 'B-20')
            ->where('currentQuestion.explanation_sign_references.1.code', 'P-12')
            ->where('currentQuestion.explanation_sign_references.0.image_url', 'https://media.example.test/traffic-signs/b-20-stop.webp')
        );

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertOk()
        ->assertJsonPath('answer.explanation_sign_references.0.code', 'B-20')
        ->assertJsonPath('answer.explanation_sign_references.1.code', 'P-12');

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('results.0.explanation_sign_references.0.code', 'B-20')
            ->where('results.0.explanation_sign_references.1.code', 'P-12')
        );
});

test('learn session question payload includes generated audio only for learning mode', function () {
    Storage::fake('media_local');
    config()->set('filesystems.disks.media_local.url', 'https://prawkonaraz.pl/storage-bulk');

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    $item = app(QuestionAudioExportManifestBuilder::class)->build([
        'external_ids' => ['99'],
        'types' => 'question',
    ])['items'][0];

    Storage::disk('media_local')->put($item['target_storage_path'], 'audio-bytes');

    QuestionAudioAsset::query()->create([
        'question_id' => $question->getKey(),
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
        'bytes' => 11,
        'voice_provider' => $item['voice_provider'],
        'voice_id' => $item['voice_id'],
        'model_id' => $item['model_id'],
        'generation_version' => $item['generation_version'],
        'status' => QuestionAudioAsset::STATUS_GENERATED,
        'generated_at' => now(),
    ]);
    $learnSession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->inProgress()
        ->create([
            'mode' => 'learn',
            'correct_answers_count' => 0,
            'total_questions_count' => 1,
            'payload' => [
                'question_ids' => [$question->getKey()],
                'current_index' => 0,
                'answered_count' => 0,
                'ui_shell' => 'zen',
                'filters' => [
                    'question_topic_id' => null,
                    'question_scope' => 'all',
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 1,
                ],
            ],
        ]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.audio.question.url', fn (mixed $url): bool => is_string($url)
                && str_ends_with($url, '/'.$item['target_storage_path']))
            ->where('currentQuestion.audio.question.transcript', 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?')
            ->where('currentQuestion.audio.question.encoding_format', 'audio/mpeg')
        );

    foreach (['exam', 'sr_review', 'pjm'] as $mode) {
        $session = StudySession::factory()
            ->for($user)
            ->for($category, 'licenseCategory')
            ->inProgress()
            ->create([
                'mode' => $mode,
                'correct_answers_count' => 0,
                'total_questions_count' => 1,
                'payload' => [
                    'question_ids' => [$question->getKey()],
                    'current_index' => 0,
                    'answered_count' => 0,
                    'ui_shell' => 'zen',
                ],
            ]);

        $this->actingAs($user)
            ->getJson(route('study-sessions.questions.show', [$session, $question]))
            ->assertOk()
            ->assertJsonPath('question.id', $question->getKey())
            ->assertJsonPath('question.audio', []);
    }
});

test('learn session payload keeps video frame annotations for current question and completion review', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/c/video/frame.mp4',
            'poster_path' => 'questions/c/video/frame-poster.webp',
            'mime_type' => 'video/mp4',
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->videoFrame(7)
        ->label('Sprawdź tor jazdy pojazdu')
        ->create([
            'annotation_type' => 'label',
            'x_percent' => 36.5,
            'y_percent' => 44.0,
            'tone' => 'danger',
            'position' => 1,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.media.0.kind', 'video')
            ->where('currentQuestion.explanation_annotations.0.target_kind', 'video_frame')
            ->where('currentQuestion.explanation_annotations.0.frame_time_seconds', 7)
            ->where('currentQuestion.explanation_annotations.0.label', 'Sprawdź tor jazdy pojazdu')
        );

    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $studySession), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1400,
        ])
        ->assertRedirect(route('study-sessions.show', $studySession));

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('results.0.explanation_annotations.0.target_kind', 'video_frame')
            ->where('results.0.explanation_annotations.0.frame_time_seconds', 7)
            ->where('results.0.explanation_annotations.0.label', 'Sprawdź tor jazdy pojazdu')
        );
});

test('learn session payload keeps image-target markers for video questions as fallback annotations', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/c/video/fallback.mp4',
            'poster_path' => 'questions/c/video/fallback-poster.webp',
            'mime_type' => 'video/mp4',
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->label('To jest starszy marker obrazu')
        ->create([
            'target_kind' => 'question_image',
            'annotation_type' => 'label',
            'x_percent' => 44.0,
            'y_percent' => 33.5,
            'tone' => 'warning',
            'position' => 1,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.media.0.kind', 'video')
            ->where('currentQuestion.explanation_annotations.0.target_kind', 'question_image')
            ->where('currentQuestion.explanation_annotations.0.label', 'To jest starszy marker obrazu')
        );

    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $studySession), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1100,
        ])
        ->assertRedirect(route('study-sessions.show', $studySession));

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('results.0.explanation_annotations.0.target_kind', 'question_image')
            ->where('results.0.explanation_annotations.0.label', 'To jest starszy marker obrazu')
        );
});

test('learn session payload can use shared explanation asset when question has no local override', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '13447',
            'source' => 'gov.pl-mi',
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'disk' => 'public',
        'file_path' => 'question-explanations/shared/warning-sign.webp',
        'title' => 'Wspolny znak ostrzegawczy',
        'alt_text' => 'Wspolny znak',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.explanation_asset.title', 'Wspolny znak ostrzegawczy')
            ->where('currentQuestion.explanation_asset.image_url', 'https://media.example.test/question-explanations/shared/warning-sign.webp')
        );
});

test('learn session payload prefers local explanation asset over shared asset', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '13447',
            'source' => 'gov.pl-mi',
            'difficulty' => 1,
            'correct_answer' => 'a',
            'published_at' => now()->subDay(),
        ]);

    SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'disk' => 'public',
        'file_path' => 'question-explanations/shared/warning-sign.webp',
        'title' => 'Wspolny znak ostrzegawczy',
        'alt_text' => 'Wspolny znak',
        'is_active' => true,
    ]);

    QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'disk' => 'public',
            'file_path' => 'question-explanations/local/warning-sign.webp',
            'title' => 'Lokalny override',
            'alt_text' => 'Lokalny znak',
            'is_active' => true,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->where('currentQuestion.explanation_asset.title', 'Lokalny override')
            ->where('currentQuestion.explanation_asset.image_url', 'https://media.example.test/question-explanations/local/warning-sign.webp')
        );
});

test('learn mode accepts optimistic out of order answer sync without redirecting away from the lightweight json flow', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(3)],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDays(2)],
            ['difficulty' => 3, 'correct_answer' => 'c', 'published_at' => now()->subDay()],
        )
        ->create()
        ->sortBy('difficulty')
        ->values();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questions[1]->getKey(),
            'selected_answer' => 'b',
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $questions[1]->getKey())
        ->assertJsonPath('progress.answered', 1)
        ->assertJsonPath('completed', false);

    $studySession->refresh();

    expect($studySession->payload['current_index'] ?? null)->toBe(2);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questions[0]->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 800,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $questions[0]->getKey())
        ->assertJsonPath('progress.answered', 2)
        ->assertJsonPath('completed', false);

    $studySession->refresh();

    expect($studySession->payload['current_index'] ?? null)->toBe(2);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questions[2]->getKey(),
            'selected_answer' => 'c',
            'response_time_ms' => 700,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $questions[2]->getKey())
        ->assertJsonPath('progress.answered', 3)
        ->assertJsonPath('completed', true);

    $studySession->refresh();

    expect($studySession->status)->toBe('completed');
    expect($studySession->answers()->count())->toBe(3);
    expect($studySession->correct_answers_count)->toBe(3);
});

test('study session question payload prefers the full image while grouping thumbnail variants', function () {
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
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(3)],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDays(2)],
            ['difficulty' => 3, 'correct_answer' => 'c', 'published_at' => now()->subDay()],
        )
        ->create();
    $question = $questions->sortBy('difficulty')->first();

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/first/image/full.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'metadata' => [
                'asset_group' => 'question-b-1-image',
                'source_role' => 'full',
            ],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/first/image/thumb.webp',
            'mime_type' => 'image/webp',
            'variant' => 'thumb',
            'sort_order' => 1,
            'metadata' => [
                'asset_group' => 'question-b-1-image',
                'source_role' => 'thumb',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $question->getKey())
            ->has('questionIds', 3)
            ->where('currentQuestion.media.0.url', 'https://media.example.test/questions/b/first/image/full.webp')
            ->where('currentQuestion.media.0.full_url', 'https://media.example.test/questions/b/first/image/full.webp')
            ->where('currentQuestion.media.0.thumb_url', 'https://media.example.test/questions/b/first/image/thumb.webp')
        );
});

test('review alias normalizes to sr review mode and only pulls due questions from the user review queue', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'a',
            'difficulty' => 4,
            'question_topic_id' => $topic->getKey(),
        ]);

    QuestionExplanationAsset::factory()
        ->for($dueQuestion, 'question')
        ->create([
            'file_path' => 'question-explanations/review/reveal.webp',
            'title' => 'Materiał po odpowiedzi',
            'body' => 'To wyjaśnienie ma wrócić dopiero po odpowiedzi.',
            'alt_text' => 'Materiał referencyjny po odpowiedzi',
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($dueQuestion, 'question')
        ->label('Adnotacja po odpowiedzi')
        ->create();

    $secondDueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'b',
            'difficulty' => 5,
            'question_topic_id' => $topic->getKey(),
        ]);

    $thirdDueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'correct_answer' => 'c',
            'difficulty' => 1,
            'question_topic_id' => $topic->getKey(),
        ]);

    $futureQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 2,
            'question_topic_id' => $topic->getKey(),
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

    UserQuestionProgress::factory()
        ->for($user)
        ->for($thirdDueQuestion, 'question')
        ->dueToday()
        ->create([
            'incorrect_count' => 1,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($futureQuestion, 'question')
        ->scheduledInFuture()
        ->create();

    UserQuestionProgress::factory()
        ->for($otherUser)
        ->for($futureQuestion, 'question')
        ->dueToday()
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'review',
            'question_count' => 10,
        ])
        ->assertRedirect();

    $reviewSession = StudySession::query()->latest()->firstOrFail();

    expect($reviewSession->mode)->toBe('sr_review');
    expect($reviewSession->questionIds()->all())->toBe([
        $secondDueQuestion->getKey(),
        $dueQuestion->getKey(),
        $thirdDueQuestion->getKey(),
    ]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'sr_review')
            ->where('currentQuestion.id', $secondDueQuestion->getKey())
            ->where('currentQuestion.correct_answer', null)
            ->where('currentQuestion.explanation', null)
            ->where('currentQuestion.explanation_asset', null)
            ->where('currentQuestion.explanation_annotations', [])
            ->where('questionPoolMode', 'windowed')
            ->has('questionPool', 0)
            ->has('prefetchedQuestions', 0)
            ->has('topicGroups', 0)
            ->has('results', 0)
        );

    $this->actingAs($user)
        ->getJson(route('study-sessions.current.questions.show', $dueQuestion))
        ->assertOk()
        ->assertJsonPath('question.correct_answer', null)
        ->assertJsonPath('question.explanation', null)
        ->assertJsonPath('question.explanation_asset', null)
        ->assertJsonPath('question.explanation_annotations', []);

    $this->actingAs($user)
        ->getJson(route('study-sessions.current.questions.index').'?'.http_build_query([
            'ids' => [$dueQuestion->getKey()],
        ]))
        ->assertOk()
        ->assertJsonPath('questions.0.id', $dueQuestion->getKey())
        ->assertJsonPath('questions.0.correct_answer', null)
        ->assertJsonPath('questions.0.explanation', null)
        ->assertJsonPath('questions.0.explanation_asset', null)
        ->assertJsonPath('questions.0.explanation_annotations', []);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $dueQuestion->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1200,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $dueQuestion->getKey())
        ->assertJsonPath('answer.correct_answer', 'A')
        ->assertJsonPath('answer.correct_answer_text', $dueQuestion->option_a)
        ->assertJsonPath('answer.explanation', $dueQuestion->explanation)
        ->assertJsonPath('answer.explanation_asset.title', 'Materiał po odpowiedzi')
        ->assertJsonPath('answer.explanation_asset.image_url', 'https://media.example.test/question-explanations/review/reveal.webp')
        ->assertJsonPath('answer.explanation_annotations.0.annotation_type', 'label')
        ->assertJsonPath('answer.explanation_annotations.0.label', 'Adnotacja po odpowiedzi')
        ->assertJsonPath('progress.answered', 1)
        ->assertJsonPath('completed', false);

    $reviewSession->refresh();

    expect($reviewSession->payload['current_index'] ?? null)->toBe(2);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $secondDueQuestion->getKey(),
            'selected_answer' => 'b',
            'response_time_ms' => 1400,
        ])
        ->assertOk()
        ->assertJsonPath('completed', false)
        ->assertJsonPath('reviewCompletion', null);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $thirdDueQuestion->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 2400,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonPath('reviewCompletion.version', ReviewMemorySignalService::VERSION)
        ->assertJsonPath('reviewCompletion.answered_count', 3)
        ->assertJsonPath('reviewCompletion.incorrect_answers_count', 1);

    expect($reviewSession->refresh()->payload[ReviewTrainerCompletionSummaryService::SNAPSHOT_PAYLOAD_KEY] ?? null)
        ->not->toBeNull()
        ->and($reviewSession->payload[ReviewTrainerCompletionSummaryService::SNAPSHOT_PAYLOAD_KEY]['snapshot_version'])
        ->toBe(ReviewTrainerCompletionSummaryService::SNAPSHOT_VERSION);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $reviewSession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'sr_review')
            ->where('session.status', 'completed')
            ->where('reviewCompletion.answered_count', 3)
            ->where('reviewCompletion.incorrect_answers_count', 1)
            ->has('results', 1)
            ->where('results.0.id', $thirdDueQuestion->getKey())
            ->where('results.0.is_correct', false)
        );
});

test('classic learn session does not load PJM sign language assets', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-PJM-ASSET-LEARN-GUARD',
            'correct_answer' => 'a',
        ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertRedirect();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk();

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->contains(fn (string $query): bool => str_contains($query, 'question_sign_language_assets')))->toBeFalse();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('StudySessions/Show')
        ->where('session.mode', 'learn')
        ->where('currentQuestion.id', $question->getKey())
        ->where('currentQuestion.sign_language_assets', [])
        ->where('questionPool.0.sign_language_assets', [])
    );
});

test('web memory trainer start rejects hidden categories outside public study context', function () {
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
        ->post(route('study-sessions.store'), [
            'license_category_id' => $hiddenCategory->getKey(),
            'mode' => 'sr_review',
            'question_count' => 10,
        ])
        ->assertNotFound();

    expect(StudySession::query()->where('user_id', $user->getKey())->count())->toBe(0);
});

test('sr review continues from the first unanswered question after an out of order answer', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a', 'difficulty' => 1],
            ['correct_answer' => 'b', 'difficulty' => 2],
            ['correct_answer' => 'c', 'difficulty' => 3],
        )
        ->create();

    $questions->each(fn (Question $question) => UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create());

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'review',
            'question_count' => 10,
        ])
        ->assertRedirect();

    $reviewSession = StudySession::query()->latest()->firstOrFail();
    $orderedQuestionIds = $reviewSession->questionIds()->all();
    $lastQuestion = Question::query()->findOrFail($orderedQuestionIds[2]);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $lastQuestion->getKey(),
            'selected_answer' => $lastQuestion->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $lastQuestion->getKey())
        ->assertJsonPath('progress.answered', 1)
        ->assertJsonPath('nextQuestion.id', $orderedQuestionIds[0])
        ->assertJsonPath('nextQuestion.correct_answer', null)
        ->assertJsonPath('nextQuestion.explanation', null)
        ->assertJsonPath('nextQuestion.explanation_asset', null)
        ->assertJsonPath('nextQuestion.explanation_annotations', [])
        ->assertJsonPath('nextQuestionNumber', 1)
        ->assertJsonPath('completed', false);

    $reviewSession->refresh();

    expect($reviewSession->status)->toBe('in_progress')
        ->and($reviewSession->payload['current_index'] ?? null)->toBe(3)
        ->and($reviewSession->payload['next_unanswered_question_id'] ?? null)->toBe($orderedQuestionIds[0]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'sr_review')
            ->where('session.status', 'in_progress')
            ->where('progress.answered', 1)
            ->where('currentQuestion.id', $orderedQuestionIds[0])
            ->where('currentQuestionNumber', 1)
            ->has('results', 1)
            ->where('results.0.id', $lastQuestion->getKey())
        );
});

test('sr review completion uses persisted answers instead of payload answered count', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $questions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a', 'difficulty' => 1],
            ['correct_answer' => 'b', 'difficulty' => 2],
        )
        ->create();

    $questions->each(fn (Question $question) => UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create());

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 2,
        ])
        ->assertRedirect();

    $reviewSession = StudySession::query()->latest()->firstOrFail();
    $payload = $reviewSession->payload;
    $payload['answered_count'] = 1;
    $reviewSession->forceFill(['payload' => $payload])->save();

    $firstQuestion = Question::query()->findOrFail($reviewSession->questionIds()->first());

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $firstQuestion->getKey(),
            'selected_answer' => $firstQuestion->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('completed', false)
        ->assertJsonPath('progress.answered', 1)
        ->assertJsonPath('progress.remaining', 1);

    $reviewSession->refresh();

    expect($reviewSession->status)->toBe('in_progress')
        ->and($reviewSession->answers()->count())->toBe(1)
        ->and($reviewSession->payload['answered_count'])->toBe(1);

    $payload = $reviewSession->payload;
    $payload['answered_count'] = 2;
    $reviewSession->forceFill(['payload' => $payload])->save();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'sr_review')
            ->where('session.status', 'in_progress')
            ->where('progress.answered', 1)
            ->where('progress.remaining', 1)
        );
});

test('quick mode caps the requested question count and uses active category questions', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'quick',
            'question_count' => 20,
        ])
        ->assertRedirect();

    $quickSession = StudySession::query()->latest()->firstOrFail();

    expect($quickSession->mode)->toBe('quick');
    expect($quickSession->total_questions_count)->toBe(10);
    expect($quickSession->questionIds())->toHaveCount(10);
    expect($quickSession->questionIds()->unique())->toHaveCount(10);
    expect($quickSession->questionIds()->every(
        fn (int $questionId): bool => Question::query()
            ->whereKey($questionId)
            ->where('license_category_id', $category->getKey())
            ->exists()
    ))->toBeTrue();
});

test('hard mode only pulls the highest-risk questions for the user', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $highestRiskQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 5,
        ]);

    $secondRiskQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 4,
        ]);

    $masteredQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 3,
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($highestRiskQuestion, 'question')
        ->create([
            'total_attempts' => 3,
            'correct_count' => 0,
            'incorrect_count' => 3,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($secondRiskQuestion, 'question')
        ->create([
            'total_attempts' => 3,
            'correct_count' => 1,
            'incorrect_count' => 2,
            'correct_streak' => 0,
            'last_quality' => 2,
            'next_review_at' => today(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($masteredQuestion, 'question')
        ->create([
            'total_attempts' => 5,
            'correct_count' => 4,
            'incorrect_count' => 1,
            'correct_streak' => 3,
            'last_quality' => 5,
            'next_review_at' => today()->addDays(4),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'hard',
            'question_count' => 10,
        ])
        ->assertRedirect();

    $hardSession = StudySession::query()->latest()->firstOrFail();

    expect($hardSession->mode)->toBe('hard');
    expect($hardSession->questionIds()->all())->toBe([
        $highestRiskQuestion->getKey(),
        $secondRiskQuestion->getKey(),
    ]);
});

test('users cannot open someone elses study session', function () {
    $owner = User::factory()->withPurchasedAccess()->create();
    $otherUser = User::factory()->withPurchasedAccess()->create();
    $studySession = StudySession::factory()
        ->for($owner)
        ->create();

    $this->actingAs($otherUser)
        ->get(route('study-sessions.show', $studySession))
        ->assertForbidden();
});

test('users can manually complete an in progress study session', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'source' => 'gov.pl',
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'source' => 'gov.pl',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->post(route('study-sessions.complete', $studySession))
        ->assertRedirect(route('study-sessions.show', $studySession));

    $studySession->refresh();

    expect($studySession->status)->toBe('completed');
    expect($studySession->completed_at)->not->toBeNull();
});

test('in progress exam sessions render the dedicated exam screen', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();
    $firstExamQuestion = Question::query()->findOrFail($studySession->questionIds()->first());
    $firstExamQuestion->update([
        'explanation' => 'B-20 nakazuje zatrzymanie przed wjazdem na skrzyzowanie.',
    ]);

    TrafficSign::factory()->published()->create([
        'code' => 'B-20',
        'name' => 'Stop',
        'image_path' => 'https://media.example.test/traffic-signs/b-20-stop.webp',
        'image_alt' => 'Znak B-20 Stop',
    ]);

    QuestionExplanationAsset::factory()
        ->for($firstExamQuestion, 'question')
        ->create([
            'disk' => 'public',
            'file_path' => 'question-explanations/exam-hidden.webp',
            'title' => 'Nie pokazuj w aktywnym egzaminie',
            'body' => 'To wyjaśnienie nie może wyciec do aktywnego egzaminu.',
            'alt_text' => 'Ukryty materiał referencyjny',
            'is_active' => true,
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($firstExamQuestion, 'question')
        ->create([
            'annotation_type' => 'circle',
            'x_percent' => 50.0,
            'y_percent' => 50.0,
            'width_percent' => 18.0,
            'height_percent' => 18.0,
        ]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Exam')
            ->where('session.id', $studySession->getKey())
            ->where('progress.total', 32)
            ->where('currentQuestionNumber', 1)
            ->where('examUi.duration_seconds', 1500)
            ->where('examUi.basic.total', 20)
            ->where('examUi.specialist.total', 12)
            ->where('currentQuestion.structure_scope', 'PODSTAWOWY')
            ->where('currentQuestion.external_id', $firstExamQuestion->external_id)
            ->where('currentQuestion.source', $firstExamQuestion->source)
            ->where('currentQuestion.explanation_asset', null)
            ->where('currentQuestion.explanation_sign_references', [])
            ->where('currentQuestion.explanation_annotations', [])
        );
});

test('exam sync endpoint can switch the current basic question into answer phase', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.exam.state'), [
            'action' => 'start-answer',
        ])
        ->assertOk()
        ->assertJsonPath('examUi.phase', 'answer')
        ->assertJsonPath('currentQuestionNumber', 1)
        ->assertJsonPath('currentQuestion.structure_scope', 'PODSTAWOWY')
        ->assertJsonPath('completed', false);
});

test('exam media phase uses known video duration for the question countdown', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-28 12:00:00'));

    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope_video',
        'name' => 'Zakres egzaminacyjny wideo',
        'sort_order' => 15,
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/exam/video/clip.mp4',
            'poster_path' => 'questions/b/exam/video/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 18,
        ]);

    StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->inProgress()
        ->create([
            'mode' => 'exam',
            'started_at' => now(),
            'total_questions_count' => 1,
            'payload' => [
                'question_ids' => [$question->getKey()],
                'current_index' => 0,
                'answered_count' => 0,
                'ui_shell' => 'exam',
                'exam_started_at' => now()->toIso8601String(),
                'exam_deadline_at' => now()->addMinutes(25)->toIso8601String(),
                'question_phase' => 'media',
                'question_started_at' => now()->toIso8601String(),
                'filters' => [
                    'question_topic_id' => null,
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 1,
                ],
            ],
        ]);

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.exam.state'))
        ->assertOk()
        ->assertJsonPath('examUi.phase', 'media')
        ->assertJsonPath('examUi.question_remaining_seconds', 18)
        ->assertJsonPath('currentQuestion.media.0.duration_seconds', 18)
        ->assertJsonPath('completed', false);

    Carbon::setTestNow();
});

test('exam render auto advances timed out questions and records unanswered timeout attempts', function () {
    Carbon::setTestNow(now());

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(20)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    Carbon::setTestNow($studySession->started_at->copy()->addSeconds(36));

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Exam')
            ->where('currentQuestionNumber', 2)
            ->where('progress.answered', 1)
            ->where('examUi.basic.answered', 1)
            ->where('examUi.phase', 'preview')
            ->where('currentQuestion.structure_scope', 'PODSTAWOWY')
        );

    $studySession->refresh();

    expect($studySession->answers()->count())->toBe(1);
    expect($studySession->answers()->first()?->selected_answer)->toBeNull();
    expect($studySession->answers()->first()?->answer_kind)->toBe(StudySessionAnswerKind::TIMEOUT);
    expect($studySession->answers()->first()?->is_correct)->toBeFalse();
    expect($studySession->answers()->first()?->response_time_ms)->toBe(15000);

    Carbon::setTestNow();
});

test('exam complete endpoint redirects to the dedicated exam result screen', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope_finish',
        'name' => 'Zakres egzaminacyjny finish',
        'sort_order' => 20,
    ]);

    $questionIds = Question::factory()
        ->count(32)
        ->for($category, 'licenseCategory')
        ->sequence(fn ($sequence) => [
            'question_topic_id' => $topic->getKey(),
            'correct_answer' => 'a',
            'points' => $sequence->index < 10 ? 3 : 2,
            'metadata' => [
                'structure_scope' => $sequence->index < 20 ? 'PODSTAWOWY' : 'SPECJALISTYCZNY',
            ],
        ])
        ->create()
        ->pluck('id')
        ->all();

    $studySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->inProgress()
        ->create([
            'mode' => 'exam',
            'started_at' => now()->subMinutes(8),
            'total_questions_count' => 32,
            'payload' => [
                'question_ids' => $questionIds,
                'current_index' => 4,
                'answered_count' => 4,
                'ui_shell' => 'exam',
                'exam_started_at' => now()->subMinutes(8)->toIso8601String(),
                'exam_deadline_at' => now()->addMinutes(17)->toIso8601String(),
                'question_phase' => 'answer',
                'question_started_at' => now()->subSeconds(8)->toIso8601String(),
                'filters' => [
                    'question_topic_id' => null,
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 32,
                ],
            ],
        ]);

    foreach (array_slice($questionIds, 0, 4) as $index => $questionId) {
        StudySessionAnswer::factory()
            ->for($studySession)
            ->create([
                'question_id' => $questionId,
                'selected_answer' => $index < 3 ? 'a' : null,
                'is_correct' => $index < 3,
                'response_time_ms' => 9000 + ($index * 500),
                'answered_at' => now()->subMinutes(7)->addSeconds($index * 10),
            ]);
    }

    $this->actingAs($user)
        ->post(route('study-sessions.current.complete'))
        ->assertJsonPath('redirect', route('study-sessions.show', $studySession));

    $studySession->refresh();

    expect($studySession->status)->toBe('completed');

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/ExamResult')
            ->where('session.status', 'completed')
            ->where('session.mode', 'exam')
        );
});

test('completed exam uses a dedicated result screen instead of the learning summary view', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    $basicQuestions = collect(range(1, 20))
        ->map(fn (int $index) => Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'question_topic_id' => $topic->getKey(),
                'correct_answer' => 'a',
                'points' => $index <= 10 ? 3 : 2,
                'metadata' => [
                    'structure_scope' => 'PODSTAWOWY',
                ],
            ]));

    $specialistQuestions = collect(range(1, 12))
        ->map(fn () => Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'question_topic_id' => $topic->getKey(),
                'correct_answer' => 'b',
                'points' => 2,
                'metadata' => [
                    'structure_scope' => 'SPECJALISTYCZNY',
                ],
            ]));

    $questionIds = $basicQuestions
        ->pluck('id')
        ->merge($specialistQuestions->pluck('id'))
        ->values()
        ->all();

    QuestionExplanationAsset::factory()
        ->for($basicQuestions->get(18), 'question')
        ->create([
            'disk' => 'public',
            'file_path' => 'question-explanations/exam-review.webp',
            'title' => 'Znak do powtórki po egzaminie',
            'body' => 'Ten blok ma być widoczny dopiero po zakończeniu egzaminu.',
            'alt_text' => 'Materiał referencyjny po egzaminie',
            'is_active' => true,
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($basicQuestions->get(18), 'question')
        ->create([
            'annotation_type' => 'circle',
            'x_percent' => 38.0,
            'y_percent' => 33.0,
            'width_percent' => 16.0,
            'height_percent' => 16.0,
            'tone' => 'danger',
        ]);

    $studySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'exam',
            'status' => 'completed',
            'started_at' => now()->subMinutes(18),
            'completed_at' => now(),
            'correct_answers_count' => 29,
            'total_questions_count' => 32,
            'score_percent' => 90.63,
            'payload' => [
                'question_ids' => $questionIds,
                'answered_count' => 32,
                'ui_shell' => 'exam',
                'exam_started_at' => now()->subMinutes(18)->toIso8601String(),
                'exam_deadline_at' => now()->addMinutes(7)->toIso8601String(),
                'filters' => [
                    'question_topic_id' => null,
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 32,
                ],
            ],
        ]);

    $answeredAt = now()->subMinutes(17);

    foreach ($basicQuestions as $index => $question) {
        StudySessionAnswer::factory()
            ->for($studySession)
            ->create([
                'question_id' => $question->getKey(),
                'selected_answer' => match ($index) {
                    18 => 'b',
                    19 => null,
                    default => 'a',
                },
                'is_correct' => $index < 18,
                'response_time_ms' => 12000 + ($index * 250),
                'answered_at' => $answeredAt->copy()->addSeconds($index * 5),
            ]);
    }

    foreach ($specialistQuestions as $index => $question) {
        StudySessionAnswer::factory()
            ->for($studySession)
            ->create([
                'question_id' => $question->getKey(),
                'selected_answer' => $index === 11 ? null : 'b',
                'is_correct' => $index < 11,
                'response_time_ms' => 18000 + ($index * 350),
                'answered_at' => $answeredAt->copy()->addMinutes(3)->addSeconds($index * 7),
            ]);
    }

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/ExamResult')
            ->where('session.mode', 'exam')
            ->where('session.status', 'completed')
            ->where('examResult.passed', true)
            ->where('examResult.earned_points', 68)
            ->where('examResult.pass_threshold', 68)
            ->where('examResult.correct_answers_count', 29)
            ->where('examResult.incorrect_answers_count', 1)
            ->where('examResult.unanswered_count', 2)
            ->where('examResult.basic.total', 20)
            ->where('examResult.specialist.total', 12)
            ->has('results', 32)
            ->where('results.18.explanation_asset.title', 'Znak do powtórki po egzaminie')
            ->where('results.18.explanation_asset.image_url', 'https://media.example.test/question-explanations/exam-review.webp')
            ->where('results.18.explanation_annotations.0.annotation_type', 'circle')
            ->where('results.18.explanation_annotations.0.tone', 'danger')
        );
});

test('study session answer store returns json payload for background sync', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a', 'difficulty' => 1],
            ['correct_answer' => 'b', 'difficulty' => 2],
            ['correct_answer' => 'c', 'difficulty' => 3],
        )
        ->create();
    $question = $questions->sortBy('difficulty')->first();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $question->getKey(),
            'selected_answer' => 'a',
            'response_time_ms' => 1250,
        ])
        ->assertOk()
        ->assertJsonPath('answer.question_id', $question->getKey())
        ->assertJsonPath('answer.selected_answer', 'a')
        ->assertJsonPath('answer.answer_kind', StudySessionAnswerKind::CHOICE)
        ->assertJsonPath('answer.is_correct', true)
        ->assertJsonPath('answer.response_time_ms', 1250)
        ->assertJsonPath('session.correct_answers_count', 1)
        ->assertJsonPath('session.total_questions_count', 3)
        ->assertJsonPath('session.score_percent', 33.33);
});

test('study session complete returns json payload for background sync', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('study-sessions.current.complete'))
        ->assertOk()
        ->assertJsonPath('session.status', 'completed')
        ->assertJsonPath('session.correct_answers_count', 0)
        ->assertJsonPath('session.total_questions_count', 3)
        ->assertJsonPath('session.score_percent', 0);
});

test('study session follow up request returns json redirect for incorrect questions', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'published_at' => now()->subDays(2), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 2, 'published_at' => now()->subDay(), 'question_topic_id' => $topic->getKey()],
        )
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions[0], 'question')
        ->create([
            'total_attempts' => 1,
            'correct_count' => 0,
            'incorrect_count' => 1,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions[1], 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 0,
            'incorrect_count' => 2,
            'correct_streak' => 0,
            'last_quality' => 1,
            'next_review_at' => today(),
        ]);

    $this->actingAs($user)
        ->withHeader('X-Study-Session-Switch', 'follow-up')
        ->postJson(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 2,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'incorrect',
            'randomize_order' => false,
        ])
        ->assertOk()
        ->assertJsonPath('redirect', route('study-sessions.current'))
        ->assertJsonPath('session.status', 'in_progress');

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->status)->toBe('in_progress');
    expect($studySession->payload['filters']['question_status'] ?? null)->toBe('incorrect');
    expect($studySession->payload['filters']['question_topic_id'] ?? null)->toBe($topic->getKey());
    expect($studySession->payload['question_ids'] ?? [])->toHaveCount(2);
});

test('completion reward does not appear for quickly closed sessions without any correct answers', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);

    $basePayload = [
        'question_ids' => [101, 102, 103],
        'filters' => [
            'question_topic_id' => 10,
            'question_status' => 'all',
        ],
    ];

    $previousSession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'started_at' => now()->subMinutes(3),
            'completed_at' => now()->subMinutes(2),
            'correct_answers_count' => 0,
            'total_questions_count' => 3,
            'score_percent' => 0,
            'payload' => $basePayload,
        ]);

    $currentSession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'started_at' => now()->subSeconds(20),
            'completed_at' => now(),
            'correct_answers_count' => 0,
            'total_questions_count' => 3,
            'score_percent' => 0,
            'payload' => $basePayload,
        ]);

    // Simulate quickly closing the current session without solving any question.
    StudySessionAnswer::query()->where('study_session_id', $previousSession->getKey())->delete();
    StudySessionAnswer::query()->where('study_session_id', $currentSession->getKey())->delete();

    $this->actingAs($user)
        ->get(route('study-sessions.show', $currentSession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('completionTiming.comparison.show_reward', false)
            ->where('completionTiming.comparison.saved_duration_seconds', null)
            ->where('completionTiming.comparison.saved_average_correct_response_time_ms', null)
        );
});

test('topic completion records are shared between classic and zen shells', function () {
    Carbon::setTestNow('2026-05-22 09:50:00');

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);
    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);
    $nextTopic = QuestionTopic::query()->create([
        'key' => 'road_markings',
        'name' => 'Znaki poziome',
        'sort_order' => 20,
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(2), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDay(), 'question_topic_id' => $topic->getKey()],
        )
        ->create();
    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 3,
            'correct_answer' => 'c',
            'question_topic_id' => $nextTopic->getKey(),
        ]);

    $clock = Carbon::parse('2026-05-22 10:00:00');
    $completeTopicSession = function (string $uiShell, int $durationSeconds) use (&$clock, $category, $topic, $user): StudySession {
        Carbon::setTestNow($clock);

        $this->actingAs($user)
            ->post(route('study-sessions.store'), [
                'license_category_id' => $category->getKey(),
                'mode' => 'learn',
                'ui_shell' => $uiShell,
                'question_count' => 2,
                'question_topic_id' => $topic->getKey(),
                'question_status' => 'all',
                'question_scope' => 'all',
            ])
            ->assertRedirect();

        $studySession = StudySession::query()->latest('id')->firstOrFail();
        $questionIds = $studySession->questionIds()->values();
        $lastIndex = $questionIds->count() - 1;

        foreach ($questionIds as $index => $questionId) {
            $question = Question::query()->findOrFail($questionId);
            Carbon::setTestNow(
                $clock->copy()->addSeconds($index === $lastIndex ? $durationSeconds : max(1, (int) floor($durationSeconds / 2))),
            );

            $this->actingAs($user)
                ->post(route('study-sessions.answers.store', $studySession), [
                    'question_id' => $question->getKey(),
                    'selected_answer' => $question->correct_answer,
                    'response_time_ms' => 1000 + ($index * 250),
                ])
                ->assertRedirect();
        }

        $clock = $clock->copy()->addMinutes(10);

        return $studySession->fresh() ?? $studySession;
    };

    $firstSession = $completeTopicSession('zen', 120);

    $record = UserTopicCompletionRecord::query()->sole();
    expect($record->best_duration_seconds)->toBe(120)
        ->and($record->best_ui_shell)->toBe('zen')
        ->and($record->completion_count)->toBe(1)
        ->and($record->perfect_completion_count)->toBe(1)
        ->and($firstSession->payload['topic_completion_record']['record_state'] ?? null)->toBe('first_record');

    $secondSession = $completeTopicSession('exam_like', 80);

    $record->refresh();
    expect(UserTopicCompletionRecord::query()->count())->toBe(1)
        ->and($record->best_duration_seconds)->toBe(80)
        ->and($record->best_ui_shell)->toBe('exam_like')
        ->and($record->completion_count)->toBe(2)
        ->and($record->perfect_completion_count)->toBe(2)
        ->and($secondSession->payload['topic_completion_record']['record_state'] ?? null)->toBe('improved_record')
        ->and($secondSession->payload['topic_completion_record']['saved_duration_seconds'] ?? null)->toBe(40);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $secondSession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->has('topicCompletionOverview.items', 2)
            ->where('topicCompletionOverview.items.0.topic_id', $topic->getKey())
            ->where('topicCompletionOverview.items.0.best_duration_seconds', 80)
            ->where('topicCompletionOverview.items.0.record_state', 'improved_record')
            ->where('topicCompletionOverview.items.0.saved_duration_seconds', 40)
            ->where('topicCompletionOverview.items.1.topic_id', $nextTopic->getKey())
            ->where('topicCompletionOverview.items.1.best_duration_seconds', null)
            ->where('topicCompletionOverview.learning_path.total_topics', 2)
            ->where('topicCompletionOverview.learning_path.mastered_topics', 1)
            ->where('topicCompletionOverview.learning_path.mastered_topic_ids', [$topic->getKey()])
            ->where('topicCompletionOverview.learning_path.current_position', 1)
            ->where('topicCompletionOverview.learning_path.remaining_after_current', 1)
        );

    Carbon::setTestNow();
});

test('learning path counters separate current position from current full-scope mastery', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);
    $firstTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 300,
    ]);
    $currentTopic = QuestionTopic::query()->create([
        'key' => 'road_markings',
        'name' => 'Znaki poziome',
        'sort_order' => 100,
    ]);
    $lastTopic = QuestionTopic::query()->create([
        'key' => 'speed_limits',
        'name' => 'Dopuszczalne prędkości',
        'sort_order' => 200,
    ]);

    $firstQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $firstTopic->getKey(),
            'metadata' => ['structure_scope' => 'PODSTAWOWY'],
        ]);
    $currentQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $currentTopic->getKey(),
            'metadata' => ['structure_scope' => 'PODSTAWOWY'],
        ]);
    $lastQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $lastTopic->getKey(),
            'metadata' => ['structure_scope' => 'SPECJALISTYCZNY'],
        ]);

    UserTopicCompletionRecord::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $firstTopic->getKey(),
        'question_scope' => 'all',
        'best_duration_seconds' => 60,
        'best_questions_count' => 1,
        'best_question_ids_hash' => hash('sha256', (string) $firstQuestion->getKey()),
        'perfect_completion_count' => 1,
    ]);
    UserTopicCompletionRecord::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $currentTopic->getKey(),
        'question_scope' => 'basic',
        'best_duration_seconds' => 45,
        'best_questions_count' => 1,
        'best_question_ids_hash' => hash('sha256', (string) $currentQuestion->getKey()),
        'perfect_completion_count' => 1,
    ]);
    UserTopicCompletionRecord::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $lastTopic->getKey(),
        'question_scope' => 'all',
        'best_duration_seconds' => 30,
        'best_questions_count' => 1,
        'best_question_ids_hash' => hash('sha256', 'stale-question-pool'),
        'perfect_completion_count' => 1,
    ]);

    $studySession = StudySession::factory()->for($user)->for($category, 'licenseCategory')->create([
        'mode' => 'learn',
        'status' => 'completed',
        'correct_answers_count' => 0,
        'total_questions_count' => 1,
        'score_percent' => 0,
        'payload' => [
            'question_ids' => [$currentQuestion->getKey()],
            'current_index' => 0,
            'answered_count' => 0,
            'ui_shell' => 'exam_like',
            'filters' => [
                'question_topic_id' => $currentTopic->getKey(),
                'question_scope' => 'all',
                'question_status' => 'all',
                'randomize_order' => false,
                'question_count' => 1,
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('topicCompletionOverview.learning_path.total_topics', 3)
            ->where('topicCompletionOverview.learning_path.mastered_topics', 1)
            ->where('topicCompletionOverview.learning_path.mastered_topic_ids', [$firstTopic->getKey()])
            ->where('topicCompletionOverview.learning_path.current_position', 2)
            ->where('topicCompletionOverview.learning_path.remaining_after_current', 1)
        );

    $payload = $studySession->payload;
    $payload['question_ids'] = [$lastQuestion->getKey()];
    $payload['filters']['question_topic_id'] = $lastTopic->getKey();
    $studySession->forceFill(['payload' => $payload])->save();

    $this->actingAs($user)
        ->get(route('study-sessions.show', $studySession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('topicCompletionOverview.learning_path.current_position', 3)
            ->where('topicCompletionOverview.learning_path.remaining_after_current', 0)
        );
});

test('topic completion records ignore incomplete manual completions and non perfect best times', function () {
    Carbon::setTestNow('2026-05-22 10:50:00');

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);
    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);
    $questions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(2), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDay(), 'question_topic_id' => $topic->getKey()],
        )
        ->create();

    Carbon::setTestNow('2026-05-22 11:00:00');
    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'zen',
            'question_count' => 2,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $manualSession = StudySession::query()->latest('id')->firstOrFail();
    Carbon::setTestNow('2026-05-22 11:01:00');
    $this->actingAs($user)
        ->post(route('study-sessions.complete', $manualSession))
        ->assertRedirect(route('study-sessions.show', $manualSession));

    expect(UserTopicCompletionRecord::query()->count())->toBe(0);

    Carbon::setTestNow('2026-05-22 11:10:00');
    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'zen',
            'question_count' => 2,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $imperfectSession = StudySession::query()->latest('id')->firstOrFail();
    $orderedQuestions = $imperfectSession->questionIds()
        ->map(fn (int $questionId) => $questions->firstWhere('id', $questionId))
        ->filter()
        ->values();

    Carbon::setTestNow('2026-05-22 11:10:30');
    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $imperfectSession), [
            'question_id' => $orderedQuestions[0]->getKey(),
            'selected_answer' => $orderedQuestions[0]->correct_answer,
            'response_time_ms' => 1000,
        ])
        ->assertRedirect();

    Carbon::setTestNow('2026-05-22 11:11:00');
    $wrongAnswer = $orderedQuestions[1]->correct_answer === 'a' ? 'b' : 'a';
    $this->actingAs($user)
        ->post(route('study-sessions.answers.store', $imperfectSession), [
            'question_id' => $orderedQuestions[1]->getKey(),
            'selected_answer' => $wrongAnswer,
            'response_time_ms' => 1100,
        ])
        ->assertRedirect(route('study-sessions.show', $imperfectSession));

    $record = UserTopicCompletionRecord::query()->sole();
    expect($record->last_duration_seconds)->toBe(60)
        ->and($record->best_duration_seconds)->toBeNull()
        ->and($record->completion_count)->toBe(1)
        ->and($record->perfect_completion_count)->toBe(0)
        ->and($imperfectSession->fresh()->payload['topic_completion_record']['record_state'] ?? null)->toBe('none');

    Carbon::setTestNow();
});

test('last answer response includes fresh topic completion overview for full topic sessions', function () {
    Carbon::setTestNow('2026-05-22 12:00:00');

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);
    $topic = QuestionTopic::query()->create([
        'key' => 'pedestrian_crossings',
        'name' => 'Przejścia i przystanki',
        'sort_order' => 10,
    ]);
    $nextTopic = QuestionTopic::query()->create([
        'key' => 'rail_crossings',
        'name' => 'Przejazdy kolejowe',
        'sort_order' => 20,
    ]);
    $questions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'correct_answer' => 'a', 'published_at' => now()->subDays(2), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 2, 'correct_answer' => 'b', 'published_at' => now()->subDay(), 'question_topic_id' => $topic->getKey()],
        )
        ->create()
        ->sortBy('difficulty')
        ->values();
    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 3,
            'correct_answer' => 'c',
            'question_topic_id' => $nextTopic->getKey(),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'zen',
            'question_count' => 2,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
            'question_scope' => 'all',
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest('id')->firstOrFail();
    $payload = $studySession->payload;
    $payload['filters']['question_status'] = 'incorrect';
    $studySession->forceFill(['payload' => $payload])->save();

    Carbon::setTestNow('2026-05-22 12:00:20');
    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questions[0]->getKey(),
            'selected_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('completed', false);

    Carbon::setTestNow('2026-05-22 12:01:00');
    $this->actingAs($user)
        ->postJson(route('study-sessions.current.answers.store'), [
            'question_id' => $questions[1]->getKey(),
            'selected_answer' => $questions[1]->correct_answer,
            'response_time_ms' => 800,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonPath('topicCompletionOverview.items.0.topic_id', $topic->getKey())
        ->assertJsonPath('topicCompletionOverview.items.0.best_duration_seconds', 60)
        ->assertJsonPath('topicCompletionOverview.items.0.record_state', 'first_record')
        ->assertJsonPath('topicCompletionOverview.items.1.topic_id', $nextTopic->getKey())
        ->assertJsonPath('topicCompletionOverview.items.1.best_duration_seconds', null)
        ->assertJsonPath('topicCompletionOverview.learning_path.total_topics', 2)
        ->assertJsonPath('topicCompletionOverview.learning_path.mastered_topics', 1)
        ->assertJsonPath('topicCompletionOverview.learning_path.mastered_topic_ids.0', $topic->getKey())
        ->assertJsonPath('topicCompletionOverview.learning_path.current_position', 1)
        ->assertJsonPath('topicCompletionOverview.learning_path.remaining_after_current', 1);

    $record = UserTopicCompletionRecord::query()->sole();
    expect($record->last_study_session_id)->toBe($studySession->getKey())
        ->and($record->best_duration_seconds)->toBe(60)
        ->and($studySession->fresh()->payload['topic_completion_record']['record_state'] ?? null)->toBe('first_record');

    Carbon::setTestNow();
});

test('large learn sessions use a windowed question preload instead of sending the full pool', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(130)
        ->for($category, 'licenseCategory')
        ->sequence(fn ($sequence) => [
            'difficulty' => ($sequence->index % 5) + 1,
            'published_at' => now()->subMinutes($sequence->index),
            'question_topic_id' => $topic->getKey(),
        ])
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 130,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();
    $orderedQuestions = $questions
        ->sortBy([
            ['difficulty', 'asc'],
            ['published_at', 'desc'],
        ])
        ->values();

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.id', $studySession->getKey())
            ->where('questionPoolMode', 'windowed')
            ->where('questionBatchSize', 12)
            ->where('currentQuestion.id', $orderedQuestions->first()->getKey())
            ->has('prefetchedQuestions', 11)
            ->where('prefetchedQuestions.0.id', $orderedQuestions->get(1)->getKey())
            ->where('prefetchedQuestions.10.id', $orderedQuestions->get(11)->getKey())
            ->has('questionPool', 0)
        );
});

test('current study session question batch endpoint returns requested questions in order', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(4)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 1, 'published_at' => now()->subDays(4), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 2, 'published_at' => now()->subDays(3), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 3, 'published_at' => now()->subDays(2), 'question_topic_id' => $topic->getKey()],
            ['difficulty' => 4, 'published_at' => now()->subDay(), 'question_topic_id' => $topic->getKey()],
        )
        ->create();

    $orderedQuestions = $questions->sortBy('difficulty')->values();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 4,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->getJson(route('study-sessions.current.questions.index', [
            'ids' => [
                $orderedQuestions->get(1)->getKey(),
                $orderedQuestions->get(3)->getKey(),
            ],
        ]))
        ->assertOk()
        ->assertJsonPath('questions.0.id', $orderedQuestions->get(1)->getKey())
        ->assertJsonPath('questions.1.id', $orderedQuestions->get(3)->getKey());
});

test('study session start excludes questions that are not ready for delivery', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $brokenQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'difficulty' => 1,
            'published_at' => now()->subDays(4),
            'requires_primary_media' => true,
            'delivery_issue' => Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA,
        ]);

    $readyQuestions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['difficulty' => 2, 'published_at' => now()->subDays(3)],
            ['difficulty' => 3, 'published_at' => now()->subDays(2)],
            ['difficulty' => 4, 'published_at' => now()->subDay()],
        )
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 4,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->total_questions_count)->toBe(3);
    expect($studySession->questionIds())->toHaveCount(3);
    expect($studySession->questionIds())->not->toContain($brokenQuestion->getKey());
    expect($studySession->questionIds()->values()->all())->toEqual(
        $readyQuestions->sortBy('difficulty')->pluck('id')->values()->all(),
    );
});
