<?php

namespace App\Filament\Resources\HomepageVideos;

use App\Filament\Resources\HomepageVideos\Pages\CreateHomepageVideo;
use App\Filament\Resources\HomepageVideos\Pages\EditHomepageVideo;
use App\Filament\Resources\HomepageVideos\Pages\ListHomepageVideos;
use App\Filament\Resources\HomepageVideos\Schemas\HomepageVideoForm;
use App\Filament\Resources\HomepageVideos\Tables\HomepageVideosTable;
use App\Models\HomepageVideo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageVideoResource extends Resource
{
    protected static ?string $model = HomepageVideo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'materiał';

    protected static ?string $pluralModelLabel = 'materiały';

    protected static ?string $navigationLabel = 'Video i podcasty';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return HomepageVideoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageVideosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageVideos::route('/'),
            'create' => CreateHomepageVideo::route('/create'),
            'edit' => EditHomepageVideo::route('/{record}/edit'),
        ];
    }
}
