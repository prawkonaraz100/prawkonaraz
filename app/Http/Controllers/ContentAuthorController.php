<?php

namespace App\Http\Controllers;

use App\Models\ContentAuthor;
use App\Models\LegalContentPage;
use App\Models\TrafficSign;
use App\Support\MediaUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignRedirectPolicy;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentAuthorController extends Controller
{
    public function __invoke(
        string $authorSlug,
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        TrafficSignRedirectPolicy $trafficSignRedirectPolicy,
        MediaUrlResolver $mediaUrlResolver,
    ): View|RedirectResponse {
        $author = ContentAuthor::query()
            ->published()
            ->where('slug', $authorSlug)
            ->first();

        if (! $author instanceof ContentAuthor) {
            $resolution = $trafficSignRedirectPolicy->resolve('authors', $authorSlug);

            if ($resolution['type'] === 'redirect') {
                return redirect()->route('content-authors.show', $resolution['slug'], 301);
            }

            abort_if($resolution['type'] === 'gone', 410);
            abort(404);
        }

        $signs = $author->trafficSigns()
            ->published()
            ->with('category:id,name,slug')
            ->whereHas('category', fn ($query) => $query->published())
            ->orderBy('updated_at', 'desc')
            ->orderBy('name')
            ->get();
        $legalPages = LegalContentPage::query()
            ->published()
            ->with('topic:id,title,slug')
            ->where(function ($query) use ($author): void {
                $query
                    ->where('author_id', $author->getKey())
                    ->orWhere('reviewer_id', $author->getKey());
            })
            ->orderBy('updated_at', 'desc')
            ->orderBy('title')
            ->get();

        $breadcrumbs = $trafficSignBreadcrumbs->author($author);
        $publicationCards = $signs
            ->map(fn (TrafficSign $sign): array => $this->publicationCard($sign, $mediaUrlResolver))
            ->concat($legalPages->map(fn (LegalContentPage $page): array => $this->legalPublicationCard($page)))
            ->sortByDesc('updated_at_sort')
            ->values();

        return view('authors.show', [
            'author' => $author,
            'signs' => $signs,
            'publicationCards' => $publicationCards,
            'authorPhotoUrl' => $mediaUrlResolver->resolve($author->photo_path, 'public'),
            'meta' => $trafficSignSeoService->author($author),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->author($author, $breadcrumbs, $signs),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicationCard(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
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
            'updated_at_sort' => $sign->updated_at?->timestamp ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legalPublicationCard(LegalContentPage $page): array
    {
        return [
            'code' => 'Prawo',
            'title' => $page->title,
            'intro' => $page->intro,
            'url' => route('public.regulations.show', $page->slug, absolute: false),
            'image_url' => null,
            'image_alt' => null,
            'category_name' => $page->topic?->title ?? 'Przepisy',
            'category_url' => route('public.regulations', absolute: false),
            'updated_at' => ($page->last_reviewed_at ?: $page->updated_at)?->format('d.m.Y'),
            'updated_at_sort' => ($page->last_reviewed_at ?: $page->updated_at)?->timestamp ?? 0,
        ];
    }

    private function transparentSignCutoutUrl(TrafficSign $sign, MediaUrlResolver $mediaUrlResolver): ?string
    {
        $path = "traffic-signs/sign-cutouts/{$sign->slug}.png";
        $absolutePath = public_path($path);

        if (! is_file($absolutePath) || @getimagesize($absolutePath) === false) {
            return null;
        }

        return $mediaUrlResolver->resolve($path);
    }
}
