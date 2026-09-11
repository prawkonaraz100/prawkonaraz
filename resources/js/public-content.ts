import '../css/app.css';
import '../images/analytics/cookie-mascot-blink.webp';
import '../images/analytics/cookie-mascot.png';
import { setupCsrfRefreshForms } from './lib/csrfForms';
import { setupCsrfSessionLifecycle } from './lib/csrfSession';
import { registerPwaServiceWorker } from './lib/pwa';
import { setupPublicAuthDrawerLoader } from './public/authDrawerLoader';
import { setupQuestionLessonAudio } from './public/questionLessonAudio';
import { setupNewsroomAnalytics } from './public/newsroomAnalytics';
import '../images/home/hero-composite-v3.webp';
import '../images/home/hero-mobile.png';
import '../images/home/mobile-app-banner.webp';
import '../images/home/learning/classic-mode.png';
import '../images/home/learning/focus-mode.png';
import '../images/home/learning/explanation-focus.png';
import '../images/home/learning/explanations-overview.png';
import '../images/home/contact/advisor-monday.jpg';
import '../images/home/contact/advisor-tuesday.jpg';
import '../images/home/contact/advisor-wednesday.jpg';
import '../images/home/contact/advisor-thursday.jpg';
import '../images/home/contact/advisor-friday.jpg';
import '../images/home/contact/advisor-saturday.jpg';
import '../images/home/contact/advisor-sunday.jpg';
import '../images/home/proof/dashboard.webp';
import '../images/home/proof/exam.webp';
import '../images/home/proof/explanation.webp';
import '../images/home/proof/incorrect-questions.webp';
import '../images/home/proof/memory-trainer.webp';
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

const setupHomeContactDialog = () => {
    const dialog = document.querySelector<HTMLDialogElement>('[data-home-contact-dialog]');
    const triggers = document.querySelectorAll<HTMLButtonElement>('[data-home-contact-trigger]');
    const closeButtons = dialog?.querySelectorAll<HTMLButtonElement>('[data-home-contact-close]');

    if (!dialog || triggers.length === 0 || !closeButtons) {
        return;
    }

    const open = () => {
        if (!dialog.open) {
            dialog.showModal();
        }
    };

    const close = () => {
        if (dialog.open) {
            dialog.close();
        }
    };

    triggers.forEach((trigger) => trigger.addEventListener('click', open));
    closeButtons.forEach((button) => button.addEventListener('click', close));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    if (dialog.dataset.homeContactAutoOpen === 'true') {
        open();
    }
};

const setupHomeLearningImageDialog = () => {
    const dialog = document.querySelector<HTMLDialogElement>('[data-home-learning-image-dialog]');
    const image = dialog?.querySelector<HTMLImageElement>('[data-home-learning-image]');
    const title = dialog?.querySelector<HTMLElement>('[data-home-learning-image-title]');
    const closeButtons = dialog?.querySelectorAll<HTMLButtonElement>('[data-home-learning-image-close]');
    const triggers = document.querySelectorAll<HTMLButtonElement>('[data-home-learning-image-trigger]');
    let activeTrigger: HTMLButtonElement | null = null;

    if (!dialog || !image || !title || !closeButtons || triggers.length === 0) {
        return;
    }

    const close = () => {
        if (dialog.open) {
            dialog.close();
        }
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const source = trigger.dataset.imageSrc;

            if (!source) {
                return;
            }

            activeTrigger = trigger;
            image.src = source;
            image.alt = trigger.dataset.imageAlt ?? '';
            title.textContent = trigger.dataset.imageTitle ?? '';

            if (!dialog.open) {
                dialog.showModal();
            }
        });
    });

    closeButtons.forEach((button) => button.addEventListener('click', close));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });
    dialog.addEventListener('close', () => {
        image.removeAttribute('src');
        activeTrigger?.focus();
        activeTrigger = null;
    });
};

