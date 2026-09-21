<?php

namespace App\Http\Controllers;

use App\Models\UserReview;
use App\Support\UserReviewPresenter;
use Illuminate\Contracts\View\View;

class ReviewPageController extends Controller
{
    public function __invoke(UserReviewPresenter $presenter): View
    {
        $query = UserReview::query()->publiclyVisible();
        $stats = (clone $query)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();
        $reviews = (clone $query)
            ->with(['user.socialAccounts'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (UserReview $review): array => $presenter->payload($review));
        $canonical = route('reviews.index');
        $description = 'Poznaj opinie użytkowników PrawkoNaRaz o nauce, testach i przygotowaniu do egzaminu teoretycznego na prawo jazdy.';

        return view('reviews.index', [
            'meta' => [
                'title' => 'Opinie o PrawkoNaRaz – doświadczenia użytkowników',
                'description' => $description,
                'canonical' => $canonical,
            ],
            'structuredData' => [[
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Opinie o PrawkoNaRaz',
                'url' => $canonical,
                'description' => $description,
                'inLanguage' => 'pl-PL',
            ]],
            'reviews' => $reviews,
            'reviewStats' => [
                'count' => (int) ($stats?->total ?? 0),
                'average' => $stats?->average !== null
                    ? number_format((float) $stats->average, 1, ',', ' ')
                    : null,
            ],
            'currentReview' => auth()->user()?->review()->first(),
        ]);
    }
}
