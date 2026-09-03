<?php

namespace App\Support;

use App\Models\TrafficSign;
use App\Models\User;
use App\Models\UserTrafficSignProgress;
use Illuminate\Support\Collection;

class TrafficSignLearningPlannerService
{
    public const SESSION_LIMIT = 12;

    public function __construct(
        protected TrafficSignLearningCorpusService $corpusService,
    ) {}

    /**
     * @param  list<string>|null  $categorySlugs
     * @return Collection<int, TrafficSign>
     */
    public function plan(User $user, ?array $categorySlugs = null, int $limit = self::SESSION_LIMIT): Collection
    {
        $signs = $this->corpusService->trainableSigns($categorySlugs);
        $progressBySignId = $this->corpusService->progressForSigns($user, $signs);

        return $this->planFromSnapshot($signs, $progressBySignId, $limit);
    }

    /**
     * @param  Collection<int, TrafficSign>  $signs
     * @param  Collection<int, UserTrafficSignProgress>  $progressBySignId
     * @return Collection<int, TrafficSign>
     */
    public function planFromSnapshot(Collection $signs, Collection $progressBySignId, int $limit = self::SESSION_LIMIT): Collection
    {
        if ($signs->isEmpty()) {
            return collect();
        }

        $now = now();
        $needsReview = $signs->filter(function (TrafficSign $sign) use ($progressBySignId, $now): bool {
            $progress = $progressBySignId->get($sign->getKey());

            return $progress?->state === UserTrafficSignProgress::STATE_NEEDS_REVIEW
                && ($progress->next_review_at === null || $progress->next_review_at->lte($now));
        });
        $learning = $signs->filter(function (TrafficSign $sign) use ($progressBySignId, $now): bool {
            $progress = $progressBySignId->get($sign->getKey());

            return $progress?->state === UserTrafficSignProgress::STATE_LEARNING
                && ($progress->next_review_at === null || $progress->next_review_at->lte($now));
        });
        $new = $signs->filter(fn (TrafficSign $sign): bool => ! $progressBySignId->has($sign->getKey()));
        $mastered = $signs->filter(fn (TrafficSign $sign): bool => $progressBySignId
            ->get($sign->getKey())?->state === UserTrafficSignProgress::STATE_MASTERED);
        $learningNotDue = $signs->filter(function (TrafficSign $sign) use ($progressBySignId, $now): bool {
            $progress = $progressBySignId->get($sign->getKey());

            return $progress?->state === UserTrafficSignProgress::STATE_LEARNING
                && $progress->next_review_at !== null
                && $progress->next_review_at->gt($now);
        });

        $selected = collect();
        $selected = $this->appendUnique($selected, $needsReview, min(5, $limit));
        $selected = $this->appendUnique($selected, $learning, min(5, $limit - $selected->count()));
        $selected = $this->appendUnique($selected, $new, min(5, $limit - $selected->count()));
        $selected = $this->appendUnique($selected, $mastered, min(2, $limit - $selected->count()));
        $selected = $this->appendUnique($selected, $learningNotDue, min(2, $limit - $selected->count()));

        if ($selected->count() < $limit) {
            $selected = $this->appendUnique($selected, $signs, $limit - $selected->count());
        }

        return $selected->take($limit)->values();
    }

    /**
     * @param  Collection<int, TrafficSign>  $selected
     * @param  Collection<int, TrafficSign>  $candidates
     * @return Collection<int, TrafficSign>
     */
    protected function appendUnique(Collection $selected, Collection $candidates, int $take): Collection
    {
        if ($take <= 0) {
            return $selected;
        }

        $selectedIds = $selected->pluck('id')->all();
        $items = $candidates
            ->reject(fn (TrafficSign $sign): bool => in_array($sign->getKey(), $selectedIds, true))
            ->take($take);

        return $selected->concat($items)->values();
    }
}
