<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PartnersPageController extends Controller
{
    public function __invoke(): View
    {
        $breadcrumbs = [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Partnerzy i źródła', 'url' => route('public.partners')],
        ];

        return view('partners.index', [
            'meta' => [
                'title' => 'Partnerzy i współpraca - PrawkoNaRaz.pl',
                'description' => 'Sprawdź, jak PrawkoNaRaz.pl współpracuje z Ministerstwem Infrastruktury, ośrodkami egzaminacyjnymi WORD i ośrodkami szkolenia kierowców OSK.',
                'canonical' => route('public.partners'),
                'og_type' => 'website',
            ],
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(
                    static fn (array $item, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['label'],
                        'item' => $item['url'],
                    ],
                    $breadcrumbs,
                    array_keys($breadcrumbs),
                ),
            ],
        ]);
    }
}
