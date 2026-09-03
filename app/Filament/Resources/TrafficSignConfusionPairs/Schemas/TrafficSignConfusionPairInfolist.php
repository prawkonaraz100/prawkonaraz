<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Schemas;

use App\Models\TrafficSignConfusionPair;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignConfusionPairInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Para znaków')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('trafficSign.code')
                                ->label('Znak bazowy')
                                ->state(fn (TrafficSignConfusionPair $record): string => static::signLabel($record, 'trafficSign')),
                            TextEntry::make('confusingTrafficSign.code')
                                ->label('Znak mylony')
                                ->state(fn (TrafficSignConfusionPair $record): string => static::signLabel($record, 'confusingTrafficSign')),
                        ])
                        ->columns(2),
                    Section::make('Źródło')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('source')
                                ->label('Źródło')
                                ->state(fn (TrafficSignConfusionPair $record): string => $record->sourceLabel())
                                ->badge(),
                            TextEntry::make('source_slug')
                                ->label('Slug źródła')
                                ->placeholder('-'),
                            TextEntry::make('strength')
                                ->label('Siła relacji')
                                ->numeric(),
                            TextEntry::make('updated_at')
                                ->label('Aktualizacja')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                ]),
            ]);
    }

    protected static function signLabel(TrafficSignConfusionPair $record, string $relation): string
    {
        $sign = $record->{$relation};

        return trim(($sign?->code ?? '').' · '.($sign?->name ?? ''));
    }
}
