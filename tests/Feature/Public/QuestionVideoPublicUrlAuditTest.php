<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('filesystems.disks.public.url', 'https://prawkonaraz.pl/storage');
    config()->set('media.default_disk', 'public');
    config()->set('media.public_disk', 'public');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage');
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
    Storage::fake('public');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
    File::deleteDirectory(storage_path('app/testing-video-public-url-audit'));
});

test('question video public URL audit passes for reachable video and poster', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '7001',
        'prompt' => 'Jak powinien zachowac sie kierujacy?',
        'explanation' => 'Nalezy zachowac ostroznosc.',
    ]);

    Storage::disk('public')->put('questions/7001/clip.mp4', 'video');
    Storage::disk('public')->put('questions/7001/poster.webp', 'poster');

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/7001/clip.mp4',
            'poster_path' => 'questions/7001/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 12,
            'width' => 1280,
            'height' => 720,
        ]);

    Http::fake([
        'https://prawkonaraz.pl/storage/questions/7001/clip.mp4' => Http::response('', 206),
        'https://prawkonaraz.pl/storage/questions/7001/poster.webp' => Http::response('', 200),
    ]);

    $reportPath = storage_path('app/testing-video-public-url-audit/report.json');

    $this->artisan('seo:audit-question-video-public-urls', [
        '--report' => $reportPath,
        '--fail-on-errors' => true,
    ])
        ->expectsOutputToContain('Question videos: total=1 checks=2 ok=2 retried=0 errors=0 warnings=0')
        ->expectsOutputToContain('Question video public URL audit has no blocking errors.')
        ->assertSuccessful();

    $report = json_decode(File::get($reportPath), true);

    expect($report['summary']['videos'])->toBe(1)
        ->and($report['summary']['video_urls'])->toBe(1)
        ->and($report['summary']['poster_urls'])->toBe(1)
        ->and($report['summary']['ok'])->toBe(2)
        ->and($report['issue_counts']['errors'])->toBe(0);
});

test('question video public URL audit fails when poster is not reachable', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '7002',
        'prompt' => 'Czy mozesz kontynuowac jazde?',
        'explanation' => 'Nalezy ocenic sytuacje na drodze.',
    ]);

    Storage::disk('public')->put('questions/7002/clip.mp4', 'video');
    Storage::disk('public')->put('questions/7002/poster.webp', 'poster');

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/7002/clip.mp4',
            'poster_path' => 'questions/7002/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 9,
        ]);

    Http::fake([
        'https://prawkonaraz.pl/storage/questions/7002/clip.mp4' => Http::response('', 200),
        'https://prawkonaraz.pl/storage/questions/7002/poster.webp' => Http::response('', 404),
    ]);

    $this->artisan('seo:audit-question-video-public-urls', [
        '--fail-on-errors' => true,
    ])
        ->expectsOutputToContain('poster_http_unexpected_status')
        ->assertFailed();
});

test('question video public URL audit retries transient HTTP failures', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '7003',
        'prompt' => 'Czy wolno ominac pojazd?',
        'explanation' => 'Nalezy upewnic sie, ze manewr jest bezpieczny.',
    ]);

    Storage::disk('public')->put('questions/7003/clip.mp4', 'video');
    Storage::disk('public')->put('questions/7003/poster.webp', 'poster');

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/7003/clip.mp4',
            'poster_path' => 'questions/7003/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 10,
        ]);

    Http::fake([
        'https://prawkonaraz.pl/storage/questions/7003/clip.mp4' => Http::sequence()
            ->push('', 500)
            ->push('', 200),
        'https://prawkonaraz.pl/storage/questions/7003/poster.webp' => Http::response('', 200),
    ]);

    $this->artisan('seo:audit-question-video-public-urls', [
        '--retries' => 1,
        '--fail-on-errors' => true,
    ])
        ->expectsOutputToContain('Question videos: total=1 checks=2 ok=2 retried=1 errors=0 warnings=0')
        ->assertSuccessful();
});
