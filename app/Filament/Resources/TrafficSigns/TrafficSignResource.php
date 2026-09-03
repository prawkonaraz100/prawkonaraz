<?php

namespace App\Filament\Resources\TrafficSigns;

use App\Filament\Resources\TrafficSigns\Pages\CreateTrafficSign;
use App\Filament\Resources\TrafficSigns\Pages\EditTrafficSign;
use App\Filament\Resources\TrafficSigns\Pages\ListTrafficSigns;
use App\Filament\Resources\TrafficSigns\Pages\ViewTrafficSign;
use App\Filament\Resources\TrafficSigns\Schemas\TrafficSignForm;
use App\Filament\Resources\TrafficSigns\Schemas\TrafficSignInfolist;
use App\Filament\Resources\TrafficSigns\Tables\TrafficSignsTable;
use App\Models\TrafficSign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrafficSignResource extends Resource
{
    protected static ?string $model = TrafficSign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'znak drogowy';

    protected static ?string $pluralModelLabel = 'znaki drogowe';

    protected static ?string $navigationLabel = 'Znaki drogowe';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return TrafficSignForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrafficSignInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrafficSignsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'author:id,name',
                'category:id,name',
                'reviewer:id,name',
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
            'index' => ListTrafficSigns::route('/'),
            'create' => CreateTrafficSign::route('/create'),
            'view' => ViewTrafficSign::route('/{record}'),
            'edit' => EditTrafficSign::route('/{record}/edit'),
        ];
    }
}
