<script setup lang="ts">
import type { PageProps } from '@/types';
import { useCompactSiteHeader } from '@/composables/useCompactSiteHeader';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage<PageProps>();
const navigationLinks = computed(() => page.props.navigation.top);
const { isCompact } = useCompactSiteHeader();

const header = ref<HTMLElement | null>(null);
const launcherMenu = ref<HTMLDetailsElement | null>(null);
const mobileMenu = ref<HTMLDetailsElement | null>(null);

const closeMenus = () => {
    if (launcherMenu.value) {
        launcherMenu.value.open = false;
    }

    if (mobileMenu.value) {
        mobileMenu.value.open = false;
    }
};

const closeOnOutsideClick = (event: PointerEvent) => {
    if (
        event.target instanceof Node &&
        !header.value?.contains(event.target)
    ) {
        closeMenus();
    }
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeMenus();
    }
};

const keepSingleMenuOpen = (activeMenu: HTMLDetailsElement | null) => {
    if (!activeMenu?.open) {
        return;
    }

    const otherMenu = activeMenu === launcherMenu.value
        ? mobileMenu.value
        : launcherMenu.value;

    if (otherMenu) {
        otherMenu.open = false;
    }
};

onMounted(() => {
    document.addEventListener('pointerdown', closeOnOutsideClick);
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', closeOnOutsideClick);
    document.removeEventListener('keydown', closeOnEscape);
});
</script>

<template>
    <header
        ref="header"
        class="home-site-header auth-page-header"
        :class="{ 'is-compact': isCompact }"
    >
        <div class="home-site-header__shell">
            <div class="home-site-header__identity">
                <a class="home-site-header__back" href="/" aria-label="Wróć na stronę główną">
                    <span class="home-site-header__back-icon" aria-hidden="true">
                        <svg class="home-site-header__back-icon-full" viewBox="0 0 40 40" fill="none">
                            <circle cx="20" cy="20" r="18.75" stroke="currentColor" stroke-width="1.5" />
                            <path d="m21.5 12.75-7.25 7.25 7.25 7.25M14.5 20h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg class="home-site-header__back-icon-compact" viewBox="0 0 20 28" fill="none">
                            <path d="m12.5 5.5-7.25 8.5 7.25 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="home-site-header__back-label" aria-hidden="true">Wróć</span>
                </a>
                <a class="home-site-header__brand" href="/" aria-label="Prawko na Raz — strona główna">
                    <span>prawko</span><strong>naraz</strong>
                </a>
            </div>

            <nav class="home-site-header__nav" aria-label="Nawigacja główna">
                <a
                    v-for="link in navigationLinks"
                    :key="link.href + link.label"
                    :href="link.href"
                >
                    <span>{{ link.label }}</span>
                </a>
            </nav>

            <div class="home-site-header__desktop-actions">
                <a class="home-site-header__account" href="/login">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                        <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6" />
                        <path d="M6.7 19.15c.85-3.05 2.62-4.55 5.3-4.55s4.45 1.5 5.3 4.55" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    <span>Logowanie</span>
                </a>

                <details
                    ref="launcherMenu"
                    class="home-site-header__launcher"
                    @toggle="keepSingleMenuOpen(launcherMenu)"
                >
                    <summary aria-label="Otwórz menu serwisu">
                        <span class="home-site-header__launcher-icon" aria-hidden="true">
                            <i v-for="dot in 9" :key="dot"></i>
                        </span>
                    </summary>
                    <div class="home-site-header__launcher-panel">
                        <a href="/login" @click="closeMenus">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <circle cx="8.5" cy="15.5" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                <path d="M11.8 13.2 20 5m-2 2 2 2m-5.2 1.2 2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>Zaloguj się</span>
                        </a>
                        <a href="/register" @click="closeMenus">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                <path d="M3.5 19c.4-3.2 2.3-5 5.5-5 1.4 0 2.5.3 3.4.9M18 13v7m-3.5-3.5h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                            <span>Załóż konto</span>
                        </a>
                    </div>
                </details>
            </div>

            <details
                ref="mobileMenu"
                class="home-site-header__mobile-menu"
                @toggle="keepSingleMenuOpen(mobileMenu)"
            >
                <summary aria-label="Otwórz menu">
                    <span class="home-site-header__launcher-icon" aria-hidden="true">
                        <i v-for="dot in 9" :key="dot"></i>
                    </span>
                </summary>
                <div class="home-site-header__mobile-panel">
                    <nav aria-label="Nawigacja mobilna">
                        <a
                            v-for="link in navigationLinks"
                            :key="link.href + link.label"
                            :href="link.href"
                            @click="closeMenus"
                        >
                            {{ link.label }}
                        </a>
                    </nav>
                    <div>
                        <a href="/register" @click="closeMenus">Załóż konto</a>
                        <a href="/login" @click="closeMenus">Zaloguj się</a>
                    </div>
                </div>
            </details>
        </div>
    </header>
</template>
