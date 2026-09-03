<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserQuestionProgress;
use App\Support\StudySessionAnswerKind;

function createMemoryTrainerLifecycleCategory(string $code): LicenseCategory
{
    $category = LicenseCategory::factory()->create([
        'code' => $code,
        'name' => 'Kategoria '.$code,
    ]);

    Question::factory()->for($category, 'licenseCategory')->create();

    return $category;
}

function startLifecycleReviewSession(object $testCase, User $user, LicenseCategory $category): StudySession
{
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($question, 'question')
        ->dueToday()
        ->create(['total_attempts' => 1]);

    $testCase->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'sr_review',
            'question_count' => 50,
        ])
        ->assertRedirect();

    return StudySession::query()
        ->where('user_id', $user->getKey())
        ->where('mode', 'sr_review')
        ->latest('id')
        ->firstOrFail();
}

test('active memory trainer session is retired when locked category changes', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createMemoryTrainerLifecycleCategory('B');
    $categoryC = createMemoryTrainerLifecycleCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->create(['target_category_id' => $categoryB->getKey()]);

    $session = startLifecycleReviewSession($this, $user, $categoryB);

    $user->profile()->update(['target_category_id' => $categoryC->getKey()]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertRedirect(route('review-queue.index'));

    $event = ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.replaced')
        ->firstOrFail();

    expect($session->refresh()->status)->toBe('completed')
        ->and(StudySession::query()->where('user_id', $user->getKey())->where('status', 'in_progress')->count())->toBe(0)
        ->and($event->payload['replacement_mode'])->toBe('category_scope_changed')
        ->and($event->payload['replacement_reason'])->toBe('category_scope_changed');
});

test('expired access retires active memory trainer session before redirecting to activation', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = createMemoryTrainerLifecycleCategory('B');

    UserProfile::factory()
        ->for($user, 'user')
        ->create(['target_category_id' => $category->getKey()]);

    $session = startLifecycleReviewSession($this, $user, $category);

    $user->productAccessGrants()->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertRedirect(route('access.activate'));

    $event = ReviewTrainerEvent::query()
        ->where('study_session_id', $session->getKey())
        ->where('event_name', 'review.replaced')
        ->firstOrFail();

    expect($session->refresh()->status)->toBe('completed')
        ->and($event->payload['replacement_mode'])->toBe('access_lost')
        ->and($event->payload['replacement_reason'])->toBe('access_lost');
});

test('api cannot answer an active memory trainer session outside the current category scope', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createMemoryTrainerLifecycleCategory('B');
    $categoryC = createMemoryTrainerLifecycleCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->create(['target_category_id' => $categoryB->getKey()]);

    $session = startLifecycleReviewSession($this, $user, $categoryB);
    $questionId = collect($session->payload['question_ids'] ?? [])->first();

    $user->profile()->update(['target_category_id' => $categoryC->getKey()]);

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.answers.store', $session), [
            'question_id' => $questionId,
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'user_answer' => 'a',
            'response_time_ms' => 1000,
        ])
        ->assertStatus(409);

    expect($session->refresh()->status)->toBe('completed')
        ->and(StudySessionAnswer::query()->where('study_session_id', $session->getKey())->count())->toBe(0);
});
