<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Enums\ContentArticleType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Artykuł')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('title')
                                ->label('Tytuł')
                                ->columnSpanFull(),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('type')
                                ->label('Typ')
                                ->formatStateUsing(fn (mixed $state): string => static::typeLabel($state)),
                            TextEntry::make('category.name')
                                ->label('Kategoria'),
                            TextEntry::make('author.name')
                                ->label('Autor')
                                ->placeholder('-'),
                            TextEntry::make('reviewer.name')
                                ->label('Reviewer')
                                ->placeholder('-'),
                            TextEntry::make('lead')
                                ->label('Lead')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Stan')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('workflow_status')
                                ->label('Workflow')
                                ->formatStateUsing(fn (mixed $state): string => static::workflowLabel($state))
                                ->badge(),
                            IconEntry::make('is_featured')
                                ->label('Featured')
                                ->boolean(),
                            IconEntry::make('is_breaking')
                                ->label('Breaking')
                                ->boolean(),
                            TextEntry::make('published_at')
                                ->label('Publikacja')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('scheduled_for')
                                ->label('Zaplanowano')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('freshness_review_due_at')
                                ->label('Freshness review')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Aktualizacja')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                ]),
                Section::make('Notatka wewnętrzna')
                    ->schema([
                        TextEntry::make('editorial_note')
                            ->label('Notatka redakcyjna')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    protected static function typeLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleType ? $state->value : (string) $state;

        return match ($value) {
            'news' => 'News',
            'guide' => 'Poradnik',
            'explainer' => 'Explainer',
            'analysis' => 'Analiza',
            'report' => 'Raport',
            default => $value,
        };
    }

    protected static function workflowLabel(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? (string) $state->value : (string) $state;

        return match ($value) {
            'draft' => 'Draft',
            'in_review' => 'W review',
            'scheduled' => 'Zaplanowany',
            'published' => 'Opublikowany',
            'needs_review' => 'Wymaga review',
            'archived' => 'Archiwalny',
            'withdrawn' => 'Wycofany',
            default => $value,
        };
    }
}
