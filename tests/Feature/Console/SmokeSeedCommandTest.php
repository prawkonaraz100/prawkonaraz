<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

test('ops seed smoke data provisions deterministic category questions and media', function () {
    Storage::fake('public');

    Config::set('media.public_disk', 'public');
    Config::set('media.default_disk', 'public');

    $this->artisan('ops:seed-smoke-data')
        ->expectsOutputToContain('Smoke dataset gotowy.')
        ->expectsOutputToContain('Pytania: 2 | Media: 2')
        ->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(1);
    expect(Question::query()->count())->toBe(2);
    expect(QuestionMedia::query()->count())->toBe(2);

    $imageQuestion = Question::query()->where('external_id', 'SMOKE-B-IMAGE-001')->firstOrFail();
    $videoQuestion = Question::query()->where('external_id', 'SMOKE-B-VIDEO-001')->firstOrFail();
    $imageMedia = $imageQuestion->media()->firstOrFail();
    $videoMedia = $videoQuestion->media()->firstOrFail();

    expect($imageQuestion->licenseCategory->code)->toBe('B');
    expect($imageMedia->kind)->toBe('image');
    expect($imageMedia->disk)->toBe('public');
    expect($imageMedia->path)->toBe('smoke/b/image/full.webp');
    expect($videoMedia->kind)->toBe('video');
    expect($videoMedia->poster_path)->toBe('smoke/b/video/poster.webp');
    expect(Storage::disk('public')->exists('smoke/b/image/full.webp'))->toBeTrue();
    expect(Storage::disk('public')->exists('smoke/b/video/clip.mp4'))->toBeTrue();
    expect(Storage::disk('public')->exists('smoke/b/video/poster.webp'))->toBeTrue();
});

test('ops seed smoke data is idempotent and enables media smoke verification', function () {
    Storage::fake('public');

    Config::set('health.monitor_backup', false);
    Config::set('app.url', 'http://localhost');
    Config::set('media.public_disk', 'public');
    Config::set('media.default_disk', 'public');
    Config::set('media.public_base_url', 'https://media.example.test');

    $this->artisan('ops:seed-smoke-data')->assertSuccessful();
    $this->artisan('ops:seed-smoke-data')->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(1);
    expect(Question::query()->count())->toBe(2);
    expect(QuestionMedia::query()->count())->toBe(2);

    $this->artisan('ops:smoke-test', ['--require-media' => true])
        ->expectsOutputToContain('Smoke test status: OK')
        ->expectsOutputToContain('[OK] media_assets')
        ->assertSuccessful();
});

test('ops smoke test prefers deterministic smoke media over broken legacy assets', function () {
    Storage::fake('public');

    Config::set('health.monitor_backup', false);
    Config::set('app.url', 'http://localhost');
    Config::set('media.public_disk', 'public');
    Config::set('media.default_disk', 'public');
    Config::set('media.public_base_url', 'https://media.example.test');

    $category = LicenseCategory::factory()->create([
        'code' => 'A',
        'sort_order' => 1,
    ]);

    $legacyQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'LEGACY-BROKEN-IMAGE-001',
        ]);

    QuestionMedia::factory()
        ->for($legacyQuestion)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'legacy/missing-image.webp',
            'mime_type' => 'image/webp',
            'metadata' => [
                'purpose' => 'legacy',
            ],
        ]);

    QuestionMedia::factory()
        ->for($legacyQuestion)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'legacy/missing-video.mp4',
            'poster_path' => null,
            'mime_type' => 'video/mp4',
            'metadata' => [
                'purpose' => 'legacy',
            ],
        ]);

    $this->artisan('ops:seed-smoke-data')->assertSuccessful();

    $this->artisan('ops:smoke-test', ['--require-media' => true])
        ->expectsOutputToContain('Smoke test status: OK')
        ->expectsOutputToContain('[OK] media_assets')
        ->assertSuccessful();
});
