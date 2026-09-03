<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionSignLanguageAsset;
use Illuminate\Support\Facades\File;
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
    File::deleteDirectory(storage_path('app/testing-media-readiness'));
});

test('question media readiness audit passes for stored image video and pjm assets', function () {
    $category = LicenseCategory::factory()->categoryB()->create();

    $imageQuestion = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '1001',
        'prompt' => 'Czy widoczny znak wymaga zatrzymania?',
        'explanation' => 'Tak, kierujący musi zatrzymać pojazd przed znakiem.',
    ]);
    Storage::disk('public')->put('questions/1001/full.webp', 'image');
    QuestionMedia::factory()->create([
        'question_id' => $imageQuestion->getKey(),
        'kind' => 'image',
        'disk' => 'public',
        'path' => 'questions/1001/full.webp',
        'poster_path' => null,
        'mime_type' => 'image/webp',
        'width' => 1280,
        'height' => 720,
    ]);

    $videoQuestion = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '1002',
        'prompt' => 'Jak powinieneś zachować się w tej sytuacji?',
        'explanation' => 'Należy zachować szczególną ostrożność i ustąpić pierwszeństwa.',
    ]);
    Storage::disk('public')->put('questions/1002/clip.mp4', 'video');
    Storage::disk('public')->put('questions/1002/poster.webp', 'poster');
    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $videoQuestion->getKey(),
            'disk' => 'public',
            'path' => 'questions/1002/clip.mp4',
            'poster_path' => 'questions/1002/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 12,
            'width' => 1280,
            'height' => 720,
        ]);

    Storage::disk('public')->put('pjm/standard/1002.mp4', 'pjm-video');
    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '1002',
        'disk' => 'public',
        'path' => 'pjm/standard/1002.mp4',
    ]);

    $reportPath = storage_path('app/testing-media-readiness/report.json');

    $this->artisan('seo:audit-question-media-readiness', [
        '--report' => $reportPath,
        '--fail-on-errors' => true,
    ])
        ->expectsOutputToContain('Question media: total=2 images=1 videos=1')
        ->expectsOutputToContain('Media sitemap readiness audit has no blocking errors.')
        ->assertSuccessful();

    $report = json_decode(File::get($reportPath), true);

    expect($report['readiness']['image_sitemap']['status'])->toBe('ready')
        ->and($report['readiness']['video_sitemap']['status'])->toBe('ready_for_implementation')
        ->and($report['question_media']['image_sitemap_candidates'])->toBe(2)
        ->and($report['question_media']['video_sitemap_candidates'])->toBe(1);
});

test('question media readiness audit fails when a public video has no poster', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '1003',
        'prompt' => 'Jak zachować się przed przejściem?',
        'explanation' => 'Należy obserwować otoczenie i zachować ostrożność.',
    ]);

    Storage::disk('public')->put('questions/1003/clip.mp4', 'video');
    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/1003/clip.mp4',
            'poster_path' => null,
            'mime_type' => 'video/mp4',
            'duration_seconds' => 9,
        ]);

    $this->artisan('seo:audit-question-media-readiness', [
        '--fail-on-errors' => true,
    ])
        ->expectsOutputToContain('video_missing_poster')
        ->assertFailed();
});
