<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use Illuminate\Support\Collection;

class SharedQuestionScopeService
{
    /**
     * @return Collection<int, Question>
     */
    public function questionsForSharedExternalId(Question $question): Collection
    {
        if (! filled($question->external_id)) {
            return collect([$question]);
        }

        return Question::query()
            ->where('external_id', $question->external_id)
            ->when(
                filled($question->source),
                fn ($query) => $query->where('source', $question->source),
            )
            ->get();
    }

    /**
     * @param  iterable<int, Question>  $questions
     * @return array<string, array<string, bool>>
     */
    public function explanationConflictMapForQuestions(iterable $questions): array
    {
        $externalIds = collect($questions)
            ->map(fn (Question $question) => $question->external_id)
            ->filter(fn (mixed $externalId) => filled($externalId))
            ->map(fn (mixed $externalId) => (string) $externalId)
            ->unique()
            ->values();

        if ($externalIds->isEmpty()) {
            return [];
        }

        /** @var Collection<int, object{external_id:string, source:?string, explanation_variants:int|string}> $rows */
        $rows = Question::query()
            ->selectRaw("external_id, source, count(distinct coalesce(explanation, '__NULL__')) as explanation_variants")
            ->whereIn('external_id', $externalIds->all())
            ->groupBy('external_id', 'source')
            ->get();

        return $rows
            ->groupBy(fn (object $row) => (string) $row->external_id)
            ->map(function (Collection $groupRows): array {
                return $groupRows
                    ->mapWithKeys(fn (object $row) => [
                        $this->sourceKey($row->source) => (int) $row->explanation_variants > 1,
                    ])
                    ->all();
            })
            ->all();
    }

    /**
     * @param  array<string, array<string, bool>>  $conflictMap
     */
    public function hasSharedExplanationConflict(Question $question, array $conflictMap): bool
    {
        if (! filled($question->external_id)) {
            return false;
        }

        $externalId = (string) $question->external_id;
        $sourceConflicts = $conflictMap[$externalId] ?? [];

        if ($sourceConflicts === []) {
            return false;
        }

        if (filled($question->source)) {
            return (bool) ($sourceConflicts[$this->sourceKey($question->source)] ?? false);
        }

        return in_array(true, $sourceConflicts, true);
    }

    public function hasSharedExplanationAssetConflict(Question $question): bool
    {
        if (! filled($question->external_id)) {
            return false;
        }

        $questions = $this->questionsForSharedExternalId($question)->loadMissing('referenceExplanationAsset');

        return $questions
            ->map(fn (Question $groupQuestion): string => $this->explanationAssetSignature($groupQuestion->referenceExplanationAsset))
            ->unique()
            ->count() > 1;
    }

    protected function sourceKey(?string $source): string
    {
        return $source ?? '__NULL_SOURCE__';
    }

    protected function explanationAssetSignature(?QuestionExplanationAsset $asset): string
    {
        if (! $asset) {
            return '__NO_ASSET__';
        }

        return implode('|', [
            $asset->disk ?? '__NULL__',
            $asset->file_path ?? '__NULL__',
            $asset->title ?? '__NULL__',
            $asset->body ?? '__NULL__',
            $asset->caption ?? '__NULL__',
            $asset->alt_text ?? '__NULL__',
            $asset->position,
            $asset->is_active ? '1' : '0',
        ]);
    }
}
