<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Support\PjmSignLanguageDryRunService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('builds a PJM dry-run report from a source folder without database writes', function (): void {
    $directory = storage_path('framework/testing/pjm-dry-run-'.Str::random(8));
    File::ensureDirectoryExists($directory);

    try {
        foreach ([
            'pjm100.mp4',
            'pjm100a.mp4',
            'pjm100b.mp4',
            'pjm100c.mp4',
            'pjm100.wmv',
            'pjm101a.mp4',
            'pjm999.mp4',
            'notes.txt',
        ] as $filename) {
            File::put($directory.DIRECTORY_SEPARATOR.$filename, 'test');
        }

        $categoryB = LicenseCategory::factory()->create([
            'code' => 'B',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $categoryC = LicenseCategory::factory()->create([
            'code' => 'C',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Question::factory()->create([
            'license_category_id' => $categoryB->id,
            'external_id' => '100',
            'is_active' => true,
            'delivery_issue' => null,
        ]);

        Question::factory()->create([
            'license_category_id' => $categoryB->id,
            'external_id' => '101',
            'is_active' => true,
            'delivery_issue' => null,
        ]);

        Question::factory()->create([
            'license_category_id' => $categoryC->id,
            'external_id' => '100',
            'is_active' => true,
            'delivery_issue' => null,
        ]);

        $report = app(PjmSignLanguageDryRunService::class)->buildReport($directory);
        $coverage = collect($report['category_coverage']);

        expect($report)->toMatchArray([
            'dry_run' => true,
            'total_files' => 8,
            'parsed_files' => 7,
            'invalid_files_count' => 1,
            'unique_external_ids' => 3,
            'question_assets' => 3,
            'answer_assets' => 4,
            'complete_answer_sets' => 1,
            'duplicates_count' => 1,
            'matched_external_ids' => 2,
            'orphaned_external_ids' => 1,
        ]);

        expect(array_map('strval', $report['orphaned_external_ids_sample']))->toContain('999');

        expect($coverage->firstWhere('code', 'B'))->toMatchArray([
            'total_questions' => 2,
            'pjm_questions' => 1,
            'missing_questions' => 1,
            'coverage_percent' => 50.0,
        ]);

        expect($coverage->firstWhere('code', 'C'))->toMatchArray([
            'total_questions' => 1,
            'pjm_questions' => 1,
            'missing_questions' => 0,
            'coverage_percent' => 100.0,
        ]);
    } finally {
        File::deleteDirectory($directory);
    }
});
