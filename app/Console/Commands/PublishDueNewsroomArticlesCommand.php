<?php

namespace App\Console\Commands;

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Support\AuditLogService;
use App\Support\ContentArticlePublishingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishDueNewsroomArticlesCommand extends Command
{
    protected $signature = 'newsroom:publish-due {--limit=100 : Maximum number of due scheduled articles to inspect in one run}';

    protected $description = 'Publish due newsroom articles after revalidating the full publication contract.';

    public function handle(
        ContentArticlePublishingService $publishingService,
        AuditLogService $auditLogService,
    ): int {
        $limit = max(1, (int) $this->option('limit'));

        $articleIds = ContentArticle::query()
            ->scheduled()
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $published = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($articleIds as $articleId) {
            $article = ContentArticle::query()->find($articleId);

            if ($article === null || ! $this->isDueScheduled($article)) {
                $skipped++;

                continue;
            }

            try {
                $publishingService->publish($article, null, 'scheduler');
                $published++;
            } catch (Throwable $exception) {
                $current = ContentArticle::query()->find($articleId);

                if ($current === null || ! $this->isDueScheduled($current)) {
                    $skipped++;

                    continue;
                }

                $failed++;

                if ($this->recordFailureOnce($current, $exception, $auditLogService)) {
                    Log::warning('newsroom_scheduled_publish_failed', [
                        'article_id' => $current->getKey(),
                        'workflow_status' => $current->workflow_status->value,
                        'scheduled_for' => $current->scheduled_for?->toAtomString(),
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);
                }

                $this->error(sprintf(
                    'Article %s was not published: %s',
                    (string) $current->getKey(),
                    $exception->getMessage(),
                ));
            }
        }

        $this->info(sprintf(
            'Newsroom due publish complete: published=%d failed=%d skipped=%d.',
            $published,
            $failed,
            $skipped,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isDueScheduled(ContentArticle $article): bool
    {
        return $article->workflow_status === ContentArticleWorkflowStatus::Scheduled
            && $article->scheduled_for !== null
            && $article->scheduled_for->lte(now());
    }

    private function recordFailureOnce(
        ContentArticle $article,
        Throwable $exception,
        AuditLogService $auditLogService,
    ): bool {
        $metadata = [
            'workflow_status' => $article->workflow_status->value,
            'scheduled_for' => $article->scheduled_for,
            'first_published_at' => $article->first_published_at,
            'trigger' => 'scheduler',
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
        ];

        $latest = AuditLog::query()
            ->where('action', 'content_article.scheduled_publish_failed')
            ->where('entity_type', ContentArticle::class)
            ->where('entity_id', (string) $article->getKey())
            ->latest('id')
            ->first();

        if (
            $latest !== null
            && ($latest->metadata['scheduled_for'] ?? null) === $article->scheduled_for?->toAtomString()
            && ($latest->metadata['error_class'] ?? null) === $exception::class
            && ($latest->metadata['error_message'] ?? null) === $exception->getMessage()
        ) {
            return false;
        }

        $auditLogService->record(
            action: 'content_article.scheduled_publish_failed',
            entityType: ContentArticle::class,
            entityId: $article->getKey(),
            metadata: $metadata,
        );

        return true;
    }
}
