<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomArticleProvenanceMediaAdapter;
use App\Support\NewsroomArticleRelationsEditorAdapter;
use App\Support\NewsroomBodyEditorAdapter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

        try {
            $data = NewsroomArticleProvenanceMediaAdapter::normalizeArticleData($data);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'data.origin_type' => $exception->getMessage(),
            ]);
        }

        $relationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData($data);
        $data = $relationPayload['article_data'];
        $relations = $relationPayload['relations'];

        $actor = auth()->user();
        $actor = $actor instanceof User ? $actor : null;

        return DB::transaction(function () use ($data, $relations, $actor): Model {
            $article = app(ContentArticleSlugService::class)->create($data, $actor);

            return NewsroomArticleRelationsEditorAdapter::sync($article, $relations);
        });
    }
}
