<?php

namespace App\Http\Controllers;

use App\Support\NewsroomHomeReadModelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class NewsroomPlaceholderController extends Controller
{
    public function news(Request $request, NewsroomHomeReadModelService $readModelService): Response
    {
        $home = $readModelService->build();

        if ($home === null) {
            return $this->placeholder(
                $request,
                'Aktualności',
                'Serwis',
                'Tu pojawią się aktualności dla kandydatów, kursantów i instruktorów prawa jazdy.',
            );
        }

        return response()->view('newsroom.home', [
            'home' => $home,
            'meta' => [
                'title' => 'Aktualności o prawie jazdy, egzaminach i przepisach | prawkonaraz.pl',
                'description' => 'Aktualności, wyjaśnienia i poradniki o prawie jazdy, egzaminach, przepisach, WORD i bezpieczeństwie ruchu.',
                'canonical' => route('public.news'),
                'robots' => 'index,follow,max-image-preview:large',
            ],
        ]);
    }

    public function guides(Request $request): Response
    {
        return $this->placeholder(
            $request,
            'Poradniki',
            'Nauka',
            'W poradnikach zbierzemy praktyczne materiały pomagające przejść od teorii do pewnego wyniku na egzaminie.',
        );
    }

    private function placeholder(
        Request $request,
        string $title,
        string $eyebrow,
        string $description,
    ): Response {
        $response = Inertia::render('Public/MarketingPlaceholder', [
            'title' => $title,
            'eyebrow' => $eyebrow,
            'description' => $description,
        ])->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }
}
