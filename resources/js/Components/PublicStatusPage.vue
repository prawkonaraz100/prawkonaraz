<script setup lang="ts">
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    title: string;
    lead: string;
    warning?: boolean;
}>();
</script>

<template>
    <Head :title="title">
        <meta head-key="robots" name="robots" content="noindex, nofollow" />
    </Head>
    <div class="public-status-page">
        <PublicTopNavigation class="public-status-page__navigation" />
        <main class="public-status-page__main" aria-labelledby="public-status-title">
            <div class="public-status-page__icon" :class="{ 'public-status-page__icon--warning': warning }" aria-hidden="true">
                <slot name="icon" />
            </div>
            <h1 id="public-status-title" class="public-status-page__title">{{ title }}</h1>
            <p class="public-status-page__lead">{{ lead }}</p>
            <div class="public-status-page__content"><slot /></div>
        </main>
        <SiteFooter />
    </div>
</template>
