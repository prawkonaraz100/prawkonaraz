<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\User;
use App\Support\ContentArticlePublishingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomPublishingService(): ContentArticlePublishingService
{
    return app(ContentArticlePublishingService::class);
}

function newsroomReviewReadyArticle(array $attributes = []): ContentArticle
{
    $article = ContentArticle::factory()->inReview()->create($attributes);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    return $article;
}

function newsroomReviewedArticle(array $attributes = [], ?User $actor = null): ContentArticle
{
    $article = newsroomReviewReadyArticle($attributes);

    return newsroomPublishingService()->markReviewed($article, $actor);
}

test('draft can submit for review and return to draft through audited transitions', function () {
    $actor = User::factory()->create();
    $author = ContentAuthor::factory()->published()->create();
    $article = ContentArticle::factory()->draft()->create([
        'author_id' => $author->id,
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $submitted = newsroomPublishingService()->submitForReview($article, $actor);

    expect($submitted->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and($submitted->reviewed_at)->toBeNull();

    $draft = newsroomPublishingService()->returnToDraft($submitted, $actor);

    expect($draft->workflow_status)->toBe(ContentArticleWorkflowStatus::Draft)
        ->and($draft->reviewed_at)->toBeNull()
        ->and(AuditLog::query()->where('action', 'content_article.review_submitted')->where('actor_user_id', $actor->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'content_article.returned_to_draft')->where('actor_user_id', $actor->id)->exists())->toBeTrue();
});

test('review blocks missing publication baseline and unpublished dependencies', function () {
    $service = newsroomPublishingService();

    $missingSource = ContentArticle::factory()->inReview()->create();

    expect(fn () => $service->markReviewed($missingSource))
        ->toThrow(DomainException::class, 'requires at least one source');

    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    $article = newsroomReviewReadyArticle([
        'category_id' => $inactiveCategory->id,
    ]);

    expect(fn () => $service->markReviewed($article))
        ->toThrow(DomainException::class, 'active category');

    $unpublishedAuthor = ContentAuthor::factory()->create();
    $article = newsroomReviewReadyArticle([
        'author_id' => $unpublishedAuthor->id,
    ]);

    expect(fn () => $service->markReviewed($article))
        ->toThrow(DomainException::class, 'published author');
});

test('review validates body hero and key points contracts', function () {
    $service = newsroomPublishingService();

    $emptyBody = newsroomReviewReadyArticle([
        'body_blocks' => [],
    ]);

    expect(fn () => $service->markReviewed($emptyBody))
        ->toThrow(DomainException::class, 'at least one renderable body block');

    $heroWithoutAlt = newsroomReviewReadyArticle([
        'hero_image_path' => 'newsroom/articles/source/example.webp',
        'hero_image_alt' => null,
        'hero_image_width' => 1200,
        'hero_image_height' => 630,
    ]);

    expect(fn () => $service->markReviewed($heroWithoutAlt))
        ->toThrow(DomainException::class, 'hero_image_alt');

    $invalidKeyPoints = newsroomReviewReadyArticle([
        'key_points' => ['<b>Niebezpieczne</b>', 'Drugi punkt'],
    ]);

    expect(fn () => $service->markReviewed($invalidKeyPoints))
        ->toThrow(DomainException::class, 'plain text');
});

test('initial publish is atomic audited and keeps user actor separate from content author', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $actor = User::factory()->create();
    $article = newsroomReviewedArticle([], $actor);
    $authorId = $article->author_id;

    $published = newsroomPublishingService()->publish($article, $actor);
    $audit = AuditLog::query()
        ->where('action', 'content_article.published')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->first_published_at?->toDateTimeString())->toBe('2026-09-16 08:00:00')
        ->and($published->published_at?->toDateTimeString())->toBe('2026-09-16 08:00:00')
        ->and($published->public_state_changed_at?->toDateTimeString())->toBe('2026-09-16 08:00:00')
        ->and($published->author_id)->toBe($authorId)
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['trigger'])->toBe('user')
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead');
});

