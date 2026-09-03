<?php

namespace App\Filament\Resources\TrafficSignCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość kategorii')
                        ->description('Najważniejsze dane wykorzystywane przez routing i stronę kategorii.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nazwa'),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('description')
                                ->label('Opis')
                                ->placeholder('-')
                                ->columnSpanFull(),
                            TextEntry::make('intro_title')
                                ->label('Tytuł wstępu')
                                ->placeholder('-'),
                            TextEntry::make('intro_body')
                                ->label('Wstęp kategorii')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność')
                        ->description('Status publikacji i kolejność kategorii na hubie.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            IconEntry::make('is_published')
                                ->label('Opublikowana')
                                ->boolean(),
                            TextEntry::make('published_at')
                                ->label('Data publikacji')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('sort_order')
                                ->label('Kolejność')
                                ->numeric(),
                        ]),
                ]),
                Section::make('Pokrycie kategorii')
                    ->description('Szybki podgląd, ile znaków już przypisano do tej kategorii.')
                    ->schema([
                        TextEntry::make('traffic_signs_count')
                            ->label('Wszystkie znaki')
                            ->numeric(),
                        TextEntry::make('published_traffic_signs_count')
                            ->label('Opublikowane znaki')
                            ->numeric(),
                    ])
                    ->columns(2),
            ]);
    }
}
