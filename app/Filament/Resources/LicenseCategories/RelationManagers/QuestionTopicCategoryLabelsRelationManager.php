<?php

namespace App\Filament\Resources\LicenseCategories\RelationManagers;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use App\Support\AuditLogService;
use App\Support\QuestionTopicLabelResolver;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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

class QuestionTopicCategoryLabelsRelationManager extends RelationManager
{
    protected static string $relationship = 'questionTopicCategoryLabels';

    protected static ?string $title = 'Nazwy działów w tej kategorii';

    protected static ?string $modelLabel = 'etykieta działu';

    protected static ?string $pluralModelLabel = 'etykiety działów';

    /**
     * @var array<string, mixed>
     */
    protected array $labelSnapshotBeforeEdit = [];

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
                    ->disabled(fn (?QuestionTopicCategoryLabel $record): bool => $record !== null)
                    ->rules([
                        fn (?QuestionTopicCategoryLabel $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if (! $value) {
                                return;
                            }

                            $exists = QuestionTopicCategoryLabel::query()
                                ->where('license_category_id', $this->ownerCategory()->getKey())
                                ->where('question_topic_id', (int) $value)
                                ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail('Ten dział ma już własną etykietę w tej kategorii.');
                            }
                        },
                    ])
                    ->helperText('Wybierz istniejący dział techniczny. To nie przenosi pytań, zmienia tylko nazwę widoczną dla tej kategorii.'),
                TextInput::make('display_name')
                    ->label('Nazwa widoczna dla kursanta')
                    ->required()
                    ->maxLength(120)
                    ->helperText('Krótka, naturalna nazwa działu dla tej konkretnej kategorii.'),
                Toggle::make('is_active')
                    ->label('Aktywna')
                    ->required()
                    ->default(true)
                    ->inline(false),
                Textarea::make('admin_note')
                    ->label('Notatka administracyjna')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Opcjonalnie: dlaczego ta kategoria potrzebuje innej nazwy działu.'),
            ])
            ->columns(2);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('questionTopic.key')
                    ->label('Klucz techniczny'),
                TextEntry::make('default_label')
                    ->label('Domyślna nazwa')
                    ->state(fn (QuestionTopicCategoryLabel $record): string => $this->defaultTopicLabel($record)),
                TextEntry::make('display_name')
                    ->label('Nazwa dla kategorii'),
                TextEntry::make('is_active')
                    ->label('Aktywna')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextEntry::make('admin_note')
                    ->label('Notatka')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('createdBy.name')
                    ->label('Utworzył')
                    ->placeholder('-'),
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
            ->recordTitleAttribute('display_name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['questionTopic', 'updatedBy']))
            ->emptyStateHeading('Brak własnych nazw działów')
            ->emptyStateDescription('Ta kategoria korzysta jeszcze z domyślnych nazw działów. Dodaj etykietę tylko wtedy, gdy nazwa ma być inna dla tej kategorii.')
            ->columns([
                TextColumn::make('questionTopic.key')
                    ->label('Klucz techniczny')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('default_label')
                    ->label('Domyślna nazwa')
                    ->state(fn (QuestionTopicCategoryLabel $record): string => $this->defaultTopicLabel($record))
                    ->wrap(),
                TextColumn::make('display_name')
                    ->label('Nazwa dla kategorii')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),
                IconColumn::make('is_active')
                    ->label('Aktywna')
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
                    ->label('Aktywna'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj etykietę działu')
                    ->mutateDataUsing(fn (array $data): array => $this->auditData($data, true))
                    ->after(fn (QuestionTopicCategoryLabel $record): mixed => $this->recordLabelAudit(
                        'admin.question_topic_category_label.created',
                        $record,
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edytuj')
                    ->before(fn (QuestionTopicCategoryLabel $record): array => $this->labelSnapshotBeforeEdit = $this->labelAuditSnapshot($record))
                    ->mutateDataUsing(fn (array $data): array => $this->auditData($data))
                    ->after(fn (QuestionTopicCategoryLabel $record): mixed => $this->recordLabelAudit(
                        'admin.question_topic_category_label.updated',
                        $record,
                        $this->labelSnapshotBeforeEdit,
                    )),
                DeleteAction::make()
                    ->label('Usuń')
                    ->before(fn (QuestionTopicCategoryLabel $record): array => $this->labelSnapshotBeforeEdit = $this->labelAuditSnapshot($record))
                    ->after(fn (QuestionTopicCategoryLabel $record): mixed => $this->recordLabelAudit(
                        'admin.question_topic_category_label.deleted',
                        $record,
                        $this->labelSnapshotBeforeEdit,
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

        return QuestionTopic::query()
            ->whereIn('id', $counts->keys()->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'sort_order'])
            ->mapWithKeys(function (QuestionTopic $topic) use ($counts): array {
                $label = app(QuestionTopicLabelResolver::class)->defaultLabelFor($topic);
                $count = number_format((int) ($counts->get((int) $topic->getKey()) ?? 0), 0, ',', ' ');

                return [
                    (int) $topic->getKey() => "{$label} ({$count} pytań, klucz: {$topic->key})",
                ];
            })
            ->all();
    }

    protected function defaultTopicLabel(QuestionTopicCategoryLabel $record): string
    {
        $topic = $record->questionTopic;

        if (! $topic instanceof QuestionTopic) {
            return '-';
        }

        return app(QuestionTopicLabelResolver::class)->defaultLabelFor($topic);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function auditData(array $data, bool $isCreate = false): array
    {
        $userId = auth()->id();

        $data['display_name'] = trim((string) ($data['display_name'] ?? ''));
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
    protected function recordLabelAudit(string $action, QuestionTopicCategoryLabel $record, ?array $before = null): void
    {
        $record->loadMissing(['licenseCategory', 'questionTopic']);

        $after = $record->exists ? $this->labelAuditSnapshot($record) : null;
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
            'question_topic_category_label',
            (string) $record->getKey(),
            auth()->user(),
            $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function labelAuditSnapshot(QuestionTopicCategoryLabel $record): array
    {
        return [
            'display_name' => (string) $record->display_name,
            'admin_note' => $record->admin_note,
            'is_active' => (bool) $record->is_active,
        ];
    }

    protected function ownerCategory(): LicenseCategory
    {
        /** @var LicenseCategory $category */
        $category = $this->getOwnerRecord();

        return $category;
    }
}
