import { trackAnalyticsEvent } from '../utils/analytics';

const ANALYTICS_READY_EVENT = 'prawkonaraz:analytics-ready';
const ARTICLE_CONTEXT_SELECTOR = '[data-newsroom-analytics-article]';
const EXPLICIT_EVENT_SELECTOR = 'a[data-newsroom-analytics-event]';
const MODULE_CARD_SELECTOR = '[data-newsroom-analytics-module][data-article-id][data-article-url]';
const PAGINATION_SELECTOR = '[data-analytics-module="pagination"] a[href]';

type AnalyticsContext = {
    article_id?: number;
    article_type?: string;
    category_slug?: string;
};

type AnalyticsParameters = AnalyticsContext & {
    module?: string;
    position?: string | number;
    destination_path?: string;
};

const cleanValue = (value: string | undefined): string | undefined => {
    const normalized = value?.trim();

    return normalized ? normalized : undefined;
};

const parseArticleId = (value: string | undefined): number | undefined => {
    if (!value) {
        return undefined;
    }

    const articleId = Number.parseInt(value, 10);

    return Number.isSafeInteger(articleId) && articleId > 0 ? articleId : undefined;
};

const destinationPath = (href: string | null): string | undefined => {
    if (!href) {
        return undefined;
    }

    try {
        return new URL(href, window.location.origin).pathname;
    } catch {
        return undefined;
    }
};

const analyticsReady = (): boolean => (
    typeof window !== 'undefined'
    && Boolean(window.__prawkonarazGoogleAnalyticsConfigured)
    && typeof window.gtag === 'function'
);

const contextFromElement = (element: HTMLElement | null): AnalyticsContext => {
    if (!element) {
        return {};
    }

    const articleId = parseArticleId(element.dataset.articleId);
    const articleType = cleanValue(element.dataset.articleType);
    const categorySlug = cleanValue(element.dataset.categorySlug);

    return {
        ...(articleId ? { article_id: articleId } : {}),
        ...(articleType ? { article_type: articleType } : {}),
        ...(categorySlug ? { category_slug: categorySlug } : {}),
    };
};

const pageArticleContext = (): AnalyticsContext => contextFromElement(
    document.querySelector<HTMLElement>(ARTICLE_CONTEXT_SELECTOR),
);

const track = (name: string, parameters: AnalyticsParameters): boolean => {
    if (!analyticsReady()) {
        return false;
    }

    trackAnalyticsEvent(name, parameters);

    return true;
};

const explicitClickParameters = (
    link: HTMLAnchorElement,
    context: AnalyticsContext,
): AnalyticsParameters => {
    const module = cleanValue(link.dataset.newsroomAnalyticsModule);
    const position = cleanValue(link.dataset.newsroomAnalyticsPosition);
    const path = destinationPath(link.getAttribute('href'));

    return {
        ...context,
        ...(module ? { module } : {}),
        ...(position ? { position } : {}),
        ...(path ? { destination_path: path } : {}),
    };
};

const setupArticleView = (): void => {
    const context = pageArticleContext();

    if (!context.article_id || !context.article_type) {
        return;
    }

    let sent = false;

    const send = () => {
        if (sent) {
            return;
        }

        sent = track('newsroom_article_view', context);
    };

    send();
    window.addEventListener(ANALYTICS_READY_EVENT, send);
};

const setupClickTracking = (): void => {
    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const link = target.closest<HTMLAnchorElement>('a[href]');

        if (!link || link.getAttribute('aria-disabled') === 'true') {
            return;
        }

        const explicitLink = link.matches(EXPLICIT_EVENT_SELECTOR) ? link : null;

        if (explicitLink) {
            const eventName = cleanValue(explicitLink.dataset.newsroomAnalyticsEvent);

            if (!eventName) {
                return;
            }

            track(eventName, explicitClickParameters(explicitLink, pageArticleContext()));

            return;
        }

        if (link.matches(PAGINATION_SELECTOR)) {
            const path = destinationPath(link.getAttribute('href'));
            const position = cleanValue(
                link.getAttribute('rel') ?? link.textContent ?? undefined,
            );

            track('newsroom_pagination_click', {
                ...(position ? { position } : {}),
                ...(path ? { destination_path: path } : {}),
            });

            return;
        }

        const card = link.closest<HTMLElement>(MODULE_CARD_SELECTOR);

        if (!card) {
            return;
        }

        const clickedPath = destinationPath(link.getAttribute('href'));
        const articlePath = destinationPath(card.dataset.articleUrl ?? null);

        if (!clickedPath || !articlePath || clickedPath !== articlePath) {
            return;
        }

        const module = cleanValue(card.dataset.newsroomAnalyticsModule);

        if (!module) {
            return;
        }

        const position = cleanValue(card.dataset.newsroomAnalyticsPosition);

        track('newsroom_module_click', {
            ...contextFromElement(card),
            module,
            ...(position ? { position } : {}),
            destination_path: clickedPath,
        });
    });
};

export const setupNewsroomAnalytics = (): void => {
    if (!document.querySelector(ARTICLE_CONTEXT_SELECTOR)
        && !document.querySelector('[data-newsroom-analytics-module]')
        && !document.querySelector(EXPLICIT_EVENT_SELECTOR)
        && !document.querySelector('[data-analytics-module="pagination"]')) {
        return;
    }

    setupArticleView();
    setupClickTracking();
};
