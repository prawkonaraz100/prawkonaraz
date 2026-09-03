<?php

use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use App\Models\QuestionExplanationAsset;
use App\Models\QuestionMedia;
use App\Models\SharedQuestionExplanationAsset;
use App\Models\SharedQuestionExplanationSignOverride;
use App\Models\TrafficSign;
use App\Models\User;
use App\Support\QuestionExplanationSignReferencePayloadBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('admin users can access the question edit page with annotation preview', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => 'B-001234',
        'prompt' => 'Czy widzisz znak ostrzegawczy?',
    ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/annotation-preview/full.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
            'metadata' => [
                'asset_group' => 'question-b-annotation-preview',
                'source_role' => 'full',
            ],
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->label('Ten znak jest kluczowy')
        ->create([
            'x_percent' => 42.0,
            'y_percent' => 31.0,
            'tone' => 'warning',
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->create([
            'annotation_type' => 'circle',
            'x_percent' => 72.0,
            'y_percent' => 28.0,
            'width_percent' => 14.0,
            'height_percent' => 16.0,
            'tone' => 'danger',
            'position' => 2,
        ]);

    $this->actingAs($admin)
        ->get(QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'))
        ->assertOk()
        ->assertSee('Dodaj marker i ustaw go na podglądzie', false)
        ->assertSee('Ustawienia markera', false)
        ->assertSee('Ten znak jest kluczowy', false)
        ->assertSee('savedAnnotations', false)
        ->assertSee('Etykieta', false)
        ->assertSee('Okrąg', false)
        ->assertSee('Strzałka', false)
        ->assertSee('Stopklatka filmu', false)
        ->assertSee('Duplikuj marker', false)
        ->assertSee('Zaawansowane (opcjonalnie)', false)
        ->assertSee('Lista markerów', false)
        ->assertSee('Zapisz całe pytanie, aby utrwalić wszystkie zmiany markerów.', false)
        ->assertSee('normalizeAnnotationStatePath', false)
        ->assertSee('data.explanation_annotations', false)
        ->assertSee('flushAnnotationsToWire', false)
        ->assertSee('this.$wire.set(this.annotationsPath, this.annotations, false)', false)
        ->assertSee('B-001234', false);
});

test('admin users see upload guard messaging on the question edit page', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'To jest wyjaśnienie pytania.',
    ]);

    $this->actingAs($admin)
        ->get(QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'))
        ->assertOk()
        ->assertSee('Po wybraniu nowego pliku poczekaj, aż zniknie status przesyłania, a dopiero potem kliknij „Zapisz”.', false)
        ->assertSee('data-upload-guard="question-explanation-asset"', false)
        ->assertSee('window.syncQuestionExplanationUploadGuards', false)
        ->assertSee('x-on:click', false);
});

test('non admin users cannot access the question edit page with annotation preview', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create([
        'explanation' => 'To jest **wyjaśnienie** pytania.',
    ]);

    $this->actingAs($user)
        ->get(QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'))
        ->assertForbidden();
});

test('admin users can persist reference explanation asset edits from the question edit page', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'file_path' => 'question-explanations/reference-sign.webp',
            'title' => 'Testowy blok referencyjny',
            'body' => 'Stary opis materialu referencyjnego.',
            'alt_text' => 'Stary alt text',
            'caption' => 'Stary podpis',
            'is_active' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_asset.title', 'Testowy blok referencyjny OK')
        ->set('data.explanation_asset.body', 'Nowy opis materialu referencyjnego.')
        ->set('data.explanation_asset.alt_text', 'Nowy alt text')
        ->call('save')
        ->assertHasNoErrors();

    $asset = $question->referenceExplanationAsset()->firstOrFail();

    expect($asset->title)->toBe('Testowy blok referencyjny OK')
        ->and($asset->body)->toBe('Nowy opis materialu referencyjnego.')
        ->and($asset->alt_text)->toBe('Nowy alt text')
        ->and($asset->caption)->toBe('Stary podpis');
});