const setupHomeVideoLibrary = () => {
    const library = document.querySelector<HTMLElement>('[data-home-video-library]');
    const player = library?.querySelector<HTMLElement>('[data-home-video-player]');
    const tabs = Array.from(
        library?.querySelectorAll<HTMLButtonElement>('[data-home-video-tab]') ?? [],
    );
    const items = Array.from(
        library?.querySelectorAll<HTMLButtonElement>('[data-home-video-item]') ?? [],
    );
    const empty = library?.querySelector<HTMLElement>('[data-home-video-empty]');

    if (!library || !player || tabs.length === 0 || items.length === 0) {
        return;
    }

    const startPlayback = (item: HTMLButtonElement) => {
        const sourceType = item.dataset.sourceType;
        const sourceUrl = item.dataset.sourceUrl;
        const posterUrl = item.dataset.posterUrl;
        const title = item.dataset.videoTitle ?? 'Film PrawkoNaRaz';

        if (!sourceType || !sourceUrl) {
            return;
        }

        player.classList.remove('is-empty');
        player.replaceChildren();

        if (sourceType === 'youtube') {
            const iframe = document.createElement('iframe');
            const separator = sourceUrl.includes('?') ? '&' : '?';
            iframe.src = `${sourceUrl}${separator}autoplay=1&controls=0&rel=0&modestbranding=1&iv_load_policy=3`;
            iframe.title = title;
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            iframe.allowFullscreen = true;
            player.append(iframe);
        } else {
            const video = document.createElement('video');
            video.autoplay = true;
            video.preload = 'metadata';

            if (posterUrl) {
                video.poster = posterUrl;
            }

            const source = document.createElement('source');
            source.src = sourceUrl;
            source.type = 'video/mp4';
            video.append(source);
            player.append(video);
            void video.play().catch(() => {
                // The controls remain available if autoplay is blocked.
            });
        }
    };

    const renderPlayer = (item: HTMLButtonElement) => {
        const posterUrl = item.dataset.posterUrl;
        const title = item.dataset.videoTitle ?? 'Film PrawkoNaRaz';

        player.classList.remove('is-empty');
        player.replaceChildren();

        const startButton = document.createElement('button');
        startButton.type = 'button';
        startButton.className = 'home-video-library__player-start';
        startButton.setAttribute('aria-label', `Odtwórz: ${title}`);

        if (posterUrl) {
            const image = document.createElement('img');
            image.src = posterUrl;
            image.alt = '';
            image.loading = 'lazy';
            startButton.append(image);
        }

        const playIcon = document.createElement('span');
        playIcon.setAttribute('aria-hidden', 'true');
        playIcon.textContent = '▶';
        startButton.append(playIcon);
        startButton.addEventListener('click', () => startPlayback(item));
        player.append(startButton);

        items.forEach((candidate) => {
            const active = candidate === item;
            candidate.classList.toggle('is-active', active);
            candidate.setAttribute('aria-pressed', String(active));

            const state = candidate.querySelector<HTMLElement>('[data-home-video-state]');
            if (state) {
                state.textContent = active ? 'Teraz odtwarzasz' : 'Odtwórz teraz';
            }
        });
    };

    items.forEach((item) => {
        item.addEventListener('click', () => renderPlayer(item));
    });

    const showKind = (kind: string) => {
        tabs.forEach((tab) => {
            const active = tab.dataset.homeVideoTab === kind;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });

        const visibleItems = items.filter((item) => item.dataset.mediaKind === kind);

        items.forEach((item) => {
            item.hidden = item.dataset.mediaKind !== kind;
            item.classList.remove('is-active');
            item.setAttribute('aria-pressed', 'false');
        });

        if (empty) {
            empty.hidden = visibleItems.length > 0;
            empty.textContent = kind === 'podcast'
                ? 'Podcasty pojawią się tutaj po dodaniu ich w panelu administratora.'
                : 'Materiały video pojawią się tutaj po dodaniu ich w panelu administratora.';
        }

        const firstItem = visibleItems[0];

        if (firstItem) {
            renderPlayer(firstItem);
            return;
        }

        player.replaceChildren();
        player.classList.add('is-empty');
        const message = document.createElement('p');
        message.textContent = kind === 'podcast'
            ? 'Wybierz podcast, gdy pojawi się na liście.'
            : 'Wybierz materiał video, gdy pojawi się na liście.';
        player.append(message);
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            const kind = tab.dataset.homeVideoTab;
            if (kind) {
                showKind(kind);
            }
        });

        tab.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextTab = tabs[(index + direction + tabs.length) % tabs.length];
            nextTab.focus();
            nextTab.click();
        });
    });

    const initialItem = items.find((item) => item.classList.contains('is-active'));
    if (initialItem) {
        renderPlayer(initialItem);
    }
};

