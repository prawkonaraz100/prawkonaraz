<?php

namespace App\Filament\Resources\ContentAuthors\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentAuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość autora')
                        ->description('Te pola budują profil autora, który później wykorzystamy na stronie publicznej i w danych uporządkowanych.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->label('Imię i nazwisko')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            TextInput::make('job_title')
                                ->label('Rola / stanowisko')
                                ->maxLength(255),
                            TextInput::make('photo_path')
                                ->label('Ścieżka zdjęcia')
                                ->maxLength(2048)
                                ->placeholder('authors/jan-kowalski.jpg')
                                ->columnSpanFull(),
                            Textarea::make('bio')
                                ->label('Bio')
                                ->rows(6)
                                ->placeholder('Krótki opis doświadczenia autora i jego specjalizacji.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i źródła zaufania')
                        ->description('To miejsce na publikację profilu i zewnętrzne sygnały E-E-A-T.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Profil opublikowany')
                                ->required()
                                ->inline(false),
                            DateTimePicker::make('published_at')
                                ->label('Data publikacji')
                                ->seconds(false),
                            TextInput::make('linkedin_url')
                                ->label('LinkedIn')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://www.linkedin.com/in/...'),
                            TextInput::make('external_profile_url')
                                ->label('Zewnętrzny profil')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://...'),
                        ]),
                ]),
            ]);
    }
}
