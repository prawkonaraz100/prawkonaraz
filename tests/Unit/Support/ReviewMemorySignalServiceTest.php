<?php

use App\Models\UserQuestionProgress;
use App\Support\ReviewMemorySignalService;
use Tests\TestCase;

uses(TestCase::class);

test('memory signal service identifies repeatedly missed questions as leech risk', function () {
    $progress = new UserQuestionProgress;
    $progress->forceFill([
        'easiness_factor' => 1.8,
        'interval_days' => 1,
        'repetitions' => 0,
        'next_review_at' => today(),
        'last_quality' => 1,
        'total_attempts' => 6,
        'correct_count' => 2,
        'incorrect_count' => 4,
        'correct_streak' => 0,
        'last_answered_at' => today(),
        'first_answered_at' => today()->subDays(10),
    ]);
    $progress->setAttribute('question_difficulty', 5);

    $signal = app(ReviewMemorySignalService::class)->signal($progress, today());

    expect($signal['version'])->toBe(ReviewMemorySignalService::VERSION)
        ->and($signal['memory_state'])->toBe(ReviewMemorySignalService::STATE_LEECH)
        ->and($signal['plan_segment'])->toBe(ReviewMemorySignalService::SEGMENT_RISKY)
        ->and($signal['leech_score'])->toBeGreaterThanOrEqual(65)
        ->and($signal['stability_score'])->toBeLessThan(45);
});

test('memory signal service keeps mastered questions out of leech state', function () {
    $progress = new UserQuestionProgress;
    $progress->forceFill([
        'easiness_factor' => 2.7,
        'interval_days' => 7,
        'repetitions' => 3,
        'next_review_at' => today()->addDays(7),
        'last_quality' => 4,
        'total_attempts' => 5,
        'correct_count' => 5,
        'incorrect_count' => 0,
        'correct_streak' => 5,
        'last_answered_at' => today(),
        'first_answered_at' => today()->subDays(20),
    ]);
    $progress->setAttribute('question_difficulty', 3);

    $signal = app(ReviewMemorySignalService::class)->signal($progress, today());

    expect($signal['memory_state'])->toBe(ReviewMemorySignalService::STATE_MASTERED)
        ->and($signal['plan_segment'])->toBe(ReviewMemorySignalService::SEGMENT_REINFORCE)
        ->and($signal['leech_score'])->toBe(0)
        ->and($signal['overdue_days'])->toBe(0);
});

test('memory signal service separates overdue pressure from memory state', function () {
    $progress = new UserQuestionProgress;
    $progress->forceFill([
        'easiness_factor' => 2.5,
        'interval_days' => 3,
        'repetitions' => 2,
        'next_review_at' => today()->subDays(2),
        'last_quality' => 4,
        'total_attempts' => 3,
        'correct_count' => 3,
        'incorrect_count' => 0,
        'correct_streak' => 3,
        'last_answered_at' => today()->subDays(5),
        'first_answered_at' => today()->subDays(12),
    ]);
    $progress->setAttribute('question_difficulty', 2);

    $signal = app(ReviewMemorySignalService::class)->signal($progress, today());

    expect($signal['memory_state'])->toBe(ReviewMemorySignalService::STATE_REVIEW)
        ->and($signal['plan_segment'])->toBe(ReviewMemorySignalService::SEGMENT_OVERDUE)
        ->and($signal['overdue_days'])->toBe(2);
});
