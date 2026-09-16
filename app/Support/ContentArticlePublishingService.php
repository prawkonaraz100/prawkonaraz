<?php

namespace App\Support;

use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Models\ContentArticle;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ContentArticlePublishingService
{
    /**
     * @var list<string>
     */
    private const PUBLISH_TRIGGERS = ['user', 'scheduler', 'system'];

    /**
     * @var list<string>
     */
    private const PUBLIC_UPDATE_FIELDS = [
        'category_id',
        'author_id',
        'reviewer_id',
        'title',
        'lead',
        'body_blocks',
        'body_schema_version',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function submitForReview(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Draft], 'submit for review');
            $this->assertReviewReady($locked);

            $from = $locked->workflow_status;
            $locked->workflow_status = ContentArticleWorkflowStatus::InReview;
            $locked->reviewed_at = null;
            $locked->scheduled_for = null;
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.review_submitted',
                $from,
                ContentArticleWorkflowStatus::InReview,
                $actor,
            );

            return $locked->refresh();
        });
    }

    public function returnToDraft(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::InReview], 'return to draft');

            $from = $locked->workflow_status;
            $locked->workflow_status = ContentArticleWorkflowStatus::Draft;
            $locked->reviewed_at = null;
            $locked->scheduled_for = null;
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.returned_to_draft',
                $from,
                ContentArticleWorkflowStatus::Draft,
                $actor,
            );

            return $locked->refresh();
        });
    }

    public function markReviewed(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [
                ContentArticleWorkflowStatus::InReview,
                ContentArticleWorkflowStatus::NeedsReview,
                ContentArticleWorkflowStatus::Archived,
            ], 'mark reviewed');
            $this->assertPublicationReady($locked);

            $locked->reviewed_at = now();
            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.reviewed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'workflow_status' => $this->statusValue($locked),
                    'reviewed_at' => $locked->reviewed_at,
                    'trigger' => $this->triggerForActor($actor),
                ],
            );

            return $locked->refresh();
        });
    }

    public function schedule(
        ContentArticle $article,
        DateTimeInterface $scheduledFor,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $scheduledFor, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::InReview], 'schedule');

            if ($locked->first_published_at !== null) {
                throw new DomainException('Newsroom v1 supports scheduling only for never-published articles.');
            }

            $scheduledAt = Carbon::parse($scheduledFor->format(DATE_ATOM));

            if (! $scheduledAt->isFuture()) {
                throw new DomainException('Scheduled publication time must be in the future.');
            }

            $this->assertPublicationReady($locked);
            $this->assertFreshReview($locked);

            $from = $locked->workflow_status;
            $locked->workflow_status = ContentArticleWorkflowStatus::Scheduled;
            $locked->scheduled_for = $scheduledAt;
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.scheduled',
                $from,
                ContentArticleWorkflowStatus::Scheduled,
                $actor,
                [
                    'scheduled_for' => $scheduledAt,
                ],
            );

            return $locked->refresh();
        });
    }

    public function assertScheduledPreviewReady(
        ContentArticle $article,
        DateTimeInterface $at,
    ): void {
        $previewAt = Carbon::parse($at->format(DATE_ATOM));

        $this->assertStatus(
            $article,
            [ContentArticleWorkflowStatus::Scheduled],
            'preview scheduled publication',
        );

        if ($article->first_published_at !== null) {
            throw new DomainException('Newsroom v1 does not support scheduled republish.');
        }

        if ($article->scheduled_for === null || $article->scheduled_for->gt($previewAt)) {
            throw new DomainException('Scheduled article is not due at the requested preview time.');
        }

        $this->assertPublicationReady($article, $previewAt);
        $this->assertFreshReview($article);
    }

    public function publish(
        ContentArticle $article,
        ?User $actor = null,
        ?string $trigger = null,
    ): ContentArticle {
        $trigger ??= $this->triggerForActor($actor);
        $this->assertTrigger($trigger);

        return DB::transaction(function () use ($article, $actor, $trigger): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [
                ContentArticleWorkflowStatus::InReview,
                ContentArticleWorkflowStatus::NeedsReview,
                ContentArticleWorkflowStatus::Scheduled,
            ], 'publish');

            if ($locked->workflow_status === ContentArticleWorkflowStatus::Scheduled) {
                if ($locked->first_published_at !== null) {
                    throw new DomainException('Newsroom v1 does not support scheduled republish.');
                }

                if ($locked->scheduled_for === null || $locked->scheduled_for->isFuture()) {
                    throw new DomainException('Scheduled article is not due for publication.');
                }
            }

            $this->assertPublicationReady($locked);
            $this->assertFreshReview($locked);

            return $this->publishLocked($locked, $actor, $trigger, 'content_article.published');
        });
    }

    public function markNeedsReview(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Published], 'mark needs review');
            $this->assertPreviouslyPublished($locked);

            $from = $locked->workflow_status;
            $at = now();

            $locked->workflow_status = ContentArticleWorkflowStatus::NeedsReview;
            $locked->needs_review_at = $at;
            $locked->public_state_changed_at = $at;
            $this->clearBreakingState($locked);
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.needs_review',
                $from,
                ContentArticleWorkflowStatus::NeedsReview,
                $actor,
                [
                    'needs_review_at' => $at,
                    'public_state_changed_at' => $at,
                ],
            );

            return $locked->refresh();
        });
    }

    public function archive(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Published], 'archive');
            $this->assertPreviouslyPublished($locked);

            $from = $locked->workflow_status;
            $at = now();

            $locked->workflow_status = ContentArticleWorkflowStatus::Archived;
            $locked->archived_at = $at;
            $locked->public_state_changed_at = $at;
            $this->clearBreakingState($locked);
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.archived',
                $from,
                ContentArticleWorkflowStatus::Archived,
                $actor,
                [
                    'archived_at' => $at,
                    'public_state_changed_at' => $at,
                ],
            );

            return $locked->refresh();
        });
    }

    public function republish(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Archived], 'republish');
            $this->assertPreviouslyPublished($locked);
            $this->assertPublicationReady($locked);
            $this->assertFreshReview($locked);

            return $this->publishLocked(
                $locked,
                $actor,
                $this->triggerForActor($actor),
                'content_article.republished',
            );
        });
    }

    public function withdraw(
        ContentArticle $article,
        string $reason,
        ?User $actor = null,
    ): ContentArticle {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Withdrawal reason is required.');
        }

        return DB::transaction(function () use ($article, $reason, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [
                ContentArticleWorkflowStatus::Published,
                ContentArticleWorkflowStatus::NeedsReview,
                ContentArticleWorkflowStatus::Archived,
            ], 'withdraw');
            $this->assertPreviouslyPublished($locked);

            $from = $locked->workflow_status;
            $at = now();

            $locked->workflow_status = ContentArticleWorkflowStatus::Withdrawn;
            $locked->withdrawn_at = $at;
            $locked->withdrawal_reason = $reason;
            $locked->public_state_changed_at = $at;
            $this->clearBreakingState($locked);
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.withdrawn',
                $from,
                ContentArticleWorkflowStatus::Withdrawn,
                $actor,
                [
                    'withdrawn_at' => $at,
                    'withdrawal_reason' => $reason,
                    'public_state_changed_at' => $at,
                ],
            );

            return $locked->refresh();
        });
    }

    public function restoreToReview(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Withdrawn], 'restore to review');
            $this->assertPreviouslyPublished($locked);

            $from = $locked->workflow_status;
            $locked->workflow_status = ContentArticleWorkflowStatus::InReview;
            $locked->reviewed_at = null;
            $locked->scheduled_for = null;
            $this->clearBreakingState($locked);
            $locked->save();

            $this->recordTransition(
                $locked,
                'content_article.restored_to_review',
                $from,
                ContentArticleWorkflowStatus::InReview,
                $actor,
                [
                    'withdrawn_at' => $locked->withdrawn_at,
                    'withdrawal_reason_retained' => $locked->withdrawal_reason !== null,
                ],
            );

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyPublicUpdate(
        ContentArticle $article,
        array $payload,
        int $expectedLockVersion,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $payload, $expectedLockVersion, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            NewsroomOptimisticLock::assertVersion($locked, $expectedLockVersion, 'Artykuł');

            if (! $locked->isPubliclyVisible()) {
                throw new DomainException('Apply public update wymaga publicznie widocznego artykułu.');
            }

            $bodyVersion = (int) ($payload['body_schema_version'] ?? $locked->body_schema_version ?? 1);
            $payload = NewsroomBodyEditorAdapter::normalizeArticleData($payload, $bodyVersion);

            $relationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData($payload);
            $attributes = $relationPayload['article_data'];
            $relations = $relationPayload['relations'];

            $sources = NewsroomArticleSourceEditorAdapter::normalize(
                $locked,
                $attributes['sources'] ?? [],
            );

            unset($attributes['sources'], $attributes['_lock_version'], $attributes['type']);

            $requestedSlug = trim((string) ($attributes['slug'] ?? $locked->slug));
            unset($attributes['slug']);

            $currentRelationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData(
                NewsroomArticleRelationsEditorAdapter::hydrateArticleData([], $locked),
            );
            $currentRelations = $currentRelationPayload['relations'];
            $currentSources = NewsroomArticleSourceEditorAdapter::normalize(
                $locked,
                NewsroomArticleSourceEditorAdapter::hydrate($locked),
            );

            $allowed = array_intersect_key(
                $attributes,
                array_flip(self::PUBLIC_UPDATE_FIELDS),
            );

            $locked->fill($allowed);

            $parentChanges = array_keys($locked->getDirty());
            $slugChanged = $requestedSlug !== (string) $locked->slug;
            $sourcesChanged = $this->sourceFingerprint($currentSources) !== $this->sourceFingerprint($sources);
            $relationsChanged = $currentRelations !== $relations;

            if ($parentChanges === [] && ! $slugChanged && ! $sourcesChanged && ! $relationsChanged) {
                return $locked->refresh();
            }

            if ($parentChanges !== []) {
                $locked->save();
            }

            if ($slugChanged) {
                $locked = app(ContentArticleSlugService::class)->changeSlug(
                    $locked,
                    $requestedSlug,
                    $actor,
                );
            }

            if ($sourcesChanged) {
                $locked = NewsroomArticleSourceEditorAdapter::sync($locked, $sources);
            }

            if ($relationsChanged) {
                $locked = NewsroomArticleRelationsEditorAdapter::sync($locked, $relations);
            }

            $locked = $locked->refresh();
            $this->assertPublicationReady($locked);

            $at = now();
            $locked->last_substantive_update_at = $at;
            $locked->save();
            $locked = $locked->refresh();

            $changeKinds = $this->publicUpdateChangeKinds(
                $parentChanges,
                $slugChanged,
                $sourcesChanged,
                $relationsChanged,
            );

            $this->auditLogService->record(
                action: 'content_article.public_updated',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'change_kinds' => $changeKinds,
                    'category_id' => $locked->category_id,
                    'author_id' => $locked->author_id,
                    'reviewer_id' => $locked->reviewer_id,
                    'source_count' => count($sources),
                    'question_count' => count($relations['questions']),
                    'legal_unit_count' => count($relations['legal_units']),
                    'traffic_sign_count' => count($relations['traffic_signs']),
                    'topic_count' => count($relations['topic_ids']),
                    'last_substantive_update_at' => $at,
                    'lock_version' => $locked->optimisticLockVersion(),
                    'trigger' => $this->triggerForActor($actor),
                ],
            );

            return $locked;
        });
    }

    public function setFeatured(
        ContentArticle $article,
        bool $featured,
        ?int $editorialPriority = null,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $featured, $editorialPriority, $actor): ContentArticle {
            $locked = $this->lockArticle($article);

            if ($featured) {
                $this->assertStatus($locked, [
                    ContentArticleWorkflowStatus::Scheduled,
                    ContentArticleWorkflowStatus::Published,
                ], 'mark as featured');
            }

            $priority = $editorialPriority ?? (int) $locked->editorial_priority;

            if ($priority < -32768 || $priority > 32767) {
                throw new InvalidArgumentException('Editorial priority must fit the signed smallint range.');
            }

            $previousFeatured = (bool) $locked->is_featured;
            $previousPriority = (int) $locked->editorial_priority;

            if ($previousFeatured === $featured && $previousPriority === $priority) {
                return $locked->refresh();
            }

            $locked->is_featured = $featured;
            $locked->editorial_priority = $priority;

            if ($locked->first_published_at !== null) {
                $locked->public_state_changed_at = now();
            }

            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.featured_changed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'previous_is_featured' => $previousFeatured,
                    'is_featured' => $featured,
                    'previous_editorial_priority' => $previousPriority,
                    'editorial_priority' => $priority,
                    'public_state_changed_at' => $locked->public_state_changed_at,
                    'trigger' => $this->triggerForActor($actor),
                ],
            );

            return $locked->refresh();
        });
    }

    public function enableBreaking(
        ContentArticle $article,
        DateTimeInterface $expiresAt,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $expiresAt, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Published], 'mark as breaking');
            $this->assertPreviouslyPublished($locked);

            if ($this->articleType($locked) !== ContentArticleType::News) {
                throw new DomainException('Breaking article must be a news article.');
            }

            $expiration = Carbon::parse($expiresAt->format(DATE_ATOM));

            if (! $expiration->isFuture()) {
                throw new DomainException('Breaking article requires a future expiration timestamp.');
            }

            $previousBreaking = (bool) $locked->is_breaking;
            $previousExpiration = $locked->breaking_expires_at;

            if ($previousBreaking && $previousExpiration?->equalTo($expiration)) {
                return $locked->refresh();
            }

            $locked->is_breaking = true;
            $locked->breaking_expires_at = $expiration;
            $locked->public_state_changed_at = now();
            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.breaking_changed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'previous_is_breaking' => $previousBreaking,
                    'is_breaking' => true,
                    'previous_breaking_expires_at' => $previousExpiration,
                    'breaking_expires_at' => $expiration,
                    'public_state_changed_at' => $locked->public_state_changed_at,
                    'trigger' => $this->triggerForActor($actor),
                ],
            );

            return $locked->refresh();
        });
    }

    public function clearBreaking(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $previousBreaking = (bool) $locked->is_breaking;
            $previousExpiration = $locked->breaking_expires_at;

            if (! $previousBreaking && $previousExpiration === null) {
                return $locked->refresh();
            }

            $this->clearBreakingState($locked);

            if ($locked->first_published_at !== null) {
                $locked->public_state_changed_at = now();
            }

            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.breaking_changed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'previous_is_breaking' => $previousBreaking,
                    'is_breaking' => false,
                    'previous_breaking_expires_at' => $previousExpiration,
                    'breaking_expires_at' => null,
                    'public_state_changed_at' => $locked->public_state_changed_at,
                    'trigger' => $this->triggerForActor($actor),
                ],
            );

            return $locked->refresh();
        });
    }

    private function publishLocked(
        ContentArticle $article,
        ?User $actor,
        string $trigger,
        string $action,
    ): ContentArticle {
        $from = $article->workflow_status;
        $at = now();

        $article->workflow_status = ContentArticleWorkflowStatus::Published;
        $article->first_published_at ??= $at;
        $article->published_at = $at;
        $article->scheduled_for = null;
        $article->needs_review_at = null;
        $article->archived_at = null;
        $article->withdrawn_at = null;
        $article->withdrawal_reason = null;
        $article->public_state_changed_at = $at;
        $article->save();

        $this->recordTransition(
            $article,
            $action,
            $from,
            ContentArticleWorkflowStatus::Published,
            $actor,
            [
                'first_published_at' => $article->first_published_at,
                'published_at' => $at,
                'public_state_changed_at' => $at,
            ],
            $trigger,
        );

        return $article->refresh();
    }

    private function assertReviewReady(ContentArticle $article): void
    {
        $this->assertRequiredText($article->title, 'title');
        $this->assertRequiredText($article->slug, 'slug');
        $this->assertRequiredText($article->lead, 'lead');

        $type = $this->articleType($article);
        NewsroomRouteContract::canonicalPath($type->value, (string) $article->slug);

        if ($article->category_id === null) {
            throw new DomainException('Content article category is required.');
        }

        if ($article->author_id === null) {
            throw new DomainException('Content article author is required.');
        }

        $body = is_array($article->body_blocks) ? $article->body_blocks : [];
        $version = (int) ($article->body_schema_version ?? 0);
        $normalized = NewsroomBodyContract::normalize($body, $version);

        if ($normalized === []) {
            throw new DomainException('Content article requires at least one renderable body block.');
        }

        $this->assertSourcePolicy($article, $type);
        $this->assertKeyPoints($article);
    }

    private function assertPublicationReady(
        ContentArticle $article,
        ?DateTimeInterface $at = null,
    ): void {
        $this->assertReviewReady($article);

        $category = $article->category()->first();

        if ($category === null || ! $category->isPublicationEligible()) {
            throw new DomainException('Content article requires an active category.');
        }

        $author = $article->author()->first();

        if ($author === null || ! $author->isPubliclyVisible()) {
            throw new DomainException('Content article requires a published author.');
        }

        if (filled($article->hero_image_path)) {
            $this->assertRequiredText($article->hero_image_alt, 'hero_image_alt');

            if ((int) $article->hero_image_width < 1 || (int) $article->hero_image_height < 1) {
                throw new DomainException('Hero image requires positive width and height.');
            }
        }

        if (filled($article->og_image_path)) {
            $canInheritHeroAlt = filled($article->hero_image_alt)
                && $article->og_image_path === $article->hero_image_path;

            if (! filled($article->og_image_alt) && ! $canInheritHeroAlt) {
                throw new DomainException('OG image requires its own alt unless it reuses the hero asset.');
            }

            if ((int) $article->og_image_width < 1 || (int) $article->og_image_height < 1) {
                throw new DomainException('OG image requires positive width and height.');
            }
        }

        $this->assertBreakingInvariant($article, $at);
    }

    private function assertPreviouslyPublished(ContentArticle $article): void
    {
        if ($article->first_published_at === null) {
            throw new DomainException('Content article transition requires a previously published article.');
        }
    }

    private function assertFreshReview(ContentArticle $article): void
    {
        if ($article->reviewed_at === null) {
            throw new DomainException('Content article requires a completed review.');
        }

        $reference = collect([
            $article->needs_review_at,
            $article->archived_at,
            $article->withdrawn_at,
        ])
            ->filter()
            ->sortByDesc(fn ($date) => $date->getTimestamp())
            ->first();

        if ($reference !== null && ! $article->reviewed_at->gt($reference)) {
            throw new DomainException('Content article requires a fresh review after its latest public-state change.');
        }
    }

    private function assertSourcePolicy(ContentArticle $article, ContentArticleType $articleType): void
    {
        $sources = $article->sources()->get();

        if ($articleType === ContentArticleType::News && $sources->isEmpty()) {
            throw new DomainException('News article requires at least one source.');
        }

        foreach ($sources as $source) {
            $this->assertRequiredText($source->title, 'source title');

            $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

            if ($sourceType === null) {
                throw new DomainException('Content article source has an unsupported source_type.');
            }

            if (filled($source->url) && ! $this->isSafeHttpUrl($source->url)) {
                throw new DomainException('Content article source URL must use a valid http or https URL.');
            }
        }

        if ($articleType !== ContentArticleType::News) {
            return;
        }

        $category = $article->category()->first();

        if ($category?->slug !== 'przepisy') {
            return;
        }

        $legalPrimarySources = $sources->filter(function ($source): bool {
            if (! $source->is_primary) {
                return false;
            }

            $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

            return in_array($sourceType, [
                ContentArticleSourceType::Official,
                ContentArticleSourceType::Legislation,
            ], true);
        });

        if ($legalPrimarySources->isEmpty()) {
            return;
        }

        $hasPublicPrimaryUrl = $legalPrimarySources->contains(
            fn ($source): bool => $source->is_publicly_cited && $this->isSafeHttpUrl($source->url),
        );

        if (! $hasPublicPrimaryUrl) {
            throw new DomainException(
                'Legal news with a primary official or legislation source requires a publicly cited http or https URL.',
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sourceFingerprint(array $rows): array
    {
        return array_map(function (array $row): array {
            foreach (['published_at', 'accessed_at'] as $field) {
                $value = $row[$field] ?? null;
                $row[$field] = $value instanceof DateTimeInterface
                    ? $value->format(DATE_ATOM)
                    : $value;
            }

            return $row;
        }, $rows);
    }

    /**
     * @param  list<string>  $parentChanges
     * @return list<string>
     */
    private function publicUpdateChangeKinds(
        array $parentChanges,
        bool $slugChanged,
        bool $sourcesChanged,
        bool $relationsChanged,
    ): array {
        $kinds = [];

        if (array_intersect($parentChanges, ['category_id', 'author_id', 'reviewer_id']) !== []) {
            $kinds[] = 'identity';
        }

        if (in_array('title', $parentChanges, true)) {
            $kinds[] = 'headline';
        }

        if (array_intersect($parentChanges, ['lead', 'body_blocks', 'body_schema_version']) !== []) {
            $kinds[] = 'content';
        }

        if ($slugChanged) {
            $kinds[] = 'slug';
        }

        if ($sourcesChanged) {
            $kinds[] = 'sources';
        }

        if ($relationsChanged) {
            $kinds[] = 'relations';
        }

        return $kinds;
    }

    private function isSafeHttpUrl(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $url = trim($value);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ['http', 'https'],
            true,
        );
    }

    private function assertKeyPoints(ContentArticle $article): void
    {
        if ($article->key_points === null) {
            return;
        }

        if (! is_array($article->key_points) || count($article->key_points) < 2 || count($article->key_points) > 5) {
            throw new DomainException('Content article key points must contain between 2 and 5 items.');
        }

        foreach ($article->key_points as $point) {
            if (
                ! is_string($point)
                || trim($point) === ''
                || strip_tags($point) !== $point
            ) {
                throw new DomainException('Content article key points must be non-empty plain text.');
            }
        }
    }

    private function assertBreakingInvariant(
        ContentArticle $article,
        ?DateTimeInterface $at = null,
    ): void {
        if (! $article->is_breaking) {
            return;
        }

        if ($this->articleType($article) !== ContentArticleType::News) {
            throw new DomainException('Breaking article must be a news article.');
        }

        $referenceAt = $at === null
            ? now()
            : Carbon::parse($at->format(DATE_ATOM));

        if (
            $article->breaking_expires_at === null
            || $article->breaking_expires_at->lte($referenceAt)
        ) {
            throw new DomainException('Breaking article requires a future expiration timestamp after the evaluated publication time.');
        }
    }

    private function clearBreakingState(ContentArticle $article): void
    {
        $article->is_breaking = false;
        $article->breaking_expires_at = null;
    }

    /**
     * @param  list<ContentArticleWorkflowStatus>  $allowed
     */
    private function assertStatus(ContentArticle $article, array $allowed, string $action): void
    {
        if (! in_array($article->workflow_status, $allowed, true)) {
            throw new DomainException(
                "Cannot {$action} content article from status [{$this->statusValue($article)}].",
            );
        }
    }

    private function lockArticle(ContentArticle $article): ContentArticle
    {
        return ContentArticle::query()
            ->whereKey($article->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function articleType(ContentArticle $article): ContentArticleType
    {
        if ($article->type instanceof ContentArticleType) {
            return $article->type;
        }

        $type = ContentArticleType::tryFrom((string) $article->type);

        if ($type === null) {
            throw new DomainException('Content article has an unsupported type.');
        }

        return $type;
    }

    private function statusValue(ContentArticle $article): string
    {
        return $article->workflow_status instanceof ContentArticleWorkflowStatus
            ? $article->workflow_status->value
            : (string) $article->workflow_status;
    }

    private function assertRequiredText(mixed $value, string $field): void
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException("Content article {$field} is required.");
        }
    }

    private function assertTrigger(string $trigger): void
    {
        if (! in_array($trigger, self::PUBLISH_TRIGGERS, true)) {
            throw new InvalidArgumentException("Unsupported newsroom publish trigger [{$trigger}].");
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordTransition(
        ContentArticle $article,
        string $action,
        ContentArticleWorkflowStatus $from,
        ContentArticleWorkflowStatus $to,
        ?User $actor,
        array $metadata = [],
        ?string $trigger = null,
    ): void {
        $trigger ??= $this->triggerForActor($actor);

        $this->auditLogService->record(
            action: $action,
            entityType: ContentArticle::class,
            entityId: $article->getKey(),
            actor: $actor,
            metadata: [
                'from_status' => $from->value,
                'to_status' => $to->value,
                'trigger' => $trigger,
                ...$metadata,
            ],
        );

        ContentArticleWorkflowTransitioned::dispatch(
            (int) $article->getKey(),
            $action,
            $from->value,
            $to->value,
            $trigger,
        );
    }

    private function triggerForActor(?User $actor): string
    {
        return $actor === null ? 'system' : 'user';
    }
}
