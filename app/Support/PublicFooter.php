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
            'groups' => $navigation['footer_groups'],
            'copyright' => '© '.now()->year.' prawkonaraz.pl',
        ];
    }
}
