<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Support\QuestionIntegrityAuditService;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LatestQuestionIntegrityAuditOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $service = app(QuestionIntegrityAuditService::class);
        $report = $service->loadLatestReport();
        $status = $service->resolveStatusMeta($report);
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
        $generatedAt = $this->formatGeneratedAt($report['generated_at'] ?? null);
        $detailsUrl = ContentImportRunResource::getUrl('index', panel: 'admin');

        return [
            Stat::make('Integralność pytań', $status['label'])
                ->description(trim($status['description'].' '.$generatedAt))
                ->descriptionIcon(match ($status['state']) {
                    'critical' => 'heroicon-m-exclamation-triangle',
                    'review' => 'heroicon-m-eye',
                    'ok' => 'heroicon-m-check-badge',
                    default => 'heroicon-m-information-circle',
                })
                ->color($status['color'])
                ->url($detailsUrl),
            Stat::make('Zmiany standardowe', number_format(
                (int) ($summary['added_total'] ?? 0) + (int) ($summary['changed_total'] ?? 0),
                0,
                ',',
                ' ',
            ))
                ->description(sprintf(
                    'Dodane %d, zmienione %d, usunięte %d.',
                    (int) ($summary['added_total'] ?? 0),
                    (int) ($summary['changed_total'] ?? 0),
                    (int) ($summary['removed_total'] ?? 0),
                ))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color(((int) ($summary['added_total'] ?? 0) + (int) ($summary['changed_total'] ?? 0) + (int) ($summary['removed_total'] ?? 0)) > 0 ? 'gray' : 'success')
                ->url($detailsUrl),
            Stat::make('Zmiany krytyczne', number_format((int) ($summary['critical_total'] ?? 0), 0, ',', ' '))
                ->description('Zmiany w odpowiedziach, typie pytania lub głównym medium.')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color((int) ($summary['critical_total'] ?? 0) > 0 ? 'danger' : 'gray')
                ->url($detailsUrl),
        ];
    }

    protected function formatGeneratedAt(mixed $generatedAt): string
    {
        if (! is_string($generatedAt) || trim($generatedAt) === '') {
            return '';
        }

        try {
            return 'Ostatni przebieg: '.CarbonImmutable::parse($generatedAt)->timezone(config('app.timezone'))->format('d.m.Y H:i');
        } catch (\Throwable) {
            return '';
        }
    }
}
