<?php

namespace App\Filament\Resources\ContentArticles\Tables;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po tytule, slugu lub leadzie')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak artykułów')
            ->emptyStateDescription('Utwórz pierwszy draft artykułu newsroomu.')
            ->columns([
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable(['title', 'slug', 'lead'])
                    ->sortable()
                    ->description(fn (ContentArticle $record): string => (string) $record->slug)
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (mixed $state): string => static::typeLabel($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategoria')
                    ->sortable(),
                TextColumn::make('workflow_status')
                    ->label('Workflow')
                    ->formatStateUsing(fn (mixed $state): string => static::workflowLabel($state))
                    ->badge()
                    ->color(fn (mixed $state): string => static::workflowColor($state))
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Autor')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Publikacja')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('scheduled_for')
                    ->label('Zaplanowano')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_breaking')
                    ->label('Breaking')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('freshness_review_due_at')
                    ->label('Freshness review')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('workflow_status')
                    ->label('Workflow')
                    ->options(static::workflowOptions()),
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(static::typeOptions()),
                SelectFilter::make('category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('author_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                TernaryFilter::make('is_breaking')
                    ->label('Breaking'),
                TernaryFilter::make('scheduled')
                    ->label('Ma termin publikacji')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('scheduled_for'),
                        false: fn (Builder $query): Builder => $query->whereNull('scheduled_for'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('freshness_overdue')
                    ->label('Freshness overdue')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('freshness_review_due_at')
                            ->where('freshness_review_due_at', '<=', now()),
                        false: fn (Builder $query): Builder => $query
                            ->where(function (Builder $builder): void {
                                $builder
                                    ->whereNull('freshness_review_due_at')
                                    ->orWhere('freshness_review_due_at', '>', now());
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('published_at')
                    ->label('Data publikacji')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Od'),
                        DatePicker::make('until')
                            ->label('Do'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('published_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->defaultSort('updated_at', 'desc');
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
    protected static function workflowOptions(): array
    {
        return [
            ContentArticleWorkflowStatus::Draft->value => 'Draft',
            ContentArticleWorkflowStatus::InReview->value => 'W review',
            ContentArticleWorkflowStatus::Scheduled->value => 'Zaplanowany',
            ContentArticleWorkflowStatus::Published->value => 'Opublikowany',
            ContentArticleWorkflowStatus::NeedsReview->value => 'Wymaga review',
            ContentArticleWorkflowStatus::Archived->value => 'Archiwalny',
            ContentArticleWorkflowStatus::Withdrawn->value => 'Wycofany',
        ];
    }

    protected static function typeLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleType ? $state->value : (string) $state;

        return static::typeOptions()[$value] ?? $value;
    }

    protected static function workflowLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleWorkflowStatus ? $state->value : (string) $state;

        return static::workflowOptions()[$value] ?? $value;
    }

    protected static function workflowColor(mixed $state): string
    {
        $value = $state instanceof ContentArticleWorkflowStatus ? $state->value : (string) $state;

        return match ($value) {
            'published' => 'success',
            'scheduled' => 'info',
            'in_review', 'needs_review' => 'warning',
            'withdrawn' => 'danger',
            default => 'gray',
        };
    }
}
