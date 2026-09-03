@php
    $measurementId = trim((string) config('services.google_analytics.measurement_id', ''));
    $enabled = (bool) config('services.google_analytics.enabled') && $measurementId !== '';
    $consentRequired = (bool) config('services.google_analytics.consent_required', true);
    $cookieFlags = trim((string) config('services.google_analytics.cookie_flags', 'SameSite=Lax;Secure'));
    $debugMode = (bool) config('services.google_analytics.debug_mode', false);
    $analyticsConfig = [
        'measurementId' => $measurementId,
        'consentRequired' => $consentRequired,
        'cookieFlags' => $cookieFlags !== '' ? $cookieFlags : null,
        'debugMode' => $debugMode,
        'storageKey' => 'prawkonaraz.analyticsConsent.v1',
        'bannerId' => 'google-analytics-consent',
    ];
@endphp

@if ($enabled)
    @if ($consentRequired)
        <style>
            .analytics-consent {
                position: fixed;
                right: 0;
                bottom: 0;
                z-index: 60;
                width: min(150px, calc(100vw - 8px));
                height: min(150px, calc(100vw - 8px));
                overflow: visible;
                border-top: 3px solid #d01921;
                border-left: 3px solid #d01921;
                border-radius: 999px 0 0 0;
                background: #ffffff;
                box-shadow: -8px -10px 24px rgba(17, 24, 39, 0.14), 0 0 8px rgba(208, 25, 33, 0.18);
                color: #111827;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                padding: 58px 6px 4px 36px;
                text-align: center;
            }

            .analytics-consent__mascot {
                position: absolute;
                right: 18px;
                bottom: 116px;
                z-index: 1;
                display: block;
                width: 72px;
                height: auto;
                filter: drop-shadow(0 8px 10px rgba(17, 24, 39, 0.16));
                pointer-events: none;
                transform-origin: 50% 100%;
                animation: analytics-consent-mascot-hop 5.8s ease-in-out infinite;
            }

            .analytics-consent__mascot-image {
                display: block;
                width: 100%;
                height: auto;
                transform-origin: 50% 86%;
                animation: analytics-consent-mascot-breathe 3.4s ease-in-out infinite;
            }

            @keyframes analytics-consent-mascot-hop {
                0%,
                61%,
                100% {
                    transform: translate3d(0, 0, 0) rotate(0deg);
                }

                66% {
                    transform: translate3d(0, -5px, 0) rotate(-2deg);
                }

                71% {
                    transform: translate3d(0, 0, 0) rotate(1.2deg);
                }

                76% {
                    transform: translate3d(0, -2px, 0) rotate(-0.8deg);
                }

                81% {
                    transform: translate3d(0, 0, 0) rotate(0deg);
                }
            }

            @keyframes analytics-consent-mascot-breathe {
                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.035);
                }
            }

            .analytics-consent[hidden] {
                display: none !important;
            }

            .analytics-consent__copy {
                position: relative;
                z-index: 2;
                margin: 0;
                font-size: 11.8px;
                line-height: 1.22;
                color: #24324a;
            }

            .analytics-consent__accent {
                color: #d01921;
                font-weight: 700;
            }

            .analytics-consent__actions {
                position: relative;
                z-index: 2;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 3px;
                margin-top: 5px;
            }

            .analytics-consent__button {
                min-height: 27px;
                border-radius: 999px;
                border: 0;
                padding: 0 10px;
                font-size: 12px;
                font-weight: 700;
                cursor: pointer;
                transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease;
            }

            .analytics-consent__button--secondary {
                min-height: auto;
                background: transparent;
                color: #4b5563;
                font-size: 10px;
                font-weight: 600;
                text-decoration: underline;
                text-underline-offset: 3px;
            }

            .analytics-consent__button--secondary:hover {
                background: transparent;
                color: #111827;
            }

            .analytics-consent__button--primary {
                background: #d01921;
                color: #ffffff;
            }

            .analytics-consent__button--primary:hover {
                background: #b9151d;
            }

            @media (prefers-reduced-motion: reduce) {
                .analytics-consent__mascot,
                .analytics-consent__mascot-image {
                    animation: none;
                }
            }
        </style>
    @endif

    <script>
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () {
            window.dataLayer.push(arguments);
        };

        window.gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: @js($consentRequired ? 'denied' : 'granted'),
        });
    </script>
    <script>
        (() => {
            const config = @js($analyticsConfig);

            if (!config.measurementId || typeof window.gtag !== 'function') {
                return;
            }

            const readStoredConsent = () => {
                try {
                    return window.localStorage.getItem(config.storageKey);
                } catch {
                    return null;
                }
            };

            const storeConsent = (value) => {
                try {
                    window.localStorage.setItem(config.storageKey, value);
                } catch {
                    // Analytics still follows the current-page choice when storage is unavailable.
                }
            };

            const hideBanner = () => {
                document.getElementById(config.bannerId)?.setAttribute('hidden', 'hidden');
            };

            const loadGoogleTag = () => {
                const selector = `script[data-google-analytics-tag="${config.measurementId}"]`;

                if (document.querySelector(selector)) {
                    return;
                }

                const script = document.createElement('script');
                script.async = true;
                script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.measurementId)}`;
                script.dataset.googleAnalyticsTag = config.measurementId;
                document.head.appendChild(script);
            };

            const configureGoogleTag = () => {
                if (window.__prawkonarazGoogleAnalyticsConfigured === config.measurementId) {
                    return;
                }

                window.__prawkonarazGoogleAnalyticsConfigured = config.measurementId;
                window.gtag('js', new Date());

                const tagConfig = {};

                if (config.cookieFlags) {
                    tagConfig.cookie_flags = config.cookieFlags;
                }

                if (config.debugMode) {
                    tagConfig.debug_mode = true;
                }

                window.gtag('config', config.measurementId, tagConfig);
            };

            const grantAnalyticsConsent = () => {
                window.gtag('consent', 'update', {
                    analytics_storage: 'granted',
                });
                loadGoogleTag();
                configureGoogleTag();
            };

            if (!config.consentRequired) {
                grantAnalyticsConsent();
                return;
            }

            const storedConsent = readStoredConsent();

            if (storedConsent === 'granted') {
                grantAnalyticsConsent();
                return;
            }

            if (storedConsent === 'denied') {
                hideBanner();
                return;
            }

            const setupBanner = () => {
                const banner = document.getElementById(config.bannerId);

                if (!banner) {
                    return;
                }

                banner.removeAttribute('hidden');

                banner
                    .querySelector('[data-google-analytics-consent="grant"]')
                    ?.addEventListener('click', () => {
                        storeConsent('granted');
                        grantAnalyticsConsent();
                        hideBanner();
                    });

                banner
                    .querySelector('[data-google-analytics-consent="deny"]')
                    ?.addEventListener('click', () => {
                        storeConsent('denied');
                        hideBanner();
                    });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupBanner, { once: true });
                return;
            }

            setupBanner();
        })();
    </script>
@endif
