<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Models\AuditLog;
use App\Support\AuditLogPresenter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Zdarzenie')
                        ->description('Najkrótsze podsumowanie tego, co stało się w panelu lub po stronie systemu.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('action_label')
                                ->label('Operacja')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::actionLabel($record->action)),
                            TextEntry::make('action_description')
                                ->label('Opis')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::actionDescription($record))
                                ->columnSpanFull(),
                            TextEntry::make('created_at')
                                ->label('Kiedy')
                                ->dateTime('d.m.Y H:i'),
                            TextEntry::make('source_label')
                                ->label('Źródło')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::sourceLabel($record)),
                            TextEntry::make('entity_summary')
                                ->label('Obiekt')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::entitySummary($record))
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Operator i żądanie')
                        ->description('Kto wykonał akcję i z jakiego kontekstu przyszło żądanie.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            TextEntry::make('actor_label')
                                ->label('Kto')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::actorLabel($record)),
                            TextEntry::make('actor_details')
                                ->label('Dodatkowy kontekst')
                                ->state(fn (AuditLog $record): string => AuditLogPresenter::actorDetails($record))
                                ->columnSpanFull(),
                            TextEntry::make('ip_address')
                                ->label('IP')
                                ->placeholder('-'),
                            TextEntry::make('request_id')
                                ->label('Request ID')
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Szczegóły operacji')
                    ->description('Najważniejsze dane wyciągnięte z metadanych, bez przebijania się przez surowy JSON.')
                    ->schema([
                        TextEntry::make('important_details')
                            ->label('Najważniejsze informacje')
                            ->state(fn (AuditLog $record): string => AuditLogPresenter::importantDetails($record))
                            ->columnSpanFull(),
                        TextEntry::make('entity_type')
                            ->label('Typ techniczny encji')
                            ->placeholder('-'),
                        TextEntry::make('entity_id')
                            ->label('ID encji')
                            ->placeholder('-'),
                        TextEntry::make('user_agent')
                            ->label('User-Agent')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Surowe metadane')
                    ->description('Pełny payload zapisany przy wpisie audytu. Zostawione na potrzeby debugowania i analizy.')
                    ->schema([
                        TextEntry::make('metadata_json')
                            ->label('Metadata')
                            ->state(fn (AuditLog $record): string => AuditLogPresenter::metadataJson($record))
                            ->formatStateUsing(fn (string $state): string => '<pre class="overflow-x-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 text-xs leading-6 text-gray-900">'.e($state).'</pre>')
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
