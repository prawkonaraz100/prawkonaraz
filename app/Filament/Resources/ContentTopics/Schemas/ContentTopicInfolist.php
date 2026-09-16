<?php

namespace App\Filament\Resources\ContentTopics\Schemas;

use App\Models\ContentTopic;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentTopicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Topic')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('title')
                                ->label('Tytuł'),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('description')
                                ->label('Opis')
                                ->placeholder('-')
                                ->columnSpanFull(),
                            TextEntry::make('featuredArticle.title')
                                ->label('Featured article')
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
                    Section::make('Publikacja i corpus')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (mixed $state): string => match ((string) $state) {
                                    ContentTopic::STATUS_DRAFT => 'Draft',
                                    ContentTopic::STATUS_PUBLISHED => 'Opublikowany',
                                    ContentTopic::STATUS_ARCHIVED => 'Archiwalny',
                                    default => (string) $state,
                                }),
                            TextEntry::make('published_at')
                                ->label('Pierwsza publikacja')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('articles_count')
                                ->label('Wszystkie artykuły')
                                ->numeric(),
                            TextEntry::make('eligible_articles_count')
                                ->label('Eligible corpus')
                                ->numeric(),
                            IconEntry::make('editorially_promotable')
                                ->label('Kwalifikuje się do promocji')
                                ->state(fn (ContentTopic $record): bool => $record->isEditoriallyPromotable())
                                ->boolean(),
                            TextEntry::make('corpus_warning')
                                ->label('Health')
                                ->state(fn (ContentTopic $record): string => $record->isCorpusBelowBaseline()
                                    ? 'Corpus poniżej baseline — napraw corpus albo jawnie zarchiwizuj topic.'
                                    : 'Bez ostrzeżenia')
                                ->color(fn (ContentTopic $record): string => $record->isCorpusBelowBaseline() ? 'warning' : 'success')
                                ->wrap(),
                        ]),
                ]),
            ]);
    }
}
