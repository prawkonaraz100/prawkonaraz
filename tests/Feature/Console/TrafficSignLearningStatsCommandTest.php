<?php

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignLearningAnswer;
use App\Models\TrafficSignLearningSession;
use App\Models\User;
use App\Support\TrafficSignLearningMetricsService;

test('traffic sign learning stats command reports core metrics', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);

    $yieldSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($category, 'category')
        ->create([
            'code' => 'A-7',
            'slug' => 'a-7-ustap-pierwszenstwa',
            'name' => 'Ustap pierwszenstwa',
        ]);
    $stopSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($category, 'category')
        ->create([
            'code' => 'B-20',
            'slug' => 'b-20-stop',
            'name' => 'STOP',
        ]);

    $session = TrafficSignLearningSession::query()->create([
        'user_id' => $user->getKey(),
        'mode' => TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN,
        'status' => TrafficSignLearningSession::STATUS_COMPLETED,
        'total_signs_count' => 2,
        'correct_answers_count' => 1,
        'score_percent' => 50,
        'started_at' => now(),
        'completed_at' => now(),
        'payload' => [
            'traffic_sign_ids' => [$yieldSign->getKey(), $stopSign->getKey()],
            'category_slugs' => ['znaki-ostrzegawcze'],
        ],
    ]);

    TrafficSignLearningAnswer::query()->create([
        'traffic_sign_learning_session_id' => $session->getKey(),
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $yieldSign->getKey(),
        'selected_traffic_sign_id' => $yieldSign->getKey(),
        'position' => 1,
        'answer_mode' => TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN,
        'options' => [],
        'is_correct' => true,
        'response_time_ms' => 1200,
        'answered_at' => now(),
    ]);
    TrafficSignLearningAnswer::query()->create([
        'traffic_sign_learning_session_id' => $session->getKey(),
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $yieldSign->getKey(),
        'selected_traffic_sign_id' => $stopSign->getKey(),
        'position' => 2,
        'answer_mode' => TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN,
        'options' => [],
        'is_correct' => false,
        'response_time_ms' => 1800,
        'answered_at' => now(),
    ]);

    $summary = app(TrafficSignLearningMetricsService::class)->summary(30);

    expect($summary['started_sessions'])->toBe(1)
        ->and($summary['completed_sessions'])->toBe(1)
        ->and($summary['mode_breakdown'][0]['mode'])->toBe(TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN)
        ->and($summary['top_confusions'][0]['code'])->toBe('B-20')
        ->and($summary['weakest_categories'][0]['slug'])->toBe('znaki-ostrzegawcze');

    $this->artisan('traffic-signs:learning-stats', ['--days' => 30])
        ->expectsOutputToContain('Rozpoczete sesje: 1')
        ->expectsOutputToContain('Ukonczone sesje: 1')
        ->expectsOutputToContain('Sredni wynik: 50%')
        ->expectsOutputToContain('description_to_sign')
        ->expectsOutputToContain('B-20')
        ->assertSuccessful();

    $this->artisan('traffic-signs:learning-stats', ['--days' => 30, '--json' => true])
        ->expectsOutputToContain('"started_sessions": 1')
        ->assertSuccessful();
});
