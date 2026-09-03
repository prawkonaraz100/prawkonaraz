<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionExplanationManualResolutionService
{
    /**
     * @param  array<int, array<string, mixed>>  $payload
     * @return array<string, mixed>
     */
    public function apply(array $payload, bool $overwriteExisting = false): array
    {
        $updatedQuestionIds = [];
        $updatedExternalIds = [];
        $missingExternalIds = [];

        foreach ($payload as $entry) {
            $externalId = trim((string) ($entry['external_id'] ?? ''));
            $explanation = trim((string) ($entry['resolved_explanation'] ?? ''));

            if ($externalId === '' || $explanation === '') {
                continue;
            }

            $query = Question::query()
                ->where('external_id', $externalId);

            if (! $overwriteExisting) {
                $query->where(function ($questionQuery): void {
                    $questionQuery->whereNull('explanation')
                        ->orWhere('explanation', '');
                });
            }

            $questionIds = $query->pluck('id')->all();

            if ($questionIds === []) {
                $missingExternalIds[] = $externalId;

                continue;
            }

            DB::transaction(function () use ($questionIds, $explanation, &$updatedQuestionIds): void {
                Question::query()
                    ->whereIn('id', $questionIds)
                    ->update(['explanation' => $explanation]);

                $updatedQuestionIds = [...$updatedQuestionIds, ...$questionIds];
            });

            $updatedExternalIds[] = $externalId;
        }

        return [
            'resolution_count' => count($payload),
            'overwrite_existing' => $overwriteExisting,
            'updated_external_id_count' => count($updatedExternalIds),
            'updated_external_ids' => $updatedExternalIds,
            'updated_question_row_count' => count($updatedQuestionIds),
            'updated_question_ids' => $updatedQuestionIds,
            'missing_external_id_count' => count($missingExternalIds),
            'missing_external_ids' => $missingExternalIds,
        ];
    }
}
