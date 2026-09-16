<?php

namespace App\Filament\Resources\ContentArticles;

use App\Filament\Resources\ContentArticles\Pages\CreateContentArticle;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Filament\Resources\ContentArticles\Pages\ListContentArticles;
use App\Filament\Resources\ContentArticles\Pages\ViewContentArticle;
use App\Filament\Resources\ContentArticles\Schemas\ContentArticleForm;
use App\Filament\Resources\ContentArticles\Schemas\ContentArticleInfolist;
use App\Filament\Resources\ContentArticles\Tables\ContentArticlesTable;
use App\Models\ContentArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentArticleResource extends Resource
{
    protected static ?string $model = ContentArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'artykuł';

    protected static ?string $pluralModelLabel = 'artykuły';

    protected static ?string $navigationLabel = 'Aktualności i artykuły';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 34;

    public static function form(Schema $schema): Schema
    {
        return ContentArticleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentArticleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentArticlesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'category:id,name,slug',
                'author:id,name,slug',
                'reviewer:id,name,slug',
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
            'index' => ListContentArticles::route('/'),
            'create' => CreateContentArticle::route('/create'),
            'view' => ViewContentArticle::route('/{record}'),
            'edit' => EditContentArticle::route('/{record}/edit'),
        ];
    }
}
