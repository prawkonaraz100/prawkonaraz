<?php

namespace App\Filament\Resources\ContentCategories\Schemas;

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
                        ->description('Slug jest częścią publicznego URL i po utworzeniu pozostaje niezmienny w v1.')
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
                                ->disabledOn('edit')
                                ->helperText('Po utworzeniu sluga nie można zmienić, ponieważ v1 nie ma historii redirectów kategorii.'),
                            Textarea::make('description')
                                ->label('Opis')
                                ->rows(6)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i kolejność')
                        ->description('Wyłączenie kategorii jest blokowane, jeśli ma publiczne lub aktywnie dystrybuowane artykuły.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktywna')
                                ->default(true)
                                ->required()
                                ->inline(false),
                            TextInput::make('position')
                                ->label('Kolejność')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(32767)
                                ->default(0),
                        ]),
                    Section::make('SEO')
                        ->description('Treści SEO są opcjonalne; brak wartości nie jest zastępowany sztucznym tekstem.')
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
