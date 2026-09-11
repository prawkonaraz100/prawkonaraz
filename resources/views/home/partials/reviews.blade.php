@php
    $reviewErrors = $errors->getBag('review');
    $reviewCount = (int) $reviewStats['count'];
@endphp

<section class="home-reviews" id="opinie" aria-labelledby="home-reviews-title">
    <div class="home-entry__shell">
        <header class="home-reviews__intro" data-home-reveal>
            @if ($reviewCount > 0)
                <h2 id="home-reviews-title">
                    @if ($reviewCount === 1)
                        <span>1</span> pierwsza pozytywna opinia od naszego zadowolonego użytkownika.
                    @else
                        <span>{{ number_format($reviewCount, 0, ',', ' ') }}</span> pierwszych pozytywnych opinii od naszych zadowolonych użytkowników.
                    @endif
                </h2>
            @else
                <h2 id="home-reviews-title">Opinie użytkowników PrawkoNaRaz</h2>
            @endif
        </header>

        @if (session('review_success'))
            <div class="home-reviews__notice home-reviews__notice--success" role="status" data-home-reveal>
                {{ session('review_success') }}
            </div>
        @endif

        @if ($publishedReviews->isNotEmpty())
            <div class="home-reviews__carousel" data-home-reveal>
                <button
                    class="home-reviews__arrow home-reviews__arrow--previous"
                    type="button"
                    aria-label="Pokaż poprzednie opinie"
                    data-home-reviews-scroll="previous"
                >
                    <span aria-hidden="true">←</span>
                </button>

                <div class="home-reviews__rail" aria-label="Opinie użytkowników PrawkoNaRaz" data-home-reviews-rail>
                    @foreach ($publishedReviews as $review)
                        <article class="home-reviews__card{{ $review['photo_url'] ? ' home-reviews__card--with-proof' : '' }}">
                            <header>
                                <div>
                                    <h3>{{ $review['name'] }}</h3>
                                    <time>{{ $review['published_label'] }}</time>
                                </div>
                                <img
                                    class="home-reviews__source-mark"
                                    src="{{ asset('images/site-brand-mark-shield-v2.png') }}"
                                    alt=""
                                    width="1200"
                                    height="1200"
                                    loading="lazy"
                                    decoding="async"
                                    aria-hidden="true"
                                >
                            </header>
                            <div class="home-reviews__stars" aria-label="Ocena {{ $review['rating'] }} z 5">
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
                                <div class="home-reviews__card-footer">
                                    @if ($review['social_links'] !== [])
                                        <nav class="home-reviews__socials" aria-label="Linki użytkownika {{ $review['name'] }}">
                                            @foreach ($review['social_links'] as $socialLink)
                                                <a
                                                    class="home-reviews__social home-reviews__social--{{ $socialLink['platform'] }}"
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
                                            class="home-reviews__proof"
                                            href="{{ $review['photo_url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="Zobacz zdjęcie potwierdzające zdany egzamin — {{ $review['name'] }}"
                                        >
                                            <img src="{{ $review['photo_url'] }}" alt="Zdany egzamin — zdjęcie użytkownika" loading="lazy">
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                <button
                    class="home-reviews__arrow home-reviews__arrow--next"
                    type="button"
                    aria-label="Pokaż kolejne opinie"
                    data-home-reviews-scroll="next"
                >
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        @else
            <div class="home-reviews__empty" data-home-reveal>
                <span aria-hidden="true">★</span>
                <p>Pierwsze opinie tworzymy razem. Opowiedz innym, jak uczy Ci się z PrawkoNaRaz.</p>
            </div>
        @endif

        <div class="home-reviews__actions" data-home-reveal>
            <a class="home-reviews__primary-action" href="{{ route('reviews.index', absolute: false) }}">
                Zobacz wszystkie opinie
                <span aria-hidden="true">→</span>
            </a>

            @auth
                @if ($reviewErrors->any())
                    <div class="home-reviews__notice home-reviews__notice--error" role="alert">
                        Nie zapisaliśmy opinii. Sprawdź oznaczone pola i spróbuj ponownie.
                    </div>
                @endif

                @if ($currentReview)
                    <span class="home-reviews__status home-reviews__status--{{ $currentReview->status }}">
                        Twoja opinia: {{ mb_strtolower($currentReview->statusLabel()) }}
                    </span>
                @endif

                <details class="home-reviews__add" @if ($reviewErrors->any()) open @endif>
                    <summary>
                        {{ $currentReview ? 'Edytuj swoją opinię' : 'Dodaj swoją opinię' }}
                        <span aria-hidden="true">→</span>
                    </summary>

                    <div class="home-reviews__form-shell">
                        <div class="home-reviews__form-intro">
                            <h3>{{ $currentReview ? 'Zaktualizuj swoją opinię' : 'Podziel się swoim doświadczeniem' }}</h3>
                            <p>
                                Publicznie pokażemy Twoje imię oraz pierwszą literę nazwiska — nigdy adres e-mail.
                                Opinia pojawi się po zatwierdzeniu.
                            </p>
                        </div>

                        @include('reviews.partials.form', [
                            'currentReview' => $currentReview,
                            'formIdPrefix' => 'home-review',
                            'returnTo' => 'home',
                        ])
                    </div>
                </details>
            @else
                <p class="home-reviews__account-links">
                    <span>Masz konto? <a href="{{ route('login', absolute: false) }}">Zaloguj się i dodaj opinię</a>.</span>
                    <span>Nie masz konta? <a href="{{ route('register', absolute: false) }}">Załóż je bezpłatnie</a>.</span>
                </p>
            @endauth
        </div>
    </div>
</section>