test('schedule is initial publish only and due publication preserves date semantics', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $article = newsroomReviewedArticle();
    $scheduled = newsroomPublishingService()->schedule(
        $article,
        Carbon::parse('2026-09-16 10:00:00'),
    );

    expect($scheduled->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($scheduled->scheduled_for?->toDateTimeString())->toBe('2026-09-16 10:00:00')
        ->and($scheduled->first_published_at)->toBeNull()
        ->and($scheduled->published_at)->toBeNull();

    expect(fn () => newsroomPublishingService()->publish($scheduled, null, 'scheduler'))
        ->toThrow(DomainException::class, 'not due');

    Carbon::setTestNow('2026-09-16 10:00:00');

    $published = newsroomPublishingService()->publish($scheduled->fresh(), null, 'scheduler');
    $audit = AuditLog::query()
        ->where('action', 'content_article.published')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->firstOrFail();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->scheduled_for)->toBeNull()
        ->and($published->first_published_at?->toDateTimeString())->toBe('2026-09-16 10:00:00')
        ->and($audit->actor_user_id)->toBeNull()
        ->and($audit->metadata['trigger'])->toBe('scheduler');

    expect(fn () => newsroomPublishingService()->schedule(
        $published,
        Carbon::parse('2026-09-17 10:00:00'),
    ))->toThrow(DomainException::class);
});

