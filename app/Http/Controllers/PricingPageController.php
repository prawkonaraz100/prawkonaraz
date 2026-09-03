<?php

namespace App\Http\Controllers;

use App\Models\ProductPlan;
use App\Support\PaymentRequirementService;
use App\Support\ProductAccessResolver;
use App\Support\ProductCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PricingPageController extends Controller
{
    private const META_TITLE = 'Cennik nauki do prawa jazdy - plany dostępu | prawkonaraz.pl';

    private const META_DESCRIPTION = 'Wybierz dostęp na miesiąc, 3 miesiące albo rok. Ucz się pytań do prawa jazdy, wracaj do błędów, korzystaj z powtórek, statystyk i trybu rankingowego.';

    private const PLAN_SLOTS = [
        'start-30' => [
            'name' => 'Dostęp na 1 miesiąc',
            'description' => 'Na szybkie przygotowanie albo powtórkę przed egzaminem.',
            'period_label' => '1 miesiąc',
            'card_title' => 'Start',
            'card_description' => 'Idealny na szybkie przygotowanie przed egzaminem.',
            'features' => [
                'Wszystkie pytania egzaminacyjne',
                'Tryb egzaminu państwowego',
                'Nauka tematami',
                'Dostęp na telefonie i komputerze',
            ],
            'cta_label' => 'Rozpocznij naukę',
            'access_days' => 30,
            'badge' => null,
            'is_featured' => false,
        ],
        'start-90' => [
            'name' => 'Dostęp na 3 miesiące',
            'description' => 'Więcej spokoju: uczysz się, robisz powtórki i wracasz do błędów bez presji.',
            'period_label' => '3 miesiące',
            'card_title' => 'Spokojna nauka',
            'card_description' => 'Najlepszy balans czasu i ceny.',
            'features' => [
                'Wszystko z planu Start',
                'Statystyki postępów',
                'Inteligentne powtórki błędów',
                'Ranking i motywacja',
                'Priorytetowe aktualizacje pytań',
            ],
            'cta_label' => 'Wybieram ten plan',
            'access_days' => 90,
            'badge' => 'Najczęściej wybierany',
            'is_featured' => true,
        ],
        'start-365' => [
            'name' => 'Dostęp na rok',
            'description' => 'Dla osób, które chcą mieć dostęp pod ręką i uczyć się wtedy, kiedy mają czas.',
            'period_label' => 'Rok',
            'card_title' => 'Premium',
            'card_description' => 'Dostęp bez presji przez cały rok.',
            'features' => [
                'Wszystko z planu 3 miesiące',
                'Nauka we własnym tempie',
                'Dostęp przez cały rok',
                'Idealne dla kursantów i instruktorów',
                'Najlepszy wybór cenowy',
            ],
            'cta_label' => 'Uzyskaj dostęp',
            'access_days' => 365,
            'badge' => null,
            'is_featured' => false,
        ],
    ];

    public function __invoke(
        Request $request,
        ProductCheckoutService $checkoutService,
        ProductAccessResolver $productAccessResolver,
        PaymentRequirementService $paymentRequirementService,
    ): View {
        $user = $request->user();
        $paymentRequired = $paymentRequirementService->requiresPayment();
        $accessDecision = $user ? $productAccessResolver->forUser($user) : null;
        $plans = $paymentRequired ? $this->plans($checkoutService) : collect();
        $canonicalUrl = route('public.pricing');
        $breadcrumbs = $this->breadcrumbs($canonicalUrl);
        $meta = [
            'title' => $paymentRequired
                ? self::META_TITLE
                : 'Nauka do prawa jazdy bez opłaty | prawkonaraz.pl',
            'description' => $paymentRequired
                ? self::META_DESCRIPTION
                : 'Załóż konto, potwierdź e-mail i rozpocznij naukę pytań do prawa jazdy bez opłaty.',
            'canonical' => $canonicalUrl,
            'og_type' => 'website',
            'robots' => 'index,follow,max-image-preview:large',
        ];

        return view('pricing.index', [
            'plans' => $plans,
            'checkout' => [
                'access_open' => ! $paymentRequired,
                'can_checkout' => $paymentRequired && $user !== null && $user->hasVerifiedEmail(),
                'has_active_access' => (bool) $accessDecision?->allowed,
                'login_url' => route('login', absolute: false),
                'register_url' => route('register', absolute: false),
                'verify_email_url' => route('verification.notice', absolute: false),
                'session_url' => route('session.index', absolute: false),
            ],
            'meta' => $meta,
            'breadcrumbs' => [],
            'structuredData' => $this->structuredData(
                $breadcrumbs,
                $plans,
                $canonicalUrl,
                $meta['title'],
                $meta['description'],
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function plans(ProductCheckoutService $checkoutService): Collection
    {
        $activePlans = $checkoutService->activePlans()->keyBy('code');

        return collect(self::PLAN_SLOTS)
            ->map(function (array $slot, string $code) use ($activePlans): array {
                /** @var ProductPlan|null $plan */
                $plan = $activePlans->get($code);

                return [
                    'code' => $code,
                    'name' => $slot['name'],
                    'description' => $slot['description'],
                    'price_gross_cents' => $plan?->price_gross_cents,
                    'formatted_price' => $plan?->formattedPrice() ?? 'Cena wkrótce',
                    'currency' => $plan?->currency ?? 'PLN',
                    'access_days' => $plan?->access_days ?? $slot['access_days'],
                    'period_label' => $slot['period_label'],
                    'card_title' => $slot['card_title'],
                    'card_description' => $slot['card_description'],
                    'features' => $slot['features'],
                    'cta_label' => $slot['cta_label'],
                    'badge' => $slot['badge'],
                    'is_featured' => $slot['is_featured'],
                    'available' => $plan !== null,
                ];
            })
            ->values();
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function breadcrumbs(string $canonicalUrl): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Cennik', 'url' => $canonicalUrl],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, array<string, mixed>>  $plans
     * @return list<array<string, mixed>>
     */
    private function structuredData(
        array $breadcrumbs,
        Collection $plans,
        string $canonicalUrl,
        string $title,
        string $description,
    ): array
    {
        $schemas = [
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
        ];

        $offers = $plans
            ->filter(fn (array $plan): bool => (bool) $plan['available'] && $plan['price_gross_cents'] !== null)
            ->values()
            ->map(fn (array $plan): array => [
                '@type' => 'Offer',
                'name' => $plan['name'],
                'description' => $plan['description'],
                'price' => number_format(((int) $plan['price_gross_cents']) / 100, 2, '.', ''),
                'priceCurrency' => $plan['currency'],
                'availability' => 'https://schema.org/InStock',
                'url' => $canonicalUrl,
            ])
            ->all();

        if ($offers !== []) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'OfferCatalog',
                'name' => 'Plany dostępu do nauki prawa jazdy',
                'url' => $canonicalUrl,
                'itemListElement' => array_map(
                    fn (array $offer, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => $offer,
                    ],
                    $offers,
                    array_keys($offers),
                ),
            ];
        }

        return $schemas;
    }
}
