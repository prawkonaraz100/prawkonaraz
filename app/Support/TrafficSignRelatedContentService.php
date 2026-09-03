<?php

namespace App\Support;

use App\Models\TrafficSign;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class TrafficSignRelatedContentService
{
    public function __construct(
        private readonly TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
    ) {}

    public function relatedSigns(TrafficSign $sign, int $limit = 6): EloquentCollection
    {
        $candidates = TrafficSign::query()
            ->published()
            ->with([
                'author:id,name,slug',
                'category:id,name,slug',
            ])
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->where('traffic_sign_category_id', $sign->traffic_sign_category_id)
            ->whereKeyNot($sign->getKey())
            ->get();

        $supportingPrioritySlugs = $this->supportingPrioritySlugs($sign, $candidates);
        $currentCodeMeta = $this->codeMeta($sign->code);

        return $candidates
            ->sort(fn (TrafficSign $left, TrafficSign $right) => $this->compareCandidates(
                left: $left,
                right: $right,
                supportingPrioritySlugs: $supportingPrioritySlugs,
                currentCodeMeta: $currentCodeMeta,
            ))
            ->take($limit)
            ->values();
    }

    /**
     * @param  EloquentCollection<int, TrafficSign>  $candidates
     * @return list<string>
     */
    private function supportingPrioritySlugs(TrafficSign $sign, EloquentCollection $candidates): array
    {
        $allowedSlugs = array_fill_keys($candidates->pluck('slug')->all(), true);
        $prioritySlugs = [];

        foreach ($this->trafficSignSupportingPageCatalog->all() as $page) {
            if (! in_array($sign->slug, $page['related_sign_slugs'] ?? [], true)) {
                continue;
            }

            foreach ($page['related_sign_slugs'] as $relatedSlug) {
                if ($relatedSlug === $sign->slug || ! isset($allowedSlugs[$relatedSlug])) {
                    continue;
                }

                if (! in_array($relatedSlug, $prioritySlugs, true)) {
                    $prioritySlugs[] = $relatedSlug;
                }
            }
        }

        return $prioritySlugs;
    }

    /**
     * @param  array{prefix: string, number: int|null, suffix: string}  $currentCodeMeta
     * @param  list<string>  $supportingPrioritySlugs
     */
    private function compareCandidates(
        TrafficSign $left,
        TrafficSign $right,
        array $supportingPrioritySlugs,
        array $currentCodeMeta,
    ): int {
        $leftTuple = $this->rankingTuple($left, $supportingPrioritySlugs, $currentCodeMeta);
        $rightTuple = $this->rankingTuple($right, $supportingPrioritySlugs, $currentCodeMeta);

        return $this->compareTuples($leftTuple, $rightTuple);
    }

    /**
     * @param  array{prefix: string, number: int|null, suffix: string}  $currentCodeMeta
     * @param  list<string>  $supportingPrioritySlugs
     * @return array{int, int, int, int, string, int, string}
     */
    private function rankingTuple(
        TrafficSign $candidate,
        array $supportingPrioritySlugs,
        array $currentCodeMeta,
    ): array {
        $priorityIndex = array_search($candidate->slug, $supportingPrioritySlugs, true);
        $candidateCodeMeta = $this->codeMeta($candidate->code);
        $distance = $this->numericDistance($currentCodeMeta['number'], $candidateCodeMeta['number']);
        $sameDecadePenalty = $this->sameDecade($currentCodeMeta['number'], $candidateCodeMeta['number']) ? 0 : 1;

        return [
            $priorityIndex === false ? 1 : 0,
            $priorityIndex === false ? 999 : $priorityIndex,
            $sameDecadePenalty,
            $distance,
            $candidateCodeMeta['prefix'],
            $candidate->sort_order ?? 9999,
            $candidate->slug,
        ];
    }

    /**
     * @param  array<int, int|string>  $left
     * @param  array<int, int|string>  $right
     */
    private function compareTuples(array $left, array $right): int
    {
        foreach ($left as $index => $leftValue) {
            $rightValue = $right[$index];

            if ($leftValue === $rightValue) {
                continue;
            }

            return $leftValue <=> $rightValue;
        }

        return 0;
    }

    /**
     * @return array{prefix: string, number: int|null, suffix: string}
     */
    private function codeMeta(string $code): array
    {
        if (preg_match('/^(?<prefix>[A-Z]+)-(?<number>\d+)(?<suffix>.*)$/i', $code, $matches) !== 1) {
            return [
                'prefix' => strtoupper($code),
                'number' => null,
                'suffix' => '',
            ];
        }

        return [
            'prefix' => strtoupper($matches['prefix']),
            'number' => (int) $matches['number'],
            'suffix' => strtolower($matches['suffix'] ?? ''),
        ];
    }

    private function numericDistance(?int $left, ?int $right): int
    {
        if ($left === null || $right === null) {
            return 999;
        }

        return abs($left - $right);
    }

    private function sameDecade(?int $left, ?int $right): bool
    {
        if ($left === null || $right === null) {
            return false;
        }

        return intdiv($left, 10) === intdiv($right, 10);
    }
}
