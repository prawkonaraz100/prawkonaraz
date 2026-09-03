<?php

namespace App\Support;

use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use App\Models\QuestionSeoTopicMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuestionRelationCanaryRolloutManager
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
    ) {}

    /**
     * Preview or explicitly configure a public V2 canary. A write requires
     * both runtime switches and a separate --confirm-public-canary intent.
     * There is no implicit conversion of an existing public rollout.
     *
     * @return array<string, mixed>
     */
    public function configure(
        string $topicKey,
        int $runId,
        int $exposurePercentage,
        string $cohortSeed,
        bool $write = false,
        bool $confirmed = false,
    ): array {
        $cohortSeed = trim($cohortSeed);

        if (! $write) {
            return $this->publicPlan(
                $this->plan($topicKey, $runId, $exposurePercentage, $cohortSeed),
                'preview',
                false,
                0,
            );
        }

        return DB::transaction(function () use ($topicKey, $runId, $exposurePercentage, $cohortSeed, $confirmed): array {
            $topicIds = QuestionSeoTopic::query()
                ->where('key', $topicKey)
                ->lockForUpdate()
                ->pluck('id');

            if ($topicIds->count() === 1) {
                QuestionRelationRollout::query()
                    ->where('question_seo_topic_id', $topicIds->first())
                    ->lockForUpdate()
                    ->first();
            }

            $plan = $this->plan($topicKey, $runId, $exposurePercentage, $cohortSeed, true, $confirmed);

            if (! (bool) data_get($plan, 'quality_gates.passed', false)
                || ! (bool) data_get($plan, 'write_gates.passed', false)) {
                return $this->publicPlan($plan, 'write_blocked', false, 0);
            }

            /** @var QuestionSeoTopic $topic */
            $topic = $plan['_topic'];
            /** @var QuestionRelationRankingRun $run */
            $run = $plan['_run'];
            /** @var QuestionRelationRollout|null $existing */
            $existing = $plan['_rollout'];
            $isCurrent = $existing instanceof QuestionRelationRollout
                && $existing->mode === QuestionRelationRollout::MODE_CANARY
                && (int) $existing->active_ranking_run_id === (int) $run->getKey()
                && (int) $existing->exposure_percentage === $exposurePercentage
                && hash_equals((string) $existing->cohort_seed, $cohortSeed);

            if ($isCurrent) {
                return $this->publicPlan($plan, 'write_idempotent', false, 0);
            }

            $rollout = $existing ?? new QuestionRelationRollout([
                'question_seo_topic_id' => $topic->getKey(),
            ]);
            $metadata = is_array($rollout->metadata) ? $rollout->metadata : [];
            $rollout->fill([
                'mode' => QuestionRelationRollout::MODE_CANARY,
                'active_ranking_run_id' => $run->getKey(),
                'exposure_percentage' => $exposurePercentage,
                'cohort_seed' => $cohortSeed,
                'started_at' => now(),
                'ended_at' => null,
                'metadata' => [
                    ...$metadata,
                    'purpose' => 'controlled_public_v2_canary',
                    'configured_by' => 'seo:configure-question-relation-canary',
                    'public_output' => true,
                    'cohort_algorithm' => 'sha256-mod-10000-v1',
                ],
            ]);
            $rollout->save();

            return $this->publicPlan($plan, 'write', true, 1, $rollout);
        });
    }

    /**
     * Promote one existing, unchanged V2 canary to the full source-question
     * cohort of its topic. This is intentionally separate from configure():
     * it only permits an increasing transition to 100% for the same run and
     * cohort seed, and requires the caller to assert the current exposure.
     *
     * @return array<string, mixed>
     */
    public function promoteToFullTopic(
        string $topicKey,
        int $runId,
        int $fromExposurePercentage,
        string $cohortSeed,
        bool $write = false,
        bool $confirmed = false,
    ): array {
        $cohortSeed = trim($cohortSeed);

        if (! $write) {
            return $this->publicPlan(
                $this->plan(
                    $topicKey,
                    $runId,
                    100,
                    $cohortSeed,
                    false,
                    false,
                    true,
                    $fromExposurePercentage,
                ),
                'promotion_preview',
                false,
                0,
            );
        }

        return DB::transaction(function () use ($topicKey, $runId, $fromExposurePercentage, $cohortSeed, $confirmed): array {
            $topicIds = QuestionSeoTopic::query()
                ->where('key', $topicKey)
                ->lockForUpdate()
                ->pluck('id');

            if ($topicIds->count() === 1) {
                QuestionRelationRollout::query()
                    ->where('question_seo_topic_id', $topicIds->first())
                    ->lockForUpdate()
                    ->first();
            }

            $plan = $this->plan(
                $topicKey,
                $runId,
                100,
                $cohortSeed,
                true,
                $confirmed,
                true,
                $fromExposurePercentage,
            );

            if (! (bool) data_get($plan, 'quality_gates.passed', false)
                || ! (bool) data_get($plan, 'write_gates.passed', false)) {
                return $this->publicPlan($plan, 'promotion_write_blocked', false, 0);
            }

            /** @var QuestionRelationRankingRun $run */
            $run = $plan['_run'];
            /** @var QuestionRelationRollout $rollout */
            $rollout = $plan['_rollout'];
            $isCurrent = $rollout->mode === QuestionRelationRollout::MODE_CANARY
                && (int) $rollout->active_ranking_run_id === (int) $run->getKey()
                && (int) $rollout->exposure_percentage === 100
                && hash_equals((string) $rollout->cohort_seed, $cohortSeed);

            if ($isCurrent) {
                return $this->publicPlan($plan, 'promotion_write_idempotent', false, 0);
            }

            $previousExposure = (int) $rollout->exposure_percentage;
            $promotedAt = now();
            $metadata = is_array($rollout->metadata) ? $rollout->metadata : [];
            $history = is_array(data_get($metadata, 'exposure_promotion_history'))
                ? data_get($metadata, 'exposure_promotion_history')
                : [];
            $history[] = [
                'from_exposure_percentage' => $previousExposure,
                'to_exposure_percentage' => 100,
                'promoted_at' => $promotedAt->toIso8601String(),
                'configured_by' => 'seo:promote-question-relation-canary',
            ];

            $rollout->fill([
                'mode' => QuestionRelationRollout::MODE_CANARY,
                'active_ranking_run_id' => $run->getKey(),
                'exposure_percentage' => 100,
                'cohort_seed' => $cohortSeed,
                'started_at' => $rollout->started_at ?? $promotedAt,
                'ended_at' => null,
                'metadata' => [
                    ...$metadata,
                    'purpose' => 'full_topic_public_v2_rollout',
                    'configured_by' => 'seo:promote-question-relation-canary',
                    'public_output' => true,
                    'cohort_algorithm' => 'sha256-mod-10000-v1',
                    'promoted_at' => $promotedAt->toIso8601String(),
                    'from_exposure_percentage' => $previousExposure,
                    'to_exposure_percentage' => 100,
                    'exposure_promotion_history' => array_slice($history, -10),
                ],
            ]);
            $rollout->save();

            return $this->publicPlan($plan, 'promotion_write', true, 1, $rollout);
        });
    }

    /** @return array<string, mixed> */
    protected function plan(
        string $topicKey,
        int $runId,
        int $exposurePercentage,
        string $cohortSeed,
        bool $forWrite = false,
        bool $confirmed = false,
        bool $fullTopicPromotion = false,
        ?int $expectedCurrentExposurePercentage = null,
    ): array {
        $topics = QuestionSeoTopic::query()->where('key', $topicKey)->get();
        $topic = $topics->count() === 1 ? $topics->first() : null;
        $run = $topic instanceof QuestionSeoTopic
            ? QuestionRelationRankingRun::query()->find($runId)
            : null;
        $rollout = $topic instanceof QuestionSeoTopic
            ? QuestionRelationRollout::query()
                ->where('question_seo_topic_id', $topic->getKey())
                ->first()
            : null;
        $minimumLinks = max(1, (int) config('question_relations.minimum_links', 15));
        $recommendationCounts = $run instanceof QuestionRelationRankingRun
            ? QuestionRelationRecommendation::query()
                ->where('question_relation_ranking_run_id', $run->getKey())
                ->selected()
                ->select('source_explanation_id', DB::raw('count(*) as aggregate'))
                ->groupBy('source_explanation_id')
                ->pluck('aggregate', 'source_explanation_id')
            : collect();
        $unpublishedTargets = $run instanceof QuestionRelationRankingRun
            ? QuestionRelationRecommendation::query()
                ->where('question_relation_ranking_run_id', $run->getKey())
                ->selected()
                ->whereDoesntHave(
                    'targetExplanation',
                    fn (Builder $query): Builder => $query->published(),
                )
                ->count()
            : 0;
        $sourceExternalIds = $recommendationCounts->isNotEmpty()
            ? QuestionPublicExplanation::query()
                ->published()
                ->whereIn('id', $recommendationCounts->keys())
                ->orderBy('external_id')
                ->pluck('external_id')
            : collect();
        $unpublishedSources = max(0, $recommendationCounts->count() - $sourceExternalIds->count());
        $sourceQuestions = $sourceExternalIds->isNotEmpty()
            ? $this->catalog->canonicalQuestionsByExternalIds($sourceExternalIds)
            : collect();
        $unresolvableSourceQuestions = max(0, $sourceExternalIds->count() - $sourceQuestions->count());
        $eligibleSourceMemberships = $topic instanceof QuestionSeoTopic && $recommendationCounts->isNotEmpty()
            ? QuestionSeoTopicMembership::query()
                ->where('question_seo_topic_id', $topic->getKey())
                ->whereIn('question_public_explanation_id', $recommendationCounts->keys())
                ->verified()
                ->primary()
                ->distinct()
                ->count('question_public_explanation_id')
            : 0;
        $sourcesWithoutPrimaryMembership = max(0, $sourceExternalIds->count() - $eligibleSourceMemberships);
        $cohort = $sourceExternalIds
            ->map(function (string $externalId) use ($topic, $cohortSeed, $exposurePercentage): array {
                return [
                    'external_id' => $externalId,
                    ...QuestionRelationV2ShadowResolver::canaryCohortForExternalId(
                        $externalId,
                        (int) $topic?->getKey(),
                        $cohortSeed,
                        $exposurePercentage,
                    ),
                ];
            })
            ->values();
        $isCurrentCanary = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_CANARY
            && (int) $rollout->active_ranking_run_id === $runId
            && (int) $rollout->exposure_percentage === $exposurePercentage
            && hash_equals((string) $rollout->cohort_seed, $cohortSeed);
        $sameRunAndSeedCanary = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_CANARY
            && (int) $rollout->active_ranking_run_id === $runId
            && hash_equals((string) $rollout->cohort_seed, $cohortSeed);
        $isCanaryExposureIncrease = $sameRunAndSeedCanary
            && (int) $rollout->exposure_percentage < $exposurePercentage;
        $existingShadowCanBeReplaced = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_SHADOW
            && (int) $rollout->exposure_percentage === 0;
        $unsafeExistingShadow = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_SHADOW
            && ! $existingShadowCanBeReplaced;
        $existingDifferentCanary = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_CANARY
            && ! $isCurrentCanary
            && ! ($fullTopicPromotion && $isCanaryExposureIncrease);
        $existingV2 = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_V2;
        $validExposure = $exposurePercentage >= 1 && $exposurePercentage <= 100;
        $trimmedSeed = trim($cohortSeed);
        $blockers = [
            'topic_not_unique' => $topics->count() === 1 ? 0 : 1,
            'topic_not_published' => $topic instanceof QuestionSeoTopic && $topic->status === QuestionSeoTopic::STATUS_PUBLISHED ? 0 : 1,
            'run_not_found' => $run instanceof QuestionRelationRankingRun ? 0 : 1,
            'run_wrong_topic' => $run instanceof QuestionRelationRankingRun
                && $topic instanceof QuestionSeoTopic
                && (int) $run->question_seo_topic_id === (int) $topic->getKey() ? 0 : 1,
            'run_not_validated' => $run instanceof QuestionRelationRankingRun
                && in_array($run->status, [
                    QuestionRelationRankingRun::STATUS_VALIDATED,
                    QuestionRelationRankingRun::STATUS_PUBLISHED,
                ], true) ? 0 : 1,
            'no_selected_recommendations' => $recommendationCounts->isNotEmpty() ? 0 : 1,
            'minimum_links_not_met' => $recommendationCounts->isNotEmpty()
                && (int) $recommendationCounts->min() >= $minimumLinks ? 0 : 1,
            'unpublished_sources' => $unpublishedSources,
            'source_questions_not_resolvable' => $unresolvableSourceQuestions,
            'sources_without_verified_primary_membership' => $sourcesWithoutPrimaryMembership,
            'unpublished_targets' => $unpublishedTargets,
            'invalid_exposure_percentage' => $validExposure ? 0 : 1,
            'cohort_seed_missing' => $trimmedSeed !== '' ? 0 : 1,
            'cohort_seed_too_long' => mb_strlen($trimmedSeed) <= 120 ? 0 : 1,
            'canary_cohort_empty' => $cohort->contains('included', true) ? 0 : 1,
            'existing_full_v2_rollout' => $existingV2 ? 1 : 0,
            'existing_different_canary' => $existingDifferentCanary ? 1 : 0,
            'existing_shadow_not_zero_exposure' => $unsafeExistingShadow ? 1 : 0,
            'full_topic_promotion_requires_existing_canary' => $fullTopicPromotion
                && ! $isCurrentCanary
                && ! $sameRunAndSeedCanary ? 1 : 0,
            'full_topic_promotion_must_increase_to_100' => $fullTopicPromotion
                && ! $isCurrentCanary
                && (! $isCanaryExposureIncrease || $exposurePercentage !== 100) ? 1 : 0,
            'full_topic_promotion_current_exposure_mismatch' => $fullTopicPromotion
                && ! $isCurrentCanary
                && ($expectedCurrentExposurePercentage === null
                    || (int) ($rollout?->exposure_percentage ?? -1) !== $expectedCurrentExposurePercentage) ? 1 : 0,
        ];
        $writeBlockers = [
            'public_canary_confirmation_missing' => $confirmed ? 0 : 1,
            'full_topic_rollout_confirmation_missing' => $fullTopicPromotion && ! $confirmed ? 1 : 0,
            'global_v2_switch_disabled' => (bool) config('question_relations.v2_enabled', false) ? 0 : 1,
            'runtime_canary_switch_disabled' => (bool) config('question_relations.v2_canary_enabled', false) ? 0 : 1,
        ];

        return [
            'schema_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'mode' => $forWrite ? 'write_preview' : 'preview',
            'topic' => [
                'key' => $topicKey,
                'id' => $topic?->getKey(),
                'status' => $topic?->status,
            ],
            'run' => [
                'id' => $run?->getKey(),
                'status' => $run?->status,
                'question_seo_topic_id' => $run?->question_seo_topic_id,
            ],
            'recommendations' => [
                'sources' => $recommendationCounts->count(),
                'selected' => $recommendationCounts->sum(),
                'minimum_per_source' => $recommendationCounts->isNotEmpty() ? (int) $recommendationCounts->min() : 0,
                'maximum_per_source' => $recommendationCounts->isNotEmpty() ? (int) $recommendationCounts->max() : 0,
                'unpublished_sources' => $unpublishedSources,
                'source_questions_not_resolvable' => $unresolvableSourceQuestions,
                'sources_without_verified_primary_membership' => $sourcesWithoutPrimaryMembership,
                'unpublished_targets' => $unpublishedTargets,
            ],
            'cohort' => [
                'algorithm' => 'sha256-mod-10000-v1',
                'seed' => $trimmedSeed,
                'exposure_percentage' => $exposurePercentage,
                'eligible_sources' => $sourceExternalIds->count(),
                'included_sources' => $cohort->where('included', true)->count(),
                'excluded_sources' => $cohort->where('included', false)->count(),
                'included_external_ids' => $cohort
                    ->where('included', true)
                    ->pluck('external_id')
                    ->values()
                    ->all(),
            ],
            'rollout' => [
                'existing' => $rollout instanceof QuestionRelationRollout,
                'id' => $rollout?->getKey(),
                'mode' => $rollout?->mode,
                'active_ranking_run_id' => $rollout?->active_ranking_run_id,
                'exposure_percentage' => $rollout?->exposure_percentage,
                'target_mode' => QuestionRelationRollout::MODE_CANARY,
                'target_exposure_percentage' => $exposurePercentage,
                'replaces_existing_zero_exposure_shadow' => $existingShadowCanBeReplaced,
                'is_current_canary' => $isCurrentCanary,
                'full_topic_promotion' => $fullTopicPromotion,
                'expected_current_exposure_percentage' => $expectedCurrentExposurePercentage,
                'canary_exposure_increase' => $isCanaryExposureIncrease,
                'public_output' => true,
            ],
            'quality_gates' => [
                'passed' => collect($blockers)->every(static fn (int $count): bool => $count === 0),
                'minimum_links' => $minimumLinks,
                'blockers' => $blockers,
            ],
            'write_gates' => [
                'passed' => collect($writeBlockers)->every(static fn (int $count): bool => $count === 0),
                'blockers' => $writeBlockers,
            ],
            'applied' => false,
            'applied_changes' => [
                'rollouts_created_or_changed' => 0,
            ],
            '_topic' => $topic,
            '_run' => $run,
            '_rollout' => $rollout,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    protected function publicPlan(
        array $plan,
        string $mode,
        bool $applied,
        int $changed,
        ?QuestionRelationRollout $rollout = null,
    ): array {
        if ($rollout instanceof QuestionRelationRollout) {
            $plan['rollout'] = [
                ...$plan['rollout'],
                'existing' => true,
                'id' => $rollout->getKey(),
                'mode' => $rollout->mode,
                'active_ranking_run_id' => $rollout->active_ranking_run_id,
                'exposure_percentage' => $rollout->exposure_percentage,
            ];
        }

        unset($plan['_topic'], $plan['_run'], $plan['_rollout']);

        $plan['mode'] = $mode;
        $plan['applied'] = $applied;
        $plan['applied_changes'] = [
            'rollouts_created_or_changed' => $changed,
        ];

        return $plan;
    }
}
