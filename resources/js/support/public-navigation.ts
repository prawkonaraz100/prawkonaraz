import { Link } from '@inertiajs/vue3';

export const documentNavigationPrefixes = [
    '/',
    '/admin',
    '/aktualnosci',
    '/autorzy',
    '/cennik',
    '/jak-to-dziala',
    '/kontakt',
    '/kurs',
    '/metodologia',
    '/najtrudniejsze-pytania-na-prawo-jazdy',
    '/o-nas',
    '/oficjalna-baza-pytan-na-prawo-jazdy',
    '/poradniki',
    '/przepisy',
    '/pytanie',
    '/reklama',
    '/spolecznosc',
    '/statystyki',
    '/szkolenia-z-instruktorem',
    '/testy-na-prawo-jazdy',
    '/wyklady',
    '/znaki-drogowe',
];

export const linkPath = (href: string) => {
    if (href.startsWith('http://') || href.startsWith('https://')) {
        try {
            return new URL(href).pathname || '/';
        } catch {
            return href;
        }
    }

    return href.split('?')[0] || '/';
};

export const requiresDocumentNavigation = (href: string) => {
    const path = linkPath(href);

    return documentNavigationPrefixes.some((prefix) =>
        path === prefix || path.startsWith(`${prefix}/`),
    );
};

export const navigationComponent = (href: string) =>
    requiresDocumentNavigation(href) ? 'a' : Link;

export const isLoginHref = (href: string) => linkPath(href) === '/login';

export const isRegisterHref = (href: string) => linkPath(href) === '/register';

export const matchesPath = (currentPath: string, matchPaths: string[]) =>
    matchPaths.some((matchPath) =>
        currentPath === matchPath || currentPath.startsWith(`${matchPath}/`),
    );
