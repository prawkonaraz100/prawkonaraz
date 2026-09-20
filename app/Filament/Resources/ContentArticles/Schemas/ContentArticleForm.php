<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Support\ContentArticlePublicationChecklist;
use App\Support\NewsroomArticleMediaService;
use App\Support\NewsroomBodyContract;
use App\Support\NewsroomMediaStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ContentArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('_edit_token')
                    ->dehydrated(fn (?ContentArticle $record): bool => $record !== null),
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość i klasyfikacja')
                        ->description('Podstawowe pola artykułu. Workflow i ekspozycja są sterowane dedykowanymi actions; provenance i media mają osobne kontrolowane sekcje.')
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
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                            Select::make('category_id')
                                ->label('Kategoria')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                            TextInput::make('title')
                                ->label('Tytuł')
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                                ->columnSpanFull(),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->regex('/\\A[a-z0-9-]+\\z/')
                                ->maxLength(255)
                                ->required(fn (?ContentArticle $record): bool => $record !== null)
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                                ->helperText('Przy tworzeniu może pozostać pusty — ContentArticleSlugService wygeneruje i zarezerwuje bezpieczny slug.')
                                ->columnSpanFull(),
                            Select::make('author_id')
                                ->label('Autor publiczny')
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez autora na etapie draftu')
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                            Select::make('reviewer_id')
                                ->label('Reviewer publiczny')
                                ->relationship('reviewer', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez reviewera')
                                ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        ])
                        ->columns(2),
                    Section::make('Stan')
                        ->description('Status jest read-only w formularzu. Zmiany workflow i ekspozycji wykonują dedykowane actions delegujące do service boundary.')
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
                Section::make('Pochodzenie i kontekst regulacyjny')
                    ->description('Pochodzenie i status regulacyjny używają enumów domenowych. Aktywny kontekst regulacyjny jest finalnie sprawdzany razem ze źródłami przez publication checklist.')
                    ->schema([
                        Select::make('origin_type')
                            ->label('Pochodzenie materiału')
                            ->options(static::originTypeOptions())
                            ->default(ContentArticleOriginType::Original->value)
                            ->native(false)
                            ->required()
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Select::make('regulatory_status')
                            ->label('Status regulacyjny')
                            ->options(static::regulatoryStatusOptions())
                            ->default(ContentArticleRegulatoryStatus::NotApplicable->value)
                            ->native(false)
                            ->required()
                            ->live()
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        DatePicker::make('effective_from')
                            ->label('Obowiązuje od')
                            ->helperText('Wymagane dla statusu „przyjęte — przyszłe” i „obowiązuje”.')
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Placeholder::make('regulatory_source_hint')
                            ->label('Źródło regulacyjne')
                            ->content('Dla aktywnego statusu backend wymaga publicznie cytowanego źródła official/legislation z bezpiecznym HTTP(S) URL.'),
                        Textarea::make('change_summary')
                            ->label('Co dokładnie się zmienia?')
                            ->rows(3)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        Textarea::make('applies_to')
                            ->label('Kogo dotyczy?')
                            ->rows(3)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Textarea::make('exam_impact')
                            ->label('Czy wpływa na egzamin?')
                            ->rows(3)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                    ])
                    ->columns(2),
                Section::make('Checklista publikacyjna')
                    ->description('Read-only stan zapisanej wersji rekordu. Te same domenowe reguły blokujące są ponownie egzekwowane przez ContentArticlePublishingService przy review, schedule i publish.')
                    ->schema([
                        Placeholder::make('publication_checklist')
                            ->label('Gotowość do publikacji')
                            ->content(fn (?ContentArticle $record): HtmlString|string => static::publicationChecklistHtml($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Treść podstawowa')
                    ->description('Lead pozostaje osobnym polem. Body jest zapisywane jako kontrolowana lista bloków, nie jako dowolny HTML.')
                    ->schema([
                        Textarea::make('lead')
                            ->label('Lead')
                            ->rows(5)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
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
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                    ]),
                Section::make('Źródła')
                    ->description('Źródła są rekordami relacyjnymi artykułu. Prywatne evidence pozostaje w backoffice i nie może być cytowane publicznie.')
                    ->schema([
                        Repeater::make('sources')
                            ->relationship('sources')
                            ->label('Źródła')
                            ->defaultItems(0)
                            ->addActionLabel('Dodaj źródło')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => static::sourceItemLabel($state))
                            ->schema([
                                Select::make('source_type')
                                    ->label('Typ źródła')
                                    ->options(static::sourceTypeOptions())
                                    ->native(false)
                                    ->required(),
                                TextInput::make('publisher')
                                    ->label('Wydawca / instytucja')
                                    ->maxLength(255),
                                TextInput::make('title')
                                    ->label('Tytuł źródła')
                                    ->required()
                                    ->maxLength(500)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->helperText('Opcjonalny. Jeśli podany, musi używać http lub https.')
                                    ->maxLength(2048)
                                    ->rules(['nullable', 'url:http,https'])
                                    ->live(onBlur: true)
                                    ->suffixAction(
                                        Action::make('openSourceUrl')
                                            ->icon(Heroicon::ArrowTopRightOnSquare)
                                            ->tooltip('Otwórz źródło')
                                            ->url(fn (Get $get): ?string => static::safeSourceUrl($get('url')))
                                            ->openUrlInNewTab()
                                            ->visible(fn (Get $get): bool => static::safeSourceUrl($get('url')) !== null),
                                    )
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
                                    ->label('Źródło primary')
                                    ->live(),
                                Toggle::make('is_official')
                                    ->label('Źródło oficjalne')
                                    ->live(),
                                Toggle::make('is_publicly_cited')
                                    ->label('Cytowane publicznie')
                                    ->helperText('Wyłącz dla wewnętrznego evidence; title/publisher/url nie mogą wtedy trafić do publicznego renderera.')
                                    ->default(true)
                                    ->live(),
                                Placeholder::make('source_status')
                                    ->label('Status źródła')
                                    ->content(fn (Get $get): string => static::sourceStatusLabel($get)),
                                Textarea::make('note')
                                    ->label('Notatka wewnętrzna')
                                    ->rows(3)
                                    ->helperText('Nigdy nie jest częścią publicznej citation.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                    ]),
                Section::make('Powiązania')
                    ->description('Relacje wskazują istniejące encje produktu. Pytania, przepisy i znaki zachowują kolejność redakcyjną; tematy nie mają ręcznego rankingu.')
                    ->schema([
                        static::topicSelect()
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        Repeater::make('question_relations')
                            ->label('Pytania')
                            ->defaultItems(0)
                            ->addActionLabel('Dodaj pytanie')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => static::relationItemLabel('Pytanie', $state['relation_type'] ?? null))
                            ->schema([
                                static::questionRelationSelect(),
                                Select::make('relation_type')
                                    ->label('Typ relacji')
                                    ->options(static::questionRelationTypeOptions())
                                    ->default('related')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('note')
                                    ->label('Notatka wewnętrzna')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        Repeater::make('legal_unit_relations')
                            ->label('Podstawy prawne')
                            ->defaultItems(0)
                            ->addActionLabel('Dodaj jednostkę prawną')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => static::relationItemLabel('Przepis', $state['relation_type'] ?? null))
                            ->schema([
                                static::legalUnitSelect(),
                                Select::make('relation_type')
                                    ->label('Typ relacji')
                                    ->options(static::legalRelationTypeOptions())
                                    ->default('related')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('note')
                                    ->label('Notatka wewnętrzna')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        Repeater::make('traffic_sign_relations')
                            ->label('Znaki drogowe')
                            ->defaultItems(0)
                            ->addActionLabel('Dodaj znak')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => static::relationItemLabel('Znak', $state['relation_type'] ?? null))
                            ->schema([
                                static::trafficSignRelationSelect(),
                                Select::make('relation_type')
                                    ->label('Typ relacji')
                                    ->options(static::trafficSignRelationTypeOptions())
                                    ->default('related')
                                    ->native(false)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                    ]),
                Section::make('Media / art direction')
                    ->description('Hero i dedykowany OG zapisują immutable newsroom source path. Backend ponownie odczytuje bytes, MIME i dimensions przez NewsroomMediaStorage. Cropy poniżej są tylko podglądem art direction — nie deklarują fizycznych wariantów.')
                    ->schema([
                        FileUpload::make('hero_image_path')
                            ->label('Hero image')
                            ->image()
                            ->disk(fn (): string => app(NewsroomMediaStorage::class)->disk())
                            ->visibility('public')
                            ->acceptedFileTypes(fn (): array => app(NewsroomMediaStorage::class)->allowedMimeTypes())
                            ->maxSize(fn (): int => (int) ceil(app(NewsroomMediaStorage::class)->maxBytes() / 1024))
                            ->saveUploadedFileUsing(
                                fn (BaseFileUpload $_component, TemporaryUploadedFile $file): string => app(NewsroomArticleMediaService::class)->store($file)['path'],
                            )
                            ->helperText('JPEG/PNG/WebP/AVIF. Zapis zawsze tworzy nowy immutable source path; istniejący publiczny URL nie jest nadpisywany.')
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        TextInput::make('hero_image_alt')
                            ->label('Hero alt')
                            ->maxLength(500)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Textarea::make('hero_image_caption')
                            ->label('Hero caption')
                            ->rows(2)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        TextInput::make('hero_focal_x')
                            ->label('Focal X')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->placeholder('0.50')
                            ->helperText('0 = lewa krawędź, 1 = prawa. Brak wartości oznacza środek.')
                            ->live(onBlur: true)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        TextInput::make('hero_focal_y')
                            ->label('Focal Y')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->placeholder('0.50')
                            ->helperText('0 = góra, 1 = dół. Brak wartości oznacza środek.')
                            ->live(onBlur: true)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Hidden::make('hero_image_width'),
                        Hidden::make('hero_image_height'),
                        Hidden::make('og_image_width'),
                        Hidden::make('og_image_height'),
                        Placeholder::make('hero_dimensions')
                            ->label('Zweryfikowane wymiary')
                            ->content(fn (Get $get): string => static::dimensionsLabel(
                                $get('hero_image_width'),
                                $get('hero_image_height'),
                            )),
                        Placeholder::make('hero_crop_preview')
                            ->label('Podgląd cropów z focal point')
                            ->content(fn (Get $get): HtmlString|string => static::cropPreviewHtml(
                                $get('hero_image_path'),
                                $get('hero_focal_x'),
                                $get('hero_focal_y'),
                            ))
                            ->columnSpanFull(),
                        FileUpload::make('og_image_path')
                            ->label('Dedykowany OG image')
                            ->image()
                            ->disk(fn (): string => app(NewsroomMediaStorage::class)->disk())
                            ->visibility('public')
                            ->acceptedFileTypes(fn (): array => app(NewsroomMediaStorage::class)->allowedMimeTypes())
                            ->maxSize(fn (): int => (int) ceil(app(NewsroomMediaStorage::class)->maxBytes() / 1024))
                            ->saveUploadedFileUsing(
                                fn (BaseFileUpload $_component, TemporaryUploadedFile $file): string => app(NewsroomArticleMediaService::class)->store($file)['path'],
                            )
                            ->helperText('Opcjonalny. Gdy brak, publiczny renderer może użyć hero jako fallback. Dedykowany OG wymaga własnego alt.')
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire))
                            ->columnSpanFull(),
                        TextInput::make('og_image_alt')
                            ->label('OG alt')
                            ->maxLength(500)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Placeholder::make('og_dimensions')
                            ->label('Zweryfikowane wymiary OG')
                            ->content(fn (Get $get): string => static::dimensionsLabel(
                                $get('og_image_width'),
                                $get('og_image_height'),
                            )),
                        TextInput::make('image_credit')
                            ->label('Credit obrazu')
                            ->maxLength(500)
                            ->helperText('Publiczny credit, jeśli jest wymagany.')
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::publicFieldsLocked($record, $livewire)),
                        Textarea::make('image_license_note')
                            ->label('Notatka licencyjna')
                            ->rows(3)
                            ->helperText('Tylko backoffice. Nie może trafić do publicznego renderera i może być zapisana bez public update.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Freshness')
                    ->description('Backoffice metadata do planowania ponownej weryfikacji. Termin overdue tworzy kolejkę pracy, ale nie zmienia automatycznie workflow ani publicznej dystrybucji.')
                    ->schema([
                        Placeholder::make('freshness_status_display')
                            ->label('Status freshness')
                            ->content(fn (?ContentArticle $record): string => static::freshnessStatusLabel($record)),
                        DateTimePicker::make('source_checked_at')
                            ->label('Źródła sprawdzone')
                            ->timezone('Europe/Warsaw')
                            ->seconds(false)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::freshnessFieldsLocked($record, $livewire))
                            ->helperText('Ręczny timestamp ostatniego sprawdzenia źródeł. Zapisuj zwykłym Save; pole jest wyłączone w trybie public update/correction.'),
                        DateTimePicker::make('freshness_review_due_at')
                            ->label('Review freshness do')
                            ->timezone('Europe/Warsaw')
                            ->seconds(false)
                            ->disabled(fn (?ContentArticle $record, mixed $livewire): bool => static::freshnessFieldsLocked($record, $livewire))
                            ->helperText('Po przekroczeniu terminu status staje się overdue. Sam termin nie wykonuje Mark needs review; zapisuj zwykłym Save.'),
                        Placeholder::make('reviewed_at_display')
                            ->label('Ostatnie review')
                            ->content(fn (?ContentArticle $record): string => static::dateTimeLabel($record?->reviewed_at)),
                        Placeholder::make('last_substantive_update_at_display')
                            ->label('Ostatnia istotna aktualizacja')
                            ->content(fn (?ContentArticle $record): string => static::dateTimeLabel($record?->last_substantive_update_at)),
                        Placeholder::make('public_state_changed_at_display')
                            ->label('Ostatnia zmiana public state')
                            ->content(fn (?ContentArticle $record): string => static::dateTimeLabel($record?->public_state_changed_at)),
                        Placeholder::make('freshness_due_soon_policy')
                            ->label('Due soon')
                            ->content('Brak zdefiniowanego progu policy — status nie jest obecnie wyliczany i nie może być hardcodowany bez osobnej decyzji.'),
                    ])
                    ->columns(2),
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
                    ->with('licenseCategory')
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
                ->with('licenseCategory')
                ->whereIn('id', $values)
                ->get(['id', 'external_id', 'prompt'])
                ->mapWithKeys(fn (Question $question): array => [
                    $question->id => static::questionLabel($question),
                ])
                ->all())
            ->minItems(1)
            ->required();
    }

    protected static function questionRelationSelect(): Select
    {
        return Select::make('question_id')
            ->label('Pytanie')
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return Question::query()
                    ->with('licenseCategory')
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(CAST(external_id AS TEXT)) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(prompt) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (Question $question): array => [
                        $question->id => static::questionLabel($question),
                    ])
                    ->all();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                $question = Question::query()->with('licenseCategory')->find($value);

                return $question instanceof Question ? static::questionLabel($question) : null;
            })
            ->required();
    }

    protected static function topicSelect(): Select
    {
        return Select::make('topic_ids')
            ->label('Tematy')
            ->helperText('Tematy nie mają ręcznej kolejności. Publiczny topic ma własny corpus i featured article.')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return ContentTopic::query()
                    ->where(function (EloquentBuilder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(title) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(slug) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get(['id', 'title', 'slug', 'status'])
                    ->mapWithKeys(fn (ContentTopic $topic): array => [
                        $topic->id => static::topicLabel($topic),
                    ])
                    ->all();
            })
            ->getOptionLabelsUsing(fn (array $values): array => ContentTopic::query()
                ->whereIn('id', $values)
                ->get(['id', 'title', 'slug', 'status'])
                ->mapWithKeys(fn (ContentTopic $topic): array => [
                    $topic->id => static::topicLabel($topic),
                ])
                ->all());
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

    protected static function trafficSignRelationSelect(): Select
    {
        return Select::make('traffic_sign_id')
            ->label('Znak drogowy')
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
                        $sign->id => static::trafficSignLabel($sign),
                    ])
                    ->all();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                $sign = TrafficSign::query()->find($value);

                return $sign instanceof TrafficSign ? static::trafficSignLabel($sign) : null;
            })
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
        $prompt = Str::limit(trim((string) $question->prompt), 110);
        $category = trim((string) $question->licenseCategory?->code);
        $active = $question->is_active ? 'aktywne' : 'nieaktywne';
        $public = $question->is_active
            && $question->published_at !== null
            && $question->published_at->lte(now())
            ? 'publiczne'
            : 'niepubliczne';

        $prefix = implode(' · ', array_values(array_filter([
            $externalId !== '' ? $externalId : null,
            $category !== '' ? 'kat. '.$category : null,
            $active,
            $public,
        ])));

        return $prefix !== '' ? $prefix.' — '.$prompt : $prompt;
    }

    protected static function trafficSignLabel(TrafficSign $sign): string
    {
        $state = $sign->isPubliclyVisible() ? 'publiczny' : 'niepubliczny';

        return $sign->publicTitle().' · '.$state;
    }

    protected static function topicLabel(ContentTopic $topic): string
    {
        return Str::limit((string) $topic->title, 100).' · '.$topic->status;
    }

    protected static function relationItemLabel(string $entity, mixed $relationType): string
    {
        $type = trim((string) $relationType);

        return $type !== '' ? $entity.' · '.$type : $entity;
    }

    /**
     * @return array<string, string>
     */
    protected static function questionRelationTypeOptions(): array
    {
        return [
            'direct' => 'Bezpośrednio dotyczy',
            'practice' => 'Do ćwiczenia',
            'background' => 'Kontekst',
            'related' => 'Powiązane',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function legalRelationTypeOptions(): array
    {
        return [
            'direct_basis' => 'Bezpośrednia podstawa',
            'changed_rule' => 'Zmieniany przepis',
            'supporting_context' => 'Kontekst prawny',
            'related' => 'Powiązane',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function trafficSignRelationTypeOptions(): array
    {
        return [
            'direct' => 'Bezpośrednio dotyczy',
            'example' => 'Przykład',
            'related' => 'Powiązane',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function sourceTypeOptions(): array
    {
        return [
            ContentArticleSourceType::Official->value => 'Oficjalne',
            ContentArticleSourceType::Legislation->value => 'Akt prawny / legislacja',
            ContentArticleSourceType::Institution->value => 'Instytucja',
            ContentArticleSourceType::PrimaryData->value => 'Dane pierwotne',
            ContentArticleSourceType::Interview->value => 'Wywiad / rozmowa',
            ContentArticleSourceType::Report->value => 'Raport',
            ContentArticleSourceType::Media->value => 'Media',
            ContentArticleSourceType::Other->value => 'Inne',
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function sourceItemLabel(array $state): string
    {
        $title = trim((string) ($state['title'] ?? ''));
        $title = $title !== '' ? Str::limit($title, 90) : 'Nowe źródło';

        $flags = array_values(array_filter([
            ($state['is_primary'] ?? false) ? 'PRIMARY' : null,
            ($state['is_official'] ?? false) ? 'OFFICIAL' : null,
            ($state['is_publicly_cited'] ?? true) ? 'PUBLIC' : 'TYLKO WEWNĘTRZNE',
        ]));

        return $flags === [] ? $title : $title.' · '.implode(' · ', $flags);
    }

    protected static function sourceStatusLabel(Get $get): string
    {
        return implode(' · ', array_values(array_filter([
            $get('is_primary') ? 'PRIMARY' : null,
            $get('is_official') ? 'OFFICIAL' : null,
            ($get('is_publicly_cited') ?? true) ? 'PUBLIC' : 'TYLKO WEWNĘTRZNE',
        ])));
    }

    protected static function safeSourceUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    protected static function publicationChecklistHtml(?ContentArticle $record): HtmlString|string
    {
        if ($record === null) {
            return 'Checklista pojawi się po pierwszym zapisie artykułu.';
        }

        $items = app(ContentArticlePublicationChecklist::class)->items($record);
        $blockingCount = collect($items)
            ->where('state', ContentArticlePublicationChecklist::STATE_BLOCKING)
            ->count();
        $warningCount = collect($items)
            ->where('state', ContentArticlePublicationChecklist::STATE_WARNING)
            ->count();

        $summary = $blockingCount === 0
            ? ($warningCount === 0
                ? 'Gotowe do publikacji.'
                : "Brak blokad · {$warningCount} ostrzeżeń.")
            : "{$blockingCount} blokad · {$warningCount} ostrzeżeń.";

        $rows = collect($items)
            ->map(function (array $item): string {
                $stateLabel = match ($item['state']) {
                    ContentArticlePublicationChecklist::STATE_BLOCKING => 'BLOKUJE',
                    ContentArticlePublicationChecklist::STATE_WARNING => 'OSTRZEŻENIE',
                    default => 'OK',
                };

                return '<li><strong>'.e($stateLabel).' · '.e($item['label']).'</strong> — '.e($item['message']).'</li>';
            })
            ->implode('');

        return new HtmlString(
            '<div><p><strong>'.e($summary).'</strong></p><ul style="margin-top:0.5rem;padding-left:1.25rem;list-style:disc">'
            .$rows
            .'</ul></div>',
        );
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

    protected static function freshnessFieldsLocked(?ContentArticle $record, mixed $livewire = null): bool
    {
        return ($record?->isPubliclyVisible() ?? false)
            && is_object($livewire)
            && method_exists($livewire, 'isPublicUpdateMode')
            && $livewire->isPublicUpdateMode();
    }

    protected static function publicFieldsLocked(?ContentArticle $record, mixed $livewire = null): bool
    {
        if (! ($record?->isPubliclyVisible() ?? false)) {
            return false;
        }

        return ! (
            is_object($livewire)
            && method_exists($livewire, 'isPublicUpdateMode')
            && $livewire->isPublicUpdateMode()
        );
    }

    /**
     * @return array<string, string>
     */
    protected static function originTypeOptions(): array
    {
        return [
            ContentArticleOriginType::Original->value => 'Oryginalny materiał',
            ContentArticleOriginType::Compiled->value => 'Opracowanie wielu źródeł',
            ContentArticleOriginType::OfficialSource->value => 'Na podstawie źródła oficjalnego',
            ContentArticleOriginType::DataAnalysis->value => 'Analiza danych',
            ContentArticleOriginType::LicensedAgency->value => 'Materiał agencyjny/licencjonowany',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function regulatoryStatusOptions(): array
    {
        return [
            ContentArticleRegulatoryStatus::NotApplicable->value => 'Nie dotyczy',
            ContentArticleRegulatoryStatus::Proposal->value => 'Projekt',
            ContentArticleRegulatoryStatus::Consultation->value => 'Konsultacje',
            ContentArticleRegulatoryStatus::OfficialAnnouncement->value => 'Oficjalna zapowiedź',
            ContentArticleRegulatoryStatus::AdoptedFuture->value => 'Przyjęte — przyszłe',
            ContentArticleRegulatoryStatus::InForce->value => 'Obowiązuje',
        ];
    }

    protected static function dimensionsLabel(mixed $width, mixed $height): string
    {
        $width = (int) $width;
        $height = (int) $height;

        return $width > 0 && $height > 0
            ? "{$width} × {$height} px"
            : 'Wymiary zostaną zapisane po server-side inspekcji assetu.';
    }

    protected static function cropPreviewHtml(mixed $path, mixed $focalX, mixed $focalY): HtmlString|string
    {
        if (! is_string($path) || trim($path) === '') {
            return 'Dodaj i zapisz hero, aby zobaczyć podgląd cropów.';
        }

        try {
            $url = app(NewsroomMediaStorage::class)->publicUrl(trim($path));
        } catch (\Throwable) {
            return 'Asset nie ma prawidłowego stabilnego newsroom URL.';
        }

        $x = is_numeric($focalX) ? max(0.0, min(1.0, (float) $focalX)) : 0.5;
        $y = is_numeric($focalY) ? max(0.0, min(1.0, (float) $focalY)) : 0.5;
        $position = number_format($x * 100, 1, '.', '').'% '.number_format($y * 100, 1, '.', '').'%';
        $safeUrl = e($url);
        $safePosition = e($position);

        $cards = [
            ['Lead 16:9', '16 / 9'],
            ['Standard 4:3', '4 / 3'],
            ['Compact 1:1', '1 / 1'],
        ];

        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">';

        foreach ($cards as [$label, $ratio]) {
            $html .= '<figure style="margin:0">'
                .'<div style="aspect-ratio:'.$ratio.';overflow:hidden;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">'
                .'<img src="'.$safeUrl.'" alt="" style="width:100%;height:100%;object-fit:cover;object-position:'.$safePosition.'">'
                .'</div>'
                .'<figcaption style="margin-top:6px;font-size:12px;color:#64748b">'.e($label).' · preview CSS, bez pliku wariantu</figcaption>'
                .'</figure>';
        }

        return new HtmlString($html.'</div>');
    }

    protected static function freshnessStatusLabel(?ContentArticle $record): string
    {
        return match ($record?->freshnessStatus()) {
            'fresh' => 'Fresh',
            'overdue' => 'Overdue',
            default => 'Not scheduled',
        };
    }

    protected static function dateTimeLabel(mixed $value): string
    {
        if (! $value instanceof \DateTimeInterface) {
            return '-';
        }

        return $value->setTimezone(new \DateTimeZone('Europe/Warsaw'))->format('d.m.Y H:i');
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
