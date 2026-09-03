<?php

namespace App\Support;

use App\Models\TrafficSign;
use App\Models\TrafficSignConfusionPair;
use Illuminate\Support\Collection;

class TrafficSignConfusionPairService
{
    public function __construct(
        protected TrafficSignLearningCorpusService $corpusService,
        protected TrafficSignSupportingPageCatalog $supportingPageCatalog,
    ) {}

    /**
     * @param  list<string>|null  $categorySlugs
     * @param  Collection<int, TrafficSign>|null  $trainableSigns
     * @return Collection<int, TrafficSign>
     */
    public function confusingSignsFor(
        TrafficSign $sign,
        ?array $categorySlugs = null,
        int $limit = 3,
        ?Collection $trainableSigns = null,
    ): Collection {
        $trainableSigns ??= $this->corpusService->trainableSigns($categorySlugs);
        $signsById = $trainableSigns->keyBy('id');
        $signsBySlug = $trainableSigns->keyBy('slug');

        $databaseCandidates = TrafficSignConfusionPair::query()
            ->where('traffic_sign_id', $sign->getKey())
            ->orderByDesc('strength')
            ->orderBy('id')
            ->pluck('confusing_traffic_sign_id')
            ->map(fn (int $trafficSignId): ?TrafficSign => $signsById->get($trafficSignId))
            ->filter();

        if ($databaseCandidates->count() >= $limit) {
            return $databaseCandidates
                ->reject(fn (TrafficSign $candidate): bool => $candidate->is($sign))
                ->unique('id')
                ->take($limit)
                ->values();
        }

        $supportingPageCandidates = collect($this->supportingPageSlugsFor($sign))
            ->map(fn (string $slug): ?TrafficSign => $signsBySlug->get($slug))
            ->filter();

        return $databaseCandidates
            ->concat($supportingPageCandidates)
            ->reject(fn (TrafficSign $candidate): bool => $candidate->is($sign))
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<string>|null  $categorySlugs
     * @param  Collection<int, TrafficSign>|null  $trainableSigns
     * @return Collection<int, TrafficSign>
     */
    public function signsWithConfusions(?array $categorySlugs = null, int $limit = 12, ?Collection $trainableSigns = null): Collection
    {
        $trainableSigns ??= $this->corpusService->trainableSigns($categorySlugs);

        if ($trainableSigns->isEmpty()) {
            return collect();
        }

        $signsById = $trainableSigns->keyBy('id');
        $signsBySlug = $trainableSigns->keyBy('slug');
        $orderedSlugs = collect();
        $selected = TrafficSignConfusionPair::query()
            ->whereIn('traffic_sign_id', $trainableSigns->pluck('id'))
            ->orderByDesc('strength')
            ->orderBy('id')
            ->pluck('traffic_sign_id')
            ->unique()
            ->map(fn (int $trafficSignId): ?TrafficSign => $signsById->get($trafficSignId))
            ->filter()
            ->take($limit)
            ->values();

        if ($selected->count() >= $limit) {
            return $selected;
        }

        foreach ($this->supportingPageCatalog->all() as $page) {
            $pageSlugs = collect($page['related_sign_slugs'] ?? [])
                ->filter(fn (string $slug): bool => $signsBySlug->has($slug))
                ->values();

            if ($pageSlugs->count() < 2) {
                continue;
            }

            $orderedSlugs = $orderedSlugs->concat($pageSlugs);
        }

        $fallbackSigns = $orderedSlugs
            ->unique()
            ->map(fn (string $slug): ?TrafficSign => $signsBySlug->get($slug))
            ->filter();

        return $selected
            ->concat($fallbackSigns)
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<string>|null  $categorySlugs
     */
    public function upsertFromSupportingPages(?array $categorySlugs = null): int
    {
        $trainableSigns = $this->corpusService->trainableSigns($categorySlugs);
        $signsBySlug = $trainableSigns->keyBy('slug');
        $upserted = 0;

        foreach ($this->supportingPageCatalog->all() as $page) {
            $pageSlugs = collect($page['related_sign_slugs'] ?? [])
                ->filter(fn (string $slug): bool => $signsBySlug->has($slug))
                ->values();

            if ($pageSlugs->count() < 2) {
                continue;
            }

            $sourceSlug = (string) ($page['slug'] ?? '');

            foreach ($pageSlugs as $sourceSignSlug) {
                /** @var TrafficSign $sourceSign */
                $sourceSign = $signsBySlug->get($sourceSignSlug);

                foreach ($pageSlugs as $confusingSignSlug) {
                    if ($sourceSignSlug === $confusingSignSlug) {
                        continue;
                    }

                    /** @var TrafficSign $confusingSign */
                    $confusingSign = $signsBySlug->get($confusingSignSlug);

                    TrafficSignConfusionPair::query()->updateOrCreate(
                        [
                            'traffic_sign_id' => $sourceSign->getKey(),
                            'confusing_traffic_sign_id' => $confusingSign->getKey(),
                            'source' => TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE,
                            'source_slug' => $sourceSlug,
                        ],
                        [
                            'strength' => 90,
                        ],
                    );

                    $upserted++;
                }
            }
        }

        return $upserted;
    }

    /**
     * @return list<string>
     */
    protected function supportingPageSlugsFor(TrafficSign $sign): array
    {
        return collect($this->supportingPageCatalog->all())
            ->filter(fn (array $page): bool => in_array($sign->slug, $page['related_sign_slugs'] ?? [], true))
            ->flatMap(fn (array $page): array => $page['related_sign_slugs'] ?? [])
            ->reject(fn (string $slug): bool => $slug === $sign->slug)
            ->unique()
            ->values()
            ->all();
    }
}
