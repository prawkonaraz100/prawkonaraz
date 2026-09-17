<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class NewsroomArticleBodyRenderer
{
    public function __construct(
        private readonly NewsroomRichTextHtmlRenderer $richTextRenderer,
        private readonly MediaUrlResolver $mediaUrlResolver,
        private readonly NewsroomArticleProductBridgeService $productBridge,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function render(ContentArticle $article, bool $includeDeferredBlocks = false): array
    {
        $blocks = NewsroomBodyContract::normalize(
            is_array($article->body_blocks) ? $article->body_blocks : [],
            (int) ($article->body_schema_version ?? NewsroomBodyContract::CURRENT_SCHEMA_VERSION),
        );
        $relatedArticles = $this->relatedArticles($blocks);
        $productBridgeBlocks = $includeDeferredBlocks ? [] : $this->productBridge->prepare($article, $blocks);
        $disk = (string) config('media.newsroom_disk', config('media.public_disk', 'public'));
        $rendered = [];

        foreach ($blocks as $index => $block) {
            $prepared = $this->prepareBlock(
                $block,
                $relatedArticles,
                $disk,
                $includeDeferredBlocks,
                $productBridgeBlocks[$index] ?? null,
            );

            if ($prepared !== null) {
                $rendered[] = $prepared;
            }
        }

        return $rendered;
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @param  Collection<int, ContentArticle>  $relatedArticles
     * @param  array<string,mixed>|null  $productBridgeBlock
     * @return array<string,mixed>|null
     */
    private function prepareBlock(
        array $block,
        Collection $relatedArticles,
        string $disk,
        bool $includeDeferredBlocks,
        ?array $productBridgeBlock,
    ): ?array {
        return match ($block['type']) {
            NewsroomBodyContract::BLOCK_RICH_TEXT => [
                ...$block,
                'render_html' => $this->richTextRenderer->render($block['data']['content']),
            ],
            NewsroomBodyContract::BLOCK_IMAGE => [
                ...$block,
                'public_url' => $this->mediaUrlResolver->resolve(
                    $block['data']['path'] ?? null,
                    $disk,
                ),
                'object_position' => $this->objectPosition(
                    $block['data']['focal_x'] ?? null,
                    $block['data']['focal_y'] ?? null,
                ),
            ],
            NewsroomBodyContract::BLOCK_QUOTE,
            NewsroomBodyContract::BLOCK_TABLE,
            NewsroomBodyContract::BLOCK_CONTEXT => $block,
            NewsroomBodyContract::BLOCK_RELATED_ARTICLE => $includeDeferredBlocks ? $block : $this->prepareRelatedArticle(
                $block,
                $relatedArticles,
            ),
            NewsroomBodyContract::BLOCK_LEGAL_REFERENCE,
            NewsroomBodyContract::BLOCK_QUESTION_GROUP,
            NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP,
            NewsroomBodyContract::BLOCK_PRODUCT_CTA => $includeDeferredBlocks ? $block : $productBridgeBlock,
            default => null,
        };
    }

    /**
     * @param  list<array<string,mixed>>  $blocks
     * @return Collection<int, ContentArticle>
     */
    private function relatedArticles(array $blocks): Collection
    {
        $ids = collect($blocks)
            ->filter(fn (array $block): bool => $block['type'] === NewsroomBodyContract::BLOCK_RELATED_ARTICLE)
            ->map(fn (array $block): int => (int) $block['data']['article_id'])
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return ContentArticle::query()
            ->select(['id', 'type', 'title', 'slug', 'lead'])
            ->publiclyVisible()
            ->whereHas('category', fn ($query) => $query->active())
            ->whereHas('author', fn ($query) => $query->published())
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (ContentArticle $article): bool => $this->canonicalPath($article) !== null)
            ->keyBy(fn (ContentArticle $article): int => (int) $article->getKey());
    }

    /**
     * @param  array{type:string,data:array<string,mixed>,key?:string}  $block
     * @param  Collection<int, ContentArticle>  $relatedArticles
     * @return array<string,mixed>|null
     */
    private function prepareRelatedArticle(array $block, Collection $relatedArticles): ?array
    {
        $article = $relatedArticles->get((int) $block['data']['article_id']);

        if (! $article instanceof ContentArticle) {
            return null;
        }

        $path = $this->canonicalPath($article);

        if ($path === null) {
            return null;
        }

        return [
            ...$block,
            'related_article' => [
                'title' => (string) $article->title,
                'lead' => filled($article->lead) ? (string) $article->lead : null,
                'url' => url($path),
            ],
        ];
    }

    private function canonicalPath(ContentArticle $article): ?string
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        try {
            return NewsroomRouteContract::canonicalPath($type, (string) $article->slug);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function objectPosition(mixed $x, mixed $y): ?string
    {
        if (! is_numeric($x) || ! is_numeric($y)) {
            return null;
        }

        $x = min(max((float) $x, 0.0), 1.0) * 100;
        $y = min(max((float) $y, 0.0), 1.0) * 100;

        return number_format($x, 2, '.', '').'% '.number_format($y, 2, '.', '').'%';
    }
}
