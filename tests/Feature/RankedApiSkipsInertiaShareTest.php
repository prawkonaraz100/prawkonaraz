<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\User;
use App\Support\PublicFooter;
use App\Support\PublicNavigation;
use App\Support\RankedMatchService;

// API rankingu jest wywoływane co kilka sekund przez każdego gracza i zwraca JSON,
// więc nie może budować nawigacji ani stopki strony (dane tylko dla stron Inertia).
it('does not build page navigation for ranked api requests', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $question = Question::factory()->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    Question::factory()->count(2)->for($category, 'licenseCategory')->create();
    $first = User::factory()->withPurchasedAccess()->create();
    $second = User::factory()->withPurchasedAccess()->create();

    $this->mock(PublicNavigation::class)->shouldNotReceive('data');
    $this->mock(PublicFooter::class)->shouldNotReceive('data');

    foreach ([$first, $second] as $user) {
        $this->actingAs($user)
            ->postJson(route('api.v1.ranked.queue.join'), ['category_id' => $category->getKey()])
            ->assertOk();
    }

    $match = RankedMatch::query()->latest('id')->firstOrFail();

    foreach ([$first, $second] as $user) {
        $this->actingAs($user)->postJson(route('api.v1.ranked.matches.ready', $match->public_id))->assertOk();
    }

    $this->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();
    $questionId = $match->fresh()->payload['question_ids'][0] ?? $question->getKey();

    $this->actingAs($first)->getJson(route('api.v1.ranked.overview'))->assertOk();
    $this->actingAs($first)->postJson(route('api.v1.ranked.matches.pong', $match->public_id))->assertOk();
    $this->actingAs($first)->getJson(route('api.v1.ranked.matches.show', $match->public_id))->assertOk();
    $this->actingAs($first)->getJson(route('api.v1.ranked.matches.events', $match->public_id))->assertOk();
    $this->actingAs($first)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionId,
            'user_answer' => 'a',
        ])
        ->assertOk();
});
