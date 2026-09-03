<?php

namespace App\Support;

use App\Models\LegalContentPage;

class LegalContentSeoService
{
    public function __construct(
        protected PublicUrlResolver $publicUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hub(int $pagesCount): array
    {
        return [
            'title' => 'Przepisy drogowe do egzaminu - podstawy prawne pytań',
            'description' => 'Praktyczny indeks przepisów drogowych do egzaminu. Sprawdź, z których aktów prawnych wynikają pytania egzaminacyjne, znaki i zasady ruchu drogowego.',
            'canonical' => route('public.regulations'),
            'image' => $this->publicUrlResolver->normalize('/images/legal/regulations-hero.png'),
            'image_alt' => 'Kodeks drogowy, otwarta ustawa i znaki drogowe na tle ulicy',
            'image_width' => 524,
            'image_height' => 361,
            'preload_image' => asset('images/legal/regulations-hero.png'),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function page(LegalContentPage $page): array
    {
        $articleImage = config("content.legal_content.article_images.{$page->slug}");
        $articleImagePath = is_array($articleImage) ? ($articleImage['path'] ?? null) : null;

        return [
            'title' => $page->meta_title ?: "{$page->title} - przepisy drogowe do egzaminu",
            'description' => $page->meta_description ?: ($page->intro ?: 'Wyjaśnienie przepisu drogowego w kontekście pytań egzaminacyjnych na prawo jazdy.'),
            'canonical' => route('public.regulations.show', $page->slug),
            'image' => filled($articleImagePath)
                ? $this->publicUrlResolver->normalize('/'.ltrim((string) $articleImagePath, '/'))
                : null,
            'image_alt' => is_array($articleImage) ? ($articleImage['alt'] ?? null) : null,
            'image_width' => is_array($articleImage) ? ($articleImage['width'] ?? null) : null,
            'image_height' => is_array($articleImage) ? ($articleImage['height'] ?? null) : null,
            'preload_image' => filled($articleImagePath) ? asset((string) $articleImagePath) : null,
            'og_type' => 'article',
            'author_name' => $page->author?->name,
            'published_time' => $page->published_at?->toIso8601String(),
            'modified_time' => ($page->last_reviewed_at ?: $page->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function methodology(): array
    {
        return [
            'title' => 'Jak weryfikujemy przepisy i podstawy prawne - prawkonaraz.pl',
            'description' => 'Zobacz, z jakich oficjalnych źródeł korzystamy, jak opisujemy przepisy prostym językiem i jak oznaczamy datę weryfikacji podstaw prawnych.',
            'canonical' => route('public.regulations.methodology'),
            'image' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'og_type' => 'website',
        ];
    }
}
