<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;

class HomePageController extends Controller
{
    private const CONTACT_ADVISORS = [
        1 => ['day' => 'Poniedziałek'],
        2 => ['day' => 'Wtorek'],
        3 => ['day' => 'Środa'],
        4 => ['day' => 'Czwartek'],
        5 => ['day' => 'Piątek'],
        6 => ['day' => 'Sobota'],
        7 => ['day' => 'Niedziela'],
    ];

    public function __invoke(): View
    {
        $updatedAt = now('Europe/Warsaw')->locale('pl');
        $canonical = route('home');
        $heroImage = Vite::asset('resources/images/home/hero-composite-v3.webp');
        $organizationName = (string) config('content.organization.name', config('app.name', 'PrawkoNaRaz'));
        $organizationLogo = (string) config('content.organization.logo_url', asset('favicon.png'));
        $contactAdvisorDay = (int) $updatedAt->format('N');
        $contactAdvisor = self::CONTACT_ADVISORS[$contactAdvisorDay];
        $contactAdvisor['day_index'] = $contactAdvisorDay;
        $seoYear = (int) $updatedAt->format('Y');
        $title = "Testy na prawo jazdy {$seoYear} – oficjalna baza pytań | {$organizationName}";
        $description = "Testy na prawo jazdy {$seoYear} z oficjalnej bazy pytań. Ucz się teorii z wyjaśnieniami i przygotuj się do egzaminu teoretycznego na prawo jazdy.";

        return view('home.index', [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => $heroImage,
                'image_alt' => "Widok platformy {$organizationName}",
                'og_type' => 'website',
            ],
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $organizationName,
                    'url' => $canonical,
                    'description' => $description,
                    'inLanguage' => 'pl-PL',
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $organizationName,
                    'url' => $canonical,
                    'logo' => $organizationLogo,
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
            'contactAdvisor' => $contactAdvisor,
            'contactTopics' => ContactMessageRequest::TOPICS,
            'seoYear' => $seoYear,
        ]);
    }
}