test('needs review clears breaking and republish keeps first published timestamp stable', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $article = newsroomReviewedArticle([
        'is_breaking' => true,
        'breaking_expires_at' => Carbon::parse('2026-09-16 12:00:00'),
    ]);
    $published = newsroomPublishingService()->publish($article);
    $firstPublishedAt = $published->first_published_at?->toDateTimeString();

    Carbon::setTestNow('2026-09-16 09:00:00');

    $needsReview = newsroomPublishingService()->markNeedsReview($published);

    expect($needsReview->workflow_status)->toBe(ContentArticleWorkflowStatus::NeedsReview)
        ->and($needsReview->is_breaking)->toBeFalse()
        ->and($needsReview->breaking_expires_at)->toBeNull()
        ->and($needsReview->isPubliclyVisible())->toBeTrue()
        ->and($needsReview->isActivelyDistributed())->toBeFalse();

    expect(fn () => newsroomPublishingService()->publish($needsReview))
        ->toThrow(DomainException::class, 'fresh review');

    Carbon::setTestNow('2026-09-16 10:00:00');
    $reviewed = newsroomPublishingService()->markReviewed($needsReview->fresh());

    Carbon::setTestNow('2026-09-16 11:00:00');
    $republished = newsroomPublishingService()->publish($reviewed);

    expect($republished->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($republished->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and($republished->published_at?->toDateTimeString())->toBe('2026-09-16 11:00:00')
        ->and($republished->needs_review_at)->toBeNull();
});

test('archived article stays historical public but requires fresh review for dedicated republish', function () {
    Carbon::setTestNow('2026-09-16 12:00:00');

    $article = ContentArticle::factory()->published()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();
    $firstPublishedAt = $article->first_published_at?->toDateTimeString();

    $archived = newsroomPublishingService()->archive($article);

    expect($archived->workflow_status)->toBe(ContentArticleWorkflowStatus::Archived)
        ->and($archived->isPubliclyVisible())->toBeTrue()
        ->and($archived->isActivelyDistributed())->toBeFalse()
        ->and($archived->archived_at)->not->toBeNull();

    expect(fn () => newsroomPublishingService()->republish($archived))
        ->toThrow(DomainException::class, 'fresh review')
        ->and(fn () => newsroomPublishingService()->publish($archived))
        ->toThrow(DomainException::class);

    Carbon::setTestNow('2026-09-16 12:05:00');
    $reviewed = newsroomPublishingService()->markReviewed($archived->fresh());

    Carbon::setTestNow('2026-09-16 12:10:00');
    $republished = newsroomPublishingService()->republish($reviewed);

    expect($republished->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($republished->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and($republished->archived_at)->toBeNull();
});

test('withdrawal requires reason keeps tombstone through restore and clears it only after reviewed publish', function () {
    Carbon::setTestNow('2026-09-16 13:00:00');

    $article = ContentArticle::factory()->breaking()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();

    expect(fn () => newsroomPublishingService()->withdraw($article, '   '))
        ->toThrow(InvalidArgumentException::class, 'reason is required');

    $withdrawn = newsroomPublishingService()->withdraw($article, 'Błąd merytoryczny.');

    expect($withdrawn->workflow_status)->toBe(ContentArticleWorkflowStatus::Withdrawn)
        ->and($withdrawn->withdrawal_reason)->toBe('Błąd merytoryczny.')
        ->and($withdrawn->withdrawn_at)->not->toBeNull()
        ->and($withdrawn->is_breaking)->toBeFalse()
        ->and($withdrawn->breaking_expires_at)->toBeNull()
        ->and($withdrawn->isPubliclyVisible())->toBeFalse();

    $restored = newsroomPublishingService()->restoreToReview($withdrawn);

    expect($restored->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and($restored->withdrawn_at)->not->toBeNull()
        ->and($restored->withdrawal_reason)->toBe('Błąd merytoryczny.')
        ->and($restored->reviewed_at)->toBeNull();

    expect(fn () => newsroomPublishingService()->publish($restored))
        ->toThrow(DomainException::class, 'completed review');

    Carbon::setTestNow('2026-09-16 13:05:00');
    $reviewed = newsroomPublishingService()->markReviewed($restored->fresh());

    Carbon::setTestNow('2026-09-16 13:10:00');
    $published = newsroomPublishingService()->publish($reviewed);

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->withdrawn_at)->toBeNull()
        ->and($published->withdrawal_reason)->toBeNull();
});

test('invalid workflow transition leaves article and audit trail unchanged', function () {
    $article = ContentArticle::factory()->draft()->create();
    $beforeAudits = AuditLog::query()->count();

    expect(fn () => newsroomPublishingService()->archive($article))
        ->toThrow(DomainException::class);

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Draft)
        ->and(AuditLog::query()->count())->toBe($beforeAudits);
});

test('workflow event is delivered only after outer commit and never after rollback', function () {
    Carbon::setTestNow('2026-09-16 14:00:00');

    $article = newsroomReviewedArticle();
    $seen = [];

    Event::listen(
        ContentArticleWorkflowTransitioned::class,
        function (ContentArticleWorkflowTransitioned $event) use (&$seen): void {
            $seen[] = $event;
        },
    );

    DB::beginTransaction();

    try {
        newsroomPublishingService()->publish($article->fresh());

        expect($seen)->toBe([]);
    } finally {
        DB::rollBack();
    }

    expect($seen)->toBe([])
        ->and($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and(AuditLog::query()->where('action', 'content_article.published')->exists())->toBeFalse();

    $published = newsroomPublishingService()->publish($article->fresh());

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($seen)->toHaveCount(1)
        ->and($seen[0]->articleId)->toBe($article->id)
        ->and($seen[0]->toStatus)->toBe(ContentArticleWorkflowStatus::Published->value);
});

test('breaking publication invariant rejects non news and expired breaking state', function () {
    Carbon::setTestNow('2026-09-16 15:00:00');

    $guide = newsroomReviewReadyArticle([
        'type' => 'guide',
        'is_breaking' => true,
        'breaking_expires_at' => Carbon::parse('2026-09-16 16:00:00'),
    ]);

    expect(fn () => newsroomPublishingService()->markReviewed($guide))
        ->toThrow(DomainException::class, 'must be a news');

    $expired = newsroomReviewReadyArticle([
        'is_breaking' => true,
        'breaking_expires_at' => Carbon::parse('2026-09-16 14:00:00'),
    ]);

    expect(fn () => newsroomPublishingService()->markReviewed($expired))
        ->toThrow(DomainException::class, 'future expiration');
});
