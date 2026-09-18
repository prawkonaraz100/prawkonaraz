<?php

namespace App\Http\Controllers;

use App\Support\NewsroomTopicReadModelService;
use App\Support\NewsroomTopicSchemaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsroomTopicController extends Controller
{
    public function __invoke(
        Request $request,
        string $topicSlug,
        NewsroomTopicReadModelService $readModelService,
        NewsroomTopicSchemaService $schemaService,
    ): Response {
        $page = $this->page($request);
        $resolution = $readModelService->resolve($topicSlug, $page);

        if ($resolution['disposition'] === NewsroomTopicReadModelService::DISPOSITION_GONE) {
            return $this->unavailable(Response::HTTP_GONE);
        }

        if (
            $resolution['disposition'] !== NewsroomTopicReadModelService::DISPOSITION_VISIBLE
            || ! isset($resolution['topic'], $resolution['articles'])
        ) {
            return $this->unavailable(Response::HTTP_NOT_FOUND);
        }

        $topic = $resolution['topic'];
        $featured = $resolution['featured'] ?? null;
        $articles = $resolution['articles'];
        $baseCanonical = route('public.news.topics.show', ['topicSlug' => $topic['slug']]);
        $canonical = $page > 1 ? $baseCanonical.'?page='.$page : $baseCanonical;
        $siteName = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));
        $baseTitle = $topic['seo_title']
            ?: $topic['title'].' — temat | '.$siteName;
        $title = $page > 1 ? $baseTitle.' — strona '.$page : $baseTitle;
        $description = $topic['seo_description'] ?: $topic['description'];
        $breadcrumbs = [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Aktualności', 'url' => route('public.news')],
            ['label' => $topic['title'], 'url' => $baseCanonical],
        ];

        return response()->view('newsroom.topic', [
            'topic' => $topic,
            'featured' => $featured,
            'articles' => $articles,
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large',
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $schemaService->build(
                $topic,
                $featured,
                $articles,
                $breadcrumbs,
                $canonical,
                $description,
            ),
        ]);
    }

    private function unavailable(int $status): Response
    {
        $isGone = $status === Response::HTTP_GONE;

        $response = response()->view('newsroom.article-unavailable', [
            'status' => $status,
            'heading' => $isGone ? 'Temat został zarchiwizowany' : 'Nie znaleziono tematu',
            'message' => $isGone
                ? 'Ten temat nie jest już dostępny jako publiczny dossier.'
                : 'Sprawdź adres albo wróć do aktualności.',
            'hubName' => 'Aktualności',
            'hubUrl' => route('public.news'),
            'breadcrumbs' => [
                ['label' => 'Strona główna', 'url' => route('home')],
                ['label' => 'Aktualności', 'url' => route('public.news')],
                ['label' => $isGone ? 'Temat zarchiwizowany' : 'Nie znaleziono'],
            ],
            'structuredData' => [],
            'meta' => [
                'title' => ($isGone ? 'Temat zarchiwizowany' : 'Nie znaleziono tematu').' - '.config('app.name', 'PrawkoNaRaz'),
                'description' => null,
                'robots' => 'noindex,follow',
                'og_type' => 'website',
            ],
        ], $status);

        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }

    private function page(Request $request): int
    {
        $rawPage = $request->query('page', '1');

        if (is_array($rawPage)) {
            abort(404);
        }

        $rawPage = trim((string) $rawPage);

        if ($rawPage === '' || preg_match('/\A[1-9][0-9]*\z/', $rawPage) !== 1) {
            abort(404);
        }

        return (int) $rawPage;
    }
}
