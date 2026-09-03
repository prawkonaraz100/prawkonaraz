<?php

namespace App\Support;

use App\Models\LegalAct;
use App\Models\LegalSourceCheck;
use App\Models\LegalUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegalUnitAuditService
{
    public function __construct(
        protected LegalUnitManifestBuilder $manifestBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(
        string $actSlug,
        ?string $source = null,
        ?string $sourceUrl = null,
        bool $write = false,
        bool $includeVerified = false,
        int $sampleLimit = 10,
    ): array {
        $act = LegalAct::query()
            ->where('slug', trim($actSlug))
            ->first();

        if (! $act instanceof LegalAct) {
            throw new InvalidArgumentException(sprintf('Nie znaleziono aktu prawnego: %s.', $actSlug));
        }

        $inferredSource = $this->inferTextHtmlSource($act);
        $source = $this->filledString($source) ?: $inferredSource;

        if ($source === null) {
            throw new InvalidArgumentException('Nie znaleziono zrodla text.html. Podaj --source.');
        }

        $baseSourceUrl = $this->filledString($sourceUrl)
            ?: ($this->isUrl($source) ? $source : ($inferredSource ?: (string) $act->source_url));
        $sampleLimit = max(0, $sampleLimit);

        $manifestReport = $this->manifestBuilder->build($source, [
            'act_slug' => $act->slug,
            'title' => $act->title,
            'short_title' => $act->short_title,
            'source_url' => $baseSourceUrl,
            'eli_url' => $act->eli_url,
            'isap_url' => $act->isap_url,
            'effective_from' => $act->effective_from?->toDateString(),
            'last_checked_at' => now()->toDateTimeString(),
            'act_status' => $act->status,
            'unit_status' => LegalUnit::STATUS_NEEDS_REVIEW,
        ]);
        $manifestUnits = collect($manifestReport['manifest']['units'] ?? [])
            ->mapWithKeys(fn (array $unit): array => [(string) $unit['canonical_path'] => $unit])
            ->all();

        $operation = fn (): array => $this->auditUnits(
            $act,
            $source,
            $baseSourceUrl,
            $manifestReport,
            $manifestUnits,
            $write,
            $includeVerified,
            $sampleLimit,
        );

        return $write ? DB::transaction($operation) : $operation();
    }

    /**
     * @param  array<string, mixed>  $manifestReport
     * @param  array<string, array<string, mixed>>  $manifestUnits
     * @return array<string, mixed>
     */
    protected function auditUnits(
        LegalAct $act,
        string $source,
        string $baseSourceUrl,
        array $manifestReport,
        array $manifestUnits,
        bool $write,
        bool $includeVerified,
        int $sampleLimit,
    ): array {
        $statuses = [LegalUnit::STATUS_NEEDS_REVIEW];

        if ($includeVerified) {
            $statuses[] = LegalUnit::STATUS_VERIFIED;
        }

        $units = LegalUnit::query()
            ->with('parent:id,legal_act_id,canonical_path,label')
            ->where('legal_act_id', $act->getKey())
            ->whereIn('status', $statuses)
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [LegalUnit::STATUS_NEEDS_REVIEW])
            ->orderBy('canonical_path')
            ->get();
        $checkedAt = Carbon::now();
        $report = [
            'mode' => $write ? 'write' : 'preview',
            'act_slug' => $act->slug,
            'source' => $source,
            'source_url' => $baseSourceUrl,
            'manifest_units' => count($manifestUnits),
            'database_units' => LegalUnit::query()->where('legal_act_id', $act->getKey())->count(),
            'audited_units' => $units->count(),
            'passed_units' => 0,
            'failed_units' => 0,
            'promoted_units' => 0,
            'already_verified_passed_units' => 0,
            'failures_by_reason' => [],
            'failure_sample' => [],
            'promoted_sample' => [],
            'skipped_replacement_units' => $manifestReport['skipped_replacement_units'] ?? 0,
            'skipped_without_parent' => $manifestReport['skipped_without_parent'] ?? 0,
        ];

        foreach ($units as $unit) {
            $expected = $manifestUnits[(string) $unit->canonical_path] ?? null;
            $issues = $this->unitIssues($act, $unit, $expected);

            if ($issues !== []) {
                $report['failed_units']++;

                foreach ($issues as $issue) {
                    $report['failures_by_reason'][$issue] = ($report['failures_by_reason'][$issue] ?? 0) + 1;
                }

                $this->pushSample($report['failure_sample'], [
                    'id' => $unit->getKey(),
                    'canonical_path' => $unit->canonical_path,
                    'label' => $unit->label,
                    'status' => $unit->status,
                    'issues' => $issues,
                ], $sampleLimit);

                continue;
            }

            $report['passed_units']++;

            if ($unit->status === LegalUnit::STATUS_VERIFIED) {
                $report['already_verified_passed_units']++;

                continue;
            }

            if (! $write) {
                continue;
            }

            $unit->forceFill([
                'status' => LegalUnit::STATUS_VERIFIED,
                'last_checked_at' => $checkedAt,
            ])->save();

            LegalSourceCheck::query()->create([
                'legal_unit_id' => $unit->getKey(),
                'checked_by' => null,
                'checked_at' => $checkedAt,
                'source_url' => (string) $unit->source_url,
                'source_status' => 'available',
                'notes' => 'Automatyczny audyt ELI: typ, etykieta, canonical_path, rodzic, source_url i excerpt zgodne z oficjalnym text.html.',
            ]);

            $report['promoted_units']++;
            $this->pushSample($report['promoted_sample'], [
                'id' => $unit->getKey(),
                'canonical_path' => $unit->canonical_path,
                'label' => $unit->label,
            ], $sampleLimit);
        }

        ksort($report['failures_by_reason']);

        return $report;
    }

    /**
     * @param  array<string, mixed>|null  $expected
     * @return list<string>
     */
    protected function unitIssues(LegalAct $act, LegalUnit $unit, ?array $expected): array
    {
        $issues = [];

        if ($act->status !== LegalAct::STATUS_VERIFIED) {
            $issues[] = 'act_not_verified';
        }

        if ($expected === null) {
            $issues[] = 'missing_in_source';

            return $issues;
        }

        foreach (['canonical_path', 'label', 'type', 'source_url'] as $field) {
            if ($this->squish((string) $unit->{$field}) !== $this->squish((string) ($expected[$field] ?? ''))) {
                $issues[] = $field.'_mismatch';
            }
        }

        if (blank($unit->title)) {
            $issues[] = 'missing_title';
        }

        $expectedParentPath = $this->filledString($expected['parent_canonical_path'] ?? null);
        $actualParentPath = $unit->parent?->canonical_path;

        if ($expectedParentPath === null && $actualParentPath !== null) {
            $issues[] = 'unexpected_parent';
        }

        if ($expectedParentPath !== null && $actualParentPath === null) {
            $issues[] = 'missing_parent';
        }

        if ($expectedParentPath !== null && $actualParentPath !== null && $expectedParentPath !== $actualParentPath) {
            $issues[] = 'parent_mismatch';
        }

        $expectedExcerpt = $this->filledString($expected['official_excerpt'] ?? null);

        if ($expectedExcerpt !== null) {
            $actualExcerpt = $this->filledString($unit->official_excerpt);

            if ($actualExcerpt === null) {
                $issues[] = 'missing_excerpt';
            } elseif ($this->squish($actualExcerpt) !== $this->squish($expectedExcerpt)) {
                $issues[] = 'excerpt_mismatch';
            }
        }

        return array_values(array_unique($issues));
    }

    protected function inferTextHtmlSource(LegalAct $act): ?string
    {
        $sourceUrl = LegalUnit::query()
            ->where('legal_act_id', $act->getKey())
            ->whereNotNull('source_url')
            ->where('source_url', 'like', '%text.html#%')
            ->orderBy('id')
            ->value('source_url');

        if (! is_string($sourceUrl) || trim($sourceUrl) === '') {
            return null;
        }

        $source = Str::before($sourceUrl, '#');

        return $source !== '' ? $source : null;
    }

    protected function isUrl(string $source): bool
    {
        return preg_match('/^https?:\/\//i', $source) === 1;
    }

    protected function filledString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function squish(string $value): string
    {
        return Str::squish(str_replace("\u{00A0}", ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
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
