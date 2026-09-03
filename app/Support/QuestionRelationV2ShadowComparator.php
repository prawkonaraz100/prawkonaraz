<?php

namespace App\Support;

use Illuminate\Support\Collection;

class QuestionRelationV2ShadowComparator
{
    /**
     * @param  array{groups: Collection<int, array{items: Collection<int, array<string, mixed>>}>}  $v1
     * @param  array{groups: Collection<int, array{items: Collection<int, array<string, mixed>>}>}  $v2
     * @return array<string, mixed>
     */
    public function compare(array $v1, array $v2): array
    {
        $v1Ids = $this->externalIds($v1['groups'] ?? collect());
        $v2Ids = $this->externalIds($v2['groups'] ?? collect());
        $v1Set = $v1Ids->flip();
        $v2Set = $v2Ids->flip();
        $shared = $v1Ids->filter(fn (string $id): bool => $v2Set->has($id))->values();
        $sameRank = $v1Ids
            ->values()
            ->filter(fn (string $id, int $index): bool => $id === $v2Ids->get($index))
            ->count();

        return [
            'v1_total' => $v1Ids->count(),
            'v2_total' => $v2Ids->count(),
            'shared_total' => $shared->count(),
            'v1_only_total' => $v1Ids->filter(fn (string $id): bool => ! $v2Set->has($id))->count(),
            'v2_only_total' => $v2Ids->filter(fn (string $id): bool => ! $v1Set->has($id))->count(),
            'same_rank_total' => $sameRank,
            'v1_overlap_ratio' => $this->ratio($shared->count(), $v1Ids->count()),
            'v2_overlap_ratio' => $this->ratio($shared->count(), $v2Ids->count()),
            'v1_external_ids' => $v1Ids->all(),
            'v2_external_ids' => $v2Ids->all(),
        ];
    }

    /**
     * @param  Collection<int, array{items: Collection<int, array<string, mixed>>}>  $groups
     * @return Collection<int, string>
     */
    protected function externalIds(Collection $groups): Collection
    {
        return $groups
            ->flatMap(fn (array $group): array => $group['items']->all())
            ->map(fn (array $item): string => trim((string) ($item['external_id'] ?? '')))
            ->filter()
            ->unique()
            ->values();
    }

    protected function ratio(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round($numerator / $denominator, 6);
    }
}