test('admin users can save shared inline sign corrections from the question edit page', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
        'study.explanation_sign_references_enabled' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $replacementSign = TrafficSign::factory()->published()->create([
        'code' => 'A-7',
        'name' => 'Ustap pierwszenstwa',
        'image_path' => 'https://media.example.test/traffic-signs/a-7.webp',
    ]);
    $addedSign = TrafficSign::factory()->published()->create([
        'code' => 'D-1',
        'name' => 'Droga z pierwszenstwem',
        'image_path' => 'https://media.example.test/traffic-signs/d-1.webp',
    ]);
    TrafficSign::factory()->published()->create([
        'code' => 'B-20',
        'name' => 'Stop',
        'image_path' => 'https://media.example.test/traffic-signs/b-20.webp',
    ]);
    TrafficSign::factory()->published()->create([
        'code' => 'P-12',
        'name' => 'Linia bezwzglednego zatrzymania',
        'image_path' => 'https://media.example.test/traffic-signs/p-12.webp',
    ]);

    $firstQuestion = Question::factory()->create([
        'external_id' => 'pj360:884',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie, a P-12 wskazuje miejsce. Po zatrzymaniu rozejrzyj sie.',
    ]);
    $secondQuestion = Question::factory()->create([
        'external_id' => 'pj360:884',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie, a P-12 wskazuje miejsce. Po zatrzymaniu rozejrzyj sie.',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $firstQuestion->getRouteKey()])
        ->set('data.explanation_sign_overrides_apply_scope', 'shared_external_id')
        ->set('data.explanation_sign_overrides', [
            [
                'action' => 'replace',
                'detected_code' => 'B-20',
                'traffic_sign_id' => $replacementSign->getKey(),
                'is_active' => true,
            ],
            [
                'action' => 'hide',
                'detected_code' => 'P-12',
                'is_active' => true,
            ],
            [
                'action' => 'add',
                'anchor_text' => 'Po zatrzymaniu',
                'traffic_sign_id' => $addedSign->getKey(),
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $overrides = SharedQuestionExplanationSignOverride::query()
        ->where('external_id', 'pj360:884')
        ->where('source_scope', SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'))
        ->orderBy('position')
        ->get();

    expect($overrides)->toHaveCount(3)
        ->and($overrides->pluck('action')->all())->toBe(['replace', 'hide', 'add'])
        ->and($overrides[0]->detected_code)->toBe('B-20')
        ->and($overrides[1]->traffic_sign_id)->toBeNull()
        ->and($overrides[2]->anchor_text)->toBe('Po zatrzymaniu');

    $references = app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($secondQuestion);

    expect($references)->toHaveCount(2)
        ->and($references[0]['code'])->toBe('A-7')
        ->and($references[0]['match_text'])->toBe('B-20')
        ->and($references[1]['code'])->toBe('D-1')
        ->and($references[1]['placement'])->toBe('after');

    expect(AuditLog::query()
        ->where('action', 'admin.question.explanation_sign_overrides_updated')
        ->where('entity_id', (string) $firstQuestion->getKey())
        ->exists())->toBeTrue();
});

test('saving another question field preserves its existing shared sign corrections', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $replacementSign = TrafficSign::factory()->published()->create([
        'code' => 'A-7',
        'name' => 'Ustap pierwszenstwa',
        'image_path' => 'https://media.example.test/traffic-signs/a-7.webp',
    ]);
    $question = Question::factory()->create([
        'external_id' => 'pj360:preserve',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie.',
    ]);
    SharedQuestionExplanationSignOverride::query()->create([
        'external_id' => 'pj360:preserve',
        'source_scope' => SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'),
        'action' => SharedQuestionExplanationSignOverride::ACTION_REPLACE,
        'detected_code' => 'B-20',
        'traffic_sign_id' => $replacementSign->getKey(),
        'position' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.points', 2)
        ->call('save')
        ->assertHasNoErrors();

    expect(SharedQuestionExplanationSignOverride::query()
        ->where('external_id', 'pj360:preserve')
        ->where('source_scope', SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'))
        ->count())->toBe(1)
        ->and($question->fresh()->points)->toBe(2);
});

test('admin users can save one shared reference explanation asset for all questions with the same external id', function () {
    Storage::fake('media_local');

    config([
        'media.public_disk' => 'media_local',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();

    $firstQuestion = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
        'explanation' => 'Wspolne wyjasnienie.',
    ]);

    $secondQuestion = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
        'explanation' => 'Wspolne wyjasnienie.',
    ]);

    Storage::disk('media_local')->put('question-explanations/first-local.png', 'first-image');
    Storage::disk('media_local')->put('question-explanations/second-local.png', 'second-image');
    Storage::disk('media_local')->put('question-explanations/shared-upload.png', 'shared-image');

    QuestionExplanationAsset::factory()
        ->for($firstQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/first-local.png',
            'title' => 'Pierwszy lokalny asset',
            'alt_text' => 'Pierwszy lokalny alt',
            'is_active' => true,
        ]);

    QuestionExplanationAsset::factory()
        ->for($secondQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/second-local.png',
            'title' => 'Drugi lokalny asset',
            'alt_text' => 'Drugi lokalny alt',
            'is_active' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $firstQuestion->getRouteKey()])
        ->set('data.explanation_asset_apply_scope', 'shared_external_id')
        ->set('data.explanation_asset.file_path', ['question-explanations/shared-upload.png'])
        ->set('data.explanation_asset.title', 'Wspolny znak ostrzegawczy')
        ->set('data.explanation_asset.body', 'Wspolny opis materialu referencyjnego.')
        ->set('data.explanation_asset.alt_text', 'Wspolny alt text')
        ->set('data.explanation_asset.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $sharedAsset = SharedQuestionExplanationAsset::query()
        ->where('external_id', '13447')
        ->where('source_scope', SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'))
        ->first();

    expect($sharedAsset)->not->toBeNull()
        ->and($sharedAsset?->title)->toBe('Wspolny znak ostrzegawczy')
        ->and($sharedAsset?->body)->toBe('Wspolny opis materialu referencyjnego.')
        ->and($sharedAsset?->alt_text)->toBe('Wspolny alt text')
        ->and($sharedAsset?->file_path)->toStartWith('question-explanations/shared/govpl-mi/13447/');

    expect($firstQuestion->fresh()->referenceExplanationAsset)->toBeNull()
        ->and($secondQuestion->fresh()->referenceExplanationAsset)->toBeNull();

    Storage::disk('media_local')->assertExists($sharedAsset->file_path);
    Storage::disk('media_local')->assertMissing('question-explanations/first-local.png');
    Storage::disk('media_local')->assertMissing('question-explanations/second-local.png');
});

test('admin users can save a reference explanation image without fallback body when the main explanation exists', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'To jest główne wyjaśnienie karty odpowiedzi.',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_asset.file_path', ['question-explanations/reference-sign.webp'])
        ->set('data.explanation_asset.alt_text', 'Alt tekst grafiki')
        ->call('save')
        ->assertHasNoErrors();

    $asset = $question->referenceExplanationAsset()->firstOrFail();

    expect($asset->file_path)->toBe('question-explanations/reference-sign.webp')
        ->and($asset->alt_text)->toBe('Alt tekst grafiki')
        ->and($asset->body)->toBeNull();
});

test('admin users replacing an existing reference image persist the newest uploaded file for that question', function () {
    Storage::fake('media_local');

    config([
        'media.public_disk' => 'media_local',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'To jest główne wyjaśnienie karty odpowiedzi.',
    ]);

    Storage::disk('media_local')->put('question-explanations/old-reference.png', 'old-image');
    Storage::disk('media_local')->put('question-explanations/new-reference.png', 'new-image');

    QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/old-reference.png',
            'alt_text' => 'Stary alt text',
            'is_active' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_asset.file_path', [
            'question-explanations/old-reference.png',
            'question-explanations/new-reference.png',
        ])
        ->set('data.explanation_asset.alt_text', 'Nowy alt text')
        ->call('save')
        ->assertHasNoErrors();

    $asset = $question->fresh()->referenceExplanationAsset;

    expect($asset)->not->toBeNull()
        ->and($asset?->question_id)->toBe($question->getKey())
        ->and($asset?->file_path)->toStartWith("question-explanations/{$question->getKey()}/")
        ->and($asset?->file_path)->toEndWith('.png')
        ->and($asset?->alt_text)->toBe('Nowy alt text')
        ->and($asset?->file_path)->not->toBe('question-explanations/old-reference.png');

    Storage::disk('media_local')->assertExists($asset->file_path);
    Storage::disk('media_local')->assertMissing('question-explanations/old-reference.png');
});

test('admin users uploading a replacement reference image store the uploaded file on disk', function () {
    Storage::fake('media_local');

    config([
        'media.public_disk' => 'media_local',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'To jest główne wyjaśnienie karty odpowiedzi.',
    ]);

    Storage::disk('media_local')->put('question-explanations/'.$question->getKey().'/existing.png', 'old-image');

    QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/'.$question->getKey().'/existing.png',
            'alt_text' => 'Stary alt text',
            'is_active' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_asset.file_path', [
            UploadedFile::fake()->image('replacement.png', 1200, 800),
        ])
        ->set('data.explanation_asset.alt_text', 'Nowy alt text')
        ->set('data.explanation_asset.body', 'Nowy opis')
        ->call('save')
        ->assertHasNoErrors();

    $asset = $question->fresh()->referenceExplanationAsset;

    expect($asset)->not->toBeNull()
        ->and($asset?->question_id)->toBe($question->getKey())
        ->and($asset?->file_path)->toStartWith("question-explanations/{$question->getKey()}/")
        ->and($asset?->file_path)->toEndWith('.png');

    Storage::disk('media_local')->assertExists($asset->file_path);
});

