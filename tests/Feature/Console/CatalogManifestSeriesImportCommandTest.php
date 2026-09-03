<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-manifest-series'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-manifest-series'));
});

test('catalog manifest series import processes multiple chunk directories and aggregates the result', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $seriesDirectory = storage_path('app/testing-manifest-series/success');
    makeManifestChunk(
        $seriesDirectory.'/chunk-0001',
        'series-batch-1',
        '501',
        'Pierwsze pytanie z serii',
        'shared/501/full.jpg',
        'image-one',
    );
    makeManifestChunk(
        $seriesDirectory.'/chunk-0002',
        'series-batch-2',
        '502',
        'Drugie pytanie z serii',
        'shared/502/full.jpg',
        'image-two',
    );

    $reportPath = $seriesDirectory.'/series-import-report.json';

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--skip-sitemap' => true,
        '--report' => $reportPath,
    ])->assertSuccessful();

    $summary = json_decode((string) File::get($reportPath), true);

    expect($summary['status'])->toBe('ok');
    expect($summary['chunks_discovered'])->toBe(2);
    expect($summary['chunks_selected'])->toBe(2);
    expect($summary['chunks_succeeded'])->toBe(2);
    expect($summary['chunks_failed'])->toBe(0);
    expect($summary['questions_total'])->toBe(2);
    expect($summary['media_total'])->toBe(2);
    expect($summary['asset_plan_total'])->toBe(2);
    expect($summary['uploaded_assets_total'])->toBe(2);
    expect($summary['stopped_reason'])->toBe('completed');
    expect(Question::query()->count())->toBe(2);
    expect(LicenseCategory::query()->count())->toBe(1);
    expect($summary['chunks'][0])->not->toHaveKey('report');
    $storedPaths = Storage::disk('r2')->allFiles('media/questions/b');

    expect($storedPaths)->toHaveCount(2);
    expect($storedPaths)->each->toStartWith('media/questions/b/');
});

test('catalog manifest series import can continue after a failed chunk when requested', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $seriesDirectory = storage_path('app/testing-manifest-series/continue');
    makeManifestChunk(
        $seriesDirectory.'/chunk-0001',
        'series-batch-1',
        '601',
        'Pierwsze pytanie z serii',
        'shared/601/full.jpg',
        'image-one',
    );
    makeManifestChunk(
        $seriesDirectory.'/chunk-0002',
        'series-batch-2',
        '602',
        'Drugie pytanie z serii',
        'shared/602/full.jpg',
        null,
    );
    makeManifestChunk(
        $seriesDirectory.'/chunk-0003',
        'series-batch-3',
        '603',
        'Trzecie pytanie z serii',
        'shared/603/full.jpg',
        'image-three',
    );

    $reportPath = $seriesDirectory.'/series-import-report.json';

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--continue-on-error' => true,
        '--report' => $reportPath,
    ])->assertFailed();

    $summary = json_decode((string) File::get($reportPath), true);

    expect($summary['status'])->toBe('failed');
    expect($summary['chunks_discovered'])->toBe(3);
    expect($summary['chunks_selected'])->toBe(3);
    expect($summary['chunks_succeeded'])->toBe(2);
    expect($summary['chunks_failed'])->toBe(1);
    expect($summary['errors_count'])->toBe(1);
    expect($summary['stopped_reason'])->toBe('completed_with_errors');
    expect(Question::query()->count())->toBe(2);
    expect(collect($summary['chunks'])->pluck('status')->all())->toBe(['ok', 'failed', 'ok']);
});

test('catalog manifest series import can resume from a previous success report', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $seriesDirectory = storage_path('app/testing-manifest-series/resume');
    makeManifestChunk(
        $seriesDirectory.'/chunk-0001',
        'series-batch-1',
        '701',
        'Pierwsze pytanie z serii',
        'shared/701/full.jpg',
        'image-one',
    );
    makeManifestChunk(
        $seriesDirectory.'/chunk-0002',
        'series-batch-2',
        '702',
        'Drugie pytanie z serii',
        'shared/702/full.jpg',
        'image-two',
    );
    makeManifestChunk(
        $seriesDirectory.'/chunk-0003',
        'series-batch-3',
        '703',
        'Trzecie pytanie z serii',
        'shared/703/full.jpg',
        'image-three',
    );

    $resumeReportPath = $seriesDirectory.'/resume-report.json';

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--to-chunk' => 1,
        '--skip-sitemap' => true,
        '--report' => $resumeReportPath,
    ])->assertSuccessful();

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--resume-from-report' => $resumeReportPath,
        '--skip-sitemap' => true,
        '--report' => $resumeReportPath,
    ])->assertSuccessful();

    $summary = json_decode((string) File::get($resumeReportPath), true);

    expect($summary['status'])->toBe('ok');
    expect($summary['resume_report_path'])->toBe($resumeReportPath);
    expect($summary['chunks_discovered'])->toBe(3);
    expect($summary['chunks_selected'])->toBe(2);
    expect($summary['chunks_skipped'])->toBe(1);
    expect($summary['chunks_succeeded'])->toBe(2);
    expect($summary['chunks_failed'])->toBe(0);
    expect($summary['questions_total'])->toBe(2);
    expect(Question::query()->count())->toBe(3);
    expect(collect($summary['chunks'])->pluck('status')->all())->toBe(['skipped', 'ok', 'ok']);
});

function makeManifestChunk(
    string $directory,
    string $batchId,
    string $externalId,
    string $prompt,
    string $imagePath,
    ?string $imageContent,
): void {
    File::ensureDirectoryExists($directory.'/media/shared/'.basename(dirname($imagePath)));

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => $batchId,
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
        'source' => 'gov.pl-mi',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($imageContent !== null) {
        File::put($directory.'/media/'.$imagePath, $imageContent);
    }

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,category_id,question_text,answer_a,answer_b,answer_c,correct_answer,explanation,points,difficulty,question_type,source,published_at,metadata_json,image_path,thumb_path,video_path,poster_path',
        sprintf(
            '%s,B,"%s","Tak","Nie","","A","",3,,boolean,gov.pl-mi,,"{""government_question_id"":""%s""}","%s","","",""',
            $externalId,
            $prompt,
            $externalId,
            $imagePath,
        ),
    ]));
}
