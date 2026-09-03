<?php

namespace App\Filament\Resources\ContentImportRuns\Pages;

use App\Filament\Pages\GovImportGuide;
use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Support\ContentImportRunPresenter;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewContentImportRun extends ViewRecord
{
    protected static string $resource = ContentImportRunResource::class;

    protected string $view = 'filament.resources.content-import-runs.pages.view-content-import-run';

    public function getHeading(): string
    {
        return 'Szczegóły importu';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return filled($record->identifier)
            ? $record->identifier.' · '.$this->presenter()->kindLabel($record->kind)
            : 'Podgląd przebiegu importu, warningów, błędów i audytu integralności.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imports')
                ->label('Wróć do importów')
                ->url(ContentImportRunResource::getUrl(panel: 'admin')),
            Action::make('guide')
                ->label('Instrukcja gov.pl')
                ->url(GovImportGuide::getUrl(panel: 'admin')),
        ];
    }

    public function getStatusMeta(): array
    {
        return $this->presenter()->statusMeta($this->getRecord());
    }

    public function getTopMetrics(): array
    {
        $record = $this->getRecord();

        return [
            ['label' => 'Pytania', 'value' => number_format((int) $record->questions_total, 0, ',', ' ')],
            ['label' => 'Media', 'value' => number_format((int) $record->media_total, 0, ',', ' ')],
            ['label' => 'Wiersze', 'value' => number_format((int) $record->rows_total, 0, ',', ' ')],
            ['label' => 'Błędy', 'value' => number_format((int) $record->errors_count, 0, ',', ' ')],
            ['label' => 'Ostrzeżenia', 'value' => number_format((int) $record->warnings_count, 0, ',', ' ')],
            ['label' => 'Czas', 'value' => $this->presenter()->durationLabel($record)],
        ];
    }

    public function getOperationalRows(): array
    {
        $record = $this->getRecord();

        return [
            ['label' => 'Typ przebiegu', 'value' => $this->presenter()->kindLabel($record->kind)],
            ['label' => 'Tryb', 'value' => $this->presenter()->modeLabel($record)],
            ['label' => 'Kategorie', 'value' => $this->presenter()->selectedCategoriesLabel($record) ?? '-'],
            ['label' => 'Zatrzymanie', 'value' => $this->presenter()->stoppedReasonLabel(data_get($record->summary, 'stopped_reason'))],
            ['label' => 'Start', 'value' => $record->started_at?->format('d.m.Y H:i:s') ?? '-'],
            ['label' => 'Koniec', 'value' => $record->completed_at?->format('d.m.Y H:i:s') ?? '-'],
        ];
    }

    public function getPathRows(): array
    {
        $record = $this->getRecord();
        $audit = $this->presenter()->integrityAudit($record);

        return array_filter([
            ['label' => 'Źródło', 'value' => $record->source_path],
            ['label' => 'Katalog wyjściowy', 'value' => $record->output_path],
            ['label' => 'Raport importu', 'value' => $record->report_path],
            ['label' => 'Raport audytu', 'value' => data_get($audit, 'report_path')],
            ['label' => 'Zmiany audytu', 'value' => data_get($audit, 'changes_path')],
        ], static fn (array $row): bool => filled($row['value'] ?? null));
    }

    public function getIntegrityStatusMeta(): array
    {
        return $this->presenter()->integrityStatusMeta($this->getRecord());
    }

    public function getIntegrityMetrics(): array
    {
        return $this->presenter()->integrityAuditMetrics($this->getRecord());
    }

    public function getWarnings(): array
    {
        return $this->presenter()->warnings($this->getRecord());
    }

    public function getErrors(): array
    {
        return $this->presenter()->errors($this->getRecord());
    }

    public function getChunks(): array
    {
        return $this->presenter()->chunks($this->getRecord());
    }

    public function getSummaryJson(): string
    {
        return $this->presenter()->summaryJson($this->getRecord());
    }

    public function badgeClasses(string $color): string
    {
        return match ($color) {
            'danger' => 'border border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border border-amber-200 bg-amber-50 text-amber-700',
            'success' => 'border border-emerald-200 bg-emerald-50 text-emerald-700',
            default => 'border border-slate-200 bg-slate-50 text-slate-700',
        };
    }

    protected function presenter(): ContentImportRunPresenter
    {
        return app(ContentImportRunPresenter::class);
    }
}
