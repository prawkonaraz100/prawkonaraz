<?php

namespace App\Support;

use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelation;
use App\Models\QuestionSeoTopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionRelationGraphAuditor
{
    /** @var array<string, mixed> */
    protected array $report = [];

    protected int $sampleLimit = 25;

    public function __construct(
        protected PublicQuestionCatalogService $catalog,
    ) {}

    /**
     * Build a read-only baseline of the V1 question relation graph.
     *
     * @return array<string, mixed>
     */
    public function audit(int $sampleLimit = 25): array
    {
        $this->sampleLimit = max(1, $sampleLimit);
        $minimumLinks = max(1, (int) config('question_relations.minimum_links', 15));
        $automaticThreshold = (float) config('question_relations.automatic_score_threshold', 0.50);
        $indexableMinimum = max(1, (int) config('question_relations.indexable_topic_minimum_questions', 10));

        $this->report = [
            'schema_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'status' => 'unknown',
            'configuration' => [
                'minimum_links' => $minimumLinks,
                'automatic_score_threshold' => $automaticThreshold,
                'indexable_topic_minimum_questions' => $indexableMinimum,
            ],
            'counts' => [],
            'relations' => [],
            'coverage' => [],
            'fallback_pressure' => [],
            'topics' => [],
            'duplicate_recommendation_sets' => [],
            'issues' => [
                'errors' => [],
                'warnings' => [],
            ],
            'issue_counts' => [
                'errors' => 0,
                'warnings' => 0,
            ],
            'issue_type_counts' => [
                'errors' => [],
                'warnings' => [],
            ],
        ];

        $publicQuestions = $this->catalog->publicQuestionsQuery()
            ->get(['id', 'external_id', 'license_category_id']);
        $publicQuestionIds = $publicQuestions->pluck('id')->mapWithKeys(fn (mixed $id): array => [(int) $id => true]);
        $publicExternalIds = $publicQuestions
            ->pluck('external_id')
            ->filter(fn (mixed $id): bool => filled($id))
            ->flatMap(fn (mixed $id): array => array_unique([
                (string) $id,
                $this->catalog->displayExternalId($id),
            ]))
            ->filter()
            ->mapWithKeys(fn (string $id): array => [$id => true]);

        $publishedExplanations = QuestionPublicExplanation::query()
            ->published()
            ->get(['id', 'question_id', 'external_id', 'related_questions']);
        $publishedExplanationIds = $publishedExplanations
            ->pluck('id')
            ->mapWithKeys(fn (mixed $id): array => [(int) $id => true]);
        $publicExplanations = $publishedExplanations
            ->filter(fn (QuestionPublicExplanation $explanation): bool => $this->hasPublicQuestion(
                $explanation,
                $publicQuestionIds,
                $publicExternalIds,
            ))
            ->values();
        $publicExplanationIds = $publicExplanations
            ->pluck('id')
            ->mapWithKeys(fn (mixed $id): array => [(int) $id => true]);
        $publicExplanationByExternalId = $this->explanationsByExternalId($publicExplanations);

        $topics = QuestionSeoTopic::query()
            ->get(['id', 'parent_id', 'key', 'slug', 'label', 'description', 'question_count', 'is_indexable']);
        $topicById = $topics->keyBy(fn (QuestionSeoTopic $topic): int => (int) $topic->getKey());
        $memberships = DB::table('question_seo_topic_memberships')
            ->get(['question_public_explanation_id', 'question_seo_topic_id', 'source']);
        $membershipByExplanation = $memberships->keyBy(fn (object $row): int => (int) $row->question_public_explanation_id);
        $publicMembersByTopic = $memberships
            ->filter(fn (object $row): bool => $publicExplanationIds->has((int) $row->question_public_explanation_id))
            ->groupBy(fn (object $row): int => (int) $row->question_seo_topic_id)
            ->map(fn (Collection $rows): Collection => $rows
                ->pluck('question_public_explanation_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values());
        $topicRelations = DB::table('question_seo_topic_relations')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['source_topic_id', 'target_topic_id', 'relation_type', 'display_order']);
        $adjacentTopicIds = $topicRelations
            ->groupBy(fn (object $row): int => (int) $row->source_topic_id)
            ->map(fn (Collection $rows): Collection => $rows
                ->pluck('target_topic_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values());

        $relations = QuestionRelation::query()
            ->get([
                'id',
                'left_explanation_id',
                'right_explanation_id',
                'relation_type',
                'direction',
                'source',
                'status',
                'score',
                'review_priority',
            ]);
        $publishedStatuses = [QuestionRelation::STATUS_VERIFIED, QuestionRelation::STATUS_AUTOMATIC];
        $publishedRelations = $relations->whereIn('status', $publishedStatuses)->values();
        $safePublishedRelations = $publishedRelations
            ->filter(fn (QuestionRelation $relation): bool => $publicExplanationIds->has((int) $relation->left_explanation_id)
                && $publicExplanationIds->has((int) $relation->right_explanation_id)
                && (int) $relation->left_explanation_id !== (int) $relation->right_explanation_id)
            ->values();

        $safeNeighbours = $this->neighbourMap($publicExplanationIds->keys(), $safePublishedRelations);
        $allNeighbours = $this->neighbourMap($publishedExplanationIds->keys(), $relations);
        $legacyNeighbours = $this->legacyNeighbourMap($publicExplanations, $publicExplanationByExternalId);
        $effectiveDirectNeighbours = $safeNeighbours->map(function (Collection $neighbours, int $explanationId) use ($legacyNeighbours): Collection {
            return $neighbours
                ->concat($legacyNeighbours->get($explanationId, collect()))
                ->filter(fn (int $id): bool => $id !== $explanationId)
                ->unique()
                ->values();
        });

        $this->auditRelationIntegrity(
            $relations,
            $publishedExplanationIds,
            $publicExplanationIds,
            $membershipByExplanation,
            $automaticThreshold,
        );
        $this->auditTopicIntegrity(
            $topics,
            $publicMembersByTopic,
            $topicRelations,
            $indexableMinimum,
        );

        $publicWithoutTopic = $publicExplanations
            ->reject(fn (QuestionPublicExplanation $explanation): bool => $membershipByExplanation->has((int) $explanation->getKey()));
        foreach ($publicWithoutTopic as $explanation) {
            $this->issue('warnings', 'public_explanation_without_topic', 'Publiczne wyjaśnienie nie ma przypisanego tematu SEO.', [
                'explanation_id' => (int) $explanation->getKey(),
                'external_id' => (string) $explanation->external_id,
            ]);
        }

        $directDegrees = $publicExplanationIds->keys()
            ->map(fn (mixed $id): int => $effectiveDirectNeighbours->get((int) $id, collect())->count());
        $safeRelationDegrees = $publicExplanationIds->keys()
            ->map(fn (mixed $id): int => $safeNeighbours->get((int) $id, collect())->count());
        $allDegrees = $publishedExplanationIds->keys()
            ->map(fn (mixed $id): int => $allNeighbours->get((int) $id, collect())->count());

        foreach ($publicExplanations as $explanation) {
            $explanationId = (int) $explanation->getKey();
            if ($effectiveDirectNeighbours->get($explanationId, collect())->isEmpty()) {
                $this->issue('warnings', 'public_explanation_without_direct_relation', 'Publiczne wyjaśnienie nie ma bezpośredniej publicznej relacji ani relacji redakcyjnej legacy.', [
                    'explanation_id' => $explanationId,
                    'external_id' => (string) $explanation->external_id,
                ]);
            }
        }

        $fallback = $this->fallbackPressure(
            $publicExplanationIds->keys(),
            $effectiveDirectNeighbours,
            $membershipByExplanation,
            $topicById,
            $publicMembersByTopic,
            $adjacentTopicIds,
            $minimumLinks,
        );
        $duplicateSets = $this->duplicateRecommendationSets($effectiveDirectNeighbours);

        if ($topicRelations->isEmpty() && $topics->isNotEmpty()) {
            $this->issue('warnings', 'no_adjacent_topic_relations', 'Nie zdefiniowano żadnych relacji między sąsiednimi tematami; ten poziom fallbacku jest nieaktywny.', [
                'topics' => $topics->count(),
            ]);
        }

        $candidateRelations = $relations->where('status', QuestionRelation::STATUS_CANDIDATE);
        if ($candidateRelations->isNotEmpty()) {
            $this->issue('warnings', 'candidate_review_backlog', 'Graf zawiera kandydatów oczekujących na decyzję redakcyjną.', [
                'count' => $candidateRelations->count(),
                'by_priority' => $this->countsBy($candidateRelations, 'review_priority'),
            ]);
        }

        if ((int) $duplicateSets['questions_in_repeated_sets'] > 0) {
            $this->issue('warnings', 'repeated_direct_recommendation_sets', 'Wiele pytań ma identyczny zestaw bezpośrednich rekomendacji.', [
                'questions' => (int) $duplicateSets['questions_in_repeated_sets'],
                'groups' => (int) $duplicateSets['repeated_set_groups'],
                'largest_group' => (int) $duplicateSets['largest_group'],
            ]);
        }

        $this->report['counts'] = [
            'public_question_rows' => $publicQuestions->count(),
            'public_question_external_ids' => $publicQuestions->pluck('external_id')->filter()->unique()->count(),
            'published_explanations' => $publishedExplanations->count(),
            'public_resolvable_explanations' => $publicExplanations->count(),
            'relations' => $relations->count(),
            'published_relations' => $publishedRelations->count(),
            'safe_published_relations' => $safePublishedRelations->count(),
            'topics' => $topics->count(),
            'topic_memberships' => $memberships->count(),
            'topic_relations' => $topicRelations->count(),
        ];
        $this->report['relations'] = [
            'by_source' => $this->countsBy($relations, 'source'),
            'by_status' => $this->countsBy($relations, 'status'),
            'by_type' => $this->countsBy($relations, 'relation_type'),
            'by_direction' => $this->countsBy($relations, 'direction'),
            'candidate_by_review_priority' => $this->countsBy($candidateRelations, 'review_priority'),
            'effective_direct_degree' => $this->distribution($directDegrees),
            'safe_relation_degree' => $this->distribution($safeRelationDegrees),
            'all_relation_degree' => $this->distribution($allDegrees),
        ];
        $this->report['coverage'] = [
            'with_topic' => $publicExplanations->count() - $publicWithoutTopic->count(),
            'without_topic' => $publicWithoutTopic->count(),
            'with_effective_direct_relation' => $directDegrees->filter(fn (int $degree): bool => $degree > 0)->count(),
            'without_effective_direct_relation' => $directDegrees->filter(fn (int $degree): bool => $degree === 0)->count(),
            'with_at_least_minimum_direct_links' => $directDegrees->filter(fn (int $degree): bool => $degree >= $minimumLinks)->count(),
            'below_minimum_direct_links' => $directDegrees->filter(fn (int $degree): bool => $degree < $minimumLinks)->count(),
        ];
        $this->report['fallback_pressure'] = $fallback;
        $this->report['topics'] = [
            'indexable' => $topics->where('is_indexable', true)->count(),
            'non_indexable' => $topics->where('is_indexable', false)->count(),
            'primary' => $topics->whereNull('parent_id')->count(),
            'secondary' => $topics->whereNotNull('parent_id')->count(),
            'actual_public_membership_distribution' => $this->distribution($topics->map(
                fn (QuestionSeoTopic $topic): int => $this->actualTopicMemberIds($topic, $topics, $publicMembersByTopic)->count(),
            )),
        ];
        $this->report['duplicate_recommendation_sets'] = $duplicateSets;
        $this->report['status'] = match (true) {
            (int) $this->report['issue_counts']['errors'] > 0 => 'failed',
            (int) $this->report['issue_counts']['warnings'] > 0 => 'warning',
            default => 'ok',
        };

        return $this->report;
    }

    /**
     * @param  Collection<int, true>  $publicQuestionIds
     * @param  Collection<string, true>  $publicExternalIds
     */
    protected function hasPublicQuestion(
        QuestionPublicExplanation $explanation,
        Collection $publicQuestionIds,
        Collection $publicExternalIds,
    ): bool {
        if ($explanation->question_id !== null) {
            return $publicQuestionIds->has((int) $explanation->question_id);
        }

        $externalId = (string) $explanation->external_id;

        return $publicExternalIds->has($externalId)
            || $publicExternalIds->has($this->catalog->displayExternalId($externalId));
    }

    /**
     * @param  Collection<int, QuestionPublicExplanation>  $explanations
     * @return Collection<string, int>
     */
    protected function explanationsByExternalId(Collection $explanations): Collection
    {
        $map = collect();

        foreach ($explanations as $explanation) {
            $id = (int) $explanation->getKey();
            $externalId = (string) $explanation->external_id;
            $displayId = $this->catalog->displayExternalId($externalId);
            $map->put($externalId, $id);
            $map->put($displayId, $id);
        }

        return $map;
    }

    /**
     * @param  Collection<int, mixed>  $nodeIds
     * @param  Collection<int, QuestionRelation>  $relations
     * @return Collection<int, Collection<int, int>>
     */
    protected function neighbourMap(Collection $nodeIds, Collection $relations): Collection
    {
        $map = $nodeIds->mapWithKeys(fn (mixed $id): array => [(int) $id => collect()]);

        foreach ($relations as $relation) {
            $left = (int) $relation->left_explanation_id;
            $right = (int) $relation->right_explanation_id;

            if ($left === $right) {
                continue;
            }

            if ($map->has($left)) {
                $map->get($left)->push($right);
            }

            if ($map->has($right)) {
                $map->get($right)->push($left);
            }
        }

        return $map->map(fn (Collection $ids): Collection => $ids->unique()->values());
    }

    /**
     * @param  Collection<int, QuestionPublicExplanation>  $explanations
     * @param  Collection<string, int>  $explanationByExternalId
     * @return Collection<int, Collection<int, int>>
     */
    protected function legacyNeighbourMap(Collection $explanations, Collection $explanationByExternalId): Collection
    {
        return $explanations->mapWithKeys(function (QuestionPublicExplanation $explanation) use ($explanationByExternalId): array {
            $targets = collect($explanation->related_questions ?? [])
                ->map(function (mixed $item) use ($explanationByExternalId): ?int {
                    if (! is_array($item)) {
                        return null;
                    }

                    $externalId = trim((string) ($item['external_id'] ?? $item['id'] ?? ''));

                    return $externalId !== '' && $explanationByExternalId->has($externalId)
                        ? (int) $explanationByExternalId->get($externalId)
                        : null;
                })
                ->filter(fn (mixed $id): bool => is_int($id))
                ->unique()
                ->values();

            return [(int) $explanation->getKey() => $targets];
        });
    }

    /**
     * @param  Collection<int, QuestionRelation>  $relations
     * @param  Collection<int, true>  $publishedExplanationIds
     * @param  Collection<int, true>  $publicExplanationIds
     * @param  Collection<int, object>  $membershipByExplanation
     */
    protected function auditRelationIntegrity(
        Collection $relations,
        Collection $publishedExplanationIds,
        Collection $publicExplanationIds,
        Collection $membershipByExplanation,
        float $automaticThreshold,
    ): void {
        $validSources = [QuestionRelation::SOURCE_EDITORIAL, QuestionRelation::SOURCE_GRAPH];
        $validStatuses = [
            QuestionRelation::STATUS_VERIFIED,
            QuestionRelation::STATUS_AUTOMATIC,
            QuestionRelation::STATUS_CANDIDATE,
            QuestionRelation::STATUS_REJECTED,
        ];
        $publishedStatuses = [QuestionRelation::STATUS_VERIFIED, QuestionRelation::STATUS_AUTOMATIC];

        foreach ($relations as $relation) {
            $left = (int) $relation->left_explanation_id;
            $right = (int) $relation->right_explanation_id;
            $context = [
                'relation_id' => (int) $relation->getKey(),
                'left_explanation_id' => $left,
                'right_explanation_id' => $right,
                'status' => (string) $relation->status,
                'source' => (string) $relation->source,
            ];

            if ($left === $right) {
                $this->issue('errors', 'self_relation', 'Relacja łączy pytanie z nim samym.', $context);
            } elseif ($left > $right) {
                $this->issue('errors', 'non_canonical_relation_pair', 'Para relacji nie jest zapisana w kanonicznej kolejności left < right.', $context);
            }

            if (! in_array($relation->source, $validSources, true)) {
                $this->issue('errors', 'invalid_relation_source', 'Relacja ma nieobsługiwane źródło.', $context);
            }

            if (! in_array($relation->status, $validStatuses, true)) {
                $this->issue('errors', 'invalid_relation_status', 'Relacja ma nieobsługiwany status.', $context);
            }

            if (! in_array($relation->status, $publishedStatuses, true)) {
                continue;
            }

            if (! $publishedExplanationIds->has($left) || ! $publishedExplanationIds->has($right)) {
                $this->issue('errors', 'published_relation_to_unpublished_explanation', 'Publiczna relacja wskazuje nieopublikowane wyjaśnienie.', $context);
            }

            if (! $publicExplanationIds->has($left) || ! $publicExplanationIds->has($right)) {
                $this->issue('errors', 'published_relation_to_non_public_question', 'Publiczna relacja wskazuje pytanie niespełniające kryteriów publicznego katalogu.', $context);
            }

            if ($relation->status !== QuestionRelation::STATUS_AUTOMATIC) {
                continue;
            }

            if ($relation->score === null || (float) $relation->score < $automaticThreshold) {
                $this->issue('errors', 'automatic_relation_below_threshold', 'Automatycznie opublikowana relacja jest poniżej skonfigurowanego progu.', $context + [
                    'score' => $relation->score,
                    'threshold' => $automaticThreshold,
                ]);
            }

            $leftMembership = $membershipByExplanation->get($left);
            $rightMembership = $membershipByExplanation->get($right);
            if (! $leftMembership || ! $rightMembership
                || (int) $leftMembership->question_seo_topic_id !== (int) $rightMembership->question_seo_topic_id) {
                $this->issue('errors', 'automatic_relation_without_shared_topic', 'Automatycznie opublikowana relacja nie łączy pytań w tym samym temacie wtórnym.', $context);
            }
        }
    }

    /**
     * @param  Collection<int, QuestionSeoTopic>  $topics
     * @param  Collection<int, Collection<int, int>>  $publicMembersByTopic
     * @param  Collection<int, object>  $topicRelations
     */
    protected function auditTopicIntegrity(
        Collection $topics,
        Collection $publicMembersByTopic,
        Collection $topicRelations,
        int $indexableMinimum,
    ): void {
        foreach ($topics as $topic) {
            $actualCount = $this->actualTopicMemberIds($topic, $topics, $publicMembersByTopic)->count();
            $context = [
                'topic_id' => (int) $topic->getKey(),
                'key' => (string) $topic->key,
                'slug' => (string) $topic->slug,
                'stored_question_count' => (int) $topic->question_count,
                'actual_public_question_count' => $actualCount,
            ];

            if ((int) $topic->question_count !== $actualCount) {
                $this->issue('warnings', 'topic_question_count_mismatch', 'Zapisany licznik tematu różni się od liczby publicznych członkostw.', $context);
            }

            if ($topic->is_indexable && $actualCount < $indexableMinimum) {
                $this->issue('errors', 'indexable_topic_below_minimum', 'Indeksowalny hub ma mniej publicznych pytań niż skonfigurowane minimum.', $context + [
                    'minimum' => $indexableMinimum,
                ]);
            }

            if (! $topic->is_indexable && $topic->parent_id !== null && $actualCount >= $indexableMinimum) {
                $this->issue('warnings', 'non_indexable_topic_at_or_above_minimum', 'Nieindeksowalny temat osiągnął próg liczebności huba i wymaga decyzji redakcyjnej.', $context + [
                    'minimum' => $indexableMinimum,
                ]);
            }
        }

        foreach ($topicRelations as $relation) {
            if ((int) $relation->source_topic_id === (int) $relation->target_topic_id) {
                $this->issue('errors', 'self_topic_relation', 'Temat sąsiadujący wskazuje sam na siebie.', [
                    'topic_id' => (int) $relation->source_topic_id,
                ]);
            }
        }
    }

    /**
     * Count direct and descendant memberships so aggregate primary-topic counters
     * are compared with the same hierarchy semantics used by the importer.
     *
     * @param  Collection<int, QuestionSeoTopic>  $topics
     * @param  Collection<int, Collection<int, int>>  $publicMembersByTopic
     * @return Collection<int, int>
     */
    protected function actualTopicMemberIds(
        QuestionSeoTopic $topic,
        Collection $topics,
        Collection $publicMembersByTopic,
    ): Collection {
        $topicIds = collect([(int) $topic->getKey()]);
        $pending = [(int) $topic->getKey()];

        while ($pending !== []) {
            $parentId = array_shift($pending);
            $children = $topics
                ->filter(fn (QuestionSeoTopic $candidate): bool => (int) $candidate->parent_id === $parentId)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->reject(fn (int $id): bool => $topicIds->containsStrict($id));

            foreach ($children as $childId) {
                $topicIds->push($childId);
                $pending[] = $childId;
            }
        }

        return $topicIds
            ->flatMap(fn (int $topicId): Collection => $publicMembersByTopic->get($topicId, collect()))
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, mixed>  $explanationIds
     * @param  Collection<int, Collection<int, int>>  $directNeighbours
     * @param  Collection<int, object>  $membershipByExplanation
     * @param  Collection<int, QuestionSeoTopic>  $topicById
     * @param  Collection<int, Collection<int, int>>  $publicMembersByTopic
     * @param  Collection<int, Collection<int, int>>  $adjacentTopicIds
     * @return array<string, mixed>
     */
    protected function fallbackPressure(
        Collection $explanationIds,
        Collection $directNeighbours,
        Collection $membershipByExplanation,
        Collection $topicById,
        Collection $publicMembersByTopic,
        Collection $adjacentTopicIds,
        int $minimumLinks,
    ): array {
        $stages = [
            'direct' => 0,
            'same_topic' => 0,
            'same_primary' => 0,
            'adjacent_topic' => 0,
            'category_fallback_required' => 0,
        ];
        $structuredCounts = collect();
        $samples = [];

        foreach ($explanationIds as $rawId) {
            $id = (int) $rawId;
            $used = $directNeighbours->get($id, collect())->mapWithKeys(fn (int $target): array => [$target => true]);
            $reachedAt = $used->count() >= $minimumLinks ? 'direct' : null;
            $membership = $membershipByExplanation->get($id);
            $topic = $membership ? $topicById->get((int) $membership->question_seo_topic_id) : null;

            if ($reachedAt === null && $topic instanceof QuestionSeoTopic) {
                $this->mergeCandidates($used, $publicMembersByTopic->get((int) $topic->getKey(), collect()), $id);
                $reachedAt = $used->count() >= $minimumLinks ? 'same_topic' : null;
            }

            if ($reachedAt === null && $topic instanceof QuestionSeoTopic && $topic->parent_id !== null) {
                $siblingTopicIds = $topicById
                    ->filter(fn (QuestionSeoTopic $candidate): bool => (int) $candidate->parent_id === (int) $topic->parent_id)
                    ->keys();
                foreach ($siblingTopicIds as $siblingTopicId) {
                    $this->mergeCandidates($used, $publicMembersByTopic->get((int) $siblingTopicId, collect()), $id);
                }
                $reachedAt = $used->count() >= $minimumLinks ? 'same_primary' : null;
            }

            if ($reachedAt === null && $topic instanceof QuestionSeoTopic) {
                foreach ($adjacentTopicIds->get((int) $topic->getKey(), collect()) as $adjacentTopicId) {
                    $this->mergeCandidates($used, $publicMembersByTopic->get((int) $adjacentTopicId, collect()), $id);
                }
                $reachedAt = $used->count() >= $minimumLinks ? 'adjacent_topic' : null;
            }

            $structuredCounts->push($used->count());
            $stage = $reachedAt ?? 'category_fallback_required';
            $stages[$stage]++;

            if ($stage === 'category_fallback_required' && count($samples) < $this->sampleLimit) {
                $samples[] = [
                    'explanation_id' => $id,
                    'structured_link_candidates' => $used->count(),
                    'missing_to_minimum' => max(0, $minimumLinks - $used->count()),
                    'topic_id' => $topic?->getKey(),
                ];
            }
        }

        return [
            'reaches_minimum_at' => $stages,
            'structured_link_candidate_distribution' => $this->distribution($structuredCounts),
            'category_fallback_samples' => $samples,
        ];
    }

    /** @param Collection<int, true> $used */
    protected function mergeCandidates(Collection $used, Collection $candidates, int $currentId): void
    {
        foreach ($candidates as $candidate) {
            $candidateId = (int) $candidate;
            if ($candidateId !== $currentId) {
                $used->put($candidateId, true);
            }
        }
    }

    /**
     * @param  Collection<int, Collection<int, int>>  $neighbours
     * @return array<string, mixed>
     */
    protected function duplicateRecommendationSets(Collection $neighbours): array
    {
        $sets = $neighbours
            ->filter(fn (Collection $ids): bool => $ids->count() >= 3)
            ->map(function (Collection $ids): string {
                $sorted = $ids->sort()->values();

                return $sorted->implode(',');
            })
            ->groupBy(fn (string $signature): string => $signature)
            ->filter(fn (Collection $group): bool => $group->count() >= 2)
            ->sortByDesc(fn (Collection $group): int => $group->count());

        $samples = [];
        foreach ($sets->take($this->sampleLimit) as $signature => $group) {
            $questionIds = $neighbours
                ->filter(function (Collection $ids) use ($signature): bool {
                    return $ids->sort()->values()->implode(',') === $signature;
                })
                ->keys()
                ->map(fn (mixed $id): int => (int) $id)
                ->values();
            $samples[] = [
                'question_explanation_ids' => $questionIds->all(),
                'recommendation_explanation_ids' => array_map('intval', explode(',', (string) $signature)),
            ];
        }

        return [
            'repeated_set_groups' => $sets->count(),
            'questions_in_repeated_sets' => $sets->sum(fn (Collection $group): int => $group->count()),
            'largest_group' => (int) ($sets->map->count()->max() ?? 0),
            'minimum_set_size' => 3,
            'samples' => $samples,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return array<string, int>
     */
    protected function countsBy(Collection $rows, string $field): array
    {
        $counts = $rows
            ->countBy(fn (mixed $row): string => trim((string) data_get($row, $field)) ?: '(empty)')
            ->sortKeys()
            ->all();

        return array_map('intval', $counts);
    }

    /**
     * @param  Collection<int, int>  $values
     * @return array<string, int|float|null>
     */
    protected function distribution(Collection $values): array
    {
        $sorted = $values->map(fn (mixed $value): int => (int) $value)->sort()->values();

        if ($sorted->isEmpty()) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'average' => null,
                'median' => null,
                'p25' => null,
                'p75' => null,
                'p90' => null,
            ];
        }

        return [
            'count' => $sorted->count(),
            'min' => (int) $sorted->first(),
            'max' => (int) $sorted->last(),
            'average' => round((float) $sorted->average(), 3),
            'median' => $this->percentile($sorted, 0.50),
            'p25' => $this->percentile($sorted, 0.25),
            'p75' => $this->percentile($sorted, 0.75),
            'p90' => $this->percentile($sorted, 0.90),
        ];
    }

    protected function percentile(Collection $sorted, float $percentile): int
    {
        $index = (int) floor(($sorted->count() - 1) * $percentile);

        return (int) $sorted->get($index);
    }

    /** @param array<string, mixed> $context */
    protected function issue(string $level, string $type, string $message, array $context = []): void
    {
        $this->report['issue_counts'][$level]++;
        $this->report['issue_type_counts'][$level][$type] =
            (int) ($this->report['issue_type_counts'][$level][$type] ?? 0) + 1;

        if (count($this->report['issues'][$level]) >= $this->sampleLimit) {
            return;
        }

        $this->report['issues'][$level][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
        ];
    }
}
