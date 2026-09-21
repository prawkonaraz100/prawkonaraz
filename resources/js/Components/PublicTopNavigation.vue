<script setup lang="ts">
import { useSafeLogout } from '@/composables/useSafeLogout';
import { useCompactSiteHeader } from '@/composables/useCompactSiteHeader';
import type { NavigationLink, PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage<PageProps>();
const header = ref<HTMLElement | null>(null);
const launcherMenu = ref<HTMLDetailsElement | null>(null);
const mobileMenu = ref<HTMLDetailsElement | null>(null);
const navigation = computed(() => page.props.navigation);
const navigationLinks = computed(() => navigation.value.top);
const user = computed(() => page.props.auth.user);
const isAuthed = computed(() => Boolean(user.value));
const accountHref = computed(() => isAuthed.value ? '/profile' : '/login');
const accountLabel = computed(() => isAuthed.value ? 'Moje konto' : 'Logowanie');
const { logout, logoutPreparing } = useSafeLogout();
const { isCompact } = useCompactSiteHeader();

const accountLinks = computed<NavigationLink[]>(() => [
    {
        label: 'Kontynuuj naukę',
        href: navigation.value.learning_href,
        match: ['/nauka', '/study-sessions', '/trener-pamieci'],
    },
    ...navigation.value.header_actions.map((link) => ({
        ...link,
        label: link.href === '/profile' ? 'Moje konto' : link.label,
    })),
]);

const closeMenus = () => {
    if (launcherMenu.value) {
        launcherMenu.value.open = false;
    }

    if (mobileMenu.value) {
        mobileMenu.value.open = false;
    }
};

const closeOnOutsideClick = (event: PointerEvent) => {
    if (event.target instanceof Node && !header.value?.contains(event.target)) {
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

const submitLogout = () => {
    closeMenus();

    if (navigation.value.logout_href) {
        logout(navigation.value.logout_href);
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
        class="home-site-header home-site-header--public-page"
        :class="{ 'is-compact': isCompact }"
    >
        <div class="home-site-header__shell">
            <div class="home-site-header__identity">
                <a class="home-site-header__back" href="/" aria-label="Wróć na stronę główną">
                    <span class="home-site-header__back-icon" aria-hidden="true">
                        <img
                            class="home-site-header__back-icon-full"
                            src="/images/site-brand-mark-shield-v2.png"
                            alt=""
                        >
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
                <a class="home-site-header__account" :href="accountHref">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                        <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6" />
                        <path d="M6.7 19.15c.85-3.05 2.62-4.55 5.3-4.55s4.45 1.5 5.3 4.55" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    <span>{{ accountLabel }}</span>
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
                        <template v-if="isAuthed">
                            <a
                                v-for="link in accountLinks"
                                :key="link.href + link.label"
                                :href="link.href"
                                @click="closeMenus"
                            >
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                                    <path d="m9.5 12 1.7 1.7 3.6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>{{ link.label }}</span>
                            </a>
                            <form v-if="navigation.logout_href" @submit.prevent="submitLogout">
                                <button type="submit" :disabled="logoutPreparing">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                        <path d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10M14.5 8.5 18 12l-3.5 3.5M9 12h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span>{{ logoutPreparing ? 'Wylogowywanie…' : 'Wyloguj' }}</span>
                                </button>
                            </form>
                        </template>
                        <template v-else>
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
                        </template>
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

                    <div v-if="isAuthed">
                        <a :href="navigation.learning_href" @click="closeMenus">Kontynuuj naukę</a>
                        <a href="/profile" @click="closeMenus">Moje konto</a>
                        <form v-if="navigation.logout_href" @submit.prevent="submitLogout">
                            <button type="submit" :disabled="logoutPreparing">
                                {{ logoutPreparing ? 'Wylogowywanie…' : 'Wyloguj' }}
                            </button>
                        </form>
                    </div>
                    <div v-else>
                        <a href="/register" @click="closeMenus">Załóż konto</a>
                        <a href="/login" @click="closeMenus">Zaloguj się</a>
                    </div>
                </div>
            </details>
        </div>
    </header>
</template>
