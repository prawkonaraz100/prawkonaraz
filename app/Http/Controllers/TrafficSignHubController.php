<?php

namespace App\Http\Controllers;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Support\MediaUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrafficSignHubController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        MediaUrlResolver $mediaUrlResolver,
    ): View {
        $categories = TrafficSignCategory::query()
            ->published()
            ->withCount([
                'trafficSigns as published_traffic_signs_count' => fn ($query) => $query->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categoryIconSigns = TrafficSign::query()
            ->published()
            ->select(['id', 'traffic_sign_category_id', 'code', 'slug', 'name', 'image_path', 'image_alt', 'sort_order'])
            ->whereIn('traffic_sign_category_id', $categories->pluck('id'))
            ->whereHas('author', fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get()
            ->unique('traffic_sign_category_id')
            ->keyBy('traffic_sign_category_id');

        $featuredSigns = TrafficSign::query()
            ->published()
            ->with([
                'author:id,name,slug',
                'category:id,name,slug',
            ])
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->orderBy('updated_at', 'desc')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $breadcrumbs = $trafficSignBreadcrumbs->hub();

        return view('traffic-signs.index', [
            'categoryColumns' => $this->categoryColumns($categories, $categoryIconSigns, $mediaUrlResolver),
            'featuredSigns' => $featuredSigns->map(fn (TrafficSign $sign): array => $this->signCard($sign, $mediaUrlResolver)),
            'meta' => $trafficSignSeoService->hub(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->hub($breadcrumbs, $categories, $featuredSigns),
        ]);
    }

    private function categoryColumns(Collection $categories, Collection $categoryIconSigns, MediaUrlResolver $mediaUrlResolver): Collection
    {
        $cards = $categories->map(function (TrafficSignCategory $category) use ($categoryIconSigns, $mediaUrlResolver): array {
            /** @var TrafficSign|null $iconSign */
            $iconSign = $categoryIconSigns->get($category->getKey());

            return [
                'name' => $category->name,
                'description' => $category->description,
                'url' => route('traffic-signs.categories.show', $category->slug, absolute: false),
                'count' => (int) $category->published_traffic_signs_count,
                'icon_url' => $iconSign
                    ? ($this->transparentSignCutoutUrl($iconSign, $mediaUrlResolver) ?: $mediaUrlResolver->resolveIfPublicAssetExists($iconSign->image_path))
                    : null,
                'icon_alt' => $iconSign?->image_alt ?: $category->name,
            ];
        });

        return $cards->chunk(max(1, (int) ceil($cards->count() / 2)))->values();
    }

    private function transparentSignCutoutUrl(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): ?string
    {
        $path = "traffic-signs/sign-cutouts/{$sign->slug}.png";

        if (! is_file(public_path($path))) {
            return null;
        }

        return $mediaUrlResolver->resolve($path);
    }

    private function signCard(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'code' => $sign->publicCode(),
            'name' => $sign->name,
            'slug' => $sign->slug,
            'title' => $sign->publicTitle(),
            'intro' => $sign->intro_definition,
            'url' => route('traffic-signs.show', $sign->slug, absolute: false),
            'image_url' => $mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
            'image_alt' => $sign->publicImageAlt(),
            'category_name' => $sign->category?->name,
            'category_url' => $sign->category ? route('traffic-signs.categories.show', $sign->category->slug, absolute: false) : null,
            'author_name' => $sign->author?->name,
            'author_url' => $sign->author ? route('content-authors.show', $sign->author->slug, absolute: false) : null,
            'updated_at' => $sign->updated_at?->format('d.m.Y'),
        ];
    }
}
