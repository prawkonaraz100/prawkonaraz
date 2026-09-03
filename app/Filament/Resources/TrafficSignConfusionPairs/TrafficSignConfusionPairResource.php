<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs;

use App\Filament\Resources\TrafficSignConfusionPairs\Pages\CreateTrafficSignConfusionPair;
use App\Filament\Resources\TrafficSignConfusionPairs\Pages\EditTrafficSignConfusionPair;
use App\Filament\Resources\TrafficSignConfusionPairs\Pages\ListTrafficSignConfusionPairs;
use App\Filament\Resources\TrafficSignConfusionPairs\Pages\ViewTrafficSignConfusionPair;
use App\Filament\Resources\TrafficSignConfusionPairs\Schemas\TrafficSignConfusionPairForm;
use App\Filament\Resources\TrafficSignConfusionPairs\Schemas\TrafficSignConfusionPairInfolist;
use App\Filament\Resources\TrafficSignConfusionPairs\Tables\TrafficSignConfusionPairsTable;
use App\Models\TrafficSignConfusionPair;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrafficSignConfusionPairResource extends Resource
{
    protected static ?string $model = TrafficSignConfusionPair::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'source_slug';

    protected static ?string $modelLabel = 'para podobnych znaków';

    protected static ?string $pluralModelLabel = 'podobne znaki';

    protected static ?string $navigationLabel = 'Podobne znaki';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 34;

    public static function form(Schema $schema): Schema
    {
        return TrafficSignConfusionPairForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrafficSignConfusionPairInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrafficSignConfusionPairsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'trafficSign:id,code,name,slug,traffic_sign_category_id',
                'trafficSign.category:id,name',
                'confusingTrafficSign:id,code,name,slug,traffic_sign_category_id',
                'confusingTrafficSign.category:id,name',
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
            'index' => ListTrafficSignConfusionPairs::route('/'),
            'create' => CreateTrafficSignConfusionPair::route('/create'),
            'view' => ViewTrafficSignConfusionPair::route('/{record}'),
            'edit' => EditTrafficSignConfusionPair::route('/{record}/edit'),
        ];
    }
}
