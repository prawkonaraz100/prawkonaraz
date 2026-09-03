<?php

namespace App\Filament\Resources\TrafficSignCategories\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość kategorii')
                        ->description('Kategoria porządkuje znaki w klastrze i buduje osobne strony kategorii.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->label('Nazwa')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label('Opis')
                                ->rows(4)
                                ->columnSpanFull(),
                            TextInput::make('intro_title')
                                ->label('Tytuł wstępu')
                                ->maxLength(255),
                            Textarea::make('intro_body')
                                ->label('Wstęp kategorii')
                                ->rows(5)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i kolejność')
                        ->description('Tutaj ustawiasz publikację kategorii i jej pozycję na hubie znaków.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Opublikowana')
                                ->required()
                                ->inline(false),
                            DateTimePicker::make('published_at')
                                ->label('Data publikacji')
                                ->seconds(false),
                            TextInput::make('sort_order')
                                ->label('Kolejność')
                                ->required()
                                ->numeric()
                                ->default(0),
                        ]),
                ]),
            ]);
    }
}
