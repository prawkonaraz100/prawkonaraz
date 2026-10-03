<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { computed } from 'vue';

type NavIcon = 'home' | 'training' | 'learn' | 'exam' | 'profile';

type NavItem = {
    key: string;
    label: string;
    href: string;
    icon: NavIcon;
    active: boolean;
};

const page = usePage<PageProps>();
const dashboardView = computed(() => new URL(page.url, 'https://prawkonaraz.pl').searchParams.get('widok'));
const isExamSession = computed(() => {
    const session = (page.props as PageProps & { session?: { mode?: string } }).session;

    return session?.mode === 'exam';
});

const items = computed<NavItem[]>(() => [
    {
        key: 'home',
        label: 'Główna',
        href: route('session.index'),
        icon: 'home',
        active: route().current('session.index') && !dashboardView.value,
    },
    {
        key: 'training',
        label: 'Trening',
        href: `${route('session.index')}?widok=trening`,
        icon: 'training',
        active: (route().current('session.index') && dashboardView.value === 'trening')
            || (route().current('study-sessions.*') && !isExamSession.value),
    },
    {
        key: 'learn',
        label: 'Nauka',
        href: `${route('session.index')}?widok=dzialy`,
        icon: 'learn',
        active: (route().current('session.index') && dashboardView.value === 'dzialy')
            || route().current('session.pjm*'),
    },
    {
        key: 'exam',
        label: 'Egzamin',
        href: `${route('session.index')}?widok=testy`,
        icon: 'exam',
        active: (route().current('session.index') && dashboardView.value === 'testy')
            || (route().current('study-sessions.*') && isExamSession.value),
    },
    {
        key: 'profile',
        label: 'Profil',
        href: route('profile.edit'),
        icon: 'profile',
        active: route().current('profile.*'),
    },
]);
</script>

<template>
    <nav
        aria-label="Nawigacja aplikacji"
        class="mobile-bottom-navigation fixed inset-x-0 bottom-0 z-40 px-3 pb-[max(env(safe-area-inset-bottom),0.7rem)] md:hidden"
    >
        <div
            class="mx-auto grid max-w-[30rem] grid-cols-5 rounded-[1.45rem] bg-white/95 px-1.5 pb-1 pt-1.5 shadow-[0_8px_30px_rgba(15,23,42,0.09)] backdrop-blur"
        >
            <component
                :is="Link"
                v-for="item in items"
                :key="item.key"
                :href="item.href"
                :aria-current="item.active ? 'page' : null"
                class="mobile-bottom-navigation__item flex h-14 min-w-0 flex-col items-center justify-center gap-0.5 text-center leading-none transition-colors duration-150"
                :class="item.active ? 'text-[#f1b000]' : 'text-[#687386]'"
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

                    <svg v-else-if="item.icon === 'training'" aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path d="m13.5 2.5-9 11.3h6.4l-.6 7.7 9.2-11.3h-6.4l.4-7.7Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" />
                    </svg>

                    <svg v-else-if="item.icon === 'learn'" aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path d="M12 5.2C9.4 3.5 6.3 3.3 3 4v14.5c3.3-.7 6.4-.5 9 1.2 2.6-1.7 5.7-1.9 9-1.2V4c-3.3-.7-6.4-.5-9 1.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M12 5.2v14.5" stroke="currentColor" stroke-width="1.8" />
                    </svg>

                    <svg
                        v-else-if="item.icon === 'exam'"
                        aria-hidden="true"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path d="M2.6 9.2 12 4l9.4 5.2L12 14.4 2.6 9.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M6 11.1v5.2c3.6 2.8 8.4 2.8 12 0v-5.2M21.4 9.2V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
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

                <span class="max-w-full whitespace-nowrap text-[0.65rem] font-medium tracking-[0]">
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
