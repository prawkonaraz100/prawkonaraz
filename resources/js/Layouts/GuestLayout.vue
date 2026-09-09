<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';

withDefaults(
    defineProps<{
        cardless?: boolean;
        showFooter?: boolean;
    }>(),
    {
        cardless: false,
        showFooter: true,
    },
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#fcfcfa] text-[#1f1d18]">
        <PublicTopNavigation />

        <main
            v-if="cardless"
            class="w-full flex-1 bg-white"
        >
            <slot />
        </main>

        <main
            v-else
            class="mx-auto flex w-full max-w-6xl flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-8"
        >
            <div
                class="w-full overflow-hidden rounded-[1.35rem] border border-[#e3dccf] bg-white px-6 py-5 shadow-[0_16px_32px_rgba(46,39,26,0.06)] sm:max-w-md"
            >
                <slot />
            </div>
        </main>

        <SiteFooter v-if="showFooter" />
    </div>
</template>
