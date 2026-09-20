<?php

use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\User;
use App\Support\ContentArticleEditToken;
use App\Support\ContentArticlePublishingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newsroomPublicUpdatePayload(ContentArticle $article, array $overrides = []): array
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

test('public update is atomic stale guarded audited and marks substantive freshness', function () {
    Carbon::setTestNow('2026-09-16 18:30:00');

    $actor = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Stary tytuł',
        'lead' => 'Stary lead',
    ]);
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Stare źródło',
            'url' => 'https://example.test/stare',
            'is_publicly_cited' => true,
        ]);

    $firstPublishedAt = $article->first_published_at?->toDateTimeString();
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    $updated = newsroomPublishingService()->applyPublicUpdate(
        $article,
        newsroomPublicUpdatePayload($article, [
            'title' => 'Nowy tytuł publiczny',
            'lead' => 'Nowy lead publiczny',
            'body_blocks' => [[
                'type' => 'context',
                'data' => [
                    'variant' => 'uwaga',
                    'title' => null,
                    'text' => 'Nowa treść publiczna.',
                ],
            ]],
            'sources' => [[
                'source_type' => ContentArticleSourceType::Official->value,
                'publisher' => 'Instytucja',
                'title' => 'Nowe źródło',
                'url' => 'https://example.test/nowe',
                'published_at' => null,
                'accessed_at' => null,
                'is_primary' => true,
                'is_official' => true,
                'is_publicly_cited' => true,
                'note' => 'Prywatna notatka źródła.',
            ]],
        ]),
        $loadedToken,
        $actor,
    );

    $audit = AuditLog::query()
        ->where('action', 'content_article.public_updated')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($updated->title)->toBe('Nowy tytuł publiczny')
        ->and($updated->lead)->toBe('Nowy lead publiczny')
        ->and($updated->body_blocks[0]['data']['text'])->toBe('Nowa treść publiczna.')
        ->and($updated->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($updated->first_published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and($updated->last_substantive_update_at?->toDateTimeString())->toBe('2026-09-16 18:30:00')
        ->and($updated->sources()->sole()->title)->toBe('Nowe źródło')
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['substantive_change'])->toBeTrue()
        ->and($audit->metadata['source_count'])->toBe(1)
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead')
        ->and($audit->metadata)->not->toHaveKey('editorial_note')
        ->and($audit->metadata)->not->toHaveKey('note');
});

test('public update without a public semantic change does not bump substantive freshness', function () {
    Carbon::setTestNow('2026-09-16 18:35:00');

    $article = ContentArticle::factory()->published()->create([
        'last_substantive_update_at' => Carbon::parse('2026-09-15 12:00:00'),
    ]);
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'sort_order' => 1,
        ]);

    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    $updated = newsroomPublishingService()->applyPublicUpdate(
        $article,
        newsroomPublicUpdatePayload($article),
        $loadedToken,
    );

    $audit = AuditLog::query()
        ->where('action', 'content_article.public_updated')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($updated->last_substantive_update_at?->toDateTimeString())->toBe('2026-09-15 12:00:00')
        ->and($audit->metadata['substantive_change'])->toBeFalse();
});

