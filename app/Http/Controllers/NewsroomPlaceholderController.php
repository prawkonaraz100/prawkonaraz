<?php

namespace App\Http\Controllers;

use App\Support\NewsroomGuideHubReadModelService;
use App\Support\NewsroomGuideHubSchemaService;
use App\Support\NewsroomHomeReadModelService;
use App\Support\NewsroomHomeSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class NewsroomPlaceholderController extends Controller
{
    public function news(
        Request $request,
        NewsroomHomeReadModelService $readModelService,
        NewsroomHomeSchemaService $schemaService,
    ): Response {
        $home = $readModelService->build();

        if ($home === null) {
            return $this->placeholder(
                $request,
                'Aktualności',
                'Serwis',
                'Tu pojawią się aktualności dla kandydatów, kursantów i instruktorów prawa jazdy.',
            );
        }

        $canonical = route('public.news');
        $description = 'Aktualności, wyjaśnienia i poradniki o prawie jazdy, egzaminach, przepisach, WORD i bezpieczeństwie ruchu.';
        $breadcrumbs = [
            [
                'label' => 'Strona główna',
                'url' => route('home'),
            ],
            [
                'label' => 'Aktualności',
                'url' => $canonical,
            ],
        ];

        return response()->view('newsroom.home', [
            'home' => $home,
            'meta' => [
                'title' => 'Aktualności o prawie jazdy, egzaminach i przepisach | prawkonaraz.pl',
                'description' => $description,
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large',
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $schemaService->build(
                $breadcrumbs,
                $canonical,
                $description,
            ),
        ]);
    }

    public function guides(
        Request $request,
        NewsroomGuideHubReadModelService $readModelService,
        NewsroomGuideHubSchemaService $schemaService,
    ): Response {
        $page = $this->page($request);
        $readModel = $readModelService->build($page);

        if ($readModel === null) {
            if ($page > 1) {
                abort(404);
            }

            return $this->placeholder(
                $request,
                'Poradniki',
                'Nauka',
                'W poradnikach zbierzemy praktyczne materiały pomagające przejść od teorii do pewnego wyniku na egzaminie.',
            );
        }

        $guides = $readModel['guides'];
        $baseCanonical = route('public.guides');
        $canonical = $page > 1 ? $baseCanonical.'?page='.$page : $baseCanonical;
        $description = 'Praktyczne poradniki o prawie jazdy, egzaminach, formalnościach i przygotowaniu do nauki oraz egzaminu.';
        $hasGuides = $guides->total() > 0;
        $robots = $hasGuides
            ? 'index,follow,max-image-preview:large'
            : 'noindex,follow';
        $title = $page > 1
            ? 'Poradniki o prawie jazdy — strona '.$page.' | prawkonaraz.pl'
            : 'Poradniki o prawie jazdy i egzaminach | prawkonaraz.pl';
        $breadcrumbs = [
            [
                'label' => 'Strona główna',
                'url' => route('home'),
            ],
            [
                'label' => 'Poradniki',
                'url' => $baseCanonical,
            ],
        ];

        $response = response()->view('newsroom.guides', [
            'guides' => $guides,
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'robots' => $robots,
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $schemaService->build(
                $guides,
                $breadcrumbs,
                $canonical,
                $description,
            ),
        ]);

        if (! $hasGuides) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        return $response;
    }

    private function page(Request $request): int
    {
        $rawPage = $request->query('page', '1');

        if (is_array($rawPage)) {
            abort(404);
        }

        $rawPage = trim((string) $rawPage);

        if ($rawPage === '' || preg_match('/\\A[1-9][0-9]*\\z/', $rawPage) !== 1) {
            abort(404);
        }

        return (int) $rawPage;
    }

    private function placeholder(
        Request $request,
        string $title,
        string $eyebrow,
        string $description,
    ): Response {
        $response = Inertia::render('Public/MarketingPlaceholder', [
            'title' => $title,
            'eyebrow' => $eyebrow,
            'description' => $description,
        ])->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }
}
