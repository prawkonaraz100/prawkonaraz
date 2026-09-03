<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use App\Models\TrafficSign;
use App\Support\MediaUrlResolver;
use App\Support\QuestionExplanationAnnotationPayloadBuilder;
use App\Support\QuestionExplanationSignReferencePayloadBuilder;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\SharedQuestionScopeService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Kontekst pytania')
                        ->description('Kategoria, temat i identyfikatory źródłowe potrzebne do pracy z bazą.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            Select::make('license_category_id')
                                ->label('Kategoria')
                                ->relationship('licenseCategory', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('question_topic_id')
                                ->label('Temat')
                                ->relationship('questionTopic', 'name', fn ($query) => $query->where('is_active', true))
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez przypisanego tematu'),
                            TextInput::make('external_id')
                                ->label('ID źródła')
                                ->helperText('Identyfikator z wsadu lub oficjalnej bazy pytań.')
                                ->maxLength(255),
                            TextInput::make('source')
                                ->label('Źródło')
                                ->helperText('Na przykład: government-catalog, manual lub staging.')
                                ->maxLength(255),
                        ])
                        ->columns(2),
                    Section::make('Publikacja i parametry')
                        ->description('Parametry pytania i aktualny status rekordu w publikacji.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            Select::make('question_type')
                                ->label('Typ pytania')
                                ->options([
                                    'single_choice' => 'Jednokrotny wybór',
                                    'boolean' => 'Tak / nie',
                                ])
                                ->required()
                                ->default('single_choice'),
                            Select::make('correct_answer')
                                ->label('Poprawna odpowiedź')
                                ->options([
                                    'a' => 'A',
                                    'b' => 'B',
                                    'c' => 'C',
                                ])
                                ->helperText('Wybierz wariant zgodny z aktualnym układem odpowiedzi poniżej.')
                                ->required(),
                            TextInput::make('difficulty')
                                ->label('Trudność')
                                ->required()
                                ->numeric()
                                ->default(1),
                            TextInput::make('points')
                                ->label('Punkty')
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label('Aktywne')
                                ->inline(false)
                                ->required(),
                            DateTimePicker::make('published_at')
                                ->label('Opublikowano')
                                ->seconds(false),
                            Placeholder::make('delivery_status')
                                ->label('Status publikacji')
                                ->content(fn (?Question $record): string => $record?->deliveryStatus() ?? 'Status pojawi się po zapisaniu pytania.'),
                            Placeholder::make('delivery_issue')
                                ->label('Problem publikacji')
                                ->content(fn (?Question $record): string => $record?->deliveryIssueLabel() ?? 'Brak problemu'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Pytanie i karta odpowiedzi')
                    ->description('To jeden formularz dla całej karty odpowiedzi. Kursant najpierw zobaczy pytanie, a po odpowiedzi dostanie jedno tłumaczenie z opcjonalną grafiką.')
                    ->schema([
                        Textarea::make('prompt')
                            ->label('Treść pytania')
                            ->helperText('Formatowanie: **tekst** (pogrubienie), [green]tekst[/green] (zielony), [red]tekst[/red] (czerwony).')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('explanation')
                            ->label('Wyjaśnienie')
                            ->helperText('To główny tekst na karcie odpowiedzi. Użyj **tekst** (pogrubienie), [green]tekst[/green] (zielony), [red]tekst[/red] (czerwony).')
                            ->rows(5)
                            ->columnSpanFull(),
                        Select::make('explanation_asset.traffic_sign_id')
                            ->label('Znak drogowy z bazy')
                            ->helperText('Jeśli wybierzesz znak z bazy, system automatycznie wyświetli jego grafikę (bez tekstu). Ręczne pola zostaną ukryte.')
                            ->options(fn () => TrafficSign::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('explanation_asset.file_path', null);
                                    $set('explanation_asset.title', null);
                                    $set('explanation_asset.body', null);
                                    $set('explanation_asset.caption', null);
                                    $set('explanation_asset.alt_text', null);

                                    $sign = TrafficSign::find($state);
                                    if ($sign?->image_path) {
                                        $set('explanation_asset.traffic_sign_image_url', app(MediaUrlResolver::class)->resolve($sign->image_path, 'public'));
                                    } else {
                                        $set('explanation_asset.traffic_sign_image_url', null);
                                    }
                                } else {
                                    $set('explanation_asset.traffic_sign_image_url', null);
                                }
                            })
                            ->columnSpanFull(),
                        FileUpload::make('explanation_asset.file_path')
                            ->label('Grafika karty')
                            ->helperText('Jeśli to pytanie ma już grafikę, najpierw kliknij „Usuń”, a dopiero potem dodaj nowy plik. Po wybraniu nowego pliku poczekaj, aż zniknie status przesyłania, a dopiero potem kliknij „Zapisz”.')
                            ->image()
                            ->disk((string) config('media.public_disk', 'public'))
                            ->directory(static fn (?Question $record): string => self::resolveExplanationAssetDirectory($record))
                            ->visibility('public')
                            ->acceptedFileTypes(config('media.allowed_mime_types.image', []))
                            ->maxSize((int) ceil(((int) config('media.max_bytes.image', 8 * 1024 * 1024)) / 1024))
                            ->downloadable()
                            ->openable()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set('explanation_asset.preview_url', self::resolveUploadedExplanationAssetPreviewUrl($state));
                            })
                            ->visible(fn (Get $get) => ! $get('explanation_asset.traffic_sign_id'))
                            ->columnSpanFull(),
                        Select::make('explanation_asset_apply_scope')
                            ->label('Zakres zapisu grafiki')
                            ->options([
                                'single' => 'Tylko to pytanie',
                                'shared_external_id' => 'Wszystkie pytania z tym samym numerem źródłowym',
                            ])
                            ->default('single')
                            ->helperText(function (?Question $record): string {
                                if (! filled($record?->external_id)) {
                                    return 'To pytanie nie ma numeru źródłowego, więc grafika może zostać zapisana tylko lokalnie.';
                                }

                                return 'Wspólny zapis utworzy jedną grafikę dla całej grupy pytań i usunie lokalne grafiki z rekordów w tej grupie.';
                            })
                            ->visible(fn (?Question $record): bool => filled($record?->external_id))
                            ->columnSpanFull(),
                        Placeholder::make('explanation_asset_shared_warning')
                            ->label('Uwaga')
                            ->content('Ta grupa pytań ma już różne lokalne grafiki albo tylko część rekordów ma własny asset. Zapis wspólny ujednolici całą grupę do jednej grafiki.')
                            ->visible(fn (?Question $record): bool => $record instanceof Question
                                && filled($record->external_id)
                                && app(SharedQuestionScopeService::class)->hasSharedExplanationAssetConflict($record))
                            ->columnSpanFull(),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            TextInput::make('explanation_asset.title')
                                ->label('Tytuł')
                                ->helperText('Opcjonalny, krótki nagłówek, np. Znak A-12a.')
                                ->maxLength(120),
                            TextInput::make('explanation_asset.alt_text')
                                ->label('Alt text')
                                ->helperText('Wymagany, gdy zapisujesz grafikę.')
                                ->maxLength(255),
                        ])->visible(fn (Get $get) => ! $get('explanation_asset.traffic_sign_id')),
                        Textarea::make('explanation_asset.body')
                            ->label('Tekst zapasowy')
                            ->helperText('Opcjonalny fallback. Użyjemy go tylko wtedy, gdy pole Wyjaśnienie jest puste. Obsługuje **pogrubienie**.')
                            ->rows(4)
                            ->visible(fn (Get $get) => ! $get('explanation_asset.traffic_sign_id'))
                            ->columnSpanFull(),
                        Toggle::make('explanation_asset.is_active')
                            ->label('Grafika aktywna')
                            ->inline(false)
                            ->default(false),
                        View::make('filament.resources.questions.partials.reference-asset-preview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Znaki w wyjaśnieniu')
                    ->description('Kody znaków, np. B-20, są dodawane automatycznie. Użyj korekty tylko wtedy, gdy trzeba ukryć, zamienić albo dopisać miniaturę w konkretnym miejscu tekstu.')
                    ->schema([
                        View::make('filament.resources.questions.partials.explanation-sign-overrides-preview')
                            ->viewData(function (?Question $record): array {
                                if (! $record instanceof Question) {
                                    return [
                                        'explanation' => null,
                                        'references' => [],
                                    ];
                                }

                                return [
                                    'explanation' => $record->explanation,
                                    'references' => app(QuestionExplanationSignReferencePayloadBuilder::class)
                                        ->forQuestion($record),
                                ];
                            })
                            ->columnSpanFull(),
                        Select::make('explanation_sign_overrides_apply_scope')
                            ->label('Zakres zapisu korekt')
                            ->options([
                                'shared_external_id' => 'Wszystkie pytania z tym samym numerem źródłowym',
                                'single' => 'Tylko to pytanie',
                            ])
                            ->default('shared_external_id')
                            ->helperText(function (?Question $record): string {
                                if (! filled($record?->external_id)) {
                                    return 'To pytanie nie ma numeru źródłowego, więc korekta może zostać zapisana tylko lokalnie.';
                                }

                                return 'Wspólna korekta ustala bazę dla wszystkich kopii pytania. Istniejące lokalne wyjątki pozostają bez zmian i mają pierwszeństwo.';
                            })
                            ->visible(fn (?Question $record): bool => filled($record?->external_id))
                            ->columnSpanFull(),
                        Repeater::make('explanation_sign_overrides')
                            ->label('Korekty')
                            ->addActionLabel('Dodaj korektę znaku')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(function (array $state): string {
                                return match ($state['action'] ?? null) {
                                    'hide' => 'Ukryj '.($state['detected_code'] ?? 'znak'),
                                    'replace' => 'Zamień '.($state['detected_code'] ?? 'znak'),
                                    'add' => 'Dodaj po fragmencie tekstu',
                                    default => 'Nowa korekta',
                                };
                            })
                            ->schema([
                                Select::make('action')
                                    ->label('Akcja')
                                    ->options([
                                        'hide' => 'Ukryj automatycznie wykryty znak',
                                        'replace' => 'Zamień automatycznie wykryty znak',
                                        'add' => 'Dodaj znak po fragmencie tekstu',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('detected_code')
                                    ->label('Kod znaku w wyjaśnieniu')
                                    ->helperText('Wpisz dokładny kod z tekstu, np. B-20.')
                                    ->maxLength(32)
                                    ->visible(fn (Get $get): bool => in_array($get('action'), ['hide', 'replace'], true)),
                                TextInput::make('anchor_text')
                                    ->label('Unikalny fragment wyjaśnienia')
                                    ->helperText('Znak pojawi się zaraz po tym fragmencie. Fragment musi wystąpić w wyjaśnieniu dokładnie raz.')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => $get('action') === 'add'),
                                Select::make('traffic_sign_id')
                                    ->label('Znak z bazy')
                                    ->options(fn (): array => TrafficSign::query()
                                        ->published()
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn (TrafficSign $sign): array => [
                                            $sign->getKey() => trim("{$sign->code} - {$sign->name}"),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => in_array($get('action'), ['replace', 'add'], true)),
                                Toggle::make('is_active')
                                    ->label('Korekta aktywna')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Adnotacje do medium')
                    ->description('Najpierw pracuj na podgladzie obrazu albo kadru filmu. Precyzyjne pola nizej zostaja tylko do wyjatkow i recznych poprawek.')
                    ->schema([
                        View::make('filament.resources.questions.partials.annotation-editor')
                            ->viewData(function (?Question $record): array {
                                if (! $record instanceof Question) {
                                    return [
                                        'image' => null,
                                        'video' => null,
                                        'savedAnnotations' => [],
                                    ];
                                }

                                $record->loadMissing('media', 'explanationAnnotations');

                                $media = collect(app(QuestionMediaPayloadBuilder::class)->forQuestion($record->media));
                                $image = $media
                                    ->first(fn (array $media): bool => ($media['kind'] ?? null) === 'image');
                                $video = $media
                                    ->first(fn (array $media): bool => ($media['kind'] ?? null) === 'video');

                                return [
                                    'image' => $image,
                                    'video' => $video,
                                    'savedAnnotations' => app(QuestionExplanationAnnotationPayloadBuilder::class)
                                        ->forRuntime($record->explanationAnnotations),
                                ];
                            })
                            ->columnSpanFull(),
                        Select::make('explanation_annotations_apply_scope')
                            ->label('Zakres zapisu adnotacji')
                            ->options([
                                'single' => 'Tylko to pytanie',
                                'shared_external_id' => 'Wszystkie pytania z tym samym numerem źródłowym',
                            ])
                            ->default('shared_external_id')
                            ->helperText(function (?Question $record): string {
                                if (! filled($record?->external_id)) {
                                    return 'To pytanie nie ma numeru źródłowego, więc adnotacje mogą zostać zapisane tylko lokalnie.';
                                }

                                return 'Wspólny zapis powieli powyższe markery i skopiuje je na wszystkie pytania z tej samej grupy, nadpisując ich dotychczasowe adnotacje.';
                            })
                            ->visible(fn (?Question $record): bool => filled($record?->external_id))
                            ->columnSpanFull(),
                        Section::make('Tryb zaawansowany')
                            ->description('Uzyj tylko wtedy, gdy chcesz recznie poprawic wartosci procentowe albo ustawienia kadru filmu.')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Repeater::make('explanation_annotations')
                                    ->label('Zaawansowane pola adnotacji')
                                    ->addActionLabel('Dodaj adnotację')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(function (array $state): ?string {
                                        $type = filled($state['annotation_type'] ?? null)
                                            ? mb_strtoupper((string) $state['annotation_type'])
                                            : 'NOWA';
                                        $label = trim((string) ($state['label'] ?? ''));

                                        return $label !== '' ? "{$type} · {$label}" : $type;
                                    })
                                    ->schema([
                                        Grid::make([
                                            'lg' => 2,
                                        ])->schema([
                                            Select::make('target_kind')
                                                ->label('Obszar')
                                                ->options([
                                                    'question_image' => 'Obraz pytania',
                                                    'video_frame' => 'Stopklatka wideo',
                                                ])
                                                ->default('question_image')
                                                ->required(),
                                            Select::make('annotation_type')
                                                ->label('Typ')
                                                ->options([
                                                    'label' => 'Etykieta',
                                                    'text' => 'Tekst',
                                                    'circle' => 'Okrag',
                                                    'arrow' => 'Strzalka',
                                                ])
                                                ->default('label')
                                                ->required(),
                                            Select::make('tone')
                                                ->label('Ton')
                                                ->options([
                                                    'info' => 'Info',
                                                    'warning' => 'Warning',
                                                    'danger' => 'Danger',
                                                ])
                                                ->default('info'),
                                        ]),
                                        TextInput::make('label')
                                            ->label('Tekst markera')
                                            ->helperText('Wymagane dla typu label i text.')
                                            ->maxLength(120),
                                        Grid::make([
                                            'lg' => 5,
                                        ])->schema([
                                            TextInput::make('frame_time_seconds')
                                                ->label('Sekunda stopklatki')
                                                ->helperText('Wymagane dla obszaru stopklatki wideo.')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('x_percent')
                                                ->label('X %')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            TextInput::make('y_percent')
                                                ->label('Y %')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            TextInput::make('width_percent')
                                                ->label('Szer. %')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100),
                                            TextInput::make('height_percent')
                                                ->label('Wys. %')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100),
                                        ]),
                                        Grid::make([
                                            'lg' => 4,
                                        ])->schema([
                                            TextInput::make('arrow_length_percent')
                                                ->label('Dlugosc strz. %')
                                                ->helperText('Dla typu arrow: 1-100.')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(100)
                                                ->step(0.01),
                                            TextInput::make('arrow_angle_degrees')
                                                ->label('Kat strz. °')
                                                ->helperText('Dla typu arrow: 0-359.')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(359)
                                                ->step(1),
                                            TextInput::make('arrow_stroke_percent')
                                                ->label('Grubosc strz. %')
                                                ->helperText('Dla typu arrow: 0.5-8.')
                                                ->numeric()
                                                ->minValue(0.5)
                                                ->maxValue(8)
                                                ->step(0.01),
                                            TextInput::make('arrow_head_percent')
                                                ->label('Grot strz. %')
                                                ->helperText('Dla typu arrow: 2-30.')
                                                ->numeric()
                                                ->minValue(2)
                                                ->maxValue(30)
                                                ->step(0.01),
                                        ]),
                                        Grid::make([
                                            'lg' => 2,
                                        ])->schema([
                                            TextInput::make('position')
                                                ->label('Pozycja')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1),
                                            Toggle::make('is_active')
                                                ->label('Aktywna')
                                                ->inline(false)
                                                ->default(true),
                                        ]),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Odpowiedzi')
                    ->description('Ułóż warianty odpowiedzi i wskaż poprawny wariant w prawej kolumnie u góry.')
                    ->schema([
                        Grid::make([
                            'lg' => 3,
                        ])->schema([
                            Textarea::make('option_a')
                                ->label('Odpowiedź A')
                                ->rows(4)
                                ->required(),
                            Textarea::make('option_b')
                                ->label('Odpowiedź B')
                                ->rows(4)
                                ->required(),
                            Textarea::make('option_c')
                                ->label('Odpowiedź C')
                                ->rows(4)
                                ->placeholder('Opcjonalna przy pytaniach jednokrotnego wyboru.'),
                        ]),
                    ]),
            ]);
    }

    protected static function resolveUploadedExplanationAssetPreviewUrl(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return rescue(
                callback: static fn (): string => $state->temporaryUrl(),
                rescue: null,
                report: false,
            );
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                $previewUrl = self::resolveUploadedExplanationAssetPreviewUrl($item);

                if ($previewUrl !== null) {
                    return $previewUrl;
                }
            }
        }

        return null;
    }

    protected static function resolveExplanationAssetDirectory(?Question $record): string
    {
        return 'question-explanations';
    }
}