const setupHomeExpertCarousel = () => {
    const carousel = document.querySelector<HTMLElement>('[data-home-expert-carousel]');
    const rail = carousel?.querySelector<HTMLElement>('[data-home-expert-rail]');
    const slides = Array.from(
        carousel?.querySelectorAll<HTMLElement>('.home-learning__expert-slide') ?? [],
    );
    const controls = Array.from(
        carousel?.querySelectorAll<HTMLButtonElement>('[data-home-expert-scroll]') ?? [],
    );
    const position = carousel?.querySelector<HTMLElement>('[data-home-expert-position]');

    if (!carousel || !rail || slides.length === 0 || controls.length === 0) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let pointerId: number | null = null;
    let pointerStartX = 0;
    let pointerStartScrollLeft = 0;
    let isDragging = false;
    let suppressClick = false;

    const activeIndex = () => Math.max(
        0,
        Math.min(slides.length - 1, Math.round(rail.scrollLeft / Math.max(rail.clientWidth, 1))),
    );

    const syncState = () => {
        const index = activeIndex();

        controls.forEach((control) => {
            const direction = control.dataset.homeExpertScroll;
            control.disabled = direction === 'previous'
                ? index === 0
                : index === slides.length - 1;
        });

        if (position) {
            position.textContent = `${index + 1} / ${slides.length}`;
        }
    };

    const showSlide = (index: number) => {
        const nextIndex = Math.max(0, Math.min(slides.length - 1, index));

        rail.scrollTo({
            left: nextIndex * rail.clientWidth,
            behavior: reducedMotion ? 'auto' : 'smooth',
        });
    };

    controls.forEach((control) => {
        control.addEventListener('click', () => {
            const direction = control.dataset.homeExpertScroll === 'previous' ? -1 : 1;
            showSlide(activeIndex() + direction);
        });
    });

    rail.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();
        showSlide(activeIndex() + (event.key === 'ArrowRight' ? 1 : -1));
    });

    const finishDrag = (event: PointerEvent) => {
        if (pointerId !== event.pointerId) {
            return;
        }

        if (rail.hasPointerCapture(event.pointerId)) {
            rail.releasePointerCapture(event.pointerId);
        }

        pointerId = null;
        rail.classList.remove('is-dragging');

        if (isDragging) {
            showSlide(activeIndex());
            window.setTimeout(() => {
                suppressClick = false;
            }, 0);
        }

        isDragging = false;
        syncState();
    };

    rail.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) {
            return;
        }

        pointerId = event.pointerId;
        pointerStartX = event.clientX;
        pointerStartScrollLeft = rail.scrollLeft;
        isDragging = false;
        suppressClick = false;
    });

    rail.addEventListener('pointermove', (event) => {
        if (pointerId !== event.pointerId) {
            return;
        }

        const distance = event.clientX - pointerStartX;

        if (!isDragging && Math.abs(distance) < 5) {
            return;
        }

        if (!isDragging) {
            rail.setPointerCapture(event.pointerId);
            rail.classList.add('is-dragging');
            isDragging = true;
            suppressClick = true;
        }

        event.preventDefault();
        rail.scrollLeft = pointerStartScrollLeft - distance;
    });

    rail.addEventListener('pointerup', finishDrag);
    rail.addEventListener('pointercancel', finishDrag);
    rail.addEventListener('click', (event) => {
        if (!suppressClick) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
    }, { capture: true });
    rail.addEventListener('scroll', syncState, { passive: true });
    window.addEventListener('resize', syncState, { passive: true });
    syncState();
};

