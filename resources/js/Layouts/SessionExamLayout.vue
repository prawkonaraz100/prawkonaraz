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
        class="min-h-screen bg-white text-[#111827]"
        :class="lockViewport ? 'overflow-x-hidden xl:h-screen xl:overflow-y-hidden' : ''"
    >
        <main
            class="mx-auto max-w-[84rem] px-3 py-3 sm:px-4 sm:py-4 lg:px-5 lg:pt-0"
            :class="lockViewport ? 'xl:h-[calc(100vh-0.25rem)]' : ''"
        >
            <slot />
        </main>
    </div>
</template>
