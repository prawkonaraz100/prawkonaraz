<?php

namespace App\Filament\Resources\LicenseCategories;

use App\Filament\Resources\LicenseCategories\Pages\CreateLicenseCategory;
use App\Filament\Resources\LicenseCategories\Pages\EditLicenseCategory;
use App\Filament\Resources\LicenseCategories\Pages\ListLicenseCategories;
use App\Filament\Resources\LicenseCategories\Pages\ViewLicenseCategory;
use App\Filament\Resources\LicenseCategories\RelationManagers\QuestionTopicCategoryHeroesRelationManager;
use App\Filament\Resources\LicenseCategories\RelationManagers\QuestionTopicCategoryLabelsRelationManager;
use App\Filament\Resources\LicenseCategories\Schemas\LicenseCategoryForm;
use App\Filament\Resources\LicenseCategories\Schemas\LicenseCategoryInfolist;
use App\Filament\Resources\LicenseCategories\Tables\LicenseCategoriesTable;
use App\Models\LicenseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseCategoryResource extends Resource
{
    protected static ?string $model = LicenseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'kategoria';

    protected static ?string $pluralModelLabel = 'kategorie';

    protected static ?string $navigationLabel = 'Kategorie';

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return LicenseCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LicenseCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $windowStart = now()->subDays(max((int) config('study.admin_activity_window_days', 90), 1));

        return parent::getEloquentQuery()
            ->withCount([
                'questions',
                'questions as active_questions_count' => fn (Builder $query): Builder => $query->where('is_active', true),
                'questions as ready_questions_count' => fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->readyForDelivery(),
                'studySessions as recent_study_sessions_count' => fn (Builder $query): Builder => $query
                    ->where('created_at', '>=', $windowStart),
                'userProfiles',
            ])
            ->withMax([
                'studySessions as last_study_session_at' => fn (Builder $query): Builder => $query,
            ], 'created_at');
    }

    public static function getRelations(): array
    {
        return [
            QuestionTopicCategoryLabelsRelationManager::class,
            QuestionTopicCategoryHeroesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenseCategories::route('/'),
            'create' => CreateLicenseCategory::route('/create'),
            'view' => ViewLicenseCategory::route('/{record}'),
            'edit' => EditLicenseCategory::route('/{record}/edit'),
        ];
    }
}
