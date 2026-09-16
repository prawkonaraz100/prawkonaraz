<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Enums\ContentArticleType;
use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Models\ContentArticle;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditContentArticle extends EditRecord
{
    protected static string $resource = ContentArticleResource::class;

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

        $actor = auth()->user();
        $actor = $actor instanceof User ? $actor : null;

        return DB::transaction(function () use ($record, $data, $actor): ContentArticle {
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

            return $record->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
