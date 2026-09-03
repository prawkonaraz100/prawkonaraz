@php
    $measurementId = trim((string) config('services.google_analytics.measurement_id', ''));
    $enabled = (bool) config('services.google_analytics.enabled') && $measurementId !== '';
    $consentRequired = (bool) config('services.google_analytics.consent_required', true);
@endphp

@if ($enabled && $consentRequired)
    <aside
        id="google-analytics-consent"
        class="analytics-consent"
        aria-label="Zgoda na Google Analytics"
        hidden
    >
        <picture
            class="analytics-consent__mascot"
            aria-hidden="true"
        >
            <source
                srcset="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/analytics/cookie-mascot-blink.webp') }}"
                type="image/webp"
            >
            <img
                class="analytics-consent__mascot-image"
                src="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/analytics/cookie-mascot.png') }}"
                alt=""
                loading="lazy"
                decoding="async"
            >
        </picture>
        <p class="analytics-consent__copy">
            Strona używa <span class="analytics-consent__accent">cookies</span>.
        </p>
        <div class="analytics-consent__actions">
            <button
                type="button"
                class="analytics-consent__button analytics-consent__button--primary"
                data-google-analytics-consent="grant"
            >
                Akceptuję
            </button>
            <button
                type="button"
                class="analytics-consent__button analytics-consent__button--secondary"
                data-google-analytics-consent="deny"
            >
                Nie teraz
            </button>
        </div>
    </aside>
@endif
