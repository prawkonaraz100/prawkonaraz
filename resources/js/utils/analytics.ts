type AnalyticsEventParameters = Record<string, string | number | boolean | null>;

declare global {
    interface Window {
        __prawkonarazGoogleAnalyticsConfigured?: string;
        gtag?: (...args: unknown[]) => void;
    }
}

export const trackAnalyticsEvent = (
    name: string,
    parameters: AnalyticsEventParameters = {},
): void => {
    if (
        typeof window === 'undefined' ||
        !window.__prawkonarazGoogleAnalyticsConfigured ||
        typeof window.gtag !== 'function'
    ) {
        return;
    }

    window.gtag('event', name, parameters);
};
