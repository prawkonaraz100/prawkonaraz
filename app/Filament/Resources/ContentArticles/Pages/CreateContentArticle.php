<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomBodyEditorAdapter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateContentArticle extends CreateRecord
{
    protected static string $resource = ContentArticleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $data = NewsroomBodyEditorAdapter::normalizeArticleData($data);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'data.body_blocks' => $exception->getMessage(),
            ]);
        }

        $actor = auth()->user();

        return app(ContentArticleSlugService::class)->create(
            $data,
            $actor instanceof User ? $actor : null,
        );
    }
}
