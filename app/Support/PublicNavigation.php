<?php

namespace App\Support;

use App\Models\User;

class PublicNavigation
{
    /**
     * @return array<string, mixed>
     */
    public function data(?User $user): array
    {
        $isAuthed = $user instanceof User;
        $learningHref = $isAuthed ? '/nauka' : '/login';
        $primaryLinks = [
            $this->link('Aktualności', route('public.news', absolute: false), ['/aktualnosci']),
            ...($isAuthed ? [
                $this->link('Nauka', $learningHref, ['/nauka', '/study-sessions', '/trener-pamieci']),
            ] : []),
            $this->link(
                'Pytania',
                route('public.questions.hub', absolute: false),
                ['/oficjalna-baza-pytan-na-prawo-jazdy', '/pytanie'],
            ),
            $this->link('Znaki drogowe', route('traffic-signs.index', absolute: false), ['/znaki-drogowe']),
            $this->link('Testy online', route('public.tests', absolute: false), [route('public.tests', absolute: false)]),
            $this->link('Przepisy', route('public.regulations', absolute: false), ['/przepisy']),
            $this->link('Poradniki', route('public.guides', absolute: false), ['/poradniki']),
            $this->link('Cennik', route('public.pricing', absolute: false), ['/cennik']),
        ];

        return [
            'top' => [
                $this->link('Baza pytań', route('public.questions.hub', absolute: false), ['/oficjalna-baza-pytan-na-prawo-jazdy', '/pytanie'], icon: 'baza-pytan'),
                $this->link('Plany nauki', route('public.pricing', absolute: false), ['/cennik'], icon: 'plany-nauki'),
                $this->link('Kursy', route('public.course', absolute: false), ['/kurs'], icon: 'kursy'),
                $this->link('Cennik', route('public.pricing', absolute: false), ['/cennik'], icon: 'cennik'),
                $this->link('Kontakt', route('about.contact', absolute: false), ['/kontakt'], icon: 'kontakt'),
            ],
            'utility' => [
                $this->link('O nas', route('about.organization', absolute: false), ['/o-nas', '/autorzy']),
                $this->link('Jak to działa', route('about.how-it-works', absolute: false), ['/jak-to-dziala']),
                $this->link('Dla instruktorów', route('public.instructor-training', absolute: false), ['/szkolenia-z-instruktorem']),
                $this->link('Reklama', route('public.advertising', absolute: false), ['/reklama']),
                $this->link('Kontakt', route('about.contact', absolute: false), ['/kontakt']),
            ],
            'primary' => $primaryLinks,
            'header_actions' => $isAuthed
                ? array_values(array_filter([
                    $user->is_admin
                        ? $this->link('Admin', '/admin', ['/admin'], 'text')
                        : null,
                    $user->isModerator()
                        ? $this->link('Panel moderatora', route('moderator.accounts.index', absolute: false), ['/moderator'], 'text')
                        : null,
                    $this->link('Profil', route('profile.edit', absolute: false), ['/profile'], 'text'),
                ]))
                : [
                    $this->link('Zaloguj się', route('login', absolute: false), ['/login'], 'text'),
                    $this->link('Zarejestruj się', route('register', absolute: false), ['/register'], 'primary'),
                ],
            'logout_href' => $isAuthed ? route('logout', absolute: false) : null,
            'learning_href' => $learningHref,
            'footer_groups' => [
                [
                    'title' => 'Nauka',
                    'links' => [
                        $this->link('Testy online', route('public.tests', absolute: false), ['/testy-na-prawo-jazdy']),
                        $this->link('Kurs teorii Online', route('public.course', absolute: false), ['/kurs']),
                        $this->link('Wykłady z instruktorem Online', route('public.lectures', absolute: false), ['/wyklady']),
                        $this->link('Kod 95 — kierowca zawodowy', route('public.code95', absolute: false), ['/kurs-kod-95']),
                        $this->link('Rozpocznij naukę', $learningHref, [$learningHref]),
                        $this->link('Baza pytań', route('public.questions.hub', absolute: false), ['/oficjalna-baza-pytan-na-prawo-jazdy', '/pytanie']),
                    ],
                ],
                [
                    'title' => 'Serwis',
                    'links' => [
                        $this->link('Strefa OSK', route('public.osk', absolute: false), ['/strefa-osk']),
                        $this->link('Aplikacje', '/#aplikacje', []),
                        $this->link('Znaki drogowe', route('traffic-signs.index', absolute: false), ['/znaki-drogowe']),
                        $this->link('Statystyki', route('public.statistics', absolute: false), ['/statystyki']),
                        $this->link(
                            'Najtrudniejsze pytania',
                            route('public.hardest-questions.index', absolute: false),
                            ['/najtrudniejsze-pytania-na-prawo-jazdy'],
                        ),
                        $this->link('Cennik', route('public.pricing', absolute: false), ['/cennik']),
                    ],
                ],
                [
                    'title' => 'Informacje',
                    'links' => [
                        $this->link('Dlaczego my?', '/#home-learning-title', []),
                        $this->link('O nas', route('about.organization', absolute: false), ['/o-nas', '/autorzy']),
                        $this->link('Jak to działa', route('about.how-it-works', absolute: false), ['/jak-to-dziala']),
                        $this->link('Kontakt', route('about.contact', absolute: false), ['/kontakt']),
                        $this->link('Metodologia', route('about.methodology', absolute: false), ['/metodologia']),
                    ],
                ],
                [
                    'title' => 'Konto',
                    'links' => $isAuthed
                        ? array_values(array_filter([
                            $this->link('Profil', route('profile.edit', absolute: false), ['/profile']),
                            $user->is_admin ? $this->link('Admin', '/admin', ['/admin']) : null,
                            $user->isModerator() ? $this->link('Panel moderatora', route('moderator.accounts.index', absolute: false), ['/moderator']) : null,
                            $this->link('Nauka', '/nauka', ['/nauka', '/study-sessions', '/trener-pamieci']),
                        ]))
                        : [
                            $this->link('Logowanie', route('login', absolute: false), ['/login']),
                            $this->link('Rejestracja', route('register', absolute: false), ['/register']),
                            $this->link('Testy', route('public.tests', absolute: false), [route('public.tests', absolute: false)]),
                        ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function link(
        string $label,
        string $href,
        array $match,
        ?string $variant = null,
        ?string $icon = null,
    ): array {
        $link = [
            'label' => $label,
            'href' => $href,
            'match' => $match,
        ];

        if ($variant !== null) {
            $link['variant'] = $variant;
        }

        if ($icon !== null) {
            $link['icon'] = $icon;
        }

        return $link;
    }
}
