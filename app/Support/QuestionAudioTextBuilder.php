<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionAudioAsset;
use Illuminate\Support\Str;

class QuestionAudioTextBuilder
{
    public function __construct(
        private readonly QuestionTextFormatter $questionTextFormatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildQuestionPrompt(Question $question, array $options = []): array
    {
        $locale = (string) ($options['locale'] ?? config('media.question_audio_locale', 'pl-PL'));
        $voiceProvider = (string) ($options['voice_provider'] ?? config('media.question_audio_voice_provider', 'elevenlabs'));
        $voiceId = (string) ($options['voice_id'] ?? config('media.question_audio_voice_id', ''));
        $modelId = (string) ($options['model_id'] ?? config('media.question_audio_model_id', ''));
        $generationVersion = (string) ($options['generation_version'] ?? config('media.question_audio_generation_version', 'question-v1'));
        $sourceText = $this->normalizeSourceText($this->questionTextFormatter->plainText($question->prompt));
        $contentScope = QuestionAudioAsset::CONTENT_SCOPE_QUESTION;
        $audioType = QuestionAudioAsset::TYPE_QUESTION;
        $externalId = $this->audioExternalId($question, $options);
        $sourceTextHash = $this->sourceTextHash($contentScope, $audioType, $locale, $sourceText);
        $assetKey = $this->assetKey(
            $externalId,
            $contentScope,
            $audioType,
            $locale,
            $sourceTextHash,
            $voiceProvider,
            $voiceId,
            $modelId,
            $generationVersion,
        );

        return [
            'asset_key' => $assetKey,
            'external_id' => $externalId,
            'content_scope' => $contentScope,
            'audio_type' => $audioType,
            'locale' => $locale,
            'source_text' => $sourceText,
            'source_text_hash' => $sourceTextHash,
            'voice_provider' => $voiceProvider,
            'voice_id' => $voiceId,
            'model_id' => $modelId,
            'generation_version' => $generationVersion,
            'target_file_name' => $this->targetFileName($externalId, $audioType, $sourceTextHash),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildCorrectAnswer(Question $question, array $options = []): ?array
    {
        $sourceText = $this->correctAnswerSourceText($question);

        if ($sourceText === null) {
            return null;
        }

        $locale = (string) ($options['locale'] ?? config('media.question_audio_locale', 'pl-PL'));
        $voiceProvider = (string) ($options['voice_provider'] ?? config('media.question_audio_voice_provider', 'elevenlabs'));
        $voiceId = (string) ($options['voice_id'] ?? config('media.question_audio_voice_id', ''));
        $modelId = (string) ($options['model_id'] ?? config('media.question_audio_model_id', ''));
        $generationVersion = (string) ($options['generation_version'] ?? config('media.question_audio_generation_version', 'question-v1'));
        $contentScope = QuestionAudioAsset::CONTENT_SCOPE_QUESTION;
        $audioType = QuestionAudioAsset::TYPE_CORRECT_ANSWER;
        $externalId = $this->audioExternalId($question, $options);
        $sourceTextHash = $this->sourceTextHash($contentScope, $audioType, $locale, $sourceText);
        $assetKey = $this->assetKey(
            $externalId,
            $contentScope,
            $audioType,
            $locale,
            $sourceTextHash,
            $voiceProvider,
            $voiceId,
            $modelId,
            $generationVersion,
        );

        return [
            'asset_key' => $assetKey,
            'external_id' => $externalId,
            'content_scope' => $contentScope,
            'audio_type' => $audioType,
            'locale' => $locale,
            'source_text' => $sourceText,
            'source_text_hash' => $sourceTextHash,
            'voice_provider' => $voiceProvider,
            'voice_id' => $voiceId,
            'model_id' => $modelId,
            'generation_version' => $generationVersion,
            'target_file_name' => $this->targetFileName($externalId, $audioType, $sourceTextHash),
        ];
    }

    public function correctAnswerSourceText(Question $question): ?string
    {
        $answer = Str::lower(trim((string) $question->correct_answer));
        $sourceText = match ($answer) {
            'a' => $question->option_a,
            'b' => $question->option_b,
            'c' => $question->option_c,
            default => null,
        };

        if (! is_string($sourceText) || trim($sourceText) === '') {
            return null;
        }

        return $this->normalizeSourceText($this->questionTextFormatter->plainText($sourceText));
    }

    public function sourceTextHash(string $contentScope, string $audioType, string $locale, string $sourceText): string
    {
        return hash('sha256', implode('|', [
            $contentScope,
            $audioType,
            $locale,
            $this->normalizeSourceText($sourceText),
        ]));
    }

    public function assetKey(
        string $externalId,
        string $contentScope,
        string $audioType,
        string $locale,
        string $sourceTextHash,
        string $voiceProvider,
        string $voiceId,
        string $modelId,
        string $generationVersion,
    ): string {
        return implode(':', [
            $contentScope,
            $this->safeKeyPart($externalId),
            $audioType,
            $locale,
            substr($sourceTextHash, 0, 12),
            $this->safeKeyPart($voiceProvider !== '' ? $voiceProvider : 'provider'),
            $this->safeKeyPart($voiceId !== '' ? $voiceId : 'voice'),
            $this->safeKeyPart($modelId !== '' ? $modelId : 'model'),
            $this->safeKeyPart($generationVersion !== '' ? $generationVersion : 'v1'),
        ]);
    }

    public function targetFileName(string $externalId, string $audioType, string $sourceTextHash): string
    {
        return $this->safePathPart($audioType).'-'.$this->safePathPart($externalId).'-'.substr($sourceTextHash, 0, 12).'.mp3';
    }

    public function targetStoragePath(string $prefix, string $externalId, string $targetFileName): string
    {
        return trim($prefix, '/').'/'.$this->safePathPart($externalId).'/'.$targetFileName;
    }

    public function canonicalExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '') {
            return '';
        }

        if (! str_contains($value, ':')) {
            return $value;
        }

        $suffix = trim(Str::afterLast($value, ':'));

        return $suffix !== '' ? $suffix : $value;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function audioExternalId(Question $question, array $options = []): string
    {
        $override = trim((string) ($options['audio_external_id'] ?? ''));

        if ($override !== '') {
            return $override;
        }

        return $this->canonicalExternalId($question->external_id);
    }

    public function normalizeSourceText(string $sourceText): string
    {
        return Str::squish($sourceText);
    }

    private function safeKeyPart(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'empty';
        }

        return preg_replace('/[^A-Za-z0-9_.-]+/', '-', $value) ?: 'value';
    }

    private function safePathPart(string $value): string
    {
        $value = $this->safeKeyPart($value);

        return trim($value, '.-') !== '' ? trim($value, '.-') : 'audio';
    }
}
