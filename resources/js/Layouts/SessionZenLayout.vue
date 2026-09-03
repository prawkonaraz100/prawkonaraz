<script setup lang="ts">
withDefaults(
    defineProps<{
        lockViewport?: boolean;
    }>(),
    {
        lockViewport: false,
    },
);
</script>

<template>
    <div
        class="relative min-h-screen bg-[#fbfcfd] text-[#111111]"
        :class="lockViewport ? 'overflow-x-hidden sm:h-screen sm:overflow-y-hidden' : ''"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none fixed inset-0 hidden lg:block bg-[radial-gradient(circle_at_50%_30%,rgba(255,255,255,0.9)_0%,rgba(255,255,255,0.62)_22%,rgba(248,251,255,0.16)_46%,rgba(248,251,255,0)_68%)]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none fixed inset-x-0 top-0 hidden lg:block h-32 bg-[linear-gradient(180deg,rgba(255,255,255,0.34),rgba(255,255,255,0))]"
        />
        <main
            class="relative mx-auto max-w-[108rem] px-4 pt-4 pb-1 sm:px-5 sm:pb-2 lg:px-6 lg:pt-0 lg:pb-0"
            :class="lockViewport ? 'sm:h-full' : ''"
        >
            <slot />
        </main>
    </div>
</template>
