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

            if (
                $locked->workflow_status === ContentArticleWorkflowStatus::Scheduled
                && ($locked->scheduled_for === null || $locked->scheduled_for->isFuture())
            ) {
                throw new DomainException('Scheduled article is not due for publication.');
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
            $this->clearBreaking($locked);
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
            $this->clearBreaking($locked);
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
            $this->clearBreaking($locked);
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
            $this->clearBreaking($locked);
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

        if (
            $type === ContentArticleType::News
            && ! $article->sources()->exists()
        ) {
            throw new DomainException('News article requires at least one source.');
        }

        $this->assertKeyPoints($article);
    }

    private function assertPublicationReady(ContentArticle $article): void
    {
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

        $this->assertBreakingInvariant($article);
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

    private function assertBreakingInvariant(ContentArticle $article): void
    {
        if (! $article->is_breaking) {
            return;
        }

        if ($this->articleType($article) !== ContentArticleType::News) {
            throw new DomainException('Breaking article must be a news article.');
        }

        if ($article->breaking_expires_at === null || ! $article->breaking_expires_at->isFuture()) {
            throw new DomainException('Breaking article requires a future expiration timestamp.');
        }
    }

    private function clearBreaking(ContentArticle $article): void
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
