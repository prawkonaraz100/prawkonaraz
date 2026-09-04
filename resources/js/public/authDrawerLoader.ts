export type AuthDrawerName = 'login' | 'register';

interface PublicAuthDrawersController {
    open: (name: AuthDrawerName) => Promise<void>;
    setupOneTap: () => Promise<void>;
}

const drawerNameFromUrl = (url: URL): AuthDrawerName | null => {
    if (url.origin !== window.location.origin) {
        return null;
    }

    if (url.pathname === '/login') {
        return 'login';
    }

    if (url.pathname === '/register') {
        return 'register';
    }

    return null;
};

export const setupPublicAuthDrawerLoader = () => {
    const root = document.querySelector<HTMLElement>(
        '[data-public-auth-drawer-root]',
    );

    if (!root) {
        return;
    }

    let controllerPromise: Promise<PublicAuthDrawersController> | null = null;
    let latestRequest = 0;

    const getController = () => {
        if (controllerPromise === null) {
            controllerPromise = import('./authDrawers')
                .then((module) => module.createPublicAuthDrawers(root))
                .catch((error: unknown) => {
                    controllerPromise = null;
                    throw error;
                });
        }

        return controllerPromise;
    };

    const openDrawer = (name: AuthDrawerName, fallbackUrl: string) => {
        latestRequest += 1;
        const requestId = latestRequest;

        void getController()
            .then((controller) => controller.open(name))
            .catch(() => {
                if (requestId === latestRequest) {
                    window.location.assign(fallbackUrl);
                }
            });
    };

    document.addEventListener('click', (event) => {
        if (
            !(event instanceof MouseEvent)
            || event.defaultPrevented
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || event.button !== 0
        ) {
            return;
        }

        const link = event.target instanceof Element
            ? event.target.closest<HTMLAnchorElement>('a[href]')
            : null;

        if (
            !link
            || link.dataset.loginDrawerIgnore === 'true'
            || link.dataset.registerDrawerIgnore === 'true'
        ) {
            return;
        }

        const href = new URL(link.href, window.location.href);
        const drawerName = drawerNameFromUrl(href);

        if (drawerName === null) {
            return;
        }

        event.preventDefault();
        openDrawer(drawerName, href.toString());
    });

    const initialDrawer = root.dataset.initialDrawer;

    if (initialDrawer === 'login' || initialDrawer === 'register') {
        openDrawer(initialDrawer, `/${initialDrawer}`);
    }

    if (
        window.location.pathname === '/'
        && root.dataset.googleOneTapEnabled === 'true'
    ) {
        void getController()
            .then((controller) => controller.setupOneTap())
            .catch(() => {
                // One Tap is optional; classic login links remain available.
            });
    }
};
