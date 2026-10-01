<?php

namespace App\Support;

use App\Models\User;

class PublicFooter
{
    /**
     * @param  array<string, mixed>|null  $navigation
     * @return array<string, mixed>
     */
    public function data(?User $user, ?array $navigation = null): array
    {
        $navigation ??= app(PublicNavigation::class)->data($user);

        return [
            'home_href' => route('home', absolute: false),
            'brand' => [
                'aria_label' => 'prawkonaraz.pl',
                'name' => 'prawkonaraz.pl',
                'tagline' => 'Pytania na Prawo Jazdy',
            ],
            'description' => 'Oficjalna baza pytań WORD, rozszerzone wyjaśnienia i inteligentny system nauki — wszystko, czego potrzebujesz, żeby dobrze przygotować się do egzaminu teoretycznego.',
            'email' => (string) config('content.organization.email', 'kontakt@prawkonaraz.pl'),
            'primary_action' => [
                'label' => 'Rozpocznij naukę',
                'href' => $navigation['learning_href'],
            ],
            'secondary_action' => [
                'label' => 'Przejdź do bazy pytań',
                'href' => route('public.questions.hub', absolute: false),
            ],
            'legal_links' => [
                ['label' => 'Regulamin', 'href' => route('legal.terms', absolute: false)],
                ['label' => 'Polityka prywatności', 'href' => route('legal.privacy', absolute: false)],
                ['label' => 'Kontakt', 'href' => route('home', absolute: false).'#kontakt'],
                ['label' => 'Ustawienia plików cookie', 'href' => route('legal.privacy', absolute: false).'#cookies'],
            ],
            'service_links' => [
                ['label' => 'Baza pytań', 'href' => route('public.questions.hub', absolute: false)],
                ['label' => 'Znaki drogowe', 'href' => route('traffic-signs.index', absolute: false)],
                ['label' => 'Przepisy', 'href' => route('public.regulations', absolute: false)],
                ['label' => 'Testy na prawo jazdy', 'href' => route('public.tests', absolute: false)],
                ['label' => 'Portal — aktualności', 'href' => route('public.news', absolute: false)],
                ['label' => 'Poradniki', 'href' => route('public.guides', absolute: false)],
                ['label' => 'Kurs teorii Online', 'href' => route('public.course', absolute: false)],
                ['label' => 'Cennik', 'href' => route('public.pricing', absolute: false)],
                ['label' => 'Opinie', 'href' => route('reviews.index', absolute: false)],
            ],
            'social_links' => [
                ['label' => 'Facebook', 'href' => 'https://www.facebook.com/PrawkoNaRaz/', 'icon' => 'facebook'],
                ['label' => 'YouTube', 'href' => 'https://www.youtube.com/channel/UCSrCDt_Aj1yslMY8sfXFBkg', 'icon' => 'youtube'],
                ['label' => 'Instagram', 'href' => 'https://www.instagram.com/prawkonaraz.pl/', 'icon' => 'instagram'],
                ['label' => 'TikTok', 'href' => 'https://www.tiktok.com/@prawkonaraz', 'icon' => 'tiktok'],
            ],
            'mobile_apps' => [
                ['label' => 'App Store', 'icon' => 'apple', 'status' => 'w przygotowaniu'],
                ['label' => 'Google Play', 'icon' => 'google-play', 'status' => 'w przygotowaniu'],
            ],
            'language' => 'Polski',
            'groups' => $this->directoryGroups($user, $navigation),
            'copyright' => '© '.now()->year.' PrawkoNaRaz.pl',
        ];
    }

    /**
     * @return list<array{title: string, links: list<array{label: string, href: string}>}>
     */
    private function directoryGroups(?User $user, array $navigation): array
    {
        $accountLinks = $user instanceof User
            ? [
                ['label' => 'Profil', 'href' => route('profile.edit', absolute: false)],
                ['label' => 'Nauka', 'href' => '/nauka'],
            ]
            : [
                ['label' => 'Zaloguj się', 'href' => route('login', absolute: false)],
                ['label' => 'Załóż konto', 'href' => route('register', absolute: false)],
            ];

        $testCategoryLinks = collect($navigation['test_menu'] ?? [])
            ->reject(fn (array $link): bool => $link['label'] === 'Wszystkie testy na prawo jazdy')
            ->values()
            ->all();

        return [
            [
                'title' => 'Testy na prawo jazdy',
                'links' => [
                    ...$testCategoryLinks,
                    ['label' => 'Pytania na prawo jazdy', 'href' => route('public.questions.hub', absolute: false)],
                ],
            ],
            [
                'title' => 'Testy i nauka',
                'links' => [
                    ['label' => 'Testy online', 'href' => route('public.tests', absolute: false)],
                    ['label' => 'Przepisy ruchu drogowego', 'href' => route('public.regulations', absolute: false)],
                    ['label' => 'Poradniki', 'href' => route('public.guides', absolute: false)],
                    ['label' => 'Kurs teorii Online', 'href' => route('public.course', absolute: false)],
                    ['label' => 'Wykłady z instruktorem Online', 'href' => route('public.lectures', absolute: false)],
                    ['label' => 'Kod 95 — kierowca zawodowy', 'href' => route('public.code95', absolute: false)],
                ],
            ],
            [
                'title' => 'Znaki drogowe',
                'links' => [
                    ['label' => 'Wszystkie znaki drogowe', 'href' => route('traffic-signs.index', absolute: false)],
                    ['label' => 'Znaki informacyjne', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-informacyjne'], absolute: false)],
                    ['label' => 'Znaki ostrzegawcze', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-ostrzegawcze'], absolute: false)],
                    ['label' => 'Znaki nakazu', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-nakazu'], absolute: false)],
                    ['label' => 'Znaki zakazu', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-zakazu'], absolute: false)],
                    ['label' => 'Znaki poziome', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-drogowe-poziome'], absolute: false)],
                    ['label' => 'Znaki uzupełniające', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-uzupelniajace'], absolute: false)],
                    ['label' => 'Sygnały świetlne', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'sygnaly-swietlne'], absolute: false)],
                    ['label' => 'Osoba kierująca ruchem', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'osoba-kierujaca-ruchem'], absolute: false)],
                    ['label' => 'Kontrolki pojazdu', 'href' => route('traffic-signs.categories.show', ['categorySlug' => 'kontrolki-w-samochodzie'], absolute: false)],
                ],
            ],
            [
                'title' => 'Pomoc',
                'links' => [
                    ['label' => 'Opinie', 'href' => route('reviews.index', absolute: false)],
                    ['label' => 'Kontakt', 'href' => route('home', absolute: false).'#kontakt'],
                    ['label' => 'Aktualności', 'href' => route('public.news', absolute: false)],
                    ['label' => 'Cennik', 'href' => route('public.pricing', absolute: false)],
                    ...$accountLinks,
                ],
            ],
        ];
    }
}
