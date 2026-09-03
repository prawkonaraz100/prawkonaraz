<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\VideoMetadataProbe;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('media.default_disk', 'public');
    config()->set('media.public_disk', 'public');

    $this->app->bind(VideoMetadataProbe::class, fn (): VideoMetadataProbe => new class extends VideoMetadataProbe
    {
        public function probe(string $sourcePath, int $timeoutSeconds = 20): array
        {
            return [
                'duration_seconds' => 13,
                'width' => 1920,
                'height' => 1080,
                'source' => 'test-ffprobe',
                'error' => null,
            ];
        }
    });
});

function questionVideoMediaForEnrichment(array $overrides = []): QuestionMedia
{
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => fake()->unique()->numberBetween(10_000, 99_999),
            'prompt' => 'Czy w tej sytuacji masz obowiazek zatrzymac pojazd?',
            'explanation' => 'Tak, nalezy zatrzymac pojazd.',
        ]);

    Storage::disk('public')->put('questions/'.$question->external_id.'/clip.mp4', 'video-bytes');

    return QuestionMedia::factory()
        ->video()
        ->create(array_replace([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/'.$question->external_id.'/clip.mp4',
            'poster_path' => 'questions/'.$question->external_id.'/poster.webp',
            'mime_type' => null,
            'bytes' => null,
            'duration_seconds' => null,
            'width' => null,
            'height' => null,
            'metadata' => null,
        ], $overrides));
}

test('question video metadata enrichment previews changes without writing', function () {
    $media = questionVideoMediaForEnrichment();
    $reportPath = storage_path('app/testing/question-video-metadata-preview.json');

    $this->artisan('seo:enrich-question-video-metadata', [
        '--report' => $reportPath,
    ])
        ->expectsOutputToContain('Tryb: PREVIEW')
        ->expectsOutputToContain('candidates=1')
        ->expectsOutputToContain('To byl preview.')
        ->assertSuccessful();

    $report = json_decode((string) File::get($reportPath), true);
    $fresh = $media->fresh();

    expect($report['would_update_media'])->toBe(1)
        ->and($fresh->duration_seconds)->toBeNull()
        ->and($fresh->width)->toBeNull()
        ->and($fresh->height)->toBeNull()
        ->and($fresh->bytes)->toBeNull()
        ->and($fresh->mime_type)->toBeNull();
});

test('question video metadata enrichment writes missing fields only when requested', function () {
    $media = questionVideoMediaForEnrichment();

    $this->artisan('seo:enrich-question-video-metadata', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('updated=1')
        ->expectsOutputToContain('[field] duration_seconds=1')
        ->assertSuccessful();

    $fresh = $media->fresh();

    expect($fresh->duration_seconds)->toBe(13)
        ->and($fresh->width)->toBe(1920)
        ->and($fresh->height)->toBe(1080)
        ->and($fresh->bytes)->toBe(strlen('video-bytes'))
        ->and($fresh->mime_type)->toBe('video/mp4')
        ->and($fresh->metadata['video_metadata_enrichment']['source'])->toBe('test-ffprobe')
        ->and($fresh->metadata['video_metadata_enrichment']['fields'])->toEqualCanonicalizing([
            'duration_seconds',
            'width',
            'height',
            'bytes',
            'mime_type',
        ]);
});

test('question video metadata enrichment does not overwrite complete metadata without force', function () {
    $media = questionVideoMediaForEnrichment([
        'mime_type' => 'video/mp4',
        'bytes' => 123,
        'duration_seconds' => 8,
        'width' => 640,
        'height' => 360,
    ]);

    $this->artisan('seo:enrich-question-video-metadata', [
        '--write' => true,
    ])
        ->expectsOutputToContain('candidates=0')
        ->assertSuccessful();

    $fresh = $media->fresh();

    expect($fresh->duration_seconds)->toBe(8)
        ->and($fresh->width)->toBe(640)
        ->and($fresh->height)->toBe(360)
        ->and($fresh->bytes)->toBe(123);
});

test('question video metadata enrichment can overwrite complete metadata with force', function () {
    $media = questionVideoMediaForEnrichment([
        'mime_type' => 'video/mp4',
        'bytes' => 123,
        'duration_seconds' => 8,
        'width' => 640,
        'height' => 360,
    ]);

    $this->artisan('seo:enrich-question-video-metadata', [
        '--write' => true,
        '--force' => true,
    ])
        ->expectsOutputToContain('force=yes')
        ->expectsOutputToContain('updated=1')
        ->assertSuccessful();

    $fresh = $media->fresh();

    expect($fresh->duration_seconds)->toBe(13)
        ->and($fresh->width)->toBe(1920)
        ->and($fresh->height)->toBe(1080)
        ->and($fresh->bytes)->toBe(strlen('video-bytes'));
});
