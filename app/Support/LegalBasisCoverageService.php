<?php

namespace App\Support;

use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use Illuminate\Support\Collection;

class LegalBasisCoverageService
{
    public function __construct(
        protected StudyContextService $studyContextService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(int $sampleLimit = 10): array
    {
        $sampleLimit = max(0, $sampleLimit);
        $questions = $this->publicQuestions();
        $questionIds = $questions
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
        $references = $questionIds === []
            ? collect()
            : QuestionLegalReference::query()
                ->with([
                    'contentPage',
                    'legalUnit.legalAct',
                    'topic',
                ])
                ->whereIn('question_id', $questionIds)
                ->get();
        $referencesByQuestionId = $references->groupBy('question_id');
        $questionGroups = $questions->groupBy(
            fn (Question $question): string => $this->canonicalExternalId($question->external_id),
        );

        $stats = [
            'total' => $questionGroups->count(),
            'with_any_reference' => 0,
            'with_verified_reference' => 0,
            'missing_verified_reference' => 0,
            'with_verified_reference_without_topic' => 0,
            'with_verified_reference_with_article' => 0,
            'with_verified_reference_without_article' => 0,
            'with_verified_reference_without_public_note' => 0,
            'with_article_level_verified_reference' => 0,
        ];
        $samples = [
            'missing_verified_reference' => [],
            'without_article' => [],
            'article_level_verified_reference' => [],
        ];

        foreach ($questionGroups as $canonicalExternalId => $groupQuestions) {
            $groupReferences = $this->referencesForQuestionGroup($groupQuestions, $referencesByQuestionId);
            $verifiedReferences = $groupReferences
                ->filter(fn (QuestionLegalReference $reference): bool => $this->isVerifiedReference($reference))
                ->values();

            if ($groupReferences->isNotEmpty()) {
                $stats['with_any_reference']++;
            }

            if ($verifiedReferences->isEmpty()) {
                $stats['missing_verified_reference']++;
                $this->pushSample(
                    $samples['missing_verified_reference'],
                    $this->questionGroupSample((string) $canonicalExternalId, $groupQuestions),
                    $sampleLimit,
                );

                continue;
            }

            $stats['with_verified_reference']++;

            if ($verifiedReferences->contains(fn (QuestionLegalReference $reference): bool => blank($reference->legal_topic_id))) {
                $stats['with_verified_reference_without_topic']++;
            }

            if ($verifiedReferences->contains(fn (QuestionLegalReference $reference): bool => $this->hasPublishedArticle($reference))) {
                $stats['with_verified_reference_with_article']++;
            } else {
                $stats['with_verified_reference_without_article']++;
                $this->pushSample(
                    $samples['without_article'],
                    $this->questionGroupSample((string) $canonicalExternalId, $groupQuestions, $verifiedReferences->first()),
                    $sampleLimit,
                );
            }

            if (! $verifiedReferences->contains(fn (QuestionLegalReference $reference): bool => filled($reference->public_note))) {
                $stats['with_verified_reference_without_public_note']++;
            }

            $articleLevelReference = $verifiedReferences->first(
                fn (QuestionLegalReference $reference): bool => $this->isArticleLevelReference($reference),
            );

            if ($articleLevelReference instanceof QuestionLegalReference) {
                $stats['with_article_level_verified_reference']++;
                $this->pushSample(
                    $samples['article_level_verified_reference'],
                    $this->questionGroupSample((string) $canonicalExternalId, $groupQuestions, $articleLevelReference),
                    $sampleLimit,
                );
            }
        }

        $stats['verified_coverage_percent'] = $this->percent(
            $stats['with_verified_reference'],
            $stats['total'],
        );

        return [
            'question_rows' => [
                'total' => $questions->count(),
            ],
            'canonical_questions' => $stats,
            'legal_catalog' => $this->legalCatalogSummary(),
            'question_legal_references' => [
                'total_for_public_questions' => $references->count(),
                'status_counts' => $this->statusCounts($references),
            ],
            'samples' => $samples,
        ];
    }

    /**
     * @return Collection<int, Question>
     */
    protected function publicQuestions(): Collection
    {
        $visibleCategoryIds = $this->studyContextService
            ->visibleCategoriesQuery()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($visibleCategoryIds === []) {
            return collect();
        }

        return Question::query()
            ->with('licenseCategory:id,code,slug,name,sort_order')
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereIn('license_category_id', $visibleCategoryIds)
            ->get(['id', 'license_category_id', 'external_id']);
    }

    protected function canonicalExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '') {
            return '';
        }

