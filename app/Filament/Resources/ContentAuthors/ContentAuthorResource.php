<?php

namespace App\Filament\Resources\ContentAuthors;

use App\Filament\Resources\ContentAuthors\Pages\CreateContentAuthor;
use App\Filament\Resources\ContentAuthors\Pages\EditContentAuthor;
use App\Filament\Resources\ContentAuthors\Pages\ListContentAuthors;
use App\Filament\Resources\ContentAuthors\Pages\ViewContentAuthor;
use App\Filament\Resources\ContentAuthors\Schemas\ContentAuthorForm;
use App\Filament\Resources\ContentAuthors\Schemas\ContentAuthorInfolist;
use App\Filament\Resources\ContentAuthors\Tables\ContentAuthorsTable;
use App\Models\ContentAuthor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentAuthorResource extends Resource
{
    protected static ?string $model = ContentAuthor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'autor';

    protected static ?string $pluralModelLabel = 'autorzy';

    protected static ?string $navigationLabel = 'Autorzy';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return ContentAuthorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentAuthorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentAuthorsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'trafficSigns',
                'trafficSigns as published_traffic_signs_count' => fn (Builder $query): Builder => $query->published(),
            ])
            ->withMax('trafficSigns', 'updated_at');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentAuthors::route('/'),
            'create' => CreateContentAuthor::route('/create'),
            'view' => ViewContentAuthor::route('/{record}'),
            'edit' => EditContentAuthor::route('/{record}/edit'),
        ];
    }
}
