<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;

test('delivery readiness audit marks broken questions and can deactivate them', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $brokenQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-BROKEN-100',
            'metadata' => [
                'main_media_original' => 'missing-stop.jpg',
            ],
        ]);

    $readyTextQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-TEXT-100',
            'metadata' => [],
        ]);

    $readyMediaQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-MEDIA-100',
            'metadata' => [
                'main_media_original' => 'ready-stop.jpg',
            ],
        ]);

    QuestionMedia::factory()
        ->for($readyMediaQuestion)
        ->create([
            'kind' => 'image',
            'variant' => 'full',
            'path' => 'questions/b/ready-stop.webp',
            'mime_type' => 'image/webp',
        ]);

    $this->artisan('content:audit-delivery-readiness', [
        '--category' => 'B',
        '--deactivate-missing-primary-media' => true,
    ])->assertSuccessful();

    $brokenQuestion->refresh();
    $readyTextQuestion->refresh();
    $readyMediaQuestion->refresh();

    expect($brokenQuestion->requires_primary_media)->toBeTrue();
    expect($brokenQuestion->delivery_issue)->toBe(Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA);
    expect($brokenQuestion->is_active)->toBeFalse();

    expect($readyTextQuestion->requires_primary_media)->toBeFalse();
    expect($readyTextQuestion->delivery_issue)->toBeNull();
    expect($readyTextQuestion->is_active)->toBeTrue();

    expect($readyMediaQuestion->requires_primary_media)->toBeTrue();
    expect($readyMediaQuestion->delivery_issue)->toBeNull();
    expect($readyMediaQuestion->is_active)->toBeTrue();
});
