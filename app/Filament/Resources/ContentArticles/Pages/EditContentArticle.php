<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Enums\ContentArticleType;
use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\Concerns\InteractsWithContentArticleWorkflowActions;
use App\Models\ContentArticle;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomArticleRelationsEditorAdapter;
use App\Support\NewsroomBodyEditorAdapter;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class EditContentArticle extends EditRecord
{
    use InteractsWithContentArticleWorkflowActions;

    protected static string $resource = ContentArticleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = NewsroomBodyEditorAdapter::hydrateArticleData($data);

        if ($this->record instanceof ContentArticle) {
            $data = NewsroomArticleRelationsEditorAdapter::hydrateArticleData($data, $this->record);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof ContentArticle) {
            return parent::handleRecordUpdate($record, $data);
        }

        if ($record->isPubliclyVisible()) {
            $record->forceFill([
                'editorial_note' => $data['editorial_note'] ?? $record->editorial_note,
            ])->save();

            return $record->refresh();
        }

        try {
            $data = NewsroomBodyEditorAdapter::normalizeArticleData(
                $data,
                (int) ($record->body_schema_version ?? 1),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'data.body_blocks' => $exception->getMessage(),
            ]);
        }

        $relationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData($data);
        $data = $relationPayload['article_data'];
        $relations = $relationPayload['relations'];

        $actor = auth()->user();
        $actor = $actor instanceof User ? $actor : null;

        return DB::transaction(function () use ($record, $data, $relations, $actor): ContentArticle {
            $requestedType = $data['type'] ?? $record->type;
            $requestedType = $requestedType instanceof ContentArticleType
                ? $requestedType->value
                : (string) $requestedType;

            $requestedSlug = trim((string) ($data['slug'] ?? $record->slug));

            unset($data['type'], $data['slug']);

            $record->fill($data);
            $record->save();

            $service = app(ContentArticleSlugService::class);
            $currentType = $record->type instanceof ContentArticleType
                ? $record->type->value
                : (string) $record->type;

            if ($requestedType !== $currentType) {
                $record = $service->changeType($record, $requestedType, $actor);
            }

            if ($requestedSlug !== '' && $requestedSlug !== (string) $record->slug) {
                $record = $service->changeSlug($record, $requestedSlug, $actor);
            }

            return NewsroomArticleRelationsEditorAdapter::sync($record, $relations);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ...$this->contentArticleWorkflowActions(),
        ];
    }
}
