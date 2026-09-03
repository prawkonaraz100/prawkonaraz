<?php

namespace App\Support;

use App\Models\TrafficSign;
use App\Models\User;
use App\Models\UserTrafficSignProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrafficSignLearningCorpusService
{
    public const MVP_CATEGORY_SLUGS = [
        'znaki-ostrzegawcze',
        'znaki-zakazu',
        'znaki-nakazu',
        'znaki-informacyjne',
    ];

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @param  list<string>|null  $categorySlugs
     * @return Builder<TrafficSign>
     */
    public function trainableQuery(?array $categorySlugs = null): Builder
    {
        $categorySlugs ??= self::MVP_CATEGORY_SLUGS;

        return TrafficSign::query()
            ->published()
            ->whereHas('author', fn (Builder $query): Builder => $query->published())
            ->whereHas('category', fn (Builder $query): Builder => $query
                ->published()
                ->whereIn('slug', $categorySlugs))
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->where(function (Builder $query): void {
                $query
                    ->whereNotNull('intro_definition')
                    ->orWhereNotNull('meaning')
                    ->orWhereNotNull('driver_behavior');
            })
            ->with('category:id,name,slug,sort_order')
            ->orderBy('traffic_sign_category_id')
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    /**
     * @param  list<string>|null  $categorySlugs
     * @return Collection<int, TrafficSign>
     */
    public function trainableSigns(?array $categorySlugs = null): Collection
    {
        return $this->trainableQuery($categorySlugs)->get();
    }

    /**
     * @param  Collection<int, TrafficSign>  $signs
     * @return Collection<int, UserTrafficSignProgress>
     */
    public function progressForSigns(User $user, Collection $signs): Collection
    {
        if ($signs->isEmpty()) {
            return collect();
        }

        return UserTrafficSignProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('traffic_sign_id', $signs->pluck('id'))
            ->get()
            ->keyBy('traffic_sign_id');
    }

    /**
     * @param  list<string>|null  $categorySlugs
     * @return array{signs: Collection<int, TrafficSign>, progress_by_sign_id: Collection<int, UserTrafficSignProgress>, overview: array<string, mixed>, categories: list<array<string, mixed>>}
     */
    public function dashboard(User $user, ?array $categorySlugs = null): array
    {
        $signs = $this->trainableSigns($categorySlugs);
        $progressBySignId = $this->progressForSigns($user, $signs);

        return [
            'signs' => $signs,
            'progress_by_sign_id' => $progressBySignId,
            'overview' => $this->overviewForSigns($signs, $progressBySignId, $categorySlugs),
            'categories' => $this->categorySummariesForSigns($signs, $progressBySignId),
        ];
    }

    /**
     * @param  list<string>|null  $categorySlugs
     * @return array<string, mixed>
     */
    public function overview(User $user, ?array $categorySlugs = null): array
    {
        return $this->dashboard($user, $categorySlugs)['overview'];
    }

    /**
     * @param  Collection<int, TrafficSign>  $signs
     * @param  Collection<int, UserTrafficSignProgress>  $progressBySignId
     * @param  list<string>|null  $categorySlugs
     * @return array<string, mixed>
     */
    public function overviewForSigns(Collection $signs, Collection $progressBySignId, ?array $categorySlugs = null): array
    {
        $masteredCount = $progressBySignId
            ->where('state', UserTrafficSignProgress::STATE_MASTERED)
            ->count();
        $needsReviewCount = $progressBySignId
            ->where('state', UserTrafficSignProgress::STATE_NEEDS_REVIEW)
            ->count();
        $learningCount = $progressBySignId
            ->where('state', UserTrafficSignProgress::STATE_LEARNING)
            ->count();
        $newCount = max($signs->count() - $progressBySignId->count(), 0);

        return [
            'trainable_count' => $signs->count(),
            'mastered_count' => $masteredCount,
            'learning_count' => $learningCount,
            'needs_review_count' => $needsReviewCount,
            'new_count' => $newCount,
            'progress_percent' => $signs->isEmpty()
                ? 0
                : round(($masteredCount / $signs->count()) * 100, 1),
            'mvp_category_slugs' => array_values($categorySlugs ?? self::MVP_CATEGORY_SLUGS),
        ];
    }

    /**
     * @param  list<string>|null  $categorySlugs
     * @return list<array<string, mixed>>
     */
    public function categorySummaries(User $user, ?array $categorySlugs = null): array
    {
        return $this->dashboard($user, $categorySlugs)['categories'];
    }

    /**
     * @param  Collection<int, TrafficSign>  $signs
     * @param  Collection<int, UserTrafficSignProgress>  $progressBySignId
     * @return list<array<string, mixed>>
     */
    public function categorySummariesForSigns(Collection $signs, Collection $progressBySignId): array
    {
        return $signs
            ->groupBy(fn (TrafficSign $sign): string => (string) $sign->category?->slug)
            ->map(function (Collection $categorySigns): array {
                /** @var TrafficSign $firstSign */
                $firstSign = $categorySigns->first();

                return [
                    'slug' => (string) $firstSign->category?->slug,
                    'name' => (string) $firstSign->category?->name,
                    'sort_order' => (int) $firstSign->category?->sort_order,
                    'total_signs' => $categorySigns->count(),
                    'signs' => $categorySigns,
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $category) use ($progressBySignId): array {
                /** @var Collection<int, TrafficSign> $signs */
                $signs = $category['signs'];
                $masteredCount = $signs
                    ->filter(fn (TrafficSign $sign): bool => $progressBySignId
                        ->get($sign->getKey())?->state === UserTrafficSignProgress::STATE_MASTERED)
                    ->count();
                $learningCount = $signs
                    ->filter(fn (TrafficSign $sign): bool => $progressBySignId
                        ->get($sign->getKey())?->state === UserTrafficSignProgress::STATE_LEARNING)
                    ->count();
                $needsReviewCount = $signs
                    ->filter(fn (TrafficSign $sign): bool => $progressBySignId
                        ->get($sign->getKey())?->state === UserTrafficSignProgress::STATE_NEEDS_REVIEW)
                    ->count();
                $progressCount = $signs
                    ->filter(fn (TrafficSign $sign): bool => $progressBySignId->has($sign->getKey()))
                    ->count();
                $newCount = max($category['total_signs'] - $progressCount, 0);

                return [
                    'slug' => $category['slug'],
                    'name' => $category['name'],
                    'total_signs' => $category['total_signs'],
                    'mastered_signs' => $masteredCount,
                    'learning_signs' => $learningCount,
                    'needs_review_signs' => $needsReviewCount,
                    'new_signs' => $newCount,
                    'progress_percent' => $category['total_signs'] > 0
                        ? round(($masteredCount / $category['total_signs']) * 100, 1)
                        : 0,
                    'sample_signs' => $signs
                        ->take(3)
                        ->map(fn (TrafficSign $sign): array => [
                            'id' => $sign->getKey(),
                            'code' => $sign->code,
                            'name' => $sign->name,
                            'image_url' => $this->mediaUrlResolver->resolve($sign->image_path, 'public'),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function signPayload(TrafficSign $sign): array
    {
        return [
            'id' => $sign->getKey(),
            'code' => $sign->code,
            'name' => $sign->name,
            'category' => [
                'slug' => $sign->category?->slug,
                'name' => $sign->category?->name,
            ],
            'image_url' => $this->mediaUrlResolver->resolve($sign->image_path, 'public'),
            'public_url' => route('traffic-signs.show', $sign->slug, absolute: false),
        ];
    }
}
