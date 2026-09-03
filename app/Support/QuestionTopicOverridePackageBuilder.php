<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QuestionTopicOverridePackageBuilder
{
    public function __construct(
        protected QuestionTopicClassifier $questionTopicClassifier,
        protected QuestionTopicReclassifierAuditService $questionTopicReclassifierAuditService,
    ) {}

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    public function build(array $spec): array
    {
        $categoryCode = strtoupper(trim((string) ($spec['category'] ?? '')));
        $scope = trim((string) ($spec['scope'] ?? QuestionTopicReclassifierAuditService::SCOPE_ACTIVE_READY));
        $rules = $spec['rules'] ?? null;

        if ($categoryCode === '') {
            throw new InvalidArgumentException('Spec musi zawierac "category".');
        }

        if (! is_array($rules) || ! array_is_list($rules) || $rules === []) {
            throw new InvalidArgumentException('Spec musi zawierac niepusta liste "rules".');
        }

        $normalizedScope = $this->questionTopicReclassifierAuditService->normalizeScope($scope);
        $questions = Question::query()
            ->with([
                'licenseCategory:id,code',
                'questionTopic:id,key,name',
            ])
            ->whereHas('licenseCategory', fn (Builder $query) => $query->where('code', $categoryCode))
            ->when($normalizedScope !== QuestionTopicReclassifierAuditService::SCOPE_ALL, fn (Builder $query) => $query->where('is_active', true))
            ->when($normalizedScope === QuestionTopicReclassifierAuditService::SCOPE_ACTIVE_READY, fn (Builder $query) => $query->readyForDelivery())
            ->orderBy('id')
            ->get();

        $questionContexts = $questions->map(function (Question $question) use ($categoryCode): array {
            $classification = $this->questionTopicClassifier->classify($question);
            $normalizedPrompt = $this->normalizeSearchableText((string) $question->prompt);
            $normalizedMainMediaOriginal = $this->normalizeSearchableText((string) data_get($question->metadata, 'main_media_original', ''));
            $normalizedExternalId = $this->normalizeIdentityValue((string) $question->external_id);

            return [
                'question' => $question,
                'current_topic_key' => (string) ($question->questionTopic?->key ?? 'unassigned'),
                'classifier_topic_key' => (string) ($classification['key'] ?? ''),
                'classifier_matched_by' => (string) ($classification['matched_by'] ?? ''),
                'normalized_prompt' => $normalizedPrompt,
                'normalized_main_media_original' => $normalizedMainMediaOriginal,
                'normalized_external_id' => $normalizedExternalId,
                'lookup_key' => implode('|', [
                    strtoupper((string) ($question->licenseCategory?->code ?? $categoryCode)),
                    Str::lower((string) $question->source),
                    (string) $question->external_id,
                ]),
            ];
        });

        $entries = [];
        $seenLookupKeys = [];
        $ruleSummaries = [];

        foreach ($rules as $index => $rule) {
            if (! is_array($rule)) {
                throw new InvalidArgumentException(sprintf('Rule[%d] musi byc obiektem.', $index));
            }

            $targetTopicKey = trim((string) ($rule['target_topic_key'] ?? ''));
            $reason = trim((string) ($rule['reason'] ?? ''));
            $name = trim((string) ($rule['name'] ?? ('rule-'.$index)));
            $allowedCurrentTopicKeys = $this->normalizeStringList($rule['current_topic_keys'] ?? []);
            $allowedClassifierMatchedBy = $this->normalizeStringList($rule['classifier_matched_by'] ?? []);
            $promptContainsAny = $this->normalizeSearchList($rule['prompt_contains_any'] ?? []);
            $promptNotContainsAny = $this->normalizeSearchList($rule['prompt_not_contains_any'] ?? []);
            $mediaContainsAny = $this->normalizeSearchList($rule['media_contains_any'] ?? []);
            $mediaNotContainsAny = $this->normalizeSearchList($rule['media_not_contains_any'] ?? []);
            $externalIds = $this->normalizeIdentityList($rule['external_ids'] ?? []);

            if ($targetTopicKey === '' || $reason === '') {
                throw new InvalidArgumentException(sprintf('Rule[%d] musi zawierac "target_topic_key" i "reason".', $index));
            }

            $matchedCount = 0;

            foreach ($questionContexts as $context) {
                /** @var Question $question */
                $question = $context['question'];
                $currentTopicKey = $context['current_topic_key'];
                $classifierTopicKey = $context['classifier_topic_key'];
                $classifierMatchedBy = $context['classifier_matched_by'];
                $normalizedPrompt = $context['normalized_prompt'];
                $normalizedMainMediaOriginal = $context['normalized_main_media_original'];
                $normalizedExternalId = $context['normalized_external_id'];

                if ($allowedCurrentTopicKeys !== [] && ! in_array($currentTopicKey, $allowedCurrentTopicKeys, true)) {
                    continue;
                }

                if ($allowedClassifierMatchedBy !== [] && ! in_array($classifierMatchedBy, $allowedClassifierMatchedBy, true)) {
                    continue;
                }

                if ($externalIds !== [] && ! in_array($normalizedExternalId, $externalIds, true)) {
                    continue;
                }

                if ($promptContainsAny !== [] && ! $this->matchesContainsAny($normalizedPrompt, $promptContainsAny)) {
                    continue;
                }

                if ($promptNotContainsAny !== [] && $this->matchesContainsAny($normalizedPrompt, $promptNotContainsAny)) {
                    continue;
                }

                if ($mediaContainsAny !== [] && ! $this->matchesContainsAny($normalizedMainMediaOriginal, $mediaContainsAny)) {
                    continue;
                }

                if ($mediaNotContainsAny !== [] && $this->matchesContainsAny($normalizedMainMediaOriginal, $mediaNotContainsAny)) {
                    continue;
                }

                if ($currentTopicKey === $targetTopicKey) {
                    continue;
                }

                if (blank($question->source) || blank($question->external_id)) {
                    continue;
                }

                $lookupKey = $context['lookup_key'];

                if (isset($seenLookupKeys[$lookupKey])) {
                    continue;
                }

                $seenLookupKeys[$lookupKey] = true;
                $matchedCount++;

                $entries[] = [
                    'license_category_code' => strtoupper((string) ($question->licenseCategory?->code ?? $categoryCode)),
                    'source' => Str::lower((string) $question->source),
                    'external_id' => (string) $question->external_id,
                    'question_topic_key' => $targetTopicKey,
                    'reason' => $reason,
                    'metadata' => [
                        'package_rule' => $name,
                        'current_topic_key' => $currentTopicKey,
                        'classifier_topic_key' => $classifierTopicKey,
                        'classifier_matched_by' => $classifierMatchedBy,
                        'prompt_excerpt' => Str::limit(Str::squish((string) $question->prompt), 180),
                        'main_media_original' => (string) data_get($question->metadata, 'main_media_original', ''),
                    ],
                ];
            }

            $ruleSummaries[] = [
                'name' => $name,
                'target_topic_key' => $targetTopicKey,
                'matched_questions' => $matchedCount,
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'category' => $categoryCode,
            'scope' => $normalizedScope,
            'rules_count' => count($rules),
            'exported_candidates' => count($entries),
            'rule_summaries' => $ruleSummaries,
            'overrides' => array_values($entries),
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,\s;]+/', $value) ?: [];
        }

        return collect(is_array($value) ? $value : [])
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function normalizeSearchList(mixed $value): array
    {
        return collect($this->normalizeStringList($value))
            ->map(fn (string $item): string => $this->normalizeSearchableText($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function normalizeIdentityList(mixed $value): array
    {
        return collect($this->normalizeStringList($value))
            ->map(fn (string $item): string => $this->normalizeIdentityValue($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeSearchableText(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }

    protected function normalizeIdentityValue(string $value): string
    {
        return Str::upper(trim($value));
    }

    /**
     * @param  array<int, string>  $needles
     */
    protected function matchesContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
