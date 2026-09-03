<?php

namespace App\Http\Controllers;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Support\MediaUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignRedirectPolicy;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use App\Support\TrafficSignSupportingPageCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrafficSignCategoryController extends Controller
{
    public function __invoke(
        string $categorySlug,
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        TrafficSignRedirectPolicy $trafficSignRedirectPolicy,
        TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
        MediaUrlResolver $mediaUrlResolver,
    ): View|RedirectResponse {
        $category = TrafficSignCategory::query()
            ->published()
            ->where('slug', $categorySlug)
            ->first();

        if (! $category instanceof TrafficSignCategory) {
            $resolution = $trafficSignRedirectPolicy->resolve('categories', $categorySlug);

            if ($resolution['type'] === 'redirect') {
                return redirect()->route('traffic-signs.categories.show', $resolution['slug'], 301);
            }

            abort_if($resolution['type'] === 'gone', 410);
            abort(404);
        }

        $signs = $category->trafficSigns()
            ->published()
            ->with([
                'author:id,name,slug',
                'category:id,name,slug',
            ])
            ->whereHas('author', fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $supportingPages = $trafficSignSupportingPageCatalog->forCategorySlug($category->slug);
        $supportingSignImages = $this->supportingSignImages($supportingPages, $mediaUrlResolver);
        $heroSign = $this->heroSign($category->slug, $signs, $mediaUrlResolver);
        $breadcrumbs = $trafficSignBreadcrumbs->category($category);

        return view('traffic-signs.category', [
            'category' => $category,
            'categoryHeroSign' => $heroSign,
            'signs' => $signs->map(fn (TrafficSign $sign): array => $this->signCard($sign, $mediaUrlResolver)),
            'supportingPages' => $this->supportingPageCards($supportingPages, $supportingSignImages),
            'meta' => $trafficSignSeoService->category($category),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->category($category, $breadcrumbs, $signs),
        ]);
    }

    private function heroSign(string $categorySlug, Collection $signs, MediaUrlResolver $mediaUrlResolver): ?array
    {
        $preferredCode = match ($categorySlug) {
            'znaki-ostrzegawcze' => 'A-30',
            'znaki-zakazu' => 'B-20',
            'znaki-nakazu' => 'C-5',
            'znaki-informacyjne' => 'D-6',
            default => null,
        };

        $sign = $preferredCode
            ? $signs->firstWhere('code', $preferredCode)
            : null;

        $sign ??= $signs->first();

        if (! $sign instanceof TrafficSign) {
            return null;
        }

        $card = $this->signCard($sign, $mediaUrlResolver);
        $card['hero_image_url'] = $this->transparentHeroImageUrl($sign, $mediaUrlResolver);

        return $card;
    }

    private function transparentHeroImageUrl(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): ?string
    {
        $path = "traffic-signs/category-heroes/{$sign->slug}.png";

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

    private function signCard(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'code' => $sign->publicCode(),
            'name' => $sign->name,
            'title' => $sign->publicTitle(),
            'intro' => $sign->intro_definition,
            'url' => route('traffic-signs.show', $sign->slug, absolute: false),
            'image_url' => $mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
            'cutout_image_url' => $this->transparentSignCutoutUrl($sign, $mediaUrlResolver),
            'image_alt' => $sign->publicImageAlt(),
            'author_name' => $sign->author?->name,
            'author_url' => $sign->author ? route('content-authors.show', $sign->author->slug, absolute: false) : null,
            'updated_at' => $sign->updated_at?->format('d.m.Y'),
        ];
    }
}
