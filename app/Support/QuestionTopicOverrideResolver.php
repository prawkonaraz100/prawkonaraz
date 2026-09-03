<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopicOverride;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionTopicOverrideResolver
{
    protected ?Collection $activeOverrides = null;

    protected ?Collection $categoryCodesById = null;

    /**
     * @return array{key:string,matched_by:string,reason:?string,override_id:int,category_code:string}|null
     */
    public function resolve(Question|array $question): ?array
    {
        $identity = $this->identityFor($question);

        if ($identity === null) {
            return null;
        }

        $override = $this->activeOverrides()
            ->get($this->lookupKey(
                $identity['category_code'],
                $identity['source'],
                $identity['external_id'],
            ));

        if (! $override) {
            return null;
        }

        return [
            'key' => (string) $override->question_topic_key,
            'matched_by' => 'override:'.$override->question_topic_key,
            'reason' => $override->reason ? (string) $override->reason : null,
            'override_id' => (int) $override->getKey(),
            'category_code' => (string) $override->license_category_code,
        ];
    }

    public function forgetCache(): void
    {
        $this->activeOverrides = null;
        $this->categoryCodesById = null;
    }

    /**
     * @param  Question|array<string, mixed>  $question
     * @return array{category_code:string,source:string,external_id:string}|null
     */
    protected function identityFor(Question|array $question): ?array
    {
        if ($question instanceof Question) {
            $categoryCode = $this->categoryCodeForQuestion($question);
            $source = $this->normalizeSource($question->source);
            $externalId = $this->normalizeExternalId($question->external_id);
        } else {
            $categoryCode = $this->normalizeCategoryCode((string) ($question['license_category_code'] ?? ''));
            $source = $this->normalizeSource($question['source'] ?? null);
            $externalId = $this->normalizeExternalId($question['external_id'] ?? null);
        }

        if (blank($categoryCode) || blank($source) || blank($externalId)) {
            return null;
        }

        return [
            'category_code' => $categoryCode,
            'source' => $source,
            'external_id' => $externalId,
        ];
    }

    protected function categoryCodeForQuestion(Question $question): string
    {
        if ($question->relationLoaded('licenseCategory')) {
            return $this->normalizeCategoryCode((string) ($question->licenseCategory?->code ?? ''));
        }

        return $this->categoryCodesById()
            ->get((int) $question->license_category_id, '');
    }

    /**
     * @return Collection<string, QuestionTopicOverride>
     */
    protected function activeOverrides(): Collection
    {
        if ($this->activeOverrides instanceof Collection) {
            return $this->activeOverrides;
        }

        return $this->activeOverrides = QuestionTopicOverride::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (QuestionTopicOverride $override): string => $this->lookupKey(
                (string) $override->license_category_code,
                (string) $override->source,
                (string) $override->external_id,
            ));
    }

    /**
     * @return Collection<int, string>
     */
    protected function categoryCodesById(): Collection
    {
        if ($this->categoryCodesById instanceof Collection) {
            return $this->categoryCodesById;
        }

        return $this->categoryCodesById = LicenseCategory::query()
            ->pluck('code', 'id')
            ->map(fn (mixed $code): string => $this->normalizeCategoryCode((string) $code));
    }

    protected function lookupKey(string $categoryCode, string $source, string $externalId): string
    {
        return implode('|', [
            $this->normalizeCategoryCode($categoryCode),
            $this->normalizeSource($source),
            $this->normalizeExternalId($externalId),
        ]);
    }

    protected function normalizeCategoryCode(string $value): string
    {
        return strtoupper(trim($value));
    }

    protected function normalizeSource(mixed $value): string
    {
        return Str::lower(trim((string) $value));
    }

    protected function normalizeExternalId(mixed $value): string
    {
        return trim((string) $value);
    }
}
