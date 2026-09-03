# Google Analytics Implementation

Status: implemented in code and enabled on production as of 2026-07-10.

## What We Added

- Google Analytics is configured through `config/services.php` under `services.google_analytics`.
- The Google tag is injected into:
  - `resources/views/layouts/public-content.blade.php` for public SEO and marketing pages,
  - `resources/views/app.blade.php` for the Inertia application shell.
- The tag is not added to standalone signed profile confirmation pages, so sensitive account-confirmation URLs are not measured.
- A small first-party consent banner is rendered when analytics is enabled and `GOOGLE_ANALYTICS_CONSENT_REQUIRED=true`.

## Runtime Behavior

1. If `GOOGLE_ANALYTICS_ENABLED=false` or `GOOGLE_ANALYTICS_MEASUREMENT_ID` is empty, no Google Analytics code is rendered.
2. If consent is required, the page sets Google Consent Mode defaults before any GA config call.
3. The external `gtag.js` script loads only after the visitor accepts analytics consent.
4. The choice is persisted in `localStorage` under `prawkonaraz.analyticsConsent.v1`.
5. If consent is not required, the tag loads immediately.

## Environment Variables

```env
GOOGLE_ANALYTICS_ENABLED=false
GOOGLE_ANALYTICS_MEASUREMENT_ID=
GOOGLE_ANALYTICS_CONSENT_REQUIRED=true
GOOGLE_ANALYTICS_DEBUG_MODE=false
GOOGLE_ANALYTICS_COOKIE_FLAGS=SameSite=Lax;Secure
```

Production activation uses:

```env
GOOGLE_ANALYTICS_ENABLED=true
GOOGLE_ANALYTICS_MEASUREMENT_ID=G-4DCKQ3BRKE
GOOGLE_ANALYTICS_CONSENT_REQUIRED=true
```

After changing production env values, clear cached config:

```bash
php artisan config:clear
php artisan optimize:clear
```

## Verification

- Before setting the Measurement ID, production should not contain `googletagmanager.com/gtag/js`.
- After setting the Measurement ID and accepting the banner, the browser should load `https://www.googletagmanager.com/gtag/js?id=G-...`.
- Google Analytics Realtime should show visits after consent is accepted.

## Notes

- Inertia page changes are left to GA4 Enhanced Measurement/history tracking to avoid duplicate `page_view` events from both GA and our own router listener.
- If we later disable Enhanced Measurement history tracking in GA4, we can add explicit Inertia pageview events in `resources/js/app.ts`.
