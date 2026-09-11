<?php

namespace App\Filament\Resources\UserReviews;

use App\Filament\Resources\UserReviews\Pages\ListUserReviews;
use App\Filament\Resources\UserReviews\Pages\EditUserReview;
use App\Filament\Resources\UserReviews\Schemas\UserReviewForm;
use App\Filament\Resources\UserReviews\Tables\UserReviewsTable;
use App\Models\UserReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserReviewResource extends Resource
{
    protected static ?string $model = UserReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $recordTitleAttribute = 'content';

    protected static ?string $modelLabel = 'opinia';

    protected static ?string $pluralModelLabel = 'opinie';

    protected static ?string $navigationLabel = 'Opinie użytkowników';

    protected static string|\UnitEnum|null $navigationGroup = 'Społeczność';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return UserReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserReviews::route('/'),
            'edit' => EditUserReview::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = UserReview::query()
            ->where('status', UserReview::STATUS_PENDING)
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
