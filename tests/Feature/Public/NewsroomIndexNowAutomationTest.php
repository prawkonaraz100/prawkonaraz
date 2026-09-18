<?php

use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\IndexNowUrlSubmission;
use App\Support\ContentArticleEditToken;
use App\Support\ContentArticlePublishingService;
use App\Support\ContentArticleSlugService;
use App\Support\IndexNowQueueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('newsroom.public_enabled', true);
    config()->set('indexnow.enabled', true);
    config()->set('indexnow.automation_enabled', true);
    config()->set('indexnow.host', 'prawkonaraz.pl');
    config()->set('indexnow.queue_debounce_minutes', 0);

    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

function newsroomIndexNowReviewedArticle(array $attributes = []): ContentArticle
{
    $article = ContentArticle::factory()->inReview()->create($attributes);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    return app(ContentArticlePublishingService::class)->markReviewed($article);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newsroomIndexNowPublicUpdatePayload(ContentArticle $article, array $overrides = []): array
{
    $article = $article->fresh();

    $sources = $article->sources()
        ->get()
        ->map(function (ContentArticleSource $source): array {
            $type = $source->source_type;

            return [
                'source_type' => $type instanceof ContentArticleSourceType
                    ? $type->value
                    : (string) $type,
                'publisher' => $source->publisher,
                'title' => $source->title,
                'url' => $source->url,
                'published_at' => $source->published_at,
                'accessed_at' => $source->accessed_at,
                'is_primary' => $source->is_primary,
                'is_official' => $source->is_official,
                'is_publicly_cited' => $source->is_publicly_cited,
                'note' => $source->note,
            ];
        })
        ->values()
        ->all();

    $type = $article->type;

    return array_replace([
        'type' => $type instanceof ContentArticleType ? $type->value : (string) $type,
        'category_id' => $article->category_id,
        'author_id' => $article->author_id,
        'reviewer_id' => $article->reviewer_id,
        'title' => $article->title,
        'slug' => $article->slug,
        'lead' => $article->lead,
        'body_blocks' => $article->body_blocks,
        'body_schema_version' => $article->body_schema_version,
        'sources' => $sources,
        'question_relations' => [],
        'legal_unit_relations' => [],
        'traffic_sign_relations' => [],
        'topic_ids' => [],
    ], $overrides);
}

test('first publish queues canonical newsroom url as created after commit', function () {
    $article = newsroomIndexNowReviewedArticle([
        'slug' => 'pierwsza-publikacja-indexnow',
    ]);

    $published = app(ContentArticlePublishingService::class)->publish($article);

    $submission = IndexNowUrlSubmission::query()->sole();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($submission->url)->toBe('https://prawkonaraz.pl/aktualnosci/pierwsza-publikacja-indexnow')
        ->and($submission->event_type)->toBe(IndexNowUrlSubmission::EVENT_CREATED)
        ->and($submission->source)->toBe('content_article.published');
});

test('archive does not queue delete while republish queues updated', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'archiwum-indexnow',
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $service = app(ContentArticlePublishingService::class);
    $archived = $service->archive($article);

    expect($archived->workflow_status)->toBe(ContentArticleWorkflowStatus::Archived)
        ->and($archived->isPubliclyVisible())->toBeTrue()
        ->and(IndexNowUrlSubmission::query()->count())->toBe(0);

    $reviewed = $service->markReviewed($archived);
    $republished = $service->republish($reviewed);

    $submission = IndexNowUrlSubmission::query()->sole();

    expect($republished->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($submission->event_type)->toBe(IndexNowUrlSubmission::EVENT_UPDATED)
        ->and($submission->url)->toBe('https://prawkonaraz.pl/aktualnosci/archiwum-indexnow')
        ->and($submission->source)->toBe('content_article.republished');
});

test('withdraw queues deleted only after 410 state and restore to review stays silent until republish', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'wycofany-indexnow',
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $service = app(ContentArticlePublishingService::class);
    $withdrawn = $service->withdraw($article, 'Test wycofania.');

    $deleted = IndexNowUrlSubmission::query()->sole();

    expect($withdrawn->workflow_status)->toBe(ContentArticleWorkflowStatus::Withdrawn)
        ->and($withdrawn->isPubliclyVisible())->toBeFalse()
        ->and($deleted->event_type)->toBe(IndexNowUrlSubmission::EVENT_DELETED)
        ->and($deleted->url)->toBe('https://prawkonaraz.pl/aktualnosci/wycofany-indexnow');

    IndexNowUrlSubmission::query()->delete();

    $restored = $service->restoreToReview($withdrawn);

    expect($restored->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and(IndexNowUrlSubmission::query()->count())->toBe(0);

    $reviewed = $service->markReviewed($restored);
    $published = $service->publish($reviewed);
    $updated = IndexNowUrlSubmission::query()->sole();

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($updated->event_type)->toBe(IndexNowUrlSubmission::EVENT_UPDATED)
        ->and($updated->url)->toBe('https://prawkonaraz.pl/aktualnosci/wycofany-indexnow');
});

test('substantive public update queues updated while semantic no-op stays silent', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'aktualizacja-indexnow',
        'title' => 'Tytuł przed zmianą',
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $service = app(ContentArticlePublishingService::class);
    $token = app(ContentArticleEditToken::class)->make($article->fresh());

    $updated = $service->applyPublicUpdate(
        $article,
        newsroomIndexNowPublicUpdatePayload($article, [
            'title' => 'Tytuł po zmianie',
        ]),
        $token,
    );

    $submission = IndexNowUrlSubmission::query()->sole();

    expect($updated->title)->toBe('Tytuł po zmianie')
        ->and($submission->event_type)->toBe(IndexNowUrlSubmission::EVENT_UPDATED)
        ->and($submission->source)->toBe('content_article.public_updated');

    IndexNowUrlSubmission::query()->delete();

    $fresh = $updated->fresh();
    $noOpToken = app(ContentArticleEditToken::class)->make($fresh);

    $service->applyPublicUpdate(
        $fresh,
        newsroomIndexNowPublicUpdatePayload($fresh),
        $noOpToken,
    );

    expect(IndexNowUrlSubmission::query()->count())->toBe(0);
});

