<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Schemas;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignQueryMapEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Mapa zapytania')
                        ->description('Tu decydujemy, jaki query chcemy wygrać, jaką stroną i z jakim priorytetem rolloutowym.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('primary_query')
                                ->label('Główne zapytanie')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('mapped_title')
                                ->label('Nazwa celu / tytuł roboczy')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Select::make('target_type')
                                ->label('Typ celu')
                                ->options(TrafficSignQueryMapEntry::targetTypeOptions())
                                ->default(TrafficSignQueryMapEntry::TARGET_SIGN)
                                ->native(false)
                                ->required(),
                            Select::make('search_intent')
                                ->label('Intencja')
                                ->options(TrafficSignQueryMapEntry::searchIntentOptions())
                                ->default(TrafficSignQueryMapEntry::INTENT_INFORMATIONAL)
                                ->native(false)
                                ->required(),
                            Select::make('priority')
                                ->label('Priorytet')
                                ->options(TrafficSignQueryMapEntry::priorityOptions())
                                ->default(TrafficSignQueryMapEntry::PRIORITY_P2)
                                ->native(false)
                                ->required(),
                            Select::make('rollout_status')
                                ->label('Status rolloutu')
                                ->options(TrafficSignQueryMapEntry::rolloutStatusOptions())
                                ->default(TrafficSignQueryMapEntry::STATUS_BACKLOG)
                                ->native(false)
                                ->required(),
                            TextInput::make('batch_label')
                                ->label('Batch')
                                ->maxLength(255)
                                ->placeholder('np. rollout-01'),
                            TextInput::make('target_path')
                                ->label('Docelowy path')
                                ->maxLength(2048)
                                ->placeholder('/znaki-drogowe/b-36-zakaz-zatrzymywania-sie')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Powiązanie w serwisie')
                        ->description('Jeśli wpis dotyczy już istniejącej strony lub kategorii, podepnij go tutaj.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Select::make('traffic_sign_id')
                                ->label('Powiązany znak')
                                ->relationship('trafficSign', 'name')
                                ->getOptionLabelFromRecordUsing(fn (TrafficSign $record): string => $record->code.' '.$record->name)
                                ->searchable()
                                ->preload()
                                ->placeholder('Brak powiązanego znaku'),
                            Select::make('traffic_sign_category_id')
                                ->label('Powiązana kategoria')
                                ->relationship('category', 'name')
                                ->getOptionLabelFromRecordUsing(fn (TrafficSignCategory $record): string => $record->name)
                                ->searchable()
                                ->preload()
                                ->placeholder('Brak powiązanej kategorii'),
                        ]),
                ]),
                Section::make('Decyzja redakcyjna')
                    ->description('Tutaj zapisujemy źródła, watchlistę i to, co ma uruchomić korektę albo kolejny ruch publikacyjny.')
                    ->schema([
                        Textarea::make('watch_reason')
                            ->label('Powód na watchliście')
                            ->rows(3),
                        Textarea::make('source_plan')
                            ->label('Plan źródeł')
                            ->rows(4)
                            ->helperText('Jakie źródła lub podstawy prawne muszą wejść do tego materiału, zanim trafi do produkcji.'),
                        Textarea::make('correction_notes')
                            ->label('Reguła korekty / update')
                            ->rows(4)
                            ->helperText('Co ma uruchamiać rewizję: zmiana przepisu, sezonowość, nowy znak, spadek jakości albo nowy insight z konkurencji.'),
                        Textarea::make('competitor_notes')
                            ->label('Notatki o konkurencji')
                            ->rows(4),
                        Textarea::make('first_mover_note')
                            ->label('Notatka first-mover')
                            ->rows(3),
                        Textarea::make('notes')
                            ->label('Notatki operacyjne')
                            ->rows(5),
                    ])
                    ->columns(2),
            ]);
    }
}
