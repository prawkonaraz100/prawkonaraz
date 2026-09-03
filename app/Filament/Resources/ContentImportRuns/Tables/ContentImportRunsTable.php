<?php

namespace App\Filament\Resources\ContentImportRuns\Tables;

use App\Models\ContentImportRun;
use App\Support\ContentImportRunPresenter;
use App\Support\QuestionIntegrityAuditService;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentImportRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po identyfikatorze lub typie runu')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak importów')
            ->emptyStateDescription('Po pierwszym imporcie tutaj zobaczysz jego status i wynik audytów.')
            ->emptyStateIcon(Heroicon::OutlinedArrowDownTray)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('identifier')
                    ->label('Przebieg')
                    ->state(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->title($record))
                    ->description(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->subtitle($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery
                                ->where('identifier', 'like', "%{$search}%")
                                ->orWhere('kind', 'like', "%{$search}%");
                        });
                    })
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Wynik')
                    ->state(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->statusMeta($record)['label'])
                    ->description(function (ContentImportRun $record): string {
                        $presenter = app(ContentImportRunPresenter::class);

                        $parts = array_filter([
                            $presenter->stoppedReasonLabel(data_get($record->summary, 'stopped_reason')),
                            $presenter->durationLabel($record) !== '-' ? 'czas '.$presenter->durationLabel($record) : null,
                        ]);

                        return implode(' · ', $parts);
                    })
                    ->badge()
                    ->color(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->statusMeta($record)['color']),
                TextColumn::make('questions_total')
                    ->label('Zakres')
                    ->state(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->scaleSummary($record))
                    ->description(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->scaleDetails($record))
                    ->wrap()
                    ->sortable(),
                TextColumn::make('issues')
                    ->label('Ryzyko')
                    ->state(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->issuesSummary($record))
                    ->badge()
                    ->color(fn (ContentImportRun $record): string => app(ContentImportRunPresenter::class)->issuesTone($record)),
                TextColumn::make('integrity_audit_status')
                    ->label('Audyt bazy')
                    ->state(function (ContentImportRun $record): string {
                        $status = app(QuestionIntegrityAuditService::class)->resolveStatusMeta(
                            is_array($record->summary['integrity_audit'] ?? null)
                                ? $record->summary['integrity_audit']
                                : null,
                        );

                        return $status['label'];
                    })
                    ->badge()
                    ->color(function (ContentImportRun $record): string {
                        $status = app(QuestionIntegrityAuditService::class)->resolveStatusMeta(
                            is_array($record->summary['integrity_audit'] ?? null)
                                ? $record->summary['integrity_audit']
                                : null,
                        );

                        return $status['color'];
                    })
                    ->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Szczegóły'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
