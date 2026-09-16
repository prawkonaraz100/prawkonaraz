<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość i klasyfikacja')
                        ->description('Shell N2-002 utrzymuje podstawowe pola draftu. Workflow, treść blokowa, źródła i media mają osobne etapy N2.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            Select::make('type')
                                ->label('Typ')
                                ->options(static::typeOptions())
                                ->default(ContentArticleType::News->value)
                                ->native(false)
                                ->required()
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record)),
                            Select::make('category_id')
                                ->label('Kategoria')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record)),
                            TextInput::make('title')
                                ->label('Tytuł')
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record))
                                ->columnSpanFull(),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->regex('/\\A[a-z0-9-]+\\z/')
                                ->maxLength(255)
                                ->required(fn (?ContentArticle $record): bool => $record !== null)
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record))
                                ->helperText('Przy tworzeniu może pozostać pusty — ContentArticleSlugService wygeneruje i zarezerwuje bezpieczny slug.')
                                ->columnSpanFull(),
                            Select::make('author_id')
                                ->label('Autor publiczny')
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez autora na etapie draftu')
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record)),
                            Select::make('reviewer_id')
                                ->label('Reviewer publiczny')
                                ->relationship('reviewer', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez reviewera')
                                ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record)),
                        ])
                        ->columns(2),
                    Section::make('Stan')
                        ->description('Workflow jest tylko informacyjny w shellu. Zmiany statusu będą wykonywane przez dedykowane actions/service.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Placeholder::make('workflow_status_display')
                                ->label('Workflow')
                                ->content(fn (?ContentArticle $record): string => static::workflowLabel($record)),
                            Placeholder::make('publication_state_display')
                                ->label('Widoczność')
                                ->content(fn (?ContentArticle $record): string => $record?->isPubliclyVisible()
                                    ? 'Publiczny rekord — publiczne pola są read-only w zwykłym Save.'
                                    : 'Niepubliczny rekord / draft.'),
                            Placeholder::make('body_editor_status')
                                ->label('Treść blokowa')
                                ->content('Builder/RichEditor nie należy do N2-002; wdraża go NEWSROOM-N2-003.'),
                        ]),
                ]),
                Section::make('Treść podstawowa')
                    ->description('Lead jest prostym polem shellu. Kanoniczny body_blocks pozostaje poza tym krokiem.')
                    ->schema([
                        Textarea::make('lead')
                            ->label('Lead')
                            ->rows(5)
                            ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Notatka wewnętrzna')
                    ->description('Pole tylko dla backoffice. Nie jest publiczną treścią artykułu i pozostaje edytowalne także dla publicznego rekordu.')
                    ->schema([
                        Textarea::make('editorial_note')
                            ->label('Notatka redakcyjna')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function typeOptions(): array
    {
        return [
            ContentArticleType::News->value => 'News',
            ContentArticleType::Guide->value => 'Poradnik',
            ContentArticleType::Explainer->value => 'Explainer',
            ContentArticleType::Analysis->value => 'Analiza',
            ContentArticleType::Report->value => 'Raport',
        ];
    }

    protected static function publicFieldsLocked(?ContentArticle $record): bool
    {
        return $record?->isPubliclyVisible() ?? false;
    }

    protected static function workflowLabel(?ContentArticle $record): string
    {
        if ($record === null) {
            return 'Draft po pierwszym zapisie.';
        }

        return match ($record->workflow_status?->value ?? (string) $record->workflow_status) {
            'draft' => 'Draft',
            'in_review' => 'W review',
            'scheduled' => 'Zaplanowany',
            'published' => 'Opublikowany',
            'needs_review' => 'Wymaga review',
            'archived' => 'Archiwalny',
            'withdrawn' => 'Wycofany',
            default => (string) $record->workflow_status,
        };
    }
}