test('public update rejects same-second stale source state without overwriting the concurrent change', function () {
    Carbon::setTestNow('2026-09-16 18:40:00');

    $article = ContentArticle::factory()->published()->create();
    $source = ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'title' => 'Źródło pierwotne',
        ]);

    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());
    $loadedUpdatedAt = $article->fresh()->updated_at?->toDateTimeString();

    $source->update([
        'title' => 'Zmiana równoległa w tej samej sekundzie',
    ]);

    expect($article->fresh()->updated_at?->toDateTimeString())->toBe($loadedUpdatedAt);

    expect(fn () => newsroomPublishingService()->applyPublicUpdate(
        $article,
        newsroomPublicUpdatePayload($article, [
            'title' => 'Próba nadpisania',
            'sources' => [[
                'source_type' => ContentArticleSourceType::Official->value,
                'publisher' => null,
                'title' => 'Stara wersja źródła',
                'url' => 'https://example.test/source',
                'published_at' => null,
                'accessed_at' => null,
                'is_primary' => false,
                'is_official' => true,
                'is_publicly_cited' => true,
                'note' => null,
            ]],
        ]),
        $loadedToken,
    ))->toThrow(DomainException::class, 'changed after this form was loaded');

    expect($article->fresh()->title)->not->toBe('Próba nadpisania')
        ->and($source->fresh()->title)->toBe('Zmiana równoległa w tej samej sekundzie')
        ->and(AuditLog::query()
            ->where('action', 'content_article.public_updated')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('invalid public update rolls back article and source mutations', function () {
    Carbon::setTestNow('2026-09-16 18:50:00');

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed błędem',
    ]);
    $source = ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'title' => 'Źródło przed błędem',
        ]);
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    expect(fn () => newsroomPublishingService()->applyPublicUpdate(
        $article,
        newsroomPublicUpdatePayload($article, [
            'title' => 'Tytuł nie może zostać',
            'sources' => [],
        ]),
        $loadedToken,
    ))->toThrow(DomainException::class, 'requires at least one source');

    expect($article->fresh()->title)->toBe('Tytuł przed błędem')
        ->and($source->fresh()->title)->toBe('Źródło przed błędem')
        ->and($article->fresh()->sources()->count())->toBe(1)
        ->and($article->fresh()->last_substantive_update_at)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.public_updated')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('correction uses the public update boundary and records a public correction note atomically', function () {
    Carbon::setTestNow('2026-09-16 19:00:00');

    $actor = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed korektą',
        'lead' => 'Lead przed korektą',
    ]);
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Źródło korekty',
            'url' => 'https://example.test/correction-source',
            'is_publicly_cited' => true,
        ]);

    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    $updated = newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, [
            'title' => 'Tytuł po korekcie',
            'lead' => 'Lead po korekcie',
        ]),
        $loadedToken,
        'Poprawiono błędną datę obowiązywania przepisu.',
        $actor,
    );

    $audit = AuditLog::query()
        ->where('action', 'content_article.corrected')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($updated->title)->toBe('Tytuł po korekcie')
        ->and($updated->lead)->toBe('Lead po korekcie')
        ->and($updated->correction_note)->toBe('Poprawiono błędną datę obowiązywania przepisu.')
        ->and($updated->last_substantive_update_at?->toDateTimeString())->toBe('2026-09-16 19:00:00')
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['substantive_change'])->toBeTrue()
        ->and($audit->metadata['correction_applied'])->toBeTrue()
        ->and($audit->metadata)->not->toHaveKey('correction_note')
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead')
        ->and($audit->metadata)->not->toHaveKey('editorial_note');
});

test('correction requires a non-empty public correction note', function () {
    $article = ContentArticle::factory()->published()->create();
    ContentArticleSource::factory()->for($article, 'article')->create();
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    expect(fn () => newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, ['title' => 'Nie zapisuj tej zmiany']),
        $loadedToken,
        '   ',
    ))->toThrow(InvalidArgumentException::class, 'Correction note is required');

    expect($article->fresh()->title)->not->toBe('Nie zapisuj tej zmiany')
        ->and($article->fresh()->correction_note)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.corrected')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('correction requires a completed fresh review before changing public content', function () {
    $article = ContentArticle::factory()->published()->create([
        'reviewed_at' => null,
        'title' => 'Tytuł bez review',
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    expect(fn () => newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, ['title' => 'Zmiana bez review']),
        $loadedToken,
        'Istotna korekta wymagająca review.',
    ))->toThrow(DomainException::class, 'completed review');

    expect($article->fresh()->title)->toBe('Tytuł bez review')
        ->and($article->fresh()->correction_note)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.corrected')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('correction rejects a review older than the latest needs-review transition', function () {
    Carbon::setTestNow('2026-09-16 19:05:00');

    $article = ContentArticle::factory()->needsReview()->create([
        'title' => 'Tytuł wymagający świeżego review',
        'reviewed_at' => Carbon::parse('2026-09-16 18:00:00'),
        'needs_review_at' => Carbon::parse('2026-09-16 18:30:00'),
    ]);
    ContentArticleSource::factory()->for($article, 'article')->create();
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    expect(fn () => newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, ['title' => 'Zmiana po starym review']),
        $loadedToken,
        'Istotna korekta po nieaktualnym review.',
    ))->toThrow(DomainException::class, 'fresh review');

    expect($article->fresh()->title)->toBe('Tytuł wymagający świeżego review')
        ->and($article->fresh()->correction_note)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.corrected')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('correction rejects stale editor state before writing the correction note', function () {
    Carbon::setTestNow('2026-09-16 19:10:00');

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed konfliktem',
    ]);
    $source = ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'title' => 'Źródło załadowane',
        ]);

    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    $source->update([
        'title' => 'Równoległa zmiana źródła',
    ]);

    expect(fn () => newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, ['title' => 'Nie nadpisuj']),
        $loadedToken,
        'Nota nie może zostać zapisana.',
    ))->toThrow(DomainException::class, 'changed after this form was loaded');

    expect($article->fresh()->title)->toBe('Tytuł przed konfliktem')
        ->and($article->fresh()->correction_note)->toBeNull()
        ->and($source->fresh()->title)->toBe('Równoległa zmiana źródła')
        ->and(AuditLog::query()
            ->where('action', 'content_article.corrected')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
});