test('published slug change queues old redirect and new canonical as updated exactly once', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'stary-indexnow',
    ]);

    $updated = app(ContentArticleSlugService::class)->changeSlug(
        $article,
        'nowy-indexnow',
    );

    $submissions = IndexNowUrlSubmission::query()
        ->orderBy('url')
        ->get();

    expect($updated->slug)->toBe('nowy-indexnow')
        ->and($submissions)->toHaveCount(2)
        ->and($submissions->pluck('url')->all())->toBe([
            'https://prawkonaraz.pl/aktualnosci/nowy-indexnow',
            'https://prawkonaraz.pl/aktualnosci/stary-indexnow',
        ])
        ->and($submissions->pluck('event_type')->unique()->all())->toBe([
            IndexNowUrlSubmission::EVENT_UPDATED,
        ])
        ->and($submissions->pluck('enqueued_count')->all())->toBe([1, 1]);
});

test('public update with slug change relies on slug events without duplicate canonical enqueue', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'public-update-stary-indexnow',
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();

    $token = app(ContentArticleEditToken::class)->make($article->fresh());

    $updated = app(ContentArticlePublishingService::class)->applyPublicUpdate(
        $article,
        newsroomIndexNowPublicUpdatePayload($article, [
            'slug' => 'public-update-nowy-indexnow',
            'title' => 'Tytuł po zmianie adresu',
        ]),
        $token,
    );

    $submissions = IndexNowUrlSubmission::query()
        ->orderBy('url')
        ->get();

    expect($updated->slug)->toBe('public-update-nowy-indexnow')
        ->and($submissions)->toHaveCount(2)
        ->and($submissions->pluck('enqueued_count')->all())->toBe([1, 1])
        ->and($submissions->pluck('event_type')->unique()->all())->toBe([
            IndexNowUrlSubmission::EVENT_UPDATED,
        ]);
});

test('scheduled article does not queue before its publication time', function () {
    $article = newsroomIndexNowReviewedArticle([
        'slug' => 'scheduled-indexnow',
    ]);

    $scheduled = app(ContentArticlePublishingService::class)->schedule(
        $article,
        now()->addHour(),
    );

    expect($scheduled->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and(IndexNowUrlSubmission::query()->count())->toBe(0);
});

test('noindex and disabled newsroom gate suppress automatic queue rows', function () {
    $noindex = newsroomIndexNowReviewedArticle([
        'slug' => 'noindex-indexnow',
        'robots' => 'noindex,follow',
    ]);

    app(ContentArticlePublishingService::class)->publish($noindex);

    expect(IndexNowUrlSubmission::query()->count())->toBe(0);

    config()->set('newsroom.public_enabled', false);

    $gated = newsroomIndexNowReviewedArticle([
        'slug' => 'gate-off-indexnow',
    ]);

    app(ContentArticlePublishingService::class)->publish($gated);

    expect(IndexNowUrlSubmission::query()->count())->toBe(0);
});

test('outer rollback does not create IndexNow queue row', function () {
    $article = newsroomIndexNowReviewedArticle([
        'slug' => 'rollback-indexnow',
    ]);

    DB::beginTransaction();

    try {
        $published = app(ContentArticlePublishingService::class)->publish($article->fresh());

        expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
            ->and(IndexNowUrlSubmission::query()->count())->toBe(0);
    } finally {
        DB::rollBack();
    }

    expect($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::InReview)
        ->and(IndexNowUrlSubmission::query()->count())->toBe(0);
});

test('IndexNow queue exception does not block committed publication', function () {
    $article = newsroomIndexNowReviewedArticle([
        'slug' => 'awaria-kolejki-indexnow',
    ]);

    $this->mock(IndexNowQueueService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('enqueueUrl')
            ->once()
            ->andThrow(new RuntimeException('IndexNow queue unavailable.'));
    });

    $published = app(ContentArticlePublishingService::class)->publish($article);

    expect($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Published);
});
