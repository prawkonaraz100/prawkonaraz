<?php

namespace App\Support;

use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelation;
use App\Models\QuestionRelationEvidence;
use App\Models\QuestionSeoTopic;
use App\Models\QuestionSeoTopicMembership;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionRelationGraphV2Backfill
{
    public const VERSION = 'v2-backfill-20260727-v1';

    private const POSTGRES_ADVISORY_LOCK = 2026072701;

    /** @return array<string, mixed> */
    public function run(bool $write = false, int $sampleLimit = 25): array
    {
        $sampleLimit = max(1, min(100, $sampleLimit));
        $before = $this->inspect($sampleLimit);

        if (! $write || ! (bool) data_get($before, 'quality_gates.passed', false)) {
            return [
                ...$before,
                'mode' => $write ? 'write_blocked' : 'preview',
                'applied' => false,
            ];
        }

        DB::transaction(function () use ($sampleLimit): void {
            if (DB::getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?)', [self::POSTGRES_ADVISORY_LOCK]);
            }

            $lockedReport = $this->inspect($sampleLimit);
            if (! (bool) data_get($lockedReport, 'quality_gates.passed', false)) {
                throw new \RuntimeException('Stan danych zmienił się przed zapisem i nie przechodzi quality gates.');
            }

            $this->applyTopics();
            $this->applyMemberships();
            $this->applyDirections();
            $this->applyEvidences();
        }, 3);

        $after = $this->inspect($sampleLimit);

        return [
            ...$after,
            'mode' => 'write',
            'applied' => true,
            'applied_changes' => [
                'topics' => (int) data_get($before, 'topics.would_update', 0),
                'memberships' => (int) data_get($before, 'memberships.would_update', 0),
                'directions' => (int) data_get($before, 'directions.would_normalize', 0),
                'evidences_created' => (int) data_get($before, 'evidences.would_create', 0),
                'evidences_updated' => (int) data_get($before, 'evidences.would_update', 0),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function inspect(int $sampleLimit): array
    {
        $topics = $this->inspectTopics($sampleLimit);
        $memberships = $this->inspectMemberships($sampleLimit);
        $directions = $this->inspectDirections();
        $evidences = $this->inspectEvidences($sampleLimit);

        $blockers = [
            'invalid_topics' => (int) $topics['invalid'],
            'membership_duplicate_groups' => (int) $memberships['duplicate_explanation_groups'],
            'published_explanations_without_membership' => (int) $memberships['published_explanations_without_membership'],
            'backfilled_membership_invariant_errors' => (int) $memberships['backfilled_invariant_errors'],
            'unknown_directions' => (int) $directions['unknown'],
            'duplicate_backfill_evidence_keys' => (int) $evidences['duplicate_keys'],
        ];

        return [
            'schema_version' => 1,
            'backfill_version' => self::VERSION,
            'generated_at' => now()->toIso8601String(),
            'counts' => [
                'topics' => DB::table('question_seo_topics')->count(),
                'memberships' => DB::table('question_seo_topic_memberships')->count(),
                'relations' => DB::table('question_relations')->count(),
                'backfill_evidences' => DB::table('question_relation_evidences')
                    ->where('version', self::VERSION)
                    ->count(),
            ],
            'topics' => $topics,
            'memberships' => $memberships,
            'directions' => $directions,
            'evidences' => $evidences,
            'quality_gates' => [
                'passed' => collect($blockers)->every(fn (int $count): bool => $count === 0),
                'blockers' => $blockers,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function inspectTopics(int $sampleLimit): array
    {
        $topics = DB::table('question_seo_topics')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'key', 'kind', 'status', 'content_quality_status', 'metadata']);
        $topicsById = $topics->keyBy(fn (object $topic): int => (int) $topic->id);
        $report = [
            'macro' => 0,
            'topic' => 0,
            'existing_v2_untouched' => 0,
            'already_backfilled' => 0,
            'would_update' => 0,
            'invalid' => 0,
            'invalid_samples' => [],
        ];

        foreach ($topics as $topic) {
            $classification = $this->classifyTopic($topic, $topicsById);

            if ($classification === null) {
                $report['invalid']++;
                if (count($report['invalid_samples']) < $sampleLimit) {
                    $report['invalid_samples'][] = [
                        'id' => (int) $topic->id,
                        'key' => (string) $topic->key,
                        'parent_id' => $topic->parent_id !== null ? (int) $topic->parent_id : null,
                    ];
                }

                continue;
            }

            if ($classification === 'existing_v2') {
                $report['existing_v2_untouched']++;

                continue;
            }

            $report[$classification]++;
            $metadata = $this->decodeMetadata($topic->metadata);
            if (data_get($metadata, 'v2_backfill.version') === self::VERSION) {
                $report['already_backfilled']++;
            } else {
                $report['would_update']++;
            }
        }

        return $report;
    }

    /** @return array<string, mixed> */
    private function inspectMemberships(int $sampleLimit): array
    {
        $memberships = DB::table('question_seo_topic_memberships')
            ->orderBy('id')
            ->get([
                'id',
                'question_public_explanation_id',
                'question_seo_topic_id',
                'source',
                'is_primary',
                'role',
                'status',
                'metadata',
            ]);
        $groups = $memberships->groupBy(fn (object $membership): int => (int) $membership->question_public_explanation_id);
        $duplicates = $groups->filter(fn (Collection $rows): bool => $rows->count() !== 1);
        $wouldUpdate = 0;
        $alreadyBackfilled = 0;
        $backfilledInvariantErrors = 0;

        foreach ($memberships as $membership) {
            $metadata = $this->decodeMetadata($membership->metadata);
            $isBackfilled = data_get($metadata, 'v2_backfill.version') === self::VERSION;

            if (! $isBackfilled) {
                $wouldUpdate++;

                continue;
            }

            $alreadyBackfilled++;
            if (
                ! (bool) $membership->is_primary
                || (string) $membership->role !== QuestionSeoTopicMembership::ROLE_PRIMARY
                || (string) $membership->status !== QuestionSeoTopicMembership::STATUS_VERIFIED
            ) {
                $backfilledInvariantErrors++;
            }
        }

        $withoutMembership = DB::table('question_public_explanations as explanations')
            ->leftJoin(
                'question_seo_topic_memberships as memberships',
                'memberships.question_public_explanation_id',
                '=',
                'explanations.id',
            )
            ->where('explanations.status', QuestionPublicExplanation::STATUS_PUBLISHED)
            ->whereNotNull('explanations.published_at')
            ->whereNull('memberships.id')
            ->count();

        return [
            'total' => $memberships->count(),
            'already_backfilled' => $alreadyBackfilled,
            'would_update' => $wouldUpdate,
            'backfilled_invariant_errors' => $backfilledInvariantErrors,
            'published_explanations_without_membership' => $withoutMembership,
            'duplicate_explanation_groups' => $duplicates->count(),
            'duplicate_samples' => $duplicates
                ->take($sampleLimit)
                ->map(fn (Collection $rows, int|string $explanationId): array => [
                    'question_public_explanation_id' => (int) $explanationId,
                    'membership_ids' => $rows->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
                ])
                ->values()
                ->all(),
            'by_source' => $memberships->countBy('source')->sortKeys()->all(),
            'by_state' => $memberships
                ->countBy(fn (object $membership): string => sprintf(
                    '%s|%s|%s',
                    (bool) $membership->is_primary ? 'primary' : 'not_primary',
                    (string) $membership->role,
                    (string) $membership->status,
                ))
                ->sortKeys()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function inspectDirections(): array
    {
        $counts = DB::table('question_relations')
            ->select('direction', DB::raw('COUNT(*) AS aggregate'))
            ->groupBy('direction')
            ->orderBy('direction')
            ->pluck('aggregate', 'direction')
            ->map(fn (mixed $count): int => (int) $count);
        $known = ['symmetric', 'left_to_right', 'right_to_left', 'symetryczna'];
        $unknown = $counts->except($known);

        return [
            'by_value' => $counts->all(),
            'would_normalize' => (int) $counts->get('symetryczna', 0),
            'unknown' => $unknown->sum(),
            'unknown_values' => $unknown->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function inspectEvidences(int $sampleLimit): array
    {
        $existing = DB::table('question_relation_evidences')
            ->where('version', self::VERSION)
            ->get([
                'id',
                'question_relation_id',
                'evidence_type',
                'summary',
                'source',
                'confidence',
                'status',
                'metadata',
                'version',
                'reviewed_by_user_id',
                'reviewed_at',
            ]);
        $existingByKey = $existing->groupBy(fn (object $evidence): string => $this->evidenceKey(
            (int) $evidence->question_relation_id,
            (string) $evidence->evidence_type,
        ));
        $duplicateKeys = $existingByKey->filter(fn (Collection $rows): bool => $rows->count() > 1);
        $report = [
            'existing' => $existing->count(),
            'desired' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'unchanged' => 0,
            'duplicate_keys' => $duplicateKeys->count(),
            'duplicate_samples' => $duplicateKeys->keys()->take($sampleLimit)->values()->all(),
            'desired_by_type' => [],
            'desired_by_status' => [],
        ];

        $this->relationQuery()->chunkById(500, function (Collection $relations) use (&$report, $existingByKey): void {
            foreach ($relations as $relation) {
                foreach ($this->desiredEvidences($relation) as $desired) {
                    $report['desired']++;
                    $type = (string) $desired['evidence_type'];
                    $status = (string) $desired['status'];
                    $report['desired_by_type'][$type] = ($report['desired_by_type'][$type] ?? 0) + 1;
                    $report['desired_by_status'][$status] = ($report['desired_by_status'][$status] ?? 0) + 1;
                    $rows = $existingByKey->get($this->evidenceKey((int) $relation->id, $type));

                    if (! $rows instanceof Collection || $rows->isEmpty()) {
                        $report['would_create']++;
                    } elseif ($this->evidenceDiffers($rows->first(), $desired)) {
                        $report['would_update']++;
                    } else {
                        $report['unchanged']++;
                    }
                }
            }
        });

        ksort($report['desired_by_type']);
        ksort($report['desired_by_status']);

        return $report;
    }

    private function applyTopics(): void
    {
        $topics = DB::table('question_seo_topics')->orderBy('id')->get();
        $topicsById = $topics->keyBy(fn (object $topic): int => (int) $topic->id);

        foreach ($topics as $topic) {
            $classification = $this->classifyTopic($topic, $topicsById);
            if (! in_array($classification, [QuestionSeoTopic::KIND_MACRO, QuestionSeoTopic::KIND_TOPIC], true)) {
                continue;
            }

            $metadata = $this->decodeMetadata($topic->metadata);
            if (data_get($metadata, 'v2_backfill.version') === self::VERSION) {
                continue;
            }

            $metadata['v2_backfill'] = [
                'version' => self::VERSION,
                'classification' => $classification,
            ];

            DB::table('question_seo_topics')->where('id', $topic->id)->update([
                'kind' => $classification,
                'status' => QuestionSeoTopic::STATUS_PUBLISHED,
                'content_quality_status' => QuestionSeoTopic::QUALITY_LEGACY,
                'metadata' => $this->encodeMetadata($metadata),
                'updated_at' => now(),
            ]);
        }
    }

    private function applyMemberships(): void
    {
        DB::table('question_seo_topic_memberships')
            ->orderBy('id')
            ->chunkById(500, function (Collection $memberships): void {
                foreach ($memberships as $membership) {
                    $metadata = $this->decodeMetadata($membership->metadata);
                    if (data_get($metadata, 'v2_backfill.version') === self::VERSION) {
                        continue;
                    }

                    $metadata['v2_backfill'] = [
                        'version' => self::VERSION,
                        'original_source' => (string) $membership->source,
                    ];

                    DB::table('question_seo_topic_memberships')->where('id', $membership->id)->update([
                        'source' => QuestionRelation::SOURCE_GRAPH,
                        'is_primary' => true,
                        'role' => QuestionSeoTopicMembership::ROLE_PRIMARY,
                        'status' => QuestionSeoTopicMembership::STATUS_VERIFIED,
                        'metadata' => $this->encodeMetadata($metadata),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function applyDirections(): void
    {
        DB::table('question_relations')
            ->where('direction', 'symetryczna')
            ->update([
                'direction' => 'symmetric',
                'updated_at' => now(),
            ]);
    }

    private function applyEvidences(): void
    {
        $existingByKey = DB::table('question_relation_evidences')
            ->where('version', self::VERSION)
            ->get()
            ->keyBy(fn (object $evidence): string => $this->evidenceKey(
                (int) $evidence->question_relation_id,
                (string) $evidence->evidence_type,
            ));

        $this->relationQuery()->chunkById(250, function (Collection $relations) use ($existingByKey): void {
            foreach ($relations as $relation) {
                foreach ($this->desiredEvidences($relation) as $desired) {
                    $key = $this->evidenceKey((int) $relation->id, (string) $desired['evidence_type']);
                    $existing = $existingByKey->get($key);
                    $values = [
                        ...$desired,
                        'metadata' => $this->encodeMetadata((array) $desired['metadata']),
                        'updated_at' => now(),
                    ];

                    if ($existing === null) {
                        $id = DB::table('question_relation_evidences')->insertGetId([
                            ...$values,
                            'created_at' => now(),
                        ]);
                        $existingByKey->put($key, (object) [...$values, 'id' => $id]);
                    } elseif ($this->evidenceDiffers($existing, $desired)) {
                        DB::table('question_relation_evidences')->where('id', $existing->id)->update($values);
                    }
                }
            }
        });
    }

    /** @return Builder */
    private function relationQuery()
    {
        return DB::table('question_relations')->select([
            'id',
            'source',
            'status',
            'score',
            'reason',
            'difference',
            'anchor_left_to_right',
            'anchor_right_to_left',
            'metadata',
            'reviewed_by_user_id',
            'reviewed_at',
        ])->orderBy('id');
    }

    /** @return array<int, array<string, mixed>> */
    private function desiredEvidences(object $relation): array
    {
        $evidences = [];
        $source = in_array((string) $relation->source, [QuestionRelation::SOURCE_EDITORIAL, QuestionRelation::SOURCE_GRAPH], true)
            ? (string) $relation->source
            : 'import';
        $status = match ((string) $relation->status) {
            QuestionRelation::STATUS_REJECTED => QuestionRelationEvidence::STATUS_REJECTED,
            QuestionRelation::STATUS_VERIFIED => QuestionRelationEvidence::STATUS_VERIFIED,
            default => QuestionRelationEvidence::STATUS_CANDIDATE,
        };
        $confidence = $relation->score !== null ? (float) $relation->score : null;
        $base = [
            'question_relation_id' => (int) $relation->id,
            'source' => $source,
            'confidence' => $confidence,
            'status' => $status,
            'version' => self::VERSION,
            'reviewed_by_user_id' => $relation->reviewed_by_user_id !== null
                ? (int) $relation->reviewed_by_user_id
                : null,
            'reviewed_at' => $relation->reviewed_at,
        ];

        $fields = [
            'reason' => $source === QuestionRelation::SOURCE_EDITORIAL ? 'editorial_note' : 'relation_reason',
            'difference' => 'contrast',
            'anchor_left_to_right' => 'anchor_left_to_right',
            'anchor_right_to_left' => 'anchor_right_to_left',
        ];

        foreach ($fields as $field => $evidenceType) {
            $summary = trim((string) ($relation->{$field} ?? ''));
            if ($summary === '') {
                continue;
            }

            $evidences[] = [
                ...$base,
                'evidence_type' => $evidenceType,
                'summary' => $summary,
                'metadata' => $this->evidenceMetadata($field),
            ];
        }

        $legacyMetadata = $this->decodeMetadata($relation->metadata);
        if ($legacyMetadata !== []) {
            $common = collect((array) ($legacyMetadata['common'] ?? []))
                ->map(fn (mixed $value): string => trim((string) $value))
                ->filter()
                ->values();
            $summary = $common->isNotEmpty()
                ? 'Wspólne cechy: '.$common->implode(', ').'.'
                : 'Zachowano metadane relacji V1 do dalszego audytu.';

            $evidences[] = [
                ...$base,
                'evidence_type' => 'legacy_metadata',
                'summary' => $summary,
                'metadata' => $this->evidenceMetadata('metadata', [
                    'legacy_relation_metadata' => $legacyMetadata,
                ]),
            ];
        }

        return $evidences;
    }

    /** @param array<string, mixed> $additional
     * @return array<string, mixed>
     */
    private function evidenceMetadata(string $field, array $additional = []): array
    {
        return [
            'v2_backfill' => [
                'version' => self::VERSION,
                'legacy_field' => $field,
            ],
            ...$additional,
        ];
    }

    /** @param Collection<int, object> $topicsById */
    private function classifyTopic(object $topic, Collection $topicsById): ?string
    {
        $key = (string) $topic->key;

        if (str_starts_with($key, 'primary:')) {
            return $topic->parent_id === null ? QuestionSeoTopic::KIND_MACRO : null;
        }

        if (str_starts_with($key, 'secondary:')) {
            $parent = $topic->parent_id !== null ? $topicsById->get((int) $topic->parent_id) : null;

            return $parent !== null
                && str_starts_with((string) $parent->key, 'primary:')
                && $parent->parent_id === null
                    ? QuestionSeoTopic::KIND_TOPIC
                    : null;
        }

        return in_array((string) $topic->kind, [
            QuestionSeoTopic::KIND_MACRO,
            QuestionSeoTopic::KIND_TOPIC,
            QuestionSeoTopic::KIND_SUBTOPIC,
        ], true) ? 'existing_v2' : null;
    }

    /** @param array<string, mixed> $desired */
    private function evidenceDiffers(object $existing, array $desired): bool
    {
        return (string) $existing->summary !== (string) $desired['summary']
            || (string) $existing->source !== (string) $desired['source']
            || (string) $existing->status !== (string) $desired['status']
            || ! $this->sameNullableFloat($existing->confidence, $desired['confidence'])
            || (int) ($existing->reviewed_by_user_id ?? 0) !== (int) ($desired['reviewed_by_user_id'] ?? 0)
            || (string) ($existing->reviewed_at ?? '') !== (string) ($desired['reviewed_at'] ?? '')
            || $this->canonicalMetadata($this->decodeMetadata($existing->metadata))
                !== $this->canonicalMetadata((array) $desired['metadata']);
    }

    private function sameNullableFloat(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === null && $right === null;
        }

        return abs((float) $left - (float) $right) < 0.000001;
    }

    /** @return array<string, mixed> */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $metadata */
    private function encodeMetadata(array $metadata): string
    {
        return (string) json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $metadata */
    private function canonicalMetadata(array $metadata): string
    {
        $sort = function (array &$value) use (&$sort): void {
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $sort($item);
                }
            }

            if (! array_is_list($value)) {
                ksort($value);
            }
        };
        $sort($metadata);

        return $this->encodeMetadata($metadata);
    }

    private function evidenceKey(int $relationId, string $type): string
    {
        return $relationId.'|'.$type;
    }
}
