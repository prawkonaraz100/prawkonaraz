<?php

use App\Models\QuestionSignLanguageAsset;
use App\Support\PjmSignLanguageAssetImportService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('imports PJM assets into storage and database with review flags', function (): void {
    Storage::fake('media_local');

    $baseDirectory = storage_path('framework/testing/pjm-import-'.Str::random(8));
    $directory = $baseDirectory.DIRECTORY_SEPARATOR.'source';
    $reviewReport = $baseDirectory.DIRECTORY_SEPARATOR.'review.csv';
    File::ensureDirectoryExists($directory);

    try {
        File::put($directory.DIRECTORY_SEPARATOR.'pjm100.mp4', 'question-video');
        File::put($directory.DIRECTORY_SEPARATOR.'pjm100a.mp4', 'answer-a-video');
        File::put($directory.DIRECTORY_SEPARATOR.'not-pjm.txt', 'ignored');
        File::put($reviewReport, implode(PHP_EOL, [
            '"source_filename","external_id","asset_role"',
            '"pjm100.wmv","100","question"',
        ]).PHP_EOL);

        $report = app(PjmSignLanguageAssetImportService::class)->import($directory, [
            'disk' => 'media_local',
            'prefix' => 'pjm/test',
            'variant' => QuestionSignLanguageAsset::VARIANT_STANDARD,
            'processing_profile' => 'safe-center-crop-80',
            'review_report' => $reviewReport,
        ]);

        expect($report)->toMatchArray([
            'parsed_files' => 2,
            'invalid_files_count' => 1,
            'imported_assets' => 2,
            'updated_assets' => 0,
            'skipped_existing_assets' => 0,
            'review_required_assets' => 1,
            'errors_count' => 0,
        ]);

        Storage::disk('media_local')->assertExists('pjm/test/standard/pjm100.mp4');
        Storage::disk('media_local')->assertExists('pjm/test/standard/pjm100a.mp4');

        $questionAsset = QuestionSignLanguageAsset::query()
            ->where('external_id', '100')
            ->where('asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
            ->firstOrFail();

        expect($questionAsset)->processing_status->toBe(QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED)
            ->review_required->toBeTrue()
            ->disk->toBe('media_local')
            ->path->toBe('pjm/test/standard/pjm100.mp4')
            ->checksum_sha256->not->toBeNull();

        $answerAsset = QuestionSignLanguageAsset::query()
            ->where('external_id', '100')
            ->where('asset_role', QuestionSignLanguageAsset::ROLE_ANSWER_A)
            ->firstOrFail();

        expect($answerAsset)->processing_status->toBe(QuestionSignLanguageAsset::STATUS_READY)
            ->review_required->toBeFalse();
    } finally {
        File::deleteDirectory($baseDirectory);
    }
});

it('skips existing PJM assets unless force is enabled', function (): void {
    Storage::fake('media_local');

    $directory = storage_path('framework/testing/pjm-import-'.Str::random(8));
    File::ensureDirectoryExists($directory);

    try {
        File::put($directory.DIRECTORY_SEPARATOR.'pjm200.mp4', 'first-video');

        app(PjmSignLanguageAssetImportService::class)->import($directory, [
            'disk' => 'media_local',
            'prefix' => 'pjm/test',
        ]);

        File::put($directory.DIRECTORY_SEPARATOR.'pjm200.mp4', 'changed-video');

        $secondReport = app(PjmSignLanguageAssetImportService::class)->import($directory, [
            'disk' => 'media_local',
            'prefix' => 'pjm/test',
        ]);

        expect($secondReport)->toMatchArray([
            'imported_assets' => 0,
            'updated_assets' => 0,
            'skipped_existing_assets' => 1,
        ]);

        $asset = QuestionSignLanguageAsset::where('external_id', '200')->firstOrFail();
        $firstChecksum = $asset->checksum_sha256;

        $forceReport = app(PjmSignLanguageAssetImportService::class)->import($directory, [
            'disk' => 'media_local',
            'prefix' => 'pjm/test',
            'force' => true,
        ]);

        expect($forceReport)->toMatchArray([
            'imported_assets' => 0,
            'updated_assets' => 1,
            'skipped_existing_assets' => 0,
        ]);

        expect($asset->fresh()->checksum_sha256)->not->toBe($firstChecksum);
    } finally {
        File::deleteDirectory($directory);
    }
});
