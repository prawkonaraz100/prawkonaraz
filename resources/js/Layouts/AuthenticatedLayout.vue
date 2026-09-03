<script setup lang="ts">
import MobileBottomNavigation from '@/Components/MobileBottomNavigation.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPathname = computed(() => page.url.split(/[?#]/)[0]);
const isStudySessionPage = computed(() => route().current('study-sessions.*'));
const isRankingPage = computed(() => route().current('session.ranking*'));
const isAnalyticsCategoryPage = computed(() => route().current('analytics.categories.*'));
const isModeratorAccountsPage = computed(() => route().current('moderator.accounts.*'));
const isProfilePage = computed(() => currentPathname.value === '/profile');
const isReviewQueuePage = computed(() => route().current('review-queue.*'));
const isSessionIndexPage = computed(() => route().current('session.index'));
const isRankingMatchPage = computed(() => currentPathname.value === '/nauka/ranking/mecz');
const isTrafficSignLearningMobileShellPage = computed(() => {
    const pathname = currentPathname.value;

    return pathname.startsWith('/nauka/znaki-drogowe');
});
const isTrafficSignLearningPlayerPage = computed(() => currentPathname.value === '/nauka/znaki-drogowe/teraz');

const shellWidthClass = computed(() =>
    isStudySessionPage.value
        ? 'max-w-[108rem]'
        : isRankingPage.value
          ? 'max-w-none md:max-w-[92vw]'
        : isReviewQueuePage.value
          ? 'max-w-[81.75rem]'
        : isModeratorAccountsPage.value || isProfilePage.value
          ? 'max-w-[75rem]'
        : isSessionIndexPage.value
          ? 'max-w-none'
        : isAnalyticsCategoryPage.value
          ? 'max-w-none md:max-w-5xl'
          : 'max-w-5xl',
);

const navShellWidthClass = computed(() =>
    'max-w-[90rem]',
);

const rootBackgroundClass = computed(() =>
    isRankingPage.value
        ? 'bg-[#f7f8fa]'
        : isReviewQueuePage.value
          ? 'bg-white'
        : isModeratorAccountsPage.value || isProfilePage.value
          ? 'bg-white'
        : isSessionIndexPage.value
          ? 'bg-white'
        : isAnalyticsCategoryPage.value
          ? 'bg-white'
          : 'bg-[#fcfcfa]',
);

const headerBackgroundClass = computed(() =>
    isRankingPage.value || isModeratorAccountsPage.value || isProfilePage.value || isReviewQueuePage.value
        ? 'bg-white'
        : 'bg-[#fcfcfa]',
);

const headerBorderClass = computed(() =>
    isReviewQueuePage.value ? 'border-[#eceff2]' : 'border-[#e6e0d5]',
);

const headerContainerClass = computed(() =>
    isStudySessionPage.value
        ? 'px-4 py-3 sm:px-5 lg:px-6'
        : isReviewQueuePage.value
          ? 'px-4 py-10 sm:px-6 lg:px-8'
        : isModeratorAccountsPage.value || isProfilePage.value
          ? 'px-4 py-9 sm:px-6 lg:px-0'
        : isSessionIndexPage.value
          ? 'px-4 py-5 sm:px-6 lg:px-8'
          : 'px-4 py-8 sm:px-6 lg:px-8',
);

const mainContainerClass = computed(() =>
    isStudySessionPage.value
        ? 'px-4 py-4 sm:px-5 lg:px-6'
        : isReviewQueuePage.value
          ? 'px-4 py-10 sm:px-6 lg:px-8'
        : isRankingPage.value
          ? 'px-0 pt-0 pb-5 md:px-4 md:py-10'
        : isModeratorAccountsPage.value || isProfilePage.value
          ? 'px-4 py-10 sm:px-6 lg:px-0'
        : isSessionIndexPage.value
          ? 'px-0 pt-0 pb-5'
        : isAnalyticsCategoryPage.value
          ? 'px-0 pt-0 pb-5 md:px-4 md:py-8'
        : isTrafficSignLearningMobileShellPage.value
          ? 'px-0 pt-0 pb-5 md:px-4 md:py-8'
          : 'px-4 py-8 sm:px-6 lg:px-8',
);

const footerVisibilityClass = computed(() =>
    isSessionIndexPage.value
        ? 'hidden'
        : isReviewQueuePage.value || isRankingPage.value || isProfilePage.value || isAnalyticsCategoryPage.value || isTrafficSignLearningMobileShellPage.value
          ? 'hidden md:block'
          : '',
);

const siteHeaderVisibilityClass = computed(() =>
    isReviewQueuePage.value || isRankingPage.value || isSessionIndexPage.value || isProfilePage.value || isAnalyticsCategoryPage.value || isTrafficSignLearningMobileShellPage.value
        ? 'hidden md:block'
        : '',
);
const pageHeaderVisibilityClass = computed(() =>
    isProfilePage.value || isAnalyticsCategoryPage.value ? 'hidden md:block' : '',
);

const shouldShowMobileBottomNavigation = computed(() => (
    !isModeratorAccountsPage.value
    && !isTrafficSignLearningPlayerPage.value
    && !isRankingMatchPage.value
));
</script>

<template>
    <div class="flex min-h-screen flex-col text-[#1f1d18]" :class="rootBackgroundClass">
        <div :class="siteHeaderVisibilityClass">
            <SiteHeader :shell-width-class="navShellWidthClass" :learning-panel="isSessionIndexPage" />
        </div>

        <header v-if="$slots.header" class="border-b" :class="[headerBackgroundClass, headerBorderClass, pageHeaderVisibilityClass]">
            <div class="mx-auto" :class="[shellWidthClass, headerContainerClass]">
                <slot name="header" />
            </div>
        </header>

        <main class="mx-auto w-full flex-1" :class="[shellWidthClass, mainContainerClass]">
            <slot />
        </main>

        <div :class="footerVisibilityClass">
            <SiteFooter :shell-width-class="navShellWidthClass" />
        </div>

        <div
            v-if="shouldShowMobileBottomNavigation"
            aria-hidden="true"
            class="h-[calc(4.75rem+env(safe-area-inset-bottom))] md:hidden"
        />

        <MobileBottomNavigation v-if="shouldShowMobileBottomNavigation" />
    </div>
</template>
