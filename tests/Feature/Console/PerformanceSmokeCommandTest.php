<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

test('ops perf smoke benchmarks critical flows and cleans up placeholder assets', function () {
    Storage::fake('public');

    Config::set('media.public_disk', 'public');
    Config::set('media.default_disk', 'public');
    Config::set('performance.perf_smoke.session_start_ms', 1000);
    Config::set('performance.perf_smoke.first_answer_ms', 1000);
    Config::set('performance.perf_smoke.session_complete_ms', 1000);
    Config::set('performance.perf_smoke.dashboard_ms', 1000);

    $this->artisan('ops:perf-smoke')
        ->expectsOutputToContain('Performance smoke status: OK')
        ->expectsOutputToContain('[OK] session_start')
        ->expectsOutputToContain('[OK] first_answer')
        ->expectsOutputToContain('[OK] session_complete')
        ->expectsOutputToContain('[OK] dashboard_metrics')
        ->assertSuccessful();

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('ops perf smoke can fail on threshold assertions and emit json output', function () {
    Storage::fake('public');

    Config::set('media.public_disk', 'public');
    Config::set('media.default_disk', 'public');

    $exitCode = Artisan::call('ops:perf-smoke', [
        '--json' => true,
        '--assert' => true,
        '--max-session-start-ms' => 0.01,
        '--max-first-answer-ms' => 0.01,
        '--max-session-complete-ms' => 0.01,
        '--max-dashboard-ms' => 0.01,
    ]);

    $payload = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(1);
    expect($payload['status'])->toBe('failed');
    expect($payload['benchmarks'])->not->toBe([]);
    expect(collect($payload['benchmarks'])->contains(
        fn (array $benchmark): bool => $benchmark['status'] === 'failed'
    ))->toBeTrue();
});
