<?php

use App\Models\ReviewMemoryProgress;
use App\Support\ReviewMemoryLegacySignalMapper;
use App\Support\ReviewMemoryProgressService;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewMemoryVerifiedSignalService;

test('verified memory legacy mapper keeps due today out of overdue segment', function () {
    $mapper = app(ReviewMemoryLegacySignalMapper::class);

    $signal = verifiedSignal([
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_DUE,
        'overdue_days' => 0,
    ]);

    expect($mapper->segment($signal))->toBe(ReviewMemorySignalService::SEGMENT_REINFORCE)
        ->and($mapper->memoryState($signal))->toBe(ReviewMemorySignalService::STATE_REVIEW);
});

test('verified memory legacy mapper maps overdue due segment to legacy overdue', function () {
    $mapper = app(ReviewMemoryLegacySignalMapper::class);

    $signal = verifiedSignal([
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_DUE,
        'overdue_days' => 3,
    ]);

    expect($mapper->segment($signal))->toBe(ReviewMemorySignalService::SEGMENT_OVERDUE);
});

test('verified memory legacy mapper separates leech risk from relearning recovery', function () {
    $mapper = app(ReviewMemoryLegacySignalMapper::class);

    $leechSignal = verifiedSignal([
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY,
        'leech_score' => 80,
    ]);
    $relearningSignal = verifiedSignal([
        'verified_memory_state' => ReviewMemoryProgress::STATE_NEEDS_RECOVERY,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY,
        'leech_score' => 40,
    ]);

    expect($mapper->segment($leechSignal))->toBe(ReviewMemorySignalService::SEGMENT_RISKY)
        ->and($mapper->memoryState($leechSignal))->toBe(ReviewMemorySignalService::STATE_LEECH)
        ->and($mapper->memoryState($relearningSignal))->toBe(ReviewMemorySignalService::STATE_RELEARNING);
});

test('verified memory legacy mapper builds compatible preview payloads', function () {
    $mapper = app(ReviewMemoryLegacySignalMapper::class);

    $signal = verifiedSignal([
        'verified_memory_state' => ReviewMemoryProgress::STATE_VERIFIED_MEMORY,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_REINFORCE,
        'stability_score' => 91,
    ]);

    $preview = $mapper->verifiedPreviewSignal($signal, 4);
    $classic = $mapper->classicPreviewSignal([
        'version' => ReviewMemorySignalService::VERSION,
        'memory_state' => ReviewMemorySignalService::STATE_LEARNING,
        'plan_segment' => ReviewMemorySignalService::SEGMENT_REINFORCE,
        'leech_score' => 0,
        'stability_score' => 45,
        'difficulty_score' => 20,
        'overdue_days' => 0,
    ]);

    expect($preview['version'])->toBe(ReviewMemorySignalService::VERSION)
        ->and($preview['source'])->toBe(ReviewMemoryLegacySignalMapper::SOURCE_VERIFIED)
        ->and($preview['verified_memory_signal_version'])->toBe(ReviewMemoryVerifiedSignalService::VERSION)
        ->and($preview['source_policy_version'])->toBe(ReviewMemoryProgressService::VERSION)
        ->and($preview['verified_memory_state'])->toBe(ReviewMemoryProgress::STATE_VERIFIED_MEMORY)
        ->and($preview['memory_state'])->toBe(ReviewMemorySignalService::STATE_MASTERED)
        ->and($preview['difficulty_score'])->toBe(80)
        ->and($classic['source'])->toBe(ReviewMemoryLegacySignalMapper::SOURCE_CLASSIC);
});

/**
 * @param  array<string, int|string>  $overrides
 * @return array<string, int|string>
 */
function verifiedSignal(array $overrides = []): array
{
    return [
        'version' => ReviewMemoryVerifiedSignalService::VERSION,
        'source_policy_version' => ReviewMemoryProgressService::VERSION,
        'verified_memory_state' => ReviewMemoryProgress::STATE_REVIEW,
        'plan_segment' => ReviewMemoryVerifiedSignalService::SEGMENT_REINFORCE,
        'recovery_score' => 0,
        'leech_score' => 0,
        'stability_score' => 60,
        'overdue_days' => 0,
        'verified_attempts_count' => 2,
        'verified_error_count' => 0,
        'verified_correct_streak' => 2,
        ...$overrides,
    ];
}
