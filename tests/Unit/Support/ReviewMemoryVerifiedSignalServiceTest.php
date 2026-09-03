<?php

use App\Models\ReviewMemoryProgress;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewMemoryVerifiedSignalService;
use Tests\TestCase;

uses(TestCase::class);

test('verified memory signal service identifies recovery leech risk', function () {
    $progress = new ReviewMemoryProgress;
    $progress->forceFill([
        'verified_attempts_count' => 5,
        'verified_correct_count' => 1,
        'verified_unknown_count' => 3,
        'verified_incorrect_count' => 1,
        'verified_correct_streak' => 0,
        'last_verified_result' => ReviewMemoryProgress::RESULT_UNKNOWN,
        'next_verified_review_at' => today()->subDays(2),
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $signal = app(ReviewMemoryVerifiedSignalService::class)->signal($progress, today());

    expect($signal['version'])->toBe(ReviewMemoryVerifiedSignalService::VERSION)
        ->and($signal['source_policy_version'])->toBe(ReviewMemoryProgressService::VERSION)
        ->and($signal['verified_memory_state'])->toBe(ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
        ->and($signal['plan_segment'])->toBe(ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY)
        ->and($signal['overdue_days'])->toBe(2)
        ->and($signal['verified_error_count'])->toBe(4)
        ->and($signal['recovery_score'])->toBeGreaterThanOrEqual(65)
        ->and($signal['leech_score'])->toBeGreaterThanOrEqual(65)
        ->and($signal['stability_score'])->toBeLessThan(30);
});

test('verified memory signal service keeps stable verified memory in reinforcement segment', function () {
    $progress = new ReviewMemoryProgress;
    $progress->forceFill([
        'verified_attempts_count' => 4,
        'verified_correct_count' => 4,
        'verified_unknown_count' => 0,
        'verified_incorrect_count' => 0,
        'verified_correct_streak' => 4,
        'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
        'next_verified_review_at' => today()->addDays(7),
        'verified_memory_state' => ReviewMemoryProgress::STATE_VERIFIED_MEMORY,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $signal = app(ReviewMemoryVerifiedSignalService::class)->signal($progress, today());

    expect($signal['verified_memory_state'])->toBe(ReviewMemoryProgress::STATE_VERIFIED_MEMORY)
        ->and($signal['plan_segment'])->toBe(ReviewMemoryVerifiedSignalService::SEGMENT_REINFORCE)
        ->and($signal['recovery_score'])->toBeLessThan(25)
        ->and($signal['leech_score'])->toBe(0)
        ->and($signal['stability_score'])->toBeGreaterThanOrEqual(80)
        ->and($signal['overdue_days'])->toBe(0);
});

test('verified memory signal service separates due review from recovery', function () {
    $progress = new ReviewMemoryProgress;
    $progress->forceFill([
        'verified_attempts_count' => 2,
        'verified_correct_count' => 2,
        'verified_unknown_count' => 0,
        'verified_incorrect_count' => 0,
        'verified_correct_streak' => 2,
        'last_verified_result' => ReviewMemoryProgress::RESULT_CORRECT,
        'next_verified_review_at' => today(),
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
    ]);

    $signal = app(ReviewMemoryVerifiedSignalService::class)->signal($progress, today());

    expect($signal['verified_memory_state'])->toBe(ReviewMemoryProgress::STATE_REVIEW)
        ->and($signal['plan_segment'])->toBe(ReviewMemoryVerifiedSignalService::SEGMENT_DUE)
        ->and($signal['leech_score'])->toBe(0)
        ->and($signal['overdue_days'])->toBe(0);
});
