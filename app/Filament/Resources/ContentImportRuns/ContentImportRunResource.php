<?php

namespace App\Filament\Resources\ContentImportRuns;

use App\Filament\Resources\ContentImportRuns\Pages\ListContentImportRuns;
use App\Filament\Resources\ContentImportRuns\Pages\ViewContentImportRun;
use App\Filament\Resources\ContentImportRuns\Schemas\ContentImportRunInfolist;
use App\Filament\Resources\ContentImportRuns\Tables\ContentImportRunsTable;
use App\Models\ContentImportRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ContentImportRunResource extends Resource
{
    protected static ?string $model = ContentImportRun::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $modelLabel = 'run importu';

    protected static ?string $pluralModelLabel = 'runy importu';

    protected static ?string $navigationLabel = 'Importy';

    protected static string|UnitEnum|null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 10;

    public static function infolist(Schema $schema): Schema
    {
        return ContentImportRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentImportRunsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentImportRuns::route('/'),
            'view' => ViewContentImportRun::route('/{record}'),
        ];
    }
}
