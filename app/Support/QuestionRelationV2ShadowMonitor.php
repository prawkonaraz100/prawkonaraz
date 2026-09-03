<?php

namespace App\Support;

use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;

class QuestionRelationV2ShadowMonitor
{
    public function __construct(
        protected QuestionRelationV2ShadowAuditor $auditor,
    ) {}

    /**
     * Inspect active zero-exposure shadow rollouts without changing graph data
     * or the public question renderer.
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
        $runtimeShadowEnabled = (bool) config('question_relations.v2_shadow_enabled', false);

        if (! $globalV2Enabled) {
            $warnings[] = [
                'type' => 'global_v2_switch_disabled',
                'message' => 'Globalna flaga V2 jest wyłączona; ruch publiczny nie uruchamia shadow.',
            ];
        }

        if (! $runtimeShadowEnabled) {
            $warnings[] = [
                'type' => 'runtime_shadow_switch_disabled',
                'message' => 'Flaga runtime shadow jest wyłączona; ruch publiczny nie uruchamia shadow.',
            ];
        }

        $rollouts = QuestionRelationRollout::query()
            ->where('mode', QuestionRelationRollout::MODE_SHADOW)
            ->where('exposure_percentage', 0)
            ->with(['topic', 'activeRankingRun'])
            ->when($topicKey !== null, function ($query) use ($topicKey): void {
                $query->whereHas('topic', fn ($topicQuery) => $topicQuery->where('key', $topicKey));
            })
            ->orderBy('question_seo_topic_id')
            ->get();

        if ($rollouts->isEmpty()) {
            $warnings[] = [
                'type' => 'no_active_shadow_rollouts',
                'message' => $topicKey === null
                    ? 'Nie znaleziono aktywnych rolloutów mode=shadow z ekspozycją 0%.'
                    : 'Wskazany topic nie ma aktywnego rolloutu mode=shadow z ekspozycją 0%.',
            ];
        }

        $topics = [];

        foreach ($rollouts as $rollout) {
            $topic = $rollout->topic;
            $run = $rollout->activeRankingRun;

            if (! $topic instanceof QuestionSeoTopic || ! $run instanceof QuestionRelationRankingRun) {
                $errors[] = [
                    'type' => 'rollout_context_not_resolvable',
                    'message' => 'Rollout shadow nie ma czytelnego topicu lub aktywnego runu.',
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
            $auditWarnings = (int) data_get($audit, 'issue_counts.warnings', 0);

            if ($auditErrors > 0) {
                $errors[] = [
                    'type' => 'shadow_audit_failed',
                    'message' => 'Audyt shadow dla topicu wykrył błędy.',
                    'context' => [
                        'topic_key' => $topic->key,
                        'run_id' => (int) $run->getKey(),
                        'errors' => $auditErrors,
                    ],
                ];
            } elseif ($auditWarnings > 0) {
                $warnings[] = [
                    'type' => 'shadow_audit_warning',
                    'message' => 'Audyt shadow dla topicu zwrócił ostrzeżenia.',
                    'context' => [
                        'topic_key' => $topic->key,
                        'run_id' => (int) $run->getKey(),
                        'warnings' => $auditWarnings,
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
                ],
                'run' => [
                    'id' => (int) $run->getKey(),
                    'status' => (string) $run->status,
                    'generator_version' => (string) $run->generator_version,
                ],
                'audit' => [
                    'status' => (string) data_get($audit, 'status', 'failed'),
                    'issue_counts' => [
                        'errors' => $auditErrors,
                        'warnings' => $auditWarnings,
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
                    'issues' => data_get($audit, 'issues', []),
                ],
            ];
        }

        $errorCount = count($errors);
        $warningCount = count($warnings);

        return [
            'schema_version' => 1,
            'monitor_version' => 'question-relation-v2-shadow-monitor-v1',
            'generated_at' => now()->toIso8601String(),
            'status' => $errorCount > 0
                ? 'failed'
                : ($warningCount > 0 ? 'warning' : 'ok'),
            'scope' => [
                'topic_key' => $topicKey,
                'public_output' => false,
            ],
            'runtime' => [
                'global_v2_enabled' => $globalV2Enabled,
                'runtime_shadow_enabled' => $runtimeShadowEnabled,
                'public_output' => false,
            ],
            'summary' => [
                'active_shadow_rollouts' => $rollouts->count(),
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

    protected function durationMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
