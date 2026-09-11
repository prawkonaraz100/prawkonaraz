<?php

namespace App\Filament\Resources\HomepageVideos\Pages;

use App\Filament\Resources\HomepageVideos\HomepageVideoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomepageVideo extends CreateRecord
{
    protected static string $resource = HomepageVideoResource::class;
}
