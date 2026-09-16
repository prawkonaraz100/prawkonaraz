<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Support\ContentArticlePublishingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

function newsroomSchedulerService(): ContentArticlePublishingService
{
    return app(ContentArticlePublishingService::class);
}

function newsroomScheduledArticle(Carbon $scheduledFor): ContentArticle
{
    $article = ContentArticle::factory()->inReview()->create();

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $reviewed = newsroomSchedulerService()->markReviewed($article);

    return newsroomSchedulerService()->schedule($reviewed, $scheduledFor);
}

test('publish due command leaves future scheduled articles untouched', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $article = newsroomScheduledArticle(Carbon::parse('2026-09-16 10:00:00'));

    Carbon::setTestNow('2026-09-16 09:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutput('Newsroom due publish complete: published=0 failed=0 skipped=0.')
        ->assertExitCode(0);

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($article->fresh()->first_published_at)->toBeNull()
        ->and(AuditLog::query()->where('action', 'content_article.published')->where('entity_id', (string) $article->id)->exists())->toBeFalse();
});

test('publish due command publishes valid due article once and repeated run is safe', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $article = newsroomScheduledArticle(Carbon::parse('2026-09-16 09:00:00'));

    Carbon::setTestNow('2026-09-16 09:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutput('Newsroom due publish complete: published=1 failed=0 skipped=0.')
        ->assertExitCode(0);

    $published = $article->fresh();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->first_published_at?->toDateTimeString())->toBe('2026-09-16 09:00:00')
        ->and($published->scheduled_for)->toBeNull();

    $audit = AuditLog::query()
        ->where('action', 'content_article.published')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($audit->actor_user_id)->toBeNull()
        ->and($audit->metadata['trigger'])->toBe('scheduler');

    $this->artisan('newsroom:publish-due')
        ->expectsOutput('Newsroom due publish complete: published=0 failed=0 skipped=0.')
        ->assertExitCode(0);

    expect(AuditLog::query()
        ->where('action', 'content_article.published')
        ->where('entity_id', (string) $article->id)
        ->count())->toBe(1);
});

test('due-time revalidation failures are audited without blocking later valid articles', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');
    $scheduledFor = Carbon::parse('2026-09-16 09:00:00');

    $invalidCategory = newsroomScheduledArticle($scheduledFor);
    $invalidCategory->category()->update(['is_active' => false]);

    $invalidAuthor = newsroomScheduledArticle($scheduledFor);
    $invalidAuthor->author()->update([
        'is_published' => false,
        'published_at' => null,
    ]);

    $invalidSource = newsroomScheduledArticle($scheduledFor);
    $invalidSource->sources()->delete();

    $invalidBody = newsroomScheduledArticle($scheduledFor);
    $invalidBody->update(['body_blocks' => []]);

    $invalidReview = newsroomScheduledArticle($scheduledFor);
    $invalidReview->update(['reviewed_at' => null]);

    $valid = newsroomScheduledArticle($scheduledFor);

    Carbon::setTestNow('2026-09-16 09:00:00');

    $this->artisan('newsroom:publish-due')
        ->expectsOutput('Newsroom due publish complete: published=1 failed=5 skipped=0.')
        ->assertExitCode(1);

    foreach ([$invalidCategory, $invalidAuthor, $invalidSource, $invalidBody, $invalidReview] as $invalid) {
        expect($invalid->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
            ->and($invalid->fresh()->first_published_at)->toBeNull();

        $failureAudit = AuditLog::query()
            ->where('action', 'content_article.scheduled_publish_failed')
            ->where('entity_id', (string) $invalid->id)
            ->sole();

        expect($failureAudit->actor_user_id)->toBeNull()
            ->and($failureAudit->metadata['trigger'])->toBe('scheduler')
            ->and($failureAudit->metadata['workflow_status'])->toBe(ContentArticleWorkflowStatus::Scheduled->value)
            ->and($failureAudit->metadata)->toHaveKeys([
                'scheduled_for',
                'error_class',
                'error_message',
            ])
            ->and($failureAudit->metadata)->not->toHaveKey('body_blocks')
            ->and($failureAudit->metadata)->not->toHaveKey('lead');
    }

    expect($valid->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Published);

    $this->artisan('newsroom:publish-due')
        ->assertExitCode(1);

    expect(AuditLog::query()
        ->where('action', 'content_article.scheduled_publish_failed')
        ->whereIn('entity_id', collect([$invalidCategory, $invalidAuthor, $invalidSource, $invalidBody, $invalidReview])
            ->map(fn (ContentArticle $article): string => (string) $article->id)
            ->all())
        ->count())->toBe(5);
});

test('scheduler never performs a scheduled republish for a previously published article', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $article = ContentArticle::factory()->published()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();
    $firstPublishedAt = $article->first_published_at?->toDateTimeString();

    $article->forceFill([
        'workflow_status' => ContentArticleWorkflowStatus::Scheduled,
        'scheduled_for' => Carbon::parse('2026-09-16 09:00:00'),
    ])->save();

    Carbon::setTestNow('2026-09-16 09:00:00');

    $this->artisan('newsroom:publish-due')
        ->assertExitCode(1);

    $article->refresh();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($article->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and(AuditLog::query()
            ->where('action', 'content_article.scheduled_publish_failed')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeTrue();
});

test('newsroom due publication command is registered every minute for production', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) ($event->command ?? ''), 'newsroom:publish-due'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->environments)->toContain('production');
});
