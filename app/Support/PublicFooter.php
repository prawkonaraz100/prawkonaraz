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
            'description' => 'Praktyczny trening pytań teoretycznych do prawa jazdy. Uczysz się, wracasz do błędów i sprawdzasz postęp bez zbędnego chaosu.',
            'primary_action' => [
                'label' => 'Rozpocznij naukę',
                'href' => $navigation['learning_href'],
            ],
            'secondary_action' => [
                'label' => 'Przejdź do bazy pytań',
                'href' => route('public.questions.hub', absolute: false),
            ],
            'legal_links' => [
                ['label' => 'O nas', 'href' => route('about.organization', absolute: false)],
                ['label' => 'Jak to działa', 'href' => route('about.how-it-works', absolute: false)],
                ['label' => 'Regulamin', 'href' => route('legal.terms', absolute: false)],
                ['label' => 'Polityka prywatności', 'href' => route('legal.privacy', absolute: false)],
                ['label' => 'Metodologia', 'href' => route('about.methodology', absolute: false)],
                ['label' => 'Kontakt', 'href' => route('about.contact', absolute: false)],
            ],
            'service_links' => [
                ['label' => 'Baza pytań', 'href' => route('public.questions.hub', absolute: false)],
                ['label' => 'Testy na prawo jazdy', 'href' => route('public.tests', absolute: false)],
                ['label' => 'Aktualności', 'href' => route('public.news', absolute: false)],
                ['label' => 'Poradniki', 'href' => route('public.guides', absolute: false)],
                ['label' => 'Kurs', 'href' => route('public.course', absolute: false)],
                ['label' => 'Cennik', 'href' => route('public.pricing', absolute: false)],
            ],
            'social_links' => [
                ['label' => 'Facebook', 'href' => 'https://www.facebook.com/PrawkoNaRaz/'],
                ['label' => 'Instagram', 'href' => 'https://www.instagram.com/prawkonaraz.pl/'],
                ['label' => 'TikTok', 'href' => 'https://www.tiktok.com/@prawkonaraz'],
                ['label' => 'YouTube', 'href' => 'https://www.youtube.com/channel/UCSrCDt_Aj1yslMY8sfXFBkg'],
            ],
            'language' => 'Polski',
            'groups' => $navigation['footer_groups'],
            'copyright' => '© '.now()->year,
        ];
    }
}
