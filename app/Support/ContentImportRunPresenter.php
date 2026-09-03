<?php

namespace App\Support;

use App\Models\ContentImportRun;
use Illuminate\Support\Arr;

class ContentImportRunPresenter
{
    public function __construct(
        protected QuestionIntegrityAuditService $questionIntegrityAuditService,
    ) {}

    public function kindLabel(?string $kind): string
    {
        return match ($kind) {
            'catalog_json_import' => 'Import katalogu JSON',
            'manifest_series_import' => 'Import serii manifestów',
            'gov_batch_prepare' => 'Przygotowanie batchy gov.pl',
            'manifest_import' => 'Import manifestu',
            default => filled($kind) ? str_replace('_', ' ', (string) $kind) : 'Przebieg importu',
        };
    }

    public function title(ContentImportRun $record): string
    {
        return filled($record->identifier)
            ? (string) $record->identifier
            : $this->kindLabel($record->kind);
    }

    public function subtitle(ContentImportRun $record): string
    {
        $parts = array_filter([
            $this->kindLabel($record->kind),
            $this->modeLabel($record),
            $this->selectedCategoriesLabel($record),
        ]);

        return implode(' · ', $parts);
    }

    public function modeLabel(ContentImportRun $record): string
    {
        return $record->dry_run ? 'Dry run' : 'Pełny import';
    }

    public function selectedCategoriesLabel(ContentImportRun $record): ?string
    {
        $categories = Arr::wrap(data_get($record->summary, 'selected_categories', []));
        $categories = array_values(array_filter(array_map(
            static fn (mixed $value): string => strtoupper(trim((string) $value)),
            $categories,
        )));

        if ($categories === []) {
            return null;
        }

        return 'Kategorie '.implode(', ', $categories);
    }

    public function scaleSummary(ContentImportRun $record): string
    {
        return sprintf(
            '%s pytań · %s mediów',
            $this->formatNumber($record->questions_total),
            $this->formatNumber($record->media_total),
        );
    }

    public function scaleDetails(ContentImportRun $record): string
    {
        $parts = array_filter([
            sprintf('%s wierszy', $this->formatNumber($record->rows_total)),
            $record->asset_plan_total > 0 ? sprintf('plan assetów %s', $this->formatNumber($record->asset_plan_total)) : null,
            $record->uploaded_assets_total > 0 ? sprintf('wgrane %s', $this->formatNumber($record->uploaded_assets_total)) : null,
        ]);

        return $parts === [] ? 'Brak dodatkowych danych o skali importu.' : implode(' · ', $parts);
    }

    public function issuesSummary(ContentImportRun $record): string
    {
        return sprintf(
            '%s błędów · %s ostrzeżeń',
            $this->formatNumber($record->errors_count),
            $this->formatNumber($record->warnings_count),
        );
    }

    public function issuesTone(ContentImportRun $record): string
    {
        if ((int) $record->errors_count > 0 || $record->status === 'failed') {
            return 'danger';
        }

        if ((int) $record->warnings_count > 0) {
            return 'warning';
        }

        return 'gray';
    }

    public function stoppedReasonLabel(?string $value): string
    {
        return match ($value) {
            'completed' => 'Zakończono poprawnie',
            'completed_with_errors' => 'Zakończono z błędami',
            'max_chunks_reached' => 'Osiągnięto limit chunków',
            'source_exhausted' => 'Źródło zostało wyczerpane',
            'chunk_failed' => 'Jeden z chunków zakończył się błędem',
            'no_chunks_selected' => 'Nie wybrano żadnego chunku',
            'nothing_to_do' => 'Nie było nic do przetworzenia',
            default => filled($value) ? str_replace('_', ' ', (string) $value) : 'Brak dodatkowej informacji',
        };
    }

    public function durationLabel(ContentImportRun $record): string
    {
        if ($record->started_at === null || $record->completed_at === null) {
            return '-';
        }

        $seconds = abs($record->started_at->diffInSeconds($record->completed_at));

        if ($seconds < 60) {
            return "{$seconds} s";
        }

        $minutes = intdiv($seconds, 60);
        $restSeconds = $seconds % 60;

        return $restSeconds === 0
            ? "{$minutes} min"
            : "{$minutes} min {$restSeconds} s";
    }

    /**
     * @return array{label:string,color:string,description:string}
     */
    public function statusMeta(ContentImportRun $record): array
    {
        if ($record->status === 'failed' || (int) $record->errors_count > 0) {
            return [
                'label' => 'Błąd',
                'color' => 'danger',
                'description' => 'Ten przebieg wymaga ręcznego sprawdzenia przed kolejnym wsadem.',
            ];
        }

        if ((int) $record->warnings_count > 0) {
            return [
                'label' => 'Ostrzeżenia',
                'color' => 'warning',
                'description' => 'Import przeszedł, ale zostawił warningi do review.',
            ];
        }

        if ($record->dry_run) {
            return [
                'label' => 'Dry run OK',
                'color' => 'gray',
                'description' => 'Symulacja zakończyła się bez błędów krytycznych.',
            ];
        }

        return [
            'label' => 'OK',
            'color' => 'success',
            'description' => 'Import zakończył się poprawnie.',
        ];
    }

