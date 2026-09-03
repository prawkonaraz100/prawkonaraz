<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionAudioAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QuestionAudioPayloadBuilder
{
    public function __construct(
        private readonly QuestionAudioTextBuilder $textBuilder,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forPublicQuestion(Question $question): array
    {
        return $this->forQuestion($question);
    }

    /**
     * @return array<string, mixed>
     */
    public function forQuestion(Question $question): array
    {
        $payload = [];
        $questionAudio = $this->assetPayload($this->textBuilder->buildQuestionPrompt($question, [
            'audio_external_id' => $this->audioExternalIdForQuestion($question, QuestionAudioAsset::TYPE_QUESTION),
        ]));

        if ($questionAudio !== null) {
            $payload[QuestionAudioAsset::TYPE_QUESTION] = $questionAudio;
        }

        $correctAnswerExpected = $this->textBuilder->buildCorrectAnswer($question, [
            'audio_external_id' => $this->audioExternalIdForQuestion($question, QuestionAudioAsset::TYPE_CORRECT_ANSWER),
        ]);
        $correctAnswerAudio = $correctAnswerExpected !== null
            ? $this->assetPayload($correctAnswerExpected)
            : null;

        if ($correctAnswerAudio !== null) {
            $payload[QuestionAudioAsset::TYPE_CORRECT_ANSWER] = $correctAnswerAudio;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @return array<string, mixed>|null
     */
    private function assetPayload(array $expected): ?array
    {
        $asset = QuestionAudioAsset::query()
            ->where('asset_key', $expected['asset_key'])
            ->where('status', QuestionAudioAsset::STATUS_GENERATED)
            ->where('source_text_hash', $expected['source_text_hash'])
            ->first();

        if (! $asset instanceof QuestionAudioAsset || ! $this->fileExists($asset)) {
            return null;
        }

        $url = $this->url($asset);

        if ($url === null) {
            return null;
        }

        return [
            'type' => (string) $expected['audio_type'],
            'url' => $url,
            'duration_seconds' => $asset->duration_seconds,
            'encoding_format' => $asset->encoding_format ?: 'audio/mpeg',
            'transcript' => $expected['source_text'],
            'asset_key' => $asset->asset_key,
        ];
    }

    private function audioExternalIdForQuestion(Question $question, string $audioType): string
    {
        $rawExternalId = trim((string) $question->external_id);
        $canonicalExternalId = $this->textBuilder->canonicalExternalId($rawExternalId);

        if ($rawExternalId === '' || $rawExternalId === $canonicalExternalId || ! str_contains($rawExternalId, ':')) {
            return $canonicalExternalId;
        }

        return $this->hasConflictingSourceText($canonicalExternalId, $audioType)
            ? $rawExternalId
            : $canonicalExternalId;
    }

    private function hasConflictingSourceText(string $canonicalExternalId, string $audioType): bool
    {
        $questions = Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->where(function (Builder $query) use ($canonicalExternalId): void {
                $query
                    ->where('external_id', $canonicalExternalId)
                    ->orWhere('external_id', 'like', '%:'.$canonicalExternalId);
            })
            ->get(['id', 'external_id', 'prompt', 'option_a', 'option_b', 'option_c', 'correct_answer']);

        if ($questions->count() <= 1) {
            return false;
        }

        return $questions
            ->map(fn (Question $question): string => $this->sourceTextForType($question, $audioType))
            ->filter()
            ->unique()
            ->count() > 1;
    }

    private function sourceTextForType(Question $question, string $audioType): string
    {
        return match ($audioType) {
            QuestionAudioAsset::TYPE_QUESTION => (string) ($this->textBuilder->buildQuestionPrompt($question)['source_text'] ?? ''),
            QuestionAudioAsset::TYPE_CORRECT_ANSWER => $this->textBuilder->correctAnswerSourceText($question) ?? '',
            default => '',
        };
    }

    private function fileExists(QuestionAudioAsset $asset): bool
    {
        if (! filled($asset->storage_disk) || ! filled($asset->storage_path)) {
            return false;
        }

        try {
            return Storage::disk((string) $asset->storage_disk)->exists((string) $asset->storage_path);
        } catch (Throwable) {
            return false;
        }
    }

    private function url(QuestionAudioAsset $asset): ?string
    {
        $url = $this->mediaUrlResolver->resolve(
            (string) $asset->storage_path,
            (string) $asset->storage_disk,
        );

        return filled($url) ? $url : null;
    }
}
