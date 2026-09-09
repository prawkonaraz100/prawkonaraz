<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AuthTopNavigation from '@/Components/Auth/AuthTopNavigation.vue';
import LoginDrawer from '@/Components/Auth/LoginDrawer.vue';
import RegisterDrawer from '@/Components/Auth/RegisterDrawer.vue';
import { useSafeLogout } from '@/composables/useSafeLogout';
import questionSourceEmblem from '../../images/session/question-source-emblem-crop.png';
import {
    isLoginHref,
    isRegisterHref,
    linkPath,
    matchesPath,
    navigationComponent,
} from '@/support/public-navigation';
import type { NavigationLink, PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type HeaderNavigationIcon =
    | 'home'
    | 'news'
    | 'learn'
    | 'question'
    | 'signs'
    | 'book'
    | 'monitor'
    | 'tag';

type HeaderNavigationLink = Omit<NavigationLink, 'icon'> & {
    icon: HeaderNavigationIcon;
};

type AuthDrawerName = 'login' | 'register';

const props = withDefaults(
    defineProps<{
        shellWidthClass?: string;
        learningPanel?: boolean;
    }>(),
    {
        shellWidthClass: '',
        learningPanel: false,
    },
);

const sourceStripStorageKey = 'prawkonaraz.sourceStrip.dismissed.v1';
const page = usePage<PageProps>();
const {
    logout,
    logoutError,
    logoutPreparing,
} = useSafeLogout();

const user = computed(() => page.props.auth.user);
const isAuthed = computed(() => Boolean(user.value));
const navigation = computed(() => page.props.navigation);
const mobileMenuOpen = ref(false);
const accountMenuOpen = ref(false);
const loginDrawerOpen = ref(false);
const registerDrawerOpen = ref(false);
const authDrawerOpen = computed(
    () => loginDrawerOpen.value || registerDrawerOpen.value,
);
const authDrawerVisualHost = ref<AuthDrawerName | null>(null);
const sourceStripVisible = ref(true);
const currentYear = new Date().getFullYear();
const currentPath = computed(() => {
    const raw = page.url ?? '/';

    return raw.split('?')[0] || '/';
});
const registrationCategories = computed(
    () => page.props.authDrawers.registrationCategories,
);
const headerActions = computed(() =>
    navigation.value.header_actions.filter((action) => {
        const actionPath = linkPath(action.href);

        return actionPath !== currentPath.value;
    }),
);
const userDisplayName = computed(() => user.value?.name?.trim() || 'Konto');
const userAvatarUrl = computed(() => user.value?.avatar_url ?? null);
const userInitials = computed(() => {
    if (user.value?.avatar_initials) {
        return user.value.avatar_initials;
    }

    const initials = userDisplayName.value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toLocaleUpperCase('pl-PL'))
        .join('');

    return initials || 'U';
});

const mobilePrimaryLinks = computed<HeaderNavigationLink[]>(() => [
    { label: 'Strona główna', href: '/', match: ['/'], icon: 'home' },
    { label: 'Aktualności', href: '/aktualnosci', match: ['/aktualnosci'], icon: 'news' },
    {
        label: 'Nauka',
        href: navigation.value.learning_href,
        match: ['/nauka', '/study-sessions', '/trener-pamieci'],
        icon: 'learn',
    },
    {
        label: 'Pytania',
        href: '/oficjalna-baza-pytan-na-prawo-jazdy',
        match: ['/oficjalna-baza-pytan-na-prawo-jazdy', '/pytanie'],
        icon: 'question',
    },
    { label: 'Znaki drogowe', href: '/znaki-drogowe', match: ['/znaki-drogowe'], icon: 'signs' },
    { label: 'Przepisy', href: '/przepisy', match: ['/przepisy'], icon: 'book' },
    { label: 'Testy online', href: '/testy-na-prawo-jazdy', match: ['/testy-na-prawo-jazdy'], icon: 'monitor' },
    { label: 'Cennik', href: '/cennik', match: ['/cennik'], icon: 'tag' },
]);

const desktopPrimaryLinks = computed<HeaderNavigationLink[]>(() => [
    {
        label: 'Nauka',
        href: navigation.value.learning_href,
        match: ['/nauka', '/study-sessions', '/trener-pamieci'],
        icon: 'learn',
    },
    { label: 'Testy', href: '/testy-na-prawo-jazdy', match: ['/testy-na-prawo-jazdy'], icon: 'monitor' },
    { label: 'Znaki', href: '/znaki-drogowe', match: ['/znaki-drogowe'], icon: 'signs' },
    { label: 'Przepisy', href: '/przepisy', match: ['/przepisy'], icon: 'book' },
    { label: 'Cennik', href: '/cennik', match: ['/cennik'], icon: 'tag' },
]);

