<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class QuestionIntegrityAuditService
{
    protected const TRACKED_FIELDS = [
        'prompt',
        'explanation',
        'option_a',
        'option_b',
        'option_c',
        'correct_answer',
        'question_type',
        'points',
        'difficulty',
        'is_active',
        'topic_key',
        'structure_scope',
        'categories_original',
        'main_media_original',
        'pjm_question',
    ];

    protected const CRITICAL_FIELDS = [
        'option_a',
        'option_b',
        'option_c',
        'correct_answer',
        'question_type',
        'main_media_original',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function loadLatestReport(string $channel = 'gov_full_catalog'): ?array
    {
        $path = storage_path('app/question-integrity/'.$channel.'/latest-report.json');

        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>|null  $audit
     * @return array{state:string,label:string,color:string,description:string}
     */
    public function resolveStatusMeta(?array $audit): array
    {
        if (! is_array($audit) || $audit === []) {
            return [
                'state' => 'unknown',
                'label' => 'Brak audytu',
                'color' => 'gray',
                'description' => 'Nie ma jeszcze snapshotu porownawczego dla oficjalnej bazy.',
            ];
        }

        $summary = is_array($audit['summary'] ?? null) ? $audit['summary'] : [];
        $baseline = (bool) ($audit['baseline'] ?? false);
        $added = (int) ($summary['added_total'] ?? 0);
        $removed = (int) ($summary['removed_total'] ?? 0);
        $changed = (int) ($summary['changed_total'] ?? 0);
        $critical = (int) ($summary['critical_total'] ?? 0);

        if ($baseline) {
            return [
                'state' => 'baseline',
                'label' => 'Baseline',
                'color' => 'gray',
                'description' => sprintf(
                    'Pierwszy snapshot katalogu: %d pytan, bez porownania do starszej wersji.',
                    (int) ($summary['current_total'] ?? 0),
                ),
            ];
        }

        if ($critical > 0 || $removed > 0) {
            return [
                'state' => 'critical',
                'label' => 'Czerwono',
                'color' => 'danger',
                'description' => sprintf(
                    'Krytyczne=%d, usuniete=%d, zmienione=%d, dodane=%d.',
                    $critical,
                    $removed,
                    $changed,
                    $added,
                ),
            ];
        }

        if ($changed > 0 || $added > 0) {
            return [
                'state' => 'review',
                'label' => 'Zolto',
                'color' => 'warning',
                'description' => sprintf(
                    'Do review: zmienione=%d, dodane=%d, bez zmian krytycznych.',
                    $changed,
                    $added,
                ),
            ];
        }

        return [
            'state' => 'ok',
            'label' => 'Zielono',
            'color' => 'success',
            'description' => 'Brak roznic wzgledem poprzedniego snapshotu oficjalnej bazy.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function auditCurrentCatalog(string $channel = 'gov_full_catalog'): array
    {
        $timestamp = now()->utc()->format('Ymd-His');
        $generatedAt = now()->utc()->toIso8601String();
        $root = storage_path('app/question-integrity/'.$channel);
        $snapshotsDir = $root.'/snapshots';
        $reportsDir = $root.'/reports';
        $latestSnapshotPath = $root.'/latest-snapshot.jsonl';
        $latestSnapshotMetaPath = $root.'/latest-snapshot-meta.json';
        $latestReportPath = $root.'/latest-report.json';
        $snapshotPath = $snapshotsDir.'/'.$timestamp.'-snapshot.jsonl';
        $snapshotMetaPath = $snapshotsDir.'/'.$timestamp.'-snapshot-meta.json';
        $reportPath = $reportsDir.'/'.$timestamp.'-report.json';
        $changesPath = $reportsDir.'/'.$timestamp.'-changes.jsonl';

        File::ensureDirectoryExists($snapshotsDir);
        File::ensureDirectoryExists($reportsDir);

        $currentSnapshot = $this->buildSnapshot();
        $previousSnapshotMeta = $this->loadSnapshotMeta($latestSnapshotMetaPath);
        $comparison = $this->compareSnapshots($latestSnapshotPath, $previousSnapshotMeta, $currentSnapshot);

        $snapshotMetaPayload = [
            'channel' => $channel,
            'generated_at' => $generatedAt,
            'questions_total' => count($currentSnapshot),
        ];

        $reportPayload = [
            'channel' => $channel,
            'generated_at' => $generatedAt,
            'baseline' => ! File::exists($latestSnapshotPath) || $previousSnapshotMeta === null,
            'snapshot_path' => $snapshotPath,
            'latest_snapshot_path' => $latestSnapshotPath,
            'changes_path' => $changesPath,
            'summary' => $comparison['summary'],
            'samples' => $comparison['samples'],
        ];

        $this->writeJsonLines($snapshotPath, array_values($currentSnapshot));
        File::put($snapshotMetaPath, json_encode($snapshotMetaPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($reportPath, json_encode($reportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::copy($snapshotPath, $latestSnapshotPath);
        File::put($latestSnapshotMetaPath, json_encode($snapshotMetaPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($latestReportPath, json_encode($reportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->writeJsonLines($changesPath, $comparison['changes']);

        return [
            'channel' => $channel,
            'generated_at' => $generatedAt,
            'baseline' => ! File::exists($latestSnapshotPath) || $previousSnapshotMeta === null,
            'snapshot_path' => $snapshotPath,
            'report_path' => $reportPath,
            'changes_path' => $changesPath,
            'summary' => $comparison['summary'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function buildSnapshot(): array
    {
        $rows = DB::table('questions as q')
            ->join('license_categories as lc', 'lc.id', '=', 'q.license_category_id')
            ->leftJoin('question_topics as qt', 'qt.id', '=', 'q.question_topic_id')
            ->select([
                'q.id',
                'q.external_id',
                'q.prompt',
                'q.explanation',
                'q.option_a',
                'q.option_b',
                'q.option_c',
                'q.correct_answer',
                'q.question_type',
                'q.points',
                'q.difficulty',
                'q.is_active',
                'q.metadata',
                'lc.code as category_code',
                'qt.key as topic_key',
            ])
            ->where(function ($query): void {
                $query
                    ->where('q.source', 'government-catalog')
                    ->orWhere('q.source', 'gov.pl-mi')
                    ->orWhere('q.source', 'like', 'gov%');
            })
            ->orderBy('lc.code')
            ->orderBy('q.external_id')
            ->orderBy('q.id')
            ->cursor();

        $snapshot = [];

        foreach ($rows as $row) {
            $metadata = is_array($row->metadata) ? $row->metadata : json_decode((string) ($row->metadata ?? '{}'), true);
            $metadata = is_array($metadata) ? $metadata : [];
            $governmentQuestionId = trim((string) ($metadata['government_question_id'] ?? $row->external_id ?? ''));
            $identity = sprintf('%s:%s', (string) $row->category_code, $governmentQuestionId !== '' ? $governmentQuestionId : (string) $row->id);

            $record = [
                'identity' => $identity,
                'category_code' => (string) $row->category_code,
                'government_question_id' => $governmentQuestionId,
                'external_id' => trim((string) ($row->external_id ?? '')),
                'prompt' => (string) $row->prompt,
                'explanation' => (string) ($row->explanation ?? ''),
                'option_a' => (string) $row->option_a,
                'option_b' => (string) $row->option_b,
                'option_c' => (string) ($row->option_c ?? ''),
                'correct_answer' => (string) $row->correct_answer,
                'question_type' => (string) $row->question_type,
                'points' => (int) $row->points,
                'difficulty' => (int) $row->difficulty,
                'is_active' => (bool) $row->is_active,
                'topic_key' => (string) ($row->topic_key ?? ''),
                'structure_scope' => (string) ($metadata['structure_scope'] ?? ''),
                'categories_original' => $this->normalizeCategoriesOriginal($metadata['categories_original'] ?? []),
                'main_media_original' => trim((string) ($metadata['main_media_original'] ?? '')),
                'pjm_question' => trim((string) data_get($metadata, 'pjm.question', '')),
            ];

            $record['signature'] = hash('sha256', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $snapshot[$identity] = $record;
        }

        ksort($snapshot);

        return $snapshot;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadSnapshotMeta(string $metaPath): ?array
    {
        if (! File::exists($metaPath)) {
            return null;
        }

        $decoded = json_decode((string) File::get($metaPath), true);

        if (! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>|null  $previousSnapshotMeta
     * @param  array<string, array<string, mixed>>  $currentSnapshot
     * @return array{summary:array<string,mixed>,samples:array<string,mixed>,changes:array<int,array<string,mixed>>}
     */
    protected function compareSnapshots(string $previousSnapshotPath, ?array $previousSnapshotMeta, array $currentSnapshot): array
    {
        if (! File::exists($previousSnapshotPath) || $previousSnapshotMeta === null) {
            return [
                'summary' => [
                    'previous_generated_at' => null,
                    'current_total' => count($currentSnapshot),
                    'previous_total' => 0,
                    'unchanged_total' => 0,
                    'added_total' => count($currentSnapshot),
                    'removed_total' => 0,
                    'changed_total' => 0,
                    'critical_total' => 0,
                    'field_changes' => [],
                ],
                'samples' => [
                    'added' => array_slice(array_values($currentSnapshot), 0, 10),
                    'removed' => [],
                    'changed' => [],
                ],
                'changes' => [],
            ];
        }

        $changes = [];
        $fieldChanges = [];
        $unchanged = 0;
        $seenCurrent = [];

        $handle = fopen($previousSnapshotPath, 'rb');

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Nie udalo sie otworzyc poprzedniego snapshotu: %s', $previousSnapshotPath));
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $previousQuestion = json_decode($line, true);

                if (! is_array($previousQuestion) || ! filled($previousQuestion['identity'] ?? null)) {
                    continue;
                }

                $identity = (string) $previousQuestion['identity'];
                $currentQuestion = $currentSnapshot[$identity] ?? null;

                if ($currentQuestion === null) {
                    $changes[] = [
                        'status' => 'removed',
                        'severity' => 'critical',
                        'identity' => $identity,
                        'category_code' => $previousQuestion['category_code'] ?? null,
                        'government_question_id' => $previousQuestion['government_question_id'] ?? null,
                        'fields_changed' => [],
                        'previous' => $previousQuestion,
                    ];

                    continue;
                }

                $seenCurrent[$identity] = true;

                if (($previousQuestion['signature'] ?? null) === $currentQuestion['signature']) {
                    $unchanged++;

                    continue;
                }

                $diff = $this->diffQuestion($previousQuestion, $currentQuestion);
                foreach ($diff['fields_changed'] as $field) {
                    $fieldChanges[$field] = ($fieldChanges[$field] ?? 0) + 1;
                }

                $changes[] = [
                    'status' => 'changed',
                    'severity' => $diff['severity'],
                    'identity' => $identity,
                    'category_code' => $currentQuestion['category_code'],
                    'government_question_id' => $currentQuestion['government_question_id'],
                    'fields_changed' => $diff['fields_changed'],
                    'previous' => $diff['previous'],
                    'current' => $diff['current'],
                ];
            }
        } finally {
            fclose($handle);
        }

        foreach ($currentSnapshot as $identity => $currentQuestion) {
            if (isset($seenCurrent[$identity])) {
                continue;
            }

            $changes[] = [
                'status' => 'added',
                'severity' => 'info',
                'identity' => $identity,
                'category_code' => $currentQuestion['category_code'],
                'government_question_id' => $currentQuestion['government_question_id'],
                'fields_changed' => [],
                'current' => $currentQuestion,
            ];
        }

        $added = array_values(array_filter($changes, static fn (array $change): bool => $change['status'] === 'added'));
        $removed = array_values(array_filter($changes, static fn (array $change): bool => $change['status'] === 'removed'));
        $changed = array_values(array_filter($changes, static fn (array $change): bool => $change['status'] === 'changed'));
        $criticalTotal = count(array_filter($changes, static fn (array $change): bool => $change['severity'] === 'critical'));
        arsort($fieldChanges);

        return [
            'summary' => [
                'previous_generated_at' => $previousSnapshotMeta['generated_at'] ?? null,
                'current_total' => count($currentSnapshot),
                'previous_total' => (int) ($previousSnapshotMeta['questions_total'] ?? 0),
                'unchanged_total' => $unchanged,
                'added_total' => count($added),
                'removed_total' => count($removed),
                'changed_total' => count($changed),
                'critical_total' => $criticalTotal,
                'field_changes' => $fieldChanges,
            ],
            'samples' => [
                'added' => array_slice($added, 0, 10),
                'removed' => array_slice($removed, 0, 10),
                'changed' => array_slice($changed, 0, 10),
            ],
            'changes' => array_values($changes),
        ];
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $current
     * @return array{fields_changed:array<int,string>,severity:string,previous:array<string,mixed>,current:array<string,mixed>}
     */
    protected function diffQuestion(array $previous, array $current): array
    {
        $fieldsChanged = [];
        $previousSubset = [];
        $currentSubset = [];

        foreach (self::TRACKED_FIELDS as $field) {
            $previousValue = $previous[$field] ?? null;
            $currentValue = $current[$field] ?? null;

            if ($previousValue === $currentValue) {
                continue;
            }

            $fieldsChanged[] = $field;
            $previousSubset[$field] = $previousValue;
            $currentSubset[$field] = $currentValue;
        }

        $severity = count(array_intersect($fieldsChanged, self::CRITICAL_FIELDS)) > 0
            ? 'critical'
            : 'review';

        return [
            'fields_changed' => $fieldsChanged,
            'severity' => $severity,
            'previous' => $previousSubset,
            'current' => $currentSubset,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeCategoriesOriginal(mixed $categoriesOriginal): array
    {
        $values = [];

        foreach ((array) $categoriesOriginal as $value) {
            $normalized = trim((string) $value);

            if ($normalized === '') {
                continue;
            }

            $values[] = strtoupper($normalized);
        }

        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function writeJsonLines(string $path, array $rows): void
    {
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Nie udalo sie otworzyc pliku do zapisu: %s', $path));
        }

        try {
            foreach ($rows as $row) {
                fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            }
        } finally {
            fclose($handle);
        }
    }
}
