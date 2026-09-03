<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelation;
use App\Models\QuestionSeoTopic;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class ImportQuestionSeoRelationsCommand extends Command
{
    protected $signature = 'questions:import-seo-relations
        {--path= : Directory containing question-catalog.jsonl and relations.jsonl}
        {--write : Persist the import; without this flag only validate and report}
        {--automatic-threshold= : Override automatic publication score threshold}
        {--json : Print the report as JSON}';

    protected $description = 'Validate and import the public-question SEO taxonomy and relation graph.';

    /** @var array<string, int> */
    protected array $report = [];

    public function handle(): int
    {
        $directory = rtrim(trim((string) $this->option('path')), '/\\');

        if ($directory === '') {
            $this->error('Podaj katalog przez --path.');

            return self::FAILURE;
        }

        $catalogPath = $directory.DIRECTORY_SEPARATOR.'question-catalog.jsonl';
        $relationsPath = $directory.DIRECTORY_SEPARATOR.'relations.jsonl';

        if (! File::isFile($catalogPath) || ! File::isFile($relationsPath)) {
            $this->error('Katalog musi zawierać question-catalog.jsonl oraz relations.jsonl.');

            return self::FAILURE;
        }

        $thresholdOption = $this->option('automatic-threshold');
        $automaticThreshold = is_numeric($thresholdOption)
            ? (float) $thresholdOption
            : (float) config('question_relations.automatic_score_threshold', 0.50);

        if ($automaticThreshold < 0 || $automaticThreshold > 1) {
            $this->error('Próg automatycznej publikacji musi mieścić się w zakresie 0–1.');

            return self::FAILURE;
        }

        $write = (bool) $this->option('write');

        try {
            $catalogRows = iterator_to_array($this->jsonLines($catalogPath), false);
            $catalogCounts = collect($catalogRows)
                ->countBy(fn (array $row): string => trim((string) ($row['source_id'] ?? '')));
            $duplicateIds = $catalogCounts
                ->filter(fn (int $count, string $id): bool => $id === '' || $count > 1)
                ->keys()
                ->flip();
            $allValidCatalogRows = collect($catalogRows)
                ->filter(fn (array $row): bool => $this->validCatalogRow($row))
                ->values();
            $validCatalogRows = $allValidCatalogRows
                ->reject(fn (array $row): bool => $duplicateIds->has((string) $row['source_id']))
                ->keyBy(fn (array $row): string => (string) $row['source_id']);

            $explanations = QuestionPublicExplanation::query()
                ->published()
                ->with('question')
                ->get()
                ->keyBy(fn (QuestionPublicExplanation $explanation): string => (string) $explanation->external_id);
            $membershipResolution = $this->resolveMembershipCatalogRows(
                $allValidCatalogRows,
                $explanations,
                $duplicateIds,
            );
            $membershipCatalogRows = $membershipResolution['rows'];
            $selectedCatalogRowsBySourceId = $membershipCatalogRows
                ->keyBy(fn (array $row): string => (string) $row['source_id']);
            $explanationsBySourceId = $membershipCatalogRows
                ->mapWithKeys(function (array $row, mixed $externalId) use ($explanations): array {
                    $explanation = $explanations->get((string) $externalId);

                    return $explanation instanceof QuestionPublicExplanation
                        ? [(string) $row['source_id'] => $explanation]
                        : [];
                });

            $this->report = [
                'mode_write' => $write ? 1 : 0,
                'catalog_rows' => count($catalogRows),
                'catalog_unique_valid' => $validCatalogRows->count(),
                'duplicate_source_ids' => $duplicateIds->count(),
                'catalog_missing_explanation' => $validCatalogRows->keys()->diff($explanations->keys())->count(),
                'memberships_resolved' => $membershipCatalogRows->count(),
                'memberships_recovered_alias' => $membershipResolution['alias_count'],
                'memberships_recovered_duplicate' => $membershipResolution['duplicate_count'],
                'memberships_unresolved' => $membershipResolution['unresolved_count'],
                'topics_written' => 0,
                'memberships_written' => 0,
                'editorial_relations_written' => 0,
                'relation_rows' => 0,
                'relations_quarantined' => 0,
                'relations_alias_resolved' => 0,
                'relations_duplicate_resolved' => 0,
                'relations_missing_explanation' => 0,
                'relations_invalid' => 0,
                'relations_candidate' => 0,
                'relations_automatic' => 0,
                'relations_written' => 0,
            ];

            $operation = function () use (
                $allValidCatalogRows,
                $membershipCatalogRows,
                $selectedCatalogRowsBySourceId,
                $duplicateIds,
                $explanations,
                $explanationsBySourceId,
                $relationsPath,
                $automaticThreshold,
                $write,
            ): void {
                $topics = $this->importTopicsAndMemberships(
                    $allValidCatalogRows,
                    $membershipCatalogRows,
                    $explanations,
                    $write,
                );
                $this->importEditorialRelations($explanations, $write);
                $this->importGraphRelations(
                    $relationsPath,
                    $selectedCatalogRowsBySourceId,
                    $duplicateIds,
                    $explanationsBySourceId,
                    $topics,
                    $automaticThreshold,
                    $write,
                );
            };

            if ($write) {
                DB::transaction($operation);
            } else {
                $operation();
            }
        } catch (JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Metryka', 'Wartość'], collect($this->report)
                ->map(fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all());
            $this->info($write ? 'Import zapisany.' : 'Podgląd zakończony. Baza nie została zmieniona.');
        }

        return self::SUCCESS;
    }

    /**
     * Resolve catalog rows to public explanations without guessing:
     * exact, unique IDs retain the V1 behavior, while aliases and duplicate
     * source IDs require a unique normalized prompt match.
     *
     * @param  Collection<int, array<string, mixed>>  $catalogRows
     * @param  Collection<string, QuestionPublicExplanation>  $explanations
     * @param  Collection<string, int>  $duplicateIds
     * @return array{rows: Collection<string, array<string, mixed>>, alias_count: int, duplicate_count: int, unresolved_count: int}
     */
    protected function resolveMembershipCatalogRows(
        Collection $catalogRows,
        Collection $explanations,
        Collection $duplicateIds,
    ): array {
        $catalogRowsBySourceId = $catalogRows
            ->groupBy(fn (array $row): string => (string) $row['source_id']);
        $externalIds = $explanations
            ->keys()
            ->map(fn (mixed $externalId): string => (string) $externalId)
            ->values();
        $questionPromptsByExternalId = Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereIn('external_id', $externalIds->all())
            ->get(['external_id', 'prompt'])
            ->groupBy(fn (Question $question): string => (string) $question->external_id)
            ->map(fn (Collection $questions): Collection => $questions
                ->pluck('prompt')
                ->map(fn (mixed $prompt): string => $this->normalizePrompt($prompt))
                ->filter()
                ->unique()
                ->values());
        $resolved = collect();
        $aliasCount = 0;
        $duplicateCount = 0;

        foreach ($explanations as $externalId => $explanation) {
            $externalId = (string) $externalId;
            $sourceId = $catalogRowsBySourceId->has($externalId)
                ? $externalId
                : Str::afterLast($externalId, ':');
            $candidates = $catalogRowsBySourceId->get($sourceId, collect());

            if ($candidates->isEmpty()) {
                continue;
            }

            if ($externalId === $sourceId && $candidates->count() === 1) {
                $selected = $candidates->first();
            } else {
                $prompts = $questionPromptsByExternalId->get($externalId, collect());
                if ($explanation->question instanceof Question) {
                    $prompts = $prompts
                        ->push($this->normalizePrompt($explanation->question->prompt))
                        ->filter()
                        ->unique()
                        ->values();
                }

                $matches = $candidates
                    ->filter(fn (array $row): bool => $prompts->containsStrict(
                        $this->normalizePrompt($row['question'] ?? ''),
                    ))
                    ->values();
                $selected = $matches->count() === 1 ? $matches->first() : null;
            }

            if (! is_array($selected)) {
                continue;
            }

            $resolved->put($externalId, $selected);

            if ($externalId !== $sourceId) {
                $aliasCount++;
            }

            if ($duplicateIds->has($sourceId)) {
                $duplicateCount++;
            }
        }

        return [
            'rows' => $resolved,
            'alias_count' => $aliasCount,
            'duplicate_count' => $duplicateCount,
            'unresolved_count' => max(0, $explanations->count() - $resolved->count()),
        ];
    }

    protected function normalizePrompt(mixed $prompt): string
    {
        $plain = html_entity_decode(strip_tags((string) $prompt), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\[(?:\/)?[a-z0-9_-]+\]/iu', ' ', $plain) ?? $plain;
        $plain = str_replace(['**', '__', '`'], ' ', $plain);
        $plain = Str::lower(Str::ascii($plain));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $plain));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $catalogRows
     * @param  Collection<string, array<string, mixed>>  $membershipCatalogRows
     * @param  Collection<string, QuestionPublicExplanation>  $explanations
     * @return array<string, QuestionSeoTopic|null>
     */
    protected function importTopicsAndMemberships(
        $catalogRows,
        $membershipCatalogRows,
        $explanations,
        bool $write,
    ): array {
        $resolvedCatalogRows = $membershipCatalogRows->values();
        $primaryDefinitions = $catalogRows
            ->mapWithKeys(fn (array $row): array => [(string) $row['seo_primary'] => (string) $row['seo_primary_label']]);
        $secondaryDefinitions = $catalogRows
            ->groupBy('seo_secondary')
            ->map(function ($rows, string $key) use ($resolvedCatalogRows): array {
                $row = $rows->first();

                return [
                    'key' => $key,
                    'label' => (string) $row['seo_secondary_label'],
                    'parent_key' => (string) $row['seo_primary'],
                    'count' => $resolvedCatalogRows->where('seo_secondary', $key)->count(),
                ];
            });
        $topics = [];

        foreach ($primaryDefinitions as $key => $label) {
            $topics['primary:'.$key] = $write
                ? QuestionSeoTopic::query()->updateOrCreate(
                    ['key' => 'primary:'.$key],
                    [
                        'parent_id' => null,
                        'slug' => 'obszar-'.$key,
                        'label' => $label,
                        'description' => "Zbiór pytań egzaminacyjnych z obszaru: {$label}.",
                        'question_count' => $resolvedCatalogRows->where('seo_primary', $key)->count(),
                        'is_indexable' => false,
                    ],
                )
                : null;
            $this->report['topics_written']++;
        }

        foreach ($secondaryDefinitions as $definition) {
            $parent = $topics['primary:'.$definition['parent_key']] ?? null;
            $isIndexable = $definition['count'] >= (int) config('question_relations.indexable_topic_minimum_questions', 10);
            $topics['secondary:'.$definition['key']] = $write
                ? QuestionSeoTopic::query()->updateOrCreate(
                    ['key' => 'secondary:'.$definition['key']],
                    [
                        'parent_id' => $parent?->getKey(),
                        'slug' => $definition['key'],
                        'label' => $definition['label'],
                        'description' => "Oficjalne pytania na prawo jazdy dotyczące tematu: {$definition['label']}.",
                        'question_count' => $definition['count'],
                        'is_indexable' => $isIndexable,
                    ],
                )
                : null;
            $this->report['topics_written']++;
        }

        foreach ($membershipCatalogRows as $externalId => $row) {
            $explanation = $explanations->get((string) $externalId);

            if (! $explanation instanceof QuestionPublicExplanation) {
                continue;
            }

            if ($write) {
                DB::table('question_seo_topic_memberships')->updateOrInsert(
                    ['question_public_explanation_id' => $explanation->getKey()],
                    [
                        'question_seo_topic_id' => $topics['secondary:'.$row['seo_secondary']]->getKey(),
                        'source' => QuestionRelation::SOURCE_GRAPH,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            $this->report['memberships_written']++;
        }

        return $topics;
    }

    /** @param  Collection<string, QuestionPublicExplanation>  $explanations */
    protected function importEditorialRelations($explanations, bool $write): void
    {
        foreach ($explanations as $source) {
            foreach ((array) $source->related_questions as $position => $rawRelation) {
                if (! is_array($rawRelation)) {
                    continue;
                }

                $target = $explanations->get(trim((string) ($rawRelation['external_id'] ?? '')));
                $description = trim((string) ($rawRelation['description'] ?? ''));

                if (! $target instanceof QuestionPublicExplanation || $target->is($source) || $description === '') {
                    continue;
                }

                [$left, $right] = $source->getKey() < $target->getKey()
                    ? [$source, $target]
                    : [$target, $source];
                $sourceIsLeft = $source->is($left);

                if ($write) {
                    $relation = QuestionRelation::query()->firstOrNew([
                        'left_explanation_id' => $left->getKey(),
                        'right_explanation_id' => $right->getKey(),
                    ]);
                    $relation->fill([
                        'relation_type' => $relation->exists ? $relation->relation_type : 'tematyczne',
                        'source' => QuestionRelation::SOURCE_EDITORIAL,
                        'status' => QuestionRelation::STATUS_VERIFIED,
                        'display_order' => $relation->exists
                            ? min((int) $relation->display_order, (int) $position)
                            : (int) $position,
                        $sourceIsLeft ? 'anchor_left_to_right' : 'anchor_right_to_left' => $description,
                        'reviewed_at' => $source->last_reviewed_at ?? $source->updated_at,
                    ]);
                    $relation->save();
                }

                $this->report['editorial_relations_written']++;
            }
        }
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $catalogRows
     * @param  Collection<string, int>  $duplicateIds
     * @param  Collection<string, QuestionPublicExplanation>  $explanations
     * @param  array<string, QuestionSeoTopic|null>  $topics
     */
    protected function importGraphRelations(
        string $relationsPath,
        $catalogRows,
        $duplicateIds,
        $explanations,
        array $topics,
        float $automaticThreshold,
        bool $write,
    ): void {
        foreach ($this->jsonLines($relationsPath) as $row) {
            $this->report['relation_rows']++;
            $aId = trim((string) ($row['a_id'] ?? ''));
            $bId = trim((string) ($row['b_id'] ?? ''));

            if ($aId === '' || $bId === '' || $aId === $bId || ! isset($row['typ'])) {
                $this->report['relations_invalid']++;

                continue;
            }

            $hasDuplicateEndpoint = $duplicateIds->has($aId) || $duplicateIds->has($bId);
            $duplicateEndpointsMatch = $this->relationEndpointMatchesSelectedTopic(
                $aId,
                (string) ($row['podtemat_a'] ?? ''),
                $duplicateIds,
                $catalogRows,
            ) && $this->relationEndpointMatchesSelectedTopic(
                $bId,
                (string) ($row['podtemat_b'] ?? ''),
                $duplicateIds,
                $catalogRows,
            );

            if ($hasDuplicateEndpoint && ! $duplicateEndpointsMatch) {
                $this->report['relations_quarantined']++;

                continue;
            }

            $a = $explanations->get($aId);
            $b = $explanations->get($bId);

            if (! $a instanceof QuestionPublicExplanation || ! $b instanceof QuestionPublicExplanation) {
                $this->report['relations_missing_explanation']++;

                continue;
            }

            if ((string) $a->external_id !== $aId || (string) $b->external_id !== $bId) {
                $this->report['relations_alias_resolved']++;
            }

            if ($hasDuplicateEndpoint) {
                $this->report['relations_duplicate_resolved']++;
            }

            $sameSecondary = (string) ($row['podtemat_a'] ?? '') !== ''
                && (string) ($row['podtemat_a'] ?? '') === (string) ($row['podtemat_b'] ?? '')
                && $catalogRows->has($aId)
                && $catalogRows->has($bId);
            $score = is_numeric($row['wynik'] ?? null) ? (float) $row['wynik'] : null;
            $status = $sameSecondary && $score !== null && $score >= $automaticThreshold
                ? QuestionRelation::STATUS_AUTOMATIC
                : QuestionRelation::STATUS_CANDIDATE;
            $this->report[$status === QuestionRelation::STATUS_AUTOMATIC ? 'relations_automatic' : 'relations_candidate']++;

            if (! $write) {
                $this->report['relations_written']++;

                continue;
            }

            [$left, $right] = $a->getKey() < $b->getKey() ? [$a, $b] : [$b, $a];
            $aIsLeft = $a->is($left);
            $relation = QuestionRelation::query()->firstOrNew([
                'left_explanation_id' => $left->getKey(),
                'right_explanation_id' => $right->getKey(),
            ]);

            if ($relation->exists && $relation->source === QuestionRelation::SOURCE_EDITORIAL) {
                $this->report['relations_written']++;

                continue;
            }

            $preserveReview = $relation->exists && in_array($relation->status, [
                QuestionRelation::STATUS_VERIFIED,
                QuestionRelation::STATUS_REJECTED,
            ], true);
            $relation->fill([
                'relation_type' => (string) $row['typ'],
                'direction' => (string) ($row['kierunek'] ?? 'symetryczna'),
                'source' => QuestionRelation::SOURCE_GRAPH,
                'status' => $preserveReview ? $relation->status : $status,
                'score' => $score,
                'reason' => $row['powod'] ?? null,
                'difference' => $row['roznica'] ?? null,
                'anchor_left_to_right' => $aIsLeft ? ($row['anchor_a_do_b'] ?? null) : ($row['anchor_b_do_a'] ?? null),
                'anchor_right_to_left' => $aIsLeft ? ($row['anchor_b_do_a'] ?? null) : ($row['anchor_a_do_b'] ?? null),
                'review_priority' => Str::ascii((string) ($row['priorytet_weryfikacji'] ?? '')),
                'metadata' => [
                    'common' => $row['wspolne'] ?? [],
                    'metrics' => $row['metryki'] ?? [],
                    'existing_relation' => (bool) ($row['istniejace_powiazanie'] ?? false),
                    'existing_types' => $row['istniejace_typy'] ?? [],
                ],
                'version' => max(1, (int) ($row['wersja'] ?? 1)),
            ]);
            $relation->save();
            $this->report['relations_written']++;
        }
    }

    /** @param Collection<string, array<string, mixed>> $catalogRows */
    protected function relationEndpointMatchesSelectedTopic(
        string $sourceId,
        string $relationTopic,
        Collection $duplicateIds,
        Collection $catalogRows,
    ): bool {
        if (! $duplicateIds->has($sourceId)) {
            return true;
        }

        $selected = $catalogRows->get($sourceId);

        return is_array($selected)
            && $relationTopic !== ''
            && (string) ($selected['seo_secondary'] ?? '') === $relationTopic;
    }

    /** @return \Generator<int, array<string, mixed>> */
    protected function jsonLines(string $path): \Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Nie można otworzyć pliku: {$path}");
        }

        try {
            $lineNumber = 0;

            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                try {
                    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new JsonException("Niepoprawny JSON w {$path}:{$lineNumber}: {$exception->getMessage()}");
                }

                if (! is_array($row)) {
                    throw new RuntimeException("Wiersz {$lineNumber} w {$path} nie jest obiektem JSON.");
                }

                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    /** @param  array<string, mixed>  $row */
    protected function validCatalogRow(array $row): bool
    {
        foreach (['source_id', 'seo_primary', 'seo_primary_label', 'seo_secondary', 'seo_secondary_label'] as $key) {
            if (trim((string) ($row[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
