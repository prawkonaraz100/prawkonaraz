<?php

namespace App\Support;

class QuestionLearningOrderResult
{
    /**
     * @param  array<int, int>  $questionIds
     */
    public function __construct(
        public readonly array $questionIds,
        public readonly string $source,
        public readonly ?int $orderSetId = null,
        public readonly ?int $orderSetVersion = null,
        public readonly int $manualCount = 0,
        public readonly int $fallbackCount = 0,
        public readonly int $staleCount = 0,
        public readonly int $missingCount = 0,
        public readonly int $filteredOutCount = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'source' => $this->source,
            'order_set_id' => $this->orderSetId,
            'order_set_version' => $this->orderSetVersion,
            'manual_count' => $this->manualCount,
            'fallback_count' => $this->fallbackCount,
            'stale_count' => $this->staleCount,
            'missing_count' => $this->missingCount,
            'filtered_out_count' => $this->filteredOutCount,
        ];
    }
}
