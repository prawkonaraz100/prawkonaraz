<?php

namespace App\Support;

use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuestionRelationShadowRolloutManager
{
    /**
     * Preview or activate a V2 shadow rollout. This manager never permits a
     * public mode; `shadow` has zero exposure and the public Blade view keeps
     * rendering V1.
     *
     * @return array<string, mixed>
     */
    public function configure(string $topicKey, int $runId, bool $write = false): array
    {
        if (! $write) {
            return $this->publicPlan($this->plan($topicKey, $runId, false), 'preview', false, 0);
        }

        return DB::transaction(function () use ($topicKey, $runId): array {
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

            $plan = $this->plan($topicKey, $runId, true);

            if (! (bool) data_get($plan, 'quality_gates.passed', false)) {
                return $this->publicPlan($plan, 'write_blocked', false, 0);
            }

            /** @var QuestionSeoTopic $topic */
            $topic = $plan['_topic'];
            /** @var QuestionRelationRankingRun $run */
            $run = $plan['_run'];
            /** @var QuestionRelationRollout|null $existing */
            $existing = $plan['_rollout'];
            $isCurrent = $existing instanceof QuestionRelationRollout
                && $existing->mode === QuestionRelationRollout::MODE_SHADOW
                && (int) $existing->active_ranking_run_id === (int) $run->getKey()
                && (int) $existing->exposure_percentage === 0;

            if ($isCurrent) {
                return $this->publicPlan($plan, 'write_idempotent', false, 0);
            }

            $rollout = $existing ?? new QuestionRelationRollout([
                'question_seo_topic_id' => $topic->getKey(),
            ]);
            $metadata = is_array($rollout->metadata) ? $rollout->metadata : [];
            $rollout->fill([
                'mode' => QuestionRelationRollout::MODE_SHADOW,
                'active_ranking_run_id' => $run->getKey(),
                'exposure_percentage' => 0,
                'cohort_seed' => 'question-relation-v2-shadow-run-'.$run->getKey(),
                'started_at' => $rollout->started_at ?? now(),
                'ended_at' => null,
                'metadata' => [
                    ...$metadata,
                    'purpose' => 'non_public_v2_shadow_comparison',
                    'configured_by' => 'seo:configure-question-relation-shadow',
                    'public_output' => false,
                ],
            ]);
            $rollout->save();

            return $this->publicPlan($plan, 'write', true, 1, $rollout);
        });
    }

    /** @return array<string, mixed> */
    protected function plan(string $topicKey, int $runId, bool $forWrite): array
    {
        $topics = QuestionSeoTopic::query()
            ->where('key', $topicKey)
            ->get();
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
        $blockedExistingMode = $rollout instanceof QuestionRelationRollout
            && in_array($rollout->mode, [
                QuestionRelationRollout::MODE_CANARY,
                QuestionRelationRollout::MODE_V2,
            ], true);
        $existingShadowCanBeReplaced = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_SHADOW
            && (int) $rollout->exposure_percentage === 0;
        $unsafeExistingShadow = $rollout instanceof QuestionRelationRollout
            && $rollout->mode === QuestionRelationRollout::MODE_SHADOW
            && ! $existingShadowCanBeReplaced;
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
            'unpublished_targets' => $unpublishedTargets,
            'existing_public_rollout_mode' => $blockedExistingMode ? 1 : 0,
            'existing_shadow_not_zero_exposure' => $unsafeExistingShadow ? 1 : 0,
        ];
        $plan = [
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
                'unpublished_targets' => $unpublishedTargets,
            ],
            'rollout' => [
                'existing' => $rollout instanceof QuestionRelationRollout,
                'id' => $rollout?->getKey(),
                'mode' => $rollout?->mode,
                'active_ranking_run_id' => $rollout?->active_ranking_run_id,
                'exposure_percentage' => $rollout?->exposure_percentage,
                'target_mode' => QuestionRelationRollout::MODE_SHADOW,
                'target_exposure_percentage' => 0,
                'replaces_existing_zero_exposure_shadow' => $existingShadowCanBeReplaced
                    && (int) $rollout->active_ranking_run_id !== $runId,
                'global_v2_switch_enabled' => (bool) config('question_relations.v2_enabled', false),
                'runtime_shadow_switch_enabled' => (bool) config('question_relations.v2_shadow_enabled', false),
                'public_output' => false,
            ],
            'quality_gates' => [
                'passed' => collect($blockers)->every(static fn (int $count): bool => $count === 0),
                'minimum_links' => $minimumLinks,
                'blockers' => $blockers,
            ],
            'applied' => false,
            'applied_changes' => [
                'rollouts_created_or_changed' => 0,
            ],
            '_topic' => $topic,
            '_run' => $run,
            '_rollout' => $rollout,
        ];

        return $plan;
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
