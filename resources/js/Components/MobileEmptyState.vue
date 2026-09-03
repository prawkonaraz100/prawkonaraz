<script setup lang="ts">
withDefaults(defineProps<{
    title: string;
    description?: string | null;
    icon?: 'activity' | 'check' | 'chart' | 'inbox';
    tone?: 'neutral' | 'success';
}>(), {
    description: null,
    icon: 'inbox',
    tone: 'neutral',
});
</script>

<template>
    <div class="flex items-start gap-3 border-y border-[#e4e7ec] py-4">
        <span
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
            :class="tone === 'success' ? 'bg-[#ecfdf3] text-[#067647]' : 'bg-[#f2f4f7] text-[#667085]'"
            aria-hidden="true"
        >
            <svg v-if="icon === 'activity'" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                <path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg v-else-if="icon === 'check'" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none">
                <path d="m6.5 12 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg v-else-if="icon === 'chart'" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none">
                <path d="M5 18V13M12 18V6M19 18v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
            <svg v-else class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none">
                <path d="M5 8.5h14v9H5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </span>

        <div class="min-w-0 flex-1 pt-0.5">
            <div role="status">
                <p class="text-[0.8rem] font-semibold leading-5" :class="tone === 'success' ? 'text-[#067647]' : 'text-[#344054]'">
                    {{ title }}
                </p>
                <p v-if="description" class="mt-0.5 text-[0.72rem] leading-5 text-[#667085]">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.default" class="mt-3">
                <slot />
            </div>
        </div>
    </div>
</template>
