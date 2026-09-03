<?php

namespace App\Filament\Resources\QuestionTopics;

use App\Filament\Resources\QuestionTopics\Pages\EditQuestionTopic;
use App\Filament\Resources\QuestionTopics\Pages\ListQuestionTopics;
use App\Filament\Resources\QuestionTopics\Pages\ViewQuestionTopic;
use App\Filament\Resources\QuestionTopics\Schemas\QuestionTopicForm;
use App\Filament\Resources\QuestionTopics\Schemas\QuestionTopicInfolist;
use App\Filament\Resources\QuestionTopics\Tables\QuestionTopicsTable;
use App\Models\QuestionTopic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionTopicResource extends Resource
{
    protected static ?string $model = QuestionTopic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'dział pytań';

    protected static ?string $pluralModelLabel = 'działy pytań';

    protected static ?string $navigationLabel = 'Działy pytań';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return QuestionTopicForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionTopicInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionTopicsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'questions',
                'categoryHeroes',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionTopics::route('/'),
            'view' => ViewQuestionTopic::route('/{record}'),
            'edit' => EditQuestionTopic::route('/{record}/edit'),
        ];
    }
}
