<?php

namespace App\Filament\Resources\ContentTopics\Tables;

use App\Models\ContentTopic;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentTopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po tytule, slugu lub opisie')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak tematów newsroomu')
            ->emptyStateDescription('Utwórz ręcznie topic/dossier i przypnij do niego świadomie wybrane artykuły.')
            ->emptyStateIcon(Heroicon::OutlinedHashtag)
            ->columns([
                TextColumn::make('title')
                    ->label('Topic')
                    ->searchable(['title', 'slug', 'description'])
                    ->sortable()
                    ->description(fn (ContentTopic $record): string => $record->slug)
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => match ((string) $state) {
                        ContentTopic::STATUS_DRAFT => 'Draft',
                        ContentTopic::STATUS_PUBLISHED => 'Opublikowany',
                        ContentTopic::STATUS_ARCHIVED => 'Archiwalny',
                        default => (string) $state,
                    })
                    ->sortable(),
                TextColumn::make('corpus_summary')
                    ->label('Corpus')
                    ->state(fn (ContentTopic $record): string => 'Eligible: '.number_format((int) $record->eligible_articles_count, 0, ',', ' ').' / '.ContentTopic::PUBLICATION_CORPUS_MINIMUM)
                    ->description(fn (ContentTopic $record): string => 'Wszystkie: '.number_format((int) $record->articles_count, 0, ',', ' '))
                    ->color(fn (ContentTopic $record): string => $record->isCorpusBelowBaseline() ? 'warning' : 'gray'),
                IconColumn::make('editorially_promotable')
                    ->label('Promocja')
                    ->state(fn (ContentTopic $record): bool => $record->isEditoriallyPromotable())
                    ->boolean(),
                TextColumn::make('featuredArticle.title')
                    ->label('Featured')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Pierwsza publikacja')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ContentTopic::STATUS_DRAFT => 'Draft',
                        ContentTopic::STATUS_PUBLISHED => 'Opublikowany',
                        ContentTopic::STATUS_ARCHIVED => 'Archiwalny',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
                DeleteAction::make()
                    ->label('Usuń')
                    ->disabled(fn (ContentTopic $record): bool => ! $record->canBeDeleted()),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
