<?php

namespace App\Listeners;

use App\Enums\ContentArticleWorkflowStatus;
use App\Events\ContentArticleIndexNowRequested;
use App\Models\ContentArticle;
use App\Models\IndexNowUrlSubmission;
use App\Support\IndexNowQueueService;
use App\Support\NewsroomPublicGate;
use App\Support\PublicUrlResolver;
use Throwable;

final class QueueNewsroomArticleIndexNow
{
    public function __construct(
        private readonly IndexNowQueueService $queue,
        private readonly NewsroomPublicGate $publicGate,
        private readonly PublicUrlResolver $publicUrlResolver,
    ) {}

    public function handle(ContentArticleIndexNowRequested $event): void
    {
        if ($this->publicGate->disabled()) {
            return;
        }

        try {
            $article = ContentArticle::query()->find($event->articleId);

            if (! $article instanceof ContentArticle || ! $this->eligible($article, $event->eventType)) {
                return;
            }

            $url = $this->publicUrlResolver->normalize($event->path);

            if (! is_string($url) || trim($url) === '') {
                return;
            }

            $this->queue->enqueueUrl(
                $url,
                $event->source,
                $event->eventType,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function eligible(ContentArticle $article, string $eventType): bool
    {
        if ($this->isNoindex($article)) {
            return false;
        }

        if ($eventType === IndexNowUrlSubmission::EVENT_DELETED) {
            return $article->first_published_at !== null
                && $article->workflow_status === ContentArticleWorkflowStatus::Withdrawn;
        }

        if (! in_array($eventType, [
            IndexNowUrlSubmission::EVENT_CREATED,
            IndexNowUrlSubmission::EVENT_UPDATED,
        ], true)) {
            return false;
        }

        return $article->isIndexable();
    }

    private function isNoindex(ContentArticle $article): bool
    {
        return str_contains(strtolower((string) $article->robots), 'noindex');
    }
}
