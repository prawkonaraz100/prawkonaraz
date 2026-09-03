<?php

namespace App\Filament\Resources\QuestionRelations\Tables;

use App\Models\QuestionRelation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class QuestionRelationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po identyfikatorze pytania')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('leftExplanation.external_id')
                    ->label('Pytanie A')
                    ->searchable()
                    ->description(fn (QuestionRelation $record): string => Str::limit((string) $record->leftExplanation?->question?->prompt, 90))
                    ->wrap(),
                TextColumn::make('rightExplanation.external_id')
                    ->label('Pytanie B')
                    ->searchable()
                    ->description(fn (QuestionRelation $record): string => Str::limit((string) $record->rightExplanation?->question?->prompt, 90))
                    ->wrap(),
                TextColumn::make('relation_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Wynik')
                    ->numeric(3)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Źródło')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    QuestionRelation::STATUS_VERIFIED => 'Zweryfikowana',
                    QuestionRelation::STATUS_AUTOMATIC => 'Automatyczna',
                    QuestionRelation::STATUS_CANDIDATE => 'Kandydat',
                    QuestionRelation::STATUS_REJECTED => 'Odrzucona',
                ]),
                SelectFilter::make('source')->options([
                    QuestionRelation::SOURCE_EDITORIAL => 'Redakcja',
                    QuestionRelation::SOURCE_GRAPH => 'Graf',
                ]),
                SelectFilter::make('relation_type')->options([
                    'blizniacze' => 'Bliźniacze',
                    'ta_sama_zasada' => 'Ta sama zasada',
                    'wariant' => 'Wariant',
                    'kontrast' => 'Kontrast',
                    'nie_pomyl_z' => 'Nie pomyl z',
                    'rozszerzenie' => 'Rozszerzenie',
                    'tematyczne' => 'Tematyczne',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('Weryfikuj'),
            ])
            ->defaultSort('score', 'desc');
    }
}
