<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Models\ContentArticle;
use App\Support\NewsroomArticleRelationsEditorAdapter;
use App\Support\NewsroomArticleSourceEditorAdapter;
use App\Support\NewsroomBodyEditorAdapter;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

final class ContentArticlePublicUpdateForm
{
    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Hidden::make('_lock_version')->required(),
            Hidden::make('body_schema_version')->required(),
            Section::make('Aktualizacja publiczna')
                ->description('Po zatwierdzeniu zmiana stanie się publiczna natychmiast. Backend ponownie sprawdzi kompletność publikacyjną oraz wersję rekordu.')
                ->schema([
                    Select::make('category_id')
                        ->label('Kategoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('author_id')
                        ->label('Autor publiczny')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('reviewer_id')
                        ->label('Reviewer publiczny')
                        ->relationship('reviewer', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->regex('/\\A[a-z0-9-]+\\z/')
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('lead')
                        ->label('Lead')
                        ->rows(5)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Treść artykułu')
                ->schema([
                    FormBuilder::make('body_blocks')
                        ->label('Bloki treści')
                        ->blocks(ContentArticleForm::bodyBlocks())
                        ->addActionLabel('Dodaj blok')
                        ->blockPickerColumns(2)
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
            Section::make('Źródła')
                ->schema([
                    Repeater::make('sources')
                        ->label('Źródła')
                        ->defaultItems(0)
                        ->addActionLabel('Dodaj źródło')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->schema([
                            Hidden::make('id'),
                            Select::make('source_type')
                                ->label('Typ źródła')
                                ->options(ContentArticleForm::sourceTypeOptions())
                                ->native(false)
                                ->required(),
                            TextInput::make('publisher')
                                ->label('Wydawca / instytucja')
                                ->maxLength(255),
                            TextInput::make('title')
                                ->label('Tytuł źródła')
                                ->required()
                                ->maxLength(500)
                                ->columnSpanFull(),
                            TextInput::make('url')
                                ->label('URL')
                                ->helperText('Opcjonalny. Jeśli podany, musi używać http lub https.')
                                ->maxLength(2048)
                                ->rules(['nullable', 'url:http,https'])
                                ->columnSpanFull(),
                            DateTimePicker::make('published_at')
                                ->label('Opublikowano')
                                ->timezone('Europe/Warsaw')
                                ->seconds(false),
                            DateTimePicker::make('accessed_at')
                                ->label('Sprawdzono / dostęp')
                                ->timezone('Europe/Warsaw')
                                ->seconds(false),
                            Toggle::make('is_primary')
                                ->label('Źródło primary'),
                            Toggle::make('is_official')
                                ->label('Źródło oficjalne'),
                            Toggle::make('is_publicly_cited')
                                ->label('Cytowane publicznie')
                                ->default(true),
                            Textarea::make('note')
                                ->label('Notatka wewnętrzna')
                                ->helperText('Pozostaje wyłącznie w backoffice; nie trafi do AuditLog ani publicznej treści.')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Powiązania')
                ->schema([
                    ContentArticleForm::topicSelect()->columnSpanFull(),
                    Repeater::make('question_relations')
                        ->label('Pytania')
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            ContentArticleForm::questionRelationSelect(),
                            Select::make('relation_type')
                                ->label('Typ relacji')
                                ->options(ContentArticleForm::questionRelationTypeOptions())
                                ->default('related')
                                ->native(false)
                                ->required(),
                            Textarea::make('note')
                                ->label('Notatka wewnętrzna')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('legal_unit_relations')
                        ->label('Podstawy prawne')
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            ContentArticleForm::legalUnitSelect(),
                            Select::make('relation_type')
                                ->label('Typ relacji')
                                ->options(ContentArticleForm::legalRelationTypeOptions())
                                ->default('related')
                                ->native(false)
                                ->required(),
                            Textarea::make('note')
                                ->label('Notatka wewnętrzna')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('traffic_sign_relations')
                        ->label('Znaki drogowe')
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            ContentArticleForm::trafficSignRelationSelect(),
                            Select::make('relation_type')
                                ->label('Typ relacji')
                                ->options(ContentArticleForm::trafficSignRelationTypeOptions())
                                ->default('related')
                                ->native(false)
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function hydrate(ContentArticle $article): array
    {
        $data = NewsroomBodyEditorAdapter::hydrateArticleData($article->attributesToArray());
        $data = NewsroomArticleRelationsEditorAdapter::hydrateArticleData($data, $article);
        $data['sources'] = NewsroomArticleSourceEditorAdapter::hydrate($article);
        $data['_lock_version'] = $article->optimisticLockVersion();

        return $data;
    }
}
