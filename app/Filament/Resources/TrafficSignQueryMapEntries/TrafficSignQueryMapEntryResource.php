<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries;

use App\Filament\Resources\TrafficSignQueryMapEntries\Pages\CreateTrafficSignQueryMapEntry;
use App\Filament\Resources\TrafficSignQueryMapEntries\Pages\EditTrafficSignQueryMapEntry;
use App\Filament\Resources\TrafficSignQueryMapEntries\Pages\ListTrafficSignQueryMapEntries;
use App\Filament\Resources\TrafficSignQueryMapEntries\Pages\ViewTrafficSignQueryMapEntry;
use App\Filament\Resources\TrafficSignQueryMapEntries\Schemas\TrafficSignQueryMapEntryForm;
use App\Filament\Resources\TrafficSignQueryMapEntries\Schemas\TrafficSignQueryMapEntryInfolist;
use App\Filament\Resources\TrafficSignQueryMapEntries\Tables\TrafficSignQueryMapEntriesTable;
use App\Models\TrafficSignQueryMapEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrafficSignQueryMapEntryResource extends Resource
{
    protected static ?string $model = TrafficSignQueryMapEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'primary_query';

    protected static ?string $modelLabel = 'wpis mapy zapytań';

    protected static ?string $pluralModelLabel = 'wpisy mapy zapytań';

    protected static ?string $navigationLabel = 'Mapa zapytań';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 33;

    public static function form(Schema $schema): Schema
    {
        return TrafficSignQueryMapEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrafficSignQueryMapEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrafficSignQueryMapEntriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'trafficSign:id,code,name',
                'category:id,name',
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
            'index' => ListTrafficSignQueryMapEntries::route('/'),
            'create' => CreateTrafficSignQueryMapEntry::route('/create'),
            'view' => ViewTrafficSignQueryMapEntry::route('/{record}'),
            'edit' => EditTrafficSignQueryMapEntry::route('/{record}/edit'),
        ];
    }
}
