<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QuestionTopicReclassifierAuditService
{
    public const SCOPE_ACTIVE_READY = 'active_ready';
    public const SCOPE_ACTIVE = 'active';
    public const SCOPE_ALL = 'all';

    public function __construct(
        protected QuestionTopicClassifier $questionTopicClassifier,
        protected QuestionTopicOverrideResolver $questionTopicOverrideResolver,
    ) {}

    /**
     * @param  array<int, string>  $categoryFilter
     * @return array<string, mixed>
     */
    public function audit(
        array $categoryFilter = [],
        string $scope = self::SCOPE_ACTIVE_READY,
        ?int $limit = null,
        bool $includeUnchanged = false,
    ): array {
        $normalizedScope = $this->normalizeScope($scope);
        $normalizedCategoryFilter = array_values(array_unique(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            $categoryFilter,
        ))));

        $query = Question::query()
            ->with([
                'licenseCategory:id,code',
                'questionTopic:id,key,name',
            ])
            ->when($normalizedCategoryFilter !== [], function (Builder $questionQuery) use ($normalizedCategoryFilter): void {
                $questionQuery->whereHas('licenseCategory', fn (Builder $categoryQuery) => $categoryQuery->whereIn('code', $normalizedCategoryFilter));
            })
            ->when($normalizedScope !== self::SCOPE_ALL, fn (Builder $questionQuery) => $questionQuery->where('is_active', true))
            ->when($normalizedScope === self::SCOPE_ACTIVE_READY, fn (Builder $questionQuery) => $questionQuery->readyForDelivery())
            ->orderBy('id');

        $processed = 0;
        $changed = 0;
        $entries = [];
        $currentTopicCounts = [];
        $classifierTopicCounts = [];
        $proposedTopicCounts = [];
        $classifierMatchedByCounts = [];
        $matchedByCounts = [];
        $transitionCounts = [];
        $categorySummaries = [];

        $query->chunkById(200, function (Collection $questions) use (
            $limit,
            $includeUnchanged,
            &$processed,
            &$changed,
            &$entries,
            &$currentTopicCounts,
            &$classifierTopicCounts,
            &$proposedTopicCounts,
            &$classifierMatchedByCounts,
            &$matchedByCounts,
            &$transitionCounts,
            &$categorySummaries,
        ) {
            foreach ($questions as $question) {
                if ($limit !== null && $processed >= $limit) {
                    return false;
                }

                $categoryCode = (string) ($question->licenseCategory?->code ?? 'UNKNOWN');
                $classifierClassification = $this->questionTopicClassifier->classify($question);
                $overrideClassification = $this->questionTopicOverrideResolver->resolve($question);
                $classification = $overrideClassification ?? $classifierClassification;
                $currentTopicKey = (string) ($question->questionTopic?->key ?? 'unassigned');
                $classifierTopicKey = (string) $classifierClassification['key'];
                $proposedTopicKey = (string) $classification['key'];
                $matchedBy = (string) $classification['matched_by'];
                $classifierMatchedBy = (string) $classifierClassification['matched_by'];
                $isChanged = $currentTopicKey !== $proposedTopicKey;

                $processed++;

                $categorySummaries[$categoryCode]['processed_questions'] = (int) ($categorySummaries[$categoryCode]['processed_questions'] ?? 0) + 1;
                $categorySummaries[$categoryCode]['changed_questions'] = (int) ($categorySummaries[$categoryCode]['changed_questions'] ?? 0);

                $currentTopicCounts[$categoryCode][$currentTopicKey] = (int) ($currentTopicCounts[$categoryCode][$currentTopicKey] ?? 0) + 1;
                $classifierTopicCounts[$categoryCode][$classifierTopicKey] = (int) ($classifierTopicCounts[$categoryCode][$classifierTopicKey] ?? 0) + 1;
                $proposedTopicCounts[$categoryCode][$proposedTopicKey] = (int) ($proposedTopicCounts[$categoryCode][$proposedTopicKey] ?? 0) + 1;
                $classifierMatchedByCounts[$categoryCode][$classifierMatchedBy] = (int) ($classifierMatchedByCounts[$categoryCode][$classifierMatchedBy] ?? 0) + 1;
                $matchedByCounts[$categoryCode][$matchedBy] = (int) ($matchedByCounts[$categoryCode][$matchedBy] ?? 0) + 1;

                if ($isChanged) {
                    $changed++;
                    $categorySummaries[$categoryCode]['changed_questions']++;
                    $transitionCounts[$categoryCode][$currentTopicKey][$proposedTopicKey] = (int) ($transitionCounts[$categoryCode][$currentTopicKey][$proposedTopicKey] ?? 0) + 1;
                }

                if (! $includeUnchanged && ! $isChanged) {
                    continue;
                }

                $entries[] = [
                    'question_id' => $question->getKey(),
                    'external_id' => $question->external_id,
                    'category_code' => $categoryCode,
                    'source' => $question->source,
                    'structure_scope' => $question->metadata['structure_scope'] ?? null,
                    'current_topic_key' => $currentTopicKey,
                    'current_topic_label' => $currentTopicKey === 'unassigned'
                        ? 'Nieprzypisany'
                        : $this->questionTopicClassifier->displayLabelForKey($currentTopicKey),
                    'current_bucket' => $currentTopicKey === 'unassigned'
                        ? null
                        : $this->questionTopicClassifier->bucketLabelForKey($currentTopicKey),
                    'classifier_topic_key' => $classifierTopicKey,
                    'classifier_topic_label' => $this->questionTopicClassifier->displayLabelForKey($classifierTopicKey),
                    'classifier_bucket' => $this->questionTopicClassifier->bucketLabelForKey($classifierTopicKey),
                    'classifier_matched_by' => $classifierMatchedBy,
                    'override_topic_key' => $overrideClassification['key'] ?? null,
                    'override_reason' => $overrideClassification['reason'] ?? null,
                    'override_id' => $overrideClassification['override_id'] ?? null,
                    'resolution_source' => $overrideClassification !== null ? 'override' : 'classifier',
                    'proposed_topic_key' => $proposedTopicKey,
                    'proposed_topic_label' => $this->questionTopicClassifier->displayLabelForKey($proposedTopicKey),
                    'proposed_bucket' => $this->questionTopicClassifier->bucketLabelForKey($proposedTopicKey),
                    'matched_by' => $matchedBy,
                    'changed' => $isChanged,
                    'prompt_excerpt' => Str::limit(Str::squish((string) $question->prompt), 180),
                ];
            }

            return null;
        });

        foreach ($categorySummaries as $categoryCode => $summary) {
            $categorySummaries[$categoryCode]['unchanged_questions'] = (int) $summary['processed_questions'] - (int) $summary['changed_questions'];
        }

        ksort($categorySummaries);

        return [
            'generated_at' => now()->toIso8601String(),
            'scope' => $normalizedScope,
            'category_filter' => $normalizedCategoryFilter,
            'include_unchanged' => $includeUnchanged,
            'limit' => $limit,
            'processed_questions' => $processed,
            'changed_questions' => $changed,
            'unchanged_questions' => $processed - $changed,
            'category_summaries' => $categorySummaries,
            'current_topic_counts' => $this->sortNestedCounts($currentTopicCounts),
            'classifier_topic_counts' => $this->sortNestedCounts($classifierTopicCounts),
            'proposed_topic_counts' => $this->sortNestedCounts($proposedTopicCounts),
            'classifier_matched_by_counts' => $this->sortNestedCounts($classifierMatchedByCounts),
            'matched_by_counts' => $this->sortNestedCounts($matchedByCounts),
            'transition_counts' => $this->sortTransitionCounts($transitionCounts),
            'entries' => $entries,
        ];
    }

    public function normalizeScope(string $scope): string
    {
        $normalizedScope = Str::lower(trim($scope));

        if (! in_array($normalizedScope, [
            self::SCOPE_ACTIVE_READY,
            self::SCOPE_ACTIVE,
            self::SCOPE_ALL,
        ], true)) {
            throw new InvalidArgumentException(sprintf(
                'Nieobslugiwany scope "%s". Dostepne: %s.',
                $scope,
                implode(', ', [self::SCOPE_ACTIVE_READY, self::SCOPE_ACTIVE, self::SCOPE_ALL]),
            ));
        }

        return $normalizedScope;
    }

    /**
     * @param  array<string, array<string, int>>  $counts
     * @return array<string, array<string, int>>
     */
    protected function sortNestedCounts(array $counts): array
    {
        ksort($counts);

        foreach ($counts as &$categoryCounts) {
            ksort($categoryCounts);
        }

        return $counts;
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $counts
     * @return array<string, array<string, array<string, int>>>
     */
    protected function sortTransitionCounts(array $counts): array
    {
        ksort($counts);

        foreach ($counts as &$fromTopics) {
            ksort($fromTopics);

            foreach ($fromTopics as &$toTopics) {
                ksort($toTopics);
            }
        }

        return $counts;
    }
}
