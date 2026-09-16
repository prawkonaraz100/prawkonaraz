<?php

namespace App\Support;

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

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ContentArticleEditToken $editToken,
        private readonly ContentArticlePublicationChecklist $publicationChecklist,
        private readonly ContentArticleSlugService $slugService,
    ) {}

    public function submitForReview(ContentArticle $article, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($article, $actor): ContentArticle {
            $locked = $this->lockArticle($article);
            $this->assertStatus($locked, [ContentArticleWorkflowStatus::Draft], 'submit for review');
            $this->publicationChecklist->assertReviewReady($locked);

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
            $this->publicationChecklist->assertPublicationReady($locked);

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

            $this->publicationChecklist->assertPublicationReady($locked);
            $this->publicationChecklist->assertFreshReview($locked);

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

        $this->publicationChecklist->assertPublicationReady($article, $previewAt);
        $this->publicationChecklist->assertFreshReview($article);
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

            $this->publicationChecklist->assertPublicationReady($locked);
            $this->publicationChecklist->assertFreshReview($locked);

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
            $this->publicationChecklist->assertPublicationReady($locked);
            $this->publicationChecklist->assertFreshReview($locked);

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

    /**
     * Reject a form that was loaded from an older article/source/relation state.
     */
    public function assertFreshEditToken(ContentArticle $article, string $loadedToken): void
    {
        $loadedToken = trim($loadedToken);

        if (
            $loadedToken === ''
            || ! hash_equals($this->editToken->make($article), $loadedToken)
        ) {
            throw new DomainException(
                'Content article changed after this form was loaded; reload before saving.',
            );
        }
    }

    /**
     * Apply the currently supported public editor payload to an already public article.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyPublicUpdate(
        ContentArticle $article,
        array $payload,
        string $loadedToken,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $payload, $loadedToken, $actor): ContentArticle {
            $locked = $this->lockArticle($article);

            if (! $locked->isPubliclyVisible()) {
                throw new DomainException('Apply public update requires a publicly visible content article.');
            }

            $this->assertFreshEditToken($locked, $loadedToken);
            $beforePublicFingerprint = $this->editToken->publicFingerprint($locked);

            unset($payload['_edit_token'], $payload['editorial_note']);

            $payload = NewsroomArticleProvenanceMediaAdapter::normalizeArticleData($payload);

            $sourcePayload = NewsroomArticleSourcesEditorAdapter::extractArticleData($payload);
            $payload = $sourcePayload['article_data'];
            $sources = $sourcePayload['sources'];

            $payload = NewsroomBodyEditorAdapter::normalizeArticleData(
                $payload,
                (int) ($locked->body_schema_version ?? 1),
            );

            $relationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData($payload);
            $articleData = array_intersect_key(
                $relationPayload['article_data'],
                array_flip([
                    'type',
                    'category_id',
                    'author_id',
                    'reviewer_id',
                    'origin_type',
                    'title',
                    'slug',
                    'lead',
                    'body_blocks',
                    'body_schema_version',
                    'regulatory_status',
                    'effective_from',
                    'change_summary',
                    'applies_to',
                    'exam_impact',
                    'hero_image_path',
                    'hero_image_alt',
                    'hero_image_width',
                    'hero_image_height',
                    'hero_image_caption',
                    'hero_focal_x',
                    'hero_focal_y',
                    'og_image_path',
                    'og_image_alt',
                    'og_image_width',
                    'og_image_height',
                    'image_credit',
                    'image_license_note',
                ]),
            );
            $relations = $relationPayload['relations'];

            $requestedType = $articleData['type'] ?? $locked->type;
            $requestedType = $requestedType instanceof ContentArticleType
                ? $requestedType->value
                : (string) $requestedType;

            $requestedSlug = trim((string) ($articleData['slug'] ?? $locked->slug));

            unset($articleData['type'], $articleData['slug']);

            $locked->fill($articleData);
            $locked->save();

            $currentType = $locked->type instanceof ContentArticleType
                ? $locked->type->value
                : (string) $locked->type;

            if ($requestedType !== $currentType) {
                $locked = $this->slugService->changeType($locked, $requestedType, $actor);
            }

            if ($requestedSlug !== '' && $requestedSlug !== (string) $locked->slug) {
                $locked = $this->slugService->changeSlug($locked, $requestedSlug, $actor);
            }

            $locked = NewsroomArticleSourcesEditorAdapter::sync($locked, $sources);
            $locked = NewsroomArticleRelationsEditorAdapter::sync($locked, $relations)->refresh();

            $this->publicationChecklist->assertPublicationReady($locked);

            $substantiveChange = ! hash_equals(
                $beforePublicFingerprint,
                $this->editToken->publicFingerprint($locked),
            );

            if ($substantiveChange) {
                $locked->last_substantive_update_at = now();
                $locked->save();
            }

            $this->auditLogService->record(
                action: 'content_article.public_updated',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'workflow_status' => $this->statusValue($locked),
                    'substantive_change' => $substantiveChange,
                    'last_substantive_update_at' => $locked->last_substantive_update_at,
                    'source_count' => count($sources),
                    'question_relation_count' => count($relations['questions']),
                    'legal_unit_relation_count' => count($relations['legal_units']),
                    'traffic_sign_relation_count' => count($relations['traffic_signs']),
                    'topic_count' => count($relations['topic_ids']),
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

    private function assertPreviouslyPublished(ContentArticle $article): void
    {
        if ($article->first_published_at === null) {
            throw new DomainException('Content article transition requires a previously published article.');
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
