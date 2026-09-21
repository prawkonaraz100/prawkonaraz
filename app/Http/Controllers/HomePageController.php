<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Models\ContentAuthor;
use App\Models\HomepageVideo;
use App\Models\Question;
use App\Models\UserReview;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use App\Support\TrafficSignAuthorProfile;
use App\Support\UserReviewPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
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

    public function __invoke(UserReviewPresenter $reviewPresenter): View
    {
        $updatedAt = now('Europe/Warsaw')->locale('pl');
        $canonical = route('home');
        $heroImage = Vite::asset('resources/images/home/hero-composite-v3.webp');
        $contactAdvisorDay = (int) $updatedAt->format('N');
        $contactAdvisor = self::CONTACT_ADVISORS[$contactAdvisorDay];
        $contactAdvisor['day_index'] = $contactAdvisorDay;
        $seoYear = (int) $updatedAt->format('Y');
        $title = "Testy na prawo jazdy {$seoYear} – oficjalna baza pytań | PrawkoNaRaz";
        $description = "Testy na prawo jazdy {$seoYear} z oficjalnej bazy pytań. Rozwiąż darmowy test: 20 pytań bez logowania. Ucz się z wyjaśnieniami do egzaminu teoretycznego.";
        $categoryBQuestionCount = Cache::remember(
            'home.faq.active-category-b-question-count',
            now()->addHours(6),
            fn (): int => Question::query()
                ->where('is_active', true)
                ->whereHas('licenseCategory', fn ($query) => $query->where('code', 'B'))
                ->count(),
        );
        $formattedCategoryBQuestionCount = number_format($categoryBQuestionCount, 0, ',', ' ');
        $publicReviewQuery = UserReview::query()->publiclyVisible();
        $reviewStatsRow = (clone $publicReviewQuery)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();
        $publishedReviews = (clone $publicReviewQuery)
            ->with(['user.socialAccounts'])
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn (UserReview $review): array => $reviewPresenter->payload($review));
        $currentReview = auth()->user()?->review()->first();
        $reviewStats = [
            'count' => (int) ($reviewStatsRow?->total ?? 0),
            'average' => $reviewStatsRow?->average !== null
                ? number_format((float) $reviewStatsRow->average, 1, ',', ' ')
                : null,
        ];
        $learningQuoteAuthors = ContentAuthor::query()
            ->published()
            ->whereIn('slug', [
                ContentAuthor::DEFAULT_LEGAL_REFERENCE_VERIFIER_SLUG,
                TrafficSignAuthorProfile::SLUG,
            ])
            ->get(['name', 'slug', 'job_title', 'photo_path'])
            ->keyBy('slug');
        $homepageVideos = HomepageVideo::query()
            ->published()
            ->orderBy('sort_order')
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (HomepageVideo $video): array => [
                'id' => (string) $video->getKey(),
                'kind' => $video->kind,
                'title' => $video->title,
                'source_type' => 'youtube',
                'source_url' => $video->embedUrl(),
                'youtube_url' => $video->youtube_url,
                'thumbnail_url' => $video->thumbnailUrl(),
                'duration' => $video->formattedDuration(),
                'published_label' => $video->published_on?->format('d.m.Y'),
            ]);

        if ($homepageVideos->isEmpty()) {
            $homepageVideos = collect([[
                'id' => 'demo',
                'kind' => HomepageVideo::KIND_VIDEO,
                'title' => 'Zobacz, jak działa nauka w PrawkoNaRaz',
                'source_type' => 'local',
                'source_url' => Vite::asset('resources/videos/home/mistakes-learning-800p.mp4'),
                'youtube_url' => null,
                'thumbnail_url' => Vite::asset('resources/images/home/mistakes-learning-poster.jpg'),
                'duration' => null,
                'published_label' => 'Demo PrawkoNaRaz',
            ]]);
        }
        $faqItems = [
            [
                'question' => 'Czym jest PrawkoNaRaz?',
                'answer' => 'PrawkoNaRaz to platforma do nauki teorii na prawo jazdy. Łączy oficjalną bazę pytań, proste wyjaśnienia, powtórki błędów i próbny egzamin, aby prowadzić Cię od pierwszego pytania do przygotowania na egzamin państwowy.',
            ],
            [
                'question' => 'Jak działa nauka w PrawkoNaRaz?',
                'answer' => 'Najpierw poznajesz pytania i schematy odpowiedzi, następnie wracasz do błędów oraz wyjaśnień, a na końcu sprawdzasz się w egzaminie próbnym. Widok, audio, filmy i sposób przechodzenia między pytaniami możesz dopasować do siebie.',
            ],
            [
                'question' => 'Czy PrawkoNaRaz korzysta z oficjalnej bazy pytań?',
                'answer' => 'Tak. PrawkoNaRaz udostępnia pytania z oficjalnego katalogu Ministerstwa Infrastruktury. Przy pytaniach pokazujemy ich numery źródłowe, a bazę aktualizujemy, gdy publikowane są zmiany.',
            ],
            [
                'question' => 'Czy PrawkoNaRaz jest darmowe?',
                'answer' => 'Tak. PrawkoNaRaz oferuje darmowe przygotowanie do egzaminu teoretycznego na prawo jazdy. Możesz korzystać z testów na prawo jazdy online, uczyć się pytań egzaminacyjnych i przygotowywać się do państwowego egzaminu teoretycznego bez opłat. Bez logowania dostępny jest również darmowy test obejmujący 20 pytań. Po założeniu konta możesz korzystać z pełnej nauki, testów egzaminacyjnych oraz zapisywania postępów całkowicie za darmo. Zastrzegamy sobie możliwość zmiany zakresu bezpłatnych funkcji w przyszłości, np. w przypadku znacznego wzrostu kosztów utrzymania platformy.',
            ],
            [
                'question' => 'Czy PrawkoNaRaz zapisuje postęp i błędne odpowiedzi?',
                'answer' => 'Tak. Po zalogowaniu PrawkoNaRaz zapisuje postęp nauki i błędne odpowiedzi. Możesz wrócić do rozpoczętej sesji, powtarzać wybrane pytania i obserwować swoją skuteczność.',
            ],
            [
                'question' => 'Ile pytań jest na egzaminie teoretycznym na prawo jazdy?',
                'answer' => 'Egzamin teoretyczny składa się z 32 pytań: 20 pytań z wiedzy podstawowej oraz 12 pytań specjalistycznych dla wybranej kategorii prawa jazdy.',
            ],
            [
                'question' => 'Ile punktów trzeba mieć, żeby zdać egzamin teoretyczny na prawo jazdy?',
                'answer' => 'Aby zdać egzamin teoretyczny, trzeba zdobyć co najmniej 68 z 74 możliwych punktów. Pytania są warte 1, 2 albo 3 punkty.',
            ],
            [
                'question' => 'Ile błędów można zrobić na egzaminie teoretycznym na prawo jazdy?',
                'answer' => 'Nie ma jednej dopuszczalnej liczby błędów, ponieważ pytania mają różną wartość punktową. Możesz stracić maksymalnie 6 punktów i nadal zdać, dlatego znaczenie ma wartość pytań, na które odpowiesz nieprawidłowo.',
            ],
            [
                'question' => 'Czy pytania na egzaminie na prawo jazdy są takie same jak w oficjalnej bazie?',
                'answer' => 'Tak. Pytania używane podczas egzaminu państwowego są losowane z oficjalnego katalogu. Na konkretnym egzaminie zobaczysz losowo wybrany zestaw, więc kolejność i połączenie pytań mogą być inne niż podczas nauki.',
            ],
            [
                'question' => 'Ile jest pytań w bazie na prawo jazdy kat. B?',
                'answer' => "Aktualnie PrawkoNaRaz udostępnia {$formattedCategoryBQuestionCount} aktywnych pytań dla kategorii B. Liczba może się zmieniać, gdy Ministerstwo Infrastruktury publikuje aktualizacje albo wycofuje pytania.",
            ],
            [
                'question' => 'Jak wygląda egzamin teoretyczny na prawo jazdy?',
                'answer' => 'Egzamin odbywa się przy komputerze i obejmuje 20 pytań podstawowych oraz 12 specjalistycznych. Pytania podstawowe mają odpowiedzi TAK albo NIE, a specjalistyczne trzy warianty odpowiedzi: A, B lub C.',
            ],
            [
                'question' => 'Ile trwa egzamin teoretyczny na prawo jazdy?',
                'answer' => 'Część teoretyczna egzaminu trwa 25 minut. Przy pytaniach podstawowych obowiązuje osobny czas na zapoznanie się z treścią i udzielenie odpowiedzi.',
            ],
            [
                'question' => 'Jak zdać teorię na prawo jazdy za pierwszym razem?',
                'answer' => 'Przerób pełną bazę pytań, czytaj wyjaśnienia, regularnie wracaj do błędnych odpowiedzi i rozwiązuj egzaminy próbne w limicie 25 minut. Przed egzaminem warto stabilnie osiągać wyniki wyższe niż wymagane 68 punktów.',
            ],
            [
                'question' => 'Jak szybko nauczyć się pytań na prawo jazdy?',
                'answer' => 'Ucz się krótkimi, regularnymi sesjami. Najpierw poznaj pytania, później powtarzaj przede wszystkim błędy i trudne działy, a na końcu sprawdzaj wiedzę egzaminami próbnymi. Wyjaśnienia pomagają zapamiętać zasadę, zamiast jedynie układ odpowiedzi.',
            ],
            [
                'question' => 'Czy wszystkie pytania na egzaminie pochodzą z oficjalnej bazy Ministerstwa Infrastruktury?',
                'answer' => 'Tak. Systemy egzaminacyjne WORD korzystają z centralnego katalogu pytań nadzorowanego przez Ministra Infrastruktury. PrawkoNaRaz wykorzystuje oficjalną bazę i pokazuje numery źródłowe pytań.',
            ],
            [
                'question' => 'Czy pytania na egzaminie na prawo jazdy się powtarzają?',
                'answer' => 'Tak. Zestaw jest losowany z oficjalnej bazy, dlatego podczas kolejnych egzaminów lub testów próbnych możesz ponownie trafić na te same pytania. Nie należy jednak zakładać, że pojawi się identyczny zestaw.',
            ],
            [
                'question' => 'Czy baza pytań na prawo jazdy jest jawna?',
                'answer' => 'Tak. Ministerstwo Infrastruktury publikuje katalog pytań egzaminacyjnych na portalu gov.pl. Baza jest aktualizowana, a pytania mogą być dodawane, poprawiane lub wycofywane.',
            ],
        ];

        return view('home.index', [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => $heroImage,
                'image_alt' => 'Widok platformy PrawkoNaRaz',
                'og_type' => 'website',
            ],
            'structuredData' => $this->schemaRenderer->graph([
                $this->siteIdentitySchema->organization(),
                $this->siteIdentitySchema->website(),
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
                    'publisher' => [
                        '@id' => $this->schemaIds->organization(),
                    ],
                    'primaryImageOfPage' => $heroImage,
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(static fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['answer'],
                        ],
                    ], $faqItems),
                ],
            ]),
            'contactAdvisor' => $contactAdvisor,
            'contactTopics' => ContactMessageRequest::TOPICS,
            'seoYear' => $seoYear,
            'faqItems' => $faqItems,
            'publishedReviews' => $publishedReviews,
            'currentReview' => $currentReview,
            'reviewStats' => $reviewStats,
            'learningQuoteAuthors' => $learningQuoteAuthors,
            'homepageVideos' => $homepageVideos,
        ]);
    }
}
