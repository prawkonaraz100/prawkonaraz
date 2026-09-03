<?php

namespace App\Support;

use App\Models\LegalAct;
use App\Models\LegalUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegalUnitManifestImporter
{
    /**
     * @return array<string, mixed>
     */
    public function import(string $manifestPath, bool $write = false, int $sampleLimit = 10): array
    {
        $manifestPath = $this->normalizePath($manifestPath);
        $manifest = $this->loadManifest($manifestPath);
        $actPayload = $this->normalizeActPayload($manifest['act'] ?? null);
        $unitPayloads = $this->normalizeUnitPayloads($manifest['units'] ?? null, $actPayload);
        $sampleLimit = max(0, $sampleLimit);

        $operation = fn (): array => $this->importManifest($manifestPath, $actPayload, $unitPayloads, $write, $sampleLimit);

        return $write ? DB::transaction($operation) : $operation();
    }

    /**
     * @param  array<string, mixed>  $actPayload
     * @param  list<array<string, mixed>>  $unitPayloads
     * @return array<string, mixed>
     */
    protected function importManifest(
        string $manifestPath,
        array $actPayload,
        array $unitPayloads,
        bool $write,
        int $sampleLimit,
    ): array {
        $existingAct = LegalAct::query()
            ->where('slug', $actPayload['slug'])
            ->first();
        $act = $existingAct;
        $actAttributes = $existingAct instanceof LegalAct
            ? $this->attributesPreservingVerifiedStatus($existingAct, $actPayload['attributes'])
            : $actPayload['attributes'];
        $actChanged = $existingAct instanceof LegalAct
            ? $this->modelWouldChange($existingAct, $actAttributes)
            : false;

        $report = [
            'mode' => $write ? 'write' : 'preview',
            'manifest_path' => $manifestPath,
            'act' => [
                'slug' => $actPayload['slug'],
                'created' => false,
                'updated' => false,
                'would_create' => ! $write && ! $existingAct instanceof LegalAct,
                'would_update' => ! $write && $existingAct instanceof LegalAct && $actChanged,
            ],
            'input_units' => count($unitPayloads),
            'created_units' => 0,
            'updated_units' => 0,
            'unchanged_units' => 0,
            'would_create_units' => 0,
            'would_update_units' => 0,
            'invalid_units' => 0,
            'invalid_sample' => [],
        ];

        if ($write) {
            $act = LegalAct::query()->updateOrCreate(
                ['slug' => $actPayload['slug']],
                $actAttributes,
            );
            $report['act']['created'] = ! $existingAct instanceof LegalAct;
            $report['act']['updated'] = $existingAct instanceof LegalAct && $actChanged;
        }

        $knownManifestPaths = collect($unitPayloads)
            ->pluck('canonical_path')
            ->flip()
            ->all();
        $writtenUnitsByCanonicalPath = [];

        foreach ($this->sortUnitsForImport($unitPayloads) as $unitPayload) {
            $parentCanonicalPath = $unitPayload['parent_canonical_path'];
            $existingUnit = $act instanceof LegalAct
                ? $this->findExistingUnit($act, $unitPayload)
                : null;
            $parentUnit = $parentCanonicalPath !== null && $act instanceof LegalAct
                ? $this->findParentUnit($act, $parentCanonicalPath, $writtenUnitsByCanonicalPath)
                : null;

            if (
                $parentCanonicalPath !== null
                && $parentUnit === null
                && ! array_key_exists($parentCanonicalPath, $knownManifestPaths)
            ) {
                $report['invalid_units']++;
                $this->pushSample($report['invalid_sample'], [
                    'canonical_path' => $unitPayload['canonical_path'],
                    'label' => $unitPayload['attributes']['label'],
                    'reason' => 'missing_parent',
                    'parent_canonical_path' => $parentCanonicalPath,
                ], $sampleLimit);

                continue;
            }

            $attributes = $unitPayload['attributes'];
            $attributes['parent_legal_unit_id'] = $parentUnit?->getKey();

            if ($existingUnit instanceof LegalUnit) {
                $attributes = $this->attributesPreservingVerifiedStatus($existingUnit, $attributes);
                $attributes = $this->attributesPreservingEditorialFields($existingUnit, $attributes);
            }

            if (! $write) {
                if (! $existingUnit instanceof LegalUnit) {
                    $report['would_create_units']++;

                    continue;
                }

                if ($this->modelWouldChange($existingUnit, $attributes)) {
                    $report['would_update_units']++;
                } else {
                    $report['unchanged_units']++;
                }

                continue;
            }

            if (! $act instanceof LegalAct) {
                throw new InvalidArgumentException('Nie mozna zapisac jednostek bez aktu prawnego.');
            }

            $attributes['legal_act_id'] = $act->getKey();

            if ($existingUnit instanceof LegalUnit) {
                if ($this->modelWouldChange($existingUnit, $attributes)) {
                    $existingUnit->fill($attributes);
                    $existingUnit->save();
                    $report['updated_units']++;
                } else {
                    $report['unchanged_units']++;
                }

                $unit = $existingUnit->fresh();
            } else {
                $unit = LegalUnit::query()->create($attributes);
                $report['created_units']++;
            }

            if ($unit instanceof LegalUnit) {
                $writtenUnitsByCanonicalPath[$unitPayload['canonical_path']] = $unit;
            }
        }

        return $report;
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException('Podaj sciezke do manifestu.');
        }

        $resolvedPath = realpath($path);

        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            throw new InvalidArgumentException(sprintf('Nie znaleziono manifestu: %s', $path));
        }

        return $resolvedPath;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadManifest(string $manifestPath): array
    {
        $payload = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Manifest musi byc poprawnym plikiem JSON.');
        }

        return $payload;
    }

    /**
     * @return array{slug:string,attributes:array<string, mixed>}
     */
    protected function normalizeActPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Manifest musi zawierac obiekt act.');
        }

        $slug = $this->requiredString($payload, 'slug', 'act.slug');
        $title = $this->requiredString($payload, 'title', 'act.title');
        $sourceUrl = $this->requiredString($payload, 'source_url', 'act.source_url');

        return [
            'slug' => $slug,
            'attributes' => [
                'title' => $title,
                'short_title' => $this->nullableString($payload['short_title'] ?? null),
                'publisher' => $this->nullableString($payload['publisher'] ?? null),
                'source_url' => $sourceUrl,
                'eli_url' => $this->nullableString($payload['eli_url'] ?? null),
                'isap_url' => $this->nullableString($payload['isap_url'] ?? null),
                'effective_from' => $this->nullableDate($payload['effective_from'] ?? null),
                'last_checked_at' => $this->nullableDateTime($payload['last_checked_at'] ?? null),
                'status' => $this->nullableString($payload['status'] ?? null) ?: LegalAct::STATUS_NEEDS_REVIEW,
            ],
        ];
    }

    /**
     * @param  array{slug:string,attributes:array<string, mixed>}  $actPayload
     * @return list<array<string, mixed>>
     */
    protected function normalizeUnitPayloads(mixed $payload, array $actPayload): array
    {
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Manifest musi zawierac tablice units.');
        }

        $units = [];
        $paths = [];

        foreach (array_values($payload) as $index => $unitPayload) {
            if (! is_array($unitPayload)) {
                throw new InvalidArgumentException(sprintf('units.%d musi byc obiektem.', $index));
            }

            $canonicalPath = $this->requiredString($unitPayload, 'canonical_path', "units.{$index}.canonical_path");

            if (isset($paths[$canonicalPath])) {
                throw new InvalidArgumentException(sprintf('Duplikat canonical_path w manifeście: %s', $canonicalPath));
            }

            $paths[$canonicalPath] = true;
            $label = $this->requiredString($unitPayload, 'label', "units.{$index}.label");
            $slug = $this->nullableString($unitPayload['slug'] ?? null)
                ?: Str::slug($label.' '.$canonicalPath);

            if ($slug === '') {
                throw new InvalidArgumentException(sprintf('Nie mozna wygenerowac slug dla jednostki %s.', $canonicalPath));
            }

            $units[] = [
                'canonical_path' => $canonicalPath,
                'parent_canonical_path' => $this->nullableString($unitPayload['parent_canonical_path'] ?? null),
                'attributes' => [
                    'type' => $this->requiredString($unitPayload, 'type', "units.{$index}.type"),
                    'label' => $label,
                    'canonical_path' => $canonicalPath,
                    'slug' => $slug,
                    'title' => $this->requiredString($unitPayload, 'title', "units.{$index}.title"),
                    'summary' => $this->nullableString($unitPayload['summary'] ?? null),
                    'official_excerpt' => $this->nullableString($unitPayload['official_excerpt'] ?? null),
                    'source_url' => $this->nullableString($unitPayload['source_url'] ?? null)
                        ?: (string) $actPayload['attributes']['source_url'],
                    'effective_from' => $this->nullableDate($unitPayload['effective_from'] ?? null),
                    'last_checked_at' => $this->nullableDateTime($unitPayload['last_checked_at'] ?? null)
                        ?: $actPayload['attributes']['last_checked_at'],
                    'status' => $this->nullableString($unitPayload['status'] ?? null) ?: LegalUnit::STATUS_NEEDS_REVIEW,
                ],
            ];
        }

        return $units;
    }

    /**
     * @param  list<array<string, mixed>>  $units
     * @return list<array<string, mixed>>
     */
    protected function sortUnitsForImport(array $units): array
    {
        usort($units, function (array $left, array $right): int {
            $leftPath = (string) $left['canonical_path'];
            $rightPath = (string) $right['canonical_path'];
            $depthComparison = substr_count($leftPath, '/') <=> substr_count($rightPath, '/');

            return $depthComparison !== 0
                ? $depthComparison
                : strnatcasecmp($leftPath, $rightPath);
        });

        return $units;
    }

    /**
     * @param  array<string, mixed>  $unitPayload
     */
    protected function findExistingUnit(LegalAct $act, array $unitPayload): ?LegalUnit
    {
        $canonicalPath = (string) $unitPayload['canonical_path'];
        $slug = (string) $unitPayload['attributes']['slug'];

        return LegalUnit::query()
            ->where('legal_act_id', $act->getKey())
            ->where('canonical_path', $canonicalPath)
            ->first()
            ?? LegalUnit::query()
                ->where('legal_act_id', $act->getKey())
                ->where('slug', $slug)
                ->first();
    }

    /**
     * @param  array<string, LegalUnit>  $writtenUnitsByCanonicalPath
     */
    protected function findParentUnit(
        LegalAct $act,
        string $parentCanonicalPath,
        array $writtenUnitsByCanonicalPath,
    ): ?LegalUnit {
        if (isset($writtenUnitsByCanonicalPath[$parentCanonicalPath])) {
            return $writtenUnitsByCanonicalPath[$parentCanonicalPath];
        }

        return LegalUnit::query()
            ->where('legal_act_id', $act->getKey())
            ->where('canonical_path', $parentCanonicalPath)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function modelWouldChange(LegalAct|LegalUnit $model, array $attributes): bool
    {
        $copy = clone $model;
        $copy->fill($attributes);

        return $copy->isDirty(array_keys($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function attributesPreservingVerifiedStatus(LegalAct|LegalUnit $model, array $attributes): array
    {
        if (
            ($model->status ?? null) === LegalAct::STATUS_VERIFIED
            && ($attributes['status'] ?? null) === LegalAct::STATUS_NEEDS_REVIEW
        ) {
            $attributes['status'] = LegalAct::STATUS_VERIFIED;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function attributesPreservingEditorialFields(LegalUnit $unit, array $attributes): array
    {
        $incomingLabel = trim((string) ($attributes['label'] ?? ''));
        $incomingTitle = trim((string) ($attributes['title'] ?? ''));
        $existingTitle = trim((string) $unit->title);

        if ($incomingTitle !== '' && $incomingTitle === $incomingLabel && $existingTitle !== '') {
            $attributes['title'] = $existingTitle;
        }

        if (blank($attributes['summary'] ?? null) && filled($unit->summary)) {
            $attributes['summary'] = $unit->summary;
        }

        if (blank($attributes['official_excerpt'] ?? null) && filled($unit->official_excerpt)) {
            $attributes['official_excerpt'] = $unit->official_excerpt;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function requiredString(array $payload, string $key, string $label): string
    {
        $value = $this->nullableString($payload[$key] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Brak wymaganego pola: %s.', $label));
        }

        return $value;
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function nullableDate(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        return $value !== null ? Carbon::parse($value)->toDateString() : null;
    }

    protected function nullableDateTime(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        return $value !== null ? Carbon::parse($value)->toDateTimeString() : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $samples
     * @param  array<string, mixed>  $sample
     */
    protected function pushSample(array &$samples, array $sample, int $sampleLimit): void
    {
        if ($sampleLimit <= 0 || count($samples) >= $sampleLimit) {
            return;
        }

        $samples[] = $sample;
    }
}
