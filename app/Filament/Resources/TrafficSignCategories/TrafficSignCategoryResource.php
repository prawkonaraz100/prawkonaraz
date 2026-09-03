<?php

namespace App\Filament\Resources\TrafficSignCategories;

use App\Filament\Resources\TrafficSignCategories\Pages\CreateTrafficSignCategory;
use App\Filament\Resources\TrafficSignCategories\Pages\EditTrafficSignCategory;
use App\Filament\Resources\TrafficSignCategories\Pages\ListTrafficSignCategories;
use App\Filament\Resources\TrafficSignCategories\Pages\ViewTrafficSignCategory;
use App\Filament\Resources\TrafficSignCategories\Schemas\TrafficSignCategoryForm;
use App\Filament\Resources\TrafficSignCategories\Schemas\TrafficSignCategoryInfolist;
use App\Filament\Resources\TrafficSignCategories\Tables\TrafficSignCategoriesTable;
use App\Models\TrafficSignCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrafficSignCategoryResource extends Resource
{
    protected static ?string $model = TrafficSignCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'kategoria znaku';

    protected static ?string $pluralModelLabel = 'kategorie znaków';

    protected static ?string $navigationLabel = 'Kategorie znaków';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return TrafficSignCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrafficSignCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrafficSignCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'trafficSigns',
                'trafficSigns as published_traffic_signs_count' => fn (Builder $query): Builder => $query->published(),
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
            'index' => ListTrafficSignCategories::route('/'),
            'create' => CreateTrafficSignCategory::route('/create'),
            'view' => ViewTrafficSignCategory::route('/{record}'),
            'edit' => EditTrafficSignCategory::route('/{record}/edit'),
        ];
    }
}
