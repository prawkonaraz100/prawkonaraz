<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use App\Models\QuestionSeoTopicMembership;
use Illuminate\Database\Eloquent\Builder;

class QuestionRelationV2ShadowResolver
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
    ) {}

    /**
     * Resolve the V2 snapshot for a public request without changing the V1
     * response. Both the runtime switch and a topic-scoped shadow rollout are
     * required before any V2 recommendation query is made.
     *
     * @return array<string, mixed>|null
     */
    public function forQuestion(Question $question): ?array
    {
        if (! (bool) config('question_relations.v2_enabled', false)
            || ! (bool) config('question_relations.v2_shadow_enabled', false)) {
            return null;
        }

        $explanation = $this->publishedExplanationForQuestion($question);

        if (! $explanation instanceof QuestionPublicExplanation) {
            return null;
        }

        $context = $this->activeShadowContext($explanation);

        if ($context === null) {
            return null;
        }

        return $this->forRun(
            $explanation,
            $context['run'],
            $context['topic'],
            $context['rollout'],
        );
    }

    /**
     * Resolve the active V2 snapshot for a normal public request assigned to
     * an explicit, deterministic canary cohort. The global V2 kill switch,
     * the dedicated canary switch and a mode=canary rollout are all required.
     *
     * @return array<string, mixed>|null
     */
    public function forCanaryQuestion(Question $question): ?array
    {
        if (! (bool) config('question_relations.v2_enabled', false)
            || ! (bool) config('question_relations.v2_canary_enabled', false)) {
            return null;
        }

        $explanation = $this->publishedExplanationForQuestion($question);

        if (! $explanation instanceof QuestionPublicExplanation) {
            return null;
        }

        $context = $this->activeCanaryContext($explanation);

        if ($context === null) {
            return null;
        }

        // Use the explanation ID used by the ranking run, not the route-facing
        // question identifier. Some legacy records carry a namespaced question
        // ID while their public explanation uses its numeric display ID.
        // This keeps request-time selection identical to the dry-run plan and
        // monitor, both of which are keyed by source_explanation_id.
        $cohort = $this->canaryCohortForQuestion(
            $question,
            $context['topic'],
            $context['rollout'],
            $explanation,
        );

        if (! $cohort['included']) {
            return null;
        }

        $result = $this->forRun(
            $explanation,
            $context['run'],
            $context['topic'],
            $context['rollout'],
            QuestionRelationRollout::MODE_CANARY,
        );

        if ($result !== null) {
            $result['cohort'] = $cohort;
        }

        return $result;
    }

    /**
     * Resolve the active zero-exposure snapshot for an authorized preview.
     * Unlike forQuestion(), this deliberately does not depend on the two
     * runtime switches so operators can inspect a prepared shadow run before
     * enabling it in request traffic.
     *
     * This method must only be called from a protected internal surface or
     * after a controller has validated a temporary signed preview URL.
     *
     * @return array<string, mixed>|null
     */
    public function forInternalPreview(Question $question): ?array
    {
        $explanation = $this->publishedExplanationForQuestion($question);

        if (! $explanation instanceof QuestionPublicExplanation) {
            return null;
        }

        $context = $this->activeShadowContext($explanation)
            ?? $this->activeCanaryContext($explanation);

        if ($context === null) {
            return null;
        }

        return $this->forRun(
            $explanation,
            $context['run'],
            $context['topic'],
            $context['rollout'],
            (string) $context['rollout']->mode,
        );
    }

    /**
     * Resolve a specific immutable run for an explanation. This method is
     * intentionally independent from the runtime switch so that CLI audits
     * can validate a run before shadow mode is enabled on public requests.
     *
     * @return array<string, mixed>|null
     */
    public function forRun(
        QuestionPublicExplanation $explanation,
        QuestionRelationRankingRun $run,
        ?QuestionSeoTopic $topic = null,
        ?QuestionRelationRollout $rollout = null,
        string $mode = QuestionRelationRollout::MODE_SHADOW,
    ): ?array {
        $topic ??= QuestionSeoTopic::query()->find($run->question_seo_topic_id);

        if (! $topic instanceof QuestionSeoTopic
            || (int) $topic->getKey() !== (int) $run->question_seo_topic_id
            || ! $this->isReadableRun($run)
            || ! $this->hasVerifiedPrimaryMembership($explanation, $topic)) {
            return null;
        }

        $recommendations = QuestionRelationRecommendation::query()
            ->where('question_relation_ranking_run_id', $run->getKey())
            ->where('source_explanation_id', $explanation->getKey())
            ->selected()
            ->with([
                'targetExplanation' => fn ($query) => $query->published(),
            ])
            ->orderByRaw('case when rank is null then 1 else 0 end')
            ->orderBy('rank')
            ->orderByDesc('score')
            ->orderBy('id')
            ->get();
        $targets = $recommendations
            ->pluck('targetExplanation')
            ->filter(fn (mixed $target): bool => $target instanceof QuestionPublicExplanation)
            ->values();
        $questionsByExternalId = $this->catalog->canonicalQuestionsByExternalIds(
            $targets->pluck('external_id'),
        );
        $usedExternalIds = collect([
            (string) $explanation->external_id,
            $this->catalog->displayExternalId($explanation->external_id),
        ])->filter()->flip();
        $groups = collect();
        $unresolvedTargetCount = 0;
        $excludedDuplicateCount = 0;

        foreach ($recommendations as $recommendation) {
            $target = $recommendation->targetExplanation;

            if (! $target instanceof QuestionPublicExplanation) {
                $unresolvedTargetCount++;

                continue;
            }

            $targetQuestion = $questionsByExternalId->get((string) $target->external_id);

            if (! $targetQuestion instanceof Question) {
                $unresolvedTargetCount++;

                continue;
            }

            $item = $this->catalog->buildQuestionListItem($targetQuestion, null, true);
            $externalId = (string) ($item['external_id'] ?? '');
            $displayId = (string) ($item['display_external_id'] ?? $externalId);

            if ($externalId === '' || $usedExternalIds->has($externalId) || $usedExternalIds->has($displayId)) {
                $excludedDuplicateCount++;

                continue;
            }

            $usedExternalIds->put($externalId, true);
            $usedExternalIds->put($displayId, true);
            $groupKey = (string) $recommendation->group_key;
            $groupIndex = $groups->search(fn (array $group): bool => $group['key'] === $groupKey);
            $item = [
                ...$item,
                'url' => $item['canonical_url'] ?? $item['url'],
                'relation_source' => 'v2_'.$mode,
                'relation_scope' => (string) $recommendation->scope,
                'relation_score' => $recommendation->score,
                'shadow_rank' => $recommendation->rank,
                'shadow_question_relation_id' => $recommendation->question_relation_id,
            ];

            if ($groupIndex === false) {
                $groups->push([
                    'key' => $groupKey,
                    'label' => $this->groupLabel($groupKey),
                    'items' => collect([$item]),
                ]);

                continue;
            }

            $group = $groups->get($groupIndex);
            $group['items'] = $group['items']->push($item);
            $groups->put($groupIndex, $group);
        }

        return [
            'mode' => $mode,
            'topic' => $topic,
            'rollout' => $rollout,
            'run' => $run,
            'source_explanation_id' => (int) $explanation->getKey(),
            'groups' => $groups->values(),
            'total' => $groups->sum(fn (array $group): int => $group['items']->count()),
            'selected_recommendation_count' => $recommendations->count(),
            'unresolved_target_count' => $unresolvedTargetCount,
            'excluded_duplicate_count' => $excludedDuplicateCount,
        ];
    }

    /** @return array{topic: QuestionSeoTopic, rollout: QuestionRelationRollout, run: QuestionRelationRankingRun}|null */
    protected function activeShadowContext(QuestionPublicExplanation $explanation): ?array
    {
        $membership = QuestionSeoTopicMembership::query()
            ->where('question_public_explanation_id', $explanation->getKey())
            ->verified()
            ->primary()
            ->with(['topic.rollout.activeRankingRun'])
            ->first();
        $topic = $membership?->topic;
        $rollout = $topic?->rollout;
        $run = $rollout?->activeRankingRun;

        if (! $topic instanceof QuestionSeoTopic
            || $topic->status !== QuestionSeoTopic::STATUS_PUBLISHED
            || ! $rollout instanceof QuestionRelationRollout
            || $rollout->mode !== QuestionRelationRollout::MODE_SHADOW
            || (int) $rollout->exposure_percentage !== 0
            || ! $run instanceof QuestionRelationRankingRun
            || (int) $run->question_seo_topic_id !== (int) $topic->getKey()
            || ! $this->isReadableRun($run)) {
            return null;
        }

        return compact('topic', 'rollout', 'run');
    }

    /** @return array{topic: QuestionSeoTopic, rollout: QuestionRelationRollout, run: QuestionRelationRankingRun}|null */
    protected function activeCanaryContext(QuestionPublicExplanation $explanation): ?array
    {
        $membership = QuestionSeoTopicMembership::query()
            ->where('question_public_explanation_id', $explanation->getKey())
            ->verified()
            ->primary()
            ->with(['topic.rollout.activeRankingRun'])
            ->first();
        $topic = $membership?->topic;
        $rollout = $topic?->rollout;
        $run = $rollout?->activeRankingRun;

        if (! $topic instanceof QuestionSeoTopic
            || $topic->status !== QuestionSeoTopic::STATUS_PUBLISHED
            || ! $rollout instanceof QuestionRelationRollout
            || $rollout->mode !== QuestionRelationRollout::MODE_CANARY
            || ! $this->validCanaryExposure((int) $rollout->exposure_percentage)
            || blank($rollout->cohort_seed)
            || ! $run instanceof QuestionRelationRankingRun
            || (int) $run->question_seo_topic_id !== (int) $topic->getKey()
            || ! $this->isReadableRun($run)) {
            return null;
        }

        return compact('topic', 'rollout', 'run');
    }

    /**
     * @return array{algorithm: string, bucket: int, threshold: int, included: bool}
     */
    public function canaryCohortForQuestion(
        Question $question,
        QuestionSeoTopic $topic,
        QuestionRelationRollout $rollout,
        ?QuestionPublicExplanation $explanation = null,
    ): array {
        $externalId = (string) ($explanation?->external_id ?? $question->external_id);

        return self::canaryCohortForExternalId(
            $externalId,
            (int) $topic->getKey(),
            (string) $rollout->cohort_seed,
            (int) $rollout->exposure_percentage,
        );
    }

    /**
     * Public so a dry-run rollout plan can report the exact same stable
     * assignment without touching a live question request.
     *
     * @return array{algorithm: string, bucket: int, threshold: int, included: bool}
     */
    public static function canaryCohortForExternalId(
        string $externalId,
        int $topicId,
        string $cohortSeed,
        int $exposurePercentage,
    ): array {
        $threshold = max(0, min(100, $exposurePercentage)) * 100;
        $hash = hash('sha256', implode('|', [
            'question-relation-v2-canary-v1',
            trim($cohortSeed),
            $topicId,
            trim($externalId),
        ]));
        $bucket = (int) (hexdec(substr($hash, 0, 8)) % 10_000);

        return [
            'algorithm' => 'sha256-mod-10000-v1',
            'bucket' => $bucket,
            'threshold' => $threshold,
            'included' => $bucket < $threshold,
        ];
    }

    protected function publishedExplanationForQuestion(Question $question): ?QuestionPublicExplanation
    {
        $externalId = (string) $question->external_id;
        $displayId = $this->catalog->displayExternalId($externalId);

        return QuestionPublicExplanation::query()
            ->published()
            ->where(function (Builder $query) use ($externalId, $displayId): void {
                $query->where('external_id', $externalId);

                if ($displayId !== $externalId) {
                    $query->orWhere('external_id', $displayId);
                }
            })
            ->first();
    }

    protected function hasVerifiedPrimaryMembership(
        QuestionPublicExplanation $explanation,
        QuestionSeoTopic $topic,
    ): bool {
        return QuestionSeoTopicMembership::query()
            ->where('question_public_explanation_id', $explanation->getKey())
            ->where('question_seo_topic_id', $topic->getKey())
            ->verified()
            ->primary()
            ->exists();
    }

    protected function isReadableRun(QuestionRelationRankingRun $run): bool
    {
        return in_array($run->status, [
            QuestionRelationRankingRun::STATUS_VALIDATED,
            QuestionRelationRankingRun::STATUS_PUBLISHED,
        ], true);
    }

    protected function validCanaryExposure(int $exposurePercentage): bool
    {
        return $exposurePercentage >= 1 && $exposurePercentage <= 100;
    }

    protected function groupLabel(string $key): string
    {
        return match ($key) {
            'context' => 'Więcej z tego tematu',
            'same_rule' => 'Ta sama zasada',
            'dont_confuse' => 'Nie pomyl z',
            'extension' => 'Rozszerz temat',
            default => 'Najbliższe pytania',
        };
    }
}
