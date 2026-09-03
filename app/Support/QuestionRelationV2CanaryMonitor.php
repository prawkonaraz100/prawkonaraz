<?php

namespace App\Support;

use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;

class QuestionRelationV2CanaryMonitor
{
    public function __construct(
        protected QuestionRelationV2ShadowAuditor $auditor,
    ) {}

    /**
     * Inspect active V2 canaries without changing rollout data. The underlying
     * run is audited with the same source/target integrity checks as shadow;
     * the shadow-only rollout warning is deliberately excluded for canaries.
     *
     * @return array<string, mixed>
     */
    public function inspect(?string $topicKey = null, int $sampleLimit = 10): array
    {
        $startedAt = hrtime(true);
        $topicKey = filled($topicKey) ? trim((string) $topicKey) : null;
        $sampleLimit = max(1, $sampleLimit);
        $warnings = [];
        $errors = [];
        $globalV2Enabled = (bool) config('question_relations.v2_enabled', false);
        $runtimeCanaryEnabled = (bool) config('question_relations.v2_canary_enabled', false);

        if (! $globalV2Enabled) {
            $warnings[] = [
                'type' => 'global_v2_switch_disabled',
                'message' => 'Globalna flaga V2 jest wyłączona; ruch publiczny nie otrzymuje canary.',
            ];
        }

        if (! $runtimeCanaryEnabled) {
            $warnings[] = [
                'type' => 'runtime_canary_switch_disabled',
                'message' => 'Flaga runtime canary jest wyłączona; ruch publiczny nie otrzymuje V2.',
            ];
        }

        $rollouts = QuestionRelationRollout::query()
            ->where('mode', QuestionRelationRollout::MODE_CANARY)
            ->with(['topic', 'activeRankingRun'])
            ->when($topicKey !== null, function ($query) use ($topicKey): void {
                $query->whereHas('topic', fn ($topicQuery) => $topicQuery->where('key', $topicKey));
            })
            ->orderBy('question_seo_topic_id')
            ->get();

        if ($rollouts->isEmpty()) {
            $warnings[] = [
                'type' => 'no_active_canary_rollouts',
                'message' => $topicKey === null
                    ? 'Nie znaleziono aktywnych rolloutów mode=canary.'
                    : 'Wskazany topic nie ma aktywnego rolloutu mode=canary.',
            ];
        }

        $topics = [];

        foreach ($rollouts as $rollout) {
            $topic = $rollout->topic;
            $run = $rollout->activeRankingRun;

            if ((int) $rollout->exposure_percentage < 1 || (int) $rollout->exposure_percentage > 100) {
                $errors[] = [
                    'type' => 'canary_exposure_out_of_range',
                    'message' => 'Rollout canary ma ekspozycję poza dozwolonym zakresem 1–100%.',
                    'context' => [
                        'rollout_id' => (int) $rollout->getKey(),
                        'exposure_percentage' => (int) $rollout->exposure_percentage,
                    ],
                ];

                continue;
            }

            if (blank($rollout->cohort_seed)) {
                $errors[] = [
                    'type' => 'canary_cohort_seed_missing',
                    'message' => 'Rollout canary nie ma seeda deterministycznej kohorty.',
                    'context' => [
                        'rollout_id' => (int) $rollout->getKey(),
                    ],
                ];

                continue;
            }

            if (! $topic instanceof QuestionSeoTopic || ! $run instanceof QuestionRelationRankingRun) {
                $errors[] = [
                    'type' => 'rollout_context_not_resolvable',
                    'message' => 'Rollout canary nie ma czytelnego topicu lub aktywnego runu.',
                    'context' => [
                        'rollout_id' => (int) $rollout->getKey(),
                        'topic_id' => $rollout->question_seo_topic_id,
                        'run_id' => $rollout->active_ranking_run_id,
                    ],
                ];

                continue;
            }

            $auditStartedAt = hrtime(true);
            $audit = $this->auditor->audit($topic->key, (int) $run->getKey(), $sampleLimit);
            $duration = $this->durationMs($auditStartedAt);
            $auditErrors = (int) data_get($audit, 'issue_counts.errors', 0);
            $auditWarnings = collect(data_get($audit, 'issues.warnings', []))
                ->reject(fn (array $issue): bool => ($issue['type'] ?? null) === 'shadow_rollout_not_active')
                ->values();

            if ($auditErrors > 0) {
                $errors[] = [
                    'type' => 'canary_audit_failed',
                    'message' => 'Audyt integralności runu V2 dla canary wykrył błędy.',
                    'context' => [
                        'topic_key' => $topic->key,
                        'run_id' => (int) $run->getKey(),
                        'errors' => $auditErrors,
                    ],
                ];
            }

            foreach ($auditWarnings as $issue) {
                $warnings[] = [
                    'type' => 'canary_audit_warning',
                    'message' => 'Audyt integralności runu V2 zwrócił ostrzeżenie.',
                    'context' => [
                        'topic_key' => $topic->key,
                        'run_id' => (int) $run->getKey(),
                        'audit_issue' => $issue,
                    ],
                ];
            }

            $cohort = $this->cohort($run, $topic, $rollout);

            if ($cohort['included_sources'] === 0) {
                $errors[] = [
                    'type' => 'canary_cohort_empty',
                    'message' => 'Aktywny canary nie przydzielił żadnego publicznego źródła.',
                    'context' => [
                        'topic_key' => $topic->key,
                        'run_id' => (int) $run->getKey(),
                        'exposure_percentage' => (int) $rollout->exposure_percentage,
                    ],
                ];
            }

            $topics[] = [
                'topic' => [
                    'id' => (int) $topic->getKey(),
                    'key' => (string) $topic->key,
                    'label' => (string) $topic->label,
                    'status' => (string) $topic->status,
                ],
                'rollout' => [
                    'id' => (int) $rollout->getKey(),
                    'mode' => (string) $rollout->mode,
                    'exposure_percentage' => (int) $rollout->exposure_percentage,
                    'cohort_seed' => (string) $rollout->cohort_seed,
                ],
                'run' => [
                    'id' => (int) $run->getKey(),
                    'status' => (string) $run->status,
                    'generator_version' => (string) $run->generator_version,
                ],
                'cohort' => $cohort,
                'audit' => [
                    'status' => $auditErrors > 0
                        ? 'failed'
                        : ($auditWarnings->isNotEmpty() ? 'warning' : 'ok'),
                    'issue_counts' => [
                        'errors' => $auditErrors,
                        'warnings' => $auditWarnings->count(),
                    ],
                    'recommendations' => [
                        'selected' => (int) data_get($audit, 'recommendations.selected', 0),
                        'sources' => (int) data_get($audit, 'recommendations.sources', 0),
                        'minimum_per_source' => (int) data_get($audit, 'recommendations.minimum_per_source', 0),
                        'maximum_per_source' => (int) data_get($audit, 'recommendations.maximum_per_source', 0),
                        'unresolved_sources' => (int) data_get($audit, 'recommendations.unresolved_sources', 0),
                        'unresolved_targets' => (int) data_get($audit, 'recommendations.unresolved_targets', 0),
                    ],
                    'comparison' => [
                        'sources_compared' => (int) data_get($audit, 'comparison.sources_compared', 0),
                        'v1_links_total' => (int) data_get($audit, 'comparison.v1_links_total', 0),
                        'v2_links_total' => (int) data_get($audit, 'comparison.v2_links_total', 0),
                        'shared_links_total' => (int) data_get($audit, 'comparison.shared_links_total', 0),
                        'v1_only_links_total' => (int) data_get($audit, 'comparison.v1_only_links_total', 0),
                        'v2_only_links_total' => (int) data_get($audit, 'comparison.v2_only_links_total', 0),
                        'same_rank_total' => (int) data_get($audit, 'comparison.same_rank_total', 0),
                    ],
                    'duration_ms' => $duration,
                    'samples' => data_get($audit, 'comparison.samples', []),
                    'issues' => [
                        'errors' => data_get($audit, 'issues.errors', []),
                        'warnings' => $auditWarnings->all(),
                    ],
                ],
            ];
        }

        $errorCount = count($errors);
        $warningCount = count($warnings);

        return [
            'schema_version' => 1,
            'monitor_version' => 'question-relation-v2-canary-monitor-v1',
            'generated_at' => now()->toIso8601String(),
            'status' => $errorCount > 0
                ? 'failed'
                : ($warningCount > 0 ? 'warning' : 'ok'),
            'scope' => [
                'topic_key' => $topicKey,
                'public_output' => true,
            ],
            'runtime' => [
                'global_v2_enabled' => $globalV2Enabled,
                'runtime_canary_enabled' => $runtimeCanaryEnabled,
                'public_output' => $globalV2Enabled && $runtimeCanaryEnabled,
            ],
            'summary' => [
                'active_canary_rollouts' => $rollouts->count(),
                'topics_checked' => count($topics),
                'errors' => $errorCount,
                'warnings' => $warningCount,
                'duration_ms' => $this->durationMs($startedAt),
            ],
            'topics' => $topics,
            'issues' => [
                'errors' => $errors,
                'warnings' => $warnings,
            ],
        ];
    }

