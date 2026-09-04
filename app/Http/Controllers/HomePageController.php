<?php

namespace App\Http\Controllers;

use App\Support\PublicQuestionCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

class HomePageController extends Controller
{
    public function __invoke(PublicQuestionCatalogService $questionCatalog): View
    {
        $updatedAt = now('Europe/Warsaw')->locale('pl');
        $updatedLabel = $updatedAt->translatedFormat('j F Y');
        $canonical = route('home');
        $heroImage = Vite::asset('resources/images/home/hero-composite-v3.webp');
        $logoImage = asset('images/orly-na-drodze-logo-tight.png');
        $featuredCategoryCodes = ['B', 'A', 'C', 'D'];
        $featuredCategories = $questionCatalog
            ->visibleCategories()
            ->filter(fn ($category): bool => in_array(Str::upper((string) $category->code), $featuredCategoryCodes, true))
            ->sortBy(function ($category) use ($featuredCategoryCodes): int {
                $index = array_search(Str::upper((string) $category->code), $featuredCategoryCodes, true);

                return is_int($index) ? $index : PHP_INT_MAX;
            })
            ->map(function ($category) use ($questionCatalog): array {
                $copy = $questionCatalog->categoryMarketingCopy($category);

                return [
                    'code' => $category->code,
                    'url' => route('public.questions.category', $category->slug),
                    'vehicle_label' => $copy['vehicle_label'],
                ];
            })
            ->values();
        $featuredCategoriesByCode = $featuredCategories
            ->keyBy(fn (array $category): string => Str::upper((string) $category['code']));
        $questionSearchUrl = static fn (string $query): string => route(
            'public.questions.search',
            ['q' => $query],
            absolute: false,
        );
        $learningAreas = collect([
            $featuredCategoriesByCode->has('B') ? [
                'label' => 'Kategoria B',
                'url' => $featuredCategoriesByCode->get('B')['url'],
            ] : null,
            $featuredCategoriesByCode->has('C') ? [
                'label' => 'Kategoria C',
                'url' => $featuredCategoriesByCode->get('C')['url'],
            ] : null,
            [
                'label' => 'Znaki drogowe',
                'url' => route('traffic-signs.index', absolute: false),
            ],
            ['label' => 'Pierwsza pomoc', 'url' => $questionSearchUrl('pierwsza pomoc')],
            ['label' => 'Skrzyżowania', 'url' => $questionSearchUrl('skrzyżowania')],
            ['label' => 'Prędkości i ograniczenia', 'url' => $questionSearchUrl('prędkość ograniczenia')],
            ['label' => 'Czas pracy kierowcy', 'url' => $questionSearchUrl('czas pracy kierowcy')],
            ['label' => 'Kwalifikacja zawodowa', 'url' => $questionSearchUrl('kwalifikacja zawodowa')],
            [
                'label' => 'Egzamin próbny',
                'url' => route('public.tests', absolute: false),
            ],
            [
                'label' => 'Błędne pytania',
                'url' => route('incorrect-questions.index', absolute: false),
            ],
        ])->filter()->values();

        $title = 'Testy na prawo jazdy 2026 - oficjalna baza pytań i skuteczna nauka teorii';
        $description = 'Ucz się do egzaminu na prawo jazdy na oficjalnej bazie pytań. Korzystaj z trybów nauki, wyjaśnień, znaków drogowych i materiałów przygotowanych pod szybkie zdanie teorii.';

        return view('home.index', [
            'updatedLabel' => $updatedLabel,
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => $heroImage,
                'image_alt' => 'Widok platformy Orły na Drodze',
                'og_type' => 'website',
            ],
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'Orły na Drodze',
                    'url' => $canonical,
                    'description' => $description,
                    'inLanguage' => 'pl-PL',
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => 'Orły na Drodze',
                    'url' => $canonical,
                    'logo' => $logoImage,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $title,
                    'url' => $canonical,
                    'description' => $description,
                    'inLanguage' => 'pl-PL',
                    'primaryImageOfPage' => $heroImage,
                ],
            ],
            'featuredCategories' => $featuredCategories,
            'learningAreas' => $learningAreas,
        ]);
    }
}
