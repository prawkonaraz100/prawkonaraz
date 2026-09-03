<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionTopic;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-manifest-imports'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-manifest-imports'));
});

test('catalog manifest import uploads ready assets and persists catalog data', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-manifest-imports/batch-success');
    $mediaDirectory = $directory.'/media/B/000001';
    File::ensureDirectoryExists($mediaDirectory);

    File::put($mediaDirectory.'/full.webp', 'image-full');
    File::put($mediaDirectory.'/thumb.webp', 'image-thumb');
    File::put($mediaDirectory.'/clip.mp4', 'video-full');
    File::put($mediaDirectory.'/poster.webp', 'video-poster');

    $manifest = [
        'batch_id' => 'manifest-success',
        'category_id' => 'B',
        'category_name' => 'Kategoria B',
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
        'source' => 'ready-assets-import',
    ];

    $csv = implode("\n", [
        'external_id,category_id,question_text,answer_a,answer_b,answer_c,correct_answer,explanation,points,difficulty,question_type,image_path,thumb_path,video_path,poster_path',
        'B-100,B,"Czy przed ruszeniem nalezy zapinac pasy?","Tak","Nie","Tylko poza miastem","A","Tak, pasy trzeba zapinac.",3,2,single_choice,"B/000001/full.webp","B/000001/thumb.webp","B/000001/clip.mp4","B/000001/poster.webp"',
    ]);

    File::put($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    File::put($directory.'/questions.csv', $csv);

    $reportPath = $directory.'/report.json';

    $this->artisan('catalog:import-manifest', [
        'path' => $directory.'/manifest.json',
        '--report' => $reportPath,
    ])->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(1);
    expect(Question::query()->count())->toBe(1);
    expect(QuestionMedia::query()->count())->toBe(3);

    $question = Question::query()->firstOrFail();
    $media = $question->media()->orderBy('sort_order')->get();

    expect($question->licenseCategory->code)->toBe('B');
    expect($question->external_id)->toBe('B-100');
    expect($question->questionTopic)->toBeInstanceOf(QuestionTopic::class);
    expect($question->questionTopic?->key)->toBe('vehicle_operation_and_safety');
    expect($media)->toHaveCount(3);
    expect($media[0]->kind)->toBe('image');
    expect($media[0]->variant)->toBe('full');
    expect($media[0]->disk)->toBe('r2');
    expect($media[1]->variant)->toBe('thumb');
    expect($media[2]->kind)->toBe('video');
    expect($media[2]->poster_path)->not->toBeNull();
    expect(Storage::disk('r2')->exists($media[0]->path))->toBeTrue();
    expect(Storage::disk('r2')->exists($media[1]->path))->toBeTrue();
    expect(Storage::disk('r2')->exists($media[2]->path))->toBeTrue();
    expect(Storage::disk('r2')->exists($media[2]->poster_path))->toBeTrue();

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['batch_id'])->toBe('manifest-success');
    expect($report['asset_plan_total'])->toBe(4);
    expect($report['uploaded_assets_total'])->toBe(4);
    expect($report['questions_created'])->toBe(1);
    expect($report['media_created'])->toBe(3);
    expect($report['errors_count'])->toBe(0);
});

test('catalog manifest import supports dry run without uploading or writing to the database', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-manifest-imports/batch-dry-run');
    $mediaDirectory = $directory.'/media/B/000002';
    File::ensureDirectoryExists($mediaDirectory);

    File::put($mediaDirectory.'/full.webp', 'image-full');

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => 'manifest-dry-run',
        'category_id' => 'B',
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,question_text,answer_a,answer_b,correct_answer,question_type,image_path',
        'B-101,"Czy nalezy zachowac ostroznosc?","Tak","Nie","A",boolean,"B/000002/full.webp"',
    ]));

    $this->artisan('catalog:import-manifest', [
        'path' => $directory.'/manifest.json',
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(0);
    expect(Question::query()->count())->toBe(0);
    expect(QuestionMedia::query()->count())->toBe(0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
});

test('catalog manifest import fails the batch when a referenced media file is missing', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-manifest-imports/batch-invalid');
    File::ensureDirectoryExists($directory.'/media/B/000003');

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => 'manifest-invalid',
        'category_id' => 'B',
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,question_text,answer_a,answer_b,correct_answer,question_type,image_path',
        'B-102,"Czy to pytanie ma brakujacy obraz?","Tak","Nie","A",boolean,"B/000003/missing.webp"',
    ]));

    $reportPath = $directory.'/invalid-report.json';

    $this->artisan('catalog:import-manifest', [
        'path' => $directory.'/manifest.json',
        '--report' => $reportPath,
    ])->assertFailed();

    expect(LicenseCategory::query()->count())->toBe(0);
    expect(Question::query()->count())->toBe(0);
    expect(QuestionMedia::query()->count())->toBe(0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['batch_id'])->toBe('manifest-invalid');
    expect($report['errors_count'])->toBe(1);
    expect($report['errors'][0]['path'])->toContain('rows.0');
});

test('catalog manifest import optimizes oversized staged images when ffmpeg is available', function () {
    if (! ffmpegAvailableForManifestImportTests()) {
        $this->markTestSkipped('ffmpeg is required for oversized image optimization test.');
    }

    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
        'media.ffmpeg_binary' => 'ffmpeg',
        'media.max_bytes.image' => 100_000,
    ]);

    $directory = storage_path('app/testing-manifest-imports/batch-oversized-image');
    $mediaDirectory = $directory.'/media/B/000004';
    File::ensureDirectoryExists($mediaDirectory);

    createLargeTestPatternImage($mediaDirectory.'/full.jpg');

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => 'manifest-oversized-image',
        'category_id' => 'B',
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
        'source' => 'oversized-image-test',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,question_text,answer_a,answer_b,correct_answer,question_type,image_path',
        'B-103,"Czy obraz moze zostac odchudzony przy imporcie?","Tak","Nie","A",boolean,"B/000004/full.jpg"',
    ]));

    $reportPath = $directory.'/oversized-report.json';

    $this->artisan('catalog:import-manifest', [
        'path' => $directory.'/manifest.json',
        '--report' => $reportPath,
    ])->assertSuccessful();

    $media = QuestionMedia::query()->sole();
    $report = json_decode((string) File::get($reportPath), true);

    expect($report['errors_count'])->toBe(0);
    expect($media->mime_type)->toBe('image/webp');
    expect($media->bytes)->toBeLessThanOrEqual(100_000);
    expect($media->path)->toEndWith('.webp');
    expect(Storage::disk('r2')->exists($media->path))->toBeTrue();
});

function ffmpegAvailableForManifestImportTests(): bool
{
    try {
        $process = new Process(['ffmpeg', '-version']);
        $process->run();

        return $process->isSuccessful();
    } catch (Throwable) {
        return false;
    }
}

function createLargeTestPatternImage(string $path): void
{
    File::ensureDirectoryExists(dirname($path));

    $process = new Process([
        'ffmpeg',
        '-y',
        '-f',
        'lavfi',
        '-i',
        'testsrc2=s=3840x2160',
        '-frames:v',
        '1',
        '-update',
        '1',
        $path,
    ]);
    $process->setTimeout(null);
    $process->mustRun();
}
