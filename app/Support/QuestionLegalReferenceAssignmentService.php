<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\LegalContentPage;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuestionLegalReferenceAssignmentService
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchForPage(LegalContentPage $page, string $search, int $limit = 30): Collection
    {
        $search = Str::squish($search);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        $like = '%'.mb_strtolower($search).'%';
        $limit = max(1, min($limit, 50));

        return Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where(function ($query) use ($like): void {
                $query
                    ->whereRaw("lower(coalesce(external_id, '')) like ?", [$like])
                    ->orWhereRaw("lower(coalesce(prompt, '')) like ?", [$like]);
            })
            ->with([
                'licenseCategory:id,code,name',
                'questionTopic:id,name',
                'legalReferences' => fn ($query) => $query
                    ->with('contentPage:id,slug,title')
                    ->where('status', QuestionLegalReference::STATUS_VERIFIED),
            ])
            ->orderBy('external_id')
            ->limit($limit * 5)
            ->get()
            ->groupBy(fn (Question $question): string => $this->canonicalExternalId($question->external_id))
            ->map(function (Collection $questions, string $externalId) use ($page): array {
                /** @var Question $representative */
                $representative = $questions
                    ->sortBy(fn (Question $question): array => [
                        str_contains((string) $question->external_id, ':') ? 1 : 0,
                        $question->getKey(),
                    ])
                    ->first();
                $item = $this->publicQuestionCatalogService->buildQuestionListItem($representative);
                $references = $questions
                    ->flatMap(fn (Question $question): Collection => $question->legalReferences)
                    ->values();
                $articleTitles = $references
                    ->map(fn (QuestionLegalReference $reference): ?string => $reference->contentPage?->title)
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'external_id' => $externalId,
                    'display_external_id' => $item['display_external_id'] ?? $externalId,
                    'prompt' => $item['prompt_plain'] ?? (string) $representative->prompt,
                    'category_code' => $item['category_code'] ?? null,
                    'topic_name' => $representative->questionTopic?->name,
                    'url' => $item['url'] ?? null,
                    'already_attached' => $references->contains(
                        fn (QuestionLegalReference $reference): bool => (int) $reference->legal_content_page_id === (int) $page->getKey(),
                    ),
                    'article_titles' => $articleTitles->all(),
                ];
            })
            ->values()
            ->take($limit);
    }

    /**
     * @param  list<string>  $externalIds
     * @param  array<string, string|null>  $publicNotes
     * @return array{external_ids:list<string>, question_rows:int, created:int, updated:int}
     */
    public function assign(
        LegalContentPage $page,
        LegalUnit $legalUnit,
        array $externalIds,
        array $publicNotes = [],
    ): array {
        if (! $this->legalUnitBelongsToPageContext($page, $legalUnit)) {
            throw ValidationException::withMessages([
                'legal_unit_id' => 'Wybrany przepis nie jest przypisany do tego artykułu.',
            ]);
        }

        $canonicalExternalIds = collect($externalIds)
            ->map(fn (string $externalId): string => $this->canonicalExternalId($externalId))
            ->filter()
            ->unique()
            ->values();
        $questionGroups = $canonicalExternalIds
            ->mapWithKeys(fn (string $externalId): array => [
                $externalId => $this->questionGroup($externalId),
            ]);
        $missingExternalIds = $questionGroups
            ->filter(fn (Collection $questions): bool => $questions->isEmpty())
            ->keys()
            ->values();

        if ($missingExternalIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'external_ids' => 'Nie znaleziono aktywnych pytań: '.$missingExternalIds->implode(', ').'.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $questionRows = 0;
        $defaultVerifierId = ContentAuthor::defaultLegalReferenceVerifier()?->getKey();

        DB::transaction(function () use (
            $page,
            $legalUnit,
            $publicNotes,
            $questionGroups,
            $defaultVerifierId,
            &$created,
            &$updated,
            &$questionRows,
        ): void {
            foreach ($questionGroups as $externalId => $questions) {
                $publicNote = filled($publicNotes[$externalId] ?? null)
                    ? trim((string) $publicNotes[$externalId])
                    : null;

                foreach ($questions as $question) {
                    $reference = QuestionLegalReference::query()->firstOrNew([
                        'question_id' => $question->getKey(),
                        'legal_unit_id' => $legalUnit->getKey(),
                        'legal_topic_id' => $page->legal_topic_id,
                    ]);
                    $reference->exists ? $updated++ : $created++;
                    $questionRows++;

                    $reference->fill([
                        'legal_content_page_id' => $page->getKey(),
                        'relation_type' => 'direct_basis',
                        'public_note' => $publicNote,
                        'internal_note' => 'Ręczne przypisanie pytania z publicznej strony artykułu.',
                        'assignment_source' => QuestionLegalReference::SOURCE_MANUAL,
                        'confidence' => 90,
                        'status' => QuestionLegalReference::STATUS_VERIFIED,
                        'verified_by' => $page->reviewer_id ?: $page->author_id ?: $defaultVerifierId,
                        'verified_at' => now(),
                    ])->save();
                }
            }
        });

        return [
            'external_ids' => $canonicalExternalIds->all(),
            'question_rows' => $questionRows,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * @return array{external_id:string, affected:int}
     */
    public function remove(LegalContentPage $page, QuestionLegalReference $reference): array
    {
        if ((int) $reference->legal_content_page_id !== (int) $page->getKey()) {
            throw ValidationException::withMessages([
                'reference' => 'To powiązanie nie należy do wybranego artykułu.',
            ]);
        }

        $reference->loadMissing('question:id,external_id');
        $externalId = $this->canonicalExternalId($reference->question?->external_id);
        $questionIds = $this->questionGroup($externalId)->pluck('id');

        if ($questionIds->isEmpty()) {
            $questionIds = collect([(int) $reference->question_id]);
        }

        $affected = QuestionLegalReference::query()
            ->where('legal_content_page_id', $page->getKey())
            ->whereIn('question_id', $questionIds)
            ->update([
                'assignment_source' => QuestionLegalReference::SOURCE_MANUAL,
                'status' => QuestionLegalReference::STATUS_REJECTED,
                'verified_by' => null,
                'verified_at' => null,
                'internal_note' => 'Ręcznie usunięto powiązanie z publicznej strony artykułu.',
                'updated_at' => now(),
            ]);

        return [
            'external_id' => $externalId,
            'affected' => $affected,
        ];
    }

    /**
     * @return Collection<int, Question>
     */
    public function questionGroup(string $externalId): Collection
    {
        $canonicalExternalId = $this->canonicalExternalId($externalId);

        return Question::query()
            ->whereIn('external_id', [$canonicalExternalId, 'pj360:'.$canonicalExternalId])
            ->where('is_active', true)
            ->get();
    }

    public function canonicalExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '' || ! str_contains($value, ':')) {
            return $value;
        }

        $suffix = trim(substr($value, (int) strrpos($value, ':') + 1));

        return $suffix !== '' ? $suffix : $value;
    }

    protected function legalUnitBelongsToPageContext(LegalContentPage $page, LegalUnit $legalUnit): bool
    {
        $pageUnitIds = $page->legalUnits()
            ->pluck('legal_units.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $currentUnit = $legalUnit;
        $visited = [];

        while ($currentUnit instanceof LegalUnit) {
            $currentId = (int) $currentUnit->getKey();

            if (in_array($currentId, $pageUnitIds, true)) {
                return true;
            }

            if ($currentUnit->parent_legal_unit_id === null || isset($visited[$currentId])) {
                return false;
            }

            $visited[$currentId] = true;
            $currentUnit = LegalUnit::query()->find($currentUnit->parent_legal_unit_id);
        }

        return false;
    }
}