const mobilePanelActions = computed(() => headerActions.value);
const isMatchActive = (matchPaths: string[]) =>
    matchesPath(currentPath.value, matchPaths);

const isLinkActive = (link: NavigationLink) => isMatchActive(link.match);

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

const closeAccountMenu = () => {
    accountMenuOpen.value = false;
};

const closeAuthDrawers = () => {
    loginDrawerOpen.value = false;
    registerDrawerOpen.value = false;
    authDrawerVisualHost.value = null;
};

const openLoginDrawer = () => {
    closeMobileMenu();
    closeAccountMenu();

    if (authDrawerVisualHost.value === null) {
        authDrawerVisualHost.value = 'login';
    }

    registerDrawerOpen.value = false;
    loginDrawerOpen.value = true;
};

const openRegisterDrawer = () => {
    closeMobileMenu();
    closeAccountMenu();

    if (authDrawerVisualHost.value === null) {
        authDrawerVisualHost.value = 'register';
    }

    loginDrawerOpen.value = false;
    registerDrawerOpen.value = true;
};

const retainsAuthDrawerVisual = (drawer: AuthDrawerName) =>
    authDrawerVisualHost.value === drawer
    && (drawer === 'login' ? registerDrawerOpen.value : loginDrawerOpen.value);

const hidesAuthDrawerVisual = (drawer: AuthDrawerName) =>
    authDrawerVisualHost.value !== null
    && authDrawerVisualHost.value !== drawer
    && (drawer === 'login' ? loginDrawerOpen.value : registerDrawerOpen.value);

const dismissSourceStrip = () => {
    sourceStripVisible.value = false;

    try {
        window.localStorage.setItem(sourceStripStorageKey, '1');
    } catch {
        // The strip is already hidden for this page load.
    }
};

const openAuthDrawerFromEvent = (event: Event) => {
    const drawer = (event as CustomEvent<{ drawer?: 'login' | 'register' }>)
        .detail?.drawer;

    if (drawer === 'login') {
        openLoginDrawer();
        return;
    }

    if (drawer === 'register') {
        openRegisterDrawer();
    }
};

onMounted(() => {
    try {
        sourceStripVisible.value =
            window.localStorage.getItem(sourceStripStorageKey) !== '1';
    } catch {
        sourceStripVisible.value = true;
    }

    window.addEventListener('prawko:open-auth-drawer', openAuthDrawerFromEvent);
});

onBeforeUnmount(() => {
    window.removeEventListener('prawko:open-auth-drawer', openAuthDrawerFromEvent);
});

watch(currentPath, () => {
    closeMobileMenu();
    closeAccountMenu();
    closeAuthDrawers();
});
</script>

