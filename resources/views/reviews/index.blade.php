@extends('layouts.public-content')

@push('styles')
    @vite('resources/css/home.css')
@endpush

@push('scripts')
    @vite('resources/js/home.ts')
@endpush

@section('content')
    <section class="reviews-page__hero">
        <div class="reviews-page__shell">
            <div class="reviews-page__hero-heading-row">
                <h1>Użytkownicy, którzy pomagają rozwijać PrawkoNaRaz</h1>
                <span class="reviews-page__hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 17.5 3.8 20V6.8A2.8 2.8 0 0 1 6.6 4h10.8a2.8 2.8 0 0 1 2.8 2.8v7.9a2.8 2.8 0 0 1-2.8 2.8H7Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="m12 7.4 1.1 2.2 2.4.4-1.7 1.7.4 2.4-2.2-1.1-2.2 1.1.4-2.4L8.5 10l2.4-.4L12 7.4Z" fill="currentColor"/>
                    </svg>
                </span>
            </div>

            @if ($reviewStats['count'] > 0)
                <dl class="reviews-page__stats" aria-label="Podsumowanie opinii">
                    <div>
                        <dt>{{ number_format($reviewStats['count'], 0, ',', ' ') }}</dt>
                        <dd>opinii</dd>
                    </div>
                    <div>
                        <dt>{{ $reviewStats['average'] }} / 5</dt>
                        <dd>średnia ocena</dd>
                    </div>
                </dl>
            @endif

            <div class="reviews-page__intro">
                <p>
                    PrawkoNaRaz rozwija się dzięki Wam! Wasze opinie i doświadczenia pomagają nam stale udoskonalać platformę,
                    a nowym kursantom ułatwiają wybór najszybszej i najbardziej efektywnej ścieżki nauki.
                </p>
                <p>
                    Dziękujemy, że dzielicie się z nami swoimi uwagami i wspólnie tworzycie najlepsze oprogramowanie do nauki
                    do egzaminu teoretycznego!
                </p>
            </div>
        </div>
    </section>

    <section class="reviews-page__content" aria-label="Wszystkie opublikowane opinie">
        <div class="reviews-page__shell">
            @if ($reviews->isNotEmpty())
                <div class="reviews-page__grid">
                    @foreach ($reviews as $review)
                        <article class="reviews-page__card{{ $review['photo_url'] ? ' reviews-page__card--with-proof' : '' }}">
                            <header>
                                <div>
                                    <h2>{{ $review['name'] }}</h2>
                                    <time>{{ $review['published_label'] }}</time>
                                </div>
                                <img
                                    src="{{ asset('images/site-brand-mark-shield-v2.png') }}"
                                    width="256"
                                    height="256"
                                    alt=""
                                    aria-hidden="true"
                                    loading="lazy"
                                >
                            </header>
                            <div class="reviews-page__stars" aria-label="Ocena {{ $review['rating'] }} z 5">
                                @for ($star = 1; $star <= 5; $star++)
                                    <span class="{{ $star <= $review['rating'] ? 'is-filled' : '' }}" aria-hidden="true">★</span>
                                @endfor
                            </div>
                            @if ($review['content_has_more'])
                                <details class="review-card-copy">
                                    <summary>
                                        <span class="review-card-copy__preview">{{ $review['content_preview'] }}</span>
                                        <span class="review-card-copy__more">Zobacz więcej</span>
                                        <span class="review-card-copy__less">Zwiń</span>
                                    </summary>
                                    <p>{{ $review['content'] }}</p>
                                </details>
                            @else
                                <p>{{ $review['content'] }}</p>
                            @endif

                            @if ($review['photo_url'] || $review['social_links'] !== [])
                                <div class="reviews-page__card-footer">
                                @if ($review['social_links'] !== [])
                                    <nav class="reviews-page__socials" aria-label="Linki użytkownika {{ $review['name'] }}">
                                    @foreach ($review['social_links'] as $socialLink)
                                        <a
                                            class="reviews-page__social reviews-page__social--{{ $socialLink['platform'] }}"
                                            href="{{ $socialLink['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer nofollow ugc"
                                            aria-label="{{ $socialLink['label'] }} — profil użytkownika {{ $review['name'] }}"
                                        >
                                            @switch($socialLink['platform'])
                                                @case('facebook')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <circle cx="12" cy="12" r="11" fill="#1877f2"/>
                                                        <path d="M13.5 20v-7h2.4l.4-2.8h-2.8V8.4c0-.8.2-1.4 1.4-1.4h1.5V4.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.8 1.4-3.8 3.9v1.9H8V13h2.4v7h3.1Z" fill="#fff"/>
                                                    </svg>
                                                    @break
                                                @case('instagram')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <rect x="2" y="2" width="20" height="20" rx="6" fill="#e4405f"/>
                                                        <rect x="6.2" y="6.2" width="11.6" height="11.6" rx="3.7" fill="none" stroke="#fff" stroke-width="1.8"/>
                                                        <circle cx="12" cy="12" r="2.8" fill="none" stroke="#fff" stroke-width="1.8"/>
                                                        <circle cx="16.3" cy="7.8" r="1" fill="#fff"/>
                                                    </svg>
                                                    @break
                                                @case('tiktok')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M14.2 3v10.2a4.4 4.4 0 1 1-3.7-4.3v2.8a1.7 1.7 0 1 0 1 1.5V3h2.7Zm0 0c.5 2.3 1.8 3.7 4.2 4.2V10a7.8 7.8 0 0 1-4.2-1.6" fill="none" stroke="#0b0b0f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    @break
                                                @case('youtube')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <rect x="1.5" y="4.5" width="21" height="15" rx="4.5" fill="#ff0033"/>
                                                        <path d="m10 9 6 3-6 3V9Z" fill="#fff"/>
                                                    </svg>
                                                    @break
                                                @case('website')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                        <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"></path>
                                                    </svg>
                                                    @break
                                            @endswitch
                                            <span class="sr-only">{{ $socialLink['label'] }}</span>
                                        </a>
                                    @endforeach
                                    </nav>
                                @endif

                                @if ($review['photo_url'])
                                    <a
                                        class="reviews-page__photo"
                                        href="{{ $review['photo_url'] }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Powiększ zdjęcie potwierdzające zdany egzamin — {{ $review['name'] }}"
                                    >
                                        <img src="{{ $review['photo_url'] }}" alt="Zdany egzamin — zdjęcie użytkownika" loading="lazy">
                                    </a>
                                @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if ($reviews->hasPages())
                    <div class="reviews-page__pagination">
                        {{ $reviews->links() }}
                    </div>
                @endif
            @else
                <div class="reviews-page__empty">
                    <h2>Bądź pierwszą osobą, która podzieli się opinią</h2>
                    <p>Po zatwierdzeniu Twoja opinia pojawi się na tej stronie.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="reviews-page__contribution" id="dodaj-opinie" aria-labelledby="reviews-contribution-title">
        <div class="reviews-page__shell reviews-page__contribution-grid">
            <div>
                <h2 id="reviews-contribution-title">
                    {{ $currentReview ? 'Edytuj swoją opinię' : 'Dodaj swoją opinię' }}
                </h2>
                <p>
                    Możesz dołączyć zdjęcie potwierdzające zdany egzamin. Przed wysłaniem samodzielnie zasłoń dane,
                    których nie chcesz publikować. Każda nowa lub zmieniona opinia przechodzi moderację.
                </p>

                @if (session('review_success'))
                    <div class="home-reviews__notice home-reviews__notice--success" role="status">
                        {{ session('review_success') }}
                    </div>
                @endif

                @if ($errors->getBag('review')->any())
                    <div class="home-reviews__notice home-reviews__notice--error" role="alert">
                        Nie zapisaliśmy opinii. Sprawdź oznaczone pola i spróbuj ponownie.
                    </div>
                @endif

                @if ($currentReview)
                    <span class="home-reviews__status home-reviews__status--{{ $currentReview->status }}">
                        Twoja opinia: {{ mb_strtolower($currentReview->statusLabel()) }}
                    </span>
                @endif
            </div>

            <div class="reviews-page__form-card">
                @auth
                    @include('reviews.partials.form', [
                        'currentReview' => $currentReview,
                        'formIdPrefix' => 'reviews-page-review',
                        'returnTo' => 'reviews',
                    ])
                @else
                    <div class="reviews-page__guest-intro">
                        <h3>Zaloguj się, aby dodać opinię</h3>
                        <p>Opinię może dodać każda osoba posiadająca konto PrawkoNaRaz.</p>
                    </div>
                    <div class="reviews-page__guest-actions">
                        <a class="home-reviews__primary-action reviews-page__guest-action" href="{{ route('login', absolute: false) }}">
                            Zaloguj się i dodaj opinię <span aria-hidden="true">→</span>
                        </a>
                        <a class="home-reviews__primary-action reviews-page__guest-action" href="{{ route('register', absolute: false) }}">
                            Załóż konto bezpłatnie <span aria-hidden="true">→</span>
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </section>
@endsection
