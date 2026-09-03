<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Support\PjmCoverageService;
use Illuminate\Database\QueryException;

it('calculates PJM coverage per learning category by question asset only', function (): void {
    $categoryB = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $categoryC = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
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
        'license_category_id' => $categoryB->id,
        'external_id' => '102',
        'is_active' => false,
        'delivery_issue' => null,
    ]);

    Question::factory()->create([
        'license_category_id' => $categoryB->id,
        'external_id' => '103',
        'is_active' => true,
        'delivery_issue' => 'missing_media',
    ]);

    Question::factory()->create([
        'license_category_id' => $categoryC->id,
        'external_id' => '100',
        'is_active' => true,
        'delivery_issue' => null,
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '100',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    QuestionSignLanguageAsset::factory()->answer()->create([
        'external_id' => '101',
    ]);

    $coverage = collect(app(PjmCoverageService::class)->categoryCoverage(['B', 'C']));

    expect($coverage->firstWhere('code', 'B'))->toMatchArray([
        'total_questions' => 2,
        'pjm_questions' => 1,
        'missing_questions' => 1,
        'coverage_percent' => 50.0,
        'question_assets' => 1,
        'answer_assets' => 1,
        'active_assets' => 2,
    ]);

    expect($coverage->firstWhere('code', 'C'))->toMatchArray([
        'total_questions' => 1,
        'pjm_questions' => 1,
        'missing_questions' => 0,
        'coverage_percent' => 100.0,
        'question_assets' => 1,
        'answer_assets' => 0,
        'active_assets' => 1,
    ]);
});

it('links PJM assets to every question with the same external id', function (): void {
    $categoryB = LicenseCategory::factory()->create(['code' => 'B']);
    $categoryC = LicenseCategory::factory()->create(['code' => 'C']);

    $questionB = Question::factory()->create([
        'license_category_id' => $categoryB->id,
        'external_id' => '200',
    ]);

    Question::factory()->create([
        'license_category_id' => $categoryC->id,
        'external_id' => '200',
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '200',
    ]);

    expect($questionB->signLanguageAssets()->count())->toBe(1);
    expect(QuestionSignLanguageAsset::first()->questions()->count())->toBe(2);
});

it('stores one PJM asset per role and blocks duplicate role variants', function (): void {
    foreach ([
        QuestionSignLanguageAsset::ROLE_QUESTION,
        QuestionSignLanguageAsset::ROLE_ANSWER_A,
        QuestionSignLanguageAsset::ROLE_ANSWER_B,
        QuestionSignLanguageAsset::ROLE_ANSWER_C,
    ] as $role) {
        QuestionSignLanguageAsset::factory()->create([
            'external_id' => '300',
            'asset_role' => $role,
            'variant' => QuestionSignLanguageAsset::VARIANT_STANDARD,
        ]);
    }

    expect(QuestionSignLanguageAsset::where('external_id', '300')->count())->toBe(4);

    expect(fn () => QuestionSignLanguageAsset::factory()->create([
        'external_id' => '300',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'variant' => QuestionSignLanguageAsset::VARIANT_STANDARD,
    ]))->toThrow(QueryException::class);
});
