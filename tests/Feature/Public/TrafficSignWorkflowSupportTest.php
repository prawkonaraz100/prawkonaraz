<?php

use App\Models\TrafficSign;
use App\Models\User;

test('traffic sign workflow helpers expose editorial and freshness states', function () {
    $reviewer = User::factory()->create([
        'is_admin' => true,
    ]);

    $sign = TrafficSign::factory()->create([
        'workflow_status' => TrafficSign::WORKFLOW_IN_REVIEW,
        'reviewer_user_id' => $reviewer->getKey(),
        'reviewed_at' => now()->subDay(),
        'source_checked_at' => null,
        'freshness_review_due_at' => now()->subDay(),
    ]);

    expect($sign->workflowLabel())->toBe('W review');
    expect($sign->workflowColor())->toBe('warning');
    expect($sign->sourceVerificationLabel())->toBe('Do sprawdzenia');
    expect($sign->freshnessStateLabel())->toBe('Po terminie');
    expect($sign->freshnessStateColor())->toBe('danger');
    expect($sign->needsFreshnessReview())->toBeTrue();
});

test('published sign can stay public while waiting for another editorial review', function () {
    $sign = TrafficSign::factory()->published()->create([
        'workflow_status' => TrafficSign::WORKFLOW_NEEDS_REVIEW,
    ]);

    expect($sign->isPubliclyVisible())->toBeTrue();
    expect($sign->workflowLabel())->toBe('Wymaga przegladu');
});

test('publication checklist exposes missing editorial requirements', function () {
    $sign = TrafficSign::factory()->create([
        'common_mistakes' => null,
        'faq_items' => [
            [
                'question' => 'Czy ten znak jest ważny?',
                'answer' => 'Tak.',
            ],
        ],
        'og_image_path' => null,
    ]);

    expect($sign->publicationChecklistCompletionLabel())->toBe('5/10 gotowe');
    expect($sign->publicationChecklistMissingLabels())->toBe([
        'Sekcja najczęstszych błędów',
        'Minimum 2 pytania FAQ',
        'Assety i metadata obrazu',
        'Źródła potwierdzone',
        'Termin kolejnego review',
    ]);
    expect($sign->hasCompletePublicationChecklist())->toBeFalse();
});
