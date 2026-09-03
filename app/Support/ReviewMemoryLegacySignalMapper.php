<?php

namespace App\Support;

use App\Models\ReviewMemoryProgress;

class ReviewMemoryLegacySignalMapper
{
    public const SOURCE_CLASSIC = 'classic_progress';

    public const SOURCE_VERIFIED = 'verified_memory';

    /**
     * @param  array<string, int|string>  $signal
     */
    public function segment(array $signal): string
    {
        if ((string) $signal['plan_segment'] === ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY) {
            return ReviewMemorySignalService::SEGMENT_RISKY;
        }

        if ((int) $signal['overdue_days'] > 0) {
            return ReviewMemorySignalService::SEGMENT_OVERDUE;
        }

        return ReviewMemorySignalService::SEGMENT_REINFORCE;
    }

    /**
     * @param  array<string, int|string>  $signal
     */
    public function memoryState(array $signal): string
    {
        return match ((string) $signal['verified_memory_state']) {
            ReviewMemoryProgress::STATE_NEEDS_RECOVERY => (int) $signal['leech_score'] >= 65
                ? ReviewMemorySignalService::STATE_LEECH
                : ReviewMemorySignalService::STATE_RELEARNING,
            ReviewMemoryProgress::STATE_VERIFIED_MEMORY => ReviewMemorySignalService::STATE_MASTERED,
            ReviewMemoryProgress::STATE_REVIEW => ReviewMemorySignalService::STATE_REVIEW,
            ReviewMemoryProgress::STATE_NEW => ReviewMemorySignalService::STATE_NEW,
            default => ReviewMemorySignalService::STATE_NEW,
        };
    }

    /**
     * @param  array<string, int|string>  $signal
     * @return array<string, int|string>
     */
    public function verifiedPreviewSignal(array $signal, int $difficulty): array
    {
        $difficulty = max(1, min(5, $difficulty));

        return [
            'version' => ReviewMemorySignalService::VERSION,
            'source' => self::SOURCE_VERIFIED,
            'verified_memory_signal_version' => (string) $signal['version'],
            'source_policy_version' => (string) $signal['source_policy_version'],
            'verified_memory_state' => (string) $signal['verified_memory_state'],
            'memory_state' => $this->memoryState($signal),
            'plan_segment' => $this->segment($signal),
            'leech_score' => (int) $signal['leech_score'],
            'stability_score' => (int) $signal['stability_score'],
            'difficulty_score' => $difficulty * 20,
            'overdue_days' => (int) $signal['overdue_days'],
            'recovery_score' => (int) $signal['recovery_score'],
        ];
    }

    /**
     * @param  array<string, int|string>  $signal
     * @return array<string, int|string>
     */
    public function classicPreviewSignal(array $signal): array
    {
        return [
            ...$signal,
            'source' => self::SOURCE_CLASSIC,
        ];
    }
}
