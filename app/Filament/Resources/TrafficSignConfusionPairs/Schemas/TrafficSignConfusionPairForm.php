<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Schemas;

use App\Models\TrafficSign;
use App\Models\TrafficSignConfusionPair;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignConfusionPairForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Para znaków')
                        ->description('Relacja jest kierunkowa: znak bazowy dostaje wskazany znak jako mocny distractor w trybie podobnych znaków.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            Select::make('traffic_sign_id')
                                ->label('Znak bazowy')
                                ->relationship('trafficSign', 'name')
                                ->getOptionLabelFromRecordUsing(fn (TrafficSign $record): string => $record->code.' · '.$record->name)
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('confusing_traffic_sign_id')
                                ->label('Znak mylony')
                                ->relationship('confusingTrafficSign', 'name')
                                ->getOptionLabelFromRecordUsing(fn (TrafficSign $record): string => $record->code.' · '.$record->name)
                                ->searchable()
                                ->preload()
                                ->rules(['different:traffic_sign_id'])
                                ->required(),
                        ])
                        ->columns(2),
                    Section::make('Źródło i priorytet')
                        ->description('Źródło mówi, czy para pochodzi z porównania publicznego, czy została dodana ręcznie przez redakcję.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Select::make('source')
                                ->label('Źródło')
                                ->options(TrafficSignConfusionPair::sourceOptions())
                                ->default(TrafficSignConfusionPair::SOURCE_MANUAL)
                                ->native(false)
                                ->required(),
                            TextInput::make('source_slug')
                                ->label('Slug źródła')
                                ->maxLength(255)
                                ->placeholder('np. a-7-vs-b-20'),
                            TextInput::make('strength')
                                ->label('Siła relacji')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(80)
                                ->required(),
                        ]),
                ]),
            ]);
    }
}
