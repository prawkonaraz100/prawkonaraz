<?php

namespace App\Console\Commands;

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Support\AuditLogService;
use App\Support\ContentArticlePublishingService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishDueNewsroomArticlesCommand extends Command
{
    protected $signature = 'newsroom:publish-due
        {--limit=100 : Maximum number of due scheduled articles to process}';

    protected $description = 'Publish due newsroom articles that are still eligible for initial publication.';

    public function handle(
        ContentArticlePublishingService $publishingService,
        AuditLogService $auditLogService,
    ): int {
        $limit = $this->parseLimit();

        if ($limit === null) {
            return self::FAILURE;
        }

        $ids = ContentArticle::query()
            ->where('workflow_status', ContentArticleWorkflowStatus::Scheduled->value)
            ->whereNull('first_published_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $published = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $article = ContentArticle::query()->find($id);

            if ($article === null) {
                $skipped++;

                continue;
            }

            try {
                $publishingService->publish($article, null, 'scheduler');
                $published++;
            } catch (Throwable $exception) {
                $fresh = ContentArticle::query()->find($id);

                if (
                    $fresh === null
                    || $fresh->workflow_status !== ContentArticleWorkflowStatus::Scheduled
                ) {
                    $skipped++;

                    continue;
                }

                $failed++;

                $auditLogService->record(
                    action: 'content_article.scheduled_publish_failed',
                    entityType: ContentArticle::class,
                    entityId: $fresh->getKey(),
                    actor: null,
                    metadata: [
                        'trigger' => 'scheduler',
                        'workflow_status' => $fresh->workflow_status->value,
                        'scheduled_for' => $fresh->scheduled_for,
                        'exception' => class_basename($exception),
                        'reason' => $exception instanceof DomainException
                            ? $exception->getMessage()
                            : 'Unexpected scheduled publish failure.',
                    ],
                );

                Log::warning('newsroom_scheduled_publish_failed', [
                    'article_id' => $fresh->getKey(),
                    'workflow_status' => $fresh->workflow_status->value,
                    'scheduled_for' => $fresh->scheduled_for?->toIso8601String(),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                $this->warn(sprintf(
                    'Article #%d remained scheduled: %s',
                    $fresh->getKey(),
                    $exception->getMessage(),
                ));
            }
        }

        $this->line(sprintf(
            'Newsroom publish due: selected=%d published=%d failed=%d skipped=%d',
            $ids->count(),
            $published,
            $failed,
            $skipped,
        ));

        if ($failed > 0) {
            $this->error('One or more due articles could not be published and remain scheduled.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function parseLimit(): ?int
    {
        $value = $this->option('limit');

        $validated = filter_var($value, FILTER_VALIDATE_INT);

        if ($validated === false || $validated < 1 || $validated > 1000) {
            $this->error('--limit must be an integer between 1 and 1000.');

            return null;
        }

        return $validated;
    }
}
