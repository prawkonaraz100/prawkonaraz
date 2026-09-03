<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('admin users can presign and confirm an image upload', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.public_disk' => 'r2',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-000101',
        ]);

    $presignResponse = $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 4096,
            'variant' => 'full',
        ])
        ->assertOk()
        ->assertJsonPath('data.question_id', $question->getKey())
        ->assertJsonPath('data.kind', 'image')
        ->assertJsonPath('data.variant', 'full')
        ->assertJsonPath('data.disk', 'r2')
        ->assertJsonPath('data.method', 'PUT');

    $path = (string) $presignResponse->json('data.path');
    $uploadToken = (string) $presignResponse->json('data.upload_token');

    expect($path)->toStartWith('media/questions/b/b-000101/image/full.');
    expect($path)->toEndWith('.webp');

    Storage::disk('r2')->put($path, 'image-binary');

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.confirm'), [
            'upload_token' => $uploadToken,
            'width' => 1280,
            'height' => 720,
            'sort_order' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.question_id', $question->getKey())
        ->assertJsonPath('data.kind', 'image')
        ->assertJsonPath('data.variant', 'full')
        ->assertJsonPath('data.bytes', strlen('image-binary'))
        ->assertJsonPath('data.url', 'https://media.example.test/'.$path)
        ->assertJsonPath('data.poster_url', null);

    $this->assertDatabaseHas('question_media', [
        'question_id' => $question->getKey(),
        'kind' => 'image',
        'disk' => 'r2',
        'path' => $path,
        'variant' => 'full',
        'bytes' => strlen('image-binary'),
        'sort_order' => 1,
    ]);
});

test('admin users can attach a poster when confirming a video upload', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.public_disk' => 'r2',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-000202',
        ]);

    $videoPresign = $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'video',
            'mime_type' => 'video/mp4',
            'bytes' => 8192,
            'variant' => 'full',
        ])
        ->assertOk();

    $posterPresign = $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 1024,
            'variant' => 'poster',
        ])
        ->assertOk();

    $videoPath = (string) $videoPresign->json('data.path');
    $posterPath = (string) $posterPresign->json('data.path');

    Storage::disk('r2')->put($videoPath, 'video-binary');
    Storage::disk('r2')->put($posterPath, 'poster-binary');

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.confirm'), [
            'upload_token' => $videoPresign->json('data.upload_token'),
            'poster_upload_token' => $posterPresign->json('data.upload_token'),
            'width' => 1280,
            'height' => 720,
            'duration_seconds' => 12,
            'metadata' => [
                'codec' => 'h264',
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.kind', 'video')
        ->assertJsonPath('data.variant', 'full')
        ->assertJsonPath('data.poster_path', $posterPath)
        ->assertJsonPath('data.poster_url', 'https://media.example.test/'.$posterPath)
        ->assertJsonPath('data.bytes', strlen('video-binary'))
        ->assertJsonPath('data.duration_seconds', 12)
        ->assertJsonPath('data.metadata.codec', 'h264');

    $this->assertDatabaseHas('question_media', [
        'question_id' => $question->getKey(),
        'kind' => 'video',
        'path' => $videoPath,
        'poster_path' => $posterPath,
        'variant' => 'full',
        'bytes' => strlen('video-binary'),
        'duration_seconds' => 12,
    ]);
});

test('presign is idempotent for the same payload and key', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => 'B-000303',
    ]);

    $firstResponse = $this->actingAs($admin)
        ->withHeader('Idempotency-Key', 'media-upload-1')
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 2048,
            'variant' => 'full',
        ])
        ->assertOk();

    $secondResponse = $this->actingAs($admin)
        ->withHeader('Idempotency-Key', 'media-upload-1')
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 2048,
            'variant' => 'full',
        ])
        ->assertOk();

    expect($secondResponse->json('data.upload_token'))->toBe($firstResponse->json('data.upload_token'));
    expect($secondResponse->json('data.path'))->toBe($firstResponse->json('data.path'));
});

test('non admin users cannot access admin media endpoints', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();
    $media = QuestionMedia::factory()->for($question)->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 1024,
            'variant' => 'full',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('api.v1.admin.media.confirm'), [
            'upload_token' => 'fake-token',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patchJson(route('api.v1.admin.questions.media.reorder', $question), [
            'media_ids' => [$media->getKey()],
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->deleteJson(route('api.v1.admin.media.destroy', $media))
        ->assertForbidden();
});

test('admin media presign validates mime types and confirm requires a valid token', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'video',
            'mime_type' => 'video/quicktime',
            'bytes' => 1024,
            'variant' => 'full',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mime_type']);

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.confirm'), [
            'upload_token' => 'missing-token',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_token']);
});

test('admin users can reorder media for a question', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    $media = QuestionMedia::factory()
        ->count(3)
        ->for($question)
        ->sequence(
            ['sort_order' => 0, 'path' => 'questions/one.webp'],
            ['sort_order' => 1, 'path' => 'questions/two.webp'],
            ['sort_order' => 2, 'path' => 'questions/three.webp'],
        )
        ->create();

    $reorderedIds = [
        $media[2]->getKey(),
        $media[0]->getKey(),
        $media[1]->getKey(),
    ];

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.media.reorder', $question), [
            'media_ids' => $reorderedIds,
        ])
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $reorderedIds[0])
        ->assertJsonPath('data.items.1.id', $reorderedIds[1])
        ->assertJsonPath('data.items.2.id', $reorderedIds[2])
        ->assertJsonPath('data.items.0.sort_order', 0)
        ->assertJsonPath('data.items.1.sort_order', 1)
        ->assertJsonPath('data.items.2.sort_order', 2);

    expect(QuestionMedia::query()->findOrFail($reorderedIds[0])->sort_order)->toBe(0);
    expect(QuestionMedia::query()->findOrFail($reorderedIds[1])->sort_order)->toBe(1);
    expect(QuestionMedia::query()->findOrFail($reorderedIds[2])->sort_order)->toBe(2);
});

test('admin users can delete media and clean up storage objects', function () {
    Storage::fake('r2');

    config([
        'media.public_disk' => 'r2',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    $media = QuestionMedia::factory()
        ->video()
        ->for($question)
        ->create([
            'disk' => 'r2',
            'path' => 'media/questions/b/b-010/video/full.asset.mp4',
            'poster_path' => 'media/questions/b/b-010/image/poster.asset.webp',
            'sort_order' => 0,
        ]);

    $otherMedia = QuestionMedia::factory()
        ->for($question)
        ->create([
            'disk' => 'r2',
            'path' => 'media/questions/b/b-010/image/full.asset.webp',
            'sort_order' => 1,
        ]);

    Storage::disk('r2')->put($media->path, 'video-binary');
    Storage::disk('r2')->put($media->poster_path, 'poster-binary');
    Storage::disk('r2')->put($otherMedia->path, 'image-binary');

    $this->actingAs($admin)
        ->deleteJson(route('api.v1.admin.media.destroy', $media))
        ->assertOk()
        ->assertJsonPath('data.deleted', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $otherMedia->getKey())
        ->assertJsonPath('data.items.0.sort_order', 0);

    $this->assertDatabaseMissing('question_media', [
        'id' => $media->getKey(),
    ]);

    $this->assertDatabaseHas('question_media', [
        'id' => $otherMedia->getKey(),
        'sort_order' => 0,
    ]);

    Storage::disk('r2')->assertMissing($media->path);
    Storage::disk('r2')->assertMissing($media->poster_path);
    Storage::disk('r2')->assertExists($otherMedia->path);
});
