<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use Illuminate\Support\Collection;

class QuestionRelationV2ShadowAuditor
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
        protected PublicQuestionRelationsService $publicRelations,
        protected QuestionRelationV2ShadowResolver $shadowResolver,
        protected QuestionRelationV2ShadowComparator $shadowComparator,
    ) {}

    /**
     * Compare one immutable V2 run with the current V1 selector without
     * mutating the graph, the rollout, or public HTML.
     *
     * @return array<string, mixed>
     */
    public function audit(string $topicKey, ?int $requestedRunId = null, int $sampleLimit = 25): array
    {
        $sampleLimit = max(1, $sampleLimit);
        $minimumLinks = max(1, (int) config('question_relations.minimum_links', 15));
        $topics = QuestionSeoTopic::query()->where('key', $topicKey)->get();
        $topic = $topics->count() === 1 ? $topics->first() : null;
        $run = $topic instanceof QuestionSeoTopic
            ? $this->runForTopic($topic, $requestedRunId)
            : null;
        $issues = [
            'errors' => [],
            'warnings' => [],
        ];
        $addIssue = function (string $severity, string $type, string $message, array $context = []) use (&$issues, $sampleLimit): void {
            if (count($issues[$severity]) >= $sampleLimit) {
                return;
            }

            $issues[$severity][] = compact('type', 'message', 'context');
        };

        if ($topics->count() !== 1) {
            $addIssue('errors', 'topic_not_unique', 'Klucz topicu nie wskazuje dokładnie jednego rekordu.', [
                'topic_key' => $topicKey,
                'matches' => $topics->count(),
            ]);
        }
        if ($topic instanceof QuestionSeoTopic && $topic->status !== QuestionSeoTopic::STATUS_PUBLISHED) {
            $addIssue('errors', 'topic_not_published', 'Topic shadow nie jest opublikowany.', [
                'topic_id' => $topic->getKey(),
                'status' => $topic->status,
            ]);
        }
        if (! $run instanceof QuestionRelationRankingRun) {
            $addIssue('errors', 'run_not_found', 'Nie znaleziono runu V2 dla wskazanego topicu.', [
                'requested_run_id' => $requestedRunId,
            ]);
        }
        if ($run instanceof QuestionRelationRankingRun
            && ! in_array($run->status, [
                QuestionRelationRankingRun::STATUS_VALIDATED,
                QuestionRelationRankingRun::STATUS_PUBLISHED,
            ], true)) {
            $addIssue('errors', 'run_not_validated', 'Run V2 nie ma statusu validated ani published.', [
                'run_id' => $run->getKey(),
                'status' => $run->status,
            ]);
        }

        $rollout = $topic instanceof QuestionSeoTopic
            ? QuestionRelationRollout::query()
                ->where('question_seo_topic_id', $topic->getKey())
                ->first()
            : null;
        if (! $rollout instanceof QuestionRelationRollout) {
            $addIssue('warnings', 'shadow_rollout_not_configured', 'Run można audytować, ale mode=shadow nie jest jeszcze skonfigurowany.', []);
        } elseif ($rollout->mode !== QuestionRelationRollout::MODE_SHADOW) {
            $addIssue('warnings', 'shadow_rollout_not_active', 'Topic ma rollout, ale nie w trybie shadow.', [
                'mode' => $rollout->mode,
            ]);
        } elseif ($run instanceof QuestionRelationRankingRun
            && (int) $rollout->active_ranking_run_id !== (int) $run->getKey()) {
            $addIssue('warnings', 'shadow_rollout_run_mismatch', 'Shadow rollout wskazuje inny run niż audytowany.', [
                'active_ranking_run_id' => $rollout->active_ranking_run_id,
                'audited_run_id' => $run->getKey(),
            ]);
        }

        $recommendations = $run instanceof QuestionRelationRankingRun
            ? QuestionRelationRecommendation::query()
                ->where('question_relation_ranking_run_id', $run->getKey())
                ->selected()
                ->orderBy('source_explanation_id')
                ->orderBy('rank')
                ->get()
            : collect();
        $sourceRecommendationCounts = $recommendations
            ->groupBy('source_explanation_id')
            ->map(fn (Collection $items): int => $items->count());

        if ($run instanceof QuestionRelationRankingRun && $recommendations->isEmpty()) {
            $addIssue('errors', 'no_selected_recommendations', 'Audytowany run nie ma wybranych rekomendacji.', [
                'run_id' => $run->getKey(),
            ]);
        }
        if ($sourceRecommendationCounts->isNotEmpty()
            && (int) $sourceRecommendationCounts->min() < $minimumLinks) {
            $addIssue('errors', 'minimum_links_not_met', 'Co najmniej jedno źródło runu ma za mało rekomendacji.', [
                'minimum_links' => $minimumLinks,
                'actual_minimum' => (int) $sourceRecommendationCounts->min(),
            ]);
        }

        $sources = QuestionPublicExplanation::query()
            ->whereIn('id', $sourceRecommendationCounts->keys())
            ->get(['id', 'external_id', 'related_questions', 'status', 'published_at'])
            ->keyBy('id');
        $questionsByExternalId = $this->catalog->canonicalQuestionsByExternalIds(
            $sources->pluck('external_id'),
        );
        $comparisons = collect();
        $unresolvedSourceCount = 0;
        $unresolvedTargetCount = 0;
        $excludedDuplicateCount = 0;

        foreach ($sourceRecommendationCounts as $sourceId => $selectedCount) {
            $source = $sources->get((int) $sourceId);

            if (! $source instanceof QuestionPublicExplanation
                || $source->status !== QuestionPublicExplanation::STATUS_PUBLISHED
                || $source->published_at === null) {
                $unresolvedSourceCount++;
                $addIssue('errors', 'source_explanation_not_public', 'Źródło rekomendacji nie jest opublikowanym wyjaśnieniem.', [
                    'source_explanation_id' => (int) $sourceId,
                ]);

                continue;
            }

            $question = $questionsByExternalId->get((string) $source->external_id);

            if (! $question instanceof Question) {
                $unresolvedSourceCount++;
                $addIssue('errors', 'source_question_not_resolvable', 'Nie można rozwiązać publicznego pytania źródłowego.', [
                    'source_explanation_id' => (int) $source->getKey(),
                    'external_id' => (string) $source->external_id,
                ]);

                continue;
            }

            $categories = $this->catalog->categoriesForQuestions(collect([$question]));
            $legacy = $this->catalog->editorialRelatedQuestions(
                is_array($source->related_questions) ? $source->related_questions : [],
                $categories,
            );
            $v1 = $this->publicRelations->forQuestion($question, $categories, $legacy, false);
            $v2 = $run instanceof QuestionRelationRankingRun && $topic instanceof QuestionSeoTopic
                ? $this->shadowResolver->forRun($source, $run, $topic)
                : null;

            if ($v2 === null) {
                $unresolvedSourceCount++;
                $addIssue('errors', 'source_not_eligible_for_v2', 'Źródło nie ma właściwego primary membershipu V2 albo run nie jest czytelny.', [
                    'source_explanation_id' => (int) $source->getKey(),
                    'external_id' => (string) $source->external_id,
                ]);

                continue;
            }

            $comparison = $this->shadowComparator->compare($v1, $v2);
            $unresolvedTargetCount += (int) $v2['unresolved_target_count'];
            $excludedDuplicateCount += (int) $v2['excluded_duplicate_count'];
            $comparisons->push([
                'source_explanation_id' => (int) $source->getKey(),
                'external_id' => (string) $source->external_id,
                'selected_recommendation_count' => (int) $selectedCount,
                'resolved_v2_count' => (int) $v2['total'],
                ...$comparison,
            ]);
        }

        if ($unresolvedTargetCount > 0) {
            $addIssue('errors', 'v2_target_not_resolvable', 'Część targetów V2 nie jest już publicznie rozwiązywalna.', [
                'count' => $unresolvedTargetCount,
            ]);
        }

        $issueCounts = [
            'errors' => count($issues['errors']),
            'warnings' => count($issues['warnings']),
        ];

        return [
            'schema_version' => 1,
            'audit_version' => 'question-relation-v2-shadow-audit-v1',
            'generated_at' => now()->toIso8601String(),
            'status' => $issueCounts['errors'] > 0
                ? 'failed'
                : ($issueCounts['warnings'] > 0 ? 'warning' : 'ok'),
            'topic' => [
                'key' => $topicKey,
                'id' => $topic?->getKey(),
                'status' => $topic?->status,
            ],
            'run' => [
                'id' => $run?->getKey(),
                'status' => $run?->status,
                'input_version' => $run?->input_version,
                'generator_version' => $run?->generator_version,
            ],
            'rollout' => [
                'exists' => $rollout instanceof QuestionRelationRollout,
                'mode' => $rollout?->mode,
                'active_ranking_run_id' => $rollout?->active_ranking_run_id,
                'exposure_percentage' => $rollout?->exposure_percentage,
                'global_v2_switch_enabled' => (bool) config('question_relations.v2_enabled', false),
                'runtime_shadow_switch_enabled' => (bool) config('question_relations.v2_shadow_enabled', false),
                'public_output' => false,
            ],
            'recommendations' => [
                'selected' => $recommendations->count(),
                'sources' => $sourceRecommendationCounts->count(),
                'minimum_per_source' => $sourceRecommendationCounts->isNotEmpty()
                    ? (int) $sourceRecommendationCounts->min()
                    : 0,
                'maximum_per_source' => $sourceRecommendationCounts->isNotEmpty()
                    ? (int) $sourceRecommendationCounts->max()
                    : 0,
                'minimum_required' => $minimumLinks,
                'unresolved_sources' => $unresolvedSourceCount,
                'unresolved_targets' => $unresolvedTargetCount,
                'excluded_duplicates' => $excludedDuplicateCount,
            ],
            'comparison' => [
                'sources_compared' => $comparisons->count(),
                'v1_links_total' => (int) $comparisons->sum('v1_total'),
                'v2_links_total' => (int) $comparisons->sum('v2_total'),
                'shared_links_total' => (int) $comparisons->sum('shared_total'),
                'v1_only_links_total' => (int) $comparisons->sum('v1_only_total'),
                'v2_only_links_total' => (int) $comparisons->sum('v2_only_total'),
                'same_rank_total' => (int) $comparisons->sum('same_rank_total'),
                'samples' => $comparisons
                    ->sortByDesc('v2_only_total')
                    ->take($sampleLimit)
                    ->values()
                    ->all(),
            ],
            'issues' => $issues,
            'issue_counts' => $issueCounts,
        ];
    }

    protected function runForTopic(QuestionSeoTopic $topic, ?int $requestedRunId): ?QuestionRelationRankingRun
    {
        $query = QuestionRelationRankingRun::query()
            ->where('question_seo_topic_id', $topic->getKey());

        if ($requestedRunId !== null) {
            return $query->find($requestedRunId);
        }

        return $query
            ->orderByDesc('validated_at')
            ->orderByDesc('id')
            ->first();
    }
}
