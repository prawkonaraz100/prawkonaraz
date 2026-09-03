<?php

namespace App\Http\Controllers;

use App\Models\QuestionSeoTopic;
use App\Support\PublicQuestionCatalogService;
use App\Support\SeoSitemapBuilder;
use App\Support\SeoSitemapXmlRenderer;
use Illuminate\Http\Response;

class SitemapQuestionsController extends Controller
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected SeoSitemapBuilder $builder,
        protected SeoSitemapXmlRenderer $renderer,
    ) {}

    public function hub(): Response
    {
        return $this->urlsetResponse($this->publicQuestionCatalogService->hubSitemapUrls());
    }

    public function categories(): Response
    {
        return $this->urlsetResponse($this->publicQuestionCatalogService->categorySitemapUrls());
    }

    public function topics(): Response
    {
        $urls = QuestionSeoTopic::query()
            ->indexable()
            ->orderBy('slug')
            ->get()
            ->map(fn (QuestionSeoTopic $topic): array => [
                'loc' => route('public.questions.topics.show', $topic->slug),
                'lastmod' => $topic->updated_at?->toIso8601String(),
                'images' => [],
            ])
            ->all();

        return $this->urlsetResponse($urls);
    }

    public function index(): Response
    {
        return $this->xmlResponse($this->renderer->sitemapIndex($this->builder->questionSitemapIndexItems()));
    }

    public function category(string $categorySlug): Response
    {
        $category = $this->publicQuestionCatalogService->findVisibleCategoryBySlug($categorySlug);

        abort_unless($category, 404);

        $urls = $this->publicQuestionCatalogService->sitemapUrlsForCanonicalCategory($category);
        $includeImages = collect($urls)
            ->contains(fn (array $item): bool => ($item['images'] ?? []) !== []);

        return $this->urlsetResponse($urls, $includeImages);
    }

    public function videos(): Response
    {
        return $this->xmlResponse(
            $this->renderer->videoUrlset($this->publicQuestionCatalogService->videoSitemapUrls()),
        );
    }

    protected function urlsetResponse(array $urls, bool $includeImages = false): Response
    {
        return $this->xmlResponse($this->renderer->urlset($urls, $includeImages));
    }

    protected function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
