<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Support\ContentArticlePublishingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomScheduledArticleAt(string $scheduledFor): ContentArticle
{
    $article = ContentArticle::factory()->inReview()->create();

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $service = app(ContentArticlePublishingService::class);
    $reviewed = $service->markReviewed($article);

    return $service->schedule($reviewed, Carbon::parse($scheduledFor));
}

test('publish due command leaves future scheduled articles unchanged', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');
    $article = newsroomScheduledArticleAt('2026-09-16 10:00:00');

    Carbon::setTestNow('2026-09-16 09:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutputToContain('selected=0 published=0 failed=0 skipped=0')
        ->assertSuccessful();

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($article->fresh()->first_published_at)->toBeNull();
});

test('publish due command publishes a due article through the publishing service', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');
    $article = newsroomScheduledArticleAt('2026-09-16 10:00:00');

    Carbon::setTestNow('2026-09-16 10:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutputToContain('selected=1 published=1 failed=0 skipped=0')
        ->assertSuccessful();

    $published = $article->fresh();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->first_published_at?->toDateTimeString())->toBe('2026-09-16 10:00:00')
        ->and($published->published_at?->toDateTimeString())->toBe('2026-09-16 10:00:00')
        ->and($published->scheduled_for)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.published')
            ->where('entity_id', (string) $article->id)
            ->whereNull('actor_user_id')
            ->where('metadata->trigger', 'scheduler')
            ->exists())->toBeTrue();
});

test('publish due command revalidates eligibility audits failure and continues later records', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $invalid = newsroomScheduledArticleAt('2026-09-16 10:00:00');
    $valid = newsroomScheduledArticleAt('2026-09-16 10:00:00');

    $invalid->category()->update(['is_active' => false]);

    Carbon::setTestNow('2026-09-16 10:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutputToContain('selected=2 published=1 failed=1 skipped=0')
        ->assertFailed();

    expect($invalid->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($invalid->fresh()->first_published_at)->toBeNull()
        ->and($valid->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and(AuditLog::query()
            ->where('action', 'content_article.scheduled_publish_failed')
            ->where('entity_id', (string) $invalid->id)
            ->whereNull('actor_user_id')
            ->exists())->toBeTrue();

    $failure = AuditLog::query()
        ->where('action', 'content_article.scheduled_publish_failed')
        ->where('entity_id', (string) $invalid->id)
        ->latest('id')
        ->firstOrFail();

    expect($failure->metadata['trigger'])->toBe('scheduler')
        ->and($failure->metadata)->not->toHaveKey('body_blocks')
        ->and($failure->metadata)->not->toHaveKey('lead');
});

test('publish due command is idempotent after successful publication', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');
    $article = newsroomScheduledArticleAt('2026-09-16 10:00:00');

    Carbon::setTestNow('2026-09-16 10:00:00');

    $this->artisan('newsroom:publish-due')->assertSuccessful();

    $firstPublishedAt = $article->fresh()->first_published_at?->toDateTimeString();
    $publishedAudits = AuditLog::query()
        ->where('action', 'content_article.published')
        ->where('entity_id', (string) $article->id)
        ->count();

    Carbon::setTestNow('2026-09-16 10:05:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutputToContain('selected=0 published=0 failed=0 skipped=0')
        ->assertSuccessful();

    expect($article->fresh()->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and(AuditLog::query()
            ->where('action', 'content_article.published')
            ->where('entity_id', (string) $article->id)
            ->count())->toBe($publishedAudits);
});

test('publish due command rejects invalid processing limits', function () {
    $this->artisan('newsroom:publish-due', ['--limit' => 0])
        ->expectsOutputToContain('--limit must be an integer between 1 and 1000.')
        ->assertFailed();

    $this->artisan('newsroom:publish-due', ['--limit' => 1001])
        ->assertFailed();
});

test('newsroom publish due command is registered in the production scheduler every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'newsroom:publish-due'));

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('* * * * *')
        ->and($event?->environments)->toContain('production')
        ->and($event?->withoutOverlapping)->toBeTrue();
});
