<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Support\NewsroomBodyContract;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;

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
                        ->description('Podstawowe pola artykułu. Workflow, źródła, media i relacje redakcyjne mają osobne etapy N2.')
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
                        ->description('Workflow jest tylko informacyjny na tym etapie. Zmiany statusu będą wykonywane przez dedykowane actions/service.')
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
                                ->content('Builder zapisuje wyłącznie kontrolowany body_blocks schema v1. Embed pozostaje wyłączony do osobnego security/CSP gate.'),
                        ]),
                ]),
                Section::make('Treść podstawowa')
                    ->description('Lead pozostaje osobnym polem. Body jest zapisywane jako kontrolowana lista bloków, nie jako dowolny HTML.')
                    ->schema([
                        Textarea::make('lead')
                            ->label('Lead')
                            ->rows(5)
                            ->disabled(fn (?ContentArticle $record): bool => static::publicFieldsLocked($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Treść artykułu')
                    ->description('Kolejność bloków jest częścią treści. Rich text zapisuje TipTap JSON; backend ponownie waliduje cały dokument przez NewsroomBodyContract.')
                    ->schema([
                        FormBuilder::make('body_blocks')
                            ->label('Bloki treści')
                            ->blocks(static::bodyBlocks())
                            ->addActionLabel('Dodaj blok')
                            ->blockPickerColumns(2)
                            ->reorderableWithButtons()
                            ->collapsible()
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
     * @return list<Block>
     */
    protected static function bodyBlocks(): array
    {
        return [
            Block::make(NewsroomBodyContract::BLOCK_RICH_TEXT)
                ->label('Treść')
                ->schema([
                    RichEditor::make('content')
                        ->label('Treść')
                        ->json()
                        ->toolbarButtons(NewsroomBodyContract::richTextToolbarButtons())
                        ->required()
                        ->columnSpanFull(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_IMAGE)
                ->label('Obraz')
                ->schema([
                    TextInput::make('path')
                        ->label('Ścieżka assetu')
                        ->helperText('Ścieżka względna storage. Upload, weryfikacja obiektu i crop mają osobny etap media N2.')
                        ->required()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    TextInput::make('alt')
                        ->label('Alt')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Podpis')
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    TextInput::make('credit')
                        ->label('Autor / źródło grafiki')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('width')
                        ->label('Szerokość px')
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('height')
                        ->label('Wysokość px')
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('focal_x')
                        ->label('Focal X')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01),
                    TextInput::make('focal_y')
                        ->label('Focal Y')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01),
                ])
                ->columns(2),
            Block::make(NewsroomBodyContract::BLOCK_QUOTE)
                ->label('Cytat')
                ->schema([
                    Textarea::make('text')
                        ->label('Cytat')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('attribution')
                        ->label('Atrybucja')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('source_url')
                        ->label('URL źródła')
                        ->helperText('Dozwolony jest adres http/https, ścieżka wewnętrzna od / albo fragment #.')
                        ->maxLength(2048),
                ])
                ->columns(2),
            Block::make(NewsroomBodyContract::BLOCK_TABLE)
                ->label('Tabela')
                ->schema([
                    TextInput::make('caption')
                        ->label('Podpis tabeli')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TagsInput::make('headers')
                        ->label('Nagłówki kolumn')
                        ->required()
                        ->helperText('Kolejność nagłówków wyznacza liczbę i kolejność komórek w każdym wierszu.')
                        ->columnSpanFull(),
                    Repeater::make('rows')
                        ->label('Wiersze')
                        ->schema([
                            TagsInput::make('cells')
                                ->label('Komórki')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_CONTEXT)
                ->label('Kontekst / callout')
                ->schema([
                    Select::make('variant')
                        ->label('Wariant')
                        ->options(static::contextVariantOptions())
                        ->native(false)
                        ->required(),
                    TextInput::make('title')
                        ->label('Tytuł')
                        ->maxLength(255),
                    Textarea::make('text')
                        ->label('Treść')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Block::make(NewsroomBodyContract::BLOCK_RELATED_ARTICLE)
                ->label('Powiązany artykuł')
                ->schema([
                    static::relatedArticleSelect(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_LEGAL_REFERENCE)
                ->label('Podstawa prawna')
                ->schema([
                    static::legalUnitSelect(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_QUESTION_GROUP)
                ->label('Grupa pytań')
                ->schema([
                    static::questionSelect(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP)
                ->label('Grupa znaków')
                ->schema([
                    static::trafficSignSelect(),
                ]),
            Block::make(NewsroomBodyContract::BLOCK_PRODUCT_CTA)
                ->label('CTA produktu')
                ->schema([
                    Select::make('kind')
                        ->label('Rodzaj CTA')
                        ->options(static::productCtaOptions())
                        ->native(false)
                        ->required(),
                ]),
        ];
    }

    protected static function relatedArticleSelect(): Select
    {
        return Select::make('article_id')
            ->label('Artykuł')
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return ContentArticle::query()
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(title) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(slug) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get(['id', 'title', 'slug'])
                    ->mapWithKeys(fn (ContentArticle $article): array => [
                        $article->id => static::articleLabel($article),
                    ])
                    ->all();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                $article = ContentArticle::query()->find($value);

                return $article instanceof ContentArticle ? static::articleLabel($article) : null;
            })
            ->required();
    }

    protected static function legalUnitSelect(): Select
    {
        return Select::make('legal_unit_id')
            ->label('Jednostka prawna')
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return LegalUnit::query()
                    ->with('legalAct')
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(COALESCE(label, \'\')) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(COALESCE(title, \'\')) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(COALESCE(canonical_path, \'\')) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (LegalUnit $unit): array => [
                        $unit->id => static::legalUnitLabel($unit),
                    ])
                    ->all();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                $unit = LegalUnit::query()->with('legalAct')->find($value);

                return $unit instanceof LegalUnit ? static::legalUnitLabel($unit) : null;
            })
            ->required();
    }

    protected static function questionSelect(): Select
    {
        return Select::make('question_ids')
            ->label('Pytania')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return Question::query()
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(CAST(external_id AS TEXT)) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(prompt) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get(['id', 'external_id', 'prompt'])
                    ->mapWithKeys(fn (Question $question): array => [
                        $question->id => static::questionLabel($question),
                    ])
                    ->all();
            })
            ->getOptionLabelsUsing(fn (array $values): array => Question::query()
                ->whereIn('id', $values)
                ->get(['id', 'external_id', 'prompt'])
                ->mapWithKeys(fn (Question $question): array => [
                    $question->id => static::questionLabel($question),
                ])
                ->all())
            ->minItems(1)
            ->required();
    }

    protected static function trafficSignSelect(): Select
    {
        return Select::make('traffic_sign_ids')
            ->label('Znaki drogowe')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return TrafficSign::query()
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(code) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(slug) LIKE ?', [$needle]);
                    })
                    ->orderBy('code')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (TrafficSign $sign): array => [
                        $sign->id => $sign->publicTitle(),
                    ])
                    ->all();
            })
            ->getOptionLabelsUsing(fn (array $values): array => TrafficSign::query()
                ->whereIn('id', $values)
                ->get()
                ->mapWithKeys(fn (TrafficSign $sign): array => [
                    $sign->id => $sign->publicTitle(),
                ])
                ->all())
            ->minItems(1)
            ->required();
    }

    protected static function articleLabel(ContentArticle $article): string
    {
        return Str::limit((string) $article->title, 100).' · /'.$article->slug;
    }

    protected static function legalUnitLabel(LegalUnit $unit): string
    {
        $prefix = trim(implode(' · ', array_filter([
            $unit->legalAct?->short_title ?: $unit->legalAct?->title,
            $unit->label,
        ])));
        $title = Str::limit((string) $unit->title, 100);

        return $prefix !== '' ? $prefix.' — '.$title : $title;
    }

    protected static function questionLabel(Question $question): string
    {
        $externalId = trim((string) $question->external_id);
        $prompt = Str::limit(trim((string) $question->prompt), 120);

        return $externalId !== '' ? $externalId.' · '.$prompt : $prompt;
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

    /**
     * @return array<string, string>
     */
    protected static function contextVariantOptions(): array
    {
        return [
            'dlaczego_to_wazne' => 'Dlaczego to ważne',
            'co_sie_zmienia' => 'Co się zmienia',
            'uwaga' => 'Uwaga',
            'metodologia' => 'Metodologia',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function productCtaOptions(): array
    {
        return [
            'test' => 'Test',
            'related_questions' => 'Powiązane pytania',
            'learning' => 'Nauka',
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
