<?php

namespace App\Filament\Resources\LicenseCategories\RelationManagers;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryHero;
use App\Support\AuditLogService;
use App\Support\QuestionTopicLabelResolver;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class QuestionTopicCategoryHeroesRelationManager extends RelationManager
{
    protected static string $relationship = 'questionTopicCategoryHeroes';

    protected static ?string $title = 'Zdjęcia działów w tej kategorii';

    protected static ?string $modelLabel = 'zdjęcie działu';

    protected static ?string $pluralModelLabel = 'zdjęcia działów';

    /**
     * @var array<string, mixed>
     */
    protected array $heroSnapshotBeforeEdit = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('question_topic_id')
                    ->label('Dział techniczny')
                    ->options(fn (): array => $this->topicOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?QuestionTopicCategoryHero $record): bool => $record !== null)
                    ->rules([
                        fn (?QuestionTopicCategoryHero $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if (! $value) {
                                return;
                            }

                            $exists = QuestionTopicCategoryHero::query()
                                ->where('license_category_id', $this->ownerCategory()->getKey())
                                ->where('question_topic_id', (int) $value)
                                ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail('Ten dział ma już własne zdjęcie w tej kategorii.');
                            }
                        },
                    ])
                    ->helperText('Wybierz dział, dla którego ta kategoria ma dostać własne zdjęcie. Jeśli go nie ustawisz, dział odziedziczy obraz globalny.'),
                FileUpload::make('hero_image_path')
                    ->label('Zdjęcie banera')
                    ->disk((string) config('media.public_disk', 'public'))
                    ->directory(fn (): string => $this->heroDirectory())
                    ->visibility('public')
                    ->acceptedFileTypes(config('media.allowed_mime_types.image', []))
                    ->maxSize((int) ceil(((int) config('media.max_bytes.image', 8 * 1024 * 1024)) / 1024))
                    ->previewable(false)
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->columnSpanFull()
                    ->helperText('Rekomendacja: WebP/JPG, około 1200x520, bez ważnego tekstu. Główny obiekt najlepiej po prawej stronie.'),
                TextInput::make('hero_image_alt')
                    ->label('Opis obrazu')
                    ->maxLength(255)
                    ->helperText('Opis administracyjny i przyszły opis publiczny. W obecnym banerze obraz jest dekoracyjny.'),
                Select::make('hero_image_position')
                    ->label('Kadrowanie')
                    ->options([
                        'center' => 'Środek',
                        'left center' => 'Lewa strona',
                        'right center' => 'Prawa strona',
                        'center top' => 'Góra',
                        'center bottom' => 'Dół',
                    ])
                    ->default('center')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktywne')
                    ->required()
                    ->default(true)
                    ->inline(false),
                Textarea::make('admin_note')
                    ->label('Notatka administracyjna')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Opcjonalnie: dlaczego ta kategoria potrzebuje innego zdjęcia działu.'),
            ])
            ->columns(2);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('questionTopic.key')
                    ->label('Klucz techniczny'),
                TextEntry::make('topic_label')
                    ->label('Nazwa działu w kategorii')
                    ->state(fn (QuestionTopicCategoryHero $record): string => $this->topicLabel($record)),
                TextEntry::make('hero_image_path')
                    ->label('Ścieżka zdjęcia')
                    ->placeholder('-'),
                TextEntry::make('hero_image_alt')
                    ->label('Opis obrazu')
                    ->placeholder('-'),
                TextEntry::make('hero_image_position')
                    ->label('Kadrowanie')
                    ->placeholder('center'),
                TextEntry::make('is_active')
                    ->label('Aktywne')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextEntry::make('admin_note')
                    ->label('Notatka')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('updatedBy.name')
                    ->label('Ostatnio zmienił')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('hero_image_path')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['questionTopic', 'updatedBy']))
            ->emptyStateHeading('Brak własnych zdjęć działów')
            ->emptyStateDescription('Ta kategoria dziedziczy globalne zdjęcia działów. Dodaj zdjęcie tylko wtedy, gdy kategoria potrzebuje innego kontekstu wizualnego.')
            ->columns([
                TextColumn::make('questionTopic.key')
                    ->label('Klucz techniczny')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('topic_label')
                    ->label('Nazwa działu')
                    ->state(fn (QuestionTopicCategoryHero $record): string => $this->topicLabel($record))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('hero_image_path')
                    ->label('Zdjęcie')
                    ->limit(42)
                    ->wrap(),
                TextColumn::make('hero_image_position')
                    ->label('Kadrowanie')
                    ->placeholder('center'),
                IconColumn::make('is_active')
                    ->label('Aktywne')
                    ->boolean(),
                TextColumn::make('updatedBy.name')
                    ->label('Ostatnio zmienił')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywne'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj zdjęcie działu')
                    ->mutateDataUsing(fn (array $data): array => $this->auditData($data, true))
                    ->after(fn (QuestionTopicCategoryHero $record): mixed => $this->recordHeroAudit(
                        'admin.question_topic_category_hero.created',
                        $record,
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edytuj')
                    ->before(fn (QuestionTopicCategoryHero $record): array => $this->heroSnapshotBeforeEdit = $this->heroAuditSnapshot($record))
                    ->mutateDataUsing(fn (array $data): array => $this->auditData($data))
                    ->after(fn (QuestionTopicCategoryHero $record): mixed => $this->recordHeroAudit(
                        'admin.question_topic_category_hero.updated',
                        $record,
                        $this->heroSnapshotBeforeEdit,
                    )),
                DeleteAction::make()
                    ->label('Usuń')
                    ->before(fn (QuestionTopicCategoryHero $record): array => $this->heroSnapshotBeforeEdit = $this->heroAuditSnapshot($record))
                    ->after(fn (QuestionTopicCategoryHero $record): mixed => $this->recordHeroAudit(
                        'admin.question_topic_category_hero.deleted',
                        $record,
                        $this->heroSnapshotBeforeEdit,
                    )),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<int, string>
     */
    protected function topicOptions(): array
    {
        $counts = Question::query()
            ->selectRaw('question_topic_id, count(*) as aggregate')
            ->where('license_category_id', $this->ownerCategory()->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('question_topic_id')
            ->groupBy('question_topic_id')
            ->pluck('aggregate', 'question_topic_id')
            ->mapWithKeys(fn (mixed $count, mixed $topicId): array => [(int) $topicId => (int) $count]);

        if ($counts->isEmpty()) {
            return [];
        }

        $topics = QuestionTopic::query()
            ->whereIn('id', $counts->keys()->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'sort_order']);
        $labels = app(QuestionTopicLabelResolver::class)->labelsForCategory($this->ownerCategory(), $topics);

        return $topics
            ->mapWithKeys(function (QuestionTopic $topic) use ($counts, $labels): array {
                $label = (string) $labels->get((int) $topic->getKey(), $topic->name);
                $count = number_format((int) ($counts->get((int) $topic->getKey()) ?? 0), 0, ',', ' ');

                return [
                    (int) $topic->getKey() => "{$label} ({$count} pytań, klucz: {$topic->key})",
                ];
            })
            ->all();
    }

    protected function topicLabel(QuestionTopicCategoryHero $record): string
    {
        $topic = $record->questionTopic;

        if (! $topic instanceof QuestionTopic) {
            return '-';
        }

        return app(QuestionTopicLabelResolver::class)->labelFor($this->ownerCategory(), $topic);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function auditData(array $data, bool $isCreate = false): array
    {
        $userId = auth()->id();
        $imagePath = $data['hero_image_path'] ?? null;

        if (is_array($imagePath)) {
            $imagePath = reset($imagePath) ?: null;
        }

        $data['hero_image_path'] = filled($imagePath) ? trim((string) $imagePath) : null;
        $data['hero_image_alt'] = filled($data['hero_image_alt'] ?? null) ? trim((string) $data['hero_image_alt']) : null;
        $data['hero_image_position'] = filled($data['hero_image_position'] ?? null) ? trim((string) $data['hero_image_position']) : 'center';
        $data['admin_note'] = filled($data['admin_note'] ?? null) ? trim((string) $data['admin_note']) : null;
        $data['updated_by'] = $userId;

        if ($isCreate) {
            $data['created_by'] = $userId;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    protected function recordHeroAudit(string $action, QuestionTopicCategoryHero $record, ?array $before = null): void
    {
        $record->loadMissing(['licenseCategory', 'questionTopic']);

        $after = $record->exists ? $this->heroAuditSnapshot($record) : null;
        $metadata = [
            'source' => 'admin_panel',
            'license_category_id' => (int) $record->license_category_id,
            'license_category_code' => (string) ($record->licenseCategory?->code ?? $this->ownerCategory()->code),
            'question_topic_id' => (int) $record->question_topic_id,
            'question_topic_key' => (string) ($record->questionTopic?->key ?? ''),
            'before' => $before,
            'after' => $after,
        ];

        app(AuditLogService::class)->record(
            $action,
            'question_topic_category_hero',
            (string) $record->getKey(),
            auth()->user(),
            $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function heroAuditSnapshot(QuestionTopicCategoryHero $record): array
    {
        return [
            'hero_image_path' => (string) $record->hero_image_path,
            'hero_image_alt' => $record->hero_image_alt,
            'hero_image_position' => $record->hero_image_position,
            'admin_note' => $record->admin_note,
            'is_active' => (bool) $record->is_active,
        ];
    }

    protected function heroDirectory(): string
    {
        return 'study/topic-heroes/'.Str::slug((string) $this->ownerCategory()->code);
    }

    protected function ownerCategory(): LicenseCategory
    {
        /** @var LicenseCategory $category */
        $category = $this->getOwnerRecord();

        return $category;
    }
}
