@php
    $reviewErrors = $errors->getBag('review');
    $selectedReviewRating = (int) old('rating', $currentReview?->rating ?? 5);
    $formIdPrefix = $formIdPrefix ?? 'review';
    $returnTo = $returnTo ?? 'reviews';
@endphp

<form
    class="home-reviews__form"
    method="POST"
    action="{{ route('reviews.store', absolute: false) }}"
    enctype="multipart/form-data"
>
    @csrf
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    <fieldset>
        <legend>Jak oceniasz PrawkoNaRaz?</legend>
        <div class="home-reviews__rating">
            @for ($rating = 5; $rating >= 1; $rating--)
                <input
                    id="{{ $formIdPrefix }}-rating-{{ $rating }}"
                    type="radio"
                    name="rating"
                    value="{{ $rating }}"
                    @checked($selectedReviewRating === $rating)
                >
                <label for="{{ $formIdPrefix }}-rating-{{ $rating }}" title="{{ $rating }} z 5">
                    <span aria-hidden="true">★</span>
                    <span class="sr-only">{{ $rating }} z 5</span>
                </label>
            @endfor
        </div>
        @error('rating', 'review')
            <p class="home-reviews__error">{{ $message }}</p>
        @enderror
    </fieldset>

    <label for="{{ $formIdPrefix }}-content">Twoja opinia</label>
    <textarea
        id="{{ $formIdPrefix }}-content"
        name="content"
        rows="5"
        minlength="3"
        maxlength="500"
        required
        placeholder="Napisz kilka zdań o nauce z PrawkoNaRaz…"
    >{{ old('content', $currentReview?->content) }}</textarea>
    <div class="home-reviews__form-meta">
        <span>Od 3 do 500 znaków</span>
        <span>Opinia pojawi się po zatwierdzeniu</span>
    </div>
    @error('content', 'review')
        <p class="home-reviews__error">{{ $message }}</p>
    @enderror

    <fieldset class="home-reviews__social-field">
        <legend>Twoje linki <span>opcjonalnie</span></legend>
        <p>Dodaj profile, pod którymi inni użytkownicy mogą Cię znaleźć.</p>

        <div class="home-reviews__social-inputs">
            @foreach ([
                'facebook' => ['Facebook', 'facebook.com/twojprofil'],
                'instagram' => ['Instagram', 'instagram.com/twojprofil'],
                'tiktok' => ['TikTok', 'tiktok.com/@twojprofil'],
                'youtube' => ['YouTube', 'youtube.com/@twojkanal'],
                'website' => ['Strona WWW', 'twojastrona.pl'],
            ] as $platform => [$label, $placeholder])
                <label for="{{ $formIdPrefix }}-social-{{ $platform }}">
                    <span>{{ $label }}</span>
                    <input
                        id="{{ $formIdPrefix }}-social-{{ $platform }}"
                        type="text"
                        name="social_links[{{ $platform }}]"
                        value="{{ old("social_links.{$platform}", $currentReview?->social_links[$platform] ?? '') }}"
                        maxlength="255"
                        inputmode="url"
                        autocomplete="url"
                        placeholder="{{ $placeholder }}"
                    >
                </label>
                @error("social_links.{$platform}", 'review')
                    <p class="home-reviews__error">{{ $message }}</p>
                @enderror
            @endforeach
        </div>
    </fieldset>

    <div class="home-reviews__photo-field">
        <label for="{{ $formIdPrefix }}-photo">Zdjęcie potwierdzające zdany egzamin <span>opcjonalnie</span></label>
        <p>Przed wysłaniem samodzielnie zasłoń dane osobowe i informacje, których nie chcesz publikować.</p>

        @if ($currentReview?->photo_path)
            <div class="home-reviews__current-photo">
                <img
                    src="{{ route('reviews.photo', ['review' => $currentReview], absolute: false) }}"
                    alt="Obecne zdjęcie dołączone do opinii"
                    loading="lazy"
                >
                <label>
                    <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
                    Usuń obecne zdjęcie
                </label>
            </div>
        @endif

        <input
            id="{{ $formIdPrefix }}-photo"
            type="file"
            name="photo"
            accept="image/jpeg,image/png"
        >
        <small>JPG lub PNG, maksymalnie 5 MB. Nowe zdjęcie zastąpi obecne.</small>
        @error('photo', 'review')
            <p class="home-reviews__error">{{ $message }}</p>
        @enderror

        <label class="home-reviews__privacy-confirmation">
            <input
                type="checkbox"
                name="photo_privacy_confirmed"
                value="1"
                @checked(old('photo_privacy_confirmed'))
            >
            <span>Potwierdzam, że przed przesłaniem zdjęcia zasłoniłem(-am) dane osobowe i inne informacje, których nie chcę publikować.</span>
        </label>
        @error('photo_privacy_confirmed', 'review')
            <p class="home-reviews__error">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit">
        {{ $currentReview ? 'Zapisz zmiany' : 'Wyślij opinię' }}
        <span aria-hidden="true">→</span>
    </button>
</form>
