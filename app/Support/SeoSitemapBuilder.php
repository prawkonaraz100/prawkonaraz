<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\LicenseCategory;
use App\Models\QuestionSeoTopic;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SeoSitemapBuilder
{
    public const STATIC_SITEMAP_PATH = '/sitemaps/static.xml';

    public const LEGACY_QUESTIONS_SITEMAP_PATH = '/sitemaps/questions.xml';

    public const VIDEO_SITEMAP_PATH = '/sitemaps/videos.xml';

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
        protected LegalContentCatalogService $legalContentCatalogService,
    ) {}

    /**
     * @return list<array{loc: string, lastmod: string|null}>
     */
    public function sitemapIndexItems(): array
    {
        return array_values(array_filter([
            [
                'loc' => url(self::STATIC_SITEMAP_PATH),
                'lastmod' => $this->staticSitemapLastModified(),
            ],
            [
                'loc' => route('sitemap.questions.hub'),
                'lastmod' => $this->publicQuestionCatalogService->latestQuestionLastModified(),
            ],
            [
                'loc' => route('sitemap.questions.categories'),
                'lastmod' => $this->publicQuestionCatalogService->latestQuestionLastModified(),
            ],
            ...(QuestionSeoTopic::query()->indexable()->exists() ? [[
                'loc' => route('sitemap.questions.topics'),
                'lastmod' => $this->maxLastModified(QuestionSeoTopic::query()->indexable()->max('updated_at')),
            ]] : []),
            ...$this->questionSitemapIndexItems(),
            [
                'loc' => route('sitemap.videos'),
                'lastmod' => $this->publicQuestionCatalogService->latestQuestionVideoLastModified(),
            ],
            [
                'loc' => route('sitemap.signs'),
                'lastmod' => $this->maxLastModified($this->publishedSignsQuery()->max('updated_at')),
            ],
            [
                'loc' => route('sitemap.supporting-pages'),
                'lastmod' => $this->supportingPagesLastModified(),
            ],
            [
                'loc' => route('sitemap.categories'),
                'lastmod' => $this->trafficSignCategoriesLastModified(),
            ],
            [
                'loc' => route('sitemap.authors'),
                'lastmod' => $this->authorsLastModified(),
            ],
            [
                'loc' => route('sitemap.legal-content'),
                'lastmod' => $this->legalContentCatalogService->latestPublishedPageLastModified(),
            ],
        ], fn (array $item): bool => filled($item['loc'] ?? null)));
    }

    /**
     * @return list<array{loc: string, lastmod: string|null}>
     */
    public function questionSitemapIndexItems(): array
    {
        $latestByCategorySlug = $this->publicQuestionCatalogService
            ->canonicalQuestionSitemapRowsByCategory()
            ->map(fn (Collection $rows): ?string => $this->maxLastModified(
                $rows->pluck('lastmod')->filter()->all(),
            ));

        return $this->publicQuestionCatalogService->visibleCategories()
            ->map(function (LicenseCategory $category) use ($latestByCategorySlug): ?array {
                $lastmod = $latestByCategorySlug->get((string) $category->slug);

                if ($lastmod === null) {
                    return null;
                }

                return [
                    'loc' => $this->questionSitemapUrlForCategory($category),
                    'lastmod' => $lastmod,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function questionSitemapUrlForCategory(LicenseCategory $category): string
    {
        return url($this->questionSitemapPathForCategory($category));
    }

    public function questionSitemapPathForCategory(LicenseCategory $category): string
    {
        return '/sitemaps/questions-'.$category->slug.'.xml';
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function staticUrls(): array
    {
        return [
            ['loc' => route('home'), 'lastmod' => null, 'images' => []],
            ['loc' => route('public.tests'), 'lastmod' => null, 'images' => []],
            ['loc' => route('public.pricing'), 'lastmod' => null, 'images' => []],
            ['loc' => route('legal.terms'), 'lastmod' => null, 'images' => []],
            ['loc' => route('legal.privacy'), 'lastmod' => null, 'images' => []],
            ['loc' => route('public.hardest-questions.index'), 'lastmod' => $this->publicQuestionCatalogService->latestQuestionLastModified(), 'images' => []],
            ['loc' => route('traffic-signs.index'), 'lastmod' => $this->maxLastModified($this->publishedSignsQuery()->max('updated_at')), 'images' => []],
            ['loc' => route('public.regulations'), 'lastmod' => $this->legalContentCatalogService->latestPublishedPageLastModified(), 'images' => []],
            ['loc' => route('public.regulations.methodology'), 'lastmod' => $this->legalContentCatalogService->latestPublishedPageLastModified(), 'images' => []],
            ['loc' => route('public.partners'), 'lastmod' => null, 'images' => []],
            ['loc' => route('about.organization'), 'lastmod' => null, 'images' => []],
            ['loc' => route('about.how-it-works'), 'lastmod' => null, 'images' => []],
            ['loc' => route('about.methodology'), 'lastmod' => null, 'images' => []],
            ['loc' => route('about.contact'), 'lastmod' => null, 'images' => []],
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function legalContentUrls(): array
    {
        return $this->legalContentCatalogService->sitemapUrls();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function trafficSignUrls(): array
    {
        return $this->publishedSignsQuery()
            ->with([
                'author:id,slug',
                'category:id,slug,name',
            ])
            ->get()
            ->map(function (TrafficSign $sign): array {
                return [
                    'loc' => route('traffic-signs.show', $sign->slug),
                    'lastmod' => $sign->updated_at->toIso8601String(),
                    'images' => array_values(array_unique(array_filter([
                        $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
                        $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->og_image_path),
                    ]))),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function trafficSignCategoryUrls(): array
    {
        return TrafficSignCategory::query()
            ->published()
            ->whereHas('trafficSigns', fn ($query) => $query->published()->whereHas('author', fn ($query) => $query->published()))
            ->withMax([
                'trafficSigns as latest_published_sign_updated_at' => fn ($query) => $query->published(),
            ], 'updated_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (TrafficSignCategory $category): array {
                return [
                    'loc' => route('traffic-signs.categories.show', $category->slug),
                    'lastmod' => $this->maxLastModified([
                        $category->updated_at?->toIso8601String(),
                        $category->latest_published_sign_updated_at,
                    ]),
                    'images' => [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function supportingPageUrls(): array
    {
        return collect($this->trafficSignSupportingPageCatalog->all())
            ->map(fn (array $page): array => [
                'loc' => route('traffic-signs.supporting.show', $page['slug']),
                'lastmod' => $this->maxLastModified($page['updated_at'] ?? null),
                'images' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function authorUrls(): array
    {
        return ContentAuthor::query()
            ->published()
            ->where(function ($query): void {
                $query
                    ->whereHas('trafficSigns', fn ($query) => $query->published()->whereHas('category', fn ($query) => $query->published()))
                    ->orWhereHas('authoredLegalContentPages', fn ($query) => $query->published())
                    ->orWhereHas('reviewedLegalContentPages', fn ($query) => $query->published())
                    ->orWhereHas('authoredContentArticles', fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()));
            })
            ->withMax([
                'trafficSigns as latest_published_sign_updated_at' => fn ($query) => $query->published(),
            ], 'updated_at')
            ->withMax([
                'authoredLegalContentPages as latest_authored_legal_content_updated_at' => fn ($query) => $query->published(),
            ], 'updated_at')
            ->withMax([
                'reviewedLegalContentPages as latest_reviewed_legal_content_updated_at' => fn ($query) => $query->published(),
            ], 'updated_at')
            ->withMax([
                'authoredContentArticles as latest_indexable_content_article_public_state_changed_at' => fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
            ], 'public_state_changed_at')
            ->orderBy('name')
            ->get()
            ->map(function (ContentAuthor $author): array {
                return [
                    'loc' => route('content-authors.show', $author->slug),
                    'lastmod' => $this->maxLastModified([
                        $author->updated_at?->toIso8601String(),
                        $author->latest_published_sign_updated_at,
                        $author->latest_authored_legal_content_updated_at,
                        $author->latest_reviewed_legal_content_updated_at,
                        $author->latest_indexable_content_article_public_state_changed_at,
                    ]),
                    'images' => [],
                ];
            })
            ->values()
            ->all();
    }

    protected function publishedSignsQuery()
    {
        return TrafficSign::query()
            ->published()
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->orderBy('updated_at', 'desc')
            ->orderBy('name');
    }

    protected function staticSitemapLastModified(): ?string
    {
        $staticUrlLastModified = $this->maxLastModified(
            collect($this->staticUrls())
                ->pluck('lastmod')
                ->filter()
                ->all(),
        );

        return $this->maxLastModified([
            $staticUrlLastModified,
            $this->staticContentFileLastModified(),
        ]);
    }

    protected function staticContentFileLastModified(): ?string
    {
        $paths = [
            base_path('routes/web.php'),
            config_path('content.php'),
            resource_path('views'),
            resource_path('js/Pages/Public'),
            public_path('robots.txt'),
            public_path('llms.txt'),
            public_path('llms-full.txt'),
            public_path('ads.txt'),
        ];

        $timestamps = collect($paths)
            ->flatMap(function (string $path): array {
                if (File::isFile($path)) {
                    $modifiedAt = @filemtime($path);

                    return is_int($modifiedAt) ? [$modifiedAt] : [];
                }

                if (! File::isDirectory($path)) {
                    return [];
                }

                return collect(File::allFiles($path))
                    ->map(fn ($file): int => $file->getMTime())
                    ->all();
            })
            ->filter(fn (int $timestamp): bool => $timestamp > 0);

        $modifiedAt = $timestamps->max();

        return is_int($modifiedAt)
            ? Carbon::createFromTimestamp($modifiedAt)->toIso8601String()
            : null;
    }

    protected function supportingPagesLastModified(): ?string
    {
        return $this->maxLastModified(
            collect($this->trafficSignSupportingPageCatalog->all())
                ->pluck('updated_at')
                ->filter()
                ->all(),
        );
    }

    protected function trafficSignCategoriesLastModified(): ?string
    {
        return $this->maxLastModified(
            TrafficSignCategory::query()
                ->published()
                ->withMax([
                    'trafficSigns as latest_published_sign_updated_at' => fn ($query) => $query->published(),
                ], 'updated_at')
                ->get()
                ->flatMap(fn (TrafficSignCategory $category): array => [
                    $category->updated_at?->toIso8601String(),
                    $category->latest_published_sign_updated_at,
                ])
                ->filter()
                ->max(),
        );
    }

    protected function authorsLastModified(): ?string
    {
        return $this->maxLastModified(
            ContentAuthor::query()
                ->published()
                ->where(function ($query): void {
                    $query
                        ->whereHas('trafficSigns', fn ($query) => $query->published())
                        ->orWhereHas('authoredLegalContentPages', fn ($query) => $query->published())
                        ->orWhereHas('reviewedLegalContentPages', fn ($query) => $query->published())
                        ->orWhereHas('authoredContentArticles', fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()));
                })
                ->withMax([
                    'trafficSigns as latest_published_sign_updated_at' => fn ($query) => $query->published(),
                ], 'updated_at')
                ->withMax([
                    'authoredLegalContentPages as latest_authored_legal_content_updated_at' => fn ($query) => $query->published(),
                ], 'updated_at')
                ->withMax([
                    'reviewedLegalContentPages as latest_reviewed_legal_content_updated_at' => fn ($query) => $query->published(),
                ], 'updated_at')
                ->withMax([
                    'authoredContentArticles as latest_indexable_content_article_public_state_changed_at' => fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
                ], 'public_state_changed_at')
                ->get()
                ->flatMap(fn (ContentAuthor $author): array => [
                    $author->updated_at?->toIso8601String(),
                    $author->latest_published_sign_updated_at,
                    $author->latest_authored_legal_content_updated_at,
                    $author->latest_reviewed_legal_content_updated_at,
                    $author->latest_indexable_content_article_public_state_changed_at,
                ])
                ->filter()
                ->max(),
        );
    }

    /**
     * @param  iterable<string|null>|string|null  $values
     */
    protected function maxLastModified(iterable|string|null $values): ?string
    {
        if (is_string($values)) {
            return Carbon::parse($values)->toIso8601String();
        }

        if ($values === null) {
            return null;
        }

        $max = Collection::make($values)
            ->filter()
            ->map(fn (string $value): Carbon => Carbon::parse($value))
            ->sortDesc()
            ->first();

        return $max?->toIso8601String();
    }
}
