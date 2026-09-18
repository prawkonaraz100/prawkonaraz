<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Models\ContentTopic;
use App\Models\LicenseCategory;
use App\Models\QuestionSeoTopic;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class SeoSitemapBuilder
{
    public const STATIC_SITEMAP_PATH = '/sitemaps/static.xml';

    public const ARTICLES_SITEMAP_PATH = '/sitemaps/articles.xml';

    public const NEWS_SITEMAP_PATH = '/sitemaps/news.xml';

    public const NEWS_SITEMAP_MAX_ENTRIES = 1000;

    public const NEWS_SITEMAP_WINDOW_DAYS = 2;

    public const LEGACY_QUESTIONS_SITEMAP_PATH = '/sitemaps/questions.xml';

    private const DEFAULT_ARTICLE_SITEMAP_SHARD_ID_SPAN = 10000;

    public const VIDEO_SITEMAP_PATH = '/sitemaps/videos.xml';

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
        protected LegalContentCatalogService $legalContentCatalogService,
        protected NewsroomPublicGate $newsroomPublicGate,
        protected SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  array<string, array{urls: list<array{loc:string,lastmod:string|null,images:array<int,string>}>, lastmod:string|null}>|null  $articleShards
     * @param  array<string, array{urls: list<array{loc:string,publication_name:string,language:string,publication_date:string,title:string}>, lastmod:string|null}>|null  $newsShards
     * @return list<array{loc: string, lastmod: string|null}>
     */
    public function sitemapIndexItems(?array $articleShards = null, ?array $newsShards = null): array
    {
        $articleShards ??= $this->articleSitemapShards();
        $newsShards ??= $this->newsSitemapShards();

        return array_values(array_filter([
            [
                'loc' => url(self::STATIC_SITEMAP_PATH),
                'lastmod' => $this->staticSitemapLastModified(),
            ],
            ...$this->articleSitemapIndexItems($articleShards),
            ...$this->newsSitemapIndexItems($newsShards),
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
            ...$this->newsroomHubUrls(),
        ];
    }

    /**
     * @return array<string, array{urls: list<array{loc:string,lastmod:string|null,images:array<int,string>}>, lastmod:string|null}>
     */
    public function articleSitemapShards(): array
    {
        if ($this->newsroomPublicGate->disabled()) {
            return [];
        }

        $shardIdSpan = max(
            1,
            (int) config('newsroom.article_sitemap_shard_id_span', self::DEFAULT_ARTICLE_SITEMAP_SHARD_ID_SPAN),
        );
        $reservedPaths = $this->reservedArticlePaths();

        $articles = $this->indexableNewsroomArticlesQuery()
            ->select([
                'id',
                'type',
                'slug',
                'first_published_at',
                'last_substantive_update_at',
                'public_state_changed_at',
            ])
            ->orderBy('id')
            ->get();

        if ($articles->isEmpty()) {
            return [];
        }

        $buckets = [];

        foreach ($articles as $article) {
            $path = $this->canonicalArticlePath($article);

            if ($path === null || $reservedPaths->has($path)) {
                continue;
            }

            $bucket = intdiv(max(1, (int) $article->getKey()) - 1, $shardIdSpan);
            $buckets[$bucket][] = [
                'loc' => url($path),
                'lastmod' => $this->articleLastModified($article),
                'images' => [],
            ];
        }

        if ($buckets === []) {
            return [];
        }

        ksort($buckets, SORT_NUMERIC);

        $maxArticleId = (int) (ContentArticle::query()->max('id') ?? 0);
        $useRangeShards = $maxArticleId > $shardIdSpan;
        $shards = [];

        foreach ($buckets as $bucket => $urls) {
            $relativePath = $useRangeShards
                ? $this->articleRangeShardPath((int) $bucket, $shardIdSpan)
                : ltrim(self::ARTICLES_SITEMAP_PATH, '/');

            $shards[$relativePath] = [
                'urls' => array_values($urls),
                'lastmod' => $this->maxLastModified(
                    collect($urls)->pluck('lastmod')->filter()->all(),
                ),
            ];
        }

        return $shards;
    }

    /**
     * @param  array<string, array{urls: list<array{loc:string,lastmod:string|null,images:array<int,string>}>, lastmod:string|null}>|null  $shards
     * @return list<array{loc:string,lastmod:string|null}>
     */
    public function articleSitemapIndexItems(?array $shards = null): array
    {
        $shards ??= $this->articleSitemapShards();

        return collect($shards)
            ->map(fn (array $shard, string $relativePath): array => [
                'loc' => url('/'.ltrim($relativePath, '/')),
                'lastmod' => $shard['lastmod'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{urls: list<array{loc:string,publication_name:string,language:string,publication_date:string,title:string}>, lastmod:string|null}>
     */
    public function newsSitemapShards(): array
    {
        if ($this->newsroomPublicGate->disabled()) {
            return [];
        }

        $maxEntries = max(
            1,
            min(
                self::NEWS_SITEMAP_MAX_ENTRIES,
                (int) config('newsroom.news_sitemap_max_entries', self::NEWS_SITEMAP_MAX_ENTRIES),
            ),
        );
        $reservedPaths = $this->reservedArticlePaths();
        $publicationName = $this->siteIdentitySchema->siteName();

        $articles = ContentArticle::query()
            ->activelyDistributed()
            ->indexable()
            ->where('type', ContentArticleType::News->value)
            ->where('workflow_status', ContentArticleWorkflowStatus::Published->value)
            ->where('first_published_at', '>=', now()->subDays(self::NEWS_SITEMAP_WINDOW_DAYS))
            ->whereHas('category', fn (Builder $query): Builder => $query->active())
            ->whereHas('author', fn (Builder $query): Builder => $query->published())
            ->select([
                'id',
                'type',
                'slug',
                'title',
                'first_published_at',
            ])
            ->orderBy('id')
            ->get();

        $urls = [];

        foreach ($articles as $article) {
            $path = $this->canonicalArticlePath($article);

            if ($path === null || $reservedPaths->has($path) || $article->first_published_at === null) {
                continue;
            }

            $urls[] = [
                'loc' => url($path),
                'publication_name' => $publicationName,
                'language' => 'pl',
                'publication_date' => $article->first_published_at->toIso8601String(),
                'title' => trim((string) $article->title),
            ];
        }

        if (count($urls) <= $maxEntries) {
            return [
                ltrim(self::NEWS_SITEMAP_PATH, '/') => [
                    'urls' => $urls,
                    'lastmod' => $this->newsSitemapLastModified($urls),
                ],
            ];
        }

        $buckets = [];

        foreach ($articles as $article) {
            $path = $this->canonicalArticlePath($article);

            if ($path === null || $reservedPaths->has($path) || $article->first_published_at === null) {
                continue;
            }

            $bucket = intdiv(max(1, (int) $article->getKey()) - 1, $maxEntries);
            $relativePath = $this->newsRangeShardPath($bucket, $maxEntries);
            $buckets[$relativePath][] = [
                'loc' => url($path),
                'publication_name' => $publicationName,
                'language' => 'pl',
                'publication_date' => $article->first_published_at->toIso8601String(),
                'title' => trim((string) $article->title),
            ];
        }

        ksort($buckets, SORT_NATURAL);

        return collect($buckets)
            ->map(fn (array $bucketUrls): array => [
                'urls' => array_values($bucketUrls),
                'lastmod' => $this->newsSitemapLastModified($bucketUrls),
            ])
            ->all();
    }

    /**
     * @param  array<string, array{urls: list<array{loc:string,publication_name:string,language:string,publication_date:string,title:string}>, lastmod:string|null}>|null  $shards
     * @return list<array{loc:string,lastmod:string|null}>
     */
    public function newsSitemapIndexItems(?array $shards = null): array
    {
        $shards ??= $this->newsSitemapShards();

        return collect($shards)
            ->map(fn (array $shard, string $relativePath): array => [
                'loc' => url('/'.ltrim($relativePath, '/')),
                'lastmod' => $shard['lastmod'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc:string,lastmod:string|null,images:array<int,string>}>
     */
    public function newsroomHubUrls(): array
    {
        if ($this->newsroomPublicGate->disabled()) {
            return [];
        }

        $urls = [
            [
                'loc' => route('public.news'),
                'lastmod' => $this->newsroomHomeLastModified(),
                'images' => [],
            ],
        ];

        $guideQuery = ContentArticle::query()
            ->activelyDistributed()
            ->whereIn('type', NewsroomRouteContract::GUIDE_TYPES);

        if ((clone $guideQuery)->exists()) {
            $urls[] = [
                'loc' => route('public.guides'),
                'lastmod' => $this->articleOutputLastModified($guideQuery),
                'images' => [],
            ];
        }

        $categories = ContentCategory::query()
            ->active()
            ->whereHas('articles', fn (Builder $query): Builder => $query
                ->activelyDistributed()
                ->whereIn('type', NewsroomRouteContract::NEWSROOM_TYPES))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $categoryArticles = ContentArticle::query()
                ->activelyDistributed()
                ->whereIn('type', NewsroomRouteContract::NEWSROOM_TYPES)
                ->where('category_id', $category->getKey());

            $urls[] = [
                'loc' => route('public.news.categories.show', ['categorySlug' => $category->slug]),
                'lastmod' => $this->maxLastModified([
                    $category->updated_at?->toIso8601String(),
                    $this->articleOutputLastModified($categoryArticles),
                ]),
                'images' => [],
            ];
        }

        $topics = ContentTopic::query()
            ->published()
            ->orderBy('slug')
            ->get();

        foreach ($topics as $topic) {
            $topicArticles = ContentArticle::query()
                ->activelyDistributed()
                ->indexable()
                ->whereIn('type', [
                    ...NewsroomRouteContract::NEWSROOM_TYPES,
                    ...NewsroomRouteContract::GUIDE_TYPES,
                ])
                ->whereHas('topics', fn (Builder $query): Builder => $query->whereKey($topic->getKey()));

            $urls[] = [
                'loc' => route('public.news.topics.show', ['topicSlug' => $topic->slug]),
                'lastmod' => $this->maxLastModified([
                    $topic->updated_at?->toIso8601String(),
                    $topic->published_at?->toIso8601String(),
                    $this->articleOutputLastModified($topicArticles),
                ]),
                'images' => [],
            ];
        }

        return $urls;
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
        $newsroomEnabled = $this->newsroomPublicGate->enabled();

        return ContentAuthor::query()
            ->published()
            ->where(function ($query) use ($newsroomEnabled): void {
                $query
                    ->whereHas('trafficSigns', fn ($query) => $query->published()->whereHas('category', fn ($query) => $query->published()))
                    ->orWhereHas('authoredLegalContentPages', fn ($query) => $query->published())
                    ->orWhereHas('reviewedLegalContentPages', fn ($query) => $query->published());

                if ($newsroomEnabled) {
                    $query->orWhereHas(
                        'authoredContentArticles',
                        fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
                    );
                }
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
            ->when($newsroomEnabled, fn ($query) => $query->withMax([
                'authoredContentArticles as latest_indexable_content_article_public_state_changed_at' => fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
            ], 'public_state_changed_at'))
            ->orderBy('name')
            ->get()
            ->map(function (ContentAuthor $author) use ($newsroomEnabled): array {
                $lastModified = [
                    $author->updated_at?->toIso8601String(),
                    $author->latest_published_sign_updated_at,
                    $author->latest_authored_legal_content_updated_at,
                    $author->latest_reviewed_legal_content_updated_at,
                ];

                if ($newsroomEnabled) {
                    $lastModified[] = $author->latest_indexable_content_article_public_state_changed_at;
                }

                return [
                    'loc' => route('content-authors.show', $author->slug),
                    'lastmod' => $this->maxLastModified($lastModified),
                    'images' => [],
                ];
            })
            ->values()
            ->all();
    }

    protected function reservedArticlePaths(): Collection
    {
        return ContentArticleRedirect::query()
            ->pluck('from_path')
            ->mapWithKeys(fn (string $path): array => ['/'.ltrim($path, '/') => true]);
    }

    protected function canonicalArticlePath(ContentArticle $article): ?string
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

    /**
     * @param  list<array{publication_date:string}>  $urls
     */
    protected function newsSitemapLastModified(array $urls): ?string
    {
        return $this->maxLastModified(
            collect($urls)->pluck('publication_date')->filter()->all(),
        );
    }

    protected function newsRangeShardPath(int $bucket, int $span): string
    {
        $start = ($bucket * $span) + 1;
        $end = ($bucket + 1) * $span;

        return sprintf('sitemaps/news-%06d-%06d.xml', $start, $end);
    }

    protected function indexableNewsroomArticlesQuery(): Builder
    {
        return ContentArticle::query()
            ->indexable()
            ->whereIn('type', [
                ...NewsroomRouteContract::NEWSROOM_TYPES,
                ...NewsroomRouteContract::GUIDE_TYPES,
            ])
            ->whereHas('category', fn (Builder $query): Builder => $query->active())
            ->whereHas('author', fn (Builder $query): Builder => $query->published());
    }

    protected function articleLastModified(ContentArticle $article): ?string
    {
        return $this->maxLastModified([
            $article->first_published_at?->toIso8601String(),
            $article->last_substantive_update_at?->toIso8601String(),
            $article->public_state_changed_at?->toIso8601String(),
        ]);
    }

    protected function articleOutputLastModified(Builder $query): ?string
    {
        return $this->maxLastModified([
            (clone $query)->max('first_published_at'),
            (clone $query)->max('last_substantive_update_at'),
            (clone $query)->max('public_state_changed_at'),
        ]);
    }

    protected function newsroomHomeLastModified(): ?string
    {
        $articleLastModified = $this->articleOutputLastModified(
            ContentArticle::query()->activelyDistributed(),
        );

        $categoryLastModified = $this->maxLastModified(
            ContentCategory::query()
                ->active()
                ->pluck('updated_at')
                ->filter()
                ->map(fn (mixed $value): string => Carbon::parse($value)->toIso8601String())
                ->all(),
        );

        $placementTimestamps = ContentHomePlacement::query()
            ->get(['updated_at', 'starts_at', 'ends_at'])
            ->flatMap(function (ContentHomePlacement $placement): array {
                $values = [
                    $placement->updated_at?->toIso8601String(),
                ];

                if ($placement->starts_at !== null && $placement->starts_at->lte(now())) {
                    $values[] = $placement->starts_at->toIso8601String();
                }

                if ($placement->ends_at !== null && $placement->ends_at->lte(now())) {
                    $values[] = $placement->ends_at->toIso8601String();
                }

                return $values;
            })
            ->filter()
            ->all();

        return $this->maxLastModified([
            $articleLastModified,
            $categoryLastModified,
            $this->maxLastModified($placementTimestamps),
        ]);
    }

    protected function articleRangeShardPath(int $bucket, int $span): string
    {
        $start = ($bucket * $span) + 1;
        $end = ($bucket + 1) * $span;

        return sprintf('sitemaps/articles-%06d-%06d.xml', $start, $end);
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
        $newsroomEnabled = $this->newsroomPublicGate->enabled();

        return $this->maxLastModified(
            ContentAuthor::query()
                ->published()
                ->where(function ($query) use ($newsroomEnabled): void {
                    $query
                        ->whereHas('trafficSigns', fn ($query) => $query->published())
                        ->orWhereHas('authoredLegalContentPages', fn ($query) => $query->published())
                        ->orWhereHas('reviewedLegalContentPages', fn ($query) => $query->published());

                    if ($newsroomEnabled) {
                        $query->orWhereHas(
                            'authoredContentArticles',
                            fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
                        );
                    }
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
                ->when($newsroomEnabled, fn ($query) => $query->withMax([
                    'authoredContentArticles as latest_indexable_content_article_public_state_changed_at' => fn ($query) => $query->indexable()->whereHas('category', fn ($query) => $query->active()),
                ], 'public_state_changed_at'))
                ->get()
                ->flatMap(function (ContentAuthor $author) use ($newsroomEnabled): array {
                    $lastModified = [
                        $author->updated_at?->toIso8601String(),
                        $author->latest_published_sign_updated_at,
                        $author->latest_authored_legal_content_updated_at,
                        $author->latest_reviewed_legal_content_updated_at,
                    ];

                    if ($newsroomEnabled) {
                        $lastModified[] = $author->latest_indexable_content_article_public_state_changed_at;
                    }

                    return $lastModified;
                })
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
