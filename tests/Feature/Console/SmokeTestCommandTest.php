<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

test('ops smoke test validates public api endpoints and session flow', function () {
    Config::set('health.monitor_backup', false);
    Config::set('app.url', 'http://localhost');

    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create();

    $this->artisan('ops:smoke-test')
        ->expectsOutputToContain('Smoke test status: OK')
        ->expectsOutputToContain('[OK] health_api')
        ->expectsOutputToContain('[OK] categories_api')
        ->expectsOutputToContain('[OK] session_flow')
        ->expectsOutputToContain('[OK] dashboard_metrics')
        ->assertSuccessful();
});

test('ops smoke test can require image and video assets', function () {
    Storage::fake('public');

    Config::set('health.monitor_backup', false);
    Config::set('app.url', 'http://localhost');
    Config::set('media.public_disk', 'public');
    Config::set('media.public_base_url', 'https://media.example.test');

    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'sort_order' => 1,
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-001',
        ]);

    Storage::disk('public')->put('questions/b-001.webp', 'image');
    Storage::disk('public')->put('questions/b-001.mp4', 'video');

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b-001.webp',
            'mime_type' => 'image/webp',
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/b-001.mp4',
            'poster_path' => null,
            'mime_type' => 'video/mp4',
        ]);

    $this->artisan('ops:smoke-test', ['--require-media' => true])
        ->expectsOutputToContain('[OK] media_assets')
        ->assertSuccessful();
});

test('ops smoke test fails cleanly when there is no active content to validate', function () {
    Config::set('health.monitor_backup', false);
    Config::set('app.url', 'http://localhost');

    $this->artisan('ops:smoke-test')
        ->expectsOutputToContain('Smoke test status: FAILED')
        ->expectsOutputToContain('[FAIL] categories_api')
        ->expectsOutputToContain('[FAIL] session_flow')
        ->assertFailed();
});
