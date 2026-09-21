<?php

namespace App\Filament\Resources\HomepageVideos\Tables;

use App\Models\HomepageVideo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HomepageVideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po tytule lub linku YouTube')
            ->emptyStateHeading('Brak materiałów')
            ->emptyStateDescription('Dodaj pierwszy film lub podcast do sekcji na stronie głównej.')
            ->emptyStateIcon(Heroicon::OutlinedPlayCircle)
            ->columns([
                TextColumn::make('title')
                    ->label('Materiał')
                    ->description(fn (HomepageVideo $record): string => $record->formattedDuration() ?? 'Czas nieuzupełniony')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('kind')
                    ->label('Rodzaj')
                    ->formatStateUsing(fn (string $state): string => $state === HomepageVideo::KIND_PODCAST ? 'Podcast' : 'Video')
                    ->badge()
                    ->sortable(),
                TextColumn::make('youtube_url')
                    ->label('YouTube')
                    ->limit(42)
                    ->url(fn (HomepageVideo $record): string => $record->youtube_url)
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('published_on')
                    ->label('Data publikacji')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Kolejność')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Opublikowany')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Rodzaj')
                    ->options([
                        HomepageVideo::KIND_VIDEO => 'Video',
                        HomepageVideo::KIND_PODCAST => 'Podcast',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Opublikowany'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
