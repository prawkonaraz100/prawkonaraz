<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('admin users can create a question through admin api', function () {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(route('api.v1.admin.questions.store'), [
            'category_code' => 'b',
            'external_id' => 'B-500',
            'prompt' => 'Czy wolno parkowac na przejsciu dla pieszych?',
            'explanation' => 'Nie, poniewaz ogranicza to widocznosc i zagraza bezpieczenstwu.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'option_c' => 'Tylko w nocy',
            'correct_answer' => 'B',
            'difficulty' => 3,
            'points' => 2,
            'question_type' => 'single_choice',
            'source' => 'official',
            'media' => [
                [
                    'kind' => 'image',
                    'disk' => 'public',
                    'path' => 'questions/b/b-500-full.webp',
                    'mime_type' => 'image/webp',
                    'bytes' => 120_000,
                    'variant' => 'full',
                    'width' => 1280,
                    'height' => 720,
                    'sort_order' => 0,
                ],
                [
                    'kind' => 'video',
                    'disk' => 'public',
                    'path' => 'questions/b/b-500.mp4',
                    'poster_path' => 'questions/b/posters/b-500.webp',
                    'mime_type' => 'video/mp4',
                    'bytes' => 2_400_000,
                    'duration_seconds' => 16,
                    'variant' => 'full',
                    'sort_order' => 1,
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('meta.action', 'created')
        ->assertJsonPath('data.question.license_category.id', $category->getKey())
        ->assertJsonPath('data.question.external_id', 'B-500')
        ->assertJsonPath('data.question.correct_answer', 'B')
        ->assertJsonPath('data.question.media.0.url', 'https://media.example.test/questions/b/b-500-full.webp')
        ->assertJsonPath('data.question.media.1.poster_url', 'https://media.example.test/questions/b/posters/b-500.webp');

    $this->assertDatabaseHas('questions', [
        'license_category_id' => $category->getKey(),
        'external_id' => 'B-500',
        'correct_answer' => 'b',
    ]);

    $this->assertDatabaseHas('question_media', [
        'path' => 'questions/b/b-500.mp4',
        'poster_path' => 'questions/b/posters/b-500.webp',
        'kind' => 'video',
    ]);
});

test('admin question api can update an existing question and sync media', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-700',
            'prompt' => 'Stare pytanie',
            'correct_answer' => 'a',
        ]);

    Storage::disk('public')->put('questions/b/old-video.mp4', 'video');
    Storage::disk('public')->put('questions/b/posters/old-video.webp', 'poster');

    $oldMedia = QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/old-video.mp4',
            'poster_path' => 'questions/b/posters/old-video.webp',
            'sort_order' => 0,
        ]);

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.questions.store'), [
            'license_category_id' => $category->getKey(),
            'external_id' => 'B-700',
            'prompt' => 'Nowe pytanie o pierwszenstwo',
            'explanation' => 'Nowe wyjasnienie.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'difficulty' => 4,
            'points' => 3,
            'question_type' => 'boolean',
            'media' => [
                [
                    'kind' => 'image',
                    'disk' => 'public',
                    'path' => 'questions/b/new-image.webp',
                    'mime_type' => 'image/webp',
                    'bytes' => 80_000,
                    'variant' => 'full',
                    'sort_order' => 0,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('meta.action', 'updated')
        ->assertJsonPath('data.question.prompt', 'Nowe pytanie o pierwszenstwo')
        ->assertJsonCount(1, 'data.question.media');

    $question->refresh();

    expect($question->prompt)->toBe('Nowe pytanie o pierwszenstwo');
    expect($question->question_type)->toBe('boolean');

    $this->assertDatabaseMissing('question_media', [
        'id' => $oldMedia->getKey(),
    ]);

    Storage::disk('public')->assertMissing('questions/b/old-video.mp4');
    Storage::disk('public')->assertMissing('questions/b/posters/old-video.webp');
});

test('admin question api leaves media untouched when payload omits media array', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create();

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-800',
        ]);

    $media = QuestionMedia::factory()
        ->for($question)
        ->create([
            'path' => 'questions/b/existing.webp',
            'sort_order' => 0,
        ]);

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.questions.store'), [
            'question_id' => $question->getKey(),
            'prompt' => 'Pytanie po aktualizacji',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'difficulty' => 2,
            'points' => 1,
            'question_type' => 'boolean',
        ])
        ->assertOk()
        ->assertJsonPath('meta.action', 'updated')
        ->assertJsonCount(1, 'data.question.media');

    $this->assertDatabaseHas('question_media', [
        'id' => $media->getKey(),
        'question_id' => $question->getKey(),
    ]);
});

test('non admin users cannot access admin question api', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.admin.questions.store'), [
            'license_category_id' => $category->getKey(),
            'prompt' => 'Czy wolno zawracac na autostradzie?',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ])
        ->assertForbidden();
});

test('admin question api validates boolean questions and media rules', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create();

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.questions.store'), [
            'license_category_id' => $category->getKey(),
            'prompt' => 'Czy mozna wybrac odpowiedz C?',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'c',
            'question_type' => 'boolean',
            'media' => [
                [
                    'kind' => 'image',
                    'disk' => 'public',
                    'path' => 'questions/b/bad-image.webp',
                    'poster_path' => 'questions/b/should-not-exist.webp',
                    'mime_type' => 'image/gif',
                    'bytes' => 10_000,
                    'variant' => 'poster',
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'correct_answer',
            'media.0.poster_path',
            'media.0.mime_type',
        ]);
});
