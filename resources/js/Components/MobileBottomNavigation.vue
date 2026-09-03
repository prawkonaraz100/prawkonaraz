<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type NavIcon = 'home' | 'training' | 'learning' | 'signs' | 'ranking' | 'profile';

type NavItem = {
    key: string;
    label: string;
    href: string;
    icon: NavIcon;
    active: boolean;
};

const items = computed<NavItem[]>(() => [
    {
        key: 'home',
        label: 'Główna',
        href: route('session.index'),
        icon: 'home',
        active: route().current('session.index') || route().current('analytics.categories.*'),
    },
    {
        key: 'training',
        label: 'Trening',
        href: route('review-queue.index'),
        icon: 'training',
        active: route().current('review-queue.*'),
    },
    {
        key: 'learning',
        label: 'Nauka',
        href: route('study-sessions.current'),
        icon: 'learning',
        active:
            route().current('study-sessions.*') ||
            route().current('session.pjm*'),
    },
    {
        key: 'signs',
        label: 'Znaki',
        href: route('session.traffic-signs'),
        icon: 'signs',
        active: route().current('session.traffic-signs') || route().current('traffic-sign-learning.*'),
    },
    {
        key: 'ranking',
        label: 'Ranking',
        href: route('session.ranking'),
        icon: 'ranking',
        active: route().current('session.ranking*'),
    },
    {
        key: 'profile',
        label: 'Profil',
        href: '/profile',
        icon: 'profile',
        active: route().current('profile.*'),
    },
]);
</script>

<template>
    <nav
        aria-label="Nawigacja aplikacji"
        class="mobile-bottom-navigation fixed inset-x-0 bottom-0 z-40 border-t border-[#eceff2] bg-white/95 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur md:hidden"
    >
        <div
            class="mx-auto grid max-w-[34rem] grid-cols-6 px-1 pb-[max(env(safe-area-inset-bottom),0.25rem)] pt-1"
        >
            <component
                :is="Link"
                v-for="item in items"
                :key="item.key"
                :href="item.href"
                :aria-current="item.active ? 'page' : null"
                class="mobile-bottom-navigation__item flex h-14 min-w-0 flex-col items-center justify-center gap-0.5 text-center leading-none transition-colors duration-150"
                :class="item.active ? 'text-[#ff6a35]' : 'text-[#6f737a]'"
            >
                <span class="grid h-7 w-7 place-items-center">
                    <svg
                        v-if="item.icon === 'home'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M3.75 10.45 12 3.4l8.25 7.05"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.1"
                        />
                        <path
                            d="M6.75 10.3v9.1h3.85v-5.1h2.8v5.1h3.85v-9.1"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.1"
                        />
                    </svg>

                    <svg
                        v-else-if="item.icon === 'training'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M13.3 2.8 5.75 13h5.5l-1.05 8.2 8.05-11.45h-5.6l.65-6.95Z"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                        />
                    </svg>

                    <svg
                        v-else-if="item.icon === 'learning'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="m3.7 8.65 8.3-4.1 8.3 4.1-8.3 4.1-8.3-4.1Z"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                        <path
                            d="M6.85 10.35v4.55c1.55 1.45 3.25 2.15 5.15 2.15s3.6-.7 5.15-2.15v-4.55"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                        <path
                            d="M20.3 8.85v5.45"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                    </svg>

                    <svg
                        v-else-if="item.icon === 'signs'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 3.25 18.6 7v7.4L12 18.15 5.4 14.4V7L12 3.25Z"
                            stroke="currentColor"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                        <path
                            d="M12 7.25v6.9M8.95 9l6.1 3.45"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                    </svg>

                    <svg
                        v-else-if="item.icon === 'ranking'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M8.1 4.3v3.45c0 2.15 1.75 3.9 3.9 3.9s3.9-1.75 3.9-3.9V4.3H8.1Z"
                            stroke="currentColor"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                        <path
                            d="M8.1 6.1H5.35v.95c0 2.25 1.7 4.1 3.88 4.32M15.9 6.1h2.75v.95c0 2.25-1.7 4.1-3.88 4.32M12 11.7v3.45M8.7 19.7h6.6M10 15.15h4l.95 4.55h-5.9l.95-4.55Z"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                    </svg>

                    <svg
                        v-else
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            cx="12"
                            cy="7.45"
                            r="3.5"
                            stroke="currentColor"
                            stroke-width="1.9"
                        />
                        <path
                            d="M4.85 20.05c.7-4.1 3.2-6.15 7.15-6.15s6.45 2.05 7.15 6.15"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.9"
                        />
                    </svg>
                </span>

                <span class="max-w-full whitespace-nowrap text-[0.62rem] font-medium tracking-[0]">
                    {{ item.label }}
                </span>
            </component>
        </div>
    </nav>
</template>

<style scoped>
.mobile-bottom-navigation {
    -webkit-tap-highlight-color: transparent;
}

.mobile-bottom-navigation__item:active {
    transform: translateY(1px);
}

@media (prefers-reduced-motion: reduce) {
    .mobile-bottom-navigation__item {
        transition: none;
    }

    .mobile-bottom-navigation__item:active {
        transform: none;
    }
}
</style>
