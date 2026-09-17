<?php

namespace App\Http\Controllers;

use App\Support\ContentArticlePathResolver;
use App\Support\ContentArticlePublicCatalogService;
use App\Support\ContentArticleSchemaService;
use App\Support\ContentArticleSeoService;
use App\Support\NewsroomArticlePresentationService;
use App\Support\NewsroomRouteContract;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ContentArticleController extends Controller
{
    public function __construct(
        private readonly ContentArticlePublicCatalogService $catalog,
        private readonly ContentArticlePathResolver $pathResolver,
        private readonly ContentArticleSeoService $seoService,
        private readonly ContentArticleSchemaService $schemaService,
        private readonly NewsroomArticlePresentationService $presentationService,
    ) {}

    public function news(string $articleSlug): Response|RedirectResponse
    {
        return $this->show(NewsroomRouteContract::FAMILY_NEWSROOM, $articleSlug);
    }

    public function guides(string $articleSlug): Response|RedirectResponse
    {
        return $this->show(NewsroomRouteContract::FAMILY_GUIDES, $articleSlug);
    }

    private function show(string $family, string $articleSlug): Response|RedirectResponse
    {
        $resolution = $this->catalog->resolveDetailBySlug($family, $articleSlug);

        if ($resolution->isGone()) {
            return $this->unavailable($family, Response::HTTP_GONE);
        }

        if ($resolution->isNotFound() || $resolution->article === null) {
            $redirectTarget = $this->pathResolver->findCanonicalRedirectTarget(request()->getPathInfo());

            if ($redirectTarget !== null) {
                return redirect($redirectTarget, Response::HTTP_MOVED_PERMANENTLY);
            }

            return $this->unavailable($family, Response::HTTP_NOT_FOUND);
        }

        $article = $resolution->article;

        try {
            $meta = $this->seoService->article($article);
            $structuredData = $this->schemaService->article($article);
            $presentation = $this->presentationService->present($article, $family);
        } catch (DomainException|RuntimeException|ValidationException) {
            return $this->unavailable($family, Response::HTTP_NOT_FOUND);
        }

        return response()->view('newsroom.article', [
            ...$presentation,
            'meta' => $meta,
            'structuredData' => $structuredData,
            'analyticsEnabled' => true,
        ]);
    }

    private function unavailable(string $family, int $status): Response
    {
        $isGone = $status === Response::HTTP_GONE;
        $hubName = $family === NewsroomRouteContract::FAMILY_GUIDES
            ? 'Poradniki'
            : 'Aktualności';
        $hubUrl = $family === NewsroomRouteContract::FAMILY_GUIDES
            ? route('public.guides')
            : route('public.news');

        $response = response()->view('newsroom.article-unavailable', [
            'status' => $status,
            'heading' => $isGone ? 'Materiał został wycofany' : 'Nie znaleziono materiału',
            'message' => $isGone
                ? 'Ta publikacja nie jest już dostępna.'
                : 'Sprawdź adres albo wróć do listy materiałów.',
            'hubName' => $hubName,
            'hubUrl' => $hubUrl,
            'breadcrumbs' => [
                ['label' => 'Strona główna', 'url' => route('home')],
                ['label' => $hubName, 'url' => $hubUrl],
                ['label' => $isGone ? 'Materiał wycofany' : 'Nie znaleziono'],
            ],
            'structuredData' => [],
            'meta' => [
                'title' => ($isGone ? 'Materiał wycofany' : 'Nie znaleziono materiału').' - '.config('app.name', 'PrawkoNaRaz'),
                'description' => null,
                'robots' => 'noindex,follow',
                'og_type' => 'website',
            ],
        ], $status);

        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }
}
