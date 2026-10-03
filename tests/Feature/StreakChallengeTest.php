<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StreakRecord;
use App\Models\StreakRun;
use App\Models\StreakRunAnswer;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

function createStreakChallengeFixture(int $questionCount = 3): array
{
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    UserProfile::factory()->for($user)->create(['target_category_id' => $category->getKey()]);
    Question::factory()
        ->count($questionCount)
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    return [$user, $category];
}

test('training view renders a separate streak challenge with a real category leaderboard', function () {
    [$user, $category] = createStreakChallengeFixture();

    $this->actingAs($user)
        ->get(route('session.index', ['widok' => 'trening']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/StreakChallenge')
            ->where('category.id', $category->getKey())
            ->where('initialStreak.personal_best', 0)
            ->where('initialStreak.question_count', 3)
            ->where('initialStreak.active_run', null)
        );
});

test('one wrong answer immediately ends the run and saves only server-scored results', function () {
    [$user, $category] = createStreakChallengeFixture();
    $this->actingAs($user);

    $start = $this->postJson(route('streak-challenge.start'), [
        'category_id' => $category->getKey(),
    ])->assertOk();
    $runId = $start->json('active_run.id');
    $firstId = $start->json('active_run.question.id');
    expect($start->json('active_run.question'))->not->toHaveKey('correct_answer');

    $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
        'question_id' => $firstId,
        'selected_answer' => 'a',
    ])->assertOk()
        ->assertJsonPath('is_correct', true)
        ->assertJsonPath('score', 1)
        ->assertJsonPath('finished', false);

    $run = StreakRun::query()->findOrFail($runId);
    expect($run->current_question_id)->not->toBe($firstId);

    $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
        'question_id' => $firstId,
        'selected_answer' => 'a',
    ])->assertStatus(409);

    $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
        'question_id' => $run->current_question_id,
        'selected_answer' => 'b',
    ])->assertOk()
        ->assertJsonPath('is_correct', false)
        ->assertJsonPath('finished', true)
        ->assertJsonPath('score', 1)
        ->assertJsonPath('correct_answer', 'A')
        ->assertJsonPath('overview.personal_best', 1)
        ->assertJsonPath('overview.leaderboard.0.score', 1);

    expect(StreakRun::query()->findOrFail($runId)->status)->toBe(StreakRun::STATUS_FAILED);
    expect(StreakRunAnswer::query()->where('streak_run_id', $runId)->count())->toBe(2);
    expect(StreakRecord::query()->where('user_id', $user->getKey())->value('best_score'))->toBe(1);

    $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
        'question_id' => $firstId,
        'selected_answer' => 'a',
    ])->assertStatus(409);
    expect(StreakRunAnswer::query()->where('streak_run_id', $runId)->count())->toBe(2);
});

test('questions never repeat in one run and completing the whole pool saves the maximum score', function () {
    [$user, $category] = createStreakChallengeFixture(3);
    $this->actingAs($user);

    $start = $this->postJson(route('streak-challenge.start'), [
        'category_id' => $category->getKey(),
    ])->assertOk();
    $runId = $start->json('active_run.id');
    $questionId = $start->json('active_run.question.id');
    $seen = [];

    for ($index = 0; $index < 3; $index++) {
        expect(in_array($questionId, $seen, true))->toBeFalse();
        $seen[] = $questionId;
        $answer = $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
            'question_id' => $questionId,
            'selected_answer' => 'a',
        ])->assertOk()
            ->assertJsonPath('score', $index + 1);
        $questionId = $answer->json('run.question.id');
    }

    expect($questionId)->toBeNull();
    expect(StreakRun::query()->findOrFail($runId)->status)->toBe(StreakRun::STATUS_COMPLETED);
    expect(StreakRecord::query()->where('user_id', $user->getKey())->value('best_score'))->toBe(3);
});

test('restarting abandons the unfinished run without overwriting the personal best', function () {
    [$user, $category] = createStreakChallengeFixture();
    $this->actingAs($user);

    $first = $this->postJson(route('streak-challenge.start'), [
        'category_id' => $category->getKey(),
    ])->assertOk();
    $firstRunId = $first->json('active_run.id');
    $this->postJson(route('streak-challenge.answer', ['streakRun' => $firstRunId]), [
        'question_id' => $first->json('active_run.question.id'),
        'selected_answer' => 'a',
    ])->assertOk();

    $second = $this->postJson(route('streak-challenge.start'), [
        'category_id' => $category->getKey(),
    ])->assertOk();
    expect($second->json('active_run.id'))->not->toBe($firstRunId);
    expect(StreakRun::query()->findOrFail($firstRunId)->status)->toBe(StreakRun::STATUS_ABANDONED);
    expect(StreakRecord::query()->where('user_id', $user->getKey())->firstOrFail()->attempts_count)->toBe(2);
    expect(StreakRecord::query()->where('user_id', $user->getKey())->firstOrFail()->best_score)->toBe(0);
});

test('leaderboard shows the best score per person in the selected category', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    Question::factory()->count(2)->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    $jan = User::factory()->withPurchasedAccess()->create(['name' => 'Jan Kowalski']);
    $ada = User::factory()->withPurchasedAccess()->create(['name' => 'Ada Nowak']);
    UserProfile::factory()->for($jan)->create(['target_category_id' => $category->getKey()]);
    UserProfile::factory()->for($ada)->create(['target_category_id' => $category->getKey()]);

    $finishWithScore = function (User $user, int $targetScore) use ($category): void {
        $this->actingAs($user);
        $start = $this->postJson(route('streak-challenge.start'), [
            'category_id' => $category->getKey(),
        ])->assertOk();
        $runId = $start->json('active_run.id');
        $questionId = $start->json('active_run.question.id');

        for ($index = 0; $index < $targetScore; $index++) {
            $answer = $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
                'question_id' => $questionId,
                'selected_answer' => 'a',
            ])->assertOk();
            $questionId = $answer->json('run.question.id');
        }

        if ($questionId) {
            $this->postJson(route('streak-challenge.answer', ['streakRun' => $runId]), [
                'question_id' => $questionId,
                'selected_answer' => 'b',
            ])->assertOk();
        }
    };

    $finishWithScore($jan, 1);
    $finishWithScore($ada, 2);

    $this->actingAs($jan)
        ->getJson(route('streak-challenge.state', ['category_id' => $category->getKey()]))
        ->assertOk()
        ->assertJsonPath('personal_best', 1)
        ->assertJsonPath('personal_rank', 2)
        ->assertJsonPath('leaderboard.0.name', 'Ada N.')
        ->assertJsonPath('leaderboard.0.score', 2)
        ->assertJsonPath('leaderboard.1.name', 'Jan K.')
        ->assertJsonPath('leaderboard.1.score', 1);
});

test('challenge endpoints require full product access', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    Question::factory()->for($category, 'licenseCategory')->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('session.index', ['widok' => 'trening']))
        ->assertRedirect(route('access.activate'));

    $this->actingAs($user)
        ->postJson(route('streak-challenge.start'), ['category_id' => $category->getKey()])
        ->assertForbidden();
});
