<?php

namespace App\Support;

use App\Models\QuestionExplanationDraft;
use Illuminate\Support\Facades\DB;

class QuestionExplanationDraftExceptionResolutionService
{
    /**
     * @param  array<int, array<string, mixed>>  $resolutions
     * @return array<string, mixed>
     */
    public function apply(array $resolutions, string $source = 'pj360'): array
    {
        $items = collect($resolutions)
            ->filter(fn (mixed $resolution): bool => is_array($resolution))
            ->values();

        $summary = [
            'source' => $source,
            'resolution_count' => $items->count(),
            'updated_count' => 0,
            'missing_drafts' => [],
            'updated_external_ids' => [],
        ];

        DB::transaction(function () use ($items, $source, &$summary): void {
            foreach ($items as $resolution) {
                $externalId = trim((string) ($resolution['external_id'] ?? ''));

                if ($externalId === '') {
                    continue;
                }

                $draft = QuestionExplanationDraft::query()
                    ->where('source', $source)
                    ->where('external_id', $externalId)
                    ->first();

                if (! $draft instanceof QuestionExplanationDraft) {
                    $summary['missing_drafts'][] = $externalId;

                    continue;
                }

                $qualityFlags = collect($draft->quality_flags ?? [])
                    ->push('manual_exception_override')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $stagingFlags = collect($draft->staging_flags ?? [])
                    ->push('manual_exception_resolved')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $sourcePayload = is_array($draft->source_payload) ? $draft->source_payload : [];
                $sourcePayload['manual_exception_resolution'] = [
                    'resolved_draft_text' => trim((string) ($resolution['resolved_draft_text'] ?? '')),
                    'resolved_source_summary' => trim((string) ($resolution['resolved_source_summary'] ?? '')),
                    'resolution_note' => trim((string) ($resolution['resolution_note'] ?? '')),
                    'resolved_at' => now()->toIso8601String(),
                ];

                $draft->forceFill([
                    'draft_text' => trim((string) ($resolution['resolved_draft_text'] ?? $draft->draft_text)),
                    'source_summary' => $this->nullableString($resolution['resolved_source_summary'] ?? $draft->source_summary),
                    'quality_flags' => $qualityFlags,
                    'staging_flags' => $stagingFlags,
                    'source_payload' => $sourcePayload,
                ])->save();

                $summary['updated_count']++;
                $summary['updated_external_ids'][] = $externalId;
            }
        });

        return $summary;
    }

    protected function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