test('admin users still need fallback body when both image and main explanation are empty', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_asset.file_path', ['question-explanations/reference-sign.webp'])
        ->set('data.explanation_asset.alt_text', 'Alt tekst grafiki')
        ->call('save')
        ->assertHasErrors([
            'explanation_asset.body' => 'Dodaj tekst zapasowy albo uzupełnij pole Wyjaśnienie.',
        ]);
});

test('admin users can see the reference material preview on the question edit page', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
        'filesystems.disks.media_local.url' => 'https://bulk.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/reference-preview.webp',
            'title' => 'Znak ostrzegawczy',
            'body' => 'To jest **materiał referencyjny** dla pytania.',
            'caption' => 'Krótki podpis pod blokiem',
            'alt_text' => 'Znak ostrzegawczy z podglądu',
            'is_active' => true,
        ]);

    $this->actingAs($admin)
        ->get(QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'))
        ->assertOk()
        ->assertSee('Pytanie i karta odpowiedzi', false)
        ->assertSee('To jeden formularz dla całej karty odpowiedzi. Kursant najpierw zobaczy pytanie, a po odpowiedzi dostanie jedno tłumaczenie z opcjonalną grafiką.', false)
        ->assertSee('Grafika karty', false)
        ->assertSee('Grafika aktywna', false)
        ->assertDontSee('To treść, którą zobaczy kursant wraz z wyjaśnieniem po odpowiedzi.', false)
        ->assertDontSee('Grafika do jednej karty odpowiedzi. Tekst tej karty bierze się z pola Wyjaśnienie, a gdy grafiki brak runtime pokaże przykładowy fallback.', false)
        ->assertSee('Podgląd karty odpowiedzi', false)
        ->assertSee('To podgląd gotowej karty odpowiedzi. Grafika z tego formularza połączy się tu z tekstem z pola Wyjaśnienie. Gdy grafiki brak, runtime pokaże przykładowy fallback.', false)
        ->assertDontSee('Podpis', false)
        ->assertSee('questionReferenceAssetPreview({', false)
        ->assertSee('window.questionReferenceAssetPreview = function (config)', false)
        ->assertSee('diskBaseUrls', false)
        ->assertSee('preview_url', false)
        ->assertSee('liveUploadUrl', false)
        ->assertSee('resolveLiveUploadUrlFromDom', false)
        ->assertSee('media_local', false)
        ->assertSee('bulk.example.test', false)
        ->assertDontSee('.replace(/&#039;/g', false);
});

