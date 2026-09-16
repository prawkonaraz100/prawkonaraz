<?php

namespace App\Filament\Resources\ContentTopics;

use App\Filament\Resources\ContentTopics\Pages\CreateContentTopic;
use App\Filament\Resources\ContentTopics\Pages\EditContentTopic;
use App\Filament\Resources\ContentTopics\Pages\ListContentTopics;
use App\Filament\Resources\ContentTopics\Pages\ViewContentTopic;
use App\Filament\Resources\ContentTopics\Schemas\ContentTopicForm;
use App\Filament\Resources\ContentTopics\Schemas\ContentTopicInfolist;
use App\Filament\Resources\ContentTopics\Tables\ContentTopicsTable;
use App\Models\ContentTopic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentTopicResource extends Resource
{
    protected static ?string $model = ContentTopic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'temat newsroomu';

    protected static ?string $pluralModelLabel = 'tematy newsroomu';

    protected static ?string $navigationLabel = 'Tematy newsroomu';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 34;

    public static function form(Schema $schema): Schema
    {
        return ContentTopicForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentTopicInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentTopicsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('featuredArticle')
            ->withCount([
                'articles',
                'articles as eligible_articles_count' => fn (Builder $query): Builder => $query
                    ->activelyDistributed()
                    ->indexable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentTopics::route('/'),
            'create' => CreateContentTopic::route('/create'),
            'view' => ViewContentTopic::route('/{record}'),
            'edit' => EditContentTopic::route('/{record}/edit'),
        ];
    }
}
