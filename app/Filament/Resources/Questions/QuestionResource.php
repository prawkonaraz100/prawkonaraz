<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Filament\Resources\Questions\Pages\ManageQuestionMedia;
use App\Filament\Resources\Questions\Pages\ViewQuestion;
use App\Filament\Resources\Questions\RelationManagers\MediaRelationManager;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Resources\Questions\Schemas\QuestionInfolist;
use App\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Models\Question;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'pytanie';

    protected static ?string $pluralModelLabel = 'pytania';

    protected static ?string $navigationLabel = 'Baza pytań';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'licenseCategory:id,code,name',
                'questionTopic:id,name',
                'referenceExplanationAsset:id,question_id,title,is_active',
            ])
            ->withCount([
                'media',
                'explanationAnnotations',
                'referenceExplanationAsset',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'view' => ViewQuestion::route('/{record}'),
            'edit' => EditQuestion::route('/{record}/edit'),
            'media' => ManageQuestionMedia::route('/{record}/media'),
        ];
    }
}
