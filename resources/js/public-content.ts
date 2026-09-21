import '../css/app.css';
import '../images/analytics/cookie-mascot-blink.webp';
import '../images/analytics/cookie-mascot.png';
import { setupCsrfRefreshForms } from './lib/csrfForms';
import { setupCsrfSessionLifecycle } from './lib/csrfSession';
import { registerPwaServiceWorker } from './lib/pwa';
import { setupPublicAuthDrawerLoader } from './public/authDrawerLoader';
import { setupQuestionLessonAudio } from './public/questionLessonAudio';
import { setupNewsroomAnalytics } from './public/newsroomAnalytics';

registerPwaServiceWorker();

const setupPublicMobileMenu = () => {
    const button = document.querySelector<HTMLButtonElement>(
        '[data-public-mobile-menu-button]',
    );
    const panel = document.querySelector<HTMLElement>(
        '[data-public-mobile-menu-panel]',
    );

    if (!button || !panel) {
        return;
    }

    const setOpen = (open: boolean) => {
        button.setAttribute('aria-expanded', String(open));
        button.textContent = open ? 'Zamknij' : 'Menu';
        panel.hidden = !open;
    };

    setOpen(false);

    button.addEventListener('click', () => {
        setOpen(button.getAttribute('aria-expanded') !== 'true');
    });

    panel.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            setOpen(false);
        }
    });
};

const setupPublicSourceStrip = () => {
    const strip = document.querySelector<HTMLElement>('[data-public-source-strip]');
    const dismissButton = document.querySelector<HTMLButtonElement>(
        '[data-public-source-strip-dismiss]',
    );
    const storageKey = 'prawkonaraz.sourceStrip.dismissed.v1';

    if (!strip || !dismissButton) {
        return;
    }

    try {
        if (window.localStorage.getItem(storageKey) === '1') {
            strip.classList.add('is-hidden');
        }
    } catch {
        // Ignore storage failures; the close button should still work for this page load.
    }

    dismissButton.addEventListener('click', () => {
        strip.classList.add('is-hidden');

        try {
            window.localStorage.setItem(storageKey, '1');
        } catch {
            // Ignore storage failures; the strip is already hidden in the current page.
        }
    });
};

const setupHomeHeaderScrollDensity = () => {
    const headers = Array.from(
        document.querySelectorAll<HTMLElement>('[data-home-site-header]'),
    );

    if (headers.length === 0) {
        return;
    }

    let animationFrame: number | null = null;

    const syncCompactState = () => {
        const compact = window.scrollY > 72;

        headers.forEach((header) => {
            header.classList.toggle('is-compact', compact);
        });

        animationFrame = null;
    };

    const handleScroll = () => {
        if (animationFrame !== null) {
            return;
        }

        animationFrame = window.requestAnimationFrame(syncCompactState);
    };

    syncCompactState();
    window.addEventListener('scroll', handleScroll, { passive: true });
};

const setupHomeHeaderMenus = () => {
    const menus = Array.from(
        document.querySelectorAll<HTMLDetailsElement>('[data-home-header-menu]'),
    );

    if (menus.length === 0) {
        return;
    }

    const closeMenu = (menu: HTMLDetailsElement, restoreFocus = false) => {
        if (!menu.open) {
            return;
        }

        menu.open = false;

        if (restoreFocus) {
            menu.querySelector<HTMLElement>(':scope > summary')?.focus();
        }
    };

    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) {
                return;
            }

            menus.forEach((otherMenu) => {
                if (otherMenu !== menu) {
                    closeMenu(otherMenu);
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Node)) {
            return;
        }

        menus.forEach((menu) => {
            if (!menu.contains(target)) {
                closeMenu(menu);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        menus.forEach((menu) => closeMenu(menu, true));
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupCsrfSessionLifecycle();
        setupCsrfRefreshForms();
        setupPublicMobileMenu();
        setupPublicSourceStrip();
        setupHomeHeaderScrollDensity();
        setupHomeHeaderMenus();
        setupPublicAuthDrawerLoader();
        setupQuestionLessonAudio();
        setupNewsroomAnalytics();
    }, {
        once: true,
    });
} else {
    setupCsrfSessionLifecycle();
    setupCsrfRefreshForms();
    setupPublicMobileMenu();
    setupPublicSourceStrip();
    setupHomeHeaderScrollDensity();
    setupHomeHeaderMenus();
    setupPublicAuthDrawerLoader();
    setupQuestionLessonAudio();
    setupNewsroomAnalytics();
}
