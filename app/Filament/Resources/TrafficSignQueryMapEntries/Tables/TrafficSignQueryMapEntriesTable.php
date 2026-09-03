<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Tables;

use App\Models\TrafficSignQueryMapEntry;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TrafficSignQueryMapEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po query, tytule, path albo batchu')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak wpisów mapy zapytań')
            ->emptyStateDescription('Dodaj pierwszy wpis, żeby przełożyć roadmapę na realny rollout batchy i watchlisty.')
            ->emptyStateIcon(Heroicon::OutlinedQueueList)
            ->columns([
                TextColumn::make('primary_query')
                    ->label('Zapytanie')
                    ->searchable(['primary_query', 'mapped_title', 'target_path', 'batch_label'])
                    ->sortable()
                    ->description(fn (TrafficSignQueryMapEntry $record): string => implode(' · ', array_filter([
                        $record->mapped_title,
                        $record->target_path,
                    ])))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('scope_summary')
                    ->label('Scope')
                    ->state(fn (TrafficSignQueryMapEntry $record): string => $record->rolloutScopeSummary())
                    ->wrap(),
                TextColumn::make('target_type')
                    ->label('Typ celu')
                    ->state(fn (TrafficSignQueryMapEntry $record): string => $record->targetTypeLabel())
                    ->badge()
                    ->color(fn (TrafficSignQueryMapEntry $record): string => $record->targetTypeColor()),
                TextColumn::make('search_intent')
                    ->label('Intencja')
                    ->state(fn (TrafficSignQueryMapEntry $record): string => $record->searchIntentLabel())
                    ->badge()
                    ->color(fn (TrafficSignQueryMapEntry $record): string => $record->searchIntentColor()),
                TextColumn::make('priority')
                    ->label('Priorytet')
                    ->state(fn (TrafficSignQueryMapEntry $record): string => $record->priorityLabel())
                    ->badge()
                    ->color(fn (TrafficSignQueryMapEntry $record): string => $record->priorityColor()),
                TextColumn::make('rollout_status')
                    ->label('Rollout')
                    ->state(fn (TrafficSignQueryMapEntry $record): string => $record->rolloutStatusLabel())
                    ->badge()
                    ->color(fn (TrafficSignQueryMapEntry $record): string => $record->rolloutStatusColor()),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rollout_status')
                    ->label('Rollout')
                    ->options(TrafficSignQueryMapEntry::rolloutStatusOptions()),
                SelectFilter::make('priority')
                    ->label('Priorytet')
                    ->options(TrafficSignQueryMapEntry::priorityOptions()),
                SelectFilter::make('search_intent')
                    ->label('Intencja')
                    ->options(TrafficSignQueryMapEntry::searchIntentOptions()),
                SelectFilter::make('target_type')
                    ->label('Typ celu')
                    ->options(TrafficSignQueryMapEntry::targetTypeOptions()),
                SelectFilter::make('traffic_sign_category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move_to_watchlist')
                        ->label('Przenieś na watchlistę')
                        ->icon(Heroicon::OutlinedEye)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => static::updateRolloutStatus(
                            $records,
                            TrafficSignQueryMapEntry::STATUS_WATCHLIST,
                            'Zaktualizowano watchlistę.',
                            'Wybrane wpisy wróciły do monitorowania zamiast aktywnego rolloutu.',
                        )),
                    BulkAction::make('mark_brief_ready')
                        ->label('Oznacz: brief gotowy')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => static::updateRolloutStatus(
                            $records,
                            TrafficSignQueryMapEntry::STATUS_BRIEF_READY,
                            'Zaktualizowano briefy.',
                            'Wybrane wpisy są gotowe do wejścia w produkcję treści.',
                        )),
                    BulkAction::make('mark_drafting')
                        ->label('Oznacz: w pisaniu')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => static::updateRolloutStatus(
                            $records,
                            TrafficSignQueryMapEntry::STATUS_DRAFTING,
                            'Wpisy są już w pisaniu.',
                            'Zespół redakcyjny może pracować na wybranym batchu.',
                        )),
                    BulkAction::make('mark_ready')
                        ->label('Oznacz: gotowe do produkcji')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => static::updateRolloutStatus(
                            $records,
                            TrafficSignQueryMapEntry::STATUS_READY,
                            'Zaktualizowano gotowość batcha.',
                            'Wybrane wpisy są gotowe do publikacyjnego passu.',
                        )),
                    BulkAction::make('mark_published')
                        ->label('Oznacz: opublikowane')
                        ->icon(Heroicon::OutlinedRocketLaunch)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => static::updateRolloutStatus(
                            $records,
                            TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                            'Zaktualizowano publikację.',
                            'Wybrane wpisy mają już status opublikowanych.',
                        )),
                    BulkAction::make('assign_batch')
                        ->label('Ustaw batch')
                        ->icon(Heroicon::OutlinedQueueList)
                        ->color('gray')
                        ->schema([
                            TextInput::make('batch_label')
                                ->label('Batch')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $updated = 0;

                            foreach ($records as $record) {
                                $record->forceFill([
                                    'batch_label' => $data['batch_label'],
                                ])->save();

                                $updated++;
                            }

                            static::sendCountNotification(
                                $updated,
                                'Nie ustawiono batcha.',
                                'Wybrane wpisy mają już wspólną etykietę batcha do dalszego planowania rolloutu.',
                            );
                        }),
                    DeleteBulkAction::make()
                        ->label('Usuń'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @param  Collection<int, TrafficSignQueryMapEntry>  $records
     */
    protected static function updateRolloutStatus(
        Collection $records,
        string $status,
        string $emptyTitle,
        string $body,
    ): void {
        $updated = 0;

        foreach ($records as $record) {
            $record->forceFill([
                'rollout_status' => $status,
            ])->save();

            $updated++;
        }

        static::sendCountNotification($updated, $emptyTitle, $body);
    }

    protected static function sendCountNotification(int $updated, string $emptyTitle, string $body): void
    {
        $notification = Notification::make();

        if ($updated > 0) {
            $notification
                ->success()
                ->title($updated === 1 ? 'Zaktualizowano 1 wpis.' : "Zaktualizowano {$updated} wpisów.")
                ->body($body);
        } else {
            $notification
                ->warning()
                ->title($emptyTitle);
        }

        $notification->send();
    }
}
