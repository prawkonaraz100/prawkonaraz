<?php

namespace App\Observers;

use App\Models\IndexNowUrlSubmission;
use App\Models\QuestionPublicExplanation;
use App\Support\IndexNowQueueService;
use Throwable;

class QuestionPublicExplanationObserver
{
    /**
     * @var list<string>
     */
    private const PUBLIC_FIELDS = [
        'question_id',
        'external_id',
        'title',
        'body',
        'dont_confuse_with',
        'exam_trap',
        'common_mistakes',
        'related_questions',
        'status',
        'author_id',
        'reviewer_id',
        'published_at',
        'last_reviewed_at',
    ];

    public function created(QuestionPublicExplanation $explanation): void
    {
        if (! $this->automationEnabled() || ! $this->isPublic($explanation)) {
            return;
        }

        $this->queueExternalIds($this->affectedExternalIds($explanation), IndexNowUrlSubmission::EVENT_CREATED);
    }

    public function updated(QuestionPublicExplanation $explanation): void
    {
        if (! $this->shouldQueueUpdatedModel($explanation)) {
            return;
        }

        $this->queueExternalIds($this->affectedExternalIds($explanation), IndexNowUrlSubmission::EVENT_UPDATED);
    }

    public function deleted(QuestionPublicExplanation $explanation): void
    {
        if (! $this->automationEnabled() || ! $this->isPublic($explanation)) {
            return;
        }

        $this->queueExternalIds(
            [trim((string) $explanation->external_id)],
            IndexNowUrlSubmission::EVENT_DELETED,
        );
    }

    private function shouldQueueUpdatedModel(QuestionPublicExplanation $explanation): bool
    {
        return $this->automationEnabled()
            && $this->hasPublicFieldChange($explanation)
            && ($this->isPublic($explanation) || $this->wasPublic($explanation));
    }

    private function hasPublicFieldChange(QuestionPublicExplanation $explanation): bool
    {
        foreach (self::PUBLIC_FIELDS as $field) {
            if ($explanation->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function affectedExternalIds(QuestionPublicExplanation $explanation): array
    {
        $externalIds = [trim((string) $explanation->external_id)];
        $originalExternalId = trim((string) $explanation->getOriginal('external_id'));

        if ($originalExternalId !== '' && $originalExternalId !== $externalIds[0]) {
            $externalIds[] = $originalExternalId;
        }

        return array_values(array_unique(array_filter($externalIds)));
    }

    /**
     * @param  list<string>  $externalIds
     */
    private function queueExternalIds(array $externalIds, string $eventType): void
    {
        $queue = app(IndexNowQueueService::class);

        foreach ($externalIds as $externalId) {
            try {
                $queue->enqueueQuestionPublicExplanation(
                    $externalId,
                    'question_public_explanation',
                    $eventType,
                );
            } catch (Throwable $exception) {
                logger()->warning('indexnow_question_public_explanation_enqueue_failed', [
                    'external_id' => $externalId,
                    'event_type' => $eventType,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function isPublic(QuestionPublicExplanation $explanation): bool
    {
        return $explanation->status === QuestionPublicExplanation::STATUS_PUBLISHED
            && $explanation->published_at !== null;
    }

    private function wasPublic(QuestionPublicExplanation $explanation): bool
    {
        return $explanation->getOriginal('status') === QuestionPublicExplanation::STATUS_PUBLISHED
            && $explanation->getOriginal('published_at') !== null;
    }

    private function automationEnabled(): bool
    {
        return (bool) config('indexnow.automation_enabled', false);
    }
}
