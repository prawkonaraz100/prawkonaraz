<?php

namespace App\Filament\Resources\QuestionTopics\Schemas;

use App\Models\QuestionTopic;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionTopicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dział')
                    ->schema([
                        TextEntry::make('key')
                            ->label('Klucz'),
                        TextEntry::make('name')
                            ->label('Nazwa globalna'),
                        TextEntry::make('description')
                            ->label('Opis')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Aktywny')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Globalne zdjęcie banera')
                    ->schema([
                        TextEntry::make('hero_image_path')
                            ->label('Ścieżka obrazu')
                            ->placeholder('-'),
                        TextEntry::make('hero_image_alt')
                            ->label('Opis obrazu')
                            ->placeholder('-'),
                        TextEntry::make('hero_image_position')
                            ->label('Kadrowanie')
                            ->placeholder('center'),
                        TextEntry::make('usage')
                            ->label('Użycie')
                            ->state(fn (QuestionTopic $record): string => implode(' · ', [
                                'Pytania: '.number_format((int) ($record->questions_count ?? 0), 0, ',', ' '),
                                'Wyjątki kategorii: '.number_format((int) ($record->category_heroes_count ?? 0), 0, ',', ' '),
                            ]))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
