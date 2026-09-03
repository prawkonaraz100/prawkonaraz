<?php

namespace App\Filament\Resources\TrafficSigns\Schemas;

use App\Models\TrafficSign;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class TrafficSignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość znaku')
                        ->description('Te pola budują URL, identyfikację znaku i podstawowe relacje contentowe.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            Select::make('traffic_sign_category_id')
                                ->label('Kategoria')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('content_author_id')
                                ->label('Autor')
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('code')
                                ->label('Kod znaku')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(20),
                            TextInput::make('name')
                                ->label('Nazwa znaku')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Publikacja')
                        ->description('Tutaj kontrolujesz, czy znak jest szkicem czy gotową stroną publiczną.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Opublikowany')
                                ->required()
                                ->inline(false),
                            DateTimePicker::make('published_at')
                                ->label('Data publikacji')
                                ->seconds(false),
                            TextInput::make('sort_order')
                                ->label('Kolejność w kategorii')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Placeholder::make('publication_status')
                                ->label('Stan rekordu')
                                ->content(fn (?TrafficSign $record): string => static::publicationStatusLabel($record)),
                            Placeholder::make('seo_status')
                                ->label('Gotowość SEO')
                                ->content(fn (?TrafficSign $record): string => static::seoStatusLabel($record)),
                        ]),
                ]),
                Section::make('Workflow redakcyjny')
                    ->description('Ten blok porządkuje review, źródła i przyszłe odświeżanie strony bez zgadywania, co jest gotowe do kolejnego kroku.')
                    ->schema([
                        Grid::make([
                            'lg' => 3,
                        ])->schema([
                            Select::make('workflow_status')
                                ->label('Workflow')
                                ->options(TrafficSign::workflowOptions())
                                ->default(TrafficSign::WORKFLOW_DRAFT)
                                ->native(false)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    if ($state !== TrafficSign::WORKFLOW_PUBLISHED) {
                                        return;
                                    }

                                    $set('is_published', true);

                                    if (blank($get('published_at'))) {
                                        $set('published_at', now()->format('Y-m-d H:i:s'));
                                    }
                                }),
                            Select::make('reviewer_user_id')
                                ->label('Reviewer')
                                ->relationship('reviewer', 'name', fn ($query) => $query->where('is_admin', true))
                                ->searchable()
                                ->preload()
                                ->placeholder('Bez przypisanego reviewera'),
                            DateTimePicker::make('reviewed_at')
                                ->label('Ostatni review')
                                ->seconds(false),
                            DateTimePicker::make('source_checked_at')
                                ->label('Źródła potwierdzone')
                                ->seconds(false),
                            DateTimePicker::make('freshness_review_due_at')
                                ->label('Kolejny przegląd')
                                ->seconds(false),
                        ]),
                        Textarea::make('review_notes')
                            ->label('Notatki z review')
                            ->rows(3)
                            ->helperText('Krótka notatka po passach redakcyjnych, legalnych albo SEO. Dzięki temu kolejne osoby wiedzą, co już zostało sprawdzone.')
                            ->columnSpanFull(),
                        Grid::make([
                            'lg' => 3,
                        ])->schema([
                            Placeholder::make('workflow_status_label')
                                ->label('Stan workflow')
                                ->content(fn (?TrafficSign $record): string => $record?->workflowLabel() ?? 'Workflow pojawi się po pierwszym zapisie.'),
                            Placeholder::make('source_status')
                                ->label('Stan źródeł')
                                ->content(fn (?TrafficSign $record): string => $record?->sourceVerificationLabel() ?? 'Po zapisaniu pokażemy stan źródeł.'),
                            Placeholder::make('freshness_status')
                                ->label('Freshness')
                                ->content(fn (?TrafficSign $record): string => $record?->freshnessStateLabel() ?? 'Po zapisaniu pokażemy termin kolejnego review.'),
                        ]),
                    ]),
                Section::make('Checklista publikacyjna')
                    ->description('Szybki pass przed wejściem w review albo przed publikacją. Dzięki temu zespół nie musi zgadywać, czy rekord naprawdę jest gotowy.')
                    ->schema([
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            Placeholder::make('publication_checklist_status')
                                ->label('Stan checklisty')
                                ->content(fn (?TrafficSign $record): string => $record?->publicationChecklistCompletionLabel() ?? 'Po pierwszym zapisie pokażemy stan checklisty.'),
                            Placeholder::make('publication_checklist_missing')
                                ->label('Brakujące elementy')
                                ->content(fn (?TrafficSign $record): string => static::publicationChecklistMissingSummary($record)),
                        ]),
                        Placeholder::make('publication_checklist_items')
                            ->label('Lista kontrolna')
                            ->content(fn (?TrafficSign $record): HtmlString => static::publicationChecklistHtml($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Treść główna')
                    ->description('To jest zasadnicza treść strony znaku. Zaczynamy od definicji i dalej schodzimy w praktykę kierowcy.')
                    ->schema([
                        Textarea::make('intro_definition')
                            ->label('Definicja otwierająca')
                            ->rows(3)
                            ->helperText('To powinien być pierwszy, możliwie precyzyjny akapit strony znaku.')
                            ->columnSpanFull(),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            Textarea::make('meaning')
                                ->label('Znaczenie')
                                ->rows(5),
                            Textarea::make('placement')
                                ->label('Gdzie występuje')
                                ->rows(5),
                            Textarea::make('driver_behavior')
                                ->label('Jak zachować się jako kierowca')
                                ->rows(5),
                            Textarea::make('common_mistakes')
                                ->label('Najczęstsze błędy')
                                ->rows(5),
                        ]),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            Textarea::make('legal_summary')
                                ->label('Podstawa prawna i kontekst')
                                ->rows(5),
                            Textarea::make('fine_summary')
                                ->label('Mandat / konsekwencje')
                                ->rows(5),
                        ]),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            TextInput::make('legal_reference_label')
                                ->label('Etykieta źródła prawnego')
                                ->maxLength(255),
                            TextInput::make('legal_reference_url')
                                ->label('URL źródła prawnego')
                                ->url()
                                ->maxLength(2048),
                        ]),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            Textarea::make('source_notes')
                                ->label('Notatki o źródłach')
                                ->rows(4)
                                ->helperText('Pole wewnętrzne dla redakcji: co sprawdziliśmy, z czego korzystaliśmy i co wymaga potwierdzenia.'),
                            Textarea::make('editorial_notes')
                                ->label('Notatki redakcyjne')
                                ->rows(4)
                                ->helperText('Pole wewnętrzne: pomysły na rozwinięcie strony, follow-up albo uwagi do kolejnego review.'),
                        ]),
                    ]),
                Section::make('FAQ i SEO')
                    ->description('FAQ i pola meta pomagają dopiąć stronę pod snippet, CTR i wygodę użytkownika.')
                    ->schema([
                        Repeater::make('faq_items')
                            ->label('FAQ')
                            ->addActionLabel('Dodaj pytanie')
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): string => filled($state['question'] ?? null) ? (string) $state['question'] : 'Nowe pytanie')
                            ->schema([
                                TextInput::make('question')
                                    ->label('Pytanie')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('answer')
                                    ->label('Odpowiedź')
                                    ->required()
                                    ->rows(4),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('meta_title')
                            ->label('Meta title')
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->label('Meta description')
                            ->rows(3)
                            ->maxLength(320),
                    ])
                    ->columns(2),
                Section::make('Assety')
                    ->description('Trzymamy tu nie tylko ścieżki, ale też alt i jawne wymiary, żeby publiczna warstwa SEO miała kompletny kontrakt assetów.')
                    ->schema([
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            TextInput::make('image_path')
                                ->label('Ścieżka obrazu znaku')
                                ->maxLength(2048)
                                ->placeholder('traffic-signs/a-7.svg'),
                            TextInput::make('image_alt')
                                ->label('Alt obrazu znaku')
                                ->maxLength(255),
                            TextInput::make('image_width')
                                ->label('Szerokość obrazu')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('image_height')
                                ->label('Wysokość obrazu')
                                ->numeric()
                                ->minValue(1),
                        ]),
                        Grid::make([
                            'lg' => 2,
                        ])->schema([
                            TextInput::make('og_image_path')
                                ->label('Ścieżka obrazu OG')
                                ->maxLength(2048)
                                ->placeholder('traffic-signs/og/a-7.png'),
                            TextInput::make('og_image_alt')
                                ->label('Alt obrazu OG')
                                ->maxLength(255),
                            TextInput::make('og_image_width')
                                ->label('Szerokość obrazu OG')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('og_image_height')
                                ->label('Wysokość obrazu OG')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    ])
                    ->columns(1),
            ]);
    }

    protected static function publicationStatusLabel(?TrafficSign $record): string
    {
        if (! $record instanceof TrafficSign) {
            return 'Status pojawi się po pierwszym zapisie.';
        }

        if (! $record->is_published) {
            return 'Szkic';
        }

        if ($record->published_at === null) {
            return 'Włączono publikację, ale brakuje daty.';
        }

        return $record->published_at->isFuture()
            ? 'Zaplanowany do publikacji.'
            : 'Opublikowany publicznie.';
    }

    protected static function seoStatusLabel(?TrafficSign $record): string
    {
        if (! $record instanceof TrafficSign) {
            return 'Po zapisaniu rekordu pokażemy brakujące pola SEO.';
        }

        $missing = static::missingSeoFields($record);

        if ($missing === []) {
            return 'Podstawowe pola SEO są uzupełnione.';
        }

        return 'Brakuje: '.implode(', ', $missing).'.';
    }

    protected static function publicationChecklistMissingSummary(?TrafficSign $record): string
    {
        if (! $record instanceof TrafficSign) {
            return 'Po zapisaniu rekordu pokażemy brakujące elementy.';
        }

        $missing = $record->publicationChecklistMissingLabels();

        if ($missing === []) {
            return 'Brak. Rekord ma komplet podstawowych elementów do publikacji.';
        }

        return implode(', ', $missing);
    }

    protected static function publicationChecklistHtml(?TrafficSign $record): HtmlString
    {
        if (! $record instanceof TrafficSign) {
            return new HtmlString('<p class="text-sm text-gray-500">Po pierwszym zapisie pokażemy checklistę publikacyjną dla tego rekordu.</p>');
        }

        $items = collect($record->publicationChecklistItems())
            ->map(function (array $item): string {
                $marker = $item['complete'] ? '[OK]' : '[ ]';

                return '<li>'.$marker.' '.e($item['label']).'</li>';
            })
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-1 pl-5 text-sm">'.$items.'</ul>');
    }

    /**
     * @return list<string>
     */
    protected static function missingSeoFields(TrafficSign $record): array
    {
        $fields = [];

        if (! filled($record->meta_title)) {
            $fields[] = 'meta title';
        }

        if (! filled($record->meta_description)) {
            $fields[] = 'meta description';
        }

        if (! filled($record->image_path)) {
            $fields[] = 'obraz znaku';
        }

        if (! filled($record->og_image_path)) {
            $fields[] = 'obraz OG';
        }

        return $fields;
    }
}
