<?php

namespace App\Http\Controllers;

use App\Support\PublicQuestionCatalogService;
use App\Support\PublicTestCategoryCatalog;
use Illuminate\View\View;

class PublicTestCategoryController extends Controller
{
    public function __invoke(
        string $categorySlug,
        PublicTestCategoryCatalog $testCategoryCatalog,
        PublicQuestionCatalogService $publicQuestionCatalogService,
    ): View {
        $content = $testCategoryCatalog->find($categorySlug);

        abort_unless($content !== null, 404);

        $category = $publicQuestionCatalogService->findVisibleCategoryBySlug($categorySlug);

        abort_unless($category !== null, 404);

        $categoryCard = $publicQuestionCatalogService
            ->categoryCards()
            ->firstWhere('slug', $categorySlug);
        $sampleQuestions = $publicQuestionCatalogService
            ->representativeQuestionsForCategory($category)
            ->take(4)
            ->map(fn ($question): array => $publicQuestionCatalogService->buildQuestionListItem($question, $category))
            ->values();

        $year = (int) now('Europe/Warsaw')->format('Y');
        $canonicalUrl = route('public.tests.category', ['categorySlug' => $categorySlug]);
        $title = "Testy na prawo jazdy kat. {$content['code']} {$year} – egzamin teoretyczny | PrawkoNaRaz";
        $description = "Testy na prawo jazdy kat. {$content['code']} {$year}: aktualne pytania egzaminacyjne, zasady teorii WORD, najważniejsze zagadnienia i przygotowanie do egzaminu.";

        if ($content['code'] === 'AM') {
            $title = "Testy na prawo jazdy kat. AM {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. AM {$year} z aktualnymi pytaniami egzaminacyjnymi. Ćwicz pytania na motorower i czterokołowiec lekki i przygotuj się do egzaminu WORD.";
        } elseif ($content['code'] === 'A1') {
            $title = "Testy na prawo jazdy kat. A1 {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. A1 {$year} z aktualnymi pytaniami egzaminacyjnymi. Ćwicz pytania na motocykl 125 cm³ i przygotuj się do egzaminu WORD.";
        } elseif ($content['code'] === 'B') {
            $title = "Testy na prawo jazdy kat. B {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. B {$year} z aktualnymi pytaniami egzaminacyjnymi. Rozwiązuj testy kat. B, sprawdzaj odpowiedzi i przygotuj się do egzaminu w WORD.";
        } elseif ($content['code'] === 'C') {
            $title = "Testy na prawo jazdy kat. C {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. C {$year} z aktualnymi pytaniami egzaminacyjnymi. Ćwicz pytania kat. C i przygotuj się do egzaminu teoretycznego w WORD.";
        } elseif ($content['code'] === 'D') {
            $title = "Testy na prawo jazdy kat. D {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. D {$year} z aktualnymi pytaniami egzaminacyjnymi. Ćwicz pytania kat. D i przygotuj się do egzaminu na autobus w WORD.";
        } elseif ($content['code'] === 'T') {
            $title = "Testy na prawo jazdy kat. T {$year} – pytania egzaminacyjne WORD | PrawkoNaRaz.pl";
            $description = "Testy na prawo jazdy kat. T {$year} z aktualnymi pytaniami egzaminacyjnymi. Ćwicz pytania na ciągnik rolniczy i przygotuj się do egzaminu WORD.";
        }
        $faq = $testCategoryCatalog->faq($content);
        $breadcrumbs = [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Testy na prawo jazdy', 'url' => route('public.tests')],
            ['label' => 'Kategoria '.$content['code'], 'url' => $canonicalUrl],
        ];

        return view('tests.category', [
            'category' => $category,
            'categoryCard' => $categoryCard,
            'content' => $content,
            'year' => $year,
            'faq' => $faq,
            'sampleQuestions' => $sampleQuestions,
            'categoryMenu' => $testCategoryCatalog->menuItems(),
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonicalUrl,
                'og_type' => 'website',
                'robots' => 'index,follow,max-image-preview:large',
                ...($content['code'] === 'AM' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-am-editorial.jpg'),
                    'image_alt' => 'Motorower na drodze',
                    'image_width' => 1774,
                    'image_height' => 887,
                ] : []),
                ...($content['code'] === 'A1' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-a1-editorial.jpg'),
                    'image_alt' => 'Motocykl o niewielkiej pojemności na drodze',
                    'image_width' => 1774,
                    'image_height' => 887,
                ] : []),
                ...($content['code'] === 'B' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-b-editorial.jpg'),
                    'image_alt' => 'Samochód osobowy na miejskiej drodze',
                    'image_width' => 1920,
                    'image_height' => 816,
                ] : []),
                ...($content['code'] === 'C' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-c-editorial.jpg'),
                    'image_alt' => 'Samochód ciężarowy na drodze',
                    'image_width' => 1774,
                    'image_height' => 887,
                ] : []),
                ...($content['code'] === 'D' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-d-editorial.jpg'),
                    'image_alt' => 'Autobus na drodze miejskiej',
                    'image_width' => 1919,
                    'image_height' => 820,
                ] : []),
                ...($content['code'] === 'T' ? [
                    'image' => asset('images/testy/prawo-jazdy-kat-t-editorial.jpg'),
                    'image_alt' => 'Ciągnik rolniczy z przyczepą na drodze',
                    'image_width' => 1942,
                    'image_height' => 809,
                ] : []),
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_map(
                        fn (array $item, int $index): array => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => $item['label'],
                            'item' => $item['url'],
                        ],
                        $breadcrumbs,
                        array_keys($breadcrumbs),
                    ),
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonicalUrl,
                    'inLanguage' => 'pl-PL',
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(
                        fn (array $item): array => [
                            '@type' => 'Question',
                            'name' => $item['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $item['answer'],
                            ],
                        ],
                        $faq,
                    ),
                ],
            ],
        ]);
    }
}
