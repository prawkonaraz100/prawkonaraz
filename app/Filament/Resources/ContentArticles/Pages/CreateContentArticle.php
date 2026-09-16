<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateContentArticle extends CreateRecord
{
    protected static string $resource = ContentArticleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        return app(ContentArticleSlugService::class)->create(
            $data,
            $actor instanceof User ? $actor : null,
        );
    }
}
