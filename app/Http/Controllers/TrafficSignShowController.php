<?php

namespace App\Http\Controllers;

use App\Models\TrafficSign;
use App\Support\MediaUrlResolver;
use App\Support\NewsroomSemanticLinkService;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignRedirectPolicy;
use App\Support\TrafficSignRelatedContentService;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use App\Support\TrafficSignSupportingPageCatalog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrafficSignShowController extends Controller
{
    public function __construct(
        private readonly NewsroomSemanticLinkService $newsroomSemanticLinks,
    ) {}

    public function __invoke(
        string $signSlug,
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        TrafficSignRedirectPolicy $trafficSignRedirectPolicy,
        TrafficSignRelatedContentService $trafficSignRelatedContentService,
        TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
        MediaUrlResolver $mediaUrlResolver,
    ): View|RedirectResponse {
        $sign = TrafficSign::query()
            ->published()
            ->with([
                'author',
                'category',
            ])
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->where('slug', $signSlug)
            ->first();

        if (! $sign instanceof TrafficSign) {
            $resolution = $trafficSignRedirectPolicy->resolve('signs', $signSlug);

            if ($resolution['type'] === 'redirect') {
                return redirect()->route('traffic-signs.show', $resolution['slug'], 301);
            }

            abort_if($resolution['type'] === 'gone', 410);
            abort(404);
        }

        $supportingPages = $trafficSignSupportingPageCatalog->forSign($sign);
        $relatedSigns = $trafficSignRelatedContentService->relatedSigns($sign);
        $breadcrumbs = $trafficSignBreadcrumbs->sign($sign);

        return view('traffic-signs.show', [
            'sign' => $sign,
            'signImageUrl' => $this->transparentSignCutoutUrl($sign, $mediaUrlResolver)
                ?: $mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
            'relatedSignCards' => $this->relatedSignCards($relatedSigns, $mediaUrlResolver),
            'newsroomReverseArticles' => $this->newsroomSemanticLinks->forTrafficSign((int) $sign->getKey()),
            'supportingPages' => $this->supportingPageCards(
                $supportingPages,
                $this->supportingSignImages($supportingPages, $mediaUrlResolver),
            ),
            'meta' => $trafficSignSeoService->sign($sign),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->sign($sign, $breadcrumbs),
        ]);
    }

    private function transparentSignCutoutUrl(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): ?string
    {
        $path = "traffic-signs/sign-cutouts/{$sign->slug}.png";

        if (! is_file(public_path($path))) {
            return null;
        }

        return $mediaUrlResolver->resolve($path);
    }

    /**
     * @param  list<array<string, mixed>>  $supportingPages
     * @return array<string, array{image_url: string|null, image_alt: string}>
     */
    private function supportingSignImages(array $supportingPages, MediaUrlResolver $mediaUrlResolver): array
    {
        $slugs = collect($supportingPages)
            ->flatMap(fn (array $page): array => $page['related_sign_slugs'] ?? [])
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return [];
        }

        return TrafficSign::query()
            ->published()
            ->select(['slug', 'code', 'name', 'image_path', 'image_alt'])
            ->whereIn('slug', $slugs->all())
            ->get()
            ->mapWithKeys(fn (TrafficSign $sign): array => [
                $sign->slug => [
                    'image_url' => $this->transparentSignCutoutUrl($sign, $mediaUrlResolver)
                        ?: $mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
                    'image_alt' => $sign->publicImageAlt(),
                ],
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $supportingPages
     * @param  array<string, array{image_url: string|null, image_alt: string}>  $supportingSignImages
     * @return Collection<int, array<string, mixed>>
     */
    private function supportingPageCards(array $supportingPages, array $supportingSignImages): Collection
    {
        return collect($supportingPages)->map(function (array $page) use ($supportingSignImages): array {
            $thumbs = collect($page['related_sign_slugs'] ?? [])
                ->map(fn (string $slug): ?array => $supportingSignImages[$slug] ?? null)
                ->filter()
                ->take(3)
                ->values()
                ->all();

            return [
                'title' => $page['title'],
                'description' => $page['description'],
                'url' => $page['url'],
                'thumbs' => $thumbs,
            ];
        });
    }

    /**
     * @param  EloquentCollection<int, TrafficSign>  $relatedSigns
     * @return Collection<int, array<string, mixed>>
     */
    private function relatedSignCards(EloquentCollection $relatedSigns, MediaUrlResolver $mediaUrlResolver): Collection
    {
        return $relatedSigns->map(fn (TrafficSign $sign): array => [
            'code' => $sign->publicCode(),
            'name' => $sign->name,
            'title' => $sign->publicTitle(),
            'intro' => $sign->intro_definition,
            'url' => route('traffic-signs.show', $sign->slug, absolute: false),
            'image_url' => $this->transparentSignCutoutUrl($sign, $mediaUrlResolver)
                ?: $mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
            'image_alt' => $sign->publicImageAlt(),
            'updated_at' => $sign->updated_at?->format('d.m.Y'),
        ]);
    }
}
