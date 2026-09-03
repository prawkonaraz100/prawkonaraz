<?php

use App\Filament\Pages\PjmCoverage;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Models\User;
use App\Support\AdminPjmCoverageReportService;

it('builds an operational PJM coverage report with orphaned and processing samples', function (): void {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $coveredQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => 'PJM-100',
        'prompt' => 'Pytanie z filmem PJM',
    ]);
    $missingQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => 'PJM-200',
        'prompt' => 'Pytanie bez aktywnego filmu PJM',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $coveredQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'bytes' => 1000,
    ]);
    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $missingQuestion->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'processing_status' => QuestionSignLanguageAsset::STATUS_DISABLED,
        'is_active' => false,
        'bytes' => 2000,
    ]);
    QuestionSignLanguageAsset::factory()->create([
        'external_id' => 'PJM-999',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'bytes' => 3000,
    ]);

    $report = app(AdminPjmCoverageReportService::class)->build('PJM-100');
    $categoryRow = collect($report['categories'])->firstWhere('code', 'B');

    expect($categoryRow)->toMatchArray([
        'total_questions' => 2,
        'pjm_questions' => 1,
        'missing_questions' => 1,
    ])
        ->and($report['summary']['orphaned_assets'])->toBe(1)
        ->and($report['summary']['processing_problem_assets'])->toBe(1)
        ->and($report['orphaned_asset_sample'][0]['external_id'])->toBe('PJM-999')
        ->and($report['processing_problem_sample'][0]['external_id'])->toBe('PJM-200')
        ->and($report['missing_question_sample'][0]['external_id'])->toBe('PJM-200')
        ->and($report['external_id_lookup']['external_id'])->toBe('PJM-100')
        ->and($report['external_id_lookup']['has_question_asset'])->toBeTrue();
});

it('lets admins open PJM coverage page and download JSON or CSV reports', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => 'PJM-300',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $question->external_id,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $this->actingAs($admin)
        ->get(PjmCoverage::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Pokrycie PJM', false)
        ->assertSee('Kategoria B', false)
        ->assertSee('Pobierz JSON', false)
        ->assertSee('Pobierz CSV', false);

    $jsonResponse = $this->actingAs($admin)
        ->get(route('admin.pjm.report.download', ['format' => 'json', 'external_id' => 'PJM-300']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    expect($jsonResponse->headers->get('Content-Disposition'))->toContain('attachment; filename="pjm-coverage-')
        ->and($jsonResponse->json('external_id_lookup.external_id'))->toBe('PJM-300')
        ->and($jsonResponse->json('categories.0.code'))->toBe('B');

    $csvResponse = $this->actingAs($admin)
        ->get(route('admin.pjm.report.download', ['format' => 'csv']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($csvResponse->headers->get('Content-Disposition'))->toContain('attachment; filename="pjm-coverage-')
        ->and($csvResponse->getContent())->toContain('category,B');
});

it('blocks non admins from PJM coverage operations', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(PjmCoverage::getUrl(panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.pjm.report.download', ['format' => 'json']))
        ->assertForbidden();
});
