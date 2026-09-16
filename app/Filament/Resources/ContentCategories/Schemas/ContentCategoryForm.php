<?php

namespace App\Filament\Resources\ContentCategories\Schemas;

use App\Models\ContentCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość kategorii')
                        ->description('Slug jest publiczną tożsamością kategorii i po utworzeniu pozostaje niezmienny w newsroom v1.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->label('Nazwa')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(160)
                                ->disabled(fn (?ContentCategory $record): bool => $record !== null)
                                ->dehydrated(fn (?ContentCategory $record): bool => $record === null),
                            Textarea::make('description')
                                ->label('Opis')
                                ->rows(6)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i kolejność')
                        ->description('Dezaktywacja jest możliwa dopiero po przepięciu lub wycofaniu publicznych materiałów.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktywna')
                                ->required()
                                ->inline(false)
                                ->default(true)
                                ->disabled(fn (?ContentCategory $record): bool => $record !== null
                                    && $record->is_active
                                    && ! $record->canBeDeactivated())
                                ->helperText(fn (?ContentCategory $record): ?string => $record !== null
                                    && $record->is_active
                                    && ! $record->canBeDeactivated()
                                        ? 'Kategoria ma publiczne lub aktywnie dystrybuowane artykuły i nie może zostać wyłączona.'
                                        : null),
                            TextInput::make('position')
                                ->label('Kolejność')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(32767)
                                ->default(0),
                        ]),
                    Section::make('SEO')
                        ->description('Pola SEO mogą pozostać puste; publiczny renderer zastosuje fallback zgodny z późniejszym kontraktem SEO.')
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('seo_title')
                                ->label('SEO title')
                                ->maxLength(255),
                            Textarea::make('seo_description')
                                ->label('SEO description')
                                ->rows(3)
                                ->maxLength(320),
                        ])
                        ->columns(2),
                ]),
            ]);
    }
}
