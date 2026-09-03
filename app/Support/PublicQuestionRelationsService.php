<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelation;
use App\Models\QuestionSeoTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicQuestionRelationsService
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
        protected QuestionRelationV2ShadowResolver $v2ShadowResolver,
        protected QuestionRelationV2ShadowComparator $v2ShadowComparator,
    ) {}

    /**
     * @param  Collection<int, LicenseCategory>  $categories
     * @param  Collection<int, array<string, mixed>>  $legacyEditorialItems
     * @return array{groups: Collection<int, array{key: string, label: string, items: Collection<int, array<string, mixed>>}>, total: int, topic: QuestionSeoTopic|null, shadow: array<string, mixed>|null, canary?: array<string, mixed>|null}
     */
    public function forQuestion(
        Question $question,
        Collection $categories,
        Collection $legacyEditorialItems = new Collection,
        bool $includeShadow = true,
    ): array {
        if (! (bool) config('question_relations.enabled', true)) {
            $items = $legacyEditorialItems->isNotEmpty()
                ? $legacyEditorialItems
                : $this->catalog
                    ->relatedQuestions($question, $categories, 4)
                    ->map(fn (Question $relatedQuestion): array => $this->catalog->buildQuestionListItem($relatedQuestion, null, true));

            return [
                'groups' => collect([[
                    'key' => 'closest',
                    'label' => $legacyEditorialItems->isNotEmpty() ? 'Najbliższe pytania' : 'Więcej z tej kategorii',
                    'items' => $items->map(fn (array $item): array => [
                        ...$item,
                        'url' => $item['canonical_url'] ?? $item['url'],
                    ])->values(),
                ]]),
                'total' => $items->count(),
                'topic' => null,
                'shadow' => null,
            ];
        }

        $minimum = max(1, (int) config('question_relations.minimum_links', 15));
        $currentExternalId = (string) $question->external_id;
        $currentDisplayId = $this->catalog->displayExternalId($currentExternalId);
        $explanation = QuestionPublicExplanation::query()
            ->published()
            ->where(function ($query) use ($currentExternalId, $currentDisplayId): void {
                $query->where('external_id', $currentExternalId);

                if ($currentDisplayId !== $currentExternalId) {
                    $query->orWhere('external_id', $currentDisplayId);
                }
            })
            ->first();
        $topic = $explanation?->seoTopics()->with('parent')->first();
        $usedExternalIds = collect([$currentExternalId, $currentDisplayId])->flip();
        $groups = collect();

        $legacy = $this->normalizeItems($legacyEditorialItems, $usedExternalIds)
            ->map(fn (array $item): array => [
                ...$item,
                'relation_source' => QuestionRelation::SOURCE_EDITORIAL,
                'relation_type' => 'tematyczne',
            ]);
        $this->appendGroup($groups, 'closest', 'Najbliższe pytania', $legacy);

        if ($explanation instanceof QuestionPublicExplanation) {
            $directGroups = $this->directRelationItems($explanation, $usedExternalIds);

            foreach (['closest', 'same_rule', 'dont_confuse', 'extension'] as $key) {
                if (isset($directGroups[$key])) {
                    $this->appendGroup($groups, $key, $this->groupLabel($key), $directGroups[$key]);
                }
            }
        }

        $currentCount = $this->groupItemCount($groups);

        if ($currentCount < $minimum && $topic instanceof QuestionSeoTopic) {
            $sameTopic = $this->topicItems(
                QuestionPublicExplanation::query()
                    ->published()
                    ->whereHas('seoTopics', fn ($query) => $query->whereKey($topic->getKey())),
                $usedExternalIds,
                $minimum - $currentCount,
                'same_topic',
                $topic,
            );
            $this->appendGroup($groups, 'same_topic', 'Więcej z tego tematu', $sameTopic);
            $currentCount = $this->groupItemCount($groups);

            if ($currentCount < $minimum && $topic->parent_id !== null) {
                $samePrimary = $this->topicItems(
                    QuestionPublicExplanation::query()
                        ->published()
                        ->whereHas('seoTopics', fn ($query) => $query->where('parent_id', $topic->parent_id)),
                    $usedExternalIds,
                    $minimum - $currentCount,
                    'same_primary',
                    $topic->parent,
                );
                $this->appendGroup($groups, 'same_primary', 'Rozszerz temat', $samePrimary);
                $currentCount = $this->groupItemCount($groups);
            }

            if ($currentCount < $minimum) {
                $adjacentTopicIds = $topic->adjacentTopics()
                    ->orderByPivot('display_order')
                    ->pluck('question_seo_topics.id');

                if ($adjacentTopicIds->isNotEmpty()) {
                    $adjacent = $this->topicItems(
                        QuestionPublicExplanation::query()
                            ->published()
                            ->whereHas('seoTopics', fn ($query) => $query->whereIn('question_seo_topics.id', $adjacentTopicIds)),
                        $usedExternalIds,
                        $minimum - $currentCount,
                        'adjacent_topic',
                    );
                    $this->appendGroup($groups, 'adjacent_topic', 'Rozszerz temat', $adjacent);
                    $currentCount = $this->groupItemCount($groups);
                }
            }
        }

        if ($currentCount < $minimum) {
            $fallback = $this->catalog
                ->relatedQuestions($question, $categories, $minimum + $usedExternalIds->count())
                ->map(fn (Question $relatedQuestion): array => $this->catalog->buildQuestionListItem($relatedQuestion, null, true));
            $fallback = $this->normalizeItems($fallback, $usedExternalIds)
                ->take($minimum - $currentCount)
                ->map(fn (array $item): array => [...$item, 'relation_scope' => 'category']);
            $this->appendGroup($groups, 'category', 'Więcej z tej kategorii', $fallback);
        }

        $result = [
            'groups' => $groups->values(),
            'total' => $this->groupItemCount($groups),
            'topic' => $topic,
            'shadow' => null,
        ];
        $shadow = $includeShadow ? $this->v2ShadowResolver->forQuestion($question) : null;

        if ($shadow !== null) {
            $shadowRun = $shadow['run'];
            $shadowTopic = $shadow['topic'];
            $result['shadow'] = [
                'mode' => (string) $shadow['mode'],
                'run_id' => (int) $shadowRun->getKey(),
                'topic_id' => (int) $shadowTopic->getKey(),
                'selected_recommendation_count' => (int) $shadow['selected_recommendation_count'],
                'resolved_recommendation_count' => (int) $shadow['total'],
                'unresolved_target_count' => (int) $shadow['unresolved_target_count'],
                'excluded_duplicate_count' => (int) $shadow['excluded_duplicate_count'],
                'comparison' => $this->v2ShadowComparator->compare($result, $shadow),
            ];
        }

        // Internal V1 ↔ V2 audits pass includeShadow=false and must retain a
        // genuine V1 baseline even after a public canary has been configured.
        $canary = $includeShadow ? $this->v2ShadowResolver->forCanaryQuestion($question) : null;

        if ($canary !== null
            && (int) $canary['total'] >= $minimum
            && (int) $canary['unresolved_target_count'] === 0) {
            return [
                'groups' => $canary['groups'],
                'total' => (int) $canary['total'],
                'topic' => $canary['topic'],
                'shadow' => null,
                'canary' => [
                    'mode' => (string) $canary['mode'],
                    'run_id' => (int) $canary['run']->getKey(),
                    'topic_id' => (int) $canary['topic']->getKey(),
                    'resolved_recommendation_count' => (int) $canary['total'],
                    'selected_recommendation_count' => (int) $canary['selected_recommendation_count'],
                    'cohort' => $canary['cohort'] ?? null,
                ],
            ];
        }

        return $result;
    }

    /**
     * Resolve the active V2 shadow snapshot for an explicitly authorized
     * one-question preview. The caller must validate the temporary signature
     * before calling this method; ordinary public requests always use
     * forQuestion() and retain V1 output.
     *
     * @return array<string, mixed>|null
     */
    public function v2SignedPreviewForQuestion(Question $question): ?array
    {
        return $this->v2ShadowResolver->forInternalPreview($question);
    }

    /**
     * @param  Collection<string, true>  $usedExternalIds
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    protected function directRelationItems(
        QuestionPublicExplanation $explanation,
        Collection $usedExternalIds,
    ): array {
        $relations = QuestionRelation::query()
            ->published()
            ->where(function ($query) use ($explanation): void {
                $query
                    ->where('left_explanation_id', $explanation->getKey())
                    ->orWhere('right_explanation_id', $explanation->getKey());
            })
            ->with(['leftExplanation', 'rightExplanation'])
            ->orderByRaw("case when source = 'editorial' then 0 else 1 end")
            ->orderBy('display_order')
            ->orderByDesc('score')
            ->orderBy('id')
            ->get();
        $targetExplanations = $relations
            ->map(function (QuestionRelation $relation) use ($explanation): ?QuestionPublicExplanation {
                return (int) $relation->left_explanation_id === (int) $explanation->getKey()
                    ? $relation->rightExplanation
                    : $relation->leftExplanation;
            })
            ->filter(fn (mixed $target): bool => $target instanceof QuestionPublicExplanation)
            ->values();
        $questionsByExternalId = $this->catalog->canonicalQuestionsByExternalIds(
            $targetExplanations->pluck('external_id'),
        );
        $groups = [];

        foreach ($relations as $relation) {
            $currentIsLeft = (int) $relation->left_explanation_id === (int) $explanation->getKey();
            $target = $currentIsLeft ? $relation->rightExplanation : $relation->leftExplanation;

            if (! $target instanceof QuestionPublicExplanation) {
                continue;
            }

            $targetQuestion = $questionsByExternalId->get((string) $target->external_id);

            if (! $targetQuestion instanceof Question) {
                continue;
            }

            $item = $this->catalog->buildQuestionListItem($targetQuestion, null, true);
            $externalId = (string) ($item['external_id'] ?? '');
            $displayId = (string) ($item['display_external_id'] ?? $externalId);

            if ($usedExternalIds->has($externalId) || $usedExternalIds->has($displayId)) {
                continue;
            }

            $usedExternalIds->put($externalId, true);
            $usedExternalIds->put($displayId, true);
            $group = $this->relationGroup((string) $relation->relation_type);
            $description = trim((string) ($relation->difference ?: $relation->reason));
            $groups[$group] ??= collect();
            $groups[$group]->push([
                ...$item,
                'url' => $item['canonical_url'],
                'relation_type' => $relation->relation_type,
                'relation_source' => $relation->source,
                'relation_score' => $relation->score,
                'relation_description_plain' => $description,
            ]);
        }

        return $groups;
    }

    /**
     * @param  Builder<QuestionPublicExplanation>  $query
     * @param  Collection<string, true>  $usedExternalIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function topicItems(
        $query,
        Collection $usedExternalIds,
        int $limit,
        string $scope,
        ?QuestionSeoTopic $topic = null,
    ): Collection {
        if ($limit <= 0) {
            return collect();
        }

        $explanations = $query
            ->orderBy('external_id')
            ->limit($limit + $usedExternalIds->count() + 10)
            ->get();
        $questionsByExternalId = $this->catalog->canonicalQuestionsByExternalIds(
            $explanations->pluck('external_id'),
        );
        $items = $explanations
            ->map(function (QuestionPublicExplanation $explanation) use ($scope, $topic, $questionsByExternalId): ?array {
                $question = $questionsByExternalId->get((string) $explanation->external_id);

                if (! $question instanceof Question) {
                    return null;
                }

                $item = $this->catalog->buildQuestionListItem($question, null, true);

                return [
                    ...$item,
                    'url' => $item['canonical_url'],
                    'relation_scope' => $scope,
                    'topic_label' => $topic?->label,
                    'topic_url' => $topic?->is_indexable
                        ? route('public.questions.topics.show', $topic->slug)
                        : null,
                ];
            })
            ->filter();

        return $this->normalizeItems($items, $usedExternalIds)->take($limit)->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<string, true>  $usedExternalIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeItems(Collection $items, Collection $usedExternalIds): Collection
    {
        return $items
            ->filter(function (array $item) use ($usedExternalIds): bool {
                $externalId = (string) ($item['external_id'] ?? '');
                $displayId = (string) ($item['display_external_id'] ?? $externalId);

                if ($externalId === '' || $usedExternalIds->has($externalId) || $usedExternalIds->has($displayId)) {
                    return false;
                }

                $usedExternalIds->put($externalId, true);
                $usedExternalIds->put($displayId, true);

                return true;
            })
            ->map(fn (array $item): array => [
                ...$item,
                'url' => $item['canonical_url'] ?? $item['url'],
            ])
            ->values();
    }

    /** @param  Collection<int, array{key: string, label: string, items: Collection<int, array<string, mixed>>}>  $groups */
    protected function appendGroup(Collection $groups, string $key, string $label, Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $existingIndex = $groups->search(fn (array $group): bool => $group['key'] === $key);

        if ($existingIndex !== false) {
            $group = $groups->get($existingIndex);
            $group['items'] = $group['items']->concat($items)->values();
            $groups->put($existingIndex, $group);

            return;
        }

        $groups->push(compact('key', 'label', 'items'));
    }

    /** @param  Collection<int, array{items: Collection<int, array<string, mixed>>}>  $groups */
    protected function groupItemCount(Collection $groups): int
    {
        return $groups->sum(fn (array $group): int => $group['items']->count());
    }

    protected function relationGroup(string $type): string
    {
        return match ($type) {
            'ta_sama_zasada' => 'same_rule',
            'nie_pomyl_z', 'kontrast' => 'dont_confuse',
            'rozszerzenie' => 'extension',
            default => 'closest',
        };
    }

    protected function groupLabel(string $key): string
    {
        return match ($key) {
            'same_rule' => 'Ta sama zasada',
            'dont_confuse' => 'Nie pomyl z',
            'extension' => 'Rozszerz temat',
            default => 'Najbliższe pytania',
        };
    }
}
