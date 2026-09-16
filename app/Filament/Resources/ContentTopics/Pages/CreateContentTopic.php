<?php

namespace App\Filament\Resources\ContentTopics\Pages;

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use App\Models\ContentTopic;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateContentTopic extends CreateRecord
{
    protected static string $resource = ContentTopicResource::class;

    /** @var list<int> */
    private array $articleIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->articleIds = $this->normalizeArticleIds($data['article_ids'] ?? []);
        unset($data['article_ids']);

        $this->assertFeaturedMembership($data['featured_article_id'] ?? null, $this->articleIds);

        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['status'] = ContentTopic::STATUS_DRAFT;
        $data['published_at'] = null;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): ContentTopic {
            $topic = ContentTopic::query()->create($data);
            $topic->articles()->sync($this->articleIds);

            return $topic->refresh();
        });
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
