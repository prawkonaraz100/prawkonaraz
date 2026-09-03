<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use App\Support\AuditLogPresenter;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po akcji, encji, ID, użytkowniku albo IP')
            ->persistFiltersInSession()
            ->filtersFormColumns(4)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak wpisów audytu')
            ->emptyStateDescription('Wpisy pojawią się tutaj po pierwszych działaniach w panelu albo po akcjach systemowych.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Kiedy')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('operation')
                    ->label('Operacja')
                    ->state(fn (AuditLog $record): string => AuditLogPresenter::actionLabel($record->action))
                    ->description(fn (AuditLog $record): string => AuditLogPresenter::actionDescription($record))
                    ->searchable(['action'])
                    ->wrap(),
                TextColumn::make('entity')
                    ->label('Obiekt')
                    ->state(fn (AuditLog $record): string => AuditLogPresenter::entitySummary($record))
                    ->description(fn (AuditLog $record): string => 'Typ techniczny: '.$record->entity_type)
                    ->searchable(['entity_type', 'entity_id'])
                    ->wrap(),
                TextColumn::make('actor')
                    ->label('Kto')
                    ->state(fn (AuditLog $record): string => AuditLogPresenter::actorLabel($record))
                    ->description(fn (AuditLog $record): string => AuditLogPresenter::actorDetails($record))
                    ->searchable(['actorUser.name', 'actorUser.email'])
                    ->wrap(),
                TextColumn::make('request_context')
                    ->label('Kontekst')
                    ->state(fn (AuditLog $record): string => AuditLogPresenter::requestSummary($record))
                    ->description(fn (AuditLog $record): string => AuditLogPresenter::requestDetails($record))
                    ->searchable(['ip_address', 'request_id'])
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Operacja')
                    ->options(fn (): array => AuditLog::query()
                        ->orderBy('action')
                        ->distinct()
                        ->pluck('action')
                        ->mapWithKeys(fn (string $action): array => [$action => AuditLogPresenter::actionLabel($action)])
                        ->all()),
                SelectFilter::make('entity_type')
                    ->label('Typ encji')
                    ->options(fn (): array => AuditLog::query()
                        ->orderBy('entity_type')
                        ->distinct()
                        ->pluck('entity_type')
                        ->mapWithKeys(fn (string $entityType): array => [$entityType => AuditLogPresenter::entityTypeLabel($entityType)])
                        ->all()),
                SelectFilter::make('actor_user_id')
                    ->label('Operator')
                    ->relationship('actorUser', 'email')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Zakres dat')
                    ->form([
                        DatePicker::make('from')
                            ->label('Od'),
                        DatePicker::make('until')
                            ->label('Do'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', (string) $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', (string) $data['until']),
                            );
                    }),
                Filter::make('system_events')
                    ->label('Tylko system')
                    ->query(fn (Builder $query): Builder => $query->whereNull('actor_user_id')),
                Filter::make('with_ip')
                    ->label('Tylko z IP')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('ip_address')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Szczegóły'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
