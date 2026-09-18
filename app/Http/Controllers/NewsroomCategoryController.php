<?php

namespace App\Http\Controllers;

use App\Support\NewsroomCategoryReadModelService;
use App\Support\NewsroomCategorySchemaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsroomCategoryController extends Controller
{
    public function __invoke(
        Request $request,
        string $categorySlug,
        NewsroomCategoryReadModelService $readModelService,
        NewsroomCategorySchemaService $schemaService,
    ): Response {
        $page = $this->page($request);
        $readModel = $readModelService->build($categorySlug, $page);

        if ($readModel === null) {
            abort(404);
        }

        $category = $readModel['category'];
        $articles = $readModel['articles'];
        $baseCanonical = route('public.news.categories.show', ['categorySlug' => $category['slug']]);
        $canonical = $page > 1 ? $baseCanonical.'?page='.$page : $baseCanonical;
        $siteName = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));
        $baseTitle = $category['seo_title']
            ?: $category['name'].' — aktualności | '.$siteName;
        $title = $page > 1 ? $baseTitle.' — strona '.$page : $baseTitle;
        $description = $category['seo_description']
            ?: $category['description']
            ?: 'Najnowsze informacje i materiały z kategorii '.$category['name'].' w serwisie '.$siteName.'.';
        $hasArticles = $articles->total() > 0;
        $robots = $hasArticles
            ? 'index,follow,max-image-preview:large'
            : 'noindex,follow';
        $breadcrumbs = [
            [
                'label' => 'Strona główna',
                'url' => route('home'),
            ],
            [
                'label' => 'Aktualności',
                'url' => route('public.news'),
            ],
            [
                'label' => $category['name'],
                'url' => $baseCanonical,
            ],
        ];

        $response = response()->view('newsroom.category', [
            'category' => $category,
            'articles' => $articles,
            'relatedCategories' => $readModel['related_categories'],
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'robots' => $robots,
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $schemaService->build(
                $category,
                $articles,
                $breadcrumbs,
                $canonical,
                $description,
            ),
        ]);

        if (! $hasArticles) {
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

        if ($rawPage === '' || preg_match('/\A[1-9][0-9]*\z/', $rawPage) !== 1) {
            abort(404);
        }

        return (int) $rawPage;
    }
}
