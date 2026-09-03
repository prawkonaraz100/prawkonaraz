<?php

use App\Models\ContentImportRun;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Support\QuestionIntegrityAuditService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/question-integrity/testing-question-integrity'));
    File::deleteDirectory(storage_path('app/question-integrity/gov_full_catalog'));
    File::deleteDirectory(storage_path('app/testing-question-integrity-series'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/question-integrity/testing-question-integrity'));
    File::deleteDirectory(storage_path('app/question-integrity/gov_full_catalog'));
    File::deleteDirectory(storage_path('app/testing-question-integrity-series'));
});

test('question integrity audit creates a baseline and then detects critical changes', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'slug' => 'b',
        'name' => 'Kategoria B',
    ]);

    $question = Question::factory()->booleanType()->create([
        'license_category_id' => $category->id,
        'external_id' => '1001',
        'prompt' => 'Czy w tej sytuacji masz pierwszenstwo?',
        'option_a' => 'Tak',
        'option_b' => 'Nie',
        'option_c' => null,
        'correct_answer' => 'A',
        'question_type' => 'boolean',
        'points' => 3,
        'difficulty' => 1,
        'is_active' => true,
        'source' => 'gov.pl-mi',
        'metadata' => [
            'government_question_id' => '1001',
            'structure_scope' => 'PODSTAWOWY',
            'categories_original' => ['B'],
            'main_media_original' => 'stop.jpg',
            'pjm' => [
                'question' => 'pjm-1001.mp4',
            ],
        ],
    ]);

    $service = app(QuestionIntegrityAuditService::class);

    $baseline = $service->auditCurrentCatalog('testing-question-integrity');

    expect($baseline['baseline'])->toBeTrue();
    expect($baseline['summary']['current_total'])->toBe(1);
    expect($baseline['summary']['added_total'])->toBe(1);
    expect($baseline['summary']['changed_total'])->toBe(0);

    $question->forceFill([
        'correct_answer' => 'B',
        'metadata' => array_merge($question->metadata ?? [], [
            'main_media_original' => 'stop-updated.jpg',
        ]),
    ])->save();

    $diff = $service->auditCurrentCatalog('testing-question-integrity');

    expect($diff['baseline'])->toBeFalse();
    expect($diff['summary']['current_total'])->toBe(1);
    expect($diff['summary']['added_total'])->toBe(0);
    expect($diff['summary']['removed_total'])->toBe(0);
    expect($diff['summary']['changed_total'])->toBe(1);
    expect($diff['summary']['critical_total'])->toBe(1);
    expect($diff['summary']['field_changes'])->toMatchArray([
        'correct_answer' => 1,
        'main_media_original' => 1,
    ]);
});

test('manifest series import stores integrity audit summary after a successful full import', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $seriesDirectory = storage_path('app/testing-question-integrity-series/success');
    makeQuestionIntegrityManifestChunk(
        $seriesDirectory.'/chunk-0001',
        'series-batch-1',
        '1101',
        'Czy widzisz znak stop?',
        'shared/1101/full.jpg',
        'image-one',
    );

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--skip-sitemap' => true,
        '--report' => $seriesDirectory.'/series-import-report.json',
    ])->assertSuccessful();

    $run = ContentImportRun::query()->latest('id')->firstOrFail();
    $integrityAudit = $run->summary['integrity_audit'] ?? null;

    expect($run->kind)->toBe('manifest_series_import');
    expect($run->dry_run)->toBeFalse();
    expect($integrityAudit)->toBeArray();
    expect($integrityAudit['baseline'])->toBeTrue();
    expect($integrityAudit['summary']['current_total'])->toBe(1);
    expect($integrityAudit['summary']['added_total'])->toBe(1);
    expect($integrityAudit['summary']['changed_total'])->toBe(0);
    expect(File::exists((string) $integrityAudit['report_path']))->toBeTrue();
    expect(File::exists((string) $integrityAudit['changes_path']))->toBeTrue();
});

function makeQuestionIntegrityManifestChunk(
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
            '%s,B,"%s","Tak","Nie","","A","",3,,boolean,gov.pl-mi,,"{""government_question_id"":""%s"",""structure_scope"":""PODSTAWOWY"",""categories_original"":[""B""],""main_media_original"":""%s""}","%s","","",""',
            $externalId,
            $prompt,
            $externalId,
            basename($imagePath),
            $imagePath,
        ),
    ]));
}
