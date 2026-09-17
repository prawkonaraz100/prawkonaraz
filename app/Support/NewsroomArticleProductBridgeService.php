<?php

namespace App\Support;

use App\Models\ContentArticle;
use App\Models\LegalUnit;
use App\Models\TrafficSign;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class NewsroomArticleProductBridgeService
{
    private const MAX_QUESTIONS_PER_BLOCK = 5;

    public function __construct(
        private readonly PublicQuestionCatalogService $questionCatalog,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * Resolve public-safe Product Bridge payloads for body blocks.
     *
     * A body-block identifier is never sufficient on its own. Question, legal and
     * traffic-sign targets must also be explicitly attached to the article through
     * the corresponding editorial pivot and satisfy their existing public contract.
     *
     * @param  list<array{type:string,data:array<string,mixed>,key?:string}>  $blocks
     * @return array<int, array<string, mixed>> keyed by body-block index
     */
    public function prepare(ContentArticle $article, array $blocks): array
    {
        $questionCards = $this->questionCards($article, $blocks);
        $legalCards = $this->legalCards($article, $blocks);
        $trafficSignCards = $this->trafficSignCards($article, $blocks);
        $prepared = [];

        foreach ($blocks as $index => $block) {
            $resolved = match ($block['type']) {
                NewsroomBodyContract::BLOCK_QUESTION_GROUP => $this->prepareQuestionGroup($block, $questionCards),
                NewsroomBodyContract::BLOCK_LEGAL_REFERENCE => $this->prepareLegalReference($block, $legalCards),
                NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP => $this->prepareTrafficSignGroup($block, $trafficSignCards),
                NewsroomBodyContract::BLOCK_PRODUCT_CTA => $this->prepareProductCta($block),
                default => null,
            };

            if ($resolved !== null) {
                $prepared[$index] = $resolved;
            }
        }

        return $prepared;
    }

    /**
     * @param  list<array{type:string,data:array<string,mixed>,key?:string}>  $blocks
     * @return Collection<int, array<string, mixed>> keyed by requested Question database id
     */
    private function questionCards(ContentArticle $article, array $blocks): Collection
    {
        $requestedIds = $this->idsFromBlocks($blocks, NewsroomBodyContract::BLOCK_QUESTION_GROUP, 'question_ids');

        if ($requestedIds->isEmpty()) {
            return collect();
        }

        $linkedIds = $article->questions()
            ->whereIn('questions.id', $requestedIds)
            ->pluck('questions.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($linkedIds->isEmpty()) {
            return collect();
        }

        $questions = $this->questionCatalog->publicQuestionsQuery()
            ->whereIn('questions.id', $linkedIds)
            ->get();

        $this->questionCatalog->prepareQuestionListQuestions($questions);

        return $questions->mapWithKeys(function ($question): array {
            $item = $this->questionCatalog->buildQuestionListItem($question);

            return [
                (int) $question->getKey() => [
                    'display_external_id' => (string) $item['display_external_id'],
                    'prompt' => (string) $item['prompt_plain'],
                    'url' => (string) $item['canonical_url'],
                    'category_code' => $item['category_code'],
                    'thumbnail_url' => $item['thumbnail_url'],
                    'thumbnail_alt' => $item['thumbnail_alt'],
                ],
            ];
        });
    }

    /**
     * @param  list<array{type:string,data:array<string,mixed>,key?:string}>  $blocks
     * @return Collection<int, array<string, mixed>> keyed by LegalUnit id
     */
    private function legalCards(ContentArticle $article, array $blocks): Collection
    {
        $requestedIds = collect($blocks)
            ->filter(fn (array $block): bool => $block['type'] === NewsroomBodyContract::BLOCK_LEGAL_REFERENCE)
            ->map(fn (array $block): int => (int) $block['data']['legal_unit_id'])
            ->filter()
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return collect();
        }

        $linkedIds = $article->legalUnits()
            ->whereIn('legal_units.id', $requestedIds)
            ->pluck('legal_units.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($linkedIds->isEmpty()) {
            return collect();
        }

        return LegalUnit::query()
            ->verified()
            ->whereHas('legalAct', fn ($query) => $query->verified())
            ->whereIn('id', $linkedIds)
            ->with([
                'legalAct:id,title,short_title',
                'contentPages' => fn ($query) => $query
                    ->published()
                    ->whereHas('topic', fn ($topicQuery) => $topicQuery->published())
                    ->orderBy('legal_content_page_legal_unit.sort_order')
                    ->orderBy('legal_content_pages.id'),
            ])
            ->get()
            ->mapWithKeys(function (LegalUnit $unit): array {
                $page = $unit->contentPages->first();

                if ($page === null) {
                    return [];
                }

                $actLabel = trim((string) ($unit->legalAct?->short_title ?: $unit->legalAct?->title));
                $unitLabel = trim((string) $unit->label);
                $reference = implode(' · ', array_values(array_filter([$actLabel, $unitLabel])));
                $title = trim((string) $unit->title);
                $summary = trim((string) ($unit->summary ?: $page->summary));

                return [
                    (int) $unit->getKey() => [
                        'reference' => $reference !== '' ? $reference : $title,
                        'title' => $title !== '' ? $title : $reference,
                        'summary' => $summary !== '' ? Str::limit(Str::squish(strip_tags($summary)), 360) : null,
                        'url' => route('public.regulations.show', $page->slug),
                    ],
                ];
            });
    }

    /**
     * @param  list<array{type:string,data:array<string,mixed>,key?:string}>  $blocks
     * @return Collection<int, array<string, mixed>> keyed by TrafficSign id
     */
    private function trafficSignCards(ContentArticle $article, array $blocks): Collection
    {
        $requestedIds = $this->idsFromBlocks($blocks, NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP, 'traffic_sign_ids');

        if ($requestedIds->isEmpty()) {
            return collect();
        }

        $linkedIds = $article->trafficSigns()
            ->whereIn('traffic_signs.id', $requestedIds)
            ->pluck('traffic_signs.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($linkedIds->isEmpty()) {
            return collect();
        }

        return TrafficSign::query()
            ->published()
            ->whereIn('id', $linkedIds)
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->get(['id', 'code', 'slug', 'name', 'image_path', 'image_alt'])
            ->mapWithKeys(fn (TrafficSign $sign): array => [
                (int) $sign->getKey() => [
                    'code' => $sign->publicCode(),
                    'title' => $sign->publicTitle(),
                    'url' => route('traffic-signs.show', $sign->slug),
                    'image_url' => $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
                    'image_alt' => $sign->publicImageAlt(),
                ],
            ]);
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @param  Collection<int, array<string,mixed>>  $cards
     * @return array<string,mixed>|null
     */
    private function prepareQuestionGroup(array $block, Collection $cards): ?array
    {
        $questions = collect($block['data']['question_ids'])
            ->map(fn (mixed $id) => $cards->get((int) $id))
            ->filter(fn (mixed $card): bool => is_array($card))
            ->take(self::MAX_QUESTIONS_PER_BLOCK)
            ->values()
            ->all();

        return $questions === [] ? null : [...$block, 'questions' => $questions];
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @param  Collection<int, array<string,mixed>>  $cards
     * @return array<string,mixed>|null
     */
    private function prepareLegalReference(array $block, Collection $cards): ?array
    {
        $card = $cards->get((int) $block['data']['legal_unit_id']);

        return is_array($card) ? [...$block, 'legal_reference' => $card] : null;
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @param  Collection<int, array<string,mixed>>  $cards
     * @return array<string,mixed>|null
     */
    private function prepareTrafficSignGroup(array $block, Collection $cards): ?array
    {
        $signs = collect($block['data']['traffic_sign_ids'])
            ->map(fn (mixed $id) => $cards->get((int) $id))
            ->filter(fn (mixed $card): bool => is_array($card))
            ->values()
            ->all();

        return $signs === [] ? null : [...$block, 'traffic_signs' => $signs];
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @return array<string,mixed>|null
     */
    private function prepareProductCta(array $block): ?array
    {
        $cta = match ((string) $block['data']['kind']) {
            'test' => [
                'label' => 'Sprawdź się w teście',
                'description' => 'Przejdź do publicznego testu na prawo jazdy.',
                'url' => route('public.tests'),
            ],
            'related_questions' => [
                'label' => 'Zobacz oficjalną bazę pytań',
                'description' => 'Przejdź do oficjalnych pytań egzaminacyjnych.',
                'url' => route('public.questions.hub'),
            ],
            'learning' => [
                'label' => 'Przejdź do nauki',
                'description' => 'Kontynuuj naukę w PrawkoNaRaz.',
                'url' => route('session.index'),
            ],
            default => null,
        };

        return $cta === null ? null : [...$block, 'product_cta' => $cta];
    }

    /**
     * @param  list<array{type:string,data:array<string,mixed>,key?:string}>  $blocks
     * @return Collection<int, int>
     */
    private function idsFromBlocks(array $blocks, string $type, string $key): Collection
    {
        return collect($blocks)
            ->filter(fn (array $block): bool => $block['type'] === $type)
            ->flatMap(fn (array $block): array => array_map('intval', $block['data'][$key] ?? []))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }
}
