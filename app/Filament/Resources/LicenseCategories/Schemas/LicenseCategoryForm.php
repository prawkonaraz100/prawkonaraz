<?php

namespace App\Filament\Resources\LicenseCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość kategorii')
                        ->description('Kod, nazwa i slug wpływają na to, jak kategoria pojawia się w serwisie i w danych.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('code')
                                ->label('Kod kategorii')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(10),
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
                                ->rows(5)
                                ->placeholder('Krótki opis kategorii do pracy wewnątrz zespołu.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i kolejność')
                        ->description('Tutaj ustawiasz, czy kategoria jest aktywna i w jakiej kolejności pokazuje się w serwisie.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktywna')
                                ->required()
                                ->inline(false),
                            TextInput::make('sort_order')
                                ->label('Kolejność')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->helperText('Niższa wartość oznacza wyższą pozycję.'),
                        ]),
                ]),
            ]);
    }
}