const setupHomeReviewsCarousel = () => {
    const rail = document.querySelector<HTMLElement>('[data-home-reviews-rail]');
    const controls = Array.from(
        document.querySelectorAll<HTMLButtonElement>('[data-home-reviews-scroll]'),
    );

    if (!rail || controls.length === 0) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let pointerId: number | null = null;
    let pointerStartX = 0;
    let pointerStartScrollLeft = 0;
    let isDragging = false;
    let suppressClick = false;

    const syncControls = () => {
        const maxScroll = Math.max(rail.scrollWidth - rail.clientWidth, 0);

        controls.forEach((control) => {
            const direction = control.dataset.homeReviewsScroll;
            control.disabled = direction === 'previous'
                ? rail.scrollLeft <= 2
                : rail.scrollLeft >= maxScroll - 2;
        });
    };

    controls.forEach((control) => {
        control.addEventListener('click', () => {
            const direction = control.dataset.homeReviewsScroll === 'previous' ? -1 : 1;
            const card = rail.querySelector<HTMLElement>('.home-reviews__card');
            const distance = card ? card.offsetWidth + 16 : Math.round(rail.clientWidth * 0.8);

            rail.scrollBy({
                left: direction * distance,
                behavior: reducedMotion ? 'auto' : 'smooth',
            });
        });
    });

    const finishDrag = (event: PointerEvent) => {
        if (pointerId !== event.pointerId) {
            return;
        }

        if (rail.hasPointerCapture(event.pointerId)) {
            rail.releasePointerCapture(event.pointerId);
        }

        pointerId = null;
        rail.classList.remove('is-dragging');

        if (isDragging) {
            window.setTimeout(() => {
                suppressClick = false;
            }, 0);
        }

        isDragging = false;
        syncControls();
    };

    rail.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) {
            return;
        }

        pointerId = event.pointerId;
        pointerStartX = event.clientX;
        pointerStartScrollLeft = rail.scrollLeft;
        isDragging = false;
        suppressClick = false;
    });

    rail.addEventListener('pointermove', (event) => {
        if (pointerId !== event.pointerId) {
            return;
        }

        const distance = event.clientX - pointerStartX;

        if (!isDragging && Math.abs(distance) < 5) {
            return;
        }

        if (!isDragging) {
            rail.setPointerCapture(event.pointerId);
            isDragging = true;
            suppressClick = true;
            rail.classList.add('is-dragging');
        }

        event.preventDefault();
        rail.scrollLeft = pointerStartScrollLeft - distance;
    });

    rail.addEventListener('pointerup', finishDrag);
    rail.addEventListener('pointercancel', finishDrag);
    rail.addEventListener('click', (event) => {
        if (!suppressClick) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
    }, { capture: true });

    rail.addEventListener('scroll', syncControls, { passive: true });
    window.addEventListener('resize', syncControls, { passive: true });
    syncControls();
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
        setupHomeOpsReveals();
        setupHomeOpsVideos();
        setupHomeContactDialog();
        setupHomeLearningImageDialog();
        setupHomeVideoLibrary();
        setupHomeExpertCarousel();
        setupHomeReviewsCarousel();
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
    setupHomeOpsReveals();
    setupHomeOpsVideos();
    setupHomeContactDialog();
    setupHomeLearningImageDialog();
    setupHomeVideoLibrary();
    setupHomeExpertCarousel();
    setupHomeReviewsCarousel();
}