test('invalid correction rolls back content sources and correction note together', function () {
    Carbon::setTestNow('2026-09-16 19:20:00');

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed nieudaną korektą',
    ]);
    $source = ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'title' => 'Źródło przed nieudaną korektą',
        ]);
    $loadedToken = app(ContentArticleEditToken::class)->make($article->fresh());

    expect(fn () => newsroomPublishingService()->applyCorrection(
        $article,
        newsroomPublicUpdatePayload($article, [
            'title' => 'Tytuł ma zostać wycofany',
            'sources' => [],
        ]),
        $loadedToken,
        'Ta nota także ma zostać wycofana.',
    ))->toThrow(DomainException::class, 'requires at least one source');

    expect($article->fresh()->title)->toBe('Tytuł przed nieudaną korektą')
        ->and($article->fresh()->correction_note)->toBeNull()
        ->and($source->fresh()->title)->toBe('Źródło przed nieudaną korektą')
        ->and($article->fresh()->sources()->count())->toBe(1)
        ->and($article->fresh()->last_substantive_update_at)->toBeNull()
        ->and(AuditLog::query()
            ->where('action', 'content_article.corrected')
            ->where('entity_id', (string) $article->id)
            ->exists())->toBeFalse();
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
        ->toThrow(DomainException::class)
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

test('source urls remain declarative metadata and never trigger server side fetches', function () {
    Http::preventStrayRequests();

    $actor = User::factory()->admin()->create();
    $article = ContentArticle::factory()->inReview()->create();

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Źródło bez dereferencji',
            'url' => 'http://127.0.0.1:9/internal-probe',
            'is_primary' => true,
            'is_official' => true,
            'is_publicly_cited' => true,
        ]);

    $reviewed = newsroomPublishingService()->markReviewed($article, $actor);
    $published = newsroomPublishingService()->publish($reviewed, $actor);
    $loadedToken = app(ContentArticleEditToken::class)->make($published->fresh());

    $updated = newsroomPublishingService()->applyPublicUpdate(
        $published,
        newsroomPublicUpdatePayload($published, [
            'sources' => [[
                'source_type' => ContentArticleSourceType::Official->value,
                'title' => 'Link-local source URL pozostaje tylko metadanymi',
                'url' => 'http://169.254.169.254/latest/meta-data/',
                'is_primary' => true,
                'is_official' => true,
                'is_publicly_cited' => true,
            ]],
        ]),
        $loadedToken,
        $actor,
    );

    Http::assertNothingSent();

    expect($updated->fresh()->sources()->sole()->url)
        ->toBe('http://169.254.169.254/latest/meta-data/');
});

test('source policy allows private interview evidence without a url but rejects unsafe source urls', function () {
    $service = newsroomPublishingService();

    $article = ContentArticle::factory()->inReview()->create();
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->privateEvidence()
        ->create([
            'source_type' => ContentArticleSourceType::Interview->value,
            'title' => 'Rozmowa z ekspertem',
            'url' => null,
            'is_official' => false,
        ]);

    expect($service->markReviewed($article)->reviewed_at)->not->toBeNull();

    $unsafe = ContentArticle::factory()->inReview()->create();
    ContentArticleSource::factory()
        ->for($unsafe, 'article')
        ->create([
            'title' => 'Niebezpieczny link',
            'url' => 'javascript:alert(1)',
        ]);

    expect(fn () => $service->markReviewed($unsafe))
        ->toThrow(DomainException::class, 'source URL must use a valid http or https URL');
});

