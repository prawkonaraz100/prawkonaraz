<?php

namespace App\Filament\Resources\QuestionRelations;

use App\Filament\Resources\QuestionRelations\Pages\EditQuestionRelation;
use App\Filament\Resources\QuestionRelations\Pages\ListQuestionRelations;
use App\Filament\Resources\QuestionRelations\Schemas\QuestionRelationForm;
use App\Filament\Resources\QuestionRelations\Tables\QuestionRelationsTable;
use App\Models\QuestionRelation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionRelationResource extends Resource
{
    protected static ?string $model = QuestionRelation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $modelLabel = 'relacja pytań';

    protected static ?string $pluralModelLabel = 'relacje pytań';

    protected static ?string $navigationLabel = 'Relacje pytań';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return QuestionRelationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionRelationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'leftExplanation.question:id,prompt,external_id',
            'rightExplanation.question:id,prompt,external_id',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionRelations::route('/'),
            'edit' => EditQuestionRelation::route('/{record}/edit'),
        ];
    }
}
