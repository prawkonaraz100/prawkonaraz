<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryHero;
use Illuminate\Support\Collection;

class QuestionTopicHeroResolver
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{image_url:?string,image_path:?string,image_alt:?string,image_position:string,source:string}|null
     */
    public function heroFor(LicenseCategory $category, QuestionTopic $topic): ?array
    {
        return $this->heroesForCategory($category, collect([$topic]))
            ->get((int) $topic->getKey());
    }

    /**
     * @param  Collection<int, QuestionTopic>  $topics
     * @return Collection<int, array{image_url:?string,image_path:?string,image_alt:?string,image_position:string,source:string}>
     */
    public function heroesForCategory(LicenseCategory $category, Collection $topics): Collection
    {
        $topicsById = $topics
            ->filter(fn (QuestionTopic $topic): bool => $topic->getKey() !== null)
            ->keyBy(fn (QuestionTopic $topic): int => (int) $topic->getKey());

        if ($topicsById->isEmpty()) {
            return collect();
        }

        $categoryHeroes = QuestionTopicCategoryHero::query()
            ->where('license_category_id', $category->getKey())
            ->whereIn('question_topic_id', $topicsById->keys()->all())
            ->where('is_active', true)
            ->get([
                'question_topic_id',
                'hero_image_path',
                'hero_image_alt',
                'hero_image_position',
            ])
            ->keyBy(fn (QuestionTopicCategoryHero $hero): int => (int) $hero->question_topic_id);

        return $topicsById
            ->mapWithKeys(function (QuestionTopic $topic, int $topicId) use ($categoryHeroes): array {
                $categoryHero = $categoryHeroes->get($topicId);

                if ($categoryHero instanceof QuestionTopicCategoryHero && filled($categoryHero->hero_image_path)) {
                    return [
                        $topicId => $this->payload(
                            path: (string) $categoryHero->hero_image_path,
                            alt: $categoryHero->hero_image_alt,
                            position: $categoryHero->hero_image_position,
                            source: 'category',
                        ),
                    ];
                }

                if (filled($topic->hero_image_path)) {
                    return [
                        $topicId => $this->payload(
                            path: (string) $topic->hero_image_path,
                            alt: $topic->hero_image_alt,
                            position: $topic->hero_image_position,
                            source: 'topic',
                        ),
                    ];
                }

                return [];
            });
    }

    /**
     * @return array{image_url:?string,image_path:string,image_alt:?string,image_position:string,source:string}
     */
    protected function payload(string $path, ?string $alt, ?string $position, string $source): array
    {
        return [
            'image_url' => $this->mediaUrlResolver->resolve($path, (string) config('media.public_disk', 'public')),
            'image_path' => $path,
            'image_alt' => filled($alt) ? trim((string) $alt) : null,
            'image_position' => filled($position) ? trim((string) $position) : 'center',
            'source' => $source,
        ];
    }
}

