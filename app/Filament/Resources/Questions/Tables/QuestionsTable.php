<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po treści pytania, ID źródła albo fragmencie odpowiedzi')
            ->persistFiltersInSession()
            ->filtersFormColumns(4)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak pytań do wyświetlenia')
            ->emptyStateDescription('Zmień filtry albo dodaj nowe pytanie do bazy.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('external_id')
                    ->label('ID źródła')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prompt')
                    ->label('Pytanie')
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::applyPromptSearchIgnoringInlineFormatting($query, $search))
                    ->limit(95)
                    ->wrap()
                    ->description(fn (Question $record): string => static::questionContext($record)),
                TextColumn::make('correct_answer_preview')
                    ->label('Poprawna')
                    ->state(fn (Question $record): string => static::answerPreview($record))
                    ->description(fn (Question $record): string => static::questionTypeLabel($record->question_type))
                    ->wrap(),
                TextColumn::make('difficulty')
                    ->label('Trudność')
                    ->numeric()
                    ->sortable()
                    ->description(fn (Question $record): string => "{$record->points} pkt"),
                TextColumn::make('delivery_status')
                    ->label('Publikacja')
                    ->badge()
                    ->state(fn (Question $record): string => $record->deliveryStatus())
                    ->color(fn (Question $record): string => $record->hasDeliveryIssue() ? 'danger' : 'success'),
                TextColumn::make('media_count')
                    ->label('Media')
                    ->counts('media')
                    ->description(fn (Question $record): string => $record->expectsPrimaryMedia() ? 'wymagane' : 'opcjonalne'),
                TextColumn::make('explanation_annotations_count')
                    ->label('Markery')
                    ->counts('explanationAnnotations')
                    ->badge()
                    ->color(fn (Question $record): string => $record->explanation_annotations_count > 0 ? 'warning' : 'gray')
                    ->description(fn (Question $record): string => $record->explanation_annotations_count > 0 ? 'wizualne objaśnienia' : 'brak markerów'),
                TextColumn::make('reference_explanation_asset_count')
                    ->label('Materiał ref.')
                    ->counts('referenceExplanationAsset')
                    ->badge()
                    ->color(fn (Question $record): string => $record->reference_explanation_asset_count > 0 ? 'info' : 'gray')
                    ->description(function (Question $record): string {
                        if ($record->reference_explanation_asset_count < 1) {
                            return 'brak bloku';
                        }

                        return $record->referenceExplanationAsset?->is_active
                            ? 'aktywny blok'
                            : 'blok wyłączony';
                    }),
                TextColumn::make('active_label')
                    ->label('Status')
                    ->state(fn (Question $record): string => $record->is_active ? 'Aktywne' : 'Nieaktywne')
                    ->badge()
                    ->color(fn (Question $record): string => $record->is_active ? 'gray' : 'danger'),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Źródło')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Opublikowano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('license_category_id')
                    ->label('Kategoria')
                    ->relationship('licenseCategory', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('question_topic_id')
                    ->label('Temat')
                    ->relationship('questionTopic', 'name', fn (Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('question_type')
                    ->label('Typ')
                    ->options([
                        'single_choice' => 'Jednokrotny wybór',
                        'boolean' => 'Tak / nie',
                    ]),
                SelectFilter::make('source')
                    ->label('Źródło')
                    ->options(fn (): array => Question::query()
                        ->whereNotNull('source')
                        ->where('source', '!=', '')
                        ->orderBy('source')
                        ->distinct()
                        ->pluck('source', 'source')
                        ->all()),
                TernaryFilter::make('is_active')
                    ->label('Aktywne'),
                TernaryFilter::make('requires_primary_media')
                    ->label('Wymaga medium'),
                TernaryFilter::make('has_visual_markers')
                    ->label('Markery')
                    ->placeholder('Wszystkie')
                    ->trueLabel('Z markerami')
                    ->falseLabel('Bez markerów')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('explanationAnnotations'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('explanationAnnotations'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('has_reference_material')
                    ->label('Materiał referencyjny')
                    ->placeholder('Wszystkie')
                    ->trueLabel('Z materiałem')
                    ->falseLabel('Bez materiału')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('referenceExplanationAsset'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('referenceExplanationAsset'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('delivery_issue')
                    ->label('Problem publikacji')
                    ->options([
                        Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA => 'Brak głównego medium',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
                Action::make('toggleActive')
                    ->label(fn (Question $record): string => $record->is_active ? 'Wyłącz' : 'Aktywuj')
                    ->icon(fn (Question $record) => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedCheckCircle)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Zmieniono status pytania')
                    ->action(function (Question $record): void {
                        $record->forceFill([
                            'is_active' => ! $record->is_active,
                        ])->save();
                    }),
                Action::make('media')
                    ->label('Media')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->url(fn (Question $record): string => QuestionResource::getUrl('media', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(
                fn (Builder $query, string $direction): Builder => $query
                    ->orderBy('updated_at', $direction)
                    ->orderBy('id', $direction),
            )
            ->defaultKeySort(false);
    }

    protected static function questionContext(Question $record): string
    {
        return implode(' · ', array_filter([
            $record->licenseCategory?->code ? 'Kategoria '.$record->licenseCategory->code : null,
            $record->questionTopic?->name,
            filled($record->source) ? $record->source : null,
        ]));
    }

    protected static function answerPreview(Question $record): string
    {
        $answer = strtolower((string) $record->correct_answer);

        return strtoupper($answer).' · '.static::answerOptionText($record, $answer);
    }

    protected static function answerOptionText(Question $record, string $answer): string
    {
        return Str::limit(match ($answer) {
            'a' => (string) $record->option_a,
            'b' => (string) $record->option_b,
            'c' => (string) ($record->option_c ?? ''),
            default => '-',
        }, 44);
    }

    protected static function questionTypeLabel(?string $questionType): string
    {
        return match ($questionType) {
            'boolean' => 'Tak / nie',
            'single_choice' => 'Jednokrotny wybór',
            default => filled($questionType) ? (string) $questionType : '-',
        };
    }

    protected static function applyPromptSearchIgnoringInlineFormatting(Builder $query, string $search): Builder
    {
        $normalizedSearch = static::normalizePromptSearchTerm($search);

        if ($normalizedSearch === '') {
            return $query;
        }

        $promptColumn = $query->getModel()->qualifyColumn('prompt');
        $normalizedPromptSql = "replace(replace(replace(replace(replace(lower(coalesce({$promptColumn}, '')), '**', ' '), '[green]', ' '), '[/green]', ' '), '[red]', ' '), '[/red]', ' ')";

        return $query->whereRaw("{$normalizedPromptSql} like ?", ['%'.$normalizedSearch.'%']);
    }

    protected static function normalizePromptSearchTerm(string $search): string
    {
        $normalized = (string) Str::of($search)
            ->lower()
            ->replace(['[green]', '[/green]', '[red]', '[/red]', '**'], ' ');

        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