test('admin users can access the question edit page with video frame timeline helper', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => 'V-000321',
        'prompt' => 'Czy na tym nagraniu widzisz zagrozenie?',
    ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/video/frame-preview.mp4',
            'poster_path' => 'questions/posters/frame-preview.webp',
            'duration_seconds' => 12,
            'width' => 1280,
            'height' => 720,
            'metadata' => [
                'asset_group' => 'question-video-preview',
                'source_role' => 'full',
            ],
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->videoFrame(4)
        ->label('Spójrz na pieszych')
        ->create([
            'x_percent' => 54.0,
            'y_percent' => 38.0,
            'tone' => 'danger',
        ]);

    $this->actingAs($admin)
        ->get(QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'))
        ->assertOk()
        ->assertSee('Ustawienia markera', false)
        ->assertSee('savedAnnotations', false)
        ->assertSee('Stopklatka filmu', false)
        ->assertSee('Zaawansowane (opcjonalnie)', false)
        ->assertSee('Lista markerów', false)
        ->assertSee('V-000321', false);
});

test('admin users can persist image and video frame markers from question edit form', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $imageQuestion = Question::factory()->create([
        'prompt' => 'Czy widzisz znak ostrzegawczy na obrazie?',
        'explanation' => 'Wyjasnienie obrazu.',
    ]);

    QuestionMedia::factory()
        ->for($imageQuestion)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/image/end-to-end-preview.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $imageQuestion->getRouteKey()])
        ->set('data.explanation_annotations', [
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'label',
                'tone' => 'warning',
                'label' => 'To jest kluczowy znak',
                'x_percent' => 44.5,
                'y_percent' => 29.0,
                'width_percent' => null,
                'height_percent' => null,
                'position' => 1,
                'is_active' => true,
            ],
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'text',
                'tone' => 'info',
                'label' => 'LEWA STRONA',
                'x_percent' => 18.0,
                'y_percent' => 22.0,
                'width_percent' => null,
                'height_percent' => null,
                'position' => 2,
                'is_active' => true,
            ],
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'circle',
                'tone' => 'danger',
                'label' => null,
                'x_percent' => 62.0,
                'y_percent' => 41.0,
                'width_percent' => 16.0,
                'height_percent' => 18.0,
                'position' => 3,
                'is_active' => true,
            ],
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'arrow',
                'tone' => 'info',
                'label' => null,
                'x_percent' => 55.0,
                'y_percent' => 36.0,
                'width_percent' => null,
                'height_percent' => null,
                'arrow_length_percent' => 24.0,
                'arrow_angle_degrees' => 315,
                'arrow_stroke_percent' => 1.4,
                'arrow_head_percent' => 4.2,
                'position' => 4,
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $imageAnnotations = $imageQuestion->fresh()->explanationAnnotations()
        ->orderBy('position')
        ->get();

    expect($imageAnnotations)->toHaveCount(4)
        ->and($imageAnnotations[0]->target_kind)->toBe('question_image')
        ->and($imageAnnotations[0]->annotation_type)->toBe('label')
        ->and($imageAnnotations[0]->label)->toBe('To jest kluczowy znak')
        ->and($imageAnnotations[1]->annotation_type)->toBe('text')
        ->and($imageAnnotations[1]->label)->toBe('LEWA STRONA')
        ->and($imageAnnotations[2]->annotation_type)->toBe('circle')
        ->and($imageAnnotations[2]->width_percent)->toBe(16.0)
        ->and($imageAnnotations[2]->height_percent)->toBe(18.0)
        ->and($imageAnnotations[3]->annotation_type)->toBe('arrow')
        ->and($imageAnnotations[3]->arrow_length_percent)->toBe(24.0)
        ->and($imageAnnotations[3]->arrow_angle_degrees)->toBe(315)
        ->and($imageAnnotations[3]->arrow_stroke_percent)->toBe(1.4)
        ->and($imageAnnotations[3]->arrow_head_percent)->toBe(4.2);

    $videoQuestion = Question::factory()->create([
        'prompt' => 'Czy na filmie widzisz sytuacje niebezpieczna?',
        'explanation' => 'Wyjasnienie filmu.',
    ]);

    QuestionMedia::factory()
        ->for($videoQuestion)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/video/end-to-end-preview.mp4',
            'poster_path' => 'questions/posters/end-to-end-preview.webp',
            'duration_seconds' => 15,
            'width' => 1280,
            'height' => 720,
        ]);

    Livewire::test(EditQuestion::class, ['record' => $videoQuestion->getRouteKey()])
        ->set('data.explanation_annotations', [
            [
                'target_kind' => 'video_frame',
                'frame_time_seconds' => 6,
                'annotation_type' => 'label',
                'tone' => 'danger',
                'label' => 'Sprawdz tor jazdy pojazdu',
                'x_percent' => 36.5,
                'y_percent' => 43.0,
                'width_percent' => null,
                'height_percent' => null,
                'position' => 1,
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $videoAnnotation = $videoQuestion->fresh()->explanationAnnotations()->first();

    expect($videoAnnotation)->not->toBeNull()
        ->and($videoAnnotation?->target_kind)->toBe('video_frame')
        ->and($videoAnnotation?->frame_time_seconds)->toBe(6)
        ->and($videoAnnotation?->annotation_type)->toBe('label')
        ->and($videoAnnotation?->label)->toBe('Sprawdz tor jazdy pojazdu');
});

test('admin users cannot save invalid arrow geometry data', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'prompt' => 'Czy strzalka wskazuje poprawny tor?',
        'explanation' => 'Wyjasnienie.',
    ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/image/arrow-validation.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_annotations', [
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'arrow',
                'tone' => 'warning',
                'label' => null,
                'x_percent' => 42.0,
                'y_percent' => 30.0,
                'width_percent' => null,
                'height_percent' => null,
                'arrow_length_percent' => 0,
                'arrow_angle_degrees' => 361,
                'arrow_stroke_percent' => 0.2,
                'arrow_head_percent' => 1.0,
                'position' => 1,
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasErrors();
});

test('admin users cannot save text marker without content', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'prompt' => 'Czy duzy tekst wskazuje poprawny obszar?',
        'explanation' => 'Wyjasnienie.',
    ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/image/text-validation.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_annotations', [
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'text',
                'tone' => 'info',
                'label' => null,
                'x_percent' => 30.0,
                'y_percent' => 40.0,
                'width_percent' => null,
                'height_percent' => null,
                'position' => 1,
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['explanation_annotations.0.label']);
});

test('admin users cannot save text marker with whitespace-only content', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'prompt' => 'Czy duzy tekst wskazuje poprawny obszar?',
        'explanation' => 'Wyjasnienie.',
    ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/image/text-validation-whitespace.webp',
            'mime_type' => 'image/webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
        ->set('data.explanation_annotations', [
            [
                'target_kind' => 'question_image',
                'frame_time_seconds' => null,
                'annotation_type' => 'text',
                'tone' => 'info',
                'label' => '   ',
                'x_percent' => 30.0,
                'y_percent' => 40.0,
                'width_percent' => null,
                'height_percent' => null,
                'position' => 1,
                'is_active' => true,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['explanation_annotations.0.label']);
});
