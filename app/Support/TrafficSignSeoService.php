<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;

class TrafficSignSeoService
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
        protected PublicUrlResolver $publicUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hub(): array
    {
        return [
            'title' => 'Znaki drogowe - znaczenie, przepisy i zachowanie kierowcy',
            'description' => 'Przegląd znaków drogowych z podziałem na kategorie, objaśnieniami i praktycznymi wskazówkami dla kierowców.',
            'canonical' => route('traffic-signs.index'),
            'image' => $this->publicUrlResolver->normalize('/images/traffic-signs/traffic-signs-hero-clean.png'),
            'image_alt' => 'Znaki STOP, przejścia dla pieszych i ograniczenia prędkości przy ulicy',
            'image_width' => 484,
            'image_height' => 353,
            'preload_image' => asset('images/traffic-signs/traffic-signs-hero-clean.png'),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function category(TrafficSignCategory $category): array
    {
        return [
            'title' => "{$category->name} - znaki drogowe i wyjaśnienia",
            'description' => $category->description ?: "Poznaj kategorię {$category->name}, jej znaki, znaczenie i praktyczne wskazówki dla kierowcy.",
            'canonical' => route('traffic-signs.categories.show', $category->slug),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sign(TrafficSign $sign): array
    {
        $imageUrl = $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path);
        $ogImageUrl = $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->og_image_path);
        $publicTitle = $sign->publicTitle();

        return [
            'title' => $sign->meta_title ?: "{$publicTitle} - znaczenie, przepisy i zachowanie kierowcy",
            'description' => $sign->meta_description ?: ($sign->intro_definition ?: "Sprawdź, co oznacza znak {$publicTitle} i jak powinien zachować się kierowca."),
            'canonical' => route('traffic-signs.show', $sign->slug),
            'image' => $ogImageUrl ?: $imageUrl,
            'image_alt' => $sign->publicOgImageAlt(),
            'image_width' => $sign->og_image_width ?: $sign->image_width,
            'image_height' => $sign->og_image_height ?: $sign->image_height,
            'preload_image' => $imageUrl,
            'og_type' => 'article',
            'author_name' => $sign->author->name,
            'published_time' => $sign->published_at?->toIso8601String(),
            'modified_time' => $sign->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function author(ContentAuthor $author): array
    {
        return [
            'title' => "{$author->name} - autor treści edukacyjnych",
            'description' => $author->bio ?: "Poznaj profil autora {$author->name} i zobacz jego opublikowane treści edukacyjne.",
            'canonical' => route('content-authors.show', $author->slug),
            'image' => $this->mediaUrlResolver->resolve($author->photo_path, 'public'),
            'og_type' => 'profile',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationPage(): array
    {
        $name = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));

        return [
            'title' => "{$name} - o nas",
            'description' => 'Poznaj zespół PrawkoNaRaz, ekspertów odpowiadających za znaki, przepisy i proces weryfikacji treści edukacyjnych dla kandydatów na kierowców.',
            'canonical' => route('about.organization'),
            'image' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function howItWorksPage(): array
    {
        $name = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));

        return [
            'title' => "{$name} - jak działa nauka prawa jazdy online",
            'description' => 'Zobacz, jak działa PrawkoNaRaz: tryby nauki, powtórki błędów, baza pytań, znaki, przepisy, rankingi, promocje i zaproszenia znajomych.',
            'canonical' => route('about.how-it-works'),
            'image' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contactPage(): array
    {
        $name = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));

        return [
            'title' => "{$name} - kontakt",
            'description' => 'Skontaktuj się z zespołem serwisu w sprawie treści, korekt merytorycznych i współpracy wokół materiałów o znakach drogowych.',
            'canonical' => route('about.contact'),
            'image' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function methodologyPage(): array
    {
        $name = (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl'));

        return [
            'title' => "{$name} - pytania na prawo jazdy i nauka teorii",
            'description' => 'Zobacz, jak PrawkoNaRaz pomaga uczyć się teorii i pytań na prawo jazdy ze zrozumieniem sytuacji drogowych oraz zasad egzaminu.',
            'canonical' => route('about.methodology'),
            'image' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'og_type' => 'website',
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     */
    public function supportingPage(array $page, ?ContentAuthor $author = null, ?TrafficSign $primarySign = null): array
    {
        $imageUrl = $primarySign instanceof TrafficSign
            ? ($this->mediaUrlResolver->resolveIfPublicAssetExists($primarySign->og_image_path)
                ?: $this->mediaUrlResolver->resolveIfPublicAssetExists($primarySign->image_path))
            : $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png'));

        return [
            'title' => (string) ($page['title'] ?? 'Materiał wspierający'),
            'description' => (string) ($page['description'] ?? ''),
            'canonical' => route('traffic-signs.supporting.show', $page['slug']),
            'image' => $imageUrl,
            'image_alt' => $primarySign?->og_image_alt ?: $primarySign?->image_alt,
            'image_width' => $primarySign?->og_image_width ?: $primarySign?->image_width,
            'image_height' => $primarySign?->og_image_height ?: $primarySign?->image_height,
            'og_type' => 'article',
            'author_name' => $author?->name,
            'published_time' => $page['published_at'] ?? null,
            'modified_time' => $page['updated_at'] ?? null,
        ];
    }
}
