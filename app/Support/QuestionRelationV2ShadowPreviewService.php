<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use Illuminate\Support\Collection;

class QuestionRelationV2ShadowPreviewService
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
        protected PublicQuestionExplanationService $explanations,
        protected PublicQuestionRelationsService $publicRelations,
        protected QuestionRelationV2ShadowResolver $shadowResolver,
        protected QuestionRelationV2ShadowComparator $shadowComparator,
    ) {}

    /**
     * Build a serializable V1 and V2 comparison for the protected admin page.
     * The method never updates a rollout, a ranking run, or any public output.
     *
     * @return array<string, mixed>
     */
    public function preview(string $externalId): array
    {
        $startedAt = hrtime(true);
        $externalId = trim($externalId);

        if ($externalId === '') {
            return $this->unavailable(
                'missing_external_id',
                'Podaj identyfikator pytania, aby przygotować porównanie.',
                $startedAt,
            );
        }

        $questions = $this->catalog->questionGroupByExternalOrDisplayId($externalId);
        $question = $this->catalog->findCanonicalQuestionByExternalId($externalId);

        if (! $question instanceof Question || $questions->isEmpty()) {
            return $this->unavailable(
                'question_not_found',
                'Nie znaleziono publicznego pytania dla podanego identyfikatora.',
                $startedAt,
                ['requested_external_id' => $externalId],
            );
        }

        $categories = $this->catalog->categoriesForQuestions($questions);
        $answerExplanation = $this->explanations->publicOrSystemFallbackForQuestionGroup($questions, $question);
        $legacy = $this->catalog->editorialRelatedQuestions(
            is_array($answerExplanation['related_questions'] ?? null)
                ? $answerExplanation['related_questions']
                : [],
            $categories,
        );

        $v1StartedAt = hrtime(true);
        $v1 = $this->publicRelations->forQuestion($question, $categories, $legacy, false);
        $v1Duration = $this->durationMs($v1StartedAt);

        $v2StartedAt = hrtime(true);
        $v2 = $this->shadowResolver->forInternalPreview($question);
        $v2Duration = $this->durationMs($v2StartedAt);
        $sourceItem = $this->catalog->buildQuestionListItem($question, null, true);
        $source = [
            'id' => (int) $question->getKey(),
            'external_id' => (string) $sourceItem['external_id'],
            'display_external_id' => (string) $sourceItem['display_external_id'],
            'prompt_plain' => (string) $sourceItem['prompt_plain'],
            'url' => (string) $sourceItem['canonical_url'],
            'category_code' => $sourceItem['category_code'] ?? null,
        ];

        if ($v2 === null) {
            return [
                'state' => 'not_eligible',
                'message' => 'Pytanie nie należy do aktywnego, zerowego rolloutu shadow albo nie ma czytelnego runu V2.',
                'source' => $source,
                'runtime' => $this->runtimeState(),
                'v1' => $this->resultPayload($v1),
                'v2' => null,
                'comparison' => null,
                'performance' => [
                    'v1_duration_ms' => $v1Duration,
                    'v2_duration_ms' => $v2Duration,
                    'total_duration_ms' => $this->durationMs($startedAt),
                ],
            ];
        }

        return [
            'state' => 'ready',
            'message' => 'Porównanie zostało wyliczone wyłącznie dla panelu administratora.',
            'source' => $source,
            'runtime' => $this->runtimeState(),
            'v1' => $this->resultPayload($v1),
            'v2' => [
                'mode' => (string) $v2['mode'],
                'total' => (int) $v2['total'],
                'selected_recommendation_count' => (int) $v2['selected_recommendation_count'],
                'unresolved_target_count' => (int) $v2['unresolved_target_count'],
                'excluded_duplicate_count' => (int) $v2['excluded_duplicate_count'],
                'topic' => $this->topicPayload($v2['topic'] ?? null),
                'run' => $this->runPayload($v2['run'] ?? null),
                'rollout' => $this->rolloutPayload($v2['rollout'] ?? null),
                'groups' => $this->groupsPayload($v2['groups'] ?? collect()),
            ],
            'comparison' => $this->shadowComparator->compare($v1, $v2),
            'performance' => [
                'v1_duration_ms' => $v1Duration,
                'v2_duration_ms' => $v2Duration,
                'total_duration_ms' => $this->durationMs($startedAt),
            ],
        ];
    }

    public function defaultExternalId(): ?string
    {
        $rollouts = QuestionRelationRollout::query()
            ->where('mode', QuestionRelationRollout::MODE_SHADOW)
            ->where('exposure_percentage', 0)
            ->with('activeRankingRun')
            ->orderBy('id')
            ->get();

        foreach ($rollouts as $rollout) {
            $run = $rollout->activeRankingRun;

            if (! $run instanceof QuestionRelationRankingRun
                || ! in_array($run->status, [
                    QuestionRelationRankingRun::STATUS_VALIDATED,
                    QuestionRelationRankingRun::STATUS_PUBLISHED,
                ], true)) {
                continue;
            }

            $sourceExplanationId = QuestionRelationRecommendation::query()
                ->where('question_relation_ranking_run_id', $run->getKey())
                ->selected()
                ->orderBy('source_explanation_id')
                ->value('source_explanation_id');

            if ($sourceExplanationId === null) {
                continue;
            }

            $externalId = QuestionPublicExplanation::query()
                ->whereKey($sourceExplanationId)
                ->value('external_id');

            if (filled($externalId)) {
                return (string) $externalId;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected function resultPayload(array $result): array
    {
        return [
            'total' => (int) ($result['total'] ?? 0),
            'topic' => $this->topicPayload($result['topic'] ?? null),
            'groups' => $this->groupsPayload($result['groups'] ?? collect()),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    protected function groupsPayload(Collection $groups): array
    {
        return $groups
            ->map(function (array $group): array {
                $items = $group['items'] instanceof Collection
                    ? $group['items']
                    : collect($group['items'] ?? []);

                return [
                    'key' => (string) ($group['key'] ?? ''),
                    'label' => (string) ($group['label'] ?? 'Powiązane pytania'),
                    'items' => $items
                        ->filter(fn (mixed $item): bool => is_array($item))
                        ->map(fn (array $item): array => [
                            'external_id' => (string) ($item['external_id'] ?? ''),
                            'display_external_id' => (string) ($item['display_external_id'] ?? $item['external_id'] ?? ''),
                            'prompt_plain' => (string) ($item['prompt_plain'] ?? ''),
                            'url' => (string) ($item['canonical_url'] ?? $item['url'] ?? ''),
                            'category_code' => $item['category_code'] ?? null,
                            'relation_source' => $item['relation_source'] ?? null,
                            'relation_type' => $item['relation_type'] ?? null,
                            'relation_scope' => $item['relation_scope'] ?? null,
                            'relation_score' => isset($item['relation_score'])
                                ? (float) $item['relation_score']
                                : null,
                            'shadow_rank' => isset($item['shadow_rank'])
                                ? (int) $item['shadow_rank']
                                : null,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    protected function runtimeState(): array
    {
        return [
            'global_v2_enabled' => (bool) config('question_relations.v2_enabled', false),
            'runtime_shadow_enabled' => (bool) config('question_relations.v2_shadow_enabled', false),
            'public_output' => false,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function topicPayload(mixed $topic): ?array
    {
        if (! $topic instanceof QuestionSeoTopic) {
            return null;
        }

        return [
            'id' => (int) $topic->getKey(),
            'key' => (string) $topic->key,
            'label' => (string) $topic->label,
            'status' => (string) $topic->status,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function runPayload(mixed $run): ?array
    {
        if (! $run instanceof QuestionRelationRankingRun) {
            return null;
        }

        return [
            'id' => (int) $run->getKey(),
            'status' => (string) $run->status,
            'generator_version' => (string) $run->generator_version,
            'validated_at' => $run->validated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function rolloutPayload(mixed $rollout): ?array
    {
        if (! $rollout instanceof QuestionRelationRollout) {
            return null;
        }

        return [
            'id' => (int) $rollout->getKey(),
            'mode' => (string) $rollout->mode,
            'exposure_percentage' => (int) $rollout->exposure_percentage,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function unavailable(
        string $state,
        string $message,
        int $startedAt,
        array $context = [],
    ): array {
        return [
            'state' => $state,
            'message' => $message,
            'context' => $context,
            'runtime' => $this->runtimeState(),
            'v1' => null,
            'v2' => null,
            'comparison' => null,
            'performance' => [
                'total_duration_ms' => $this->durationMs($startedAt),
            ],
        ];
    }

    protected function durationMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
