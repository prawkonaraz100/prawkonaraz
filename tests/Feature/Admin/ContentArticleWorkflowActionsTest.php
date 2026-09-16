<?php

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Filament\Resources\ContentArticles\Pages\ViewContentArticle;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('article workflow actions delegate review draft and publish transitions to publishing service', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $author = ContentAuthor::factory()->published()->create();
    $article = ContentArticle::factory()->draft()->create([
        'author_id' => $author->id,
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('submitForReview');

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and(AuditLog::query()
            ->where('action', 'content_article.review_submitted')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('returnToDraft');

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Draft);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('submitForReview');

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('markReviewed');

    expect($article->fresh()->reviewed_at)->not->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.reviewed')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('publishNow');

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and(AuditLog::query()
            ->where('action', 'content_article.published')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('schedule action stores a future initial publication through publishing service', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->inReview()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('markReviewed');

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('schedule', data: [
            'scheduled_for' => '2026-09-16 21:30:00',
        ]);

    $article = $article->fresh();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($article->scheduled_for)->not->toBeNull()
        ->and($article->scheduled_for?->getTimestamp())
        ->toBe(Carbon::parse('2026-09-16 21:30:00', 'Europe/Warsaw')->getTimestamp())
        ->and(AuditLog::query()
            ->where('action', 'content_article.scheduled')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('archive and republish actions preserve first publication identity and require fresh review', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();
    $firstPublishedAt = $article->first_published_at?->toDateTimeString();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('archive');

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Archived);

    Carbon::setTestNow('2026-09-16 18:05:00');

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('markReviewed');

    Carbon::setTestNow('2026-09-16 18:10:00');

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('republish');

    $article = $article->fresh();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and(AuditLog::query()
            ->where('action', 'content_article.republished')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('withdraw and restore actions preserve the tombstone until a later successful publish', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('withdraw', data: [
            'withdrawal_reason' => 'Pilna korekta merytoryczna.',
        ]);

    $article = $article->fresh();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Withdrawn)
        ->and($article->withdrawal_reason)->toBe('Pilna korekta merytoryczna.')
        ->and($article->withdrawn_at)->not->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.withdrawn')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('restoreToReview');

    $article = $article->fresh();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and($article->withdrawal_reason)->toBe('Pilna korekta merytoryczna.')
        ->and($article->withdrawn_at)->not->toBeNull();
});

test('featured and breaking actions use audited service controlled exposure state', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'is_featured' => false,
        'is_breaking' => false,
        'editorial_priority' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('feature', data: [
            'editorial_priority' => 30,
        ]);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('breaking', data: [
            'breaking_expires_at' => '2026-09-16 22:00:00',
        ]);

    $article = $article->fresh();

    expect($article->is_featured)->toBeTrue()
        ->and($article->editorial_priority)->toBe(30)
        ->and($article->is_breaking)->toBeTrue()
        ->and($article->breaking_expires_at?->getTimestamp())
        ->toBe(Carbon::parse('2026-09-16 22:00:00', 'Europe/Warsaw')->getTimestamp())
        ->and(AuditLog::query()
            ->where('action', 'content_article.featured_changed')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue()
        ->and(AuditLog::query()
            ->where('action', 'content_article.breaking_changed')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('clearBreaking');

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('unfeature');

    $article = $article->fresh();

    expect($article->is_breaking)->toBeFalse()
        ->and($article->breaking_expires_at)->toBeNull()
        ->and($article->is_featured)->toBeFalse();
});

test('view page exposes the same workflow actions without duplicating transition logic', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(ViewContentArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('markNeedsReview');

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::NeedsReview)
        ->and(AuditLog::query()
            ->where('action', 'content_article.needs_review')
            ->where('entity_id', (string) $article->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});