    /**
     * @return array{label:string,color:string,description:string}
     */
    public function integrityStatusMeta(ContentImportRun $record): array
    {
        return $this->questionIntegrityAuditService->resolveStatusMeta($this->integrityAudit($record));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function integrityAudit(ContentImportRun $record): ?array
    {
        $audit = data_get($record->summary, 'integrity_audit');

        return is_array($audit) ? $audit : null;
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    public function integrityAuditMetrics(ContentImportRun $record): array
    {
        $summary = data_get($this->integrityAudit($record), 'summary');

        if (! is_array($summary)) {
            return [];
        }

        return [
            ['label' => 'W katalogu', 'value' => $this->formatNumber((int) ($summary['current_total'] ?? 0))],
            ['label' => 'Dodane', 'value' => $this->formatNumber((int) ($summary['added_total'] ?? 0))],
            ['label' => 'Usunięte', 'value' => $this->formatNumber((int) ($summary['removed_total'] ?? 0))],
            ['label' => 'Zmienione', 'value' => $this->formatNumber((int) ($summary['changed_total'] ?? 0))],
            ['label' => 'Krytyczne', 'value' => $this->formatNumber((int) ($summary['critical_total'] ?? 0))],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function warnings(ContentImportRun $record): array
    {
        return $this->formatMessages(Arr::wrap(data_get($record->summary, 'warnings', [])));
    }

    /**
     * @return array<int, string>
     */
    public function errors(ContentImportRun $record): array
    {
        return $this->formatMessages(Arr::wrap(data_get($record->summary, 'errors', [])));
    }

    /**
     * @return array<int, array{title:string,details:string}>
     */
    public function chunks(ContentImportRun $record): array
    {
        $chunks = data_get($record->summary, 'chunks');

        if (! is_array($chunks)) {
            return [];
        }

        return array_values(array_map(function (mixed $chunk): array {
            $chunk = is_array($chunk) ? $chunk : [];
            $number = $chunk['number'] ?? '?';
            $status = $this->chunkStatusLabel($chunk['status'] ?? null);

            $details = array_filter([
                isset($chunk['rows_total']) ? sprintf('%s wierszy', $this->formatNumber((int) $chunk['rows_total'])) : null,
                isset($chunk['questions_total']) ? sprintf('%s pytań', $this->formatNumber((int) $chunk['questions_total'])) : null,
                isset($chunk['errors_count']) ? sprintf('%s błędów', $this->formatNumber((int) $chunk['errors_count'])) : null,
                isset($chunk['source_offset']) ? sprintf('offset %s', $this->formatNumber((int) $chunk['source_offset'])) : null,
                isset($chunk['source_limit']) ? sprintf('limit %s', $this->formatNumber((int) $chunk['source_limit'])) : null,
            ]);

            return [
                'title' => "Chunk {$number} · {$status}",
                'details' => implode(' · ', $details),
            ];
        }, $chunks));
    }

    public function summaryJson(ContentImportRun $record): string
    {
        if (! is_array($record->summary) || $record->summary === []) {
            return '{}';
        }

        return json_encode(
            $record->summary,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '{}';
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    protected function formatMessages(array $items): array
    {
        return array_values(array_filter(array_map(function (mixed $item): ?string {
            if (is_string($item)) {
                $item = trim($item);

                return $item !== '' ? $item : null;
            }

            if (! is_array($item)) {
                return null;
            }

            $main = trim((string) ($item['message'] ?? $item['error'] ?? $item['reason'] ?? ''));
            $prefix = trim(implode(' · ', array_filter([
                isset($item['code']) ? strtoupper((string) $item['code']) : null,
                isset($item['chunk']) ? 'chunk '.(string) $item['chunk'] : null,
                isset($item['path']) ? basename((string) $item['path']) : null,
            ])));

            if ($main === '') {
                $main = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            }

            return trim($prefix !== '' ? "{$prefix} · {$main}" : $main);
        }, $items)));
    }

    protected function chunkStatusLabel(?string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'failed' => 'Błąd',
            'reused' => 'Użyto ponownie',
            'skipped' => 'Pominięto',
            default => filled($status) ? str_replace('_', ' ', (string) $status) : 'Nieznany status',
        };
    }

    protected function formatNumber(?int $value): string
    {
        return number_format((int) $value, 0, ',', ' ');
    }
}
