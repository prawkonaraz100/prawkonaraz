<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{
    title: string;
    backHref: string;
    backLabel?: string;
    subtitle?: string | null;
    contextLabel?: string | null;
    sticky?: boolean;
}>(), {
    backLabel: 'Wróć',
    subtitle: null,
    contextLabel: null,
    sticky: true,
});
</script>

<template>
    <header
        class="mobile-app-bar border-b border-[#e4e7ec] bg-white/95 px-4 pt-[env(safe-area-inset-top)] backdrop-blur"
        :class="sticky ? 'sticky top-0 z-30' : ''"
    >
        <div class="flex h-14 items-center gap-3">
            <Link
                :href="backHref"
                :aria-label="backLabel"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[0.5rem] text-[#344054] transition-colors hover:bg-[#f2f4f7] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] active:bg-[#eaecf0]"
            >
                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                    <path d="m14.5 5-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </Link>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-[1.05rem] font-semibold leading-5 text-[#101828]">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="truncate text-[0.7rem] leading-4 text-[#667085]">
                    {{ subtitle }}
                </p>
            </div>

            <div v-if="$slots.action || contextLabel" class="min-w-0 shrink-0">
                <slot name="action">
                    <span class="inline-flex h-9 max-w-[9rem] items-center truncate rounded-[0.5rem] border border-[#d0d5dd] px-3 text-[0.72rem] font-semibold text-[#475467]">
                        {{ contextLabel }}
                    </span>
                </slot>
            </div>
        </div>
    </header>
</template>

<style scoped>
.mobile-app-bar {
    -webkit-tap-highlight-color: transparent;
}
</style>
