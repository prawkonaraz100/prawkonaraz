<?php

namespace App\Support;

use App\Models\Question;

class QuestionDeliveryReadinessService
{
    public function sync(Question $question): Question
    {
        $question->loadMissing('media');

        $requiresPrimaryMedia = $this->requiresPrimaryMedia($question);
        $hasPrimaryMedia = $question->media
            ->contains(fn ($media): bool => (string) $media->variant === 'full');
        $deliveryIssue = $requiresPrimaryMedia && ! $hasPrimaryMedia
            ? Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA
            : null;

        if (
            (bool) $question->requires_primary_media === $requiresPrimaryMedia
            && $question->delivery_issue === $deliveryIssue
        ) {
            return $question;
        }

        $question->forceFill([
            'requires_primary_media' => $requiresPrimaryMedia,
            'delivery_issue' => $deliveryIssue,
        ])->save();

        return $question;
    }

    /**
     * @return array{processed:int,updated:int,issues:int}
     */
    public function syncMany(iterable $questions): array
    {
        $processed = 0;
        $updated = 0;
        $issues = 0;

        foreach ($questions as $question) {
            if (! $question instanceof Question) {
                continue;
            }

            $processed++;
            $beforeIssue = $question->delivery_issue;
            $beforeRequiresPrimaryMedia = (bool) $question->requires_primary_media;

            $question = $this->sync($question);

            if (
                $beforeIssue !== $question->delivery_issue
                || $beforeRequiresPrimaryMedia !== (bool) $question->requires_primary_media
            ) {
                $updated++;
            }

            if ($question->hasDeliveryIssue()) {
                $issues++;
            }
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'issues' => $issues,
        ];
    }

    protected function requiresPrimaryMedia(Question $question): bool
    {
        $mainMediaOriginal = trim((string) data_get($question->metadata, 'main_media_original', ''));

        return $mainMediaOriginal !== '';
    }
}
