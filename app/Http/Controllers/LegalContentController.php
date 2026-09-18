<?php

namespace App\Http\Controllers;

use App\Support\LegalContentBreadcrumbs;
use App\Support\LegalContentCatalogService;
use App\Support\LegalContentSchemaService;
use App\Support\LegalContentSeoService;
use App\Support\MediaUrlResolver;
use App\Support\NewsroomSemanticLinkService;
use Illuminate\View\View;

class LegalContentController extends Controller
{
    public function __construct(
        private readonly NewsroomSemanticLinkService $newsroomSemanticLinks,
    ) {}

    public function index(
        LegalContentCatalogService $catalog,
        LegalContentSeoService $seo,
        LegalContentSchemaService $schema,
        LegalContentBreadcrumbs $breadcrumbs,
    ): View {
        $pages = $catalog->publishedPages();
        $breadcrumbItems = $breadcrumbs->hub();

        return view('legal-content.index', [
            'pages' => $pages,
            'meta' => $seo->hub($pages->count()),
            'breadcrumbs' => $breadcrumbItems,
            'structuredData' => $schema->hub($breadcrumbItems, $pages),
        ]);
    }

    public function show(
        string $slug,
        LegalContentCatalogService $catalog,
        LegalContentSeoService $seo,
        LegalContentSchemaService $schema,
        LegalContentBreadcrumbs $breadcrumbs,
        MediaUrlResolver $mediaUrlResolver,
    ): View {
        $page = $catalog->findPublishedPage($slug);

        abort_unless($page !== null, 404);

        $questionReferences = $catalog->questionReferencesForPage($page);
        $questionCards = $catalog->questionCardsForPage($page);
        $questionAssignmentLegalUnits = (bool) request()->user()?->isAdministrator()
            ? $catalog->questionAssignmentLegalUnits($page)
            : collect();
        $breadcrumbItems = $breadcrumbs->page($page);
        $articleImageConfig = config("content.legal_content.article_images.{$page->slug}");
        $articleImage = is_array($articleImageConfig)
            ? [
                ...$articleImageConfig,
                'url' => $mediaUrlResolver->resolveIfPublicAssetExists($articleImageConfig['path'] ?? null),
            ]
            : null;

        return view('legal-content.show', [
            'page' => $page,
            'articleImage' => $articleImage,
            'authorPhotoUrl' => $mediaUrlResolver->resolveIfPublicAssetExists($page->author?->photo_path),
            'reviewerPhotoUrl' => $mediaUrlResolver->resolveIfPublicAssetExists($page->reviewer?->photo_path),
            'questionReferences' => $questionReferences,
            'questionCards' => $questionCards,
            'questionAssignmentLegalUnits' => $questionAssignmentLegalUnits,
            'newsroomReverseArticles' => $this->newsroomSemanticLinks->forLegalUnits(
                $page->legalUnits()->pluck('legal_units.id'),
            ),
            'meta' => $seo->page($page),
            'breadcrumbs' => $breadcrumbItems,
            'structuredData' => $schema->page($page, $breadcrumbItems, $questionReferences),
        ]);
    }

    public function methodology(
        LegalContentSeoService $seo,
        LegalContentSchemaService $schema,
        LegalContentBreadcrumbs $breadcrumbs,
    ): View {
        $breadcrumbItems = $breadcrumbs->methodology();

        return view('legal-content.methodology', [
            'meta' => $seo->methodology(),
            'breadcrumbs' => $breadcrumbItems,
            'structuredData' => $schema->methodology($breadcrumbItems),
        ]);
    }
}
