<?php

namespace App\Filament\Resources\ContentCategories;

use App\Filament\Resources\ContentCategories\Pages\CreateContentCategory;
use App\Filament\Resources\ContentCategories\Pages\EditContentCategory;
use App\Filament\Resources\ContentCategories\Pages\ListContentCategories;
use App\Filament\Resources\ContentCategories\Pages\ViewContentCategory;
use App\Filament\Resources\ContentCategories\Schemas\ContentCategoryForm;
use App\Filament\Resources\ContentCategories\Schemas\ContentCategoryInfolist;
use App\Filament\Resources\ContentCategories\Tables\ContentCategoriesTable;
use App\Models\ContentCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentCategoryResource extends Resource
{
    protected static ?string $model = ContentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'kategoria newsroomu';

    protected static ?string $pluralModelLabel = 'kategorie newsroomu';

    protected static ?string $navigationLabel = 'Kategorie newsroomu';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 33;

    public static function form(Schema $schema): Schema
    {
        return ContentCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'articles',
                'articles as published_articles_count' => fn (Builder $query): Builder => $query->activelyDistributed(),
            ]);
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
            'index' => ListContentCategories::route('/'),
            'create' => CreateContentCategory::route('/create'),
            'view' => ViewContentCategory::route('/{record}'),
            'edit' => EditContentCategory::route('/{record}/edit'),
        ];
    }
}