        if (! str_contains($value, ':')) {
            return $value;
        }

        $suffix = trim(substr($value, (int) strrpos($value, ':') + 1));

        return $suffix !== '' ? $suffix : $value;
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  Collection<int|string, Collection<int, QuestionLegalReference>>  $referencesByQuestionId
     * @return Collection<int, QuestionLegalReference>
     */
    protected function referencesForQuestionGroup(Collection $questions, Collection $referencesByQuestionId): Collection
    {
        return $questions
            ->flatMap(fn (Question $question): Collection => $referencesByQuestionId->get($question->getKey(), collect()))
            ->values();
    }

    protected function isVerifiedReference(QuestionLegalReference $reference): bool
    {
        return $reference->status === QuestionLegalReference::STATUS_VERIFIED
            && $reference->verified_at !== null;
    }

    protected function hasPublishedArticle(QuestionLegalReference $reference): bool
    {
        return $reference->contentPage instanceof LegalContentPage
            && $reference->contentPage->isPubliclyVisible();
    }

    protected function isArticleLevelReference(QuestionLegalReference $reference): bool
    {
        $unit = $reference->legalUnit;

        if (! $unit instanceof LegalUnit) {
            return false;
        }

        $type = mb_strtolower(trim((string) $unit->type));

        if ($type === 'article') {
            return true;
        }

        $label = mb_strtolower(trim((string) $unit->label));

        return $label !== ''
            && ! preg_match('/\b(ust\.|pkt|lit\.|§|par\.)/u', $label);
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<string, mixed>
     */
    protected function questionGroupSample(
        string $canonicalExternalId,
        Collection $questions,
        ?QuestionLegalReference $reference = null,
    ): array {
        $sample = [
            'external_id' => $canonicalExternalId,
            'raw_external_ids' => $questions
                ->pluck('external_id')
                ->map(fn (mixed $externalId): string => (string) $externalId)
                ->unique()
                ->values()
                ->all(),
            'question_ids' => $questions
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all(),
            'category_codes' => $questions
                ->pluck('licenseCategory.code')
                ->filter()
                ->map(fn (mixed $code): string => (string) $code)
                ->unique()
                ->values()
                ->all(),
        ];

        if ($reference instanceof QuestionLegalReference) {
            $sample['legal_reference'] = [
                'id' => (int) $reference->getKey(),
                'legal_unit' => $reference->legalUnit instanceof LegalUnit
                    ? trim((string) $reference->legalUnit->label)
                    : null,
                'legal_act' => $reference->legalUnit?->legalAct instanceof LegalAct
                    ? ($reference->legalUnit->legalAct->short_title ?: $reference->legalUnit->legalAct->title)
                    : null,
                'topic' => $reference->topic instanceof LegalTopic ? $reference->topic->title : null,
                'content_page' => $reference->contentPage instanceof LegalContentPage ? $reference->contentPage->slug : null,
            ];
        }

        return $sample;
    }

    /**
     * @param  array<int, array<string, mixed>>  $samples
     * @param  array<string, mixed>  $sample
     */
    protected function pushSample(array &$samples, array $sample, int $sampleLimit): void
    {
        if ($sampleLimit <= 0 || count($samples) >= $sampleLimit) {
            return;
        }

        $samples[] = $sample;
    }

    /**
     * @param  Collection<int, QuestionLegalReference>  $references
     * @return array<string, int>
     */
    protected function statusCounts(Collection $references): array
    {
        return $references
            ->countBy(fn (QuestionLegalReference $reference): string => (string) $reference->status)
            ->sortKeys()
            ->map(fn (int $count): int => $count)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function legalCatalogSummary(): array
    {
        return [
            'legal_acts_total' => LegalAct::query()->count(),
            'verified_legal_acts' => LegalAct::query()->verified()->count(),
            'legal_units_total' => LegalUnit::query()->count(),
            'verified_legal_units' => LegalUnit::query()->verified()->count(),
            'legal_units_with_canonical_path' => LegalUnit::query()
                ->whereNotNull('canonical_path')
                ->where('canonical_path', '!=', '')
                ->count(),
            'legal_units_with_parent' => LegalUnit::query()
                ->whereNotNull('parent_legal_unit_id')
                ->count(),
            'legal_topics_total' => LegalTopic::query()->count(),
            'published_legal_topics' => LegalTopic::query()->published()->count(),
            'legal_content_pages_total' => LegalContentPage::query()->count(),
            'published_legal_content_pages' => LegalContentPage::query()->published()->count(),
        ];
    }

    protected function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }
}
