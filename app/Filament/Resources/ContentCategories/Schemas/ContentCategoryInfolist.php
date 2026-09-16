<?php

namespace App\Filament\Resources\ContentCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Kategoria')
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
                            TextEntry::make('seo_title')
                                ->label('SEO title')
                                ->placeholder('-'),
                            TextEntry::make('seo_description')
                                ->label('SEO description')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i użycie')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            IconEntry::make('is_active')
                                ->label('Aktywna')
                                ->boolean(),
                            TextEntry::make('position')
                                ->label('Kolejność')
                                ->numeric(),
                            TextEntry::make('articles_count')
                                ->label('Wszystkie artykuły')
                                ->numeric(),
                            TextEntry::make('publicly_visible_articles_count')
                                ->label('Publicznie widoczne')
                                ->numeric(),
                            TextEntry::make('actively_distributed_articles_count')
                                ->label('Aktywnie dystrybuowane')
                                ->numeric(),
                        ]),
                ]),
            ]);
    }
}