<template>
    <header
        class="site-header"
        :class="{ 'site-header--auth-covered': authDrawerOpen }"
    >
        <div
            class="site-header__source-strip"
            :class="{ 'is-hidden': !sourceStripVisible }"
        >
            <a
                href="/partnerzy"
                class="site-header__source-strip-link"
                aria-label="Zobacz partnerów i źródło oficjalnej bazy pytań"
            >
                <span class="site-header__source-emblem" aria-hidden="true">
                    <img :src="questionSourceEmblem" alt="">
                </span>
                <span class="site-header__source-badge">Oficjalna baza {{ currentYear }}</span>
                <span class="site-header__source-copy">Pytania pochodzą z rządowego źródła</span>
                <span class="site-header__source-handwritten">Ministerstwa Infrastruktury</span>
                <span class="site-header__source-arrow" aria-hidden="true">
                    <svg viewBox="0 0 90 44" focusable="false">
                        <path class="site-header__source-arrow-line site-header__source-arrow-line--ghost" d="M5 8C20 12 31 21 47 24C59 26 67 23 75 30" />
                        <path class="site-header__source-arrow-line" d="M4 6C19 10 31 19 47 22C59 24 68 22 77 30" />
                        <path class="site-header__source-arrow-head" d="M66 21L79 31L64 38" />
                    </svg>
                </span>
                <span class="site-header__source-link">Partnerzy i źródła <span aria-hidden="true">→</span></span>
            </a>
            <button
                type="button"
                class="site-header__source-dismiss"
                aria-label="Zamknij pasek informacyjny"
                @click="dismissSourceStrip"
            >
                <svg aria-hidden="true" viewBox="0 0 16 16" focusable="false">
                    <path d="m4.2 4.2 7.6 7.6M11.8 4.2l-7.6 7.6" />
                </svg>
            </button>
        </div>

        <div class="site-header__shell" :class="props.shellWidthClass">
            <div class="site-header__mobile">
                <component
                    :is="navigationComponent('/')"
                    href="/"
                    class="inline-flex min-w-0 shrink items-center text-[#151515]"
                    @click="closeMobileMenu"
                >
                    <ApplicationLogo variant="header" />
                </component>

                <div class="site-header__mobile-actions">
                    <a
                        href="/testy-na-prawo-jazdy"
                        class="site-header__mobile-button site-header__mobile-button--primary"
                        @click="closeMobileMenu"
                    >
                        SpeedRun
                    </a>
                    <button
                        type="button"
                        class="site-header__mobile-button"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="mobile-site-menu"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        {{ mobileMenuOpen ? 'Zamknij' : 'Menu' }}
                    </button>
                </div>
            </div>

            <Transition name="header-panel">
                <div
                    v-if="mobileMenuOpen"
                    id="mobile-site-menu"
                    class="site-header__mobile-panel max-h-[calc(100svh-76px)] overflow-y-auto border-t border-[#eceff2] py-4 xl:hidden"
                >
                    <form
                        method="GET"
                        action="/oficjalna-baza-pytan-na-prawo-jazdy"
                        class="mb-4 flex overflow-hidden rounded-full border border-[#cfd4db] bg-white"
                    >
                        <label class="sr-only" for="mobile-header-question-search">Szukaj pytań</label>
                        <input
                            id="mobile-header-question-search"
                            name="q"
                            type="search"
                            placeholder="Szukaj pytań, odpowiedzi, przepisów..."
                            class="min-w-0 flex-1 border-0 px-4 py-3 text-sm font-medium text-[#151515] placeholder:text-[#8d94a0] focus:ring-0"
                        >
                        <button
                            type="submit"
                            class="m-1 grid w-11 shrink-0 place-items-center rounded-full bg-[#d01921] text-white"
                            aria-label="Szukaj"
                        >
                            <svg aria-hidden="true" class="h-5 w-5">
                                <use href="/images/site-header-symbols.svg#arrow-right" />
                            </svg>
                        </button>
                    </form>

                    <nav aria-label="Menu mobilne" class="grid sm:grid-cols-2">
                        <component
                            v-for="link in mobilePrimaryLinks"
                            :key="link.href + link.label"
                            :is="navigationComponent(link.href)"
                            :href="link.href"
                            class="flex min-h-12 items-center gap-3 border-b border-[#eef1f4] py-2.5 text-[0.82rem] font-bold uppercase transition"
                            :class="
                                isLinkActive(link)
                                    ? 'text-[#d01921]'
                                    : 'text-[#151515] hover:text-[#d01921]'
                            "
                            @click="closeMobileMenu"
                        >
                            <svg aria-hidden="true" class="h-6 w-6 shrink-0">
                                <use :href="`/images/site-header-symbols.svg#${link.icon}`" />
                            </svg>
                            {{ link.label }}
                        </component>
                    </nav>

                    <div
                        v-if="mobilePanelActions.length || navigation.logout_href"
                        class="mt-4 grid gap-2 sm:grid-cols-2"
                    >
                        <template
                            v-for="action in mobilePanelActions"
                            :key="action.href + action.label"
                        >
                            <button
                                v-if="!isAuthed && isLoginHref(action.href)"
                                type="button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] border border-[#d6d9df] px-4 text-sm font-bold text-[#151515] transition hover:border-[#d01921]"
                                @click="openLoginDrawer"
                            >
                                {{ action.label }}
                            </button>
                            <button
                                v-else-if="!isAuthed && isRegisterHref(action.href)"
                                type="button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] bg-[#d01921] px-4 text-sm font-bold text-white transition hover:bg-[#b9151c]"
                                @click="openRegisterDrawer"
                            >
                                {{ action.label }}
                            </button>
                            <component
                                v-else
                                :is="navigationComponent(action.href)"
                                :href="action.href"
                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] px-4 text-sm font-bold transition"
                                :class="
                                    action.variant === 'primary'
                                        ? 'bg-[#d01921] text-white hover:bg-[#b9151c]'
                                        : 'border border-[#d6d9df] text-[#151515] hover:border-[#d01921]'
                                "
                                @click="closeMobileMenu"
                            >
                                {{ action.label }}
                            </component>
                        </template>

                        <form
                            v-if="navigation.logout_href"
                            @submit.prevent="logout(navigation.logout_href)"
                        >
                            <p
                                v-if="logoutError"
                                class="mb-2 text-xs font-semibold text-red-700"
                                role="alert"
                            >
                                {{ logoutError }}
                            </p>
                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center rounded-[4px] border border-[#d6d9df] px-4 text-sm font-bold text-[#151515] transition hover:border-[#d01921]"
                                :disabled="logoutPreparing"
                            >
                                {{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj' }}
                            </button>
                        </form>
                    </div>

                    <nav
                        aria-label="Informacje"
                        class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-[#eceff2] pt-4"
                    >
                        <component
                            v-for="item in navigation.utility"
                            :key="item.href + item.label"
                            :is="navigationComponent(item.href)"
                            :href="item.href"
                            class="text-[0.76rem] font-bold tracking-[0.02em] transition"
                            :class="
                                isLinkActive(item)
                                    ? 'text-[#d01921]'
                                    : 'text-[#4b5563] hover:text-[#d01921]'
                            "
                            @click="closeMobileMenu"
                        >
                            {{ item.label }}
                        </component>
                    </nav>
                </div>
            </Transition>

            <div class="site-header__desktop">
                <div class="site-header__top">
                    <div class="site-header__brand-cell">
                        <a
                            href="/"
                            class="site-header__wordmark"
                            aria-label="PrawkoNaRaz.pl - Pytania na Prawo Jazdy"
                        >
                            PRAWKO<span>NARAZ</span><small>.PL</small>
                        </a>
                    </div>

                    <div class="site-header__search-cell">
                        <form
                            method="GET"
                            action="/oficjalna-baza-pytan-na-prawo-jazdy"
                            class="site-header__search"
                        >
                            <label class="sr-only" for="header-question-search">Szukaj pytań</label>
                            <span class="site-header__search-leading" aria-hidden="true">
                                <svg class="h-6 w-6">
                                    <use href="/images/site-header-symbols.svg#search" />
                                </svg>
                            </span>
                            <input
                                id="header-question-search"
                                name="q"
                                type="search"
                                :placeholder="props.learningPanel ? 'Szukaj pytań, odpowiedzi, użytkowników...' : 'Szukaj pytań, odpowiedzi, przepisów...'"
                                class="site-header__search-input"
                            >
                            <button
                                type="submit"
                                class="site-header__search-submit"
                                aria-label="Szukaj"
                            >
                                <svg aria-hidden="true" class="h-6 w-6">
                                    <use href="/images/site-header-symbols.svg#arrow-right" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <nav aria-label="Nawigacja główna" class="site-header__nav site-header__nav--inline">
                        <component
                            v-for="item in desktopPrimaryLinks"
                            :key="item.href + item.label"
                            :is="navigationComponent(item.href)"
                            :href="item.href"
                            class="site-header__nav-link"
                            :class="{ 'is-active': isLinkActive(item) }"
                        >
                            <svg aria-hidden="true" class="site-header__nav-icon">
                                <use :href="`/images/site-header-symbols.svg#${item.icon}`" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </component>
                    </nav>

                    <div class="site-header__exam-cell">
                        <a href="/testy-na-prawo-jazdy" class="site-header__exam">
                            <span>Start SpeedRun</span>
                            <svg aria-hidden="true" class="h-5 w-5">
                                <use href="/images/site-header-symbols.svg#arrow-right" />
                            </svg>
                        </a>
                    </div>

                    <div class="relative flex items-center justify-end">
                        <button
                            v-if="isAuthed"
                            type="button"
                            class="site-header__account"
                            :aria-expanded="accountMenuOpen"
                            aria-controls="site-account-menu"
                            @click="accountMenuOpen = !accountMenuOpen"
                        >
                            <span class="site-header__account-avatar" aria-hidden="true">
                                <img
                                    v-if="userAvatarUrl"
                                    :src="userAvatarUrl"
                                    alt=""
                                    referrerpolicy="no-referrer"
                                >
                                <span v-else>{{ userInitials }}</span>
                            </span>
                            <span>Moje konto</span>
                            <svg
                                aria-hidden="true"
                                class="h-4 w-4 transition-transform"
                                :class="accountMenuOpen ? 'rotate-180' : ''"
                            >
                                <use href="/images/site-header-symbols.svg#chevron-down" />
                            </svg>
                        </button>
                        <div v-else class="site-header__auth-actions">
                            <div class="site-header__login" aria-label="Logowanie i rejestracja">
                                <a
                                    href="/login"
                                    class="site-header__login-icon-link"
                                    aria-label="Zaloguj się"
                                    @click.prevent="openLoginDrawer"
                                >
                                    <svg aria-hidden="true" class="site-header__login-icon" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.75" />
                                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.75" />
                                        <path d="M6.8 19.8V19c0-1.4 1.1-2.5 2.5-2.5h5.4c1.4 0 2.5 1.1 2.5 2.5v.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                                    </svg>
                                </a>
                                <span class="site-header__login-copy">
                                    <a href="/login" class="site-header__login-main" @click.prevent="openLoginDrawer">Zaloguj się</a>
                                    <a href="/register" class="site-header__login-sub" @click.prevent="openRegisterDrawer">lub zarejestruj</a>
                                </span>
                                <a
                                    href="/register"
                                    class="site-header__login-chevron-link"
                                    aria-label="Zarejestruj się"
                                    @click.prevent="openRegisterDrawer"
                                >
                                    <svg aria-hidden="true" class="site-header__login-chevron" viewBox="0 0 16 16">
                                        <path d="M4.5 6.5 8 10l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="isAuthed && accountMenuOpen"
                            id="site-account-menu"
                            class="absolute right-0 top-[calc(100%+0.2rem)] z-50 w-64 rounded-[8px] border border-[#dde3ea] bg-white p-2 shadow-[0_20px_48px_rgba(15,23,42,0.16)]"
                        >
                            <div class="mb-2 flex items-center gap-3 border-b border-[#edf0f4] px-3 py-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-[#d4d8de] bg-[#f7f8fa] text-xs font-bold uppercase text-[#10172f]">
                                    <img
                                        v-if="userAvatarUrl"
                                        :src="userAvatarUrl"
                                        alt=""
                                        class="h-full w-full object-cover"
                                        referrerpolicy="no-referrer"
                                    >
                                    <span v-else>{{ userInitials }}</span>
                                </span>
                                <span class="min-w-0 truncate text-sm font-bold text-[#111827]">
                                    {{ userDisplayName }}
                                </span>
                            </div>
                            <component
                                :is="navigationComponent(navigation.learning_href)"
                                :href="navigation.learning_href"
                                class="flex min-h-10 items-center rounded-[5px] px-3 text-sm font-bold text-[#111827] transition hover:bg-[#f6f7f9] hover:text-[#d01921]"
                                @click="closeAccountMenu"
                            >
                                Nauka
                            </component>
                            <component
                                v-for="action in navigation.header_actions"
                                :key="action.href + action.label"
                                :is="navigationComponent(action.href)"
                                :href="action.href"
                                class="flex min-h-10 items-center rounded-[5px] px-3 text-sm font-bold text-[#111827] transition hover:bg-[#f6f7f9] hover:text-[#d01921]"
                                @click="closeAccountMenu"
                            >
                                {{ action.label }}
                            </component>
                            <form
                                v-if="navigation.logout_href"
                                class="mt-2 border-t border-[#edf0f4] pt-2"
                                @submit.prevent="logout(navigation.logout_href)"
                            >
                                <p
                                    v-if="logoutError"
                                    class="px-3 py-2 text-xs font-semibold text-red-700"
                                    role="alert"
                                >
                                    {{ logoutError }}
                                </p>
                                <button
                                    type="submit"
                                    class="flex min-h-10 w-full items-center rounded-[5px] px-3 text-left text-sm font-bold text-[#40454f] transition hover:bg-[#fff4f4] hover:text-[#d01921]"
                                    :disabled="logoutPreparing"
                                >
                                    {{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <AuthTopNavigation v-if="!isAuthed && authDrawerOpen" />

    <LoginDrawer
        v-if="!isAuthed"
        standalone
        :open="loginDrawerOpen"
        :retain-visual="retainsAuthDrawerVisual('login')"
        :hide-visual="hidesAuthDrawerVisual('login')"
        @close="closeAuthDrawers"
        @open-register="openRegisterDrawer"
    />

    <RegisterDrawer
        v-if="!isAuthed"
        standalone
        :open="registerDrawerOpen"
        :categories="registrationCategories"
        :retain-visual="retainsAuthDrawerVisual('register')"
        :hide-visual="hidesAuthDrawerVisual('register')"
        @close="closeAuthDrawers"
        @open-login="openLoginDrawer"
    />
</template>

<style scoped>
.header-panel-enter-active,
.header-panel-leave-active {
    transition:
        opacity 160ms ease,
        transform 160ms ease;
}

.header-panel-enter-from,
.header-panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@media (prefers-reduced-motion: reduce) {
    .header-panel-enter-active,
    .header-panel-leave-active {
        transition: none;
    }
}
</style>
