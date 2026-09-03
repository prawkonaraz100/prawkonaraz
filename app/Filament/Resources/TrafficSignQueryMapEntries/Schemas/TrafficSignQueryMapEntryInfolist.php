<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Schemas;

use App\Models\TrafficSignQueryMapEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignQueryMapEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Mapa zapytania')
                        ->description('Szybki wgląd w query, intencję i etap produkcyjny wpisu.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('primary_query')
                                ->label('Główne zapytanie'),
                            TextEntry::make('mapped_title')
                                ->label('Cel / tytuł roboczy'),
                            TextEntry::make('target_type')
                                ->label('Typ celu')
                                ->state(fn (TrafficSignQueryMapEntry $record): string => $record->targetTypeLabel()),
                            TextEntry::make('search_intent')
                                ->label('Intencja')
                                ->state(fn (TrafficSignQueryMapEntry $record): string => $record->searchIntentLabel()),
                            TextEntry::make('priority')
                                ->label('Priorytet')
                                ->state(fn (TrafficSignQueryMapEntry $record): string => $record->priorityLabel()),
                            TextEntry::make('rollout_status')
                                ->label('Status rolloutu')
                                ->state(fn (TrafficSignQueryMapEntry $record): string => $record->rolloutStatusLabel()),
                            TextEntry::make('batch_label')
                                ->label('Batch')
                                ->placeholder('-'),
                            TextEntry::make('target_path')
                                ->label('Docelowy path')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Powiązania')
                        ->description('Powiązanie z istniejącym znakiem lub kategorią pomaga domknąć rollout bez szukania kontekstu ręcznie.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('trafficSign.code')
                                ->label('Znak')
                                ->placeholder('-'),
                            TextEntry::make('category.name')
                                ->label('Kategoria')
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Aktualizacja')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                ]),
                Section::make('Decyzje i notatki')
                    ->description('Tu zbieramy uzasadnienie, źródła i zasady rewizji przy dalszym rolloucie.')
                    ->schema([
                        TextEntry::make('watch_reason')
                            ->label('Powód na watchliście')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('source_plan')
                            ->label('Plan źródeł')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('correction_notes')
                            ->label('Reguła korekty / update')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('competitor_notes')
                            ->label('Notatki o konkurencji')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('first_mover_note')
                            ->label('Notatka first-mover')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Notatki operacyjne')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
