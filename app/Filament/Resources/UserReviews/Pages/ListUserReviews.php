<?php

namespace App\Filament\Resources\UserReviews\Pages;

use App\Filament\Resources\UserReviews\UserReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListUserReviews extends ListRecords
{
    protected static string $resource = UserReviewResource::class;
}
