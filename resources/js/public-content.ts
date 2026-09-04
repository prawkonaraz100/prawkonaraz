import '../css/app.css';
import '../images/analytics/cookie-mascot-blink.webp';
import '../images/analytics/cookie-mascot.png';
import { setupCsrfRefreshForms } from './lib/csrfForms';
import { setupCsrfSessionLifecycle } from './lib/csrfSession';
import { registerPwaServiceWorker } from './lib/pwa';
import { setupPublicAuthDrawerLoader } from './public/authDrawerLoader';
import { setupQuestionLessonAudio } from './public/questionLessonAudio';
import '../images/home/hero-composite-v3.webp';
import '../images/home/hero-mobile.png';
import '../images/home/proof/dashboard.webp';
import '../images/home/proof/exam.webp';
import '../images/home/proof/explanation.webp';
import '../images/home/proof/incorrect-questions.webp';
import '../images/home/proof/memory-trainer.webp';
import '../images/home/proof/pjm.webp';
import '../images/home/proof/ranking.webp';
import '../images/home/proof/traffic-signs.webp';
import '../images/home/inteligentny-odtwarzacz-wideo-poster.jpg';
import '../images/home/mistakes-learning-poster.jpg';
import '../videos/home/inteligentny-odtwarzacz-wideo-720p.mp4';
import '../videos/home/mistakes-learning-800p.mp4';
import '../images/questions/category-icons/a.png';
import '../images/questions/category-icons/a1.png';
import '../images/questions/category-icons/a2.png';
import '../images/questions/category-icons/am.png';
import '../images/questions/category-icons/b.png';
import '../images/questions/category-icons/b1.png';
import '../images/questions/category-icons/c.png';
import '../images/questions/category-icons/c1.png';
import '../images/questions/category-icons/d.png';
import '../images/questions/category-icons/d1.png';
import '../images/questions/category-icons/t.png';
import '../images/questions/featured-category-icons/a.png';
import '../images/questions/featured-category-icons/b.png';
import '../images/questions/featured-category-icons/c.png';
import '../images/session/categories/kat_B.webp';
import '../images/session/categories/prawo-jazdy-kat-a-cutout.png';
import '../images/session/categories/prawo-jazdy-kat-c-cutout.png';
import '../images/session/categories/prawo-jazdy-kat-d-cutout.png';

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

const setupHomeHeaderPointerReveal = () => {
    const header = document.querySelector<HTMLElement>('[data-home-site-header]');

    if (!header) {
        return;
    }

    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    let revealed = false;

    const reveal = () => {
        if (revealed) {
            return;
        }

        revealed = true;
        header.classList.add('is-visible');
        header.classList.remove('home-site-header--awaiting-pointer');
        window.removeEventListener('pointermove', reveal);
        window.removeEventListener('keydown', revealFromKeyboard);
        document.removeEventListener('focusin', reveal);
    };

    const revealFromKeyboard = (event: KeyboardEvent) => {
        if (event.key === 'Tab') {
            reveal();
        }
    };

    if (!finePointer.matches) {
        reveal();

        return;
    }

    window.addEventListener('pointermove', reveal, { passive: true });
    window.addEventListener('keydown', revealFromKeyboard);
    document.addEventListener('focusin', reveal);
};

const setupHomeHeaderMenus = () => {
    const header = document.querySelector<HTMLElement>('[data-home-site-header]');
    const menus = Array.from(
        header?.querySelectorAll<HTMLDetailsElement>('[data-home-header-menu]') ?? [],
    );

    if (!header || menus.length === 0) {
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

const setupHomeOpsReveals = () => {
    const story = document.querySelector<HTMLElement>('.home-ops');
    const elements = Array.from(
        document.querySelectorAll<HTMLElement>('[data-home-reveal]'),
    );

    if (!story || elements.length === 0) {
        return;
    }

    if (
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || !('IntersectionObserver' in window)
    ) {
        elements.forEach((element) => element.classList.add('is-visible'));

        return;
    }

    story.classList.add('is-reveal-ready');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -8% 0px',
    });

    elements.forEach((element) => observer.observe(element));
};

const setupHomeOpsVideos = () => {
    if (!window.matchMedia('(min-width: 1280px)').matches) {
        return;
    }

    document.querySelectorAll<HTMLVideoElement>('[data-home-video]').forEach((video) => {
        const source = video.querySelector<HTMLSourceElement>('[data-home-video-source]');
        const sourceUrl = source?.dataset.homeVideoSource;
        const posterUrl = video.dataset.poster;

        if (!source || !sourceUrl) {
            return;
        }

        if (posterUrl) {
            video.poster = posterUrl;
        }

        source.src = sourceUrl;
        video.load();
        void video.play().catch(() => {
            // The poster remains visible when a browser blocks autoplay.
        });
    });
};

const setupHomeComparisonProof = () => {
    const dialog = document.querySelector<HTMLDialogElement>('[data-home-proof-dialog]');
    const image = dialog?.querySelector<HTMLImageElement>('[data-home-proof-image]');
    const eyebrow = dialog?.querySelector<HTMLElement>('[data-home-proof-eyebrow]');
    const title = dialog?.querySelector<HTMLElement>('[data-home-proof-title]');
    const description = dialog?.querySelector<HTMLElement>('[data-home-proof-description]');
    const points = dialog?.querySelector<HTMLElement>('[data-home-proof-points]');
    const closeButtons = dialog?.querySelectorAll<HTMLButtonElement>('[data-home-proof-close]');

    if (!dialog || !image || !eyebrow || !title || !description || !points || !closeButtons) {
        return;
    }

    const close = () => {
        if (dialog.open) {
            dialog.close();
        }
    };

    document.querySelectorAll<HTMLButtonElement>('[data-home-proof-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const imageUrl = trigger.dataset.proofImage;

            if (!imageUrl) {
                return;
            }

            eyebrow.textContent = trigger.dataset.proofEyebrow || 'Funkcja platformy';
            title.textContent = trigger.dataset.proofTitle || '';
            description.textContent = trigger.dataset.proofDescription || '';
            image.alt = trigger.dataset.proofAlt || '';

            if (image.getAttribute('src') !== imageUrl) {
                image.src = imageUrl;
            }

            points.replaceChildren();

            [trigger.dataset.proofPointOne, trigger.dataset.proofPointTwo]
                .filter((point): point is string => Boolean(point))
                .forEach((point) => {
                    const item = document.createElement('li');
                    item.textContent = point;
                    points.appendChild(item);
                });

            dialog.showModal();
        });
    });

    closeButtons.forEach((button) => button.addEventListener('click', close));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupCsrfSessionLifecycle();
        setupCsrfRefreshForms();
        setupPublicMobileMenu();
        setupPublicSourceStrip();
        setupHomeHeaderPointerReveal();
        setupHomeHeaderMenus();
        setupPublicAuthDrawerLoader();
        setupQuestionLessonAudio();
        setupHomeOpsReveals();
        setupHomeOpsVideos();
        setupHomeComparisonProof();
    }, {
        once: true,
    });
} else {
    setupCsrfSessionLifecycle();
    setupCsrfRefreshForms();
    setupPublicMobileMenu();
    setupPublicSourceStrip();
    setupHomeHeaderPointerReveal();
    setupHomeHeaderMenus();
    setupPublicAuthDrawerLoader();
    setupQuestionLessonAudio();
    setupHomeOpsReveals();
    setupHomeOpsVideos();
    setupHomeComparisonProof();
}
