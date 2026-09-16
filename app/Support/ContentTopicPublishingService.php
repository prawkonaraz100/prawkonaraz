<?php

namespace App\Support;

use App\Models\ContentTopic;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ContentTopicPublishingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function publish(ContentTopic $topic, ?User $actor = null): ContentTopic
    {
        return DB::transaction(function () use ($topic, $actor): ContentTopic {
            $locked = $this->lockTopic($topic);
            $this->assertStatus($locked, ContentTopic::STATUS_DRAFT, 'publish');
            $this->assertPublicationRequirements($locked);

            $locked->status = ContentTopic::STATUS_PUBLISHED;
            $locked->published_at ??= now();
            $locked->save();

            $this->record(
                $locked,
                'content_topic.published',
                ContentTopic::STATUS_DRAFT,
                ContentTopic::STATUS_PUBLISHED,
                $actor,
            );

            return $locked->refresh();
        });
    }

    public function archive(ContentTopic $topic, ?User $actor = null): ContentTopic
    {
        return DB::transaction(function () use ($topic, $actor): ContentTopic {
            $locked = $this->lockTopic($topic);
            $this->assertStatus($locked, ContentTopic::STATUS_PUBLISHED, 'archive');

            $locked->status = ContentTopic::STATUS_ARCHIVED;
            $locked->save();

            $this->record(
                $locked,
                'content_topic.archived',
                ContentTopic::STATUS_PUBLISHED,
                ContentTopic::STATUS_ARCHIVED,
                $actor,
            );

            return $locked->refresh();
        });
    }

    public function republish(ContentTopic $topic, ?User $actor = null): ContentTopic
    {
        return DB::transaction(function () use ($topic, $actor): ContentTopic {
            $locked = $this->lockTopic($topic);
            $this->assertStatus($locked, ContentTopic::STATUS_ARCHIVED, 'republish');
            $this->assertPublicationRequirements($locked);

            $locked->status = ContentTopic::STATUS_PUBLISHED;
            $locked->published_at ??= now();
            $locked->save();

            $this->record(
                $locked,
                'content_topic.republished',
                ContentTopic::STATUS_ARCHIVED,
                ContentTopic::STATUS_PUBLISHED,
                $actor,
            );

            return $locked->refresh();
        });
    }

    public function assertPublicationRequirements(ContentTopic $topic): void
    {
        if (trim((string) $topic->description) === '') {
            throw new DomainException('Topic requires its own editorial description before publication.');
        }

        $eligibleCount = $topic->eligibleCorpusCount();

        if ($eligibleCount < ContentTopic::PUBLICATION_CORPUS_MINIMUM) {
            throw new DomainException(
                'Topic requires at least '.ContentTopic::PUBLICATION_CORPUS_MINIMUM
                ." actively distributed and indexable linked articles; current eligible corpus: {$eligibleCount}.",
            );
        }

        if (! $topic->hasEligibleFeaturedArticle()) {
            throw new DomainException(
                'Featured article must belong to the topic and be actively distributed and indexable.',
            );
        }
    }

    private function lockTopic(ContentTopic $topic): ContentTopic
    {
        return ContentTopic::query()
            ->whereKey($topic->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertStatus(ContentTopic $topic, string $expected, string $action): void
    {
        if ($topic->status !== $expected) {
            throw new DomainException(
                "Cannot {$action} content topic from status [{$topic->status}].",
            );
        }
    }

    private function record(
        ContentTopic $topic,
        string $action,
        string $from,
        string $to,
        ?User $actor,
    ): void {
        $this->auditLogService->record(
            action: $action,
            entityType: ContentTopic::class,
            entityId: $topic->getKey(),
            actor: $actor,
            metadata: [
                'from_status' => $from,
                'to_status' => $to,
                'published_at' => $topic->published_at,
                'eligible_corpus_count' => $topic->eligibleCorpusCount(),
                'featured_article_id' => $topic->featured_article_id,
                'trigger' => $actor === null ? 'system' : 'user',
            ],
        );
    }
}