    /** @return array{algorithm: string, eligible_sources: int, included_sources: int, excluded_sources: int, included_external_ids: array<int, string>} */
    protected function cohort(
        QuestionRelationRankingRun $run,
        QuestionSeoTopic $topic,
        QuestionRelationRollout $rollout,
    ): array {
        $sourceExternalIds = QuestionRelationRecommendation::query()
            ->where('question_relation_ranking_run_id', $run->getKey())
            ->selected()
            ->distinct()
            ->pluck('source_explanation_id');
        $externalIds = QuestionPublicExplanation::query()
            ->published()
            ->whereIn('id', $sourceExternalIds)
            ->orderBy('external_id')
            ->pluck('external_id');
        $assignments = $externalIds->map(fn (string $externalId): array => [
            'external_id' => $externalId,
            ...QuestionRelationV2ShadowResolver::canaryCohortForExternalId(
                $externalId,
                (int) $topic->getKey(),
                (string) $rollout->cohort_seed,
                (int) $rollout->exposure_percentage,
            ),
        ]);

        return [
            'algorithm' => 'sha256-mod-10000-v1',
            'eligible_sources' => $externalIds->count(),
            'included_sources' => $assignments->where('included', true)->count(),
            'excluded_sources' => $assignments->where('included', false)->count(),
            'included_external_ids' => $assignments
                ->where('included', true)
                ->pluck('external_id')
                ->values()
                ->all(),
        ];
    }

    protected function durationMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
