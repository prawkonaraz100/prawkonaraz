<?php

namespace App\Filament\Resources\ContentAuthors\Schemas;

use App\Models\ContentAuthor;
use Carbon\Carbon;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentAuthorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Profil autora')
                        ->description('Podstawowe informacje, które później trafią na stronę autora i do znaczników schema.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('name')
                                ->label('Imię i nazwisko'),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('job_title')
                                ->label('Rola / stanowisko')
                                ->placeholder('-'),
                            TextEntry::make('photo_path')
                                ->label('Ścieżka zdjęcia')
                                ->placeholder('-')
                                ->columnSpanFull(),
                            TextEntry::make('bio')
                                ->label('Bio')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność')
                        ->description('Status publikacji i główne linki potwierdzające profil autora.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            IconEntry::make('is_published')
                                ->label('Opublikowany')
                                ->boolean(),
                            TextEntry::make('published_at')
                                ->label('Data publikacji')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('linkedin_url')
                                ->label('LinkedIn')
                                ->placeholder('-')
                                ->url(fn (ContentAuthor $record): ?string => $record->linkedin_url)
                                ->openUrlInNewTab(),
                            TextEntry::make('external_profile_url')
                                ->label('Zewnętrzny profil')
                                ->placeholder('-')
                                ->url(fn (ContentAuthor $record): ?string => $record->external_profile_url)
                                ->openUrlInNewTab(),
                        ]),
                ]),
                Section::make('Zawartość autora')
                    ->description('Podgląd użycia profilu autora w module znaków drogowych.')
                    ->schema([
                        TextEntry::make('traffic_signs_count')
                            ->label('Wszystkie znaki')
                            ->numeric(),
                        TextEntry::make('published_traffic_signs_count')
                            ->label('Opublikowane znaki')
                            ->numeric(),
                        TextEntry::make('traffic_signs_max_updated_at')
                            ->label('Ostatnia aktualizacja znaku')
                            ->state(fn (ContentAuthor $record): ?string => $record->traffic_signs_max_updated_at
                                ? Carbon::parse((string) $record->traffic_signs_max_updated_at)->format('d.m.Y H:i')
                                : null)
                            ->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }
}
