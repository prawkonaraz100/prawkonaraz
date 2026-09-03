<?php

namespace App\Support;

use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionSeoTopic;
use App\Models\QuestionSeoTopicMembership;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

class QuestionRelationShadowRunImporter
{
    private const POSTGRES_ADVISORY_LOCK = 2026072702;

    /** @return array<string, mixed> */
    public function run(
        string $rankingPath,
        string $manifestPath,
        string $evaluationPath,
        string $topicKey,
        bool $write = false,
        int $sampleLimit = 25,
    ): array {
        $sampleLimit = max(1, min(100, $sampleLimit));
        $artifact = $this->loadArtifact($rankingPath, $manifestPath, $evaluationPath);
        $before = $this->inspect($artifact, $topicKey, $sampleLimit);

        if (! $write || ! (bool) data_get($before, 'quality_gates.passed', false)) {
            return $this->withoutInternalRecords([
                ...$before,
                'mode' => $write ? 'write_blocked' : 'preview',
                'applied' => false,
            ]);
        }

        $created = false;
        DB::transaction(function () use ($artifact, $topicKey, $sampleLimit, &$created): void {
            if (DB::getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?)', [self::POSTGRES_ADVISORY_LOCK]);
            }

            $locked = $this->inspect($artifact, $topicKey, $sampleLimit);
            if (! (bool) data_get($locked, 'quality_gates.passed', false)) {
                throw new RuntimeException('Stan danych zmienił się przed zapisem i nie przechodzi quality gates.');
            }
            if ((bool) data_get($locked, 'run.already_imported', false)) {
                return;
            }

            $topicId = (int) data_get($locked, 'topic.id');
            $run = QuestionRelationRankingRun::query()->create([
                'question_seo_topic_id' => $topicId,
                'input_version' => $artifact['input_version'],
                'generator_version' => $artifact['algorithm_version'],
                'config_hash' => $artifact['config_hash'],
                'status' => QuestionRelationRankingRun::STATUS_VALIDATED,
                'metrics' => [
                    'mode' => 'offline_shadow_import',
                    'ranking_sha256' => $artifact['ranking_sha256'],
                    'evaluation_sha256' => $artifact['evaluation_sha256'],
                    'core_sources' => $locked['artifact']['core_sources'],
                    'recommendations' => $locked['recommendations']['planned'],
                    'direct_links' => $locked['recommendations']['direct'],
                    'hub_fallback_links' => $locked['recommendations']['hub_fallback'],
                    'minimum_links' => $locked['recommendations']['minimum_per_core_source'],
                    'public_rollout_authorized' => false,
                ],
                'generated_at' => now(),
                'validated_at' => now(),
                'published_at' => null,
            ]);

            $now = now();
            $records = array_map(static fn (array $row): array => [
                'question_relation_ranking_run_id' => $run->getKey(),
                'source_explanation_id' => $row['source_explanation_id'],
                'target_explanation_id' => $row['target_explanation_id'],
                'question_relation_id' => $row['question_relation_id'],
                'scope' => $row['scope'],
                'group_key' => $row['group_key'],
                'rank' => $row['rank'],
                'score' => $row['score'],
                'score_components' => json_encode(
                    $row['score_components'],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                ),
                'status' => QuestionRelationRecommendation::STATUS_SELECTED,
                'suppression_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $locked['_planned_records']);
            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('question_relation_recommendations')->insert($chunk);
            }
            $created = true;
        }, 3);

        $after = $this->inspect($artifact, $topicKey, $sampleLimit);

        return $this->withoutInternalRecords([
            ...$after,
            'mode' => $created ? 'write' : 'write_idempotent',
            'applied' => $created,
            'applied_changes' => [
                'ranking_runs_created' => $created ? 1 : 0,
                'recommendations_created' => $created ? (int) data_get($after, 'recommendations.planned') : 0,
                'rollouts_created_or_changed' => 0,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function inspect(array $artifact, string $topicKey, int $sampleLimit): array
    {
        $topics = DB::table('question_seo_topics')->where('key', $topicKey)->get(['id', 'key', 'status']);
        $topic = $topics->count() === 1 ? $topics->first() : null;

        $externalIds = collect($artifact['core_rows'])
            ->flatMap(static fn (array $row): array => [(string) $row['source_id'], (string) $row['target_id']])
            ->unique()
            ->values();
        $published = QuestionPublicExplanation::query()
            ->published()
            ->whereIn('external_id', $externalIds->all())
            ->get(['id', 'external_id'])
            ->groupBy(fn (QuestionPublicExplanation $explanation): string => (string) $explanation->external_id);
        $duplicateExplanationIds = $published
            ->filter(static fn ($rows): bool => $rows->count() !== 1)
            ->keys()
            ->values();
        $resolved = $published
            ->filter(static fn ($rows): bool => $rows->count() === 1)
            ->map(static fn ($rows): QuestionPublicExplanation => $rows->first());
        $missingExternalIds = $externalIds->diff($resolved->keys())->values();

        $coreSourceIds = collect($artifact['core_source_ids']);
        $coreExplanationIds = $coreSourceIds
            ->map(fn (string $externalId): ?int => $resolved->get($externalId)?->getKey())
            ->filter()
            ->values();
        $verifiedPrimaryMemberships = $topic === null
            ? collect()
            : QuestionSeoTopicMembership::query()
                ->where('question_seo_topic_id', $topic->id)
                ->whereIn('question_public_explanation_id', $coreExplanationIds->all())
                ->where('status', QuestionSeoTopicMembership::STATUS_VERIFIED)
                ->where('is_primary', true)
                ->pluck('question_public_explanation_id');
        $missingCoreMembershipIds = $coreExplanationIds->diff($verifiedPrimaryMemberships)->values();

        $relations = DB::table('question_relations')
            ->whereIn('left_explanation_id', $resolved->pluck('id')->all())
            ->whereIn('right_explanation_id', $resolved->pluck('id')->all())
            ->get(['id', 'left_explanation_id', 'right_explanation_id'])
            ->keyBy(fn (object $relation): string => $this->databasePair(
                (int) $relation->left_explanation_id,
                (int) $relation->right_explanation_id,
            ));

        $plannedRecords = [];
        foreach ($artifact['core_rows'] as $row) {
            $source = $resolved->get((string) $row['source_id']);
            $target = $resolved->get((string) $row['target_id']);
            if (! $source instanceof QuestionPublicExplanation || ! $target instanceof QuestionPublicExplanation) {
                continue;
            }
            $relation = $relations->get($this->databasePair($source->getKey(), $target->getKey()));
            $plannedRecords[] = [
                'source_external_id' => (string) $row['source_id'],
                'target_external_id' => (string) $row['target_id'],
                'source_explanation_id' => $source->getKey(),
                'target_explanation_id' => $target->getKey(),
                'question_relation_id' => $relation !== null ? (int) $relation->id : null,
                'scope' => $this->scope($row),
                'group_key' => $row['layer'] === 'direct_score' ? 'closest' : 'context',
                'rank' => (int) $row['position'],
                'score' => $row['score'] !== null ? round((float) $row['score'], 6) : 0.0,
                'score_components' => [
                    'artifact_pair_key' => (string) $row['pair_key'],
                    'layer' => (string) $row['layer'],
                    'source_score' => $row['score'] !== null ? round((float) $row['score'], 6) : null,
                    'proposed_relation_type' => $row['proposed_relation_type'] ?? null,
                    'fallback_scope' => $row['fallback_scope'] ?? null,
                    'artifact_position' => (int) $row['position'],
                ],
            ];
        }
        usort($plannedRecords, fn (array $left, array $right): int => [
            $this->sortableId($left['source_external_id']),
            $left['rank'],
            $this->sortableId($left['target_external_id']),
        ] <=> [
            $this->sortableId($right['source_external_id']),
            $right['rank'],
            $this->sortableId($right['target_external_id']),
        ]);

        $runs = $topic === null ? collect() : QuestionRelationRankingRun::query()
            ->where('question_seo_topic_id', $topic->id)
            ->where('input_version', $artifact['input_version'])
            ->where('generator_version', $artifact['algorithm_version'])
            ->where('config_hash', $artifact['config_hash'])
            ->get();
        $existingMatches = false;
        if ($runs->count() === 1) {
            $existingMatches = $this->existingSnapshotHash((int) $runs->first()->getKey())
                === $this->plannedSnapshotHash($plannedRecords);
        }

        $perSourceCounts = collect($artifact['core_rows'])->countBy('source_id')->values();
        $direct = count(array_filter(
            $artifact['core_rows'],
            static fn (array $row): bool => $row['layer'] === 'direct_score',
        ));
        $rollout = $topic === null ? null : DB::table('question_relation_rollouts')
            ->where('question_seo_topic_id', $topic->id)
            ->first(['id', 'mode', 'active_ranking_run_id', 'exposure_percentage']);
        $blockers = [
            'topic_not_unique' => $topics->count() === 1 ? 0 : 1,
            'topic_not_published' => $topic !== null && $topic->status !== QuestionSeoTopic::STATUS_PUBLISHED ? 1 : 0,
            'missing_published_explanations' => $missingExternalIds->count(),
            'duplicate_published_explanations' => $duplicateExplanationIds->count(),
            'missing_verified_primary_core_memberships' => $missingCoreMembershipIds->count(),
            'incomplete_recommendation_mapping' => count($artifact['core_rows']) - count($plannedRecords),
            'duplicate_matching_runs' => max(0, $runs->count() - 1),
            'existing_run_snapshot_mismatch' => $runs->count() === 1 && ! $existingMatches ? 1 : 0,
        ];

        return [
            'schema_version' => 1,
            'import_version' => 'question-relation-shadow-run-import-v1',
            'generated_at' => now()->toIso8601String(),
            'artifact' => [
                'dataset_version' => $artifact['dataset_version'],
                'algorithm_version' => $artifact['algorithm_version'],
                'input_version' => $artifact['input_version'],
                'ranking_sha256' => $artifact['ranking_sha256'],
                'evaluation_sha256' => $artifact['evaluation_sha256'],
                'all_source_rankings' => $artifact['all_source_count'],
                'core_sources' => count($artifact['core_source_ids']),
                'supporting_source_rankings_excluded' => $artifact['all_source_count'] - count($artifact['core_source_ids']),
            ],
            'topic' => [
                'key' => $topicKey,
                'id' => $topic !== null ? (int) $topic->id : null,
                'status' => $topic?->status,
            ],
            'mapping' => [
                'external_ids_required' => $externalIds->count(),
                'published_explanations_resolved' => $resolved->count(),
                'missing_external_ids' => $missingExternalIds->take($sampleLimit)->all(),
                'duplicate_external_ids' => $duplicateExplanationIds->take($sampleLimit)->all(),
                'verified_primary_core_memberships' => $verifiedPrimaryMemberships->count(),
                'missing_core_membership_explanation_ids' => $missingCoreMembershipIds->take($sampleLimit)->all(),
            ],
            'recommendations' => [
                'planned' => count($plannedRecords),
                'direct' => $direct,
                'hub_fallback' => count($artifact['core_rows']) - $direct,
                'direct_with_relation_id' => count(array_filter(
                    $plannedRecords,
                    static fn (array $row): bool => $row['scope'] === 'direct' && $row['question_relation_id'] !== null,
                )),
                'direct_without_relation_id' => count(array_filter(
                    $plannedRecords,
                    static fn (array $row): bool => $row['scope'] === 'direct' && $row['question_relation_id'] === null,
                )),
                'minimum_per_core_source' => $perSourceCounts->min(),
                'maximum_per_core_source' => $perSourceCounts->max(),
            ],
            'run' => [
                'matching_runs' => $runs->count(),
                'already_imported' => $runs->count() === 1 && $existingMatches,
                'would_create_run' => $runs->count() === 0,
                'would_create_recommendations' => $runs->count() === 0 ? count($plannedRecords) : 0,
                'existing_run_id' => $runs->count() === 1 ? (int) $runs->first()->getKey() : null,
                'target_status' => QuestionRelationRankingRun::STATUS_VALIDATED,
            ],
            'rollout' => [
                'existing' => $rollout !== null,
                'mode' => $rollout?->mode,
                'active_ranking_run_id' => $rollout?->active_ranking_run_id,
                'exposure_percentage' => $rollout?->exposure_percentage !== null
                    ? (int) $rollout->exposure_percentage
                    : null,
                'would_create_or_change' => false,
                'public_rollout_authorized' => false,
            ],
            'quality_gates' => [
                'passed' => collect($blockers)->every(static fn (int $count): bool => $count === 0),
                'minimum_fifteen_links_for_every_core_source' => $perSourceCounts->min() >= 15,
                'artifact_evaluation_passed' => true,
                'public_rollout_authorized' => false,
                'blockers' => $blockers,
            ],
            '_planned_records' => $plannedRecords,
        ];
    }

    /** @return array<string, mixed> */
    private function loadArtifact(string $rankingPath, string $manifestPath, string $evaluationPath): array
    {
        $rankingRows = $this->jsonLines($rankingPath);
        $manifest = $this->jsonObject($manifestPath, 'manifest shadow');
        $evaluation = $this->jsonObject($evaluationPath, 'ewaluacja shadow');
        if ($rankingRows === []) {
            throw new RuntimeException('Ranking shadow jest pusty.');
        }

        $rankingHash = (string) hash_file('sha256', $rankingPath);
        if (($manifest['outputs']['shadow-ranking.jsonl']['sha256'] ?? null) !== $rankingHash) {
            throw new RuntimeException('SHA-256 rankingu nie zgadza się z manifestem shadow.');
        }
        if (($evaluation['inputs']['shadow_ranking']['sha256'] ?? null) !== $rankingHash) {
            throw new RuntimeException('Ewaluacja nie dotyczy wskazanego rankingu shadow.');
        }
        if (! (bool) data_get($manifest, 'quality_gates.passed', false)
            || ! (bool) data_get($evaluation, 'quality_gates.passed', false)) {
            throw new RuntimeException('Manifest lub ewaluacja shadow nie przechodzi quality gates.');
        }
        if ((bool) data_get($manifest, 'quality_gates.public_rollout_authorized', true)
            || (bool) data_get($evaluation, 'quality_gates.public_rollout_authorized', true)) {
            throw new RuntimeException('Artefakt shadow nie może autoryzować publicznego rolloutu.');
        }
        if (data_get($manifest, 'selection_policy.gold_labels_used_for_selection') !== false
            || data_get($evaluation, 'evaluation_policy.gold_used_only_after_selection') !== true
            || data_get($evaluation, 'evaluation_policy.unknown_links_treated_as_negative') !== false) {
            throw new RuntimeException('Artefakt nie zachowuje wymaganej separacji selekcji od gold setu.');
        }

        $datasetVersion = $this->requiredString($rankingRows[0], 'dataset_version', 'ranking:1');
        $algorithmVersion = $this->requiredString($rankingRows[0], 'algorithm_version', 'ranking:1');
        if (($manifest['dataset_version'] ?? null) !== $datasetVersion
            || ($evaluation['dataset_version'] ?? null) !== $datasetVersion
            || ($manifest['algorithm_version'] ?? null) !== $algorithmVersion
            || ($evaluation['algorithm_version'] ?? null) !== $algorithmVersion) {
            throw new RuntimeException('Wersje rankingu, manifestu i ewaluacji nie są zgodne.');
        }
        $configHash = trim((string) data_get($manifest, 'inputs.config.sha256', ''));
        if (! preg_match('/^[a-f0-9]{64}$/', $configHash)) {
            throw new RuntimeException('Manifest nie zawiera poprawnego SHA-256 konfiguracji.');
        }

        $bySource = [];
        $seen = [];
        foreach ($rankingRows as $index => $row) {
            $context = 'ranking:'.($index + 1);
            if (($row['dataset_version'] ?? null) !== $datasetVersion
                || ($row['algorithm_version'] ?? null) !== $algorithmVersion) {
                throw new RuntimeException("{$context}: niespójna wersja.");
            }
            $sourceId = $this->requiredString($row, 'source_id', $context);
            $targetId = $this->requiredString($row, 'target_id', $context);
            $directedKey = $sourceId.'>'.$targetId;
            if ($sourceId === $targetId || isset($seen[$directedKey])) {
                throw new RuntimeException("{$context}: self-link albo duplikat linku kierunkowego.");
            }
            $seen[$directedKey] = true;
            if (! in_array($row['layer'] ?? null, ['direct_score', 'hub_fallback'], true)) {
                throw new RuntimeException("{$context}: nieobsługiwana warstwa.");
            }
            $bySource[$sourceId][] = $row;
        }

        $coreRows = [];
        $coreSourceIds = [];
        foreach ($bySource as $sourceId => $rows) {
            usort($rows, static fn (array $left, array $right): int => (int) $left['position'] <=> (int) $right['position']);
            foreach ($rows as $position => $row) {
                if ((int) ($row['position'] ?? 0) !== $position + 1) {
                    throw new RuntimeException("Ranking źródła {$sourceId} nie ma ciągłych pozycji.");
                }
            }
            $roles = array_unique(array_map(static fn (array $row): string => (string) ($row['source_pilot_role'] ?? ''), $rows));
            if (count($roles) !== 1 || ! in_array($roles[0], ['core', 'supporting_candidate'], true)) {
                throw new RuntimeException("Ranking źródła {$sourceId} ma niespójną rolę pilota.");
            }
            if ($roles[0] === 'core') {
                if (count($rows) < 15) {
                    throw new RuntimeException("Rdzeniowe pytanie {$sourceId} ma mniej niż 15 rekomendacji.");
                }
                $coreSourceIds[] = (string) $sourceId;
                array_push($coreRows, ...$rows);
            }
        }
        if ($coreSourceIds === []) {
            throw new RuntimeException('Ranking nie zawiera źródeł o roli core.');
        }

        return [
            'dataset_version' => $datasetVersion,
            'algorithm_version' => $algorithmVersion,
            'input_version' => $datasetVersion.'@'.substr($rankingHash, 0, 16),
            'config_hash' => $configHash,
            'ranking_sha256' => $rankingHash,
            'evaluation_sha256' => (string) hash_file('sha256', $evaluationPath),
            'all_source_count' => count($bySource),
            'core_source_ids' => $coreSourceIds,
            'core_rows' => $coreRows,
        ];
    }

    private function scope(array $row): string
    {
        if ($row['layer'] === 'direct_score') {
            return 'direct';
        }

        return match ($row['fallback_scope'] ?? null) {
            'same_secondary' => 'same_subtopic',
            'same_primary' => 'same_topic',
            'core_hub', 'pilot_hub' => 'adjacent_topic',
            default => throw new RuntimeException('Nieobsługiwany fallback_scope w rankingu shadow.'),
        };
    }

    private function existingSnapshotHash(int $runId): string
    {
        $rows = DB::table('question_relation_recommendations as recommendations')
            ->join('question_public_explanations as source', 'source.id', '=', 'recommendations.source_explanation_id')
            ->join('question_public_explanations as target', 'target.id', '=', 'recommendations.target_explanation_id')
            ->where('recommendations.question_relation_ranking_run_id', $runId)
            ->orderBy('source.external_id')
            ->orderBy('recommendations.rank')
            ->get([
                'source.external_id as source_external_id',
                'target.external_id as target_external_id',
                'recommendations.question_relation_id',
                'recommendations.scope',
                'recommendations.group_key',
                'recommendations.rank',
                'recommendations.score',
                'recommendations.score_components',
                'recommendations.status',
                'recommendations.suppression_reason',
            ])
            ->map(fn (object $row): array => [
                'source_external_id' => (string) $row->source_external_id,
                'target_external_id' => (string) $row->target_external_id,
                'question_relation_id' => $row->question_relation_id !== null ? (int) $row->question_relation_id : null,
                'scope' => (string) $row->scope,
                'group_key' => (string) $row->group_key,
                'rank' => (int) $row->rank,
                'score' => round((float) $row->score, 6),
                'score_components' => $this->decodeJsonValue($row->score_components),
                'status' => (string) $row->status,
                'suppression_reason' => $row->suppression_reason,
            ])
            ->all();

        return $this->snapshotHash($rows);
    }

    private function plannedSnapshotHash(array $rows): string
    {
        return $this->snapshotHash(array_map(static fn (array $row): array => [
            'source_external_id' => $row['source_external_id'],
            'target_external_id' => $row['target_external_id'],
            'question_relation_id' => $row['question_relation_id'],
            'scope' => $row['scope'],
            'group_key' => $row['group_key'],
            'rank' => $row['rank'],
            'score' => round((float) $row['score'], 6),
            'score_components' => $row['score_components'],
            'status' => QuestionRelationRecommendation::STATUS_SELECTED,
            'suppression_reason' => null,
        ], $rows));
    }

    private function snapshotHash(array $rows): string
    {
        usort($rows, fn (array $left, array $right): int => [
            $this->sortableId($left['source_external_id']),
            $left['rank'],
            $this->sortableId($left['target_external_id']),
        ] <=> [
            $this->sortableId($right['source_external_id']),
            $right['rank'],
            $this->sortableId($right['target_external_id']),
        ]);

        return hash('sha256', json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function sortableId(string $id): string
    {
        return ctype_digit($id) ? str_pad($id, 32, '0', STR_PAD_LEFT) : 'z:'.$id;
    }

    private function databasePair(int $left, int $right): string
    {
        return min($left, $right).'|'.max($left, $right);
    }

    private function withoutInternalRecords(array $report): array
    {
        unset($report['_planned_records']);

        return $report;
    }

    private function decodeJsonValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        try {
            $decoded = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Niepoprawny score_components istniejącej rekomendacji: '.$exception->getMessage());
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    private function jsonObject(string $path, string $label): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Brak pliku: {$path}");
        }
        try {
            $value = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Niepoprawny JSON ({$label}): {$exception->getMessage()}");
        }
        if (! is_array($value)) {
            throw new RuntimeException("{$label} nie jest obiektem JSON.");
        }

        return $value;
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonLines(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Brak pliku JSONL: {$path}");
        }
        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Nie można otworzyć JSONL: {$path}");
        }
        try {
            $line = 0;
            while (($contents = fgets($handle)) !== false) {
                $line++;
                $contents = trim($contents);
                if ($contents === '') {
                    continue;
                }
                try {
                    $row = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException("Niepoprawny JSON w {$path}:{$line}: {$exception->getMessage()}");
                }
                if (! is_array($row)) {
                    throw new RuntimeException("Wiersz {$path}:{$line} nie jest obiektem JSON.");
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function requiredString(array $row, string $key, string $context): string
    {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException("Brak pola {$key} w {$context}.");
        }

        return $value;
    }
}
