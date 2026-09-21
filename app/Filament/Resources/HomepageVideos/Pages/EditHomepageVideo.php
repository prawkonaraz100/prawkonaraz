<?php

namespace App\Filament\Resources\HomepageVideos\Pages;

use App\Filament\Resources\HomepageVideos\HomepageVideoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomepageVideo extends EditRecord
{
    protected static string $resource = HomepageVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
