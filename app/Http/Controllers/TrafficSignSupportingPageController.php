<?php

namespace App\Http\Controllers;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Support\MediaUrlResolver;
use App\Support\TrafficSignAuthorProfile;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use App\Support\TrafficSignSupportingPageCatalog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrafficSignSupportingPageController extends Controller
{
    public function __invoke(
        string $supportingPageSlug,
        TrafficSignSupportingPageCatalog $trafficSignSupportingPageCatalog,
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        MediaUrlResolver $mediaUrlResolver,
    ): View {
        $page = $trafficSignSupportingPageCatalog->find($supportingPageSlug);

        abort_if($page === null, 404);

        $relatedSignOrder = array_flip($page['related_sign_slugs'] ?? []);

        $relatedSigns = TrafficSign::query()
            ->published()
            ->with([
                'author:id,name,slug',
                'category:id,name,slug',
            ])
            ->whereHas('author', fn ($query) => $query->published())
            ->whereHas('category', fn ($query) => $query->published())
            ->whereIn('slug', $page['related_sign_slugs'] ?? [])
            ->get()
            ->sortBy(fn (TrafficSign $sign): int => $relatedSignOrder[$sign->slug] ?? PHP_INT_MAX)
            ->values();

        $author = ContentAuthor::query()
            ->published()
            ->where('slug', TrafficSignAuthorProfile::SLUG)
            ->first();

        $breadcrumbs = $trafficSignBreadcrumbs->supportingPage($page['title'], $page['slug']);

        return view('traffic-signs.supporting.show', [
            'page' => $page,
            'author' => $author,
            'relatedSigns' => $relatedSigns,
            'relatedSignCards' => $this->relatedSignCards($relatedSigns, $mediaUrlResolver),
            'meta' => $trafficSignSeoService->supportingPage($page, $author, $relatedSigns->first()),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->supportingPage($page, $breadcrumbs, $author, $relatedSigns),
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
            'category_name' => $sign->category?->name,
            'category_url' => $sign->category ? route('traffic-signs.categories.show', $sign->category->slug, absolute: false) : null,
            'updated_at' => $sign->updated_at?->format('d.m.Y'),
        ]);
    }
}