test('legal news primary official source must have a publicly cited http or https url', function () {
    $service = newsroomPublishingService();
    $category = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy',
    ]);
    $article = ContentArticle::factory()->inReview()->for($category, 'category')->create();
    $source = ContentArticleSource::factory()
        ->for($article, 'article')
        ->privateEvidence()
        ->create([
            'source_type' => ContentArticleSourceType::Legislation->value,
            'title' => 'Projekt ustawy',
            'url' => null,
            'is_primary' => true,
            'is_official' => true,
        ]);

    expect(fn () => $service->markReviewed($article))
        ->toThrow(DomainException::class, 'requires a publicly cited http or https URL');

    $source->update([
        'url' => 'https://legislacja.gov.pl/example',
        'is_publicly_cited' => true,
    ]);

    expect($service->markReviewed($article->fresh())->reviewed_at)->not->toBeNull();
});

test('featured state is service controlled audited and limited when enabling', function () {
    Carbon::setTestNow('2026-09-16 16:00:00');

    $actor = User::factory()->create();
    $article = ContentArticle::factory()->published()->create([
        'is_featured' => false,
        'editorial_priority' => 0,
    ]);

    $featured = newsroomPublishingService()->setFeatured($article, true, 25, $actor);
    $audit = AuditLog::query()
        ->where('action', 'content_article.featured_changed')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->firstOrFail();

    expect($featured->is_featured)->toBeTrue()
        ->and($featured->editorial_priority)->toBe(25)
        ->and($featured->public_state_changed_at?->toDateTimeString())->toBe('2026-09-16 16:00:00')
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['previous_is_featured'])->toBeFalse()
        ->and($audit->metadata['is_featured'])->toBeTrue()
        ->and($audit->metadata['editorial_priority'])->toBe(25)
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('editorial_note');

    Carbon::setTestNow('2026-09-16 16:05:00');

    $unfeatured = newsroomPublishingService()->setFeatured($featured, false, null, $actor);

    expect($unfeatured->is_featured)->toBeFalse()
        ->and($unfeatured->editorial_priority)->toBe(25)
        ->and($unfeatured->public_state_changed_at?->toDateTimeString())->toBe('2026-09-16 16:05:00');

    $draft = ContentArticle::factory()->draft()->create();

    expect(fn () => newsroomPublishingService()->setFeatured($draft, true, 1, $actor))
        ->toThrow(DomainException::class);
});

test('breaking state is service controlled audited and enforces published news with future expiry', function () {
    Carbon::setTestNow('2026-09-16 17:00:00');

    $actor = User::factory()->create();
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'is_breaking' => false,
        'breaking_expires_at' => null,
    ]);

    $breaking = newsroomPublishingService()->enableBreaking(
        $article,
        Carbon::parse('2026-09-16 19:00:00'),
        $actor,
    );
    $audit = AuditLog::query()
        ->where('action', 'content_article.breaking_changed')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->firstOrFail();

    expect($breaking->is_breaking)->toBeTrue()
        ->and($breaking->breaking_expires_at?->toDateTimeString())->toBe('2026-09-16 19:00:00')
        ->and($breaking->public_state_changed_at?->toDateTimeString())->toBe('2026-09-16 17:00:00')
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['is_breaking'])->toBeTrue()
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead');

    Carbon::setTestNow('2026-09-16 17:05:00');

    $cleared = newsroomPublishingService()->clearBreaking($breaking, $actor);

    expect($cleared->is_breaking)->toBeFalse()
        ->and($cleared->breaking_expires_at)->toBeNull()
        ->and($cleared->public_state_changed_at?->toDateTimeString())->toBe('2026-09-16 17:05:00');

    $guide = ContentArticle::factory()->published()->guide()->create();

    expect(fn () => newsroomPublishingService()->enableBreaking(
        $guide,
        Carbon::parse('2026-09-16 20:00:00'),
        $actor,
    ))->toThrow(DomainException::class, 'must be a news');

    expect(fn () => newsroomPublishingService()->enableBreaking(
        $article->fresh(),
        Carbon::parse('2026-09-16 16:59:00'),
        $actor,
    ))->toThrow(DomainException::class, 'future expiration');
});
