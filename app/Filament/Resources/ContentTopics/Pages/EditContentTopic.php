<?php

namespace App\Filament\Resources\ContentTopics\Pages;

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use App\Filament\Resources\ContentTopics\Pages\Concerns\InteractsWithContentTopicWorkflowActions;
use App\Models\ContentTopic;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditContentTopic extends EditRecord
{
    use InteractsWithContentTopicWorkflowActions;

    protected static string $resource = ContentTopicResource::class;

    /** @var list<int> */
    private array $articleIds = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record instanceof ContentTopic) {
            $data['article_ids'] = $this->record->articles()
                ->pluck('content_articles.id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->articleIds = $this->normalizeArticleIds($data['article_ids'] ?? []);
        unset($data['article_ids']);

        $this->assertFeaturedMembership($data['featured_article_id'] ?? null, $this->articleIds);
        $data['description'] = trim((string) ($data['description'] ?? ''));

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): ContentTopic {
            if (! $record instanceof ContentTopic) {
                throw new \RuntimeException('Content topic edit requires a ContentTopic record.');
            }

            $record->fill($data);
            $record->save();
            $record->articles()->sync($this->articleIds);

            return $record->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->contentTopicWorkflowActions(),
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (ContentTopic $record): bool => ! $record->canBeDeleted()),
        ];
    }

    protected function refreshContentTopicEditStateAfterWorkflowAction(): void
    {
        if ($this->record instanceof ContentTopic) {
            $this->record = $this->record->refresh();
            $this->fillForm();
            $this->rememberData();
        }
    }

    /**
     * @return list<int>
     */
    private function normalizeArticleIds(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $articleIds
     */
    private function assertFeaturedMembership(mixed $featuredArticleId, array $articleIds): void
    {
        if ($featuredArticleId === null || $featuredArticleId === '') {
            return;
        }

        if (! in_array((int) $featuredArticleId, $articleIds, true)) {
            throw ValidationException::withMessages([
                'data.featured_article_id' => 'Featured article musi należeć do corpus topicu.',
            ]);
        }
    }
}
