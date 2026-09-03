<?php

namespace App\Filament\Resources\Questions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label('Typ')
                    ->options([
                        'image' => 'Obraz',
                        'video' => 'Wideo',
                    ])
                    ->required(),
                TextInput::make('disk')
                    ->label('Dysk')
                    ->required()
                    ->default((string) config('media.default_disk')),
                TextInput::make('path')
                    ->label('Ścieżka pliku')
                    ->required(),
                TextInput::make('poster_path')
                    ->label('Ścieżka postera'),
                TextInput::make('mime_type')
                    ->label('MIME type'),
                TextInput::make('bytes')
                    ->label('Rozmiar (B)')
                    ->numeric(),
                TextInput::make('duration_seconds')
                    ->label('Długość (s)')
                    ->numeric(),
                TextInput::make('width')
                    ->label('Szerokość')
                    ->numeric(),
                TextInput::make('height')
                    ->label('Wysokość')
                    ->numeric(),
                Select::make('variant')
                    ->label('Wariant')
                    ->options([
                        'full' => 'Full',
                        'thumb' => 'Thumb',
                        'poster' => 'Poster',
                    ])
                    ->required()
                    ->default('full'),
                TextInput::make('sort_order')
                    ->label('Kolejność')
                    ->required()
                    ->numeric()
                    ->default(0),
                KeyValue::make('metadata')
                    ->label('Metadane')
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('kind')
                    ->label('Typ'),
                TextEntry::make('disk')
                    ->label('Dysk'),
                TextEntry::make('path')
                    ->label('Ścieżka pliku'),
                TextEntry::make('poster_path')
                    ->label('Ścieżka postera')
                    ->placeholder('-'),
                TextEntry::make('mime_type')
                    ->label('MIME type')
                    ->placeholder('-'),
                TextEntry::make('bytes')
                    ->label('Rozmiar (B)')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('duration_seconds')
                    ->label('Długość (s)')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('width')
                    ->label('Szerokość')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('height')
                    ->label('Wysokość')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('variant')
                    ->label('Wariant')
                    ->placeholder('-'),
                TextEntry::make('sort_order')
                    ->label('Kolejność')
                    ->numeric(),
                TextEntry::make('metadata')
                    ->label('Metadane')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Utworzono')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Zaktualizowano')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                TextColumn::make('kind')
                    ->label('Typ')
                    ->searchable(),
                TextColumn::make('disk')
                    ->label('Dysk')
                    ->searchable(),
                TextColumn::make('path')
                    ->label('Ścieżka pliku')
                    ->searchable(),
                TextColumn::make('poster_path')
                    ->label('Poster')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label('MIME')
                    ->searchable(),
                TextColumn::make('bytes')
                    ->label('Rozmiar')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_seconds')
                    ->label('Długość')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('width')
                    ->label('Szerokość')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('height')
                    ->label('Wysokość')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('variant')
                    ->label('Wariant')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Kolejność')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Zaktualizowano')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
