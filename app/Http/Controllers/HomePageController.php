<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;

class HomePageController extends Controller
{
    public function __construct(
        protected SiteIdentitySchema $siteIdentitySchema,
        protected SchemaIds $schemaIds,
        protected SchemaRenderer $schemaRenderer,
    ) {}

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
        $organizationName = $this->siteIdentitySchema->name();
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
            'structuredData' => $this->schemaRenderer->graph([
                $this->siteIdentitySchema->organization(),
                $this->siteIdentitySchema->website([
                    '@type' => 'SearchAction',
                    'target' => route('public.questions.hub').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ]),
                [
                    '@id' => $this->schemaIds->fragment($canonical, 'webpage'),
                    '@type' => 'WebPage',
                    'name' => $title,
                    'url' => $canonical,
                    'description' => $description,
                    'inLanguage' => 'pl-PL',
                    'isPartOf' => [
                        '@id' => $this->schemaIds->website(),
                    ],
                    'about' => [
                        '@id' => $this->schemaIds->organization(),
                    ],
                    'primaryImageOfPage' => $heroImage,
                ],
            ]),
            'contactAdvisor' => $contactAdvisor,
            'contactTopics' => ContactMessageRequest::TOPICS,
            'seoYear' => $seoYear,
        ]);
    }
}
