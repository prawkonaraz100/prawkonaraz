<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class QuestionAudioExportManifestBuilder
{
    private const SUPPORTED_TYPES = [
        QuestionAudioAsset::TYPE_QUESTION,
        QuestionAudioAsset::TYPE_CORRECT_ANSWER,
    ];

    public function __construct(
        private readonly StudyContextService $studyContextService,
        private readonly QuestionAudioTextBuilder $textBuilder,
    ) {}

    /**
     * @param  array{
     *     external_ids?: array<int, string>|string|null,
     *     category?: string|null,
     *     question_scope?: string|null,
     *     types?: array<int, string>|string|null,
     *     limit?: int|null,
     *     locale?: string|null,
     *     voice_provider?: string|null,
     *     voice_id?: string|null,
     *     model_id?: string|null,
     *     generation_version?: string|null,
     *     storage_prefix?: string|null
     * }  $options
     * @return array<string, mixed>
     */
    public function build(array $options = []): array
    {
        $types = $this->normalizeTypes($options['types'] ?? [QuestionAudioAsset::TYPE_QUESTION]);
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;
        $questionScope = $this->normalizeQuestionScope($options['question_scope'] ?? null);
        $prefix = trim((string) ($options['storage_prefix'] ?? config('media.question_audio_prefix', 'audio/questions')), '/');
        $groups = $this->questionGroups([
            ...$options,
            'question_scope' => $questionScope,
        ]);

        if ($limit !== null) {
            $groups = $groups->take($limit);
        }

        $items = [];
        $errors = [];
        $reviewItems = [];

        foreach ($groups as $canonicalExternalId => $questions) {
            foreach ($types as $type) {
                $sourceTextVariants = $this->sourceTextVariants($questions, $type);

                if ($sourceTextVariants->isEmpty()) {
                    $errors[] = [
                        'external_id' => (string) $canonicalExternalId,
                        'type' => $type,
                        'message' => 'Source text is empty.',
                    ];

                    continue;
                }

                if ($sourceTextVariants->count() > 1) {
                    $resolvedVariants = $this->resolvedAudioVariants((string) $canonicalExternalId, $sourceTextVariants);

                    if ($resolvedVariants === null) {
                        $reviewItems[] = [
                            'external_id' => (string) $canonicalExternalId,
                            'type' => $type,
                            'reason' => 'conflicting_source_text',
                            'message' => 'Canonical external_id has multiple source texts for this audio type. Verify the catalog before generating audio.',
                            'variant_count' => $sourceTextVariants->count(),
                            'raw_external_ids' => $this->rawExternalIds($questions),
                            'category_codes' => $this->categoryCodes($questions),
                            'variants' => $sourceTextVariants->values()->all(),
                        ];

                        continue;
                    }

                    foreach ($resolvedVariants as $variant) {
                        $variantQuestions = $questions
                            ->filter(fn (Question $question): bool => in_array((int) $question->getKey(), $variant['question_ids'] ?? [], true))
                            ->values();
                        $representative = $this->representativeQuestion($variantQuestions);
                        $item = $this->itemForType($representative, $type, [
                            ...$options,
                            'audio_external_id' => (string) $variant['audio_external_id'],
                        ]);

                        if ($item === null || $item['source_text'] === '') {
                            $errors[] = [
                                'external_id' => (string) $canonicalExternalId,
                                'type' => $type,
                                'message' => 'Source text is empty.',
                            ];

                            continue;
                        }

                        $item['question_id'] = (int) $representative->getKey();
                        $item['canonical_external_id'] = (string) $canonicalExternalId;
                        $item['question_scope'] = $questionScope;
                        $item['raw_external_ids'] = $variant['raw_external_ids'] ?? [];
                        $item['category_codes'] = $variant['category_codes'] ?? [];
                        $item['target_storage_disk'] = (string) config('media.question_audio_disk', 'media_local');
                        $item['target_storage_path'] = $this->textBuilder->targetStoragePath(
                            $prefix,
                            (string) $item['external_id'],
                            (string) $item['target_file_name'],
                        );

                        $items[] = $item;
                    }

                    continue;
                }

                $variant = $sourceTextVariants->first();
                $variantQuestionIds = is_array($variant) ? ($variant['question_ids'] ?? []) : [];
                $variantQuestions = $questions
                    ->filter(fn (Question $question): bool => in_array((int) $question->getKey(), $variantQuestionIds, true))
                    ->values();
                $representative = $this->representativeQuestion($variantQuestions->isNotEmpty() ? $variantQuestions : $questions);
                $item = $this->itemForType($representative, $type, $options);

                if ($item === null || $item['source_text'] === '') {
                    $errors[] = [
                        'external_id' => (string) $canonicalExternalId,
                        'type' => $type,
                        'message' => 'Source text is empty.',
                    ];

                    continue;
                }

                $item['question_id'] = (int) $representative->getKey();
                $item['canonical_external_id'] = (string) $canonicalExternalId;
                $item['question_scope'] = $questionScope;
                $item['raw_external_ids'] = $this->rawExternalIds($questions);
                $item['category_codes'] = $this->categoryCodes($questions);
                $item['target_storage_disk'] = (string) config('media.question_audio_disk', 'media_local');
                $item['target_storage_path'] = $this->textBuilder->targetStoragePath(
                    $prefix,
                    (string) $item['external_id'],
                    (string) $item['target_file_name'],
                );

                $items[] = $item;
            }
        }

        return [
            'schema_version' => 'question-audio-manifest-v1',
            'generated_at' => now()->toIso8601String(),
            'audio_types' => $types,
            'question_scope' => $questionScope,
            'locale' => (string) ($options['locale'] ?? config('media.question_audio_locale', 'pl-PL')),
            'voice_provider' => (string) ($options['voice_provider'] ?? config('media.question_audio_voice_provider', 'elevenlabs')),
            'voice_id' => (string) ($options['voice_id'] ?? config('media.question_audio_voice_id', '')),
            'model_id' => (string) ($options['model_id'] ?? config('media.question_audio_model_id', '')),
            'generation_version' => (string) ($options['generation_version'] ?? config('media.question_audio_generation_version', 'question-v1')),
            'storage_disk' => (string) config('media.question_audio_disk', 'media_local'),
            'storage_prefix' => $prefix,
            'items_count' => count($items),
            'errors_count' => count($errors),
            'errors' => $errors,
            'review_count' => count($reviewItems),
            'review_items' => $reviewItems,
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    private function itemForType(Question $question, string $type, array $options): ?array
    {
        return match ($type) {
            QuestionAudioAsset::TYPE_QUESTION => $this->textBuilder->buildQuestionPrompt($question, $options),
            QuestionAudioAsset::TYPE_CORRECT_ANSWER => $this->textBuilder->buildCorrectAnswer($question, $options),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<string, Collection<int, Question>>
     */
    private function questionGroups(array $options): Collection
    {
        $visibleCategoryIds = $this->studyContextService
            ->visibleCategoriesQuery()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($visibleCategoryIds === []) {
            return collect();
        }

        $query = Question::query()
            ->with('licenseCategory:id,code,slug,name,sort_order')
            ->withCount('media')
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereIn('license_category_id', $visibleCategoryIds);

        $categoryCode = trim((string) ($options['category'] ?? ''));

        if ($categoryCode !== '') {
            $query->whereHas('licenseCategory', fn ($categoryQuery) => $categoryQuery->where('code', $categoryCode));
        }

        $questionScope = trim((string) ($options['question_scope'] ?? ''));

        if ($questionScope === 'specialist') {
            $query->where('metadata->structure_scope', 'SPECJALISTYCZNY');
        } elseif ($questionScope === 'basic') {
            $query->where(function ($scopeQuery): void {
                $scopeQuery
                    ->whereNull('metadata->structure_scope')
                    ->orWhere('metadata->structure_scope', '!=', 'SPECJALISTYCZNY');
            });
        }

        $wantedExternalIds = $this->normalizeExternalIds($options['external_ids'] ?? null);

        return $query
            ->get(['id', 'license_category_id', 'external_id', 'prompt', 'option_a', 'option_b', 'option_c', 'correct_answer'])
            ->groupBy(fn (Question $question): string => $this->textBuilder->canonicalExternalId($question->external_id))
            ->when($wantedExternalIds !== [], fn (Collection $groups): Collection => $groups
                ->filter(fn (Collection $questions, string $externalId): bool => in_array($externalId, $wantedExternalIds, true)))
            ->sortKeysUsing('strnatcasecmp');
    }

    /**
     * @return list<string>
     */
    private function normalizeTypes(mixed $types): array
    {
        $values = is_array($types)
            ? $types
            : explode(',', (string) $types);

        $types = collect($values)
            ->map(fn (mixed $type): string => trim((string) $type))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($types === []) {
            $types = [QuestionAudioAsset::TYPE_QUESTION];
        }

        $unsupported = array_values(array_diff($types, self::SUPPORTED_TYPES));

        if ($unsupported !== []) {
            throw new InvalidArgumentException('Unsupported audio types: '.implode(', ', $unsupported));
        }

        return $types;
    }

    private function normalizeQuestionScope(mixed $questionScope): string
    {
        $value = trim((string) ($questionScope ?? ''));

        if ($value === '') {
            return 'all';
        }

        if (! in_array($value, ['all', 'basic', 'specialist'], true)) {
            throw new InvalidArgumentException('Unsupported question scope: '.$value);
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function normalizeExternalIds(mixed $externalIds): array
    {
        if ($externalIds === null || $externalIds === '') {
            return [];
        }

        $values = is_array($externalIds)
            ? $externalIds
            : explode(',', (string) $externalIds);

        return collect($values)
            ->map(fn (mixed $externalId): string => $this->textBuilder->canonicalExternalId($externalId))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function representativeQuestion(Collection $questions): Question
    {
        /** @var Question $question */
        $question = $questions
            ->sort(function (Question $left, Question $right): int {
                $comparisons = [
                    ((int) ($right->media_count ?? 0)) <=> ((int) ($left->media_count ?? 0)),
                    ((int) ($left->licenseCategory?->sort_order ?? PHP_INT_MAX)) <=> ((int) ($right->licenseCategory?->sort_order ?? PHP_INT_MAX)),
                    strcmp((string) ($left->licenseCategory?->code ?? ''), (string) ($right->licenseCategory?->code ?? '')),
                    $left->getKey() <=> $right->getKey(),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->first();

        return $question;
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, array<string, mixed>>
     */
    private function sourceTextVariants(Collection $questions, string $type): Collection
    {
        return $questions
            ->map(function (Question $question) use ($type): array {
                return [
                    'question_id' => (int) $question->getKey(),
                    'raw_external_id' => trim((string) $question->external_id),
                    'category_code' => (string) ($question->licenseCategory?->code ?? ''),
                    'source_text' => $this->sourceTextForType($question, $type),
                ];
            })
            ->filter(fn (array $variant): bool => (string) $variant['source_text'] !== '')
            ->groupBy('source_text')
            ->map(function (Collection $variants, string $sourceText): array {
                return [
                    'source_text' => $sourceText,
                    'question_ids' => $variants
                        ->pluck('question_id')
                        ->unique()
                        ->values()
                        ->all(),
                    'raw_external_ids' => $variants
                        ->pluck('raw_external_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'category_codes' => $variants
                        ->pluck('category_code')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }

    private function sourceTextForType(Question $question, string $type): string
    {
        return match ($type) {
            QuestionAudioAsset::TYPE_QUESTION => $this->textBuilder->normalizeSourceText(
                $this->textBuilder->buildQuestionPrompt($question)['source_text'] ?? '',
            ),
            QuestionAudioAsset::TYPE_CORRECT_ANSWER => $this->textBuilder->correctAnswerSourceText($question) ?? '',
            default => '',
        };
    }

    /**
     * @param  Collection<int|string, array<string, mixed>>  $sourceTextVariants
     * @return Collection<int, array<string, mixed>>|null
     */
    private function resolvedAudioVariants(string $canonicalExternalId, Collection $sourceTextVariants): ?Collection
    {
        $resolved = $sourceTextVariants
            ->map(function (array $variant) use ($canonicalExternalId): ?array {
                $audioExternalId = $this->audioExternalIdForSourceVariant($canonicalExternalId, $variant);

                if ($audioExternalId === null) {
                    return null;
                }

                return [
                    ...$variant,
                    'audio_external_id' => $audioExternalId,
                ];
            })
            ->values();

        if ($resolved->contains(null)) {
            return null;
        }

        $audioExternalIds = $resolved
            ->pluck('audio_external_id')
            ->map(fn (mixed $externalId): string => (string) $externalId)
            ->filter()
            ->values();

        if ($audioExternalIds->unique()->count() !== $audioExternalIds->count()) {
            return null;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $variant
     */
    private function audioExternalIdForSourceVariant(string $canonicalExternalId, array $variant): ?string
    {
        $rawExternalIds = collect($variant['raw_external_ids'] ?? [])
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values();

        if ($rawExternalIds->isEmpty()) {
            return null;
        }

        if ($rawExternalIds->count() === 1) {
            $rawExternalId = (string) $rawExternalIds->first();

            if ($this->textBuilder->canonicalExternalId($rawExternalId) !== $canonicalExternalId) {
                return null;
            }

            return str_contains($rawExternalId, ':')
                ? $rawExternalId
                : $canonicalExternalId;
        }

        $allBelongToCanonical = $rawExternalIds
            ->every(fn (string $rawExternalId): bool => $this->textBuilder->canonicalExternalId($rawExternalId) === $canonicalExternalId);

        $hasUnprefixedCanonical = $rawExternalIds
            ->contains(fn (string $rawExternalId): bool => $rawExternalId === $canonicalExternalId);

        return $allBelongToCanonical && $hasUnprefixedCanonical
            ? $canonicalExternalId
            : null;
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return list<string>
     */
    private function rawExternalIds(Collection $questions): array
    {
        return $questions
            ->pluck('external_id')
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return list<string>
     */
    private function categoryCodes(Collection $questions): array
    {
        return $questions
            ->map(fn (Question $question): ?LicenseCategory => $question->licenseCategory)
            ->filter(fn (mixed $category): bool => $category instanceof LicenseCategory)
            ->sort(function (LicenseCategory $left, LicenseCategory $right): int {
                $comparisons = [
                    ((int) $left->sort_order) <=> ((int) $right->sort_order),
                    strcmp((string) $left->code, (string) $right->code),
                    $left->getKey() <=> $right->getKey(),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->pluck('code')
            ->map(fn (mixed $code): string => (string) $code)
            ->unique()
            ->values()
            ->all();
    }
}
