<?php

namespace App\Filament\Resources\QuestionTopics\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionTopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Dział techniczny')
                        ->description('Te pola identyfikują dział w systemie. Nie zmieniaj ich podczas pracy nad samymi zdjęciami.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            TextInput::make('key')
                                ->label('Klucz')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('name')
                                ->label('Nazwa globalna')
                                ->disabled()
                                ->dehydrated(false),
                            Textarea::make('description')
                                ->label('Opis')
                                ->rows(4)
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                    Section::make('Globalne zdjęcie banera')
                        ->description('To zdjęcie dziedziczą wszystkie kategorie, jeśli nie mają własnego zdjęcia dla tego działu.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            FileUpload::make('hero_image_path')
                                ->label('Zdjęcie globalne')
                                ->disk((string) config('media.public_disk', 'public'))
                                ->directory('study/topic-heroes/default')
                                ->visibility('public')
                                ->acceptedFileTypes(config('media.allowed_mime_types.image', []))
                                ->maxSize((int) ceil(((int) config('media.max_bytes.image', 8 * 1024 * 1024)) / 1024))
                                ->previewable(false)
                                ->downloadable()
                                ->openable()
                                ->columnSpanFull()
                                ->helperText('Ustaw tutaj wspólny obraz działu, np. dla znaków ostrzegawczych. Wyjątki dla B/C/D ustawiaj przy konkretnej kategorii.'),
                            TextInput::make('hero_image_alt')
                                ->label('Opis obrazu')
                                ->maxLength(255),
                            Select::make('hero_image_position')
                                ->label('Kadrowanie')
                                ->options([
                                    'center' => 'Środek',
                                    'left center' => 'Lewa strona',
                                    'right center' => 'Prawa strona',
                                    'center top' => 'Góra',
                                    'center bottom' => 'Dół',
                                ])
                                ->default('center'),
                        ])
                        ->columns(2),
                ]),
            ]);
    }
}
