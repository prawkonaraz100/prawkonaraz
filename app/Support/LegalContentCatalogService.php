<?php

namespace App\Support;

use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LegalContentCatalogService
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
    ) {}

    /**
     * @return Collection<int, LegalContentPage>
     */
    public function publishedPages(): Collection
    {
        return LegalContentPage::query()
            ->published()
            ->with([
                'topic:id,slug,title,description,sort_order',
                'author:id,name,slug,job_title',
                'reviewer:id,name,slug,job_title',
                'legalUnits.legalAct:id,slug,title,short_title,source_url,eli_url,isap_url',
            ])
            ->whereHas('topic', fn ($query) => $query->published())
            ->orderBy(
                LegalTopic::query()
                    ->select('sort_order')
                    ->whereColumn('legal_topics.id', 'legal_content_pages.legal_topic_id')
                    ->limit(1),
            )
            ->orderBy('title')
            ->get();
    }

    public function findPublishedPage(string $slug): ?LegalContentPage
    {
        return LegalContentPage::query()
            ->published()
            ->with([
                'topic:id,slug,title,description,sort_order',
                'author:id,name,slug,job_title,bio,photo_path',
                'reviewer:id,name,slug,job_title,bio,photo_path',
                'legalUnits.legalAct:id,slug,title,short_title,source_url,eli_url,isap_url',
            ])
            ->whereHas('topic', fn ($query) => $query->published())
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return Collection<int, QuestionLegalReference>
     */
    public function questionReferencesForPage(LegalContentPage $page): Collection
    {
        return $page->questionLegalReferences()
            ->verified()
            ->with([
                'question.licenseCategory:id,code,slug,name,sort_order',
                'legalUnit.legalAct:id,slug,title,short_title,source_url',
            ])
            ->whereHas('question', fn ($query) => $query
                ->where('is_active', true)
                ->readyForDelivery())
            ->whereHas('contentPage', fn ($query) => $query->published())
            ->get()
            ->unique(function (QuestionLegalReference $reference): string {
                if (! $reference->question instanceof Question) {
                    return 'missing:'.$reference->getKey();
                }

                return $this->publicQuestionCatalogService->questionUrl($reference->question);
            })
            ->sortBy(fn (QuestionLegalReference $reference): string => (string) $reference->question?->external_id)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function questionCardsForPage(LegalContentPage $page): Collection
    {
        return $this->questionReferencesForPage($page)
            ->map(function (QuestionLegalReference $reference): ?array {
                if (! $reference->question instanceof Question) {
                    return null;
                }

                $item = $this->publicQuestionCatalogService->buildQuestionListItem($reference->question);
                $item['legal_note'] = $reference->public_note;
                $item['legal_reference_id'] = (int) $reference->getKey();
                $item['legal_unit_label'] = $reference->legalUnit?->label;

                return $item;
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, LegalUnit>
     */
    public function questionAssignmentLegalUnits(LegalContentPage $page): Collection
    {
        $units = $page->legalUnits
            ->filter(fn (LegalUnit $unit): bool => $unit->status === LegalUnit::STATUS_VERIFIED)
            ->keyBy(fn (LegalUnit $unit): int => (int) $unit->getKey());
        $parentIds = $units->keys()->all();

        while ($parentIds !== []) {
            $children = LegalUnit::query()
                ->verified()
                ->with('legalAct:id,title,short_title,status')
                ->whereIn('parent_legal_unit_id', $parentIds)
                ->whereNotIn('id', $units->keys()->all())
                ->orderBy('label')
                ->get();

            if ($children->isEmpty()) {
                break;
            }

            foreach ($children as $child) {
                $units->put((int) $child->getKey(), $child);
            }

            $parentIds = $children
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        }

        return $units
            ->sortBy(fn (LegalUnit $unit): string => (string) ($unit->canonical_path ?: $unit->label))
            ->values();
    }

    /**
     * @return Collection<int, QuestionLegalReference>
     */
    public function verifiedReferencesForQuestionGroup(Collection $questions): Collection
    {
        $questionIds = $questions
            ->pluck('id')
            ->filter()
            ->values();

        if ($questionIds->isEmpty()) {
            return collect();
        }

        return QuestionLegalReference::query()
            ->verified()
            ->with([
                'topic:id,slug,title,description',
                'contentPage' => fn ($query) => $query
                    ->published()
                    ->select('id', 'legal_topic_id', 'slug', 'title', 'summary', 'status', 'published_at', 'last_reviewed_at'),
                'legalUnit.legalAct:id,slug,title,short_title,source_url',
                'verifier:id,name,slug,job_title,is_published,published_at',
            ])
            ->whereIn('question_id', $questionIds)
            ->where(function ($query): void {
                $query
                    ->whereNull('legal_topic_id')
                    ->orWhereHas('topic', fn ($topicQuery) => $topicQuery->published());
            })
            ->whereHas('legalUnit', fn ($query) => $query->verified())
            ->get()
            ->unique(fn (QuestionLegalReference $reference): string => implode(':', [
                $reference->legal_content_page_id,
                $reference->legal_unit_id,
                $reference->legal_topic_id,
            ]))
            ->values();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function sitemapUrls(): array
    {
        return $this->publishedPages()
            ->map(fn (LegalContentPage $page): array => [
                'loc' => route('public.regulations.show', $page->slug),
                'lastmod' => ($page->last_reviewed_at ?: $page->updated_at)?->toIso8601String(),
                'images' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     legal_unit_current: array<string, mixed>|null,
     *     legal_units: list<array<string, mixed>>,
     *     legal_topics: list<array{id:int,label:string}>,
     *     legal_content_pages: list<array{id:int,label:string,legal_topic_id:int|null}>
     * }
     */
    public function questionLegalReferenceEditorOptions(?LegalUnit $selectedLegalUnit = null): array
    {
        $selectedLegalUnit?->loadMissing('legalAct:id,title,short_title,status');
        $legalUnitCurrent = $selectedLegalUnit instanceof LegalUnit
            ? $this->legalUnitSearchOption($selectedLegalUnit)
            : null;

        $legalTopics = LegalTopic::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (LegalTopic $topic): array => [
                'id' => (int) $topic->getKey(),
                'label' => (string) $topic->title,
            ])
            ->values()
            ->all();

        $legalContentPages = LegalContentPage::query()
            ->published()
            ->with('topic:id,title')
            ->orderBy('title')
            ->get(['id', 'legal_topic_id', 'title'])
            ->map(fn (LegalContentPage $page): array => [
                'id' => (int) $page->getKey(),
                'legal_topic_id' => $page->legal_topic_id ? (int) $page->legal_topic_id : null,
                'label' => trim(sprintf(
                    '%s%s',
                    $page->topic ? $page->topic->title.' - ' : '',
                    $page->title,
                )),
            ])
            ->values()
            ->all();

        return [
            'legal_unit_current' => $legalUnitCurrent,
            'legal_units' => $legalUnitCurrent !== null ? [$legalUnitCurrent] : [],
            'legal_topics' => $legalTopics,
            'legal_content_pages' => $legalContentPages,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchLegalUnitEditorOptions(string $query, int $limit = 20): array
    {
        $query = Str::squish($query);
        $limit = max(1, min($limit, 50));

        if (mb_strlen($query) < 2) {
            return [];
        }

        $like = '%'.$query.'%';

        return LegalUnit::query()
            ->with('legalAct:id,title,short_title,status')
            ->whereIn('status', [
                LegalUnit::STATUS_VERIFIED,
                LegalUnit::STATUS_NEEDS_REVIEW,
            ])
            ->whereHas('legalAct', fn ($legalActQuery) => $legalActQuery->verified())
            ->where(function ($unitQuery) use ($like): void {
                $unitQuery
                    ->where('label', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('canonical_path', 'like', $like)
                    ->orWhere('official_excerpt', 'like', $like)
                    ->orWhereHas('legalAct', function ($legalActQuery) use ($like): void {
                        $legalActQuery
                            ->where('title', 'like', $like)
                            ->orWhere('short_title', 'like', $like);
                    });
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [LegalUnit::STATUS_VERIFIED])
            ->orderByRaw('CASE WHEN label = ? THEN 0 ELSE 1 END', [$query])
            ->orderBy('label')
            ->limit($limit)
            ->get()
            ->map(fn (LegalUnit $unit): array => $this->legalUnitSearchOption($unit))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function legalUnitSearchOption(LegalUnit $unit): array
    {
        $actTitle = trim((string) ($unit->legalAct?->short_title ?: $unit->legalAct?->title));
        $label = trim(sprintf(
            '%s %s%s',
            $actTitle,
            $unit->label,
            filled($unit->title) && $unit->title !== $unit->label ? ' - '.$unit->title : '',
        ));
        $status = (string) $unit->status;

        return [
            'id' => (int) $unit->getKey(),
            'label' => $label,
            'legal_act' => $actTitle,
            'unit_label' => (string) $unit->label,
            'title' => (string) $unit->title,
            'canonical_path' => $unit->canonical_path,
            'status' => $status,
            'status_label' => match ($status) {
                LegalUnit::STATUS_VERIFIED => 'zweryfikowany',
                LegalUnit::STATUS_NEEDS_REVIEW => 'do review',
                default => $status,
            },
            'can_select' => $status === LegalUnit::STATUS_VERIFIED,
            'official_excerpt' => filled($unit->official_excerpt)
                ? Str::limit(Str::squish((string) $unit->official_excerpt), 220)
                : null,
            'source_url' => $unit->source_url,
        ];
    }

    public function latestPublishedPageLastModified(): ?string
    {
        $latest = LegalContentPage::query()
            ->published()
            ->whereHas('topic', fn ($query) => $query->published())
            ->get()
            ->flatMap(fn (LegalContentPage $page): array => [
                $page->updated_at?->toIso8601String(),
                $page->last_reviewed_at?->toIso8601String(),
            ])
            ->filter()
            ->sortDesc()
            ->first();

        return is_string($latest) ? $latest : null;
    }
}
